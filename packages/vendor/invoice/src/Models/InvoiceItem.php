<?php

namespace Vendor\Invoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id', 'article_id', 'position', 'description', 'reference',
        'quantity', 'unit', 'unit_price', 'discount_type', 'discount_value',
        'discount_amount', 'tax_rate', 'tax_amount', 'total',
    ];

    protected $casts = [
        'quantity'        => 'decimal:4',
        'unit_price'      => 'decimal:4',
        'discount_value'  => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(\Vendor\Stock\Models\Article::class, 'article_id');
    }

    public function calculateTotal(): float
    {
        $lineTotal = $this->quantity * $this->unit_price;
        $discountAmount = match ($this->discount_type) {
            'percent' => $lineTotal * ($this->discount_value / 100),
            'fixed' => (float) $this->discount_value,
            default => 0,
        };

        $afterDiscount = $lineTotal - $discountAmount;
        $taxAmount = $afterDiscount * ($this->tax_rate / 100);

        $this->discount_amount = $discountAmount;
        $this->tax_amount = $taxAmount;
        $this->total = $afterDiscount + $taxAmount;

        return $this->total;
    }
}
