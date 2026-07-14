<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ __('invoice::invoices.common.quote') }}</title>
    <style>
        body { font-family: "DM Sans", sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 18px; margin-bottom: 8px; }
        p { color: #64748b; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ __('invoice::invoices.common.quote') }}</h1>
    <p>Généré le {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ __('invoice::invoices.fields.number') }}</th>
                <th>{{ __('invoice::invoices.fields.client') }}</th>
                <th>{{ __('invoice::invoices.fields.status') }}</th>
                <th>{{ __('invoice::invoices.fields.issue_date') }}</th>
                <th>{{ __('invoice::invoices.fields.valid_until') }}</th>
                <th class="right">{{ __('invoice::invoices.common.total') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($quotes as $quote)
            <tr>
                <td>{{ $quote->number }}</td>
                <td>{{ $quote->client?->company_name }}</td>
                <td>{{ $quote->status_label ?? $quote->status }}</td>
                <td>{{ optional($quote->issue_date)->format('d/m/Y') }}</td>
                <td>{{ optional($quote->valid_until)->format('d/m/Y') ?: '—' }}</td>
                <td class="right">{{ number_format($quote->total, 2, ',', ' ') }} {{ $quote->currency }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
