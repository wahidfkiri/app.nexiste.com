<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Facture {{ $invoice->number }}</title>
  @php
    $status = (string) ($invoice->status ?? 'draft');
  @endphp
  <style>
    * { box-sizing: border-box; }
    body { font-family: "DM Sans", sans-serif; font-size: 10pt; color: #0f172a; margin: 0; }
    .wrap { padding: 34px 36px 120px; }
    .muted { color: #64748b; }
    .row { width:100%; border-collapse: collapse; }
    .h1 { font-size: 22pt; font-weight: 800; margin: 0; }
    .kicker { font-size: 8pt; text-transform: uppercase; letter-spacing: .14em; color: #64748b; }
    .box { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; background: #fff; }
    .meta { width:100%; border-collapse: collapse; margin-top: 10px; }
    .meta td { width:25%; border-top: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; padding: 10px 12px; }
    .meta td:last-child { border-right: none; }
    .meta-label { font-size: 7.2pt; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin-bottom: 4px; }
    .meta-val { font-size: 9.4pt; font-weight: 700; }
    .addr { width:100%; border-collapse: collapse; margin-top: 14px; }
    .addr td { width:50%; vertical-align: top; border: 1px solid #e5e7eb; padding: 12px; }
    .addr-title { font-size: 7.5pt; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin-bottom: 6px; }
    .addr-name { font-size: 11pt; font-weight: 800; margin-bottom: 4px; }
    .items { width: 100%; border-collapse: collapse; margin-top: 16px; }
    .items thead th { border-bottom: 2px solid #0f172a; padding: 9px 8px; font-size: 7.8pt; text-transform: uppercase; letter-spacing: .05em; text-align:left; }
    .items tbody td { border-bottom: 1px solid #e5e7eb; padding: 8px; font-size: 8.9pt; vertical-align: top; }
    .right { text-align: right; }
    .totals { width: 44%; margin-left: auto; border-collapse: collapse; margin-top: 12px; }
    .totals td { border-bottom: 1px solid #e5e7eb; padding: 7px 10px; font-size: 9pt; }
    .totals .label { color: #64748b; }
    .totals .grand td { border-top: 2px solid #0f172a; font-size: 11pt; font-weight: 800; }
    .signature { margin-top: 14px; text-align: right; }
    .signature img { max-height: 70px; max-width: 220px; display: block; margin-left: auto; }
    .footer { position: fixed; left: 36px; right: 36px; bottom: 18px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 7.8pt; color: #64748b; text-align: center; line-height: 1.5; }
    .watermark {
      position: fixed;
      top: 43%;
      left: 10%;
      font-size: 72pt;
      color: rgba(15, 23, 42, 0.05);
      transform: rotate(-28deg);
      z-index: -1;
      font-weight: 800;
      letter-spacing: .08em;
    }
    @page { margin: 0; }
  </style>
</head>
<body>
@if(in_array($status, ['draft', 'cancelled']))
  <div class="watermark">{{ mb_strtoupper($status === 'draft' ? __('invoice::invoices.status.draft') : __('invoice::invoices.status.cancelled')) }}</div>
@endif

<div class="wrap">
  <div class="box">
    <table class="row">
      <tr>
        <td style="width:60%;vertical-align:top;">
          <div class="kicker">{{ __('invoice::invoices.common.invoice') }}</div>
          <div class="h1">{{ $invoice->number }}</div>
          @if($invoice->reference)<div class="muted" style="font-size:8.8pt;margin-top:4px;">Référence : {{ $invoice->reference }}</div>@endif
          <div class="muted" style="font-size:8.8pt;line-height:1.5;margin-top:10px;">
            <strong>{{ $invoice->tenant->name ?? config('app.name') }}</strong><br>
            {{ $invoice->tenant->address ?? '' }}<br>
            {{ $invoice->tenant->email ?? '' }}
            @if(!empty($invoice->tenant->phone))<br>{{ $invoice->tenant->phone }}@endif
          </div>
        </td>
        <td style="width:40%;vertical-align:top;text-align:right;">
          <div class="muted" style="font-size:8.6pt;">{{ __('invoice::invoices.fields.status') }}</div>
          <div style="font-weight:800;">{{ $invoice->status_label ?? $status }}</div>
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
          <div class="meta-val">{{ $invoice->currency ?? config('invoice.default_currency', 'EUR') }} {{ $invoice->currency_symbol ?? '' }}</div>
        </td>
        <td>
          <div class="meta-label">{{ __('invoice::invoices.pages.invoice_show.payment') }}</div>
          <div class="meta-val">{{ $invoice->payment_method ? (config("invoice.payment_methods.{$invoice->payment_method}") ?? $invoice->payment_method) : '-' }}</div>
        </td>
      </tr>
    </table>
  </div>

  <table class="addr">
    <tr>
      <td>
        <div class="addr-title">{{ __('invoice::invoices.common.issuer') }}</div>
        <div class="addr-name">{{ $invoice->tenant->name ?? config('app.name') }}</div>
        <div class="muted" style="font-size:8.8pt;line-height:1.55;">{{ $invoice->tenant->address ?? '' }}<br>{{ $invoice->tenant->email ?? '' }}</div>
      </td>
      <td>
        <div class="addr-title">{{ __('invoice::invoices.common.billed_to') }}</div>
        <div class="addr-name">{{ $invoice->client->company_name ?? '-' }}</div>
        <div class="muted" style="font-size:8.8pt;line-height:1.55;">
          {{ $invoice->client->contact_name ?? '' }}<br>
          {{ $invoice->client->full_address ?? '' }}<br>
          {{ $invoice->client->email ?? '' }}
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
      @foreach($invoice->items as $i => $item)
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
      <td class="right"><strong>{{ number_format((float) $invoice->subtotal, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</strong></td>
    </tr>
    @if((float) $invoice->discount_amount > 0)
      <tr>
        <td class="label">{{ __('invoice::invoices.fields.discount') }}</td>
        <td class="right">-{{ number_format((float) $invoice->discount_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</td>
      </tr>
    @endif
    <tr>
      <td class="label">{{ __('invoice::invoices.common.vat') }}</td>
      <td class="right">{{ number_format((float) $invoice->tax_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</td>
    </tr>
    @if((float) $invoice->withholding_tax_rate > 0)
      <tr>
        <td class="label">{{ __('invoice::invoices.withholding.label') }}</td>
        <td class="right">-{{ number_format((float) $invoice->withholding_tax_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</td>
      </tr>
    @endif
    <tr class="grand">
      <td>{{ __('invoice::invoices.common.total_ttc') }}</td>
      <td class="right">{{ number_format((float) $invoice->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</td>
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
