@extends('invoice::layouts.invoice')

@php
  $paymentsPage = trans('invoice::invoices.pages.payments_index');
  $common = trans('invoice::invoices.common');
  $tenantCurrency = strtoupper((string) (auth()->user()->tenant->currency ?: config('invoice.default_currency', 'EUR')));
@endphp

@section('title', __('invoice::invoices.payments'))

@section('breadcrumb')
  <span>{{ __('invoice::invoices.billing') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('invoice::invoices.payments') }}</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-money-check', 'bg' => 'var(--c-success-lt)', 'color' => 'var(--c-success)', 'alt' => __('invoice::invoices.payments')])
      <h1 style="margin:0;">{{ $paymentsPage['title'] }}</h1>
    </div>
    <p>{{ $paymentsPage['subtitle'] }}</p>
  </div>
  <div class="page-header-actions">
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-arrow-down-to-line"></i> {{ __('invoice::invoices.actions.export') }}
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('invoices.payments.export.csv') }}"   class="dropdown-item"><i class="fas fa-file-csv"></i>   CSV</a>
        <a href="{{ route('invoices.payments.export.excel') }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
      </div>
    </div>
  </div>
</div>

{{-- Stats --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-money-bill-wave"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statPTotal">—</div>
      <div class="stat-label">{{ $paymentsPage['stats_total'] }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-calendar-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statPMonth">—</div>
      <div class="stat-label">{{ $paymentsPage['stats_month'] }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info)"><i class="fas fa-credit-card"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statPCount">—</div>
      <div class="stat-label">{{ $paymentsPage['stats_count'] }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-building-columns"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statPTransfer">—</div>
      <div class="stat-label">{{ $paymentsPage['stats_transfer'] }}</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ $paymentsPage['table_title'] }}</span>
    <span class="table-count" id="payCount">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="{{ $paymentsPage['search_placeholder'] }}" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="payment_method">
      <option value="">{{ $paymentsPage['all_methods'] }}</option>
      @foreach(config('invoice.payment_methods') as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <input type="date" class="filter-select" data-filter="date_from" style="width:140px" title="{{ $common['from'] }}">
    <input type="date" class="filter-select" data-filter="date_to"   style="width:140px" title="{{ $common['to'] }}">

    <button class="btn btn-ghost btn-sm" id="resetFilters">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <table class="crm-table" id="paymentsTable">
    <thead>
      <tr>
        <th data-sort="payment_date" class="sortable">{{ $paymentsPage['date_column'] }} <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th>{{ $paymentsPage['invoice_column'] }}</th>
        <th>{{ $paymentsPage['client_column'] }}</th>
        <th data-sort="payment_method" class="sortable">{{ $paymentsPage['method_column'] }}</th>
        <th>{{ $paymentsPage['reference_column'] }}</th>
        <th>{{ $paymentsPage['bank_column'] }}</th>
        <th data-sort="amount" class="sortable" style="text-align:right">{{ $paymentsPage['amount_column'] }}</th>
        <th style="text-align:right;padding-right:20px">{{ $common['actions_label'] }}</th>
      </tr>
    </thead>
    <tbody id="paymentsTableBody">
      {{-- AJAX --}}
    </tbody>
  </table>

  <div class="table-pagination">
    <span class="pagination-info" id="paginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="paginationControls"></div>
  </div>
</div>

@endsection

@push('scripts')
<script>
window.INVOICE_CURRENCIES = @json(config('invoice.currencies'));
window.DEFAULT_CURRENCY   = '{{ $tenantCurrency }}';

document.addEventListener('DOMContentLoaded', () => {
  window._payTable = new InvTable({
    tbodyId: 'paymentsTableBody',
    dataUrl: '{{ route("invoices.payments.data") }}',
    statsUrl:'{{ route("invoices.payments.stats") }}',
    mode:    'payment',
    countEl: 'payCount',
    statsMap:{ total:'statPTotal', month:'statPMonth', count:'statPCount', transfer:'statPTransfer' }
  });
});
</script>
@endpush
