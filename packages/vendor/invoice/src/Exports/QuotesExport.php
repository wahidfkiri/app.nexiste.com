<?php

namespace Vendor\Invoice\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Vendor\Invoice\Models\Quote;

class QuotesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function title(): string { return (string) trans('invoice::invoices.exports.quotes_title'); }

    public function query()
    {
        return Quote::with(['client'])->orderBy('issue_date', 'desc');
    }

    public function headings(): array
    {
        return (array) trans('invoice::invoices.exports.quote_headings');
    }

    public function map($quote): array
    {
        return [
            $quote->number,
            $quote->client?->company_name,
            $quote->reference,
            $quote->status_label,
            $quote->issue_date?->format('d/m/Y'),
            $quote->valid_until?->format('d/m/Y'),
            $quote->currency,
            number_format($quote->subtotal, 2, ',', ' '),
            number_format($quote->discount_amount, 2, ',', ' '),
            number_format($quote->tax_amount, 2, ',', ' '),
            number_format($quote->withholding_tax_amount, 2, ',', ' '),
            number_format($quote->total, 2, ',', ' '),
            $quote->is_converted ? trans('invoice::invoices.exports.yes') : trans('invoice::invoices.exports.no'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF7C3AED']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
