<?php

namespace Vendor\Invoice\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Vendor\Invoice\Models\Invoice;

class InvoicesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function title(): string { return (string) trans('invoice::invoices.exports.invoices_title'); }

    public function query()
    {
        return Invoice::with(['client'])->orderBy('issue_date', 'desc');
    }

    public function headings(): array
    {
        return (array) trans('invoice::invoices.exports.invoice_headings');
    }

    public function map($invoice): array
    {
        return [
            $invoice->number,
            $invoice->client?->company_name,
            $invoice->reference,
            $invoice->status_label,
            $invoice->issue_date?->format('d/m/Y'),
            $invoice->due_date?->format('d/m/Y'),
            $invoice->currency,
            number_format($invoice->subtotal, 2, ',', ' '),
            number_format($invoice->discount_amount, 2, ',', ' '),
            number_format($invoice->tax_amount, 2, ',', ' '),
            number_format($invoice->withholding_tax_amount, 2, ',', ' '),
            number_format($invoice->total, 2, ',', ' '),
            number_format($invoice->amount_paid, 2, ',', ' '),
            number_format($invoice->amount_due, 2, ',', ' '),
            $invoice->payment_method
                ? (config("invoice.payment_methods.{$invoice->payment_method}") ?? $invoice->payment_method)
                : '',
            $invoice->payment_date?->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF2563EB']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
