<?php

namespace Vendor\Stock\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Vendor\Stock\Models\StockMovement;

class StockMovementsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return StockMovement::query()
            ->with(['article', 'deliveryNote'])
            ->orderByDesc('happened_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (StockMovement $movement) {
                return [
                    'date' => optional($movement->happened_at)->format('Y-m-d H:i:s'),
                    'article' => $movement->article?->name,
                    'sku' => $movement->article?->sku,
                    'movement_type' => $movement->movement_type,
                    'direction' => $movement->direction,
                    'quantity' => $movement->quantity,
                    'signed_quantity' => $movement->signed_quantity,
                    'reference' => $movement->reference,
                    'delivery_note' => $movement->deliveryNote?->number,
                    'reason' => $movement->reason,
                    'notes' => $movement->notes,
                ];
            });
    }

    public function headings(): array
    {
        return trans('stock::stock.exports.movements');
    }
}
