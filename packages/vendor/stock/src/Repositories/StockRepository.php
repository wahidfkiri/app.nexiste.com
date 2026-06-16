<?php

namespace Vendor\Stock\Repositories;

use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\Supplier;

class StockRepository
{
    public function articleQuery()
    {
        return Article::query()->with('supplier');
    }

    public function supplierQuery()
    {
        return Supplier::query();
    }

    public function orderQuery()
    {
        return Order::query()->with(['supplier', 'items.article']);
    }
}
