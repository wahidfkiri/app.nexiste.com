<?php

namespace Vendor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Lang;
use Vendor\Client\Models\Client;
use Vendor\CrmCore\Traits\MultiTenantTrait;
use Vendor\CrmCore\Traits\HasPublicUuid;
use Vendor\Invoice\Models\Invoice;

class DeliveryNote extends Model
{
    use SoftDeletes, MultiTenantTrait, HasPublicUuid;

    protected $table = 'stock_delivery_notes';

    protected $fillable = [
        'tenant_id', 'user_id', 'number', 'type', 'status', 'supplier_id', 'client_id',
        'stock_order_id', 'invoice_id', 'reference', 'issue_date', 'validated_at',
        'validated_by', 'cancelled_at', 'cancelled_by', 'notes', 'meta',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'validated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'meta' => 'array',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'stock_order_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryNoteItem::class, 'delivery_note_id')->orderBy('position');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'delivery_note_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function validator()
    {
        return $this->belongsTo(\App\Models\User::class, 'validated_by');
    }

    public function canceller()
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by');
    }

    public function getTypeLabelAttribute(): string
    {
        $key = 'stock::stock.labels.delivery_note_types.' . $this->type;

        return Lang::has($key) ? trans($key) : ucfirst((string) $this->type);
    }

    public function getStatusLabelAttribute(): string
    {
        $key = 'stock::stock.labels.delivery_note_statuses.' . $this->status;

        return Lang::has($key) ? trans($key) : ucfirst((string) $this->status);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%")
                ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$term}%"))
                ->orWhereHas('client', fn ($client) => $client->where('company_name', 'like', "%{$term}%"));
        });
    }
}
