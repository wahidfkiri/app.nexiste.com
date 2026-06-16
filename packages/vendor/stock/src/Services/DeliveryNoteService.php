<?php

namespace Vendor\Stock\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Vendor\Stock\Events\DeliveryNoteCreated;
use Vendor\Stock\Events\DeliveryNoteValidated;
use Vendor\Stock\Events\LowStockThresholdReached;
use Vendor\Stock\Models\DeliveryNote;
use Vendor\Stock\Models\DeliveryNoteItem;
use Vendor\Stock\Models\Order;

class DeliveryNoteService
{
    public function __construct(protected StockMovementService $movementService) {}

    public function create(array $data): DeliveryNote
    {
        return DB::transaction(function () use ($data) {
            $note = DeliveryNote::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'number' => $this->generateNumber((string) $data['type']),
                'type' => $data['type'],
                'status' => 'draft',
                'supplier_id' => $data['supplier_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'stock_order_id' => $data['stock_order_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($note, $data['items'] ?? []);

            $note = $note->fresh(['supplier', 'client', 'order', 'invoice', 'items.article']);

            DB::afterCommit(function () use ($note): void {
                event(new DeliveryNoteCreated($note, [
                    'created_via' => request()?->expectsJson() ? 'api' : 'web',
                ]));
            });

            return $note;
        });
    }

    public function update(DeliveryNote $note, array $data): DeliveryNote
    {
        if ($note->status !== 'draft') {
            throw new RuntimeException(trans('stock::stock.errors.delivery_note_locked'));
        }

        return DB::transaction(function () use ($note, $data) {
            $note->update([
                'type' => $data['type'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'stock_order_id' => $data['stock_order_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'issue_date' => $data['issue_date'] ?? $note->issue_date,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($note, $data['items'] ?? []);

            return $note->fresh(['supplier', 'client', 'order', 'invoice', 'items.article']);
        });
    }

    public function delete(DeliveryNote $note): void
    {
        if ($note->status !== 'draft') {
            throw new RuntimeException(trans('stock::stock.errors.delivery_note_delete_locked'));
        }

        $note->delete();
    }

    public function validate(DeliveryNote $note): DeliveryNote
    {
        if ($note->status !== 'draft') {
            throw new RuntimeException(trans('stock::stock.errors.delivery_note_cannot_validate'));
        }

        $note->loadMissing('items.article');

        if ($note->items->isEmpty()) {
            throw new RuntimeException(trans('stock::stock.errors.delivery_note_requires_items'));
        }

        return DB::transaction(function () use ($note) {
            $this->movementService->assertAvailability($note);

            $note->update([
                'status' => 'validated',
                'validated_at' => now(),
                'validated_by' => auth()->id(),
                'issue_date' => $note->issue_date ?: now()->toDateString(),
            ]);

            $this->movementService->postDeliveryNote($note->fresh('items'));

            if ($note->stock_order_id && $note->type === 'in') {
                $note->order?->update([
                    'status' => 'received',
                    'received_date' => now()->toDateString(),
                ]);
            }

            $note = $note->fresh(['supplier', 'client', 'order', 'invoice', 'items.article', 'movements.article']);
            $lowStockArticles = $this->movementService
                ->lowStockArticlesForIds($note->items->pluck('article_id')->all())
                ->values();

            $articleIds = $note->items->pluck('article_id')->all();

            DB::afterCommit(function () use ($note, $lowStockArticles, $articleIds): void {
                event(new DeliveryNoteValidated($note, [
                    'validated_via' => request()?->expectsJson() ? 'api' : 'web',
                    'low_stock_articles' => $lowStockArticles->map(fn ($article) => [
                        'id' => (int) $article->id,
                        'name' => (string) $article->name,
                        'sku' => (string) ($article->sku ?? ''),
                        'current_stock' => (float) $article->current_stock,
                        'min_stock' => (float) $article->min_stock,
                    ])->all(),
                ]));

                $this->movementService->expireRecoveredLowStockSuggestions($articleIds);

                foreach ($lowStockArticles as $article) {
                    event(new LowStockThresholdReached($article, [
                        'detected_via' => 'delivery_note_validated',
                        'delivery_note_id' => (int) $note->id,
                        'delivery_note_number' => (string) $note->number,
                    ]));
                }
            });

            return $note;
        });
    }

    public function cancel(DeliveryNote $note): DeliveryNote
    {
        if ($note->status === 'cancelled') {
            return $note->fresh(['items.article', 'movements.article']);
        }

        return DB::transaction(function () use ($note) {
            $previousStatus = $note->status;

            $note->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]);

            if ($previousStatus === 'validated') {
                $this->movementService->reverseDeliveryNote($note->fresh('items'));
            }

            if ($note->stock_order_id && $note->type === 'in' && $note->order) {
                $hasOtherValidatedReceipts = DeliveryNote::query()
                    ->where('stock_order_id', $note->stock_order_id)
                    ->where('type', 'in')
                    ->where('status', 'validated')
                    ->whereKeyNot($note->id)
                    ->exists();

                $note->order->update([
                    'status' => $hasOtherValidatedReceipts ? 'received' : 'ordered',
                    'received_date' => $hasOtherValidatedReceipts ? ($note->order->received_date ?: now()->toDateString()) : null,
                ]);
            }

            $note = $note->fresh(['supplier', 'client', 'order', 'invoice', 'items.article', 'movements.article']);
            $articleIds = $note->items->pluck('article_id')->all();

            DB::afterCommit(function () use ($articleIds): void {
                $this->movementService->expireRecoveredLowStockSuggestions($articleIds);
            });

            return $note;
        });
    }

    public function createValidatedReceiptFromOrder(Order $order): DeliveryNote
    {
        $order->loadMissing('supplier', 'items.article');

        if ($order->status === 'cancelled') {
            throw new RuntimeException(trans('stock::stock.errors.order_cancelled_cannot_receive'));
        }

        $existing = DeliveryNote::query()
            ->where('stock_order_id', $order->id)
            ->where('type', 'in')
            ->where('status', 'validated')
            ->first();

        if ($existing) {
            return $existing->load(['supplier', 'order', 'items.article']);
        }

        $note = $this->create([
            'type' => 'in',
            'supplier_id' => $order->supplier_id,
            'stock_order_id' => $order->id,
            'reference' => $order->number,
            'issue_date' => now()->toDateString(),
            'notes' => trans('stock::stock.errors.receipt_generated_from_order'),
            'items' => $order->items->map(fn ($item) => [
                'article_id' => $item->article_id,
                'stock_order_item_id' => $item->id,
                'sku' => $item->article?->sku,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
            ])->all(),
        ]);

        return $this->validate($note);
    }

    protected function syncItems(DeliveryNote $note, array $items): void
    {
        $note->items()->delete();

        foreach ($items as $index => $item) {
            DeliveryNoteItem::create([
                'delivery_note_id' => $note->id,
                'article_id' => $item['article_id'] ?? null,
                'stock_order_item_id' => $item['stock_order_item_id'] ?? null,
                'position' => $index,
                'sku' => $item['sku'] ?? null,
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity' => (float) $item['quantity'],
                'unit' => $item['unit'] ?? trans('stock::stock.common.unit_piece'),
            ]);
        }
    }

    protected function generateNumber(string $type): string
    {
        $tenantId = auth()->user()->tenant_id;
        $prefix = $type === 'in' ? 'BLI' : 'BLO';
        $year = now()->year;

        $count = DeliveryNote::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('%s-%s-%04d', $prefix, $year, $count + 1);
    }
}
