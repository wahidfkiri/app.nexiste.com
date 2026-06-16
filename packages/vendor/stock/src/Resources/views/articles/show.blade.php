@extends('layouts.global')

@section('title', __('stock::stock.pages.articles.show.title'))

@section('breadcrumb')
  <a href="{{ route('stock.articles.index') }}">{{ __('stock::stock.common.articles') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $article->name }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ $article->name }}</h1><p>{{ __('stock::stock.pages.articles.show.description') }}</p></div>
  <div class="page-header-actions">
    <a href="{{ route('stock.movements.index', ['article_id' => $article->id]) }}" class="btn btn-secondary"><i class="fas fa-arrows-rotate"></i> {{ __('stock::stock.common.history') }}</a>
    <a href="{{ route('stock.articles.edit', $article) }}" class="btn btn-primary"><i class="fas fa-pen"></i> {{ __('stock::stock.common.edit') }}</a>
  </div>
</div>

@include('stock::partials.module-nav')

<div class="form-section" style="margin-bottom:18px;">
  <div class="row">
    <div class="col-3"><strong>{{ __('stock::stock.common.sku') }}</strong><div>{{ $article->sku ?? __('stock::stock.common.none_short') }}</div></div>
    <div class="col-3"><strong>{{ __('stock::stock.common.current_stock') }}</strong><div>{{ $article->current_stock }}</div></div>
    <div class="col-3"><strong>{{ __('stock::stock.common.sale_price') }}</strong><div>{{ $article->sale_price }}</div></div>
    <div class="col-3"><strong>{{ __('stock::stock.common.supplier') }}</strong><div>{{ $article->supplier?->name ?? __('stock::stock.common.none_short') }}</div></div>
    <div class="col-3" style="margin-top:10px;"><strong>{{ __('stock::stock.common.minimum_stock') }}</strong><div>{{ $article->min_stock }}</div></div>
    <div class="col-3" style="margin-top:10px;"><strong>{{ __('stock::stock.common.status') }}</strong><div>{{ $article->status_label }}</div></div>
    <div class="col-12" style="margin-top:10px"><strong>{{ __('stock::stock.common.description') }}</strong><div>{{ $article->description ?: __('stock::stock.common.none_short') }}</div></div>
  </div>
</div>

<div class="table-wrapper">
  <div class="table-header"><span class="table-title">{{ __('stock::stock.pages.articles.show.latest_movements') }}</span></div>
  <table class="crm-table">
    <thead><tr><th>{{ __('stock::stock.common.date') }}</th><th>{{ __('stock::stock.pages.articles.show.bl_or_reference') }}</th><th>{{ __('stock::stock.common.directions') }}</th><th>{{ __('stock::stock.common.quantity') }}</th><th>{{ __('stock::stock.common.reason') }}</th></tr></thead>
    <tbody>
      @forelse($article->movements as $movement)
        <tr>
          <td>{{ optional($movement->happened_at)->format('Y-m-d H:i') ?: __('stock::stock.common.none_short') }}</td>
          <td>@if($movement->deliveryNote)<a href="{{ route('stock.delivery-notes.show', $movement->deliveryNote) }}">{{ $movement->deliveryNote->number }}</a>@else{{ $movement->reference ?: __('stock::stock.common.none_short') }}@endif</td>
          <td>{{ $movement->direction_label }}</td>
          <td>{{ $movement->direction === 'out' ? '-' : '+' }}{{ $movement->quantity }}</td>
          <td>{{ $movement->display_reason ?: __('stock::stock.common.none_short') }}</td>
        </tr>
      @empty
        <tr><td colspan="5"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-arrows-rotate"></i></div><h3>{{ __('stock::stock.pages.articles.show.no_movements_title') }}</h3><p>{{ __('stock::stock.pages.articles.show.no_movements_description') }}</p></div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
