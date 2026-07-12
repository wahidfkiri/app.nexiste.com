@extends('client::layouts.crm')

@section('title', __('client::clients.pages.index.title'))

@section('breadcrumb')
  <span>CRM</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('client::clients.pages.index.title') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-users', 'bg' => 'var(--c-accent-lt)', 'color' => 'var(--c-accent)', 'alt' => __('client::clients.pages.index.title')])
      <h1 style="margin:0;">{{ __('client::clients.pages.index.title') }}</h1>
    </div>
    <p>{{ __('client::clients.pages.index.subtitle') }}</p>
  </div>
  <div class="page-header-actions">
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-arrow-down-to-line"></i> {{ __('client::clients.actions.export') }}
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('clients.export.csv') }}" class="dropdown-item"><i class="fas fa-file-csv"></i> CSV</a>
        <a href="{{ route('clients.export.excel') }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
        <a href="{{ route('clients.export.pdf') }}" data-pdf-export data-pdf-filename="clients.pdf" class="dropdown-item"><i class="fas fa-file-pdf"></i> PDF</a>
      </div>
    </div>
    <button class="btn btn-secondary" data-modal-open="importModal">
      <i class="fas fa-arrow-up-from-line"></i> {{ __('client::clients.actions.import') }}
    </button>
    <a href="{{ route('clients.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> {{ __('client::clients.actions.create') }}
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
        <h3>{{ $suggestion['name'] ?? __('client::clients.marketplace.application') }}</h3>
        <p>{{ $suggestion['description'] ?? '' }}</p>
      </div>
      <a href="{{ $suggestion['url'] ?? route('marketplace.index') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-store"></i> {{ __('client::clients.marketplace.install') }}
      </a>
    </article>
  @endforeach
</div>
@endif

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-users"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="totalClients">—</div>
      <div class="stat-label">{{ __('client::clients.stats.total') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-user-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="activeClients">—</div>
      <div class="stat-label">{{ __('client::clients.stats.active') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-clock"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="pendingClients">—</div>
      <div class="stat-label">{{ __('client::clients.stats.pending') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-coins"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="totalRevenue">—</div>
      <div class="stat-label">{{ __('client::clients.stats.revenue') }}</div>
    </div>
  </div>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ __('client::clients.pages.index.table_title') }}</span>
    <span class="table-count">—</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="{{ __('client::clients.placeholders.search') }}" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="type">
      <option value="">{{ __('client::clients.table.all_types') }}</option>
      @foreach($types as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="status">
      <option value="">{{ __('client::clients.table.all_statuses') }}</option>
      @foreach($statuses as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="source">
      <option value="">{{ __('client::clients.table.all_sources') }}</option>
      @foreach($sources as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <button class="btn btn-ghost btn-sm" id="resetFilters" title="{{ __('client::clients.table.reset_filters') }}">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <div class="bulk-bar" id="bulkBar">
    <span><strong id="selectedCount">0</strong> {{ __('client::clients.bulk.selected_suffix') }}</span>
    <div class="bulk-bar-actions">
      <button class="btn btn-sm btn-secondary" onclick="bulkStatus('actif')">
        <i class="fas fa-check-circle"></i> {{ __('client::clients.bulk.activate') }}
      </button>
      <button class="btn btn-sm btn-secondary" onclick="bulkStatus('inactif')">
        <i class="fas fa-ban"></i> {{ __('client::clients.bulk.deactivate') }}
      </button>
      <button class="btn btn-sm btn-danger" onclick="bulkDelete()">
        <i class="fas fa-trash"></i> {{ __('client::clients.actions.delete') }}
      </button>
    </div>
  </div>

  <table class="crm-table" id="clientsTable">
    <thead>
      <tr>
        <th style="width:40px"><input type="checkbox" id="selectAll"></th>
        <th data-sort="company_name" class="sortable">{{ __('client::clients.table.client') }} <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th>{{ __('client::clients.fields.type') }}</th>
        <th data-sort="email" class="sortable">{{ __('client::clients.table.email') }}</th>
        <th>{{ __('client::clients.table.phone') }}</th>
        <th data-sort="status" class="sortable">{{ __('client::clients.table.status') }}</th>
        <th data-sort="revenue" class="sortable">{{ __('client::clients.table.revenue') }}</th>
        <th style="text-align:right;padding-right:20px">{{ __('client::clients.table.actions') }}</th>
      </tr>
    </thead>
    <tbody id="clientsTableBody"></tbody>
  </table>

  <div class="table-pagination">
    <span class="pagination-info" id="paginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="paginationControls"></div>
  </div>
</div>

<div class="modal-overlay" id="importModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)">
        <i class="fas fa-file-import"></i>
      </div>
      <div>
        <div class="modal-title">{{ __('client::clients.import.title') }}</div>
        <div class="modal-subtitle">{{ __('client::clients.import.subtitle') }}</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="importForm" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
          <label class="form-label">{{ __('client::clients.fields.file') }}</label>
          <div style="border:2px dashed var(--c-ink-10);border-radius:var(--r-md);padding:28px;text-align:center;cursor:pointer;transition:all var(--dur-fast);"
               id="dropzone" onclick="document.getElementById('importFile').click()">
            <i class="fas fa-cloud-arrow-up" style="font-size:28px;color:var(--c-ink-20);margin-bottom:10px;display:block;"></i>
            <div style="font-size:14px;color:var(--c-ink-60);margin-bottom:4px;">{{ __('client::clients.import.dropzone_title') }}</div>
            <div style="font-size:12px;color:var(--c-ink-40);" id="dropzoneText">{{ __('client::clients.import.dropzone_help') }}</div>
          </div>
          <input type="file" id="importFile" name="file" accept=".csv,.xlsx,.xls" style="display:none" onchange="handleFileSelect(this)">
        </div>
        <div style="background:var(--c-accent-xl);border-radius:var(--r-sm);padding:12px 14px;font-size:12.5px;color:var(--c-ink-60);">
          <i class="fas fa-info-circle" style="color:var(--c-accent)"></i>
          {{ __('client::clients.import.template_intro') }}
          <a href="{{ route('clients.import.template') }}" style="color:var(--c-accent)">{{ __('client::clients.import.template_link') }}</a>
          {{ __('client::clients.import.template_tail') }}
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('client::clients.actions.cancel') }}</button>
      <button class="btn btn-primary" id="importSubmitBtn" disabled onclick="submitImport()">
        <i class="fas fa-upload"></i> {{ __('client::clients.actions.import') }}
      </button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.DEFAULT_CURRENCY = '{{ strtoupper((string) (auth()->user()->tenant->currency ?: config('invoice.default_currency', 'EUR'))) }}';
window.CRM_ROUTES = {
  data: '{{ route("clients.data") }}',
  stats: '{{ route("clients.stats") }}',
  create: '{{ route("clients.create") }}',
  bulkDelete: '{{ route("clients.bulk.delete") }}',
  bulkStatus: '{{ route("clients.bulk.status") }}',
};

window.CLIENT_LANG = {
  title: @json(__('client::clients.pages.index.title')),
  emptyTitle: @json(__('client::clients.pages.index.empty_title')),
  emptyText: @json(__('client::clients.pages.index.empty_text')),
  createAction: @json(__('client::clients.actions.create')),
  tableNone: @json(__('client::clients.table.none')),
  showing: @json(__('client::clients.table.showing')),
  loadingError: @json(__('client::clients.messages.loading_error')),
  successTitle: @json(__('client::clients.messages.success_title')),
  errorTitle: @json(__('client::clients.messages.error_title')),
  validationTitle: @json(__('client::clients.messages.validation_title')),
  deletedTitle: @json(__('client::clients.messages.deleted')),
  deleteTitle: @json(__('client::clients.confirmations.delete_title')),
  deleteMessage: @json(__('client::clients.confirmations.delete_message', ['name' => ':name'])),
  bulkDeleteTitle: @json(__('client::clients.confirmations.bulk_delete_title', ['count' => ':count'])),
  bulkDeleteMessage: @json(__('client::clients.confirmations.bulk_delete_message')),
  operationSuccess: @json(__('client::clients.messages.operation_success')),
  unexpectedError: @json(__('client::clients.messages.unexpected_error')),
  importedTitle: @json(__('client::clients.import.success_title')),
  importErrorTitle: @json(__('client::clients.import.error_title')),
  statuses: @json(trans('client::clients.statuses')),
  types: @json(trans('client::clients.types')),
};

document.addEventListener('DOMContentLoaded', () => {
  window._crmTable = new CrmTable({
    tableId: 'clientsTable',
    tbodyId: 'clientsTableBody',
    dataUrl: window.CRM_ROUTES.data,
    statsUrl: window.CRM_ROUTES.stats,
    perPage: 15,
  });
});

function handleFileSelect(input) {
  const file = input.files[0];
  const btn = document.getElementById('importSubmitBtn');
  const text = document.getElementById('dropzoneText');
  if (file) {
    text.textContent = @json(__('client::clients.import.selected_file', ['name' => ':name', 'size' => ':size']))
      .replace(':name', file.name)
      .replace(':size', (file.size / 1024).toFixed(1));
    text.style.color = 'var(--c-success)';
    document.getElementById('dropzone').style.borderColor = 'var(--c-success)';
    btn.disabled = false;
  }
}

async function submitImport() {
  const btn = document.getElementById('importSubmitBtn');
  const form = document.getElementById('importForm');
  const fData = new FormData(form);
  CrmForm.setLoading(btn, true);

  const { ok, data } = await Http.post('{{ route("clients.import") }}', fData);
  CrmForm.setLoading(btn, false);

  if (ok) {
    Modal.close(document.getElementById('importModal'));
    Toast.success(window.CLIENT_LANG.importedTitle, data.message);
    window._crmTable?.load();
    window._crmTable?.loadStats();
  } else {
    Toast.error(window.CLIENT_LANG.importErrorTitle, data.message);
  }
}

const dropzone = document.getElementById('dropzone');
if (dropzone) {
  dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.style.background = 'var(--c-accent-xl)'; });
  dropzone.addEventListener('dragleave', () => { dropzone.style.background = ''; });
  dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.style.background = '';
    const file = e.dataTransfer.files[0];
    if (file) {
      const dt = new DataTransfer();
      dt.items.add(file);
      const inp = document.getElementById('importFile');
      inp.files = dt.files;
      handleFileSelect(inp);
    }
  });
}
</script>
@endpush
