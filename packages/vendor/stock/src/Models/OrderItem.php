<?php

namespace Vendor\Stock\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'stock_order_items';

    protected $fillable = [
        'order_id', 'article_id', 'position', 'name', 'description',
        'quantity', 'unit', 'unit_price', 'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function deliveryNoteItems()
    {
        return $this->hasMany(DeliveryNoteItem::class, 'stock_order_item_id');
    }
}
