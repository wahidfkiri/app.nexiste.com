@extends('layouts.global')

@section('title', __('extensions::extensions.marketplace.my_apps.title'))

@section('breadcrumb')
  <a href="{{ route('marketplace.index') }}">{{ __('extensions::extensions.common.marketplace') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('extensions::extensions.marketplace.my_apps.title') }}</span>
@endsection

@section('content')
@php
  $marketplaceAdmin = auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('super-admin'));
@endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-th-large', 'bg' => '#dbeafe', 'color' => '#2563eb', 'alt' => __('extensions::extensions.common.my_apps')])
      <h1 style="margin:0;">{{ __('extensions::extensions.marketplace.my_apps.heading') }}</h1>
    </div>
    <p>{{ __('extensions::extensions.marketplace.my_apps.description') }}</p>
  </div>
  <div class="page-header-actions">
    @if($marketplaceAdmin)
      <a href="{{ route('superadmin.extensions.index') }}" class="btn btn-secondary">
        <i class="fas fa-sliders-h"></i> {{ __('extensions::extensions.common.marketplace_settings') }}
      </a>
    @endif
    <a href="{{ route('marketplace.index') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> {{ __('extensions::extensions.common.discover_apps') }}
    </a>
  </div>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
  @php
    $total     = $activations->count();
    $active    = $activations->where('status','active')->count();
    $trial     = $activations->where('status','trial')->count();
    $inactive  = $activations->where('status','inactive')->count();
  @endphp
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-puzzle-piece"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $total }}</div><div class="stat-label">{{ __('extensions::extensions.marketplace.my_apps.stats_installed') }}</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success);"><i class="fas fa-check-circle"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $active }}</div><div class="stat-label">{{ __('extensions::extensions.marketplace.my_apps.stats_active') }}</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info);"><i class="fas fa-clock"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $trial }}</div><div class="stat-label">{{ __('extensions::extensions.marketplace.my_apps.stats_trial') }}</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-ink-02);color:var(--c-ink-40);"><i class="fas fa-pause-circle"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $inactive }}</div><div class="stat-label">{{ __('extensions::extensions.marketplace.my_apps.stats_inactive') }}</div></div>
  </div>
</div>

@if($activations->isEmpty())
  {{-- Empty state --}}
  <div style="text-align:center;padding:80px 20px;background:var(--surface-0);border-radius:var(--r-2xl);border:1px solid var(--c-ink-05);">
    <div style="width:72px;height:72px;background:var(--surface-2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:28px;color:var(--c-ink-20);">
      <i class="fas fa-puzzle-piece"></i>
    </div>
    <h2 style="font-size:20px;color:var(--c-ink);margin-bottom:8px;">{{ __('extensions::extensions.marketplace.my_apps.empty_title') }}</h2>
    <p style="color:var(--c-ink-40);margin-bottom:24px;">{{ __('extensions::extensions.marketplace.my_apps.empty_description') }}</p>
    <a href="{{ route('marketplace.index') }}" class="btn btn-primary">
      <i class="fas fa-store"></i> {{ __('extensions::extensions.actions.explore_marketplace') }}
    </a>
  </div>
@else

  {{-- Filtres statut --}}
  <div style="display:flex;gap:8px;margin-bottom:20px;">
    @foreach(['' => __('extensions::extensions.marketplace.my_apps.status_all'), 'active' => __('extensions::extensions.marketplace.my_apps.status_active'), 'trial' => __('extensions::extensions.marketplace.my_apps.status_trial'), 'inactive' => __('extensions::extensions.marketplace.my_apps.status_inactive'), 'suspended' => __('extensions::extensions.marketplace.my_apps.status_suspended')] as $st => $stLabel)
    <button class="mkt-cat-btn {{ $st === '' ? 'active' : '' }}" data-status="{{ $st }}" onclick="filterByStatus('{{ $st }}', this)">
      {{ $stLabel }}
    </button>
    @endforeach
  </div>

  {{-- Liste des apps installées --}}
  <div id="myAppsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
    @foreach($activations as $activation)
    @php
      $ext   = $activation->extension;
      if (!$ext) {
          continue;
      }
      $color = $ext->category_color ?? '#64748b';
      $extIconClass = (string) ($ext->icon_class ?? 'fas fa-puzzle-piece');
      $st    = $activation->status;
      $stCfg = ['active'=>['bg'=>'#dcfce7','color'=>'#15803d','label'=>__('extensions::extensions.status.active')],
                 'trial' =>['bg'=>'#dbeafe','color'=>'#1d4ed8','label'=>__('extensions::extensions.status.trial')],
                 'inactive'=>['bg'=>'var(--c-ink-02)','color'=>'var(--c-ink-40)','label'=>__('extensions::extensions.marketplace.my_apps.status_inactive')],
                 'suspended'=>['bg'=>'#fee2e2','color'=>'#b91c1c','label'=>__('extensions::extensions.status.suspended')],
                 'pending'=>['bg'=>'#fef3c7','color'=>'#92400e','label'=>__('extensions::extensions.status.pending')],
                ][$st] ?? ['bg'=>'var(--c-ink-02)','color'=>'var(--c-ink-40)','label'=>ucfirst($st)];
    @endphp
    <div class="my-app-card" data-status="{{ $st }}"
         style="background:var(--surface-0);border:1px solid var(--c-ink-05);border-radius:var(--r-xl);padding:20px;transition:all .25s;">
      {{-- Header --}}
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;">
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="width:48px;height:48px;border-radius:12px;background:{{ $color }}18;display:flex;align-items:center;justify-content:center;font-size:22px;border:1px solid {{ $color }}22;flex-shrink:0;">
            @if($ext->icon_url)
              <img src="{{ $ext->icon_url }}" style="width:28px;height:28px;object-fit:contain;" alt="">
            @else
              <i class="{{ $extIconClass }}" style="color:{{ $color }};"></i>
            @endif
          </div>
          <div>
            <div style="font-weight:700;color:var(--c-ink);font-size:15px;">{{ $ext->name }}</div>
            <div style="font-size:11.5px;color:var(--c-ink-40);">v{{ $ext->version }}</div>
          </div>
        </div>
        <span style="background:{{ $stCfg['bg'] }};color:{{ $stCfg['color'] }};padding:4px 10px;border-radius:99px;font-size:11px;font-weight:700;white-space:nowrap;">
          {{ $stCfg['label'] }}
        </span>
      </div>

      <p style="font-size:12.5px;color:var(--c-ink-40);margin-bottom:12px;line-height:1.5;height:38px;overflow:hidden;">{{ $ext->tagline ?: $ext->description }}</p>

      {{-- Trial warning --}}
      @if($activation->is_trial && $activation->trial_ends_at)
      <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:var(--r-sm);padding:8px 12px;margin-bottom:12px;font-size:12px;color:#92400e;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-clock" style="flex-shrink:0;"></i>
        <span>{{ __('extensions::extensions.marketplace.my_apps.trial_expires') }} <strong>{{ $activation->trial_ends_at->format('d/m/Y') }}</strong>
          ({{ __('extensions::extensions.marketplace.my_apps.trial_remaining', ['count' => $activation->trial_days_remaining]) }})</span>
      </div>
      @endif

      {{-- Infos --}}
      <div style="display:flex;gap:10px;font-size:11.5px;color:var(--c-ink-40);margin-bottom:14px;">
        <span><i class="fas fa-calendar" style="margin-right:3px;"></i>{{ __('extensions::extensions.marketplace.my_apps.since', ['date' => $activation->activated_at?->format('d/m/Y') ?? __('extensions::extensions.common.none_short')]) }}</span>
        @if($activation->price_paid > 0)
          <span><i class="fas fa-euro-sign" style="margin-right:3px;"></i>{{ number_format($activation->price_paid,2) }} {{ $activation->currency }}</span>
        @else
          <span style="color:var(--c-success);"><i class="fas fa-gift" style="margin-right:3px;"></i>{{ __('extensions::extensions.marketplace.my_apps.free_label') }}</span>
        @endif
      </div>

      {{-- Actions --}}
      <div style="display:flex;gap:8px;">
        @if($activation->is_active)
          <a href="{{ route('marketplace.settings', $ext->slug) }}" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">
            <i class="fas fa-cog"></i> {{ __('extensions::extensions.actions.configure') }}
          </a>
          <button class="btn btn-ghost btn-sm" style="color:var(--c-danger);"
                  onclick="deactivateMyApp(@js($ext->slug), @js($ext->name), @js($ext->icon_url), @js($extIconClass), @js($color))">
            <i class="fas fa-plug-circle-xmark"></i>
          </button>
        @elseif($st === 'inactive')
          <button class="btn btn-primary btn-sm" style="flex:1;justify-content:center;"
                  onclick="reactivateMyApp(@js($ext->slug), @js($ext->name), @js($ext->icon_url), @js($extIconClass), @js($color))">
            <i class="fas fa-plug"></i> {{ __('extensions::extensions.marketplace.my_apps.reactivate_button') }}
          </button>
        @elseif($st === 'suspended')
          <div style="font-size:12px;color:var(--c-danger);flex:1;">
            <i class="fas fa-ban" style="margin-right:4px;"></i>
            {{ $activation->suspension_reason ?? __('extensions::extensions.marketplace.my_apps.suspended_fallback') }}
          </div>
        @endif
        <a href="{{ route('marketplace.show', $ext->slug) }}" class="btn btn-ghost btn-sm" title="{{ __('extensions::extensions.marketplace.my_apps.details_title') }}">
          <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    </div>
    @endforeach
  </div>

@endif

@endsection

@push('styles')
<style>
.mkt-cat-btn { padding:8px 16px;background:var(--surface-1);border:1.5px solid var(--c-ink-10);border-radius:99px;font-size:12.5px;font-weight:var(--fw-medium);color:var(--c-ink-60);cursor:pointer;transition:all .2s; }
.mkt-cat-btn.active { background:var(--c-accent);border-color:var(--c-accent);color:#fff; }
.my-app-card:hover { box-shadow:var(--shadow-md);transform:translateY(-2px); }
</style>
@endpush

@push('scripts')
<script>
const MY_APPS_ROUTES = {
  activate: @json(route('marketplace.activate', ['slug' => '__SLUG__'])),
  deactivate: @json(route('marketplace.deactivate', ['slug' => '__SLUG__'])),
};

function marketplaceRoute(template, slug) {
  return String(template).replace('__SLUG__', encodeURIComponent(String(slug)));
}

function filterByStatus(status, btn) {
  document.querySelectorAll('.mkt-cat-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.my-app-card').forEach(card => {
    card.style.display = (!status || card.dataset.status === status) ? '' : 'none';
  });
}

function buildMarketplaceConfirmIcon(name, iconUrl, iconClass) {
  const safeName = document.createElement('div');
  safeName.textContent = name || '';

  if (iconUrl) {
    return `<img src="${String(iconUrl).replace(/"/g, '&quot;')}" alt="${safeName.innerHTML}">`;
  }

  return `<i class="${String(iconClass || 'fas fa-puzzle-piece').replace(/"/g, '')}"></i>`;
}

async function deactivateMyApp(slug, name, iconUrl = '', iconClass = 'fas fa-puzzle-piece', color = '#2563eb') {
  Modal.confirm({
    title: @json(__('extensions::extensions.marketplace.my_apps.deactivate_title', ['name' => ':name'])).replace(':name', name),
    message: @json(__('extensions::extensions.marketplace.my_apps.deactivate_message')),
    confirmText: @json(__('extensions::extensions.marketplace.my_apps.deactivate_confirm')),
    type: 'danger',
    iconHtml: buildMarketplaceConfirmIcon(name, iconUrl, iconClass),
    iconVariant: 'app',
    iconColor: color,
    onConfirm: async () => {
      const { ok, data } = await Http.post(marketplaceRoute(MY_APPS_ROUTES.deactivate, slug), {});
      if (ok) { Toast.success(@json(__('extensions::extensions.marketplace.my_apps.deactivate_success')), data.message); setTimeout(() => location.reload(), 900); }
      else Toast.error(@json(__('extensions::extensions.common.error')), data.message);
    }
  });
}

async function reactivateMyApp(slug, name, iconUrl = '', iconClass = 'fas fa-puzzle-piece', color = '#2563eb') {
  const { ok, data } = await Http.post(marketplaceRoute(MY_APPS_ROUTES.activate, slug), {});
  if (ok) {
    Toast.success(@json(__('extensions::extensions.marketplace.my_apps.reactivate_success')), data.message);
    if (data.redirect) {
      setTimeout(() => { window.location.href = data.redirect; }, 450);
      return;
    }
    setTimeout(() => location.reload(), 900);
  }
  else Toast.error(@json(__('extensions::extensions.common.error')), data.message);
}
</script>
@endpush
