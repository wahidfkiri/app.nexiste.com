@extends('layouts.global')

@section('title', __('stock::stock.pages.suppliers.index.title'))

@section('breadcrumb')
  <span>{{ __('stock::stock.common.stock') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('stock::stock.common.suppliers') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-building', 'bg' => '#f3e8ff', 'color' => '#7c3aed', 'alt' => __('stock::stock.pages.suppliers.index.heading')])
      <h1 style="margin:0;">{{ __('stock::stock.pages.suppliers.index.heading') }}</h1>
    </div>
    <p>{{ __('stock::stock.pages.suppliers.index.description') }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('stock.orders.index') }}" class="btn btn-secondary">{{ __('stock::stock.common.orders') }}</a>
    <a href="{{ route('stock.suppliers.export.excel') }}" class="btn btn-secondary">{{ __('stock::stock.common.export_excel') }}</a>
    <a href="{{ route('stock.suppliers.create') }}" class="btn btn-primary">{{ __('stock::stock.common.new_supplier') }}</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ __('stock::stock.pages.suppliers.index.table_title') }}</span>
    <div class="table-spacer"></div>
    <div class="table-search"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="{{ __('stock::stock.common.search_name_email') }}"></div>
  </div>
  <table class="crm-table">
    <thead><tr><th>{{ __('stock::stock.common.name') }}</th><th>{{ __('stock::stock.common.contact') }}</th><th>{{ __('stock::stock.common.email') }}</th><th>{{ __('stock::stock.common.phone') }}</th><th></th></tr></thead>
    <tbody id="suppliersTableBody"></tbody>
  </table>
  <div class="table-pagination"><span class="pagination-info" id="paginationInfo"></span><div class="pagination-spacer"></div><div class="pagination-pages" id="paginationControls"></div></div>
</div>
@endsection

@push('scripts')
<script>
const STOCK_SUPPLIER_ROUTES = {
  show: @json(route('stock.suppliers.show', ['supplier' => '__SUPPLIER__'])),
  edit: @json(route('stock.suppliers.edit', ['supplier' => '__SUPPLIER__'])),
};
const stockSupplierRoute = (template, id) => String(template).replace('__SUPPLIER__', encodeURIComponent(String(id)));

document.addEventListener('DOMContentLoaded', () => {
 window._stockSuppliersTable = new CrmTable({
  tbodyId:'suppliersTableBody',
  dataUrl:'{{ route('stock.suppliers.data') }}',
  renderRow:(supplier)=>`<tr><td><a href="${stockSupplierRoute(STOCK_SUPPLIER_ROUTES.show, supplier.uuid ?? supplier.id)}" style="color:var(--c-accent);font-weight:600;text-decoration:none;">${supplier.name}</a></td><td>${supplier.contact_name ?? '—'}</td><td>${supplier.email ?? '—'}</td><td>${supplier.phone ?? '—'}</td><td><a class="btn-icon" href="${stockSupplierRoute(STOCK_SUPPLIER_ROUTES.edit, supplier.uuid ?? supplier.id)}"><i class="fas fa-pen"></i></a></td></tr>`
 });
});
</script>
@endpush
