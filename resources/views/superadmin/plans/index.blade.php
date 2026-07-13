@extends('layouts.global')

@section('title', __('billing.plans.title'))

@section('breadcrumb')
  <span>{{ __('billing.plans.title') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>{{ __('billing.plans.title') }}</h1>
    <p>{{ __('billing.plans.subtitle') }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('superadmin.plans.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('billing.plans.add') }}</a>
  </div>
</div>

@if(session('success'))
  <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:#dcfce7;color:#166534;">
    <i class="fas fa-circle-check"></i> {{ session('success') }}
  </div>
@endif

@forelse($plans as $plan)
  <div class="form-section" style="margin-bottom:16px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h3 style="margin:0 0 4px;display:flex;align-items:center;gap:10px;">
          {{ $plan->name }}
          @if($plan->is_free)<span class="badge" style="background:#ecfeff;color:#0e7490;">{{ __('billing.common.free') }}</span>@endif
          @if($plan->is_active)
            <span class="badge" style="background:#dcfce7;color:#15803d;">{{ __('billing.common.active') }}</span>
          @else
            <span class="badge" style="background:#f1f5f9;color:#64748b;">{{ __('billing.common.inactive') }}</span>
          @endif
        </h3>
        <p style="margin:0;color:var(--c-ink-60);font-size:13.5px;">{{ $plan->description }}</p>
        <p style="margin:6px 0 0;color:var(--c-ink-40);font-size:12.5px;">
          {{ __('billing.plans.monthly_price') }} : <strong>{{ number_format((float) $plan->monthly_price, 2, ',', ' ') }} {{ $plan->currency }}</strong>
          @if($plan->trial_days > 0) · {{ __('billing.common.trial') }} : {{ $plan->trial_days }} {{ __('billing.common.months') === 'mois' ? 'j' : 'j' }}@endif
        </p>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('superadmin.plans.edit', $plan) }}" class="btn btn-secondary btn-sm"><i class="fas fa-pen"></i> {{ __('billing.common.edit') }}</a>
        <form method="POST" action="{{ route('superadmin.plans.toggle', $plan) }}">@csrf
          <button class="btn btn-ghost btn-sm">{{ $plan->is_active ? __('billing.common.deactivate') : __('billing.common.activate') }}</button>
        </form>
        <form method="POST" action="{{ route('superadmin.plans.destroy', $plan) }}" onsubmit="return confirm('{{ __('billing.common.delete') }} ?');">@csrf @method('DELETE')
          <button class="btn-icon danger btn-sm"><i class="fas fa-trash"></i></button>
        </form>
      </div>
    </div>

    @if($plan->prices->count())
      <table class="crm-table" style="margin-top:12px;">
        <thead><tr>
          <th>{{ __('billing.common.period') }}</th>
          <th class="text-right">{{ __('billing.common.discount') }}</th>
          <th class="text-right">{{ __('billing.plans.final_price') }}</th>
          <th class="text-right">{{ __('billing.common.per_month') }}</th>
        </tr></thead>
        <tbody>
          @foreach($plan->prices as $price)
            <tr>
              <td>{{ $price->period_months }} {{ __('billing.common.months') }}</td>
              <td class="text-right">{{ rtrim(rtrim(number_format((float) $price->discount_percent, 2, '.', ''), '0'), '.') }} %</td>
              <td class="text-right"><strong>{{ number_format((float) $price->price, 2, ',', ' ') }} {{ $plan->currency }}</strong></td>
              <td class="text-right" style="color:var(--c-ink-40);">{{ number_format($price->monthly_equivalent, 2, ',', ' ') }} {{ $plan->currency }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
@empty
  <div class="form-section" style="text-align:center;color:var(--c-ink-40);padding:40px;">
    <i class="fas fa-layer-group" style="font-size:28px;margin-bottom:10px;"></i>
    <p>{{ __('billing.plans.empty') }}</p>
  </div>
@endforelse
@endsection
