@extends('layouts.global')

@section('title', __('stock::stock.pages.orders.create.title'))

@section('breadcrumb')
  <a href="{{ route('stock.orders.index') }}">{{ __('stock::stock.common.orders') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('stock::stock.common.new') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ __('stock::stock.pages.orders.create.heading') }}</h1><p>{{ __('stock::stock.pages.orders.create.description') }}</p></div>
  <a href="{{ route('stock.orders.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> {{ __('stock::stock.common.back') }}</a>
</div>
@include('stock::partials.module-nav')

@php
  $articleOptionsHtml = '<option value="">-</option>';
  foreach ($articles as $article) {
      $articleOptionsHtml .= '<option value="' . $article->id . '" data-name="' . e($article->name) . '" data-unit="' . e($article->unit) . '" data-purchase-price="' . e($article->purchase_price) . '">' . e($article->name) . ' (' . e($article->sku) . ')</option>';
  }
@endphp

<form id="orderForm" action="{{ route('stock.orders.store') }}" method="POST">
@csrf
<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-file-lines"></i> {{ __('stock::stock.pages.orders.create.section_information') }}</h3>
      <div class="row">
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.supplier') }} <span class="required">*</span></label><select name="supplier_id" class="form-control" required><option value="">{{ __('stock::stock.common.select') }}</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.reference') }}</label><input name="reference" class="form-control" placeholder="{{ __('stock::stock.pages.orders.create.placeholder_reference') }}"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.date_order') }}</label><input type="date" name="order_date" class="form-control" value="{{ date('Y-m-d') }}"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.expected_date') }}</label><input type="date" name="expected_date" class="form-control"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.status') }}</label><select name="status" class="form-control">@foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-list"></i> {{ __('stock::stock.pages.orders.create.section_lines') }}</h3>
      <div class="order-items-wrap">
        <table>
          <thead><tr><th>{{ __('stock::stock.common.linked_article') }}</th><th>{{ __('stock::stock.common.name') }} *</th><th>{{ __('stock::stock.common.quantity') }}</th><th>{{ __('stock::stock.common.unit') }}</th><th>{{ __('stock::stock.common.purchase_price') }}</th><th></th></tr></thead>
          <tbody id="orderItemsBody">
            <tr>
              <td><select name="items[0][article_id]" class="form-control" onchange="Stock.fillOrderLineFromArticle(this)">{!! $articleOptionsHtml !!}</select></td>
              <td><input name="items[0][name]" class="form-control" required></td>
              <td><input type="number" name="items[0][quantity]" class="form-control" min="0.0001" step="any" value="1" required></td>
              <td><input name="items[0][unit]" class="form-control" value="{{ __('stock::stock.common.unit_piece') }}"></td>
              <td><input type="number" name="items[0][unit_price]" class="form-control" min="0" step="any" value="0" required></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <button type="button" class="btn btn-ghost" onclick="Stock.addOrderLine('orderItemsBody')"><i class="fas fa-plus"></i> {{ __('stock::stock.actions.add_line') }}</button>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> {{ __('stock::stock.pages.orders.create.section_notes') }}</h3>
      <div class="form-group"><textarea name="notes" class="form-control" rows="4" placeholder="{{ __('stock::stock.pages.orders.create.placeholder_notes') }}"></textarea></div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="form-section" style="margin-bottom:16px;">
      <h3 class="form-section-title"><i class="fas fa-building-columns"></i> {{ __('stock::stock.pages.orders.create.section_taxation') }}</h3>
      <div class="form-group"><label class="form-label">{{ __('stock::stock.common.vat_rate') }}</label><input type="number" step="any" min="0" max="100" name="tax_rate" class="form-control" value="0"></div>
    </div>
    <div class="form-section">
      <div class="form-actions" style="padding-top:0;display:flex;flex-direction:column;gap:10px;">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> {{ __('stock::stock.common.create_order') }}</button>
        <a href="{{ route('stock.orders.index') }}" class="btn btn-secondary" style="width:100%;justify-content:center;"><i class="fas fa-times"></i> {{ __('stock::stock.common.cancel') }}</a>
      </div>
    </div>
  </div>
</div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  window.StockLang = Object.assign(window.StockLang || {}, {
    unitPiece: @json(__('stock::stock.common.unit_piece')),
  });
  window.StockArticleOptionsHtml = @json($articleOptionsHtml);
  Stock.bindAjaxForm('orderForm');
});
</script>
@endpush
