<?php

namespace Vendor\Stock\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\DeliveryNote;
use Vendor\Stock\Models\StockMovement;

class StockMovementService
{
    public function applyCurrentStock(Builder $query, string $alias = 'current_stock'): Builder
    {
        $articleTable = $query->getModel()->getTable();
        $movementTable = (new StockMovement())->getTable();

        return $query->addSelect([
            $alias => StockMovement::query()
                ->selectRaw("COALESCE(SUM(CASE WHEN {$movementTable}.direction = 'in' THEN {$movementTable}.quantity ELSE -{$movementTable}.quantity END), 0)")
                ->whereColumn("{$movementTable}.article_id", "{$articleTable}.id"),
        ]);
    }

    public function currentStockForArticle(Article $article): float
    {
        return $this->currentStockForArticleId((int) $article->id);
    }

    public function currentStockForArticleId(int $articleId): float
    {
        return (float) StockMovement::query()
            ->where('article_id', $articleId)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END), 0) as stock_balance")
            ->value('stock_balance');
    }

    public function createOpeningBalanceMovement(Article $article, float $quantity, ?string $reason = null, ?Carbon $happenedAt = null): ?StockMovement
    {
        if ($quantity <= 0) {
            return null;
        }

        return StockMovement::create([
            'tenant_id' => $article->tenant_id,
            'user_id' => auth()->id() ?: $article->user_id,
            'article_id' => $article->id,
            'source_type' => 'manual_opening_balance',
            'source_id' => $article->id,
            'movement_type' => 'opening_balance',
            'direction' => 'in',
            'quantity' => $quantity,
            'unit' => $article->unit,
            'reference' => 'OPENING-STOCK',
            'reason' => $reason ?: trans('stock::stock.reasons.opening_stock_declared'),
            'happened_at' => $happenedAt ?: now(),
        ]);
    }

    public function assertAvailability(DeliveryNote $note): void
    {
        if ($note->type !== 'out') {
            return;
        }

        $requiredByArticle = $note->items
            ->filter(fn ($item) => (int) $item->article_id > 0)
            ->groupBy('article_id')
            ->map(fn ($rows) => (float) $rows->sum('quantity'));

        foreach ($requiredByArticle as $articleId => $requiredQty) {
            $currentStock = $this->currentStockForArticleId((int) $articleId);
            if ($currentStock < $requiredQty) {
                $article = Article::query()->find($articleId);
                throw new RuntimeException(trans('stock::stock.errors.stock_insufficient', [
                    'article' => $article?->name ?: ('Article #' . $articleId),
                    'available' => number_format($currentStock, 4, '.', ''),
                    'required' => number_format($requiredQty, 4, '.', ''),
                ]));
            }
        }
    }

    public function postDeliveryNote(DeliveryNote $note): void
    {
        $direction = $note->type === 'in' ? 'in' : 'out';
        $movementType = $note->type === 'in' ? 'delivery_note_in' : 'delivery_note_out';
        $happenedAt = $note->validated_at ?: $note->issue_date ?: now();

        foreach ($note->items as $item) {
            if (!$item->article_id) {
                continue;
            }

            StockMovement::create([
                'tenant_id' => $note->tenant_id,
                'user_id' => auth()->id() ?: $note->user_id,
                'article_id' => $item->article_id,
                'delivery_note_id' => $note->id,
                'delivery_note_item_id' => $item->id,
                'source_type' => DeliveryNote::class,
                'source_id' => $note->id,
                'movement_type' => $movementType,
                'direction' => $direction,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'reference' => $note->number,
                'reason' => trans('stock::stock.reasons.posted_from_note', [
                    'type' => $note->type_label,
                ]),
                'happened_at' => $happenedAt,
                'notes' => $note->notes,
                'meta' => [
                    'delivery_note_type' => $note->type,
                    'delivery_note_status' => $note->status,
                ],
            ]);
        }
    }

    public function reverseDeliveryNote(DeliveryNote $note): void
    {
        $existingReverseCount = StockMovement::query()
            ->where('delivery_note_id', $note->id)
            ->where('movement_type', 'delivery_note_reversal')
            ->count();

        if ($existingReverseCount > 0) {
            return;
        }

        $happenedAt = $note->cancelled_at ?: now();

        foreach ($note->items as $item) {
            if (!$item->article_id) {
                continue;
            }

            StockMovement::create([
                'tenant_id' => $note->tenant_id,
                'user_id' => auth()->id() ?: $note->user_id,
                'article_id' => $item->article_id,
                'delivery_note_id' => $note->id,
                'delivery_note_item_id' => $item->id,
                'source_type' => DeliveryNote::class,
                'source_id' => $note->id,
                'movement_type' => 'delivery_note_reversal',
                'direction' => $note->type === 'in' ? 'out' : 'in',
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'reference' => $note->number,
                'reason' => trans('stock::stock.reasons.reversal_after_cancellation', [
                    'type' => $note->type_label,
                ]),
                'happened_at' => $happenedAt,
                'notes' => $note->notes,
                'meta' => [
                    'reversal_of_delivery_note_id' => $note->id,
                    'delivery_note_type' => $note->type,
                ],
            ]);
        }
    }

    public function historyQuery(array $filters = []): Builder
    {
        return StockMovement::query()
            ->with(['article', 'deliveryNote'])
            ->when(!empty($filters['article_id']), fn ($query) => $query->where('article_id', (int) $filters['article_id']))
            ->when(!empty($filters['direction']), fn ($query) => $query->where('direction', (string) $filters['direction']))
            ->when(!empty($filters['movement_type']), fn ($query) => $query->where('movement_type', (string) $filters['movement_type']))
            ->when(!empty($filters['reference']), fn ($query) => $query->where('reference', 'like', '%' . $filters['reference'] . '%'))
            ->when(!empty($filters['date_from']), fn ($query) => $query->whereDate('happened_at', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($query) => $query->whereDate('happened_at', '<=', $filters['date_to']))
            ->orderByDesc('happened_at')
            ->orderByDesc('id');
    }

    public function lowStockCount(): int
    {
        $query = Article::query()->select(['stock_articles.id', 'stock_articles.min_stock']);
        $this->applyCurrentStock($query);

        return DB::query()
            ->fromSub($query, 'article_stocks')
            ->where('min_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->count();
    }

    public function lowStockArticlesForIds(array $articleIds): Collection
    {
        $articleIds = collect($articleIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($articleIds->isEmpty()) {
            return collect();
        }

        $query = Article::query()
            ->with('supplier')
            ->select(['stock_articles.*']);

        $this->applyCurrentStock($query);

        return $query
            ->whereIn('stock_articles.id', $articleIds->all())
            ->get()
            ->filter(fn (Article $article) => (float) $article->min_stock > 0 && $article->is_low_stock);
    }

    public function expireRecoveredLowStockSuggestions(array $articleIds): void
    {
        $articleIds = collect($articleIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($articleIds->isEmpty()) {
            return;
        }

        $lowStockIds = $this->lowStockArticlesForIds($articleIds->all())
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        AutomationSuggestion::query()
            ->where('source_event', 'stock_low_threshold_reached')
            ->where('source_type', Article::class)
            ->whereIn('source_id', $articleIds->map(fn ($id) => (string) $id)->all())
            ->where('status', AutomationSuggestion::STATUS_PENDING)
            ->when(
                !empty($lowStockIds),
                fn ($query) => $query->whereNotIn('source_id', $lowStockIds)
            )
            ->update([
                'status' => AutomationSuggestion::STATUS_EXPIRED,
                'pending_dedupe_key' => null,
                'rejection_reason' => 'Stock revenu au-dessus du seuil minimum.',
            ]);
    }
}
