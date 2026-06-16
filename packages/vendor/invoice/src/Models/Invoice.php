<?php

namespace Vendor\Invoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Vendor\CrmCore\Models\Tenant;
use Vendor\CrmCore\Traits\MultiTenantTrait;
use Vendor\Client\Models\Client;

class Invoice extends Model
{
    use SoftDeletes, MultiTenantTrait;

    protected $table = 'invoices';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'client_id',
        'quote_id',
        'stock_order_id',
        'number',
        'reference',
        'status',
        'currency',
        'exchange_rate',
        'issue_date',
        'due_date',
        'payment_date',
        'payment_method',
        'payment_terms',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'withholding_tax_rate',
        'withholding_tax_amount',
        'total',
        'amount_paid',
        'amount_due',
        'notes',
        'terms',
        'footer',
        'internal_notes',
        'sent_at',
        'viewed_at',
        'reminder_count',
        'last_reminder_at',
        'custom_fields',
    ];

    protected $casts = [
        'exchange_rate'           => 'decimal:6',
        'subtotal'                => 'decimal:2',
        'discount_value'          => 'decimal:2',
        'discount_amount'         => 'decimal:2',
        'tax_rate'                => 'decimal:2',
        'tax_amount'              => 'decimal:2',
        'withholding_tax_rate'    => 'decimal:2',
        'withholding_tax_amount'  => 'decimal:2',
        'total'                   => 'decimal:2',
        'amount_paid'             => 'decimal:2',
        'amount_due'              => 'decimal:2',
        'issue_date'              => 'date',
        'due_date'                => 'date',
        'payment_date'            => 'date',
        'sent_at'                 => 'datetime',
        'viewed_at'               => 'datetime',
        'last_reminder_at'        => 'datetime',
        'custom_fields'           => 'array',
    ];

    // ===================== RELATIONS =====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function stockOrder(): BelongsTo
    {
        return $this->belongsTo(\Vendor\Stock\Models\Order::class, 'stock_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ===================== SCOPES =====================

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'sent')
                     ->whereDate('due_date', '<', now());
    }

    public function scopeByClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    public function scopeDateRange($query, $from, $to)
    {
        if ($from) $query->whereDate('issue_date', '>=', $from);
        if ($to)   $query->whereDate('issue_date', '<=', $to);
        return $query;
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
              ->orWhere('reference', 'like', "%{$term}%")
              ->orWhereHas('client', fn($c) =>
                  $c->where('company_name', 'like', "%{$term}%")
                    ->orWhere('contact_name', 'like', "%{$term}%")
              );
        });
    }

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['status']))     $query->byStatus($filters['status']);
        if (!empty($filters['client_id']))  $query->byClient($filters['client_id']);
        if (!empty($filters['currency']))   $query->byCurrency($filters['currency']);
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $query->dateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        }
        if (!empty($filters['overdue']))    $query->overdue();
        if (!empty($filters['search']))     $query->search($filters['search']);

        $sort  = $filters['sort']  ?? 'issue_date';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        return $query;
    }

    // ===================== ACCESSORS =====================

    public function getStatusLabelAttribute(): string
    {
        return config("invoice.invoice_statuses.{$this->status}", $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft'     => 'secondary',
            'sent'      => 'info',
            'viewed'    => 'info',
            'partial'   => 'warning',
            'paid'      => 'success',
            'overdue'   => 'danger',
            'cancelled' => 'dark',
            'refunded'  => 'warning',
            default     => 'secondary',
        };
    }

    public function getCurrencySymbolAttribute(): string
    {
        return config("invoice.currencies.{$this->currency}.symbol", $this->currency);
    }

    public function getIsOverdueAttribute(): bool
    {
        return !in_array($this->status, ['paid','cancelled','refunded'])
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->total <= 0) return 0;
        return (int) min(100, round($this->amount_paid / $this->total * 100));
    }

    // ===================== METHODS =====================

    public function markAsSent(): void
    {
        $this->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function markAsViewed(): void
    {
        if ($this->status === 'sent') {
            $this->update(['status' => 'viewed', 'viewed_at' => now()]);
        }
    }

    public function recalculate(): void
    {
        $subtotal = $this->items()->sum(\DB::raw('quantity * unit_price - discount_amount'));

        $discountAmount = match($this->discount_type) {
            'percent' => $subtotal * ($this->discount_value / 100),
            'fixed'   => (float) $this->discount_value,
            default   => 0,
        };

        $taxableAmount        = $subtotal - $discountAmount;
        $taxAmount            = $taxableAmount * ($this->tax_rate / 100);
        $withholdingAmount    = $taxableAmount * ($this->withholding_tax_rate / 100);
        $total                = $taxableAmount + $taxAmount;
        $amountDue            = $total - $this->amount_paid;

        $this->update(compact(
            'subtotal','discountAmount','taxAmount',
            'withholdingAmount','total','amountDue'
        ) + ['discount_amount' => $discountAmount, 'tax_amount' => $taxAmount,
             'withholding_tax_amount' => $withholdingAmount, 'amount_due' => $amountDue]);
    }
}
