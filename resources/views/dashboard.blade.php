@extends('layouts.global')

@section('title', data_get($dashboard, 'meta.title', __('dashboard.page_title')))
@section('body_class', 'nexus-dashboard-shell')
@section('content_class', 'nexus-dashboard-content')

@section('breadcrumb')
  <span>{{ data_get($dashboard, 'meta.title', __('dashboard.page_title')) }}</span>
@endsection

@php
  $formatDate = static function ($value, string $format = 'd/m/Y') {
      if (empty($value)) return '—';
      try {
          return $value instanceof \Carbon\CarbonInterface
              ? $value->format($format)
              : \Carbon\Carbon::parse($value)->format($format);
      } catch (\Throwable) {
          return (string) $value;
      }
  };

  $formatMoney = static function ($value, ?string $currency = null) use ($dashboard) {
      return number_format((float) $value, 2, ',', ' ') . ' ' . strtoupper((string) ($currency ?: data_get($dashboard, 'meta.currency', 'EUR')));
  };

  $iconMarkup = static function (?string $icon, string $fallback = 'fas fa-circle') {
      $icon = trim((string) ($icon ?: $fallback));
      if (str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://') || str_starts_with($icon, '/')) {
          return '<img src="' . e($icon) . '" alt="">';
      }
      $class = str_contains($icon, ' ') ? $icon : 'fas ' . $icon;
      return '<i class="' . e($class) . '"></i>';
  };

  $charts = data_get($dashboard, 'charts', []);
@endphp

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard-command.css') }}?v={{ filemtime(public_path('css/dashboard-command.css')) }}">
@endpush

@section('content')
<div class="nexus-dashboard">
  <section class="nd-hero">
    <div class="nd-hero-orb nd-hero-orb-one"></div>
    <div class="nd-hero-orb nd-hero-orb-two"></div>

    <div class="nd-hero-main">
      @if(data_get($dashboard, 'meta.eyebrow'))
        <span class="nd-kicker"><i class="fas fa-sparkles"></i>{{ data_get($dashboard, 'meta.eyebrow') }}</span>
      @endif
      <h1>{{ data_get($dashboard, 'meta.title') }}</h1>
      <p>{{ data_get($dashboard, 'meta.subtitle') }}</p>

      <div class="nd-actions">
        @forelse(data_get($dashboard, 'actions', []) as $action)
          @continue(empty($action['url']))
          <a href="{{ $action['url'] }}" class="nd-action nd-action-{{ $action['variant'] ?? 'secondary' }}">
            <span>{!! $iconMarkup($action['icon'] ?? 'fas fa-arrow-right') !!}</span>
            {{ $action['label'] ?? __('dashboard.actions.fallback') }}
          </a>
        @empty
          <a href="{{ route('marketplace.index') }}" class="nd-action nd-action-primary"><span><i class="fas fa-store"></i></span>{{ __('dashboard.actions.applications') }}</a>
        @endforelse
      </div>
    </div>

    <aside class="nd-command-card">
      <div class="nd-user-chip">
        <div class="nd-user-avatar">{{ data_get($dashboard, 'meta.user.initials', 'U') }}</div>
        <div>
          <strong>{{ data_get($dashboard, 'meta.user.name') }}</strong>
          <span>{{ data_get($dashboard, 'meta.user.role') }}</span>
        </div>
      </div>

      <div class="nd-command-grid">
        <div><span>{{ __('dashboard.command.modules') }}</span><strong>{{ __('dashboard.command.modules_active', ['count' => count(data_get($dashboard, 'modules', []))]) }}</strong></div>
        <div><span>{{ __('dashboard.command.currency') }}</span><strong>{{ data_get($dashboard, 'meta.currency') }}</strong></div>
        <div><span>{{ __('dashboard.command.integrations') }}</span><strong>{{ data_get($dashboard, 'integrations.connected', 0) }}/{{ data_get($dashboard, 'integrations.total', 0) }}</strong></div>
        <div><span>{{ __('dashboard.command.date') }}</span><strong>{{ data_get($dashboard, 'meta.date') }}</strong></div>
      </div>
    </aside>
  </section>

  <section class="nd-signal-rail" aria-label="{{ __('dashboard.signals.aria_label') }}">
    @foreach(data_get($dashboard, 'signals', []) as $signal)
      <article class="nd-signal nd-tone-{{ $signal['tone'] ?? 'blue' }}">
        <div class="nd-signal-icon">{!! $iconMarkup($signal['icon'] ?? 'fas fa-chart-simple') !!}</div>
        <div class="nd-signal-copy">
          <span>{{ $signal['label'] ?? __('dashboard.signals.fallback_label') }}</span>
          <strong>{{ $signal['value'] ?? '0' }}</strong>
          <small>{{ $signal['hint'] ?? '' }}</small>
        </div>
      </article>
    @endforeach
  </section>

  <section class="nd-bento">
    <article class="nd-panel nd-panel-wide nd-finance-panel">
      <div class="nd-panel-head">
        <div>
          <span class="nd-panel-kicker">{{ __('dashboard.finance.kicker') }}</span>
          <h2>{{ __('dashboard.finance.title') }}</h2>
        </div>
        @if(data_get($dashboard, 'finance.route'))
          <a href="{{ data_get($dashboard, 'finance.route') }}" class="nd-link">{{ __('dashboard.finance.view_invoices') }} <i class="fas fa-arrow-right"></i></a>
        @endif
      </div>
      <div class="nd-finance-layout">
        <div class="nd-chart-wrap"><canvas id="ndFinanceChart"></canvas></div>
        <div class="nd-finance-stack">
          <div class="nd-mini-stat"><span>{{ __('dashboard.finance.issued_revenue') }}</span><strong>{{ $formatMoney(data_get($dashboard, 'finance.revenue_month', 0)) }}</strong></div>
          <div class="nd-mini-stat"><span>{{ __('dashboard.finance.collected') }}</span><strong>{{ $formatMoney(data_get($dashboard, 'finance.payments_month', 0)) }}</strong></div>
          <div class="nd-mini-stat nd-mini-alert"><span>{{ __('dashboard.finance.pending') }}</span><strong>{{ $formatMoney(data_get($dashboard, 'finance.pending_amount', 0)) }}</strong></div>
        </div>
      </div>
    </article>

    <article class="nd-panel nd-modules-panel">
      <div class="nd-panel-head">
        <div>
          <span class="nd-panel-kicker">{{ __('dashboard.modules.kicker') }}</span>
          <h2>{{ __('dashboard.modules.title') }}</h2>
        </div>
      </div>
      <div class="nd-module-grid">
        @forelse(data_get($dashboard, 'modules', []) as $module)
          @if(!empty($module['url']))
            <a href="{{ $module['url'] }}" class="nd-module-card" style="--module-accent:{{ $module['accent'] ?? '#2563eb' }}">
          @else
            <div class="nd-module-card" style="--module-accent:{{ $module['accent'] ?? '#2563eb' }}">
          @endif
              <span class="nd-module-icon">{!! $iconMarkup($module['icon'] ?? 'fas fa-cube') !!}</span>
              <span class="nd-module-label">{{ $module['label'] ?? __('dashboard.modules.fallback_label') }}</span>
              <strong>{{ $module['value'] ?? 0 }}</strong>
              <small>{{ $module['caption'] ?? '' }}</small>
          @if(!empty($module['url']))
            </a>
          @else
            </div>
          @endif
        @empty
          <div class="nd-empty"><i class="fas fa-puzzle-piece"></i><strong>{{ __('dashboard.modules.empty_title') }}</strong><span>{{ __('dashboard.modules.empty_description') }}</span></div>
        @endforelse
      </div>
    </article>

    <div class="nd-ops-activity-row">
    <article class="nd-panel nd-focus-panel">
      <div class="nd-panel-head">
        <div>
          <span class="nd-panel-kicker">{{ __('dashboard.focus.kicker') }}</span>
          <h2>{{ __('dashboard.focus.title') }}</h2>
        </div>
      </div>
      <div class="nd-focus-list">
        @forelse(data_get($dashboard, 'focus', []) as $item)
          @if(!empty($item['url']))
            <a href="{{ $item['url'] }}" class="nd-focus-item" style="--focus-tone:{{ $item['tone'] ?? '#2563eb' }}">
          @else
            <div class="nd-focus-item" style="--focus-tone:{{ $item['tone'] ?? '#2563eb' }}">
          @endif
              <span class="nd-focus-icon">{!! $iconMarkup($item['icon'] ?? 'fas fa-bolt') !!}</span>
              <span class="nd-focus-body">
                <small>{{ $item['kind'] ?? __('dashboard.focus.fallback_kind') }}</small>
                <strong>{{ $item['title'] ?? __('dashboard.focus.fallback_title') }}</strong>
                <em>{{ $item['description'] ?? '' }}</em>
              </span>
              <span class="nd-focus-meta">
                <strong>{{ $item['meta'] ?? '' }}</strong>
                <small>{{ $formatDate($item['date'] ?? null) }}</small>
              </span>
          @if(!empty($item['url']))
            </a>
          @else
            </div>
          @endif
        @empty
          <div class="nd-empty"><i class="fas fa-circle-check"></i><strong>{{ __('dashboard.focus.empty_title') }}</strong><span>{{ __('dashboard.focus.empty_description') }}</span></div>
        @endforelse
      </div>
    </article>

    <article class="nd-panel nd-activity-panel">
      <div class="nd-panel-head">
        <div>
          <span class="nd-panel-kicker">{{ __('dashboard.activity.kicker') }}</span>
          <h2>{{ __('dashboard.activity.title') }}</h2>
        </div>
      </div>
      <div class="nd-timeline">
        @forelse(data_get($dashboard, 'activity', []) as $event)
          @if(!empty($event['url']))
            <a href="{{ $event['url'] }}" class="nd-event" style="--event-tone:{{ $event['tone'] ?? '#2563eb' }}">
          @else
            <div class="nd-event" style="--event-tone:{{ $event['tone'] ?? '#2563eb' }}">
          @endif
              <span class="nd-event-icon">{!! $iconMarkup($event['icon'] ?? 'fas fa-circle') !!}</span>
              <span>
                <strong>{{ $event['title'] ?? __('dashboard.activity.fallback_event') }}</strong>
                <small>{{ $event['description'] ?? '' }}</small>
              </span>
              <time>{{ $formatDate($event['at'] ?? null, 'd/m H:i') }}</time>
          @if(!empty($event['url']))
            </a>
          @else
            </div>
          @endif
        @empty
          <div class="nd-empty"><i class="fas fa-clock"></i><strong>{{ __('dashboard.activity.empty_title') }}</strong><span>{{ __('dashboard.activity.empty_description') }}</span></div>
        @endforelse
      </div>
    </article>
    </div>
  </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function () {
  const charts = @json($charts);
  const root = document.querySelector('.nexus-dashboard');
  if (!root || !window.Chart) return;

  Chart.defaults.font.family = 'DM Sans, sans-serif';
  Chart.defaults.color = '#64748b';

  const ctx = (id) => document.getElementById(id)?.getContext('2d');
  const tooltip = {
    backgroundColor: '#0b1220',
    titleColor: '#fff',
    bodyColor: '#dbeafe',
    borderColor: 'rgba(255,255,255,.10)',
    borderWidth: 1,
    padding: 12,
    cornerRadius: 14
  };

  const financeCtx = ctx('ndFinanceChart');
  if (financeCtx && charts.finance) {
    new Chart(financeCtx, {
      type: 'line',
      data: {
        labels: charts.finance.labels || [],
        datasets: [
          { label: @json(__('dashboard.charts.finance.invoices')), data: charts.finance.invoices || [], borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .42, pointRadius: 3, pointHoverRadius: 6 },
          { label: @json(__('dashboard.charts.finance.payments')), data: charts.finance.payments || [], borderColor: '#14b8a6', backgroundColor: 'rgba(20,184,166,.10)', fill: true, tension: .42, pointRadius: 3, pointHoverRadius: 6 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { tooltip, legend: { display: true, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { weight: '700' } } } },
        scales: {
          x: { grid: { display: false }, ticks: { color: '#64748b' } },
          y: { beginAtZero: true, grid: { color: 'rgba(15,23,42,.08)' }, ticks: { color: '#64748b' } }
        }
      }
    });
  }

})();
</script>
@endpush
