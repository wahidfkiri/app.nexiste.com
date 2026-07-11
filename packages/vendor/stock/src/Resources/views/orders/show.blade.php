@extends('layouts.global')

@php
  $tenantCurrency = strtoupper((string) (auth()->user()->tenant->currency ?: config('invoice.default_currency', 'EUR')));
  $currencySymbol = config("invoice.currencies.{$tenantCurrency}.symbol", $tenantCurrency);
@endphp

@section('title', __('stock::stock.pages.orders.show.title'))

@section('breadcrumb')
  <a href="{{ route('stock.orders.index') }}">{{ __('stock::stock.common.orders') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $order->number }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ $order->number }}</h1><p>{{ __('stock::stock.pages.orders.show.heading_subtitle') }}</p></div>
  <div class="page-header-actions">
    @if($order->status !== 'received' && $order->status !== 'cancelled')
      <button class="btn btn-secondary" onclick="receiveOrder()"><i class="fas fa-truck-ramp-box"></i> {{ __('stock::stock.common.receive_delivery_note') }}</button>
    @endif
    <a href="{{ route('stock.orders.edit', $order) }}" class="btn btn-primary">{{ __('stock::stock.common.edit') }}</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="form-section" style="margin-bottom:18px;">
  <div class="row">
    <div class="col-3"><strong>{{ __('stock::stock.common.supplier') }}</strong><div>{{ $order->supplier?->name }}</div></div>
    <div class="col-3"><strong>{{ __('stock::stock.common.date_order') }}</strong><div>{{ optional($order->order_date)->format('Y-m-d') }}</div></div>
    <div class="col-3"><strong>{{ __('stock::stock.common.status') }}</strong><div>{{ $order->status_label }}</div></div>
    <div class="col-3"><strong>{{ __('stock::stock.common.total') }}</strong><div>{{ number_format((float) $order->total, 2, ',', ' ') }} {{ $currencySymbol }}</div></div>
  </div>
</div>

<div class="table-wrapper" style="margin-bottom:18px;">
  <table class="crm-table">
    <thead><tr><th>{{ __('stock::stock.common.article') }}</th><th>{{ __('stock::stock.common.quantity') }}</th><th>{{ __('stock::stock.common.unit') }}</th><th>{{ __('stock::stock.common.purchase_price') }}</th><th>{{ __('stock::stock.common.total') }}</th></tr></thead>
    <tbody>@foreach($order->items as $item)<tr><td>{{ $item->name }}</td><td>{{ $item->quantity }}</td><td>{{ $item->unit }}</td><td>{{ number_format((float) $item->unit_price, 2, ',', ' ') }} {{ $currencySymbol }}</td><td>{{ number_format((float) $item->total, 2, ',', ' ') }} {{ $currencySymbol }}</td></tr>@endforeach</tbody>
  </table>
</div>

<div class="table-wrapper">
  <div class="table-header"><span class="table-title">{{ __('stock::stock.pages.orders.show.linked_delivery_notes') }}</span></div>
  <table class="crm-table">
    <thead><tr><th>{{ __('stock::stock.pages.orders.index.columns.number') }}</th><th>{{ __('stock::stock.common.type') }}</th><th>{{ __('stock::stock.common.status') }}</th><th>{{ __('stock::stock.common.date') }}</th></tr></thead>
    <tbody>
      @forelse($order->deliveryNotes as $note)
        <tr>
          <td><a href="{{ route('stock.delivery-notes.show', $note) }}">{{ $note->number }}</a></td>
          <td>{{ $note->type_label }}</td>
          <td>{{ $note->status_label }}</td>
          <td>{{ optional($note->issue_date)->format('Y-m-d') ?: '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="4"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-truck-ramp-box"></i></div><h3>{{ __('stock::stock.pages.orders.show.no_delivery_notes_title') }}</h3><p>{{ __('stock::stock.pages.orders.show.no_delivery_notes_description') }}</p></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection

@push('scripts')
<script>
async function receiveOrder() {
  const { ok, data } = await Http.post('{{ route('stock.orders.receive', $order) }}', {});
  if (ok && data.success) {
    Toast.success(@json(__('stock::stock.common.success')), data.message || @json(__('stock::stock.pages.orders.show.receive_success')));

    if (data.automation?.should_prompt && window.AutomationSuggestions) {
      const flow = window.AutomationSuggestions.open(data.automation, {
        redirectUrl: data.redirect || null,
      });

      await Promise.resolve(flow).finally(() => {
        if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          window.location.reload();
        }
      });

      return;
    }

    if (data.redirect) {
      window.location.href = data.redirect;
    } else {
      window.location.reload();
    }
  } else {
    Toast.error(@json(__('stock::stock.common.error')), data.message || @json(__('stock::stock.pages.orders.show.receive_error')));
  }
}
</script>
@endpush
