@extends('layouts.global')

@section('title', __('billing.onboarding.title'))

@section('breadcrumb')
  <span>{{ __('billing.onboarding.title') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>{{ __('billing.onboarding.title') }}</h1>
    <p>{{ __('billing.onboarding.subtitle') }}</p>
  </div>
</div>

@if($errors->any())
  <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:#fee2e2;color:#991b1b;"><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</div>
@endif

@if($current)
  <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:#eff6ff;color:#1e40af;">
    <i class="fas fa-circle-info"></i> {{ __('billing.invoice.valid_until') }} : <strong>{{ optional($current->ends_at)->format('d/m/Y') }}</strong>
  </div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;">
  @forelse($plans as $plan)
    <form method="POST" action="{{ route('subscription.subscribe') }}" class="form-section" style="display:flex;flex-direction:column;gap:12px;">
      @csrf
      <input type="hidden" name="plan" value="{{ $plan->uuid }}">

      <div>
        <h3 style="margin:0 0 4px;">{{ $plan->name }}</h3>
        <p style="margin:0;color:var(--c-ink-60);font-size:13px;">{{ $plan->description }}</p>
      </div>

      @if(is_array($plan->features) && count($plan->features))
        <ul style="margin:0;padding-left:18px;color:var(--c-ink-70);font-size:13px;line-height:1.7;">
          @foreach($plan->features as $feature)<li>{{ $feature }}</li>@endforeach
        </ul>
      @endif

      @if($plan->prices->count())
        <div>
          <div style="font-size:12px;font-weight:600;color:var(--c-ink-50);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">{{ __('billing.onboarding.choose_period') }}</div>
          @foreach($plan->prices as $idx => $price)
            <label style="display:flex;align-items:center;gap:10px;padding:9px 12px;border:1px solid var(--c-ink-05);border-radius:9px;margin-bottom:6px;cursor:pointer;">
              <input type="radio" name="plan_price_id" value="{{ $price->id }}" {{ $idx === 0 ? 'checked' : '' }}>
              <span style="flex:1;">
                <strong>{{ $price->period_months }} {{ __('billing.common.months') }}</strong>
                @if((float) $price->discount_percent > 0)
                  <span class="badge" style="background:#dcfce7;color:#15803d;margin-left:6px;">-{{ rtrim(rtrim(number_format((float)$price->discount_percent,2,'.',''),'0'),'.') }}%</span>
                @endif
              </span>
              <span style="font-weight:700;">{{ number_format((float) $price->price, 2, ',', ' ') }} {{ $plan->currency }}</span>
            </label>
          @endforeach
        </div>

        <div class="form-group" style="margin:0;">
          <label class="form-label">{{ __('billing.onboarding.payment_method') }}</label>
          <select name="payment_method" class="form-control">
            @forelse($methods as $method)
              <option value="{{ $method->provider }}" {{ $method->is_default ? 'selected' : '' }}>{{ $method->name }}</option>
            @empty
              <option value="paypal">{{ __('billing.payments.provider_paypal') }}</option>
            @endforelse
          </select>
        </div>

        <button type="submit" name="mode" value="paid" class="btn btn-primary" style="width:100%;justify-content:center;">
          <i class="fas fa-credit-card"></i> {{ __('billing.onboarding.subscribe') }}
        </button>
      @endif

      @if(($plan->is_free || $plan->trial_days > 0) && !$hasUsedTrial)
        <button type="submit" name="mode" value="trial" class="btn btn-secondary" style="width:100%;justify-content:center;">
          <i class="fas fa-gift"></i> {{ __('billing.onboarding.start_trial') }}
          @if($plan->trial_days > 0) <span style="opacity:.7;">({{ $plan->trial_days }} j)</span>@endif
        </button>
      @elseif(($plan->is_free || $plan->trial_days > 0) && $hasUsedTrial)
        <p style="margin:0;font-size:12px;color:var(--c-ink-40);text-align:center;">{{ __('billing.onboarding.trial_once_used') }}</p>
      @endif
    </form>
  @empty
    <div class="form-section" style="text-align:center;color:var(--c-ink-40);padding:40px;grid-column:1/-1;">
      <p>{{ __('billing.plans.empty') }}</p>
    </div>
  @endforelse
</div>
@endsection
