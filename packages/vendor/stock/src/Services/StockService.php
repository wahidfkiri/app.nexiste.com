<?php

namespace Vendor\Stock\Services;

use Illuminate\Support\Facades\DB;
use Vendor\Stock\Events\ArticleCreated;
use Vendor\Stock\Events\LowStockThresholdReached;
use Vendor\Stock\Events\StockOrderCreated;
use Vendor\Stock\Events\SupplierCreated;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\DeliveryNote;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\OrderItem;
use Vendor\Stock\Models\StockMovement;
use Vendor\Stock\Models\Supplier;
use Vendor\Stock\Repositories\StockRepository;

class StockService
{
    public function __construct(
        protected ?StockRepository $repo = null,
        protected ?StockMovementService $movementService = null,
        protected ?DeliveryNoteService $deliveryNoteService = null,
    ) {
        $this->repo = $this->repo ?: new StockRepository();
        $this->movementService = $this->movementService ?: app(StockMovementService::class);
        $this->deliveryNoteService = $this->deliveryNoteService ?: app(DeliveryNoteService::class);
    }

    public function stats(): array
    {
        return [
            'articles_total' => Article::count(),
            'articles_low_stock' => $this->movementService->lowStockCount(),
            'suppliers_total' => Supplier::count(),
            'orders_total' => Order::count(),
            'orders_draft' => Order::where('status', 'draft')->count(),
            'orders_received' => Order::where('status', 'received')->count(),
            'delivery_notes_total' => DeliveryNote::count(),
            'movements_total' => StockMovement::count(),
        ];
    }

    public function createArticle(array $data): Article
    {
        $openingStock = (float) ($data['opening_stock'] ?? 0);
        unset($data['opening_stock'], $data['stock_quantity']);

        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['user_id'] = auth()->id();

        return DB::transaction(function () use ($data, $openingStock) {
            $article = Article::create($data);
            $this->movementService->createOpeningBalanceMovement($article, $openingStock);
            $article = $article->fresh('supplier');

            DB::afterCommit(function () use ($article): void {
                event(new ArticleCreated($article, [
                    'created_via' => request()?->expectsJson() ? 'api' : 'web',
                ]));

                $lowStockArticle = $this->movementService
                    ->lowStockArticlesForIds([(int) $article->id])
                    ->first();

                if ($lowStockArticle) {
                    event(new LowStockThresholdReached($lowStockArticle, [
                        'detected_via' => 'article_created',
                    ]));
                }
            });

            return $article;
        });
    }

    public function updateArticle(Article $article, array $data): Article
    {
        unset($data['opening_stock'], $data['stock_quantity']);

        return DB::transaction(function () use ($article, $data) {
            $article->update($data);
            $article = $article->fresh('supplier');

            DB::afterCommit(function () use ($article): void {
                $lowStockArticle = $this->movementService
                    ->lowStockArticlesForIds([(int) $article->id])
                    ->first();

                if ($lowStockArticle) {
                    event(new LowStockThresholdReached($lowStockArticle, [
                        'detected_via' => 'article_updated',
                    ]));
                } else {
                    $this->movementService->expireRecoveredLowStockSuggestions([(int) $article->id]);
                }
            });

            return $article;
        });
    }

    public function createSupplier(array $data): Supplier
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['user_id'] = auth()->id();

        return DB::transaction(function () use ($data) {
            $supplier = Supplier::create($data);

            DB::afterCommit(function () use ($supplier): void {
                event(new SupplierCreated($supplier, [
                    'created_via' => request()?->expectsJson() ? 'api' : 'web',
                ]));
            });

            return $supplier;
        });
    }

    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier->fresh();
    }

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'supplier_id' => $data['supplier_id'],
                'number' => $this->generateOrderNumber(),
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncOrderItems($order, $data['items'] ?? []);
            $this->recalculateOrder($order);
            $order = $order->fresh(['supplier', 'items.article']);

            DB::afterCommit(function () use ($order): void {
                event(new StockOrderCreated($order, [
                    'created_via' => request()?->expectsJson() ? 'api' : 'web',
                ]));
            });

            return $order;
        });
    }

    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'supplier_id' => $data['supplier_id'],
                'reference' => $data['reference'] ?? null,
                'status' => $data['status'] ?? $order->status,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'expected_date' => $data['expected_date'] ?? null,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncOrderItems($order, $data['items'] ?? []);
            $this->recalculateOrder($order);
            return $order->fresh(['supplier', 'items.article']);
        });
    }

    public function receiveOrder(Order $order)
    {
        return $this->deliveryNoteService->createValidatedReceiptFromOrder($order);
    }

    public function syncOrderItems(Order $order, array $items): void
    {
        $order->items()->delete();
        foreach ($items as $index => $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            OrderItem::create([
                'order_id' => $order->id,
                'article_id' => $item['article_id'] ?? null,
                'position' => $index,
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity' => $quantity,
                'unit' => $item['unit'] ?? trans('stock::stock.common.unit_piece'),
                'unit_price' => $unitPrice,
                'total' => $quantity * $unitPrice,
            ]);
        }
    }

    public function recalculateOrder(Order $order): void
    {
        $subtotal = (float) $order->items()->sum(DB::raw('quantity * unit_price'));
        $taxAmount = $subtotal * ((float) $order->tax_rate / 100);
        $order->updateQuietly([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ]);
    }

    public function generateOrderNumber(): string
    {
        $tenantId = auth()->user()->tenant_id;
        $prefix = 'CMD';
        $year = now()->year;
        $last = Order::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('%s-%s-%04d', $prefix, $year, $last + 1);
    }
}
