<?php

namespace Vendor\Stock\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNoteItem extends Model
{
    protected $table = 'stock_delivery_note_items';

    protected $fillable = [
        'delivery_note_id', 'article_id', 'stock_order_item_id', 'position', 'sku',
        'name', 'description', 'quantity', 'unit', 'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'meta' => 'array',
    ];

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class, 'delivery_note_id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'stock_order_item_id');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'delivery_note_item_id');
    }
}