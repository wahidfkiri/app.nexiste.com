<?php

namespace Vendor\Invoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $table = 'quote_items';

    protected $fillable = [
        'quote_id', 'article_id', 'position', 'description', 'reference',
        'quantity', 'unit', 'unit_price', 'discount_type', 'discount_value',
        'discount_amount', 'tax_rate', 'tax_amount', 'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(\Vendor\Stock\Models\Article::class, 'article_id');
    }
}
