@extends('layouts.global')

@section('title', __('stock::stock.pages.delivery_notes.index.title'))

@section('breadcrumb')
  <span>{{ __('stock::stock.common.stock') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('stock::stock.common.delivery_notes') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-truck-ramp-box', 'bg' => '#ecfeff', 'color' => '#0f766e', 'alt' => __('stock::stock.pages.delivery_notes.index.heading')])
      <h1 style="margin:0;">{{ __('stock::stock.pages.delivery_notes.index.heading') }}</h1>
    </div>
    <p>{{ __('stock::stock.pages.delivery_notes.index.description') }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('stock.movements.index') }}" class="btn btn-secondary"><i class="fas fa-arrows-rotate"></i> {{ __('stock::stock.common.history') }}</a>
    <a href="{{ route('stock.delivery-notes.export.excel') }}" class="btn btn-secondary"><i class="fas fa-file-excel"></i> {{ __('stock::stock.common.export_excel') }}</a>
    <a href="{{ route('stock.delivery-notes.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('stock::stock.common.new_delivery_note') }}</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ __('stock::stock.pages.delivery_notes.index.table_title') }}</span>
    <div class="table-spacer"></div>
    <div class="table-search"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="{{ __('stock::stock.common.search_number_reference_partner') }}"></div>
    <select class="filter-select" data-filter="type"><option value="">{{ __('stock::stock.common.all_types') }}</option>@foreach($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
    <select class="filter-select" data-filter="status"><option value="">{{ __('stock::stock.common.all_statuses') }}</option>@foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
  </div>
  <table class="crm-table">
    <thead>
      <tr>
        <th>{{ __('stock::stock.pages.delivery_notes.index.columns.number') }}</th>
        <th>{{ __('stock::stock.pages.delivery_notes.index.columns.type') }}</th>
        <th>{{ __('stock::stock.pages.delivery_notes.index.columns.partner') }}</th>
        <th>{{ __('stock::stock.pages.delivery_notes.index.columns.date') }}</th>
        <th>{{ __('stock::stock.pages.delivery_notes.index.columns.status') }}</th>
        <th>{{ __('stock::stock.pages.delivery_notes.index.columns.lines') }}</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="deliveryNotesTableBody"></tbody>
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
const STOCK_DELIVERY_NOTE_ROUTES = {
  show: @json(route('stock.delivery-notes.show', ['deliveryNote' => '__DELIVERY_NOTE__'])),
  edit: @json(route('stock.delivery-notes.edit', ['deliveryNote' => '__DELIVERY_NOTE__'])),
};
const stockDeliveryNoteRoute = (template, id) => String(template).replace('__DELIVERY_NOTE__', encodeURIComponent(String(id)));

document.addEventListener('DOMContentLoaded', () => {
  window._stockDeliveryNotesTable = new CrmTable({
    tbodyId: 'deliveryNotesTableBody',
    dataUrl: '{{ route('stock.delivery-notes.data') }}',
    renderRow: (note) => {
      const partner = note.type === 'in' ? (note.supplier?.name ?? '—') : (note.client?.company_name ?? '—');
      const typeLabel = note.type === 'in' ? @json(__('stock::stock.common.entry_bl')) : @json(__('stock::stock.common.output_bl'));
      const badgeTone = note.status === 'validated' ? 'paid' : (note.status === 'cancelled' ? 'cancelled' : 'sent');
      return `
        <tr>
          <td><a href="${stockDeliveryNoteRoute(STOCK_DELIVERY_NOTE_ROUTES.show, note.uuid ?? note.id)}" style="color:var(--c-accent);font-weight:600;text-decoration:none;">${note.number}</a><div style="font-size:11px;color:var(--c-ink-40);">${note.reference ?? '—'}</div></td>
          <td>${typeLabel}</td>
          <td>${partner}</td>
          <td>${Stock.formatDate(note.issue_date)}</td>
          <td><span class="badge badge-${badgeTone}">${note.status_label ?? note.status}</span></td>
          <td>${note.items?.length ?? 0}</td>
          <td><a class="btn-icon" href="${stockDeliveryNoteRoute(STOCK_DELIVERY_NOTE_ROUTES.edit, note.uuid ?? note.id)}"><i class="fas fa-pen"></i></a></td>
        </tr>`;
    },
  });
});
</script>
@endpush
