@extends('invoice::layouts.invoice')

@php
  $page = trans('invoice::invoices.pages.quotes_index');
  $common = trans('invoice::invoices.common');
  $tenantCurrency = strtoupper((string) (auth()->user()->tenant->currency ?: config('invoice.default_currency', 'EUR')));
@endphp

@section('title', __('invoice::invoices.quotes'))

@section('breadcrumb')
  <span>{{ __('invoice::invoices.billing') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('invoice::invoices.quotes') }}</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-file-signature', 'bg' => '#e0f2fe', 'color' => '#0369a1', 'alt' => __('invoice::invoices.quotes')])
      <h1 style="margin:0;">{{ __('invoice::invoices.quotes') }}</h1>
    </div>
    <p>{{ $page['subtitle'] }}</p>
  </div>
  <div class="page-header-actions">
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-arrow-down-to-line"></i> {{ __('invoice::invoices.actions.export') }}
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('invoices.quotes.export.csv') }}"   class="dropdown-item"><i class="fas fa-file-csv"></i>   CSV</a>
        <a href="{{ route('invoices.quotes.export.excel') }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
      </div>
    </div>
    <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
      <i class="fas fa-file-invoice"></i> {{ $page['to_invoices'] }}
    </a>
    <a href="{{ route('invoices.quotes.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> {{ __('invoice::invoices.actions.create_quote') }}
    </a>
  </div>
</div>

{{-- Stats --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-file-signature"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statQTotal">—</div>
      <div class="stat-label">{{ __('invoice::invoices.stats.total_quotes') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info)"><i class="fas fa-paper-plane"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statQSent">—</div>
      <div class="stat-label">{{ __('invoice::invoices.quote_status.sent') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-handshake"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statQAccepted">—</div>
      <div class="stat-label">{{ __('invoice::invoices.quote_status.accepted') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-clock-rotate-left"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statQExpired">—</div>
      <div class="stat-label">{{ __('invoice::invoices.quote_status.expired') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-arrow-trend-up"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statQConversion">—</div>
      <div class="stat-label">{{ __('invoice::invoices.stats.conversion_rate') }}</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ $page['table_title'] }}</span>
    <span class="table-count" id="quoteCount">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="{{ $page['search_placeholder'] }}" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="status">
      <option value="">{{ $common['status_all'] }}</option>
      @foreach(config('invoice.quote_statuses') as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <input type="date" class="filter-select" data-filter="date_from" style="width:140px" title="{{ $common['from'] }}">
    <input type="date" class="filter-select" data-filter="date_to"   style="width:140px" title="{{ $common['to'] }}">

    <button class="btn btn-ghost btn-sm" id="resetFilters">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <table class="crm-table" id="quotesTable">
    <thead>
      <tr>
        <th style="width:40px"><input type="checkbox" id="selectAll"></th>
        <th data-sort="number" class="sortable">{{ $page['number_column'] }} <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th data-sort="client_id" class="sortable">{{ __('invoice::invoices.fields.client') }}</th>
        <th data-sort="issue_date" class="sortable">{{ $page['issue_column'] }}</th>
        <th data-sort="valid_until" class="sortable">{{ $page['valid_until_column'] }}</th>
        <th>{{ __('invoice::invoices.fields.currency') }}</th>
        <th data-sort="total" class="sortable" style="text-align:right">{{ $common['total_ttc'] }}</th>
        <th>{{ __('invoice::invoices.fields.status') }}</th>
        <th style="text-align:right;padding-right:20px">{{ $common['actions_label'] }}</th>
      </tr>
    </thead>
    <tbody id="quotesTableBody">
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
const quoteIndexLang = {
  successTitle: @json(__('invoice::invoices.js.success_title')),
  errorTitle: @json(__('invoice::invoices.js.error_title')),
  irreversibleAction: @json(__('invoice::invoices.alerts.irreversible')),
  convertConfirm: @json(__('invoice::invoices.js.quote_convert_confirm')),
  deleteLabel: @json(__('invoice::invoices.actions.delete')),
  convertTitleTemplate: @json(__('invoice::invoices.js.quote_convert_title')),
  deleteTitle: @json(__('invoice::invoices.js.quote_delete_title')),
};

window.QUOTE_ROUTES = {
  data: @json(route('invoices.quotes.data')),
  stats: @json(route('invoices.stats')),
  show: @json(route('invoices.quotes.show', ['quote' => '__QUOTE__'])),
  edit: @json(route('invoices.quotes.edit', ['quote' => '__QUOTE__'])),
  pdf: @json(route('invoices.quotes.pdf', ['quote' => '__QUOTE__'])),
  convert: @json(route('invoices.quotes.convert', ['quote' => '__QUOTE__'])),
  destroy: @json(route('invoices.quotes.destroy', ['quote' => '__QUOTE__'])),
};
window.INVOICE_ROUTES = Object.assign(window.INVOICE_ROUTES || {}, {
  quoteShow: window.QUOTE_ROUTES.show,
  quoteEdit: window.QUOTE_ROUTES.edit,
  quotePdf: window.QUOTE_ROUTES.pdf,
  quoteConvert: window.QUOTE_ROUTES.convert,
  quoteDestroy: window.QUOTE_ROUTES.destroy,
});
window.INVOICE_CURRENCIES = @json(config('invoice.currencies'));
window.DEFAULT_CURRENCY   = '{{ $tenantCurrency }}';
const quoteRoute = (template, id) => String(template).replace('__QUOTE__', encodeURIComponent(String(id)));

document.addEventListener('DOMContentLoaded', () => {
  window._quoteTable = new InvTable({
    tbodyId:  'quotesTableBody',
    dataUrl:  window.QUOTE_ROUTES.data,
    statsUrl: window.QUOTE_ROUTES.stats,
    mode:     'quote',
    countEl:  'quoteCount',
    statsMap: {
      total:    'statQTotal',
      sent:     'statQSent',
      accepted: 'statQAccepted',
      expired:  'statQExpired',
      conversion: 'statQConversion',
    }
  });
});

async function convertQuote(id, number) {
  Modal.confirm({
    title: quoteIndexLang.convertTitleTemplate.replace(':number', number),
    message: @json(__('invoice::invoices.js.quote_convert_message')),
    confirmText: quoteIndexLang.convertConfirm,
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.post(quoteRoute(window.QUOTE_ROUTES.convert, id), {});
      if (ok) { Toast.success(@json(__('invoice::invoices.js.quote_converted_title')), data.message); setTimeout(() => window.location.href = data.redirect, 1000); }
      else Toast.error(quoteIndexLang.errorTitle, data.message);
    }
  });
}

async function deleteQuote(id) {
  Modal.confirm({
    title: quoteIndexLang.deleteTitle,
    message: quoteIndexLang.irreversibleAction,
    confirmText: quoteIndexLang.deleteLabel,
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete(quoteRoute(window.QUOTE_ROUTES.destroy, id));
      if (ok) { Toast.success(quoteIndexLang.successTitle, data.message); window._quoteTable?.load(); }
      else Toast.error(quoteIndexLang.errorTitle, data.message);
    }
  });
}
</script>
@endpush
