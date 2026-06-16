<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis {{ $quote->number }}</title>
    @php
        $theme = $branding['theme'] ?? 'ocean';
        $themes = [
            'ocean' => ['primary' => '#1d4ed8', 'soft' => '#eff6ff', 'accent' => '#0ea5e9', 'text' => '#0f172a', 'muted' => '#64748b', 'border' => '#dbeafe'],
            'emerald' => ['primary' => '#047857', 'soft' => '#ecfdf5', 'accent' => '#10b981', 'text' => '#052e2b', 'muted' => '#4b5563', 'border' => '#d1fae5'],
            'sunset' => ['primary' => '#c2410c', 'soft' => '#fff7ed', 'accent' => '#f97316', 'text' => '#3f1d0d', 'muted' => '#6b7280', 'border' => '#fed7aa'],
            'mono' => ['primary' => '#111827', 'soft' => '#f9fafb', 'accent' => '#4b5563', 'text' => '#111827', 'muted' => '#6b7280', 'border' => '#e5e7eb'],
        ];
        $palette = $themes[$theme] ?? $themes['ocean'];
        $status = (string) ($quote->status ?? 'draft');
    @endphp
    <style>
        * { box-sizing: border-box; }
        body { font-family: "DM Sans", sans-serif; font-size: 10pt; color: {{ $palette['text'] }}; margin: 0; }
        /* Extra bottom padding so fixed footer never overlaps content */
        .wrap { padding: 34px 36px 120px; }

        .header-band { background: {{ $palette['soft'] }}; border: 1px solid {{ $palette['border'] }}; border-radius: 10px; padding: 18px 18px 14px; margin-bottom: 18px; }
        .table-layout { width: 100%; border-collapse: collapse; }
        .table-layout td { vertical-align: top; }
        .company-name { font-size: 18pt; font-weight: bold; color: {{ $palette['primary'] }}; }
        .company-meta { color: {{ $palette['muted'] }}; font-size: 8.8pt; line-height: 1.5; margin-top: 4px; }
        .doc-title { text-align: right; }
        .doc-title .kicker { font-size: 8pt; letter-spacing: .12em; color: {{ $palette['muted'] }}; text-transform: uppercase; }
        .doc-title .big { font-size: 20pt; color: {{ $palette['primary'] }}; font-weight: bold; margin-top: 2px; }
        .doc-title .ref { font-size: 8.6pt; color: {{ $palette['muted'] }}; margin-top: 4px; }

        .status-pill { display: inline-block; margin-top: 7px; padding: 3px 10px; border-radius: 999px; font-size: 7.8pt; font-weight: bold; text-transform: uppercase; letter-spacing: .05em; }
        .status-draft { background: #e5e7eb; color: #4b5563; }
        .status-sent { background: #cffafe; color: #0e7490; }
        .status-accepted { background: #dcfce7; color: #15803d; }
        .status-declined { background: #fee2e2; color: #b91c1c; }
        .status-expired { background: #fef3c7; color: #b45309; }

        .meta-grid { margin-bottom: 16px; }
        .meta-grid td { width: 25%; border: 1px solid {{ $palette['border'] }}; padding: 10px 12px; }
        .meta-label { font-size: 7.2pt; text-transform: uppercase; letter-spacing: .08em; color: {{ $palette['muted'] }}; margin-bottom: 4px; }
        .meta-val { font-size: 9.4pt; font-weight: bold; }

        .addr-card { width: 100%; margin-bottom: 16px; border-collapse: separate; border-spacing: 0; }
        .addr-card td { width: 50%; border: 1px solid {{ $palette['border'] }}; padding: 12px; background: #fff; }
        .addr-title { font-size: 7.5pt; text-transform: uppercase; letter-spacing: .08em; color: {{ $palette['muted'] }}; margin-bottom: 6px; }
        .addr-name { font-size: 11pt; font-weight: bold; color: {{ $palette['primary'] }}; margin-bottom: 4px; }
        .addr-lines { font-size: 8.8pt; line-height: 1.55; color: {{ $palette['text'] }}; }

        .items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .items thead th { background: {{ $palette['primary'] }}; color: #fff; padding: 9px 8px; font-size: 7.6pt; text-transform: uppercase; letter-spacing: .05em; text-align: left; }
        .items tbody td { border: 1px solid {{ $palette['border'] }}; padding: 8px; font-size: 8.8pt; vertical-align: top; }
        .items tbody tr:nth-child(even) td { background: {{ $palette['soft'] }}; }
        .right { text-align: right; }
        .muted { color: {{ $palette['muted'] }}; }

        .totals { width: 44%; margin-left: auto; border-collapse: collapse; margin-bottom: 10px; }
        .totals td { border: 1px solid {{ $palette['border'] }}; padding: 7px 10px; font-size: 8.8pt; }
        .totals .label { color: {{ $palette['muted'] }}; }
        .totals .grand td { background: {{ $palette['soft'] }}; font-size: 11pt; font-weight: bold; color: {{ $palette['primary'] }}; border-top: 2px solid {{ $palette['primary'] }}; }

        .info-box { margin-top: 8px; border: 1px solid {{ $palette['border'] }}; border-left: 4px solid {{ $palette['accent'] }}; border-radius: 6px; padding: 10px 12px; background: #fff; }
        .info-title { font-size: 7.6pt; text-transform: uppercase; letter-spacing: .08em; color: {{ $palette['muted'] }}; margin-bottom: 5px; }
        .info-body { font-size: 8.7pt; color: {{ $palette['text'] }}; line-height: 1.55; }

        .signature { margin-top: 14px; text-align: right; }
        .signature img { max-height: 70px; max-width: 220px; display: block; margin-left: auto; }

        /* Footer pinned to the bottom of each page */
        .footer { position: fixed; left: 36px; right: 36px; bottom: 18px; padding-top: 10px; border-top: 1px solid {{ $palette['border'] }}; font-size: 7.8pt; color: {{ $palette['muted'] }}; text-align: center; line-height: 1.5; }

        @page { margin: 0; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header-band">
        <table class="table-layout">
            <tr>
                <td style="width:58%;">
                    @if(($branding['show_logo'] ?? true) && !empty($branding['logo_path']))
                        <img src="{{ $branding['logo_path'] }}" alt="Logo" style="max-height:58px;max-width:240px;">
                    @endif
                    <div class="company-name">{{ $quote->tenant->name ?? config('app.name') }}</div>
                    <div class="company-meta">
                        {{ $quote->tenant->address ?? '' }}<br>
                        {{ $quote->tenant->email ?? '' }}
                        @if(!empty($quote->tenant->phone))<br>{{ $quote->tenant->phone }}@endif
                        @if(!empty($quote->tenant->vat_number))<br>TVA: {{ $quote->tenant->vat_number }}@endif
                    </div>
                </td>
                <td class="doc-title" style="width:42%;">
                    <div class="kicker">{{ __('invoice::invoices.common.quote') }}</div>
                    <div class="big">{{ $quote->number }}</div>
                    @if($quote->reference)<div class="ref">Référence : {{ $quote->reference }}</div>@endif
                    <span class="status-pill status-{{ $status }}">{{ $quote->status_label ?? $status }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-grid table-layout">
        <tr>
            <td>
                <div class="meta-label">{{ __('invoice::invoices.fields.issue_date') }}</div>
                <div class="meta-val">{{ optional($quote->issue_date)->format('d/m/Y') }}</div>
            </td>
            <td>
                <div class="meta-label">{{ __('invoice::invoices.fields.valid_until') }}</div>
                <div class="meta-val">{{ optional($quote->valid_until)->format('d/m/Y') ?: '-' }}</div>
            </td>
            <td>
                <div class="meta-label">{{ __('invoice::invoices.fields.currency') }}</div>
                <div class="meta-val">{{ $quote->currency ?? 'EUR' }}</div>
            </td>
            <td>
                <div class="meta-label">{{ __('invoice::invoices.fields.status') }}</div>
                <div class="meta-val">{{ $quote->status_label ?? $status }}</div>
            </td>
        </tr>
    </table>

    <table class="addr-card">
        <tr>
            <td>
                <div class="addr-title">{{ __('invoice::invoices.common.issuer') }}</div>
                <div class="addr-name">{{ $quote->tenant->name ?? config('app.name') }}</div>
                <div class="addr-lines">
                    {{ $quote->tenant->address ?? '' }}<br>
                    {{ $quote->tenant->email ?? '' }}
                </div>
            </td>
            <td>
                <div class="addr-title">{{ __('invoice::invoices.fields.client') }}</div>
                <div class="addr-name">{{ $quote->client->company_name ?? '-' }}</div>
                <div class="addr-lines">
                    {{ $quote->client->contact_name ?? '' }}<br>
                    {{ $quote->client->full_address ?? '' }}<br>
                    {{ $quote->client->email ?? '' }}
                    @if(!empty($quote->client->vat_number))<br>TVA: {{ $quote->client->vat_number }}@endif
                    @if(!empty($quote->client->siret))<br>SIRET: {{ $quote->client->siret }}@endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:28px;">#</th>
                <th>{{ __('invoice::invoices.fields.description') }}</th>
                <th style="width:70px;" class="right">{{ __('invoice::invoices.common.line_quantity') }}</th>
                <th style="width:58px;">{{ __('invoice::invoices.fields.unit') }}</th>
                <th style="width:92px;" class="right">{{ __('invoice::invoices.common.line_unit_price_ht') }}</th>
                <th style="width:70px;" class="right">{{ __('invoice::invoices.fields.discount') }}</th>
                <th style="width:56px;" class="right">{{ __('invoice::invoices.common.vat') }}</th>
                <th style="width:100px;" class="right">{{ __('invoice::invoices.common.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $i => $item)
                <tr>
                    <td class="muted">{{ $i + 1 }}</td>
                    <td>
                        {{ $item->description }}
                        @if($item->reference)<div class="muted" style="font-size:8pt;">Ref: {{ $item->reference }}</div>@endif
                    </td>
                    <td class="right">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                    <td class="muted">{{ $item->unit ?: '' }}</td>
                    <td class="right">{{ number_format((float) $item->unit_price, 2, ',', ' ') }}</td>
                    <td class="right">{{ (float) $item->discount_amount > 0 ? '-' . number_format((float) $item->discount_amount, 2, ',', ' ') : '-' }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->tax_rate, 2, '.', ''), '0'), '.') }}%</td>
                    <td class="right">{{ number_format((float) $item->total, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">{{ __('invoice::invoices.common.subtotal_ht') }}</td>
            <td class="right"><strong>{{ number_format((float) $quote->subtotal, 2, ',', ' ') }} {{ $quote->currency }}</strong></td>
        </tr>
        @if((float) $quote->discount_amount > 0)
            <tr>
                <td class="label">{{ __('invoice::invoices.fields.discount') }}</td>
                <td class="right">-{{ number_format((float) $quote->discount_amount, 2, ',', ' ') }} {{ $quote->currency }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">{{ __('invoice::invoices.common.vat') }}</td>
            <td class="right">{{ number_format((float) $quote->tax_amount, 2, ',', ' ') }} {{ $quote->currency }}</td>
        </tr>
        @if((float) $quote->withholding_tax_rate > 0)
            <tr>
                <td class="label">{{ __('invoice::invoices.withholding.label') }}</td>
                <td class="right">-{{ number_format((float) $quote->withholding_tax_amount, 2, ',', ' ') }} {{ $quote->currency }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>{{ __('invoice::invoices.common.total_ttc') }}</td>
            <td class="right">{{ number_format((float) $quote->total, 2, ',', ' ') }} {{ $quote->currency }}</td>
        </tr>
    </table>

    @if(!empty($quote->notes))
        <div class="info-box">
            <div class="info-title">{{ __('invoice::invoices.fields.notes') }}</div>
            <div class="info-body">{{ $quote->notes }}</div>
        </div>
    @endif

    @if(!empty($quote->terms))
        <div class="info-box">
            <div class="info-title">Conditions</div>
            <div class="info-body">{{ $quote->terms }}</div>
        </div>
    @endif

    @if(($signature['enabled'] ?? false) && ($signature['show_on_quote'] ?? false) && !empty($signature['data']))
        <div class="signature">
            <div class="muted" style="font-size:8pt;margin-bottom:4px;">{{ __('invoice::invoices.common.signature') }}</div>
            <img src="{{ $signature['data'] }}" alt="Signature">
            @if(!empty($signature['name']))<div style="font-size:9pt;font-weight:bold;">{{ $signature['name'] }}</div>@endif
            @if(!empty($signature['title']))<div class="muted" style="font-size:8pt;">{{ $signature['title'] }}</div>@endif
        </div>
    @endif

    @if(($branding['show_footer'] ?? true) && (!empty($branding['footer_text']) || !empty($branding['legal_mentions'])))
        <div class="footer">
            @if(!empty($branding['footer_text'])){{ $branding['footer_text'] }}<br>@endif
            @if(!empty($branding['legal_mentions'])){{ $branding['legal_mentions'] }}<br>@endif
            Généré le {{ now()->format('d/m/Y H:i') }}
        </div>
    @endif
</div>
</body>
</html>
