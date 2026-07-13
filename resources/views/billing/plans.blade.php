@extends('layouts.global')

@section('title', __('billing.onboarding.title'))

@section('breadcrumb')
  <span>{{ __('billing.onboarding.title') }}</span>
@endsection

@push('styles')
<style>
  .plan-wrap{max-width:1100px;margin:0 auto;}
  .plan-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:22px;align-items:stretch;}
  .plan-card{position:relative;display:flex;flex-direction:column;background:var(--surface-0,#fff);border:1px solid var(--c-ink-05,#eef2f7);border-radius:18px;padding:26px 24px;transition:transform .18s ease,box-shadow .2s ease,border-color .2s ease;box-shadow:0 4px 18px rgba(15,23,42,.04);}
  .plan-card:hover{transform:translateY(-4px);box-shadow:0 20px 44px rgba(15,23,42,.10);}
  .plan-card.is-featured{border-color:var(--c-accent,#2563eb);box-shadow:0 22px 50px rgba(37,99,235,.16);}
  .plan-badge{position:absolute;top:-11px;left:50%;transform:translateX(-50%);background:var(--c-accent,#2563eb);color:#fff;font-size:11px;font-weight:700;letter-spacing:.03em;padding:5px 14px;border-radius:999px;white-space:nowrap;box-shadow:0 8px 18px rgba(37,99,235,.3);}
  .plan-name{font-size:19px;font-weight:700;margin:0 0 4px;color:var(--c-ink,#0f172a);}
  .plan-desc{font-size:13px;color:var(--c-ink-50,#64748b);margin:0 0 18px;line-height:1.5;min-height:38px;}
  .plan-price{display:flex;align-items:baseline;gap:6px;margin-bottom:4px;}
  .plan-price .amount{font-size:34px;font-weight:800;letter-spacing:-1px;color:var(--c-ink,#0f172a);}
  .plan-price .cur{font-size:16px;font-weight:700;color:var(--c-ink-60,#475569);}
  .plan-price .suffix{font-size:13px;color:var(--c-ink-40,#94a3b8);}
  .plan-price-note{font-size:12px;color:var(--c-ink-40,#94a3b8);margin-bottom:18px;min-height:16px;}
  .plan-features{list-style:none;margin:0 0 20px;padding:0;display:flex;flex-direction:column;gap:9px;flex:1;}
  .plan-features li{display:flex;align-items:flex-start;gap:9px;font-size:13.5px;color:var(--c-ink-70,#334155);line-height:1.45;}
  .plan-features li i{color:#16a34a;margin-top:2px;font-size:13px;}
  .plan-periods{display:flex;flex-direction:column;gap:8px;margin-bottom:18px;}
  .plan-period{display:flex;align-items:center;gap:10px;padding:10px 13px;border:1.5px solid var(--c-ink-08,#e2e8f0);border-radius:11px;cursor:pointer;transition:border-color .15s ease,background .15s ease;}
  .plan-period:hover{border-color:var(--c-accent-lt,#bfdbfe);}
  .plan-period input{accent-color:var(--c-accent,#2563eb);}
  .plan-period.selected{border-color:var(--c-accent,#2563eb);background:var(--c-accent-xl,#eff6ff);}
  .plan-period .p-label{flex:1;font-weight:600;font-size:13.5px;}
  .plan-period .p-price{font-weight:700;font-size:14px;}
  .plan-period .p-badge{background:#dcfce7;color:#15803d;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:999px;}
  .plan-cta{width:100%;justify-content:center;padding:12px;font-size:14.5px;}
  .plan-note{font-size:12px;color:var(--c-ink-40,#94a3b8);text-align:center;margin-top:10px;}
</style>
@endpush

@section('content')
<div class="plan-wrap">
  <div class="page-header" style="text-align:center;justify-content:center;flex-direction:column;">
    <div class="page-header-left" style="text-align:center;">
      <h1>{{ __('billing.onboarding.title') }}</h1>
      <p>{{ __('billing.onboarding.subtitle') }}</p>
    </div>
  </div>

  @if($errors->any())
    <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:#fee2e2;color:#991b1b;"><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</div>
  @endif

  @if($current)
    <div style="margin-bottom:18px;padding:12px 14px;border-radius:10px;background:#eff6ff;color:#1e40af;text-align:center;">
      <i class="fas fa-circle-info"></i> {{ __('billing.success.valid_until', ['date' => optional($current->ends_at)->format('d/m/Y')]) }}
    </div>
  @endif

  <div class="plan-grid">
    @forelse($plans as $plan)
      @php
        $isFree = (bool) $plan->is_free || (float) $plan->monthly_price <= 0;
        $featured = $loop->index === 1 && $plans->count() > 1;
        $firstPrice = $plan->prices->first();
      @endphp
      <div class="plan-card {{ $featured ? 'is-featured' : '' }}">
        @if($featured)<span class="plan-badge">{{ __('billing.onboarding.most_popular') }}</span>@endif

        <div class="plan-name">{{ $plan->name }}</div>
        <p class="plan-desc">{{ $plan->description }}</p>

        @if($isFree)
          <div class="plan-price"><span class="amount">{{ __('billing.common.free') }}</span></div>
          <div class="plan-price-note">{{ $plan->trial_days > 0 ? __('billing.onboarding.trial_badge', ['days' => $plan->trial_days]) : __('billing.onboarding.free_forever') }}</div>
        @elseif($firstPrice)
          <div class="plan-price">
            <span class="cur">{{ $plan->currency }}</span>
            <span class="amount">{{ number_format((float) $plan->monthly_price, 0, ',', ' ') }}</span>
            <span class="suffix">{{ __('billing.common.per_month') }}</span>
          </div>
          <div class="plan-price-note">{{ __('billing.onboarding.from') }} {{ number_format((float) $firstPrice->price, 2, ',', ' ') }} {{ $plan->currency }}</div>
        @endif

        @if(is_array($plan->features) && count($plan->features))
          <ul class="plan-features">
            @foreach($plan->features as $feature)
              <li><i class="fas fa-check"></i><span>{{ $feature }}</span></li>
            @endforeach
          </ul>
        @else
          <div style="flex:1;"></div>
        @endif

        @if($isFree)
          {{-- Forfait gratuit / démo : activation directe --}}
          @if(!$hasUsedTrial)
            <form method="POST" action="{{ route('subscription.free') }}">
              @csrf
              <input type="hidden" name="plan" value="{{ $plan->uuid }}">
              <button type="submit" class="btn btn-primary plan-cta"><i class="fas fa-bolt"></i> {{ __('billing.onboarding.activate_free') }}</button>
            </form>
          @else
            <button class="btn btn-secondary plan-cta" disabled>{{ __('billing.onboarding.trial_once_used') }}</button>
          @endif
        @elseif($plan->prices->count())
          {{-- Forfait payant : choix de la période -> page de paiement --}}
          <form method="GET" action="{{ route('subscription.checkout') }}">
            <input type="hidden" name="plan" value="{{ $plan->uuid }}">
            <div class="plan-periods">
              @foreach($plan->prices as $idx => $price)
                <label class="plan-period {{ $idx === 0 ? 'selected' : '' }}">
                  <input type="radio" name="plan_price_id" value="{{ $price->id }}" {{ $idx === 0 ? 'checked' : '' }} onchange="this.closest('.plan-periods').querySelectorAll('.plan-period').forEach(p=>p.classList.remove('selected'));this.closest('.plan-period').classList.add('selected');">
                  <span class="p-label">{{ $price->period_months }} {{ __('billing.common.months') }}</span>
                  @if((float) $price->discount_percent > 0)<span class="p-badge">-{{ rtrim(rtrim(number_format((float)$price->discount_percent,2,'.',''),'0'),'.') }}%</span>@endif
                  <span class="p-price">{{ number_format((float) $price->price, 2, ',', ' ') }} {{ $plan->currency }}</span>
                </label>
              @endforeach
            </div>
            <button type="submit" class="btn btn-primary plan-cta"><i class="fas fa-arrow-right"></i> {{ __('billing.onboarding.choose_plan') }}</button>
          </form>
        @endif
      </div>
    @empty
      <div class="form-section" style="text-align:center;color:var(--c-ink-40);padding:40px;grid-column:1/-1;">{{ __('billing.plans.empty') }}</div>
    @endforelse
  </div>
</div>
@endsection
