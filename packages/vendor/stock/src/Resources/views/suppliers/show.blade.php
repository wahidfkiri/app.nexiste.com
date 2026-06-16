@extends('layouts.global')

@section('title', __('stock::stock.pages.suppliers.show.title'))

@section('breadcrumb')
  <a href="{{ route('stock.suppliers.index') }}">{{ __('stock::stock.common.suppliers') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $supplier->name }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ $supplier->name }}</h1></div>
  <div class="page-header-actions"><a href="{{ route('stock.suppliers.edit', $supplier) }}" class="btn btn-primary">{{ __('stock::stock.common.edit') }}</a></div>
</div>

@include('stock::partials.module-nav')

<div class="form-section">
  <div class="row">
    <div class="col-4"><strong>{{ __('stock::stock.common.contact') }}</strong><div>{{ $supplier->contact_name ?: __('stock::stock.common.none_short') }}</div></div>
    <div class="col-4"><strong>{{ __('stock::stock.common.email') }}</strong><div>{{ $supplier->email ?: __('stock::stock.common.none_short') }}</div></div>
    <div class="col-4"><strong>{{ __('stock::stock.common.phone') }}</strong><div>{{ $supplier->phone ?: __('stock::stock.common.none_short') }}</div></div>
    <div class="col-12" style="margin-top:10px"><strong>{{ __('stock::stock.common.address') }}</strong><div>{{ $supplier->address ?: __('stock::stock.common.none_short') }}</div></div>
  </div>
</div>
@endsection
