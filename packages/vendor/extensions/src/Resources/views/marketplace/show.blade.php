@extends('layouts.global')

@section('title', $extension->name)

@section('breadcrumb')
  <a href="{{ route('marketplace.index') }}">{{ __('extensions::extensions.common.applications') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $extension->name }}</span>
@endsection

@section('content')

@php
  $color    = $extension->category_color;
  $isActive = $activation && $activation->is_active;
  $isTrial  = $activation && $activation->is_trial;
  $extIconClass = (string) ($extension->icon_class ?? 'fas fa-puzzle-piece');
  $categoryIconClass = str_starts_with((string) $extension->category_icon, 'fa-')
      ? 'fas ' . $extension->category_icon
      : (string) $extension->category_icon;
@endphp

{{-- Banner --}}
@if($extension->banner_url)
<div style="height:200px;border-radius:var(--r-2xl);overflow:hidden;margin-bottom:24px;background:linear-gradient(135deg,{{ $color }}22,{{ $color }}08);">
  <img src="{{ $extension->banner_url }}" style="width:100%;height:100%;object-fit:cover;" alt="">
</div>
@endif

<div class="row" style="align-items:flex-start;">

  {{-- Colonne principale --}}
  <div class="col-8" style="padding:0 12px 0 0;">

    {{-- Header extension --}}
    <div style="display:flex;align-items:flex-start;gap:20px;margin-bottom:24px;">
      <div style="width:72px;height:72px;border-radius:18px;background:{{ $color }}18;display:flex;align-items:center;justify-content:center;font-size:32px;flex-shrink:0;border:1px solid {{ $color }}22;">
        @if($extension->icon_url)
          <img src="{{ $extension->icon_url }}" style="width:44px;height:44px;object-fit:contain;" alt="">
        @else
          <i class="{{ $extIconClass }}" style="color:{{ $color }};"></i>
        @endif
      </div>
      <div style="flex:1;">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
          <h1 style="font-size:24px;font-weight:800;color:var(--c-ink);margin:0;">{{ $extension->name }}</h1>
          @if($extension->is_official)
            <span style="background:#f3e8ff;color:#7c3aed;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;">
              <i class="fas fa-certificate" style="font-size:10px;"></i> {{ __('extensions::extensions.common.official') }}
            </span>
          @endif
          @if($extension->is_verified)
            <span style="background:#dbeafe;color:#1d4ed8;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;">
              <i class="fas fa-check-circle" style="font-size:10px;"></i> {{ __('extensions::extensions.common.verified') }}
            </span>
          @endif
          @if($extension->is_new)
            <span style="background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;">{{ __('extensions::extensions.common.new') }}</span>
          @endif
        </div>
        <p style="font-size:15px;color:var(--c-ink-60);margin:0 0 10px;">{{ $extension->tagline }}</p>
        <div style="display:flex;align-items:center;gap:16px;font-size:13px;color:var(--c-ink-40);">
          <span style="background:{{ $color }}18;color:{{ $color }};padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">
            <i class="{{ $categoryIconClass }}" style="font-size:10px;margin-right:4px;"></i>{{ $extension->category_label }}
          </span>
          @if($extension->rating > 0)
          <span><i class="fas fa-star" style="color:#f59e0b;margin-right:3px;"></i>{{ $extension->rating }}/5 ({{ $extension->ratings_count }} avis)</span>
          @endif
          <span><i class="fas fa-download" style="margin-right:3px;"></i>{{ number_format($extension->installs_count) }} installations</span>
          <span>v{{ $extension->version }}</span>
        </div>
      </div>
    </div>

    {{-- Description --}}
    @if($extension->description)
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-circle-info"></i><h3>{{ __('extensions::extensions.common.about') }}</h3></div>
      <div class="info-card-body">
        @if($extension->long_description)
          <div style="font-size:14px;color:var(--c-ink-60);line-height:1.8;">
            {!! nl2br(e($extension->long_description)) !!}
          </div>
        @else
          <p style="font-size:14px;color:var(--c-ink-60);line-height:1.8;margin:0;">{{ $extension->description }}</p>
        @endif
      </div>
    </div>
    @endif

    {{-- Screenshots --}}
    @if(!empty($extension->screenshots))
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-images"></i><h3>{{ __('extensions::extensions.common.preview') }}</h3></div>
      <div class="info-card-body" style="display:flex;gap:10px;overflow-x:auto;padding-bottom:4px;">
        @foreach($extension->screenshots as $s)
          <img src="{{ asset($s) }}" style="height:160px;border-radius:var(--r-md);border:1px solid var(--c-ink-05);flex-shrink:0;" alt="">
        @endforeach
      </div>
    </div>
    @endif

    {{-- Avis --}}
    @if($extension->approvedReviews->isNotEmpty())
    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-comments"></i><h3>{{ __('extensions::extensions.marketplace.show.reviews', ['count' => $extension->approvedReviews->count()]) }}</h3></div>
      <div class="info-card-body" style="padding:0;">
        @foreach($extension->approvedReviews->take(5) as $review)
        <div style="padding:16px 20px;border-bottom:1px solid var(--c-ink-05);">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:32px;height:32px;border-radius:50%;background:var(--c-accent-lt);color:var(--c-accent);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;">
                {{ strtoupper(substr($review->user->name ?? 'A', 0, 2)) }}
              </div>
              <div>
                <div style="font-weight:var(--fw-medium);font-size:13px;">{{ $review->user->name ?? '—' }}</div>
                <div style="font-size:11px;color:var(--c-ink-40);">{{ $review->tenant->name ?? '' }}</div>
              </div>
            </div>
            <div>
              @for($i=1;$i<=5;$i++)
                <i class="fas fa-star" style="color:{{ $i <= $review->rating ? '#f59e0b' : 'var(--c-ink-10)' }};font-size:12px;"></i>
              @endfor
            </div>
          </div>
          @if($review->title)
            <div style="font-weight:var(--fw-semi);font-size:13.5px;margin-bottom:4px;">{{ $review->title }}</div>
          @endif
          @if($review->body)
            <p style="font-size:13px;color:var(--c-ink-60);line-height:1.6;margin:0;">{{ $review->body }}</p>
          @endif
        </div>
        @endforeach
      </div>
    </div>
    @endif

  </div>

  {{-- Sidebar --}}
  <div class="col-4" style="padding:0 0 0 12px;">

    {{-- CTA principal --}}
    <div class="form-section" style="margin-bottom:16px;text-align:center;">
      @if($isActive)
        {{-- Déjà activée --}}
        <div style="background:#dcfce7;border-radius:var(--r-md);padding:14px;margin-bottom:16px;display:flex;align-items:center;gap:10px;">
          <i class="fas fa-circle-check" style="color:#15803d;font-size:18px;"></i>
          <div style="text-align:left;">
            <div style="font-weight:var(--fw-semi);color:#15803d;font-size:13.5px;">
              {{ $isTrial ? __('extensions::extensions.marketplace.show.trial_now') : __('extensions::extensions.marketplace.show.active_now') }}
            </div>
            @if($isTrial && $activation->trial_ends_at)
              <div style="font-size:12px;color:#166534;">
                {{ __('extensions::extensions.marketplace.show.trial_expires', ['date' => $activation->trial_ends_at->format('d/m/Y'), 'days' => $activation->trial_days_remaining]) }}
              </div>
            @endif
          </div>
        </div>
        @if(!$extension->is_free && $isTrial)
          <button class="btn btn-primary" style="width:100%;justify-content:center;margin-bottom:10px;">
            <i class="fas fa-credit-card"></i> {{ __('extensions::extensions.marketplace.show.upgrade') }}
          </button>
        @endif
        <a href="{{ route('marketplace.settings', $extension->slug) }}" class="btn btn-secondary" style="width:100%;justify-content:center;margin-bottom:10px;">
          <i class="fas fa-cog"></i> {{ __('extensions::extensions.actions.configure') }}
        </a>
        <button class="btn btn-ghost" style="width:100%;justify-content:center;color:var(--c-danger);"
                onclick="deactivateExt(@js($extension->slug), @js($extension->name), @js($extension->icon_url), @js($extIconClass), @js($color))">
          <i class="fas fa-plug-circle-xmark"></i> {{ __('extensions::extensions.actions.deactivate') }}
        </button>
      @else
        {{-- Pas encore activée --}}
        <div style="font-size:26px;font-weight:800;color:var(--c-ink);margin-bottom:4px;">
          @if($extension->is_free)
            <span style="color:var(--c-success);">{{ __('extensions::extensions.common.free') }}</span>
          @else
            {{ number_format($extension->price, 2) }} {{ $extension->currency }}
            <span style="font-size:14px;font-weight:400;color:var(--c-ink-40);">
              / {{ config("extensions.billing_cycles.{$extension->billing_cycle}", '') }}
            </span>
          @endif
        </div>
        @if($extension->yearly_price)
          <div style="font-size:12.5px;color:var(--c-success);margin-bottom:12px;">
            {{ __('extensions::extensions.marketplace.show.yearly_offer', ['price' => number_format($extension->yearly_price, 2) . ' ' . $extension->currency]) }}
            <span style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:99px;font-size:10px;margin-left:4px;">
              {{ __('extensions::extensions.marketplace.show.save_percent', ['percent' => round((1 - $extension->yearly_price / ($extension->price * 12)) * 100)]) }}
            </span>
          </div>
        @endif

        @if($extension->has_trial)
          <button class="btn btn-primary" style="width:100%;justify-content:center;margin-bottom:10px;"
                  onclick="activateExt(@js($extension->slug), @js($extension->name), true, @js($extension->icon_url), @js($extIconClass), @js($color))">
            <i class="fas fa-rocket"></i> {{ __('extensions::extensions.actions.try_free') }} {{ $extension->trial_days }} jours
          </button>
          @if(!$extension->is_free)
            <button class="btn btn-secondary" style="width:100%;justify-content:center;"
                    onclick="activateExt(@js($extension->slug), @js($extension->name), false, @js($extension->icon_url), @js($extIconClass), @js($color))">
              <i class="fas fa-plug"></i> {{ __('extensions::extensions.actions.activate_now') }}
            </button>
          @endif
        @else
          <button class="btn btn-primary" style="width:100%;justify-content:center;"
                  onclick="activateExt(@js($extension->slug), @js($extension->name), false, @js($extension->icon_url), @js($extIconClass), @js($color))">
            <i class="fas fa-plug"></i>
            {{ $extension->is_free ? __('extensions::extensions.marketplace.show.free_install_button') : __('extensions::extensions.marketplace.show.install_button') }}
          </button>
        @endif
      @endif
    </div>

    {{-- Infos tarification --}}
    @if(!$extension->is_free)
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-euro-sign"></i><h3>{{ __('extensions::extensions.marketplace.show.pricing_title') }}</h3></div>
      <div class="info-card-body">
        <div class="info-row">
          <span class="info-row-label">{{ __('extensions::extensions.marketplace.show.pricing_type') }}</span>
          <span class="info-row-value">{{ config("extensions.pricing_types.{$extension->pricing_type}", $extension->pricing_type) }}</span>
        </div>
        @if($extension->price > 0)
        <div class="info-row">
          <span class="info-row-label">{{ __('extensions::extensions.common.monthly_price') }}</span>
          <span class="info-row-value" style="font-weight:var(--fw-semi);">{{ number_format($extension->price, 2) }} {{ $extension->currency }}</span>
        </div>
        @endif
        @if($extension->yearly_price)
        <div class="info-row">
          <span class="info-row-label">{{ __('extensions::extensions.common.annual_price') }}</span>
          <span class="info-row-value" style="font-weight:var(--fw-semi);color:var(--c-success);">{{ number_format($extension->yearly_price, 2) }} {{ $extension->currency }}</span>
        </div>
        @endif
        @if($extension->has_trial)
        <div class="info-row">
          <span class="info-row-label">{{ __('extensions::extensions.common.trial_free') }}</span>
          <span class="info-row-value">{{ $extension->trial_days }} {{ __('extensions::extensions.common.day_unit') }}</span>
        </div>
        @endif
      </div>
    </div>
    @endif

    {{-- Infos extension --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-circle-info"></i><h3>{{ __('extensions::extensions.common.details') }}</h3></div>
      <div class="info-card-body">
        @if($extension->developer_name)
        <div class="info-row">
          <span class="info-row-label">{{ __('extensions::extensions.common.editor') }}</span>
          <span class="info-row-value">
            @if($extension->developer_url)
              <a href="{{ $extension->developer_url }}" target="_blank" rel="noopener" style="color:var(--c-accent);">{{ $extension->developer_name }}</a>
            @else
              {{ $extension->developer_name }}
            @endif
          </span>
        </div>
        @endif
        <div class="info-row">
          <span class="info-row-label">{{ __('extensions::extensions.common.version') }}</span>
          <span class="info-row-value" style="font-family: "DM Sans", sans-serif;font-size:12px;">{{ $extension->version }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">{{ __('extensions::extensions.marketplace.show.installs') }}</span>
          <span class="info-row-value">{{ number_format($extension->installs_count) }}</span>
        </div>
        @if($extension->rating > 0)
        <div class="info-row">
          <span class="info-row-label">{{ __('extensions::extensions.marketplace.show.rating') }}</span>
          <span class="info-row-value" style="display:flex;align-items:center;gap:4px;">
            <i class="fas fa-star" style="color:#f59e0b;font-size:12px;"></i>
            {{ $extension->rating }}/5
          </span>
        </div>
        @endif
      </div>
    </div>

    {{-- Liens utiles --}}
    @if($extension->documentation_url || $extension->support_url)
    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-link"></i><h3>{{ __('extensions::extensions.common.resources') }}</h3></div>
      <div class="info-card-body" style="display:flex;flex-direction:column;gap:8px;">
        @if($extension->documentation_url)
          <a href="{{ $extension->documentation_url }}" target="_blank" rel="noopener" class="btn btn-secondary" style="justify-content:flex-start;">
            <i class="fas fa-book"></i> {{ __('extensions::extensions.common.documentation') }}
          </a>
        @endif
        @if($extension->support_url)
          <a href="{{ $extension->support_url }}" target="_blank" rel="noopener" class="btn btn-secondary" style="justify-content:flex-start;">
            <i class="fas fa-headset"></i> {{ __('extensions::extensions.common.support') }}
          </a>
        @endif
      </div>
    </div>
    @endif

  </div>
</div>

@endsection

@push('scripts')
<script>
const MKT_SHOW_ROUTES = {
  activate: @json(route('marketplace.activate', $extension->slug)),
  deactivate: @json(route('marketplace.deactivate', $extension->slug)),
};

function buildMarketplaceConfirmIcon(name, iconUrl, iconClass) {
  const safeName = document.createElement('div');
  safeName.textContent = name || '';

  if (iconUrl) {
    return `<img src="${String(iconUrl).replace(/"/g, '&quot;')}" alt="${safeName.innerHTML}">`;
  }

  return `<i class="${String(iconClass || 'fas fa-puzzle-piece').replace(/"/g, '')}"></i>`;
}

async function activateExt(slug, name, isTrial, iconUrl = '', iconClass = 'fas fa-puzzle-piece', color = '#2563eb') {
  const msg = isTrial ? @json(__('extensions::extensions.marketplace.show.activate_trial_title', ['name' => ':name'])).replace(':name', name) : @json(__('extensions::extensions.marketplace.show.activate_default_title', ['name' => ':name'])).replace(':name', name);
  Modal.confirm({
    title: msg,
    message: isTrial ? @json(__('extensions::extensions.marketplace.show.activate_trial_message')) : @json(__('extensions::extensions.marketplace.show.activate_default_message')),
    confirmText: isTrial ? @json(__('extensions::extensions.marketplace.show.activate_trial_confirm')) : @json(__('extensions::extensions.marketplace.show.activate_default_confirm')),
    type: 'success',
    iconHtml: buildMarketplaceConfirmIcon(name, iconUrl, iconClass),
    iconVariant: 'app',
    iconColor: color,
    onConfirm: async () => {
      const { ok, data } = await Http.post(MKT_SHOW_ROUTES.activate, {});
      if (ok) {
        Toast.success(@json(__('extensions::extensions.marketplace.show.activate_success')), data.message);
        if (data.redirect) {
          setTimeout(() => { window.location.href = data.redirect; }, 450);
          return;
        }
        setTimeout(() => location.reload(), 1000);
      } else Toast.error(@json(__('extensions::extensions.common.error')), data.message);
    }
  });
}

async function deactivateExt(slug, name, iconUrl = '', iconClass = 'fas fa-puzzle-piece', color = '#2563eb') {
  Modal.confirm({
    title: @json(__('extensions::extensions.marketplace.show.deactivate_title', ['name' => ':name'])).replace(':name', name),
    message: @json(__('extensions::extensions.marketplace.show.deactivate_message')),
    confirmText: @json(__('extensions::extensions.marketplace.show.deactivate_confirm')),
    type: 'danger',
    iconHtml: buildMarketplaceConfirmIcon(name, iconUrl, iconClass),
    iconVariant: 'app',
    iconColor: color,
    onConfirm: async () => {
      const { ok, data } = await Http.post(MKT_SHOW_ROUTES.deactivate, {});
      if (ok) { Toast.success(@json(__('extensions::extensions.marketplace.show.deactivate_success')), data.message); setTimeout(() => location.reload(), 900); }
      else Toast.error(@json(__('extensions::extensions.common.error')), data.message);
    }
  });
}
</script>
@endpush

