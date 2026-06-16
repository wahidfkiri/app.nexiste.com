@extends('layouts.global')

@section('title', __('stock::stock.pages.articles.index.title'))

@section('breadcrumb')
  <span>{{ __('stock::stock.common.stock') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('stock::stock.common.articles') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-boxes', 'bg' => '#e0f2fe', 'color' => '#0891b2', 'alt' => __('stock::stock.pages.articles.index.heading')])
      <h1 style="margin:0;">{{ __('stock::stock.pages.articles.index.heading') }}</h1>
    </div>
    <p>{{ __('stock::stock.pages.articles.index.description') }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('stock.movements.index') }}" class="btn btn-secondary"><i class="fas fa-arrows-rotate"></i> {{ __('stock::stock.common.history') }}</a>
    <a href="{{ route('stock.delivery-notes.index') }}" class="btn btn-secondary"><i class="fas fa-truck-ramp-box"></i> {{ __('stock::stock.common.delivery_notes') }}</a>
    <a href="{{ route('stock.articles.export.excel') }}" class="btn btn-secondary"><i class="fas fa-file-excel"></i> {{ __('stock::stock.common.export_excel') }}</a>
    <a href="{{ route('stock.articles.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('stock::stock.common.new_article') }}</a>
  </div>
</div>

@include('stock::partials.module-nav')

@if(!empty($marketplaceSuggestions))
  <div class="module-app-suggestions">
    @foreach($marketplaceSuggestions as $suggestion)
      <article class="module-app-suggestion-card">
        <div class="module-app-suggestion-icon">
          <i class="{{ $suggestion['icon'] ?? 'fas fa-puzzle-piece' }}"></i>
        </div>
        <div class="module-app-suggestion-body">
          <h3>{{ $suggestion['name'] ?? __('stock::stock.pages.articles.index.empty_marketplace_name') }}</h3>
          <p>{{ $suggestion['description'] ?? '' }}</p>
        </div>
        <a href="{{ $suggestion['url'] ?? route('marketplace.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-store"></i> {{ __('stock::stock.actions.install') }}</a>
      </article>
    @endforeach
  </div>
@endif

<div class="stock-grid">
  <div class="stock-card"><h4>{{ __('stock::stock.pages.articles.index.kpis.articles') }}</h4><div id="kpiArticles" class="v">-</div></div>
  <div class="stock-card"><h4>{{ __('stock::stock.pages.articles.index.kpis.low_stock') }}</h4><div id="kpiLowStock" class="v stock-kpi-low">-</div></div>
  <div class="stock-card"><h4>{{ __('stock::stock.pages.articles.index.kpis.suppliers') }}</h4><div id="kpiSuppliers" class="v">-</div></div>
  <div class="stock-card"><h4>{{ __('stock::stock.pages.articles.index.kpis.orders') }}</h4><div id="kpiOrders" class="v">-</div></div>
  <div class="stock-card"><h4>{{ __('stock::stock.pages.articles.index.kpis.delivery_notes') }}</h4><div id="kpiDeliveryNotes" class="v">-</div></div>
  <div class="stock-card"><h4>{{ __('stock::stock.pages.articles.index.kpis.movements') }}</h4><div id="kpiMovements" class="v">-</div></div>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ __('stock::stock.pages.articles.index.table_title') }}</span>
    <div class="table-spacer"></div>
    <div class="table-search"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="{{ __('stock::stock.common.search_name_sku') }}"></div>
    <select class="filter-select" data-filter="status"><option value="">{{ __('stock::stock.common.all_statuses') }}</option>@foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
  </div>
  <table class="crm-table">
    <thead>
      <tr>
        <th>{{ __('stock::stock.pages.articles.index.columns.sku') }}</th>
        <th>{{ __('stock::stock.pages.articles.index.columns.name') }}</th>
        <th>{{ __('stock::stock.pages.articles.index.columns.supplier') }}</th>
        <th>{{ __('stock::stock.pages.articles.index.columns.current_stock') }}</th>
        <th>{{ __('stock::stock.pages.articles.index.columns.min_stock') }}</th>
        <th>{{ __('stock::stock.pages.articles.index.columns.sale_price') }}</th>
        <th></th>
      </tr>
    </thead>
    <tbody id="articlesTableBody"></tbody>
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
const STOCK_ARTICLE_ROUTES = {
  show: @json(route('stock.articles.show', ['article' => '__ARTICLE__'])),
  edit: @json(route('stock.articles.edit', ['article' => '__ARTICLE__'])),
};
const stockArticleRoute = (template, id) => String(template).replace('__ARTICLE__', encodeURIComponent(String(id)));

document.addEventListener('DOMContentLoaded', () => {
  Stock.loadStats('{{ route('stock.stats') }}');
  window._stockArticlesTable = new CrmTable({
    tbodyId: 'articlesTableBody',
    dataUrl: '{{ route('stock.articles.data') }}',
    renderRow: (article) => {
      const isLow = Number(article.current_stock ?? 0) <= Number(article.min_stock ?? 0);
      return `
        <tr>
          <td>${article.sku ?? '—'}</td>
          <td><a href="${stockArticleRoute(STOCK_ARTICLE_ROUTES.show, article.id)}" style="color:var(--c-accent);font-weight:600;text-decoration:none;">${article.name}</a></td>
          <td>${article.supplier?.name ?? '—'}</td>
          <td><span style="font-weight:600;color:${isLow ? 'var(--c-danger)' : 'var(--c-ink)'};">${article.current_stock ?? 0}</span></td>
          <td>${article.min_stock ?? 0}</td>
          <td>${article.sale_price}</td>
          <td><a class="btn-icon" href="${stockArticleRoute(STOCK_ARTICLE_ROUTES.edit, article.id)}"><i class="fas fa-pen"></i></a></td>
        </tr>`;
    },
  });
});
</script>
@endpush
