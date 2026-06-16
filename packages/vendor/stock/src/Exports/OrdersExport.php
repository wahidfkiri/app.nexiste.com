<?php

namespace Vendor\Stock\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Vendor\Stock\Models\Order;

class OrdersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Order::with('supplier')->get()->map(function ($order) {
            return [
                $order->id,
                $order->number,
                $order->supplier?->name,
                $order->status,
                optional($order->order_date)->format('Y-m-d'),
                optional($order->expected_date)->format('Y-m-d'),
                $order->total,
            ];
        });
    }

    public function headings(): array
    {
        return trans('stock::stock.exports.orders');
    }
}
