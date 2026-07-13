@extends('layouts.billing')

@section('title', __('billing.checkout.title'))

@section('content')
<div style="max-width:560px;margin:0 auto;">
  <div style="text-align:center;margin-bottom:22px;">
    <h1 style="font-size:24px;font-weight:800;margin:0 0 6px;">{{ __('billing.checkout.title') }}</h1>
    <p style="margin:0;color:var(--c-ink-50,#64748b);font-size:14px;">{{ __('billing.checkout.subtitle') }}</p>
  </div>

  @if($errors->any())
    <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:#fee2e2;color:#991b1b;"><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</div>
  @endif

  {{-- Récapitulatif --}}
  <div class="form-section" style="margin-bottom:16px;">
    <h3 class="form-section-title"><i class="fas fa-receipt"></i> {{ __('billing.checkout.summary') }}</h3>
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--c-ink-05);">
      <span style="color:var(--c-ink-60);">{{ __('billing.checkout.plan') }}</span><strong>{{ $plan->name }}</strong>
    </div>
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--c-ink-05);">
      <span style="color:var(--c-ink-60);">{{ __('billing.checkout.period') }}</span><strong>{{ $price->period_months }} {{ __('billing.common.months') }}</strong>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0 4px;">
      <span style="font-weight:600;">{{ __('billing.checkout.total') }}</span>
      <span style="font-size:22px;font-weight:800;color:var(--c-accent);">{{ number_format((float) $price->price, 2, ',', ' ') }} {{ $plan->currency }}</span>
    </div>
  </div>

  {{-- Moyen de paiement --}}
  <form method="POST" action="{{ route('subscription.pay') }}" class="form-section">
    @csrf
    <input type="hidden" name="plan" value="{{ $plan->uuid }}">
    <input type="hidden" name="plan_price_id" value="{{ $price->id }}">

    <h3 class="form-section-title"><i class="fas fa-credit-card"></i> {{ __('billing.checkout.choose_method') }}</h3>

    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
      @forelse($methods as $idx => $method)
        <label style="display:flex;align-items:center;gap:12px;padding:13px 15px;border:1.5px solid {{ $idx === 0 ? 'var(--c-accent)' : 'var(--c-ink-08)' }};border-radius:12px;cursor:pointer;">
          <input type="radio" name="payment_method" value="{{ $method->provider }}" {{ $idx === 0 ? 'checked' : '' }} style="accent-color:var(--c-accent);">
          @if($method->provider === 'paypal')<i class="fab fa-paypal" style="font-size:20px;color:#003087;"></i>@else<i class="fas fa-building-columns" style="font-size:18px;color:var(--c-ink-50);"></i>@endif
          <span style="flex:1;font-weight:600;">{{ $method->name }}</span>
        </label>
      @empty
        <label style="display:flex;align-items:center;gap:12px;padding:13px 15px;border:1.5px solid var(--c-accent);border-radius:12px;">
          <input type="radio" name="payment_method" value="paypal" checked style="accent-color:var(--c-accent);">
          <i class="fab fa-paypal" style="font-size:20px;color:#003087;"></i>
          <span style="flex:1;font-weight:600;">PayPal</span>
        </label>
      @endforelse
    </div>

    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:11px 14px;margin-bottom:16px;font-size:12.5px;color:#166534;">
      <i class="fas fa-lock"></i> {{ __('billing.checkout.secure_notice') }}
    </div>

    <div style="display:flex;gap:10px;">
      <a href="{{ route('subscription.plans') }}" class="btn btn-secondary">{{ __('billing.checkout.back') }}</a>
      <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">
        <i class="fas fa-lock"></i> {{ __('billing.checkout.pay_now') }} · {{ number_format((float) $price->price, 2, ',', ' ') }} {{ $plan->currency }}
      </button>
    </div>
  </form>
</div>
@endsection
