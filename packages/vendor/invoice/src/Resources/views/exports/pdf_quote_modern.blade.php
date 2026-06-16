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
    .wrap { padding: 34px 36px 120px; }
    .topbar { position: fixed; top: 0; left: 0; right: 0; height: 10px; background: {{ $palette['primary'] }}; }

    .table { width: 100%; border-collapse: collapse; }
    .muted { color: {{ $palette['muted'] }}; }
    .h1 { font-size: 22pt; font-weight: bold; margin: 0; color: {{ $palette['primary'] }}; }
    .kicker { font-size: 8pt; text-transform: uppercase; letter-spacing: .14em; color: {{ $palette['muted'] }}; }

    .pill { display:inline-block; padding: 3px 10px; border-radius: 999px; font-size: 7.8pt; font-weight: bold; text-transform: uppercase; letter-spacing: .06em; margin-top: 8px; }
    .pill-draft { background: #e5e7eb; color: #4b5563; }
    .pill-sent { background: #cffafe; color: #0e7490; }
    .pill-accepted { background: #dcfce7; color: #15803d; }
    .pill-declined { background: #fee2e2; color: #b91c1c; }
    .pill-expired { background: #fef3c7; color: #b45309; }

    .card { border: 1px solid {{ $palette['border'] }}; border-radius: 12px; overflow: hidden; background: #fff; }
    .card-hd { background: {{ $palette['soft'] }}; padding: 14px 16px; }
    .card-bd { padding: 14px 16px; }

    .meta { width: 100%; border-collapse: collapse; margin-top: 12px; }
    .meta td { width: 25%; padding: 10px 12px; border-top: 1px solid {{ $palette['border'] }}; border-right: 1px solid {{ $palette['border'] }}; }
    .meta td:last-child { border-right: none; }
    .meta-label { font-size: 7.2pt; text-transform: uppercase; letter-spacing: .08em; color: {{ $palette['muted'] }}; margin-bottom: 4px; }
    .meta-val { font-size: 9.4pt; font-weight: bold; }

    .addr { width: 100%; border-collapse: collapse; margin-top: 14px; }
    .addr td { width: 50%; vertical-align: top; padding: 12px; border: 1px solid {{ $palette['border'] }}; background: #fff; }
    .addr-title { font-size: 7.5pt; text-transform: uppercase; letter-spacing: .08em; color: {{ $palette['muted'] }}; margin-bottom: 6px; }
    .addr-name { font-size: 11pt; font-weight: bold; color: {{ $palette['primary'] }}; margin-bottom: 4px; }
    .addr-lines { font-size: 8.8pt; line-height: 1.55; color: {{ $palette['text'] }}; }

    .items { width: 100%; border-collapse: collapse; margin-top: 16px; }
    .items thead th { background: {{ $palette['primary'] }}; color: #fff; padding: 10px 8px; font-size: 7.6pt; text-transform: uppercase; letter-spacing: .05em; text-align: left; }
    .items tbody td { border: 1px solid {{ $palette['border'] }}; padding: 8px; font-size: 8.8pt; vertical-align: top; }
    .items tbody tr:nth-child(even) td { background: {{ $palette['soft'] }}; }
    .right { text-align: right; }

    .totals { width: 44%; margin-left: auto; border-collapse: collapse; margin-top: 12px; }
    .totals td { border: 1px solid {{ $palette['border'] }}; padding: 7px 10px; font-size: 8.8pt; }
    .totals .label { color: {{ $palette['muted'] }}; }
    .totals .grand td { background: {{ $palette['soft'] }}; font-size: 11pt; font-weight: bold; color: {{ $palette['primary'] }}; border-top: 2px solid {{ $palette['primary'] }}; }

    .signature { margin-top: 14px; text-align: right; }
    .signature img { max-height: 70px; max-width: 220px; display: block; margin-left: auto; }

    .footer { position: fixed; left: 36px; right: 36px; bottom: 18px; padding-top: 10px; border-top: 1px solid {{ $palette['border'] }}; font-size: 7.8pt; color: {{ $palette['muted'] }}; text-align: center; line-height: 1.5; }

    @page { margin: 0; }
  </style>
</head>
<body>
<div class="topbar"></div>

<div class="wrap">
  <div class="card">
    <div class="card-hd">
      <table class="table">
        <tr>
          <td style="width:60%;vertical-align:top;">
            @if(($branding['show_logo'] ?? true) && !empty($branding['logo_path']))
              <img src="{{ $branding['logo_path'] }}" alt="Logo" style="max-height:58px;max-width:240px;margin-bottom:8px;">
            @endif
            <div style="font-size:16pt;font-weight:bold;color:{{ $palette['primary'] }};">{{ $quote->tenant->name ?? config('app.name') }}</div>
            <div class="muted" style="font-size:8.8pt;line-height:1.5;margin-top:4px;">
              {{ $quote->tenant->address ?? '' }}<br>
              {{ $quote->tenant->email ?? '' }}
              @if(!empty($quote->tenant->phone))<br>{{ $quote->tenant->phone }}@endif
              @if(!empty($quote->tenant->vat_number))<br>TVA : {{ $quote->tenant->vat_number }}@endif
            </div>
          </td>
          <td style="width:40%;vertical-align:top;text-align:right;">
            <div class="kicker">{{ __('invoice::invoices.common.quote') }}</div>
            <div class="h1">{{ $quote->number }}</div>
            @if($quote->reference)<div class="muted" style="font-size:8.6pt;margin-top:4px;">Référence : {{ $quote->reference }}</div>@endif
            <span class="pill pill-{{ $status }}">{{ $quote->status_label ?? $status }}</span>
          </td>
        </tr>
      </table>

      <table class="meta">
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
    </div>

    <div class="card-bd">
      <table class="addr">
        <tr>
          <td>
            <div class="addr-title">{{ __('invoice::invoices.common.issuer') }}</div>
            <div class="addr-name">{{ $quote->tenant->name ?? config('app.name') }}</div>
            <div class="addr-lines">{{ $quote->tenant->address ?? '' }}<br>{{ $quote->tenant->email ?? '' }}</div>
          </td>
          <td>
            <div class="addr-title">{{ __('invoice::invoices.fields.client') }}</div>
            <div class="addr-name">{{ $quote->client->company_name ?? '-' }}</div>
            <div class="addr-lines">
              {{ $quote->client->contact_name ?? '' }}<br>
              {{ $quote->client->full_address ?? '' }}<br>
              {{ $quote->client->email ?? '' }}
              @if(!empty($quote->client->vat_number))<br>TVA : {{ $quote->client->vat_number }}@endif
              @if(!empty($quote->client->siret))<br>SIRET : {{ $quote->client->siret }}@endif
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
                @if($item->reference)<div class="muted" style="font-size:8pt;">Ref : {{ $item->reference }}</div>@endif
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
          <td class="right"><strong>{{ number_format((float) $quote->subtotal, 2, ',', ' ') }} {{ $quote->currency_symbol ?? '' }}</strong></td>
        </tr>
        @if((float) $quote->discount_amount > 0)
          <tr>
            <td class="label">{{ __('invoice::invoices.fields.discount') }}</td>
            <td class="right">-{{ number_format((float) $quote->discount_amount, 2, ',', ' ') }} {{ $quote->currency_symbol ?? '' }}</td>
          </tr>
        @endif
        <tr>
          <td class="label">{{ __('invoice::invoices.common.vat') }}</td>
          <td class="right">{{ number_format((float) $quote->tax_amount, 2, ',', ' ') }} {{ $quote->currency_symbol ?? '' }}</td>
        </tr>
        <tr class="grand">
          <td>{{ __('invoice::invoices.common.total_ttc') }}</td>
          <td class="right">{{ number_format((float) $quote->total, 2, ',', ' ') }} {{ $quote->currency_symbol ?? '' }}</td>
        </tr>
      </table>

      @if(($signature['enabled'] ?? false) && ($signature['show_on_quote'] ?? false) && !empty($signature['data']))
        <div class="signature">
          <div class="muted" style="font-size:8pt;margin-bottom:4px;">{{ __('invoice::invoices.common.signature') }}</div>
          <img src="{{ $signature['data'] }}" alt="Signature">
          @if(!empty($signature['name']))<div style="font-size:9pt;font-weight:bold;">{{ $signature['name'] }}</div>@endif
          @if(!empty($signature['title']))<div class="muted" style="font-size:8pt;">{{ $signature['title'] }}</div>@endif
        </div>
      @endif
    </div>
  </div>
</div>

@if(($branding['show_footer'] ?? true) && (!empty($branding['footer_text']) || !empty($branding['legal_mentions'])))
  <div class="footer">
    @if(!empty($branding['footer_text'])){{ $branding['footer_text'] }}<br>@endif
    @if(!empty($branding['legal_mentions'])){{ $branding['legal_mentions'] }}<br>@endif
    Généré le {{ now()->format('d/m/Y H:i') }}
  </div>
@endif

</body>
</html>

