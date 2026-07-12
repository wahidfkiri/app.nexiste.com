<?php

namespace Vendor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Traits\MultiTenantTrait;
use Vendor\CrmCore\Traits\HasPublicUuid;

class Article extends Model
{
    use SoftDeletes, MultiTenantTrait, HasPublicUuid;

    protected $table = 'stock_articles';

    protected $fillable = [
        'tenant_id', 'user_id', 'supplier_id', 'sku', 'name', 'description', 'unit',
        'purchase_price', 'sale_price', 'min_stock', 'status',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:4',
        'sale_price' => 'decimal:4',
        'min_stock' => 'decimal:4',
        'current_stock' => 'decimal:4',
    ];

    protected $appends = ['current_stock', 'is_low_stock', 'status_label'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'article_id');
    }

    public function deliveryNoteItems()
    {
        return $this->hasMany(DeliveryNoteItem::class, 'article_id');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'article_id');
    }

    public function scopeWithCurrentStock($query)
    {
        $articleTable = $this->getTable();
        $movementTable = (new StockMovement())->getTable();

        return $query->addSelect([
            'current_stock' => StockMovement::query()
                ->selectRaw("COALESCE(SUM(CASE WHEN {$movementTable}.direction = 'in' THEN {$movementTable}.quantity ELSE -{$movementTable}.quantity END), 0)")
                ->whereColumn("{$movementTable}.article_id", "{$articleTable}.id"),
        ]);
    }

    public function getCurrentStockAttribute($value): float
    {
        if ($value !== null) {
            return (float) $value;
        }

        return (float) $this->movements()
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END), 0) as stock_balance")
            ->value('stock_balance');
    }

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->current_stock <= (float) $this->min_stock;
    }

    public function getStatusLabelAttribute(): string
    {
        return (string) (config('stock.article_statuses.' . $this->status) ?? ucfirst((string) $this->status));
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
