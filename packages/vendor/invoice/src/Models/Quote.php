<?php

namespace Vendor\Invoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Vendor\CrmCore\Models\Tenant;
use Vendor\CrmCore\Traits\MultiTenantTrait;
use Vendor\Client\Models\Client;

class Quote extends Model
{
    use SoftDeletes, MultiTenantTrait;

    protected $table = 'quotes';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'client_id',
        'stock_order_id',
        'number',
        'reference',
        'status',
        'currency',
        'exchange_rate',
        'issue_date',
        'valid_until',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'withholding_tax_rate',
        'withholding_tax_amount',
        'total',
        'notes',
        'terms',
        'footer',
        'internal_notes',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'declined_at',
        'decline_reason',
        'converted_to_invoice_id',
        'custom_fields',
    ];

    protected $casts = [
        'exchange_rate'          => 'decimal:6',
        'subtotal'               => 'decimal:2',
        'discount_value'         => 'decimal:2',
        'discount_amount'        => 'decimal:2',
        'tax_rate'               => 'decimal:2',
        'tax_amount'             => 'decimal:2',
        'withholding_tax_rate'   => 'decimal:2',
        'withholding_tax_amount' => 'decimal:2',
        'total'                  => 'decimal:2',
        'issue_date'             => 'date',
        'valid_until'            => 'date',
        'sent_at'                => 'datetime',
        'viewed_at'              => 'datetime',
        'accepted_at'            => 'datetime',
        'declined_at'            => 'datetime',
        'custom_fields'          => 'array',
    ];

    // ===================== RELATIONS =====================

    public function tenant(): BelongsTo  { return $this->belongsTo(Tenant::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class, 'user_id'); }
    public function client(): BelongsTo  { return $this->belongsTo(Client::class); }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('position');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'quote_id');
    }

    public function stockOrder(): BelongsTo
    {
        return $this->belongsTo(\Vendor\Stock\Models\Order::class, 'stock_order_id');
    }

    // ===================== SCOPES =====================

    public function scopeByStatus($query, string $status) { return $query->where('status', $status); }
    public function scopeExpired($query) { return $query->where('status', 'sent')->whereDate('valid_until', '<', now()); }
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
              ->orWhere('reference', 'like', "%{$term}%")
              ->orWhereHas('client', fn($c) =>
                  $c->where('company_name', 'like', "%{$term}%")
              );
        });
    }

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['status']))    $query->byStatus($filters['status']);
        if (!empty($filters['client_id'])) $query->where('client_id', $filters['client_id']);
        if (!empty($filters['search']))    $query->search($filters['search']);
        if (!empty($filters['date_from'])) $query->whereDate('issue_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('issue_date', '<=', $filters['date_to']);
        $query->orderBy($filters['sort'] ?? 'issue_date', $filters['order'] ?? 'desc');
        return $query;
    }

    // ===================== ACCESSORS =====================

    public function getStatusLabelAttribute(): string { return config("invoice.quote_statuses.{$this->status}", $this->status); }
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft'    => 'secondary',
            'sent'     => 'info',
            'viewed'   => 'info',
            'accepted' => 'success',
            'declined' => 'danger',
            'expired'  => 'warning',
            default    => 'secondary',
        };
    }
    public function getIsExpiredAttribute(): bool { return $this->valid_until && $this->valid_until->isPast() && !in_array($this->status, ['accepted','declined']); }
    public function getIsConvertedAttribute(): bool { return !is_null($this->converted_to_invoice_id); }

    // ===================== METHODS =====================

    public function accept(): void { $this->update(['status' => 'accepted', 'accepted_at' => now()]); }
    public function decline(string $reason = ''): void { $this->update(['status' => 'declined', 'declined_at' => now(), 'decline_reason' => $reason]); }

    public function hasConvertibleItems(): bool
    {
        if ($this->relationLoaded('items')) {
            return $this->items->isNotEmpty();
        }

        return $this->items()->exists();
    }

    public function canBeConvertedToInvoice(): bool
    {
        if ($this->is_converted) {
            return false;
        }

        if (in_array($this->status, ['declined'], true)) {
            return false;
        }

        return $this->hasConvertibleItems();
    }

    public function conversionBlockedReason(): string
    {
        if ($this->is_converted) {
            return 'Ce devis a deja ete converti en facture.';
        }

        if (in_array($this->status, ['declined'], true)) {
            return 'Un devis refuse ne peut pas etre converti en facture.';
        }

        if (!$this->hasConvertibleItems()) {
            return 'Ce devis brouillon doit etre complete et enregistre avec au moins une ligne avant conversion en facture.';
        }

        return '';
    }
}
