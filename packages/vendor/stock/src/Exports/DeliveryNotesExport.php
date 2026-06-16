<?php

namespace Vendor\Stock\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Vendor\Stock\Models\DeliveryNote;

class DeliveryNotesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return DeliveryNote::query()
            ->with(['supplier', 'client', 'items'])
            ->orderByDesc('issue_date')
            ->get()
            ->map(function (DeliveryNote $note) {
                return [
                    'number' => $note->number,
                    'type' => $note->type,
                    'status' => $note->status,
                    'issue_date' => optional($note->issue_date)->format('Y-m-d'),
                    'supplier' => $note->supplier?->name,
                    'client' => $note->client?->company_name,
                    'reference' => $note->reference,
                    'items_count' => $note->items->count(),
                    'total_quantity' => $note->items->sum('quantity'),
                    'notes' => $note->notes,
                ];
            });
    }

    public function headings(): array
    {
        return trans('stock::stock.exports.delivery_notes');
    }
}
