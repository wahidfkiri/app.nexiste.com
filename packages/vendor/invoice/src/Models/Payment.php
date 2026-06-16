<?php

namespace Vendor\Invoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class Payment extends Model
{
    use SoftDeletes, MultiTenantTrait;

    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'user_id',
        'amount',
        'currency',
        'exchange_rate',
        'amount_base_currency',
        'payment_date',
        'payment_method',
        'reference',
        'bank_name',
        'bank_account',
        'notes',
        'attachment',
    ];

    protected $casts = [
        'amount'               => 'decimal:2',
        'exchange_rate'        => 'decimal:6',
        'amount_base_currency' => 'decimal:2',
        'payment_date'         => 'date',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class, 'user_id'); }

    public function getMethodLabelAttribute(): string
    {
        return config("invoice.payment_methods.{$this->payment_method}", $this->payment_method);
    }

    protected static function booted(): void
    {
        // After creating a payment, update invoice amounts
        static::created(function (Payment $payment) {
            $invoice = $payment->invoice;
            $totalPaid = $invoice->payments()->sum('amount');
            $status = match(true) {
                $totalPaid >= $invoice->total => 'paid',
                $totalPaid > 0               => 'partial',
                default                      => $invoice->status,
            };
            $invoice->update([
                'amount_paid' => $totalPaid,
                'amount_due'  => max(0, $invoice->total - $totalPaid),
                'status'      => $status,
                'payment_date'=> $status === 'paid' ? $payment->payment_date : null,
            ]);
        });

        static::deleted(function (Payment $payment) {
            $invoice = $payment->invoice;
            $totalPaid = $invoice->payments()->sum('amount');
            $invoice->update([
                'amount_paid' => $totalPaid,
                'amount_due'  => max(0, $invoice->total - $totalPaid),
                'status'      => $totalPaid > 0 ? 'partial' : 'sent',
                'payment_date'=> null,
            ]);
        });
    }
}
