@extends('layouts.global')

@section('title', __('stock::stock.pages.suppliers.edit.title'))

@section('breadcrumb')
  <a href="{{ route('stock.suppliers.index') }}">{{ __('stock::stock.common.suppliers') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('stock::stock.common.edit') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ __('stock::stock.pages.suppliers.edit.heading', ['name' => $supplier->name]) }}</h1><p>{{ __('stock::stock.pages.suppliers.edit.description') }}</p></div>
  <a href="{{ route('stock.suppliers.show', $supplier) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> {{ __('stock::stock.common.back') }}</a>
</div>
@include('stock::partials.module-nav')

<form id="supplierForm" action="{{ route('stock.suppliers.update', $supplier) }}" method="POST">
@csrf
@method('PUT')
<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-truck-field"></i> {{ __('stock::stock.pages.suppliers.edit.section_general') }} <span class="form-section-badge">{{ __('stock::stock.common.step', ['current' => 1, 'total' => 2]) }}</span></h3>
      <div class="row">
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.name') }} <span class="required">*</span></label><input name="name" class="form-control" value="{{ $supplier->name }}" required></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.contact_name') }}</label><input name="contact_name" class="form-control" value="{{ $supplier->contact_name }}"></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.email') }}</label><input type="email" name="email" class="form-control" value="{{ $supplier->email }}"></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.phone') }}</label><input name="phone" class="form-control" value="{{ $supplier->phone }}"></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-location-dot"></i> {{ __('stock::stock.pages.suppliers.edit.section_address') }}</h3>
      <div class="row">
        <div class="col-12"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.address') }}</label><textarea name="address" class="form-control" rows="2">{{ $supplier->address }}</textarea></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.city') }}</label><input name="city" class="form-control" value="{{ $supplier->city }}"></div></div>
        <div class="col-6"><div class="form-group"><label class="form-label">{{ __('stock::stock.common.country') }}</label><input name="country" class="form-control" value="{{ $supplier->country }}"></div></div>
      </div>
    </div>

    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> {{ __('stock::stock.pages.suppliers.edit.section_notes') }}</h3>
      <div class="form-group"><textarea name="notes" class="form-control" rows="4">{{ $supplier->notes }}</textarea></div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="form-section">
      <h3 class="form-section-title"><i class="fas fa-check-circle"></i> {{ __('stock::stock.pages.suppliers.edit.section_actions') }} <span class="form-section-badge">{{ __('stock::stock.common.step', ['current' => 2, 'total' => 2]) }}</span></h3>
      <div class="form-actions" style="padding-top:0;display:flex;flex-direction:column;gap:10px;">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> {{ __('stock::stock.common.save') }}</button>
        <a href="{{ route('stock.suppliers.show', $supplier) }}" class="btn btn-secondary" style="width:100%;justify-content:center;"><i class="fas fa-times"></i> {{ __('stock::stock.common.cancel') }}</a>
      </div>
    </div>
  </div>
</div>
</form>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => Stock.bindAjaxForm('supplierForm'));</script>
@endpush
