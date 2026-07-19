<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->number }}</title>
    @php
        $theme = $branding['theme'] ?? 'ocean';
        $themes = [
            'ocean' => ['primary' => '#1d4ed8', 'soft' => '#eff6ff', 'accent' => '#0ea5e9', 'text' => '#0f172a', 'muted' => '#64748b', 'border' => '#e2e8f0'],
            'emerald' => ['primary' => '#047857', 'soft' => '#ecfdf5', 'accent' => '#10b981', 'text' => '#052e2b', 'muted' => '#4b5563', 'border' => '#dcede6'],
            'sunset' => ['primary' => '#c2410c', 'soft' => '#fff7ed', 'accent' => '#f97316', 'text' => '#3f1d0d', 'muted' => '#6b7280', 'border' => '#f0e2d5'],
            'mono' => ['primary' => '#111827', 'soft' => '#f9fafb', 'accent' => '#4b5563', 'text' => '#111827', 'muted' => '#6b7280', 'border' => '#e5e7eb'],
        ];
        $palette = $themes[$theme] ?? $themes['ocean'];
        if (!empty($branding['primary_color'])) { $palette['primary'] = $branding['primary_color']; $palette['accent'] = $branding['primary_color']; }
        $status = (string) ($invoice->status ?? 'draft');
        $sym = $invoice->currency_symbol ?? '';
        $money = fn ($v) => number_format((float) $v, 2, ',', ' ') . ' ' . $sym;
    @endphp
    <style>
        * { box-sizing: border-box; }
        @page { margin: 0; }
        body { font-family: "DM Sans", sans-serif; font-size: 10pt; color: {{ $palette['text'] }}; margin: 0; }
        .accent-bar { height: 7px; background: {{ $palette['primary'] }}; }
        .wrap { padding: 26px 38px 130px; }

        table { border-collapse: collapse; }
        .layout { width: 100%; }
        .layout td { vertical-align: top; }
        .right { text-align: right; }
        .muted { color: {{ $palette['muted'] }}; }

        /* Header */
        .company-name { font-size: 17pt; font-weight: bold; color: {{ $palette['text'] }}; }
        .company-meta { color: {{ $palette['muted'] }}; font-size: 8.6pt; line-height: 1.55; margin-top: 5px; }
        .doc-kicker { font-size: 8pt; letter-spacing: .18em; color: {{ $palette['muted'] }}; text-transform: uppercase; }
        .doc-number { font-size: 22pt; color: {{ $palette['primary'] }}; font-weight: bold; margin: 2px 0 2px; }
        .doc-ref { font-size: 8.6pt; color: {{ $palette['muted'] }}; }
        .status-pill { display: inline-block; margin-top: 8px; padding: 4px 12px; border-radius: 999px; font-size: 7.8pt; font-weight: bold; text-transform: uppercase; letter-spacing: .05em; }
        .status-draft { background: #e5e7eb; color: #4b5563; }
        .status-sent { background: #cffafe; color: #0e7490; }
        .status-paid { background: #dcfce7; color: #15803d; }
        .status-overdue { background: #fee2e2; color: #b91c1c; }
        .status-cancelled { background: #e5e7eb; color: #6b7280; }
        .status-partial { background: #fef3c7; color: #b45309; }

        /* Parties (émetteur / client) */
        .parties { width: 100%; margin: 22px 0 6px; }
        .parties td { width: 50%; padding: 14px 16px; background: {{ $palette['soft'] }}; border-radius: 10px; }
        .parties td.left-cell { padding-right: 8px; background: transparent; }
        .party-title { font-size: 7.4pt; text-transform: uppercase; letter-spacing: .1em; color: {{ $palette['muted'] }}; margin-bottom: 6px; }
        .party-name { font-size: 11pt; font-weight: bold; color: {{ $palette['text'] }}; margin-bottom: 4px; }
        .party-lines { font-size: 8.8pt; line-height: 1.6; color: {{ $palette['text'] }}; }

        /* Meta strip */
        .meta { width: 100%; margin: 16px 0 18px; border-top: 1px solid {{ $palette['border'] }}; border-bottom: 1px solid {{ $palette['border'] }}; }
        .meta td { padding: 10px 6px; width: 25%; }
        .meta td + td { border-left: 1px solid {{ $palette['border'] }}; padding-left: 14px; }
        .meta-label { font-size: 7pt; text-transform: uppercase; letter-spacing: .09em; color: {{ $palette['muted'] }}; margin-bottom: 3px; }
        .meta-val { font-size: 9.6pt; font-weight: bold; }

        /* Items */
        .items { width: 100%; margin-bottom: 18px; }
        .items thead th { background: {{ $palette['primary'] }}; color: #fff; padding: 9px 9px; font-size: 7.6pt; text-transform: uppercase; letter-spacing: .04em; text-align: left; }
        .items thead th:first-child { border-top-left-radius: 7px; }
        .items thead th:last-child { border-top-right-radius: 7px; }
        .items tbody td { padding: 9px; font-size: 8.9pt; vertical-align: top; border-bottom: 1px solid {{ $palette['border'] }}; }
        .items tbody tr:nth-child(even) td { background: {{ $palette['soft'] }}; }
        .item-ref { color: {{ $palette['muted'] }}; font-size: 7.8pt; margin-top: 2px; }

        /* Bottom: notes (left) + totals (right) */
        .bottom { width: 100%; }
        .bottom .notes-col { width: 52%; padding-right: 18px; vertical-align: top; }
        .bottom .totals-col { width: 48%; vertical-align: top; }
        .totals { width: 100%; }
        .totals td { padding: 7px 12px; font-size: 9pt; }
        .totals .label { color: {{ $palette['muted'] }}; }
        .totals .sep td { border-top: 1px solid {{ $palette['border'] }}; }
        .totals .grand td { background: {{ $palette['primary'] }}; color: #fff; font-size: 12pt; font-weight: bold; }
        .totals .grand td:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        .totals .grand td:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
        .totals .due td { background: {{ $palette['soft'] }}; color: {{ $palette['primary'] }}; font-weight: bold; font-size: 10pt; }

        .info-box { margin-bottom: 10px; border-left: 3px solid {{ $palette['accent'] }}; padding: 4px 0 4px 12px; }
        .info-title { font-size: 7.6pt; text-transform: uppercase; letter-spacing: .08em; color: {{ $palette['muted'] }}; margin-bottom: 4px; }
        .info-body { font-size: 8.7pt; color: {{ $palette['text'] }}; line-height: 1.6; }

        .signature { margin-top: 20px; text-align: right; }
        .signature img { max-height: 68px; max-width: 220px; display: block; margin-left: auto; }

        .footer { position: fixed; left: 38px; right: 38px; bottom: 18px; padding-top: 10px; border-top: 1px solid {{ $palette['border'] }}; font-size: 7.8pt; color: {{ $palette['muted'] }}; text-align: center; line-height: 1.5; }

        .watermark { position: fixed; top: 42%; left: 14%; font-size: 74pt; color: rgba(148, 163, 184, 0.10); transform: rotate(-28deg); z-index: -1; font-weight: bold; letter-spacing: .08em; }
    </style>
</head>
<body>
<div class="accent-bar"></div>

@if(in_array($status, ['draft', 'cancelled']))
    <div class="watermark">{{ mb_strtoupper($status === 'draft' ? __('invoice::invoices.status.draft') : __('invoice::invoices.status.cancelled')) }}</div>
@endif

<div class="wrap">
    <table class="layout">
        <tr>
            <td style="width:58%;">
                @if(($branding['show_logo'] ?? true) && !empty($branding['logo_path']))
                    <img src="{{ $branding['logo_path'] }}" alt="Logo" style="max-height:56px;max-width:240px;margin-bottom:8px;">
                @endif
                <div class="company-name">{{ $invoice->tenant->name ?? config('app.name') }}</div>
                <div class="company-meta">
                    {{ $invoice->tenant->address ?? '' }}<br>
                    {{ $invoice->tenant->email ?? '' }}
                    @if(!empty($invoice->tenant->phone))<br>{{ $invoice->tenant->phone }}@endif
                    @if(!empty($invoice->tenant->vat_number))<br>TVA : {{ $invoice->tenant->vat_number }}@endif
                </div>
            </td>
            <td class="right" style="width:42%;">
                <div class="doc-kicker">{{ __('invoice::invoices.common.invoice') }}</div>
                <div class="doc-number">{{ $invoice->number }}</div>
                @if($invoice->reference)<div class="doc-ref">Référence : {{ $invoice->reference }}</div>@endif
                <span class="status-pill status-{{ $status }}">{{ $invoice->status_label ?? $status }}</span>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td class="left-cell">
                <div class="party-title">{{ __('invoice::invoices.common.issuer') }}</div>
                <div class="party-name">{{ $invoice->tenant->name ?? config('app.name') }}</div>
                <div class="party-lines">
                    {{ $invoice->tenant->address ?? '' }}<br>
                    {{ $invoice->tenant->email ?? '' }}
                </div>
            </td>
            <td>
                <div class="party-title">{{ __('invoice::invoices.common.billed_to') }}</div>
                <div class="party-name">{{ $invoice->client->company_name ?? '-' }}</div>
                <div class="party-lines">
                    {{ $invoice->client->contact_name ?? '' }}<br>
                    {{ $invoice->client->full_address ?? '' }}<br>
                    {{ $invoice->client->email ?? '' }}
                    @if(!empty($invoice->client->vat_number))<br>TVA : {{ $invoice->client->vat_number }}@endif
                    @if(!empty($invoice->client->siret))<br>SIRET : {{ $invoice->client->siret }}@endif
                </div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="meta-label">{{ __('invoice::invoices.fields.issue_date') }}</div>
                <div class="meta-val">{{ optional($invoice->issue_date)->format('d/m/Y') }}</div>
            </td>
            <td>
                <div class="meta-label">{{ __('invoice::invoices.fields.due_date') }}</div>
                <div class="meta-val">{{ optional($invoice->due_date)->format('d/m/Y') ?: '-' }}</div>
            </td>
            <td>
                <div class="meta-label">{{ __('invoice::invoices.fields.currency') }}</div>
                <div class="meta-val">{{ $invoice->currency ?? config('invoice.default_currency', 'EUR') }} {{ $sym }}</div>
            </td>
            <td>
                <div class="meta-label">{{ __('invoice::invoices.fields.payment_method') }}</div>
                <div class="meta-val">{{ $invoice->payment_method ? (config("invoice.payment_methods.{$invoice->payment_method}") ?? $invoice->payment_method) : '-' }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:26px;">#</th>
                <th>{{ __('invoice::invoices.fields.description') }}</th>
                <th style="width:66px;" class="right">{{ __('invoice::invoices.common.line_quantity') }}</th>
                <th style="width:54px;">{{ __('invoice::invoices.fields.unit') }}</th>
                <th style="width:92px;" class="right">{{ __('invoice::invoices.common.line_unit_price_ht') }}</th>
                <th style="width:68px;" class="right">{{ __('invoice::invoices.fields.discount') }}</th>
                <th style="width:52px;" class="right">{{ __('invoice::invoices.common.vat') }}</th>
                <th style="width:100px;" class="right">{{ __('invoice::invoices.common.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
                <tr>
                    <td class="muted">{{ $i + 1 }}</td>
                    <td>
                        {{ $item->description }}
                        @if($item->reference)<div class="item-ref">Réf. {{ $item->reference }}</div>@endif
                    </td>
                    <td class="right">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                    <td class="muted">{{ $item->unit ?: '' }}</td>
                    <td class="right">{{ number_format((float) $item->unit_price, 2, ',', ' ') }}</td>
                    <td class="right">{{ (float) $item->discount_amount > 0 ? '-' . number_format((float) $item->discount_amount, 2, ',', ' ') : '-' }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->tax_rate, 2, '.', ''), '0'), '.') }}%</td>
                    <td class="right"><strong>{{ number_format((float) $item->total, 2, ',', ' ') }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="bottom">
        <tr>
            <td class="notes-col">
                @if(!empty($invoice->notes))
                    <div class="info-box">
                        <div class="info-title">{{ __('invoice::invoices.fields.notes') }}</div>
                        <div class="info-body">{{ $invoice->notes }}</div>
                    </div>
                @endif
                @if(!empty($invoice->terms))
                    <div class="info-box">
                        <div class="info-title">Conditions</div>
                        <div class="info-body">{{ $invoice->terms }}</div>
                    </div>
                @endif
            </td>
            <td class="totals-col">
                <table class="totals">
                    <tr>
                        <td class="label">{{ __('invoice::invoices.common.subtotal_ht') }}</td>
                        <td class="right">{{ $money($invoice->subtotal) }}</td>
                    </tr>
                    @if((float) $invoice->discount_amount > 0)
                        <tr>
                            <td class="label">{{ __('invoice::invoices.fields.discount') }}</td>
                            <td class="right">-{{ $money($invoice->discount_amount) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">{{ __('invoice::invoices.common.vat') }}</td>
                        <td class="right">{{ $money($invoice->tax_amount) }}</td>
                    </tr>
                    @if((float) $invoice->withholding_tax_rate > 0)
                        <tr>
                            <td class="label">{{ __('invoice::invoices.withholding.label') }}</td>
                            <td class="right">-{{ $money($invoice->withholding_tax_amount) }}</td>
                        </tr>
                    @endif
                    <tr class="grand">
                        <td>{{ __('invoice::invoices.common.total_ttc') }}</td>
                        <td class="right">{{ $money($invoice->total) }}</td>
                    </tr>
                    @include('invoice::partials.pdf_base_equivalent', ['doc' => $invoice])
                    @if((float) $invoice->amount_paid > 0)
                        <tr>
                            <td class="label">{{ __('invoice::invoices.fields.amount_paid') }}</td>
                            <td class="right">{{ $money($invoice->amount_paid) }}</td>
                        </tr>
                    @endif
                    @if((float) $invoice->amount_due > 0)
                        <tr class="due">
                            <td>{{ __('invoice::invoices.fields.amount_due') }}</td>
                            <td class="right">{{ $money($invoice->amount_due) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if(($signature['enabled'] ?? false) && ($signature['show_on_invoice'] ?? false) && !empty($signature['data']))
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
