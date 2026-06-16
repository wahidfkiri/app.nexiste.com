<?php

namespace Vendor\Stock\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Vendor\Stock\Models\Supplier;

class SuppliersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Supplier::query()->get([
            'id', 'name', 'contact_name', 'email', 'phone', 'city', 'country',
        ]);
    }

    public function headings(): array
    {
        return trans('stock::stock.exports.suppliers');
    }
}
