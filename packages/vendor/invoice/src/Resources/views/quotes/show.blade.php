@extends('invoice::layouts.invoice')

@section('title', __('invoice::invoices.pages.quote_show.title', ['number' => $quote->number]))

@section('breadcrumb')
  <a href="{{ route('invoices.quotes.index') }}">{{ __('invoice::invoices.quotes') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $quote->number }}</span>
@endsection

@php
  $canConvertQuote = $quote->canBeConvertedToInvoice();
  $convertBlockedReason = $quote->conversionBlockedReason();
@endphp

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $quote->number }}</h1>
    <p>
      <span class="badge badge-{{ $quote->status }}"><span class="badge-dot" style="background:currentColor"></span>{{ $quote->status_label }}</span>
      <span style="margin-left:10px;color:var(--c-ink-40);">{{ __('invoice::invoices.pages.quote_show.issued_on', ['date' => optional($quote->issue_date)->format('d/m/Y')]) }}</span>
    </p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('invoices.quotes.pdf', $quote) }}" data-pdf-export data-pdf-filename="devis-{{ $quote->number }}.pdf" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> PDF</a>
    @if(!$quote->is_converted && !in_array($quote->status, ['declined']))
      @if($canConvertQuote)
        <button class="btn btn-success" onclick="convertQuote({{ $quote->id }}, '{{ $quote->number }}')"><i class="fas fa-arrow-right"></i> {{ __('invoice::invoices.actions.convert') }}</button>
      @else
        <button class="btn btn-secondary" type="button" disabled title="{{ $convertBlockedReason }}"><i class="fas fa-lock"></i> {{ __('invoice::invoices.actions.convert') }}</button>
      @endif
    @endif
    @if(!in_array($quote->status, ['accepted', 'declined']))
      <a href="{{ route('invoices.quotes.edit', $quote) }}" class="btn btn-primary"><i class="fas fa-pen"></i> {{ __('invoice::invoices.actions.edit') }}</a>
    @endif
  </div>
</div>

@if(!$canConvertQuote && !$quote->is_converted)
  <div class="info-card" style="margin-bottom:16px;">
    <div class="info-card-header"><i class="fas fa-circle-info"></i><h3>{{ __('invoice::invoices.pages.quote_show.conversion_unavailable') }}</h3></div>
    <div class="info-card-body">
      <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">{{ $convertBlockedReason }}</p>
      @if(!in_array($quote->status, ['accepted', 'declined']))
        <a href="{{ route('invoices.quotes.edit', $quote) }}" class="btn btn-primary"><i class="fas fa-pen"></i> {{ __('invoice::invoices.pages.quote_show.edit_this_quote') }}</a>
      @endif
    </div>
  </div>
@endif

<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-list"></i><h3>{{ __('invoice::invoices.pages.quote_show.quote_lines') }}</h3></div>
      <table class="crm-table">
        <thead><tr><th>#</th><th>{{ __('invoice::invoices.fields.description') }}</th><th style="text-align:right">{{ __('invoice::invoices.common.line_quantity') }}</th><th style="text-align:right">{{ __('invoice::invoices.common.line_unit_price_ht') }}</th><th style="text-align:right">{{ __('invoice::invoices.common.vat') }}</th><th style="text-align:right">{{ __('invoice::invoices.common.total') }}</th></tr></thead>
        <tbody>
          @foreach($quote->items as $i => $item)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>{{ $item->description }}</td>
              <td style="text-align:right">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
              <td style="text-align:right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
              <td style="text-align:right">{{ $item->tax_rate }}%</td>
              <td style="text-align:right">{{ number_format($item->total, 2, ',', ' ') }} {{ config('invoice.currencies.'.$quote->currency.'.symbol', $quote->currency) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <div style="display:flex;justify-content:flex-end;padding:18px;">
        <div style="width:280px;">
          <div class="totals-row"><span class="totals-label">{{ __('invoice::invoices.common.subtotal') }}</span><span class="totals-value">{{ number_format($quote->subtotal, 2, ',', ' ') }}</span></div>
          <div class="totals-row"><span class="totals-label">{{ __('invoice::invoices.common.vat') }}</span><span class="totals-value">{{ number_format($quote->tax_amount, 2, ',', ' ') }}</span></div>
          <div class="totals-row grand-total"><span class="totals-label">{{ __('invoice::invoices.common.total') }}</span><span class="totals-value">{{ number_format($quote->total, 2, ',', ' ') }} {{ config('invoice.currencies.'.$quote->currency.'.symbol', $quote->currency) }}</span></div>
          @php $__base = strtoupper((string) ($quote->tenant->currency ?? config('invoice.default_currency', 'EUR'))); $__rate = (float) ($quote->exchange_rate ?? 1); @endphp
          @if(strtoupper((string) $quote->currency) !== $__base && $__rate > 0 && abs($__rate - 1.0) > 0.0000001)
          <div class="totals-row grand-total" style="color:var(--c-ink-60);"><span class="totals-label">{{ __('invoice::invoices.common.equivalent_total') }} {{ $__base }}</span><span class="totals-value">{{ \Vendor\Invoice\Support\Money::format((float) $quote->total * $__rate, $__base) }}</span></div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user"></i><h3>{{ __('invoice::invoices.fields.client') }}</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">{{ __('invoice::invoices.pages.quote_show.company') }}</span><span class="info-row-value">{{ $quote->client->company_name }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('invoice::invoices.pages.quote_show.contact') }}</span><span class="info-row-value">{{ $quote->client->contact_name }}</span></div>
        <div class="info-row"><span class="info-row-label">Email</span><span class="info-row-value">{{ $quote->client->email }}</span></div>
      </div>
    </div>

    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-circle-info"></i><h3>{{ __('invoice::invoices.pages.quote_show.details') }}</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">{{ __('invoice::invoices.fields.reference') }}</span><span class="info-row-value">{{ $quote->reference ?: '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('invoice::invoices.pages.quote_show.validity') }}</span><span class="info-row-value">{{ optional($quote->valid_until)->format('d/m/Y') ?: '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('invoice::invoices.fields.currency') }}</span><span class="info-row-value">{{ $quote->currency }}</span></div>
        @if($quote->invoice)
          <div class="info-row"><span class="info-row-label">{{ __('invoice::invoices.pages.quote_show.linked_invoice') }}</span><span class="info-row-value"><a href="{{ route('invoices.show', $quote->invoice) }}">{{ $quote->invoice->number }}</a></span></div>
        @endif
      </div>
    </div>

    @if($quote->notes)
      <div class="info-card">
        <div class="info-card-header"><i class="fas fa-note-sticky"></i><h3>{{ __('invoice::invoices.fields.notes') }}</h3></div>
        <div class="info-card-body">{{ $quote->notes }}</div>
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
const quoteShowLang = {
  successTitle: @json(__('invoice::invoices.js.quote_converted_title')),
  errorTitle: @json(__('invoice::invoices.js.error_title')),
  convertMessage: @json(__('invoice::invoices.js.quote_convert_message_short')),
  convertFallbackSuccess: @json(__('invoice::invoices.messages.quote_converted')),
  convertFallbackError: @json(__('invoice::invoices.alerts.conversion_impossible')),
  convertConfirm: @json(__('invoice::invoices.actions.convert')),
  convertTitleTemplate: @json(__('invoice::invoices.pages.quote_show.convert_title', ['number' => ':number'])),
};
const quoteShowRoutes = {
  convert: @json(route('invoices.quotes.convert', $quote)),
};

async function convertQuote(id, number) {
  Modal.confirm({
    title: quoteShowLang.convertTitleTemplate.replace(':number', number),
    message: quoteShowLang.convertMessage,
    confirmText: quoteShowLang.convertConfirm,
    type: 'success',
    onConfirm: async () => {
      const { ok, data } = await Http.post(quoteShowRoutes.convert, {});
      if (ok) {
        Toast.success(quoteShowLang.successTitle, data.message || quoteShowLang.convertFallbackSuccess);
        if (data.redirect) setTimeout(() => window.location.href = data.redirect, 800);
      } else {
        Toast.error(quoteShowLang.errorTitle, data.message || quoteShowLang.convertFallbackError);
      }
    }
  });
}
</script>
@endpush
