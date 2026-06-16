<?php

namespace Vendor\Invoice\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Vendor\Invoice\Models\Payment;

class PaymentsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Payment::with(['invoice.client'])->orderByDesc('payment_date');
    }

    public function headings(): array
    {
        return [
            'Date paiement',
            'Facture',
            'Client',
            'Mode',
            'Reference',
            'Banque',
            'Devise',
            'Montant',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->payment_date?->format('d/m/Y'),
            $payment->invoice?->number,
            $payment->invoice?->client?->company_name,
            $payment->method_label,
            $payment->reference,
            $payment->bank_name,
            $payment->currency,
            (float) $payment->amount,
        ];
    }
}
