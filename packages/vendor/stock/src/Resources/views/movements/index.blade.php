@extends('layouts.global')

@section('title', __('stock::stock.pages.movements.index.title'))

@section('breadcrumb')
  <span>{{ __('stock::stock.common.stock') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('stock::stock.pages.movements.index.heading') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-arrows-rotate', 'bg' => '#eff6ff', 'color' => '#1d4ed8', 'alt' => __('stock::stock.pages.movements.index.heading')])
      <h1 style="margin:0;">{{ __('stock::stock.pages.movements.index.heading') }}</h1>
    </div>
    <p>{{ __('stock::stock.pages.movements.index.description') }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('stock.delivery-notes.index') }}" class="btn btn-secondary"><i class="fas fa-truck-ramp-box"></i> {{ __('stock::stock.common.delivery_notes') }}</a>
    <a href="{{ route('stock.movements.export.excel') }}" class="btn btn-primary"><i class="fas fa-file-excel"></i> {{ __('stock::stock.common.export_excel') }}</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ __('stock::stock.pages.movements.index.table_title') }}</span>
    <div class="table-spacer"></div>
    <input type="date" class="filter-select" data-filter="date_from">
    <input type="date" class="filter-select" data-filter="date_to">
    <select class="filter-select" data-filter="article_id"><option value="">{{ __('stock::stock.common.all_articles') }}</option>@foreach($articles as $article)<option value="{{ $article->id }}" {{ $selectedArticleId == $article->id ? 'selected' : '' }}>{{ $article->name }}{{ $article->sku ? ' (' . $article->sku . ')' : '' }}</option>@endforeach</select>
    <select class="filter-select" data-filter="direction"><option value="">{{ __('stock::stock.common.all_directions') }}</option>@foreach($directions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
    <select class="filter-select" data-filter="movement_type"><option value="">{{ __('stock::stock.common.all_movement_types') }}</option>@foreach($movementTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
  </div>
  <table class="crm-table">
    <thead>
      <tr>
        <th>{{ __('stock::stock.common.date') }}</th>
        <th>{{ __('stock::stock.common.article') }}</th>
        <th>{{ __('stock::stock.common.type') }}</th>
        <th>{{ __('stock::stock.common.directions') }}</th>
        <th>{{ __('stock::stock.common.quantity') }}</th>
        <th>{{ __('stock::stock.common.reference') }}</th>
        <th>{{ __('stock::stock.common.reason') }}</th>
      </tr>
    </thead>
    <tbody id="stockMovementsTableBody"></tbody>
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
document.addEventListener('DOMContentLoaded', () => {
  const stockMovementNone = @json(__('stock::stock.common.none_short'));
  const stockMovementDirectionIn = @json(__('stock::stock.common.direction_in'));
  const stockMovementDirectionOut = @json(__('stock::stock.common.direction_out'));

  window._stockMovementsTable = new CrmTable({
    tbodyId: 'stockMovementsTableBody',
    dataUrl: '{{ route('stock.movements.data') }}',
    renderRow: (movement) => `
      <tr>
        <td>${movement.happened_at_display ?? Stock.formatDateTime(movement.happened_at)}</td>
        <td>${movement.article?.name ?? stockMovementNone}</td>
        <td>${movement.movement_type_label ?? movement.movement_type}</td>
        <td>${movement.direction_label ?? (movement.direction === 'in' ? stockMovementDirectionIn : stockMovementDirectionOut)}</td>
        <td>${movement.direction === 'out' ? '-' : '+'}${movement.quantity}</td>
        <td>${movement.display_reference ?? movement.reference ?? stockMovementNone}</td>
        <td>${movement.display_reason ?? movement.reason ?? stockMovementNone}</td>
      </tr>`,
  });

  const articleFilter = document.querySelector('[data-filter="article_id"]');
  if (articleFilter && articleFilter.value) {
    window._stockMovementsTable.state.filters.article_id = articleFilter.value;
    window._stockMovementsTable.state.page = 1;
    window._stockMovementsTable.load();
  }
});
</script>
@endpush
