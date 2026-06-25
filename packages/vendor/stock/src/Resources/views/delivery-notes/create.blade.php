@extends('layouts.global')

@section('title', __('stock::stock.pages.delivery_notes.create.title'))

@section('breadcrumb')
  <a href="{{ route('stock.delivery-notes.index') }}">{{ __('stock::stock.common.delivery_notes') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('stock::stock.common.new') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ __('stock::stock.pages.delivery_notes.create.heading') }}</h1><p>{{ __('stock::stock.pages.delivery_notes.create.description') }}</p></div>
  <a href="{{ route('stock.delivery-notes.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> {{ __('stock::stock.common.back') }}</a>
</div>
@include('stock::partials.module-nav')

@php
  $articleOptionsHtml = '<option value="">-</option>';
  foreach ($articles as $article) {
      $articleOptionsHtml .= '<option value="' . $article->id . '" data-name="' . e($article->name) . '" data-sku="' . e($article->sku) . '" data-unit="' . e($article->unit) . '">' . e($article->name) . ' (' . e($article->sku ?: __('stock::stock.common.article_without_sku')) . ') - ' . __('stock::stock.common.stock_word') . ' ' . number_format((float) $article->current_stock, 4, '.', '') . '</option>';
  }
@endphp

<form id="deliveryNoteForm" action="{{ route('stock.delivery-notes.store') }}" method="POST">
@csrf
<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-file-lines"></i> {{ __('stock::stock.pages.delivery_notes.create.section_information') }}</h3>
      <div class="row">
        <div class="col-4"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.type') }} <span class="required">*</span></label><select name="type" id="deliveryTypeInput" class="form-control" required><option value="in">{{ __('stock::stock.common.entry_bl') }}</option><option value="out">{{ __('stock::stock.common.output_bl') }}</option></select></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.date') }}</label><input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}"></div></div>
        <div class="col-4"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.reference') }}</label><input type="text" name="reference" class="form-control" placeholder="{{ __('stock::stock.pages.delivery_notes.create.placeholder_reference') }}"></div></div>
        <div class="col-6" id="deliverySupplierWrap"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.supplier') }} <span class="required">*</span></label><select name="supplier_id" class="form-control"><option value="">{{ __('stock::stock.common.select') }}</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div></div>
        <div class="col-6" id="deliveryClientWrap" style="display:none;"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.client') }} <span class="required">*</span></label><select name="client_id" class="form-control" disabled><option value="">{{ __('stock::stock.common.select') }}</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->company_name }}</option>@endforeach</select></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.linked_supplier_order') }}</label><select name="stock_order_id" class="form-control"><option value="">{{ __('stock::stock.common.none') }}</option>@foreach($orders as $order)<option value="{{ $order->id }}">{{ $order->number }} - {{ $order->supplier?->name ?? '—' }} ({{ $order->status_label }})</option>@endforeach</select></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-list"></i> {{ __('stock::stock.pages.delivery_notes.create.section_lines') }}</h3>
      <div class="order-items-wrap">
        <table>
          <thead><tr><th>{{ __('stock::stock.common.linked_article') }}</th><th>{{ __('stock::stock.common.sku') }}</th><th>{{ __('stock::stock.common.name') }} *</th><th>{{ __('stock::stock.common.quantity') }}</th><th>{{ __('stock::stock.common.unit') }}</th><th></th></tr></thead>
          <tbody id="deliveryNoteItemsBody">
            <tr>
              <td><select name="items[0][article_id]" class="form-control" onchange="Stock.fillDeliveryLineFromArticle(this)">{!! $articleOptionsHtml !!}</select></td>
              <td><input type="text" name="items[0][sku]" class="form-control" placeholder="SKU"></td>
              <td><input type="text" name="items[0][name]" class="form-control" required></td>
              <td><input type="number" name="items[0][quantity]" class="form-control" min="0.0001" step="any" value="1" required></td>
              <td><input type="text" name="items[0][unit]" class="form-control" value="{{ __('stock::stock.common.unit_piece') }}"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <button type="button" class="btn btn-ghost" onclick="Stock.addDeliveryLine('deliveryNoteItemsBody')"><i class="fas fa-plus"></i> {{ __('stock::stock.actions.add_line') }}</button>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> {{ __('stock::stock.pages.delivery_notes.create.section_notes') }}</h3>
      <div class="form-group"><textarea name="notes" class="form-control" rows="4" placeholder="{{ __('stock::stock.pages.delivery_notes.create.placeholder_notes') }}"></textarea></div>
    </div>
  </div>
  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="form-section">
      <div class="form-actions" style="padding-top:0;display:flex;flex-direction:column;gap:10px;">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> {{ __('stock::stock.common.create_delivery_note') }}</button>
        <a href="{{ route('stock.delivery-notes.index') }}" class="btn btn-secondary" style="width:100%;justify-content:center;"><i class="fas fa-times"></i> {{ __('stock::stock.common.cancel') }}</a>
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
    skuPlaceholder: @json(__('stock::stock.common.sku_placeholder')),
  });
  window.StockArticleOptionsHtml = @json($articleOptionsHtml);
  Stock.bindAjaxForm('deliveryNoteForm');
  Stock.toggleDeliveryType(document.getElementById('deliveryTypeInput')?.value || 'in');
  document.getElementById('deliveryTypeInput')?.addEventListener('change', (event) => Stock.toggleDeliveryType(event.target.value));

  window.CrmDrafts?.attach('deliveryNoteForm', {
    type: 'stock_delivery_note',
    label: 'bon livraison',
    collect: (data) => {
      const items = [];
      const tbody = document.getElementById('deliveryNoteItemsBody');
      if (tbody) {
        Array.from(tbody.querySelectorAll('tr')).forEach((tr) => {
          const get = (sel) => {
            const el = tr.querySelector(sel);
            return el ? el.value : '';
          };
          items.push({
            article_id: get('[name$="[article_id]"]') || '',
            sku: get('[name$="[sku]"]') || '',
            name: get('[name$="[name]"]') || '',
            quantity: get('[name$="[quantity]"]') || '',
            unit: get('[name$="[unit]"]') || '',
          });
        });
      }
      data.__draft_items = items;
      return data;
    },
    apply: (data) => {
      if (Array.isArray(data.__draft_items)) {
        const tbody = document.getElementById('deliveryNoteItemsBody');
        if (tbody) {
          tbody.innerHTML = '';
          data.__draft_items.forEach((item) => {
            Stock.addDeliveryLine('deliveryNoteItemsBody');
            const rows = tbody.querySelectorAll('tr');
            const last = rows[rows.length - 1];
            if (!last) return;
            const set = (sel, value) => {
              const el = last.querySelector(sel);
              if (!el) return;
              el.value = value ?? '';
              el.dispatchEvent(new Event('input', { bubbles: true }));
              el.dispatchEvent(new Event('change', { bubbles: true }));
            };
            set('[name$="[article_id]"]', item.article_id || '');
            set('[name$="[sku]"]', item.sku || '');
            set('[name$="[name]"]', item.name || '');
            set('[name$="[quantity]"]', item.quantity ?? 1);
            set('[name$="[unit]"]', item.unit || '');
          });
        }
      }
    },
  });
});
</script>
@endpush
