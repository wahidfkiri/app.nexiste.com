@extends('layouts.global')

@section('title', __('stock::stock.pages.orders.edit.title'))

@section('breadcrumb')
  <a href="{{ route('stock.orders.index') }}">{{ __('stock::stock.common.orders') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $order->number }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ __('stock::stock.pages.orders.edit.heading', ['number' => $order->number]) }}</h1><p>{{ __('stock::stock.pages.orders.edit.description') }}</p></div>
  <a href="{{ route('stock.orders.show', $order) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> {{ __('stock::stock.common.back') }}</a>
</div>
@include('stock::partials.module-nav')

<form id="orderForm" action="{{ route('stock.orders.update', $order) }}" method="POST">
@csrf
@method('PUT')
<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-file-lines"></i> {{ __('stock::stock.pages.orders.edit.section_information') }}</h3>
      <div class="row">
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.supplier') }} <span class="required">*</span></label><select name="supplier_id" class="form-control" required><option value="">{{ __('stock::stock.common.select') }}</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" {{ $order->supplier_id == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>@endforeach</select></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.reference') }}</label><input name="reference" class="form-control" value="{{ $order->reference }}"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.date_order') }}</label><input type="date" name="order_date" class="form-control" value="{{ optional($order->order_date)->format('Y-m-d') }}"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.expected_date') }}</label><input type="date" name="expected_date" class="form-control" value="{{ optional($order->expected_date)->format('Y-m-d') }}"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.status') }}</label><select name="status" class="form-control">@foreach($statuses as $key=>$label)<option value="{{ $key }}" {{ $order->status === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-list"></i> {{ __('stock::stock.pages.orders.edit.section_lines') }}</h3>
      <div class="order-items-wrap">
        <table>
          <thead><tr><th>{{ __('stock::stock.common.linked_article') }}</th><th>{{ __('stock::stock.common.name') }} *</th><th>{{ __('stock::stock.common.quantity') }}</th><th>{{ __('stock::stock.common.unit') }}</th><th>{{ __('stock::stock.common.purchase_price') }}</th><th></th></tr></thead>
          <tbody id="orderItemsBody">
            @foreach($order->items as $index => $item)
            <tr>
              <td><select name="items[{{ $index }}][article_id]" class="form-control" onchange="Stock.fillOrderLineFromArticle(this)"><option value="">-</option>@foreach($articles as $article)<option value="{{ $article->id }}" data-name="{{ $article->name }}" data-unit="{{ $article->unit }}" data-purchase-price="{{ $article->purchase_price }}" {{ $item->article_id == $article->id ? 'selected' : '' }}>{{ $article->name }} ({{ $article->sku }})</option>@endforeach</select></td>
              <td><input name="items[{{ $index }}][name]" class="form-control" value="{{ $item->name }}" required></td>
              <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control" min="0.0001" step="any" value="{{ $item->quantity }}" required></td>
              <td><input name="items[{{ $index }}][unit]" class="form-control" value="{{ $item->unit }}"></td>
              <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control" min="0" step="any" value="{{ $item->unit_price }}" required></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <button type="button" class="btn btn-ghost" onclick="Stock.addOrderLine('orderItemsBody')"><i class="fas fa-plus"></i> {{ __('stock::stock.actions.add_line') }}</button>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> {{ __('stock::stock.pages.orders.edit.section_notes') }}</h3>
      <div class="form-group"><textarea name="notes" class="form-control" rows="4">{{ $order->notes }}</textarea></div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="form-section" style="margin-bottom:16px;">
      <h3 class="form-section-title"><i class="fas fa-building-columns"></i> {{ __('stock::stock.pages.orders.edit.section_taxation') }}</h3>
      <div class="form-group"><label class="form-label">{{ __('stock::stock.common.vat_rate') }}</label><input type="number" step="any" min="0" max="100" name="tax_rate" class="form-control" value="{{ $order->tax_rate }}"></div>
    </div>
    <div class="form-section">
      <div class="form-actions" style="padding-top:0;display:flex;flex-direction:column;gap:10px;">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> {{ __('stock::stock.common.save') }}</button>
        <a href="{{ route('stock.orders.show', $order) }}" class="btn btn-secondary" style="width:100%;justify-content:center;"><i class="fas fa-times"></i> {{ __('stock::stock.common.cancel') }}</a>
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
  window.StockArticleOptionsHtml = @json('<option value="">-</option>' . collect($articles)->map(fn($article) => '<option value="' . $article->id . '" data-name="' . e($article->name) . '" data-unit="' . e($article->unit) . '" data-purchase-price="' . e($article->purchase_price) . '">' . e($article->name) . ' (' . e($article->sku) . ')</option>')->implode(''));
  Stock.bindAjaxForm('orderForm');
});
</script>
@endpush
