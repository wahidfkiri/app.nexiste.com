@extends('layouts.billing')

@section('title', __('billing.onboarding.title'))

@push('styles')
<style>
  .plan-wrap{max-width:1100px;margin:0 auto;}
  .plan-head{text-align:center;margin-bottom:26px;}
  .plan-head h1{font-size:26px;font-weight:800;margin:0 0 6px;}
  .plan-head p{margin:0;color:var(--c-ink-50,#64748b);font-size:14.5px;}
  .plan-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:22px;align-items:stretch;}
  .plan-card{position:relative;display:flex;flex-direction:column;background:#fff;border:1px solid #eef2f7;border-radius:18px;padding:26px 24px;transition:transform .18s ease,box-shadow .2s ease,border-color .2s ease;box-shadow:0 4px 18px rgba(15,23,42,.04);}
  .plan-card:hover{transform:translateY(-4px);box-shadow:0 20px 44px rgba(15,23,42,.10);}
  .plan-card.is-featured{border-color:var(--c-accent,#2563eb);box-shadow:0 22px 50px rgba(37,99,235,.16);}
  .plan-badge{position:absolute;top:-11px;left:50%;transform:translateX(-50%);background:var(--c-accent,#2563eb);color:#fff;font-size:11px;font-weight:700;padding:5px 14px;border-radius:999px;white-space:nowrap;box-shadow:0 8px 18px rgba(37,99,235,.3);}
  .plan-name{font-size:19px;font-weight:700;margin:0 0 4px;}
  .plan-desc{font-size:13px;color:var(--c-ink-50,#64748b);margin:0 0 18px;line-height:1.5;min-height:38px;}
  .plan-price{display:flex;align-items:baseline;gap:6px;margin-bottom:14px;}
  .plan-price .amount{font-size:34px;font-weight:800;letter-spacing:-1px;}
  .plan-price .cur{font-size:16px;font-weight:700;color:var(--c-ink-60,#475569);}
  .plan-price .suffix{font-size:13px;color:var(--c-ink-40,#94a3b8);}
  .plan-features{list-style:none;margin:0 0 18px;padding:0;display:flex;flex-direction:column;gap:9px;flex:1;}
  .plan-features li{display:flex;align-items:flex-start;gap:9px;font-size:13.5px;color:var(--c-ink-70,#334155);line-height:1.45;}
  .plan-features li i{color:#16a34a;margin-top:2px;font-size:13px;}
  .plan-total{font-size:13px;color:var(--c-ink-60,#475569);margin:2px 0 14px;line-height:1.5;}
  .plan-total .gross{color:var(--c-ink-40,#94a3b8);text-decoration:line-through;margin-right:6px;}
  .plan-total .final{font-weight:800;color:var(--c-ink,#0f172a);font-size:15px;}
  .plan-total .off{background:#dcfce7;color:#15803d;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:999px;margin-left:6px;}
  .plan-cta{width:100%;justify-content:center;padding:12px;font-size:14.5px;}
</style>
@endpush

@section('content')
<div class="plan-wrap">
  <div class="plan-head">
    <h1>{{ __('billing.onboarding.title') }}</h1>
    <p>{{ __('billing.onboarding.subtitle') }}</p>
  </div>

  @if($errors->any())
    <div style="max-width:640px;margin:0 auto 18px;padding:12px 14px;border-radius:10px;background:#fee2e2;color:#991b1b;"><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</div>
  @endif

  @if($current)
    <div style="max-width:640px;margin:0 auto 18px;padding:12px 14px;border-radius:10px;background:#eff6ff;color:#1e40af;text-align:center;">
      <i class="fas fa-circle-info"></i> {{ __('billing.success.valid_until', ['date' => optional($current->ends_at)->format('d/m/Y')]) }}
    </div>
  @endif

  <div class="plan-grid">
    @forelse($plans as $plan)
      @php
        $isFree = (bool) $plan->is_free || (float) $plan->monthly_price <= 0;
        $featured = $loop->index === 1 && $plans->count() > 1;
      @endphp
      <div class="plan-card {{ $featured ? 'is-featured' : '' }}">
        @if($featured)<span class="plan-badge">{{ __('billing.onboarding.most_popular') }}</span>@endif

        <div class="plan-name">{{ $plan->name }}</div>
        <p class="plan-desc">{{ $plan->description }}</p>

        @if($isFree)
          <div class="plan-price"><span class="amount">{{ __('billing.common.free') }}</span></div>
        @else
          {{-- Prix mensuel : "15 TND / mois" --}}
          <div class="plan-price">
            <span class="amount">{{ rtrim(rtrim(number_format((float) $plan->monthly_price, 2, ',', ' '), '0'), ',') }}</span>
            <span class="cur">{{ $plan->currency }}</span>
            <span class="suffix">{{ __('billing.common.per_month') }}</span>
          </div>
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
          <form method="GET" action="{{ route('subscription.checkout') }}">
            <input type="hidden" name="plan" value="{{ $plan->uuid }}">
            <div class="form-group" style="margin-bottom:10px;">
              <select name="plan_price_id" class="form-control js-period" data-monthly="{{ (float) $plan->monthly_price }}" data-currency="{{ $plan->currency }}">
                @foreach($plan->prices as $price)
                  <option value="{{ $price->id }}" data-months="{{ $price->period_months }}" data-final="{{ (float) $price->price }}">
                    {{ $price->period_months }} {{ __('billing.common.months') }}
                  </option>
                @endforeach
              </select>
            </div>
            {{-- Prix total (mensuel × durée) et prix après remise --}}
            <div class="plan-total js-total"></div>
            <button type="submit" class="btn btn-primary plan-cta"><i class="fas fa-arrow-right"></i> {{ __('billing.onboarding.choose_plan') }}</button>
          </form>
        @endif
      </div>
    @empty
      <div style="text-align:center;color:var(--c-ink-40);padding:40px;grid-column:1/-1;">{{ __('billing.plans.empty') }}</div>
    @endforelse
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var totalTpl = @json(__('billing.checkout.total'));
  document.querySelectorAll('form select.js-period').forEach(function (sel) {
    var out = sel.closest('form').querySelector('.js-total');
    var monthly = parseFloat(sel.dataset.monthly) || 0;
    var cur = sel.dataset.currency || '';

    function fmt(n) { return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function render() {
      var opt = sel.options[sel.selectedIndex];
      var months = parseInt(opt.dataset.months) || 1;
      var final = parseFloat(opt.dataset.final) || 0;
      var gross = monthly * months;                 // prix total = mensuel × durée
      var off = gross > 0 ? Math.round((1 - final / gross) * 100) : 0;
      var html = totalTpl + ' (' + months + ' ' + @json(__('billing.common.months')) + ') : ';
      if (off > 0) {
        html += '<span class="gross">' + fmt(gross) + ' ' + cur + '</span>';
        html += '<span class="final">' + fmt(final) + ' ' + cur + '</span>';
        html += '<span class="off">-' + off + '%</span>';
      } else {
        html += '<span class="final">' + fmt(final) + ' ' + cur + '</span>';
      }
      out.innerHTML = html;
    }

    sel.addEventListener('change', render);
    render();
  });
})();
</script>
@endpush
