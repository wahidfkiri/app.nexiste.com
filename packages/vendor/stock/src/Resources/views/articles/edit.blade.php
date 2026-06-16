@extends('layouts.global')

@section('title', __('stock::stock.pages.articles.edit.title'))

@section('breadcrumb')
  <a href="{{ route('stock.articles.index') }}">{{ __('stock::stock.common.articles') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('stock::stock.common.edit') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ __('stock::stock.pages.articles.edit.heading', ['name' => $article->name]) }}</h1><p>{{ __('stock::stock.pages.articles.edit.description') }}</p></div>
  <a href="{{ route('stock.articles.show', $article) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> {{ __('stock::stock.common.back') }}</a>
</div>
@include('stock::partials.module-nav')

<form id="articleForm" action="{{ route('stock.articles.update', $article) }}" method="POST">
@csrf
@method('PUT')
<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-box"></i> {{ __('stock::stock.pages.articles.edit.section_general') }}</h3>
      <div class="row">
        <div class="col-4"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.sku') }}</label><input name="sku" class="form-control" value="{{ $article->sku }}"></div></div>
        <div class="col-8"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.name') }} <span class="required">*</span></label><input name="name" class="form-control" value="{{ $article->name }}" required></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.supplier') }}</label><select name="supplier_id" class="form-control"><option value="">{{ __('stock::stock.common.select') }}</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" {{ $article->supplier_id == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>@endforeach</select></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.unit') }}</label><input name="unit" class="form-control" value="{{ $article->unit }}"></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-warehouse"></i> {{ __('stock::stock.pages.articles.edit.section_stock') }}</h3>
      <div class="row">
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.current_stock') }}</label><input type="text" class="form-control" value="{{ $article->current_stock }}" readonly></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.minimum_stock') }}</label><input type="number" step="any" min="0" name="min_stock" class="form-control" value="{{ $article->min_stock }}"></div></div>
      </div>
      <p style="margin:0;color:var(--c-ink-40);font-size:12px;">{{ __('stock::stock.pages.articles.edit.stock_help') }}</p>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> {{ __('stock::stock.pages.articles.edit.section_description') }}</h3>
      <div class="form-group"><textarea name="description" class="form-control" rows="4">{{ $article->description }}</textarea></div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="form-section" style="margin-bottom:16px;">
      <h3 class="form-section-title"><i class="fas fa-tags"></i> {{ __('stock::stock.pages.articles.edit.section_prices') }}</h3>
      <div class="form-group"><label class="form-label">{{ __('stock::stock.common.purchase_price') }}</label><input type="number" step="any" min="0" name="purchase_price" class="form-control" value="{{ $article->purchase_price }}"></div>
      <div class="form-group"><label class="form-label">{{ __('stock::stock.common.sale_price') }} <span class="required">*</span></label><input type="number" step="any" min="0" name="sale_price" class="form-control" value="{{ $article->sale_price }}" required></div>
      <div class="form-group"><label class="form-label">{{ __('stock::stock.common.status') }}</label><select name="status" class="form-control">@foreach($statuses as $key => $label)<option value="{{ $key }}" {{ $article->status === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
    </div>

    <div class="form-section">
      <div class="form-actions" style="padding-top:0;display:flex;flex-direction:column;gap:10px;">
        <button type="submit" class="btn btn-primary" id="submitBtn" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> {{ __('stock::stock.common.save') }}</button>
        <a href="{{ route('stock.articles.show', $article) }}" class="btn btn-secondary" style="width:100%;justify-content:center;"><i class="fas fa-times"></i> {{ __('stock::stock.common.cancel') }}</a>
      </div>
    </div>
  </div>
</div>
</form>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => Stock.bindAjaxForm('articleForm'));</script>
@endpush
