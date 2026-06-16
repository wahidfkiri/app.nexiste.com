@extends('invoice::layouts.invoice')

@php
  $page = trans('invoice::invoices.pages.invoices_index');
  $common = trans('invoice::invoices.common');
@endphp

@section('title', __('invoice::invoices.invoices'))

@section('breadcrumb')
  <span>{{ __('invoice::invoices.billing') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('invoice::invoices.invoices') }}</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-file-invoice', 'bg' => '#ede9fe', 'color' => '#7c3aed', 'alt' => __('invoice::invoices.invoices')])
      <h1 style="margin:0;">{{ __('invoice::invoices.invoices') }}</h1>
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
        <a href="{{ route('invoices.export.csv') }}"   class="dropdown-item"><i class="fas fa-file-csv"></i>   CSV</a>
        <a href="{{ route('invoices.export.excel') }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
        <a href="{{ route('invoices.export.pdf') }}"   class="dropdown-item"><i class="fas fa-file-pdf"></i>   PDF</a>
      </div>
    </div>
    <button class="btn btn-secondary" data-modal-open="importModal">
      <i class="fas fa-arrow-up-from-line"></i> {{ __('invoice::invoices.actions.import') }}
    </button>
    <a href="{{ route('invoices.quotes.create') }}" class="btn btn-secondary">
      <i class="fas fa-file-signature"></i> {{ __('invoice::invoices.actions.create_quote') }}
    </a>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> {{ __('invoice::invoices.actions.create_invoice') }}
    </a>
  </div>
</div>

@if(!empty($marketplaceSuggestions))
  <div class="module-app-suggestions">
    @foreach($marketplaceSuggestions as $suggestion)
      <article class="module-app-suggestion-card">
        <div class="module-app-suggestion-icon">
          <i class="{{ $suggestion['icon'] ?? 'fas fa-puzzle-piece' }}"></i>
        </div>
        <div class="module-app-suggestion-body">
          <h3>{{ $suggestion['name'] ?? 'Application' }}</h3>
          <p>{{ $suggestion['description'] ?? '' }}</p>
        </div>
        <a href="{{ $suggestion['url'] ?? route('marketplace.index') }}" class="btn btn-secondary btn-sm">
          <i class="fas fa-store"></i> {{ $page['marketplace_install'] }}
        </a>
      </article>
    @endforeach
  </div>
@endif

{{-- Stats --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-file-invoice"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statTotal">—</div>
      <div class="stat-label">{{ __('invoice::invoices.stats.total_invoices') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-circle-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statPaid">—</div>
      <div class="stat-label">{{ __('invoice::invoices.status.paid') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-danger-lt);color:var(--c-danger)"><i class="fas fa-clock-rotate-left"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statOverdue">—</div>
      <div class="stat-label">{{ __('invoice::invoices.status.overdue') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-circle-euro-sign"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statRevenue">—</div>
      <div class="stat-label">{{ __('invoice::invoices.stats.paid') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="statDue">—</div>
      <div class="stat-label">{{ __('invoice::invoices.stats.outstanding') }}</div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ $page['table_title'] }}</span>
    <span class="table-count" id="invCount">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="{{ $page['search_placeholder'] }}" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="status">
      <option value="">{{ $common['status_all'] }}</option>
      @foreach(config('invoice.invoice_statuses') as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="currency">
      <option value="">{{ $common['currency_all'] }}</option>
      @foreach(config('invoice.currencies') as $code => $cfg)
        <option value="{{ $code }}">{{ $code }}</option>
      @endforeach
    </select>

    <input type="date" class="filter-select" data-filter="date_from" style="width:140px" title="{{ $common['from'] }}">
    <input type="date" class="filter-select" data-filter="date_to"   style="width:140px" title="{{ $common['to'] }}">

    <button class="btn btn-ghost btn-sm" id="resetFilters" title="{{ $common['reset'] }}">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  {{-- Bulk bar --}}
  <div class="bulk-bar" id="bulkBar">
    <span><strong id="selectedCount">0</strong> {{ $page['bulk_selected_suffix'] }}</span>
    <div class="bulk-bar-actions" style="display:flex;gap:6px;">
      <button class="btn btn-sm btn-secondary" onclick="bulkInvoiceAction('send')">
        <i class="fas fa-paper-plane"></i> {{ $page['mark_sent'] }}
      </button>
      <button class="btn btn-sm btn-danger" onclick="bulkInvoiceAction('delete')">
        <i class="fas fa-trash"></i> {{ __('invoice::invoices.actions.delete') }}
      </button>
    </div>
  </div>

  <table class="crm-table" id="invoicesTable">
    <thead>
      <tr>
        <th style="width:40px"><input type="checkbox" id="selectAll"></th>
        <th data-sort="number" class="sortable">{{ $page['number_column'] }} <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th data-sort="client_id" class="sortable">{{ __('invoice::invoices.fields.client') }}</th>
        <th data-sort="issue_date" class="sortable">{{ $page['issue_column'] }}</th>
        <th data-sort="due_date" class="sortable">{{ $page['due_column'] }}</th>
        <th>{{ __('invoice::invoices.fields.currency') }}</th>
        <th data-sort="total" class="sortable" style="text-align:right">{{ $common['total_ttc'] }}</th>
        <th data-sort="amount_due" class="sortable" style="text-align:right">{{ $page['remaining_column'] }}</th>
        <th>{{ __('invoice::invoices.fields.status') }}</th>
        <th style="text-align:right;padding-right:20px">{{ $common['actions_label'] }}</th>
      </tr>
    </thead>
    <tbody id="invoicesTableBody">
      {{-- AJAX --}}
    </tbody>
  </table>

  <div class="table-pagination">
    <span class="pagination-info" id="paginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="paginationControls"></div>
  </div>
</div>

{{-- Import Modal --}}
<div class="modal-overlay" id="importModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)">
        <i class="fas fa-file-import"></i>
      </div>
      <div>
        <div class="modal-title">{{ $page['import_title'] }}</div>
        <div class="modal-subtitle">{{ $page['import_formats'] }}</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="importForm" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
          <label class="form-label">{{ $page['import_file'] }}</label>
          <div id="dropzone" style="border:2px dashed var(--c-ink-10);border-radius:var(--r-md);padding:28px;text-align:center;cursor:pointer;transition:all var(--dur-fast);"
               onclick="document.getElementById('importFile').click()">
            <i class="fas fa-cloud-arrow-up" style="font-size:28px;color:var(--c-ink-20);margin-bottom:10px;display:block;"></i>
            <div style="font-size:14px;color:var(--c-ink-60);margin-bottom:4px;">{{ $page['import_dropzone'] }}</div>
            <div style="font-size:12px;color:var(--c-ink-40);" id="dropzoneText">{{ __('invoice::invoices.js.import_dropzone_default') }}</div>
          </div>
          <input type="file" id="importFile" name="file" accept=".csv,.xlsx,.xls" style="display:none" onchange="handleImportFile(this)">
        </div>
        <div style="background:var(--c-accent-xl);border-radius:var(--r-sm);padding:12px 14px;font-size:12.5px;color:var(--c-ink-60);">
          <i class="fas fa-info-circle" style="color:var(--c-accent)"></i>
          {{ $page['import_template_help'] }}
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('invoice::invoices.actions.cancel') }}</button>
      <button class="btn btn-primary" id="importSubmitBtn" disabled onclick="submitImport()">
        <i class="fas fa-upload"></i> {{ __('invoice::invoices.actions.import') }}
      </button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
const invoiceIndexLang = {
  successTitle: @json(__('invoice::invoices.js.success_title')),
  errorTitle: @json(__('invoice::invoices.js.error_title')),
  irreversibleAction: @json(__('invoice::invoices.alerts.irreversible')),
  importSuccessTitle: @json(__('invoice::invoices.js.import_success_title')),
  importErrorTitle: @json(__('invoice::invoices.js.import_error_title')),
  importFileSelected: @json(__('invoice::invoices.js.import_dropzone_selected')),
  deleteLabel: @json(__('invoice::invoices.actions.delete')),
  bulkDeleteTitle: @json(__('invoice::invoices.js.bulk_invoice_delete_title')),
};

window.CRM_ROUTES = {
  data:       '{{ route("invoices.data") }}',
  stats:      '{{ route("invoices.stats") }}',
  bulkDelete: '{{ route("invoices.bulk.delete") }}',
  bulkSend:   '{{ route("invoices.bulk.send") }}',
  import:     '{{ route("invoices.import") }}',
};
window.INVOICE_CURRENCIES = @json(config('invoice.currencies'));
window.DEFAULT_CURRENCY   = '{{ config('crm-core.formats.currency','EUR') ?? 'EUR' }}';

document.addEventListener('DOMContentLoaded', () => {
  window._invTable = new InvTable({
    tbodyId:  'invoicesTableBody',
    dataUrl:  window.CRM_ROUTES.data,
    statsUrl: window.CRM_ROUTES.stats,
  });
});

function bulkInvoiceAction(action) {
  const ids = window._invTable?.getSelectedIds();
  if (!ids?.length) return;
  if (action === 'delete') {
    Modal.confirm({
      title: invoiceIndexLang.bulkDeleteTitle.replace(':count', ids.length),
      message: invoiceIndexLang.irreversibleAction,
      confirmText: invoiceIndexLang.deleteLabel,
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.post(window.CRM_ROUTES.bulkDelete, { ids });
        if (ok) { Toast.success(invoiceIndexLang.successTitle, data.message); window._invTable?.load(); }
        else Toast.error(invoiceIndexLang.errorTitle, data.message);
      }
    });
  } else if (action === 'send') {
    Http.post(window.CRM_ROUTES.bulkSend, { ids }).then(({ ok, data }) => {
      if (ok) { Toast.success(invoiceIndexLang.successTitle, data.message); window._invTable?.load(); }
      else Toast.error(invoiceIndexLang.errorTitle, data.message);
    });
  }
}

function handleImportFile(input) {
  const file = input.files[0];
  if (file) {
    document.getElementById('dropzoneText').textContent = invoiceIndexLang.importFileSelected
      .replace(':name', file.name)
      .replace(':size', (file.size / 1024).toFixed(1));
    document.getElementById('dropzoneText').style.color = 'var(--c-success)';
    document.getElementById('dropzone').style.borderColor = 'var(--c-success)';
    document.getElementById('importSubmitBtn').disabled = false;
  }
}

async function submitImport() {
  const btn  = document.getElementById('importSubmitBtn');
  const fData = new FormData(document.getElementById('importForm'));
  CrmForm.setLoading(btn, true);
  const { ok, data } = await Http.post(window.CRM_ROUTES.import, fData);
  CrmForm.setLoading(btn, false);
  if (ok) {
    Modal.close(document.getElementById('importModal'));
    Toast.success(invoiceIndexLang.importSuccessTitle, data.message);
    window._invTable?.load();
  } else {
    Toast.error(invoiceIndexLang.importErrorTitle, data.message);
  }
}
</script>
@endpush
