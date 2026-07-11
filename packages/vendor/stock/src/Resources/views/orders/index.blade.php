@extends('layouts.global')

@php
  $tenantCurrency = strtoupper((string) (auth()->user()->tenant->currency ?: config('invoice.default_currency', 'EUR')));
  $currencySymbol = config("invoice.currencies.{$tenantCurrency}.symbol", $tenantCurrency);
@endphp

@section('title', __('stock::stock.pages.orders.index.title'))

@section('breadcrumb')
  <span>{{ __('stock::stock.common.stock') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('stock::stock.common.orders') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-truck-loading', 'bg' => '#e0f2fe', 'color' => '#0891b2', 'alt' => __('stock::stock.pages.orders.index.heading')])
      <h1 style="margin:0;">{{ __('stock::stock.pages.orders.index.heading') }}</h1>
    </div>
    <p>{{ __('stock::stock.pages.orders.index.description') }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('stock.delivery-notes.index') }}" class="btn btn-secondary">{{ __('stock::stock.common.delivery_notes') }}</a>
    <a href="{{ route('stock.orders.export.excel') }}" class="btn btn-secondary">{{ __('stock::stock.common.export_excel') }}</a>
    <a href="{{ route('stock.orders.create') }}" class="btn btn-primary">{{ __('stock::stock.common.new_order') }}</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ __('stock::stock.pages.orders.index.table_title') }}</span>
    <div class="table-spacer"></div>
    <div class="table-search"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="{{ __('stock::stock.common.search_number_supplier') }}"></div>
    <select class="filter-select" data-filter="status"><option value="">{{ __('stock::stock.common.all_statuses') }}</option>@foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
  </div>
  <table class="crm-table">
    <thead><tr><th>{{ __('stock::stock.pages.orders.index.columns.number') }}</th><th>{{ __('stock::stock.pages.orders.index.columns.supplier') }}</th><th>{{ __('stock::stock.pages.orders.index.columns.date') }}</th><th>{{ __('stock::stock.pages.orders.index.columns.total') }}</th><th>{{ __('stock::stock.pages.orders.index.columns.status') }}</th><th></th></tr></thead>
    <tbody id="ordersTableBody"></tbody>
  </table>
  <div class="table-pagination"><span class="pagination-info" id="paginationInfo"></span><div class="pagination-spacer"></div><div class="pagination-pages" id="paginationControls"></div></div>
</div>
@endsection

@push('scripts')
<script>
const STOCK_ORDER_ROUTES = {
  show: @json(route('stock.orders.show', ['order' => '__ORDER__'])),
  edit: @json(route('stock.orders.edit', ['order' => '__ORDER__'])),
};
const stockOrderRoute = (template, id) => String(template).replace('__ORDER__', encodeURIComponent(String(id)));
const STOCK_CURRENCY_SYMBOL = @json($currencySymbol);
const formatStockPrice = (value) => {
  const n = Number(value);
  if (!isFinite(n)) return '—';
  return `${n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${STOCK_CURRENCY_SYMBOL}`;
};

document.addEventListener('DOMContentLoaded', () => {
 window._stockOrdersTable = new CrmTable({
  tbodyId:'ordersTableBody',
  dataUrl:'{{ route('stock.orders.data') }}',
  renderRow:(order)=>`<tr><td><a href="${stockOrderRoute(STOCK_ORDER_ROUTES.show, order.id)}" style="color:var(--c-accent);font-weight:600;text-decoration:none;">${order.number}</a></td><td>${order.supplier?.name ?? '—'}</td><td>${Stock.formatDate(order.order_date)}</td><td>${formatStockPrice(order.total)}</td><td><span class="badge badge-${order.status==='received'?'paid':(order.status==='cancelled'?'cancelled':'sent')}">${order.status_label ?? order.status}</span></td><td><a class="btn-icon" href="${stockOrderRoute(STOCK_ORDER_ROUTES.edit, order.id)}"><i class="fas fa-pen"></i></a></td></tr>`
 });
});
</script>
@endpush
