<?php

namespace Vendor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Traits\MultiTenantTrait;
use Vendor\CrmCore\Traits\HasPublicUuid;

class Order extends Model
{
    use SoftDeletes, MultiTenantTrait, HasPublicUuid;

    protected $table = 'stock_orders';

    protected $appends = ['status_label'];

    protected $fillable = [
        'tenant_id', 'user_id', 'supplier_id', 'number', 'reference', 'status',
        'order_date', 'expected_date', 'received_date',
        'subtotal', 'tax_rate', 'tax_amount', 'total', 'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id')->orderBy('position');
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class, 'stock_order_id')->orderByDesc('created_at');
    }

    public function getStatusLabelAttribute(): string
    {
        return (string) (config('stock.order_statuses.' . $this->status) ?? ucfirst((string) $this->status));
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%")
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$term}%"));
        });
    }
}
