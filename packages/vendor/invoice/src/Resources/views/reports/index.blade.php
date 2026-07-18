@extends('invoice::layouts.invoice')

@php
  $reportsPage = trans('invoice::invoices.pages.reports_index');
  $common = trans('invoice::invoices.common');

  // Devise de base (paramètres généraux) utilisée pour les totaux consolidés.
  $baseCurrency = $baseCurrency ?? strtoupper((string) (auth()->user()->tenant->currency ?: config('invoice.default_currency', 'EUR')));
  $multiCurrency = $multiCurrency ?? false;
  $ratesConfigured = $ratesConfigured ?? false;

  // Formateur monétaire (repli sur la devise de base).
  $fmt = fn ($v, $code = null, $dec = 0) => \Vendor\Invoice\Support\Money::format((float) $v, $code ?: $baseCurrency, $dec);

  // Ventilation par devise -> "12 500 € · 8 300 $ · 45 000 DH" (uniquement en multi-devise).
  $chips = function (array $by) use ($multiCurrency) {
    if (!$multiCurrency) { return ''; }
    $parts = [];
    foreach ($by as $code => $amount) {
      if ((float) $amount == 0.0) { continue; }
      $parts[] = \Vendor\Invoice\Support\Money::format((float) $amount, (string) $code, 0);
    }
    return implode(' · ', $parts);
  };

  $eq = $multiCurrency ? '≈ ' : '';
  $equivLabel = $multiCurrency ? ' (' . ($reportsPage['equivalent'] ?? 'équivalent') . ')' : '';

  $kpiRevenueYear = $kpiRevenueYear ?? ['base' => $stats['revenue']['year'] ?? 0, 'by' => []];
  $kpiCollected   = $kpiCollected   ?? ['base' => $stats['invoices']['paid_total'] ?? 0, 'by' => []];
  $kpiDue         = $kpiDue         ?? ['base' => $stats['invoices']['due_total'] ?? 0, 'by' => []];
@endphp

@section('title', __('invoice::invoices.reports'))

@section('breadcrumb')
  <span>{{ __('invoice::invoices.billing') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('invoice::invoices.reports') }}</span>
@endsection

@push('styles')
<style>
  .stat-breakdown{margin-top:4px;font-size:11px;line-height:1.5;color:var(--c-ink-40);word-break:break-word;}
  .report-currency-note{display:flex;align-items:center;gap:8px;margin:0 0 14px;padding:10px 14px;border:1px solid var(--c-ink-05);border-radius:12px;background:var(--surface-1);font-size:12.5px;color:var(--c-ink-60);}
  .report-currency-note i{color:var(--c-accent);}
</style>
@endpush

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-chart-line', 'bg' => '#dcfce7', 'color' => '#15803d', 'alt' => __('invoice::invoices.reports')])
      <h1 style="margin:0;">{{ $reportsPage['title'] }}</h1>
    </div>
    <p>{{ $reportsPage['subtitle'] }}</p>
  </div>
  <div class="page-header-actions">
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-calendar"></i> {{ date('Y') }}
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
          <a href="?year={{ $y }}" class="dropdown-item {{ (request('year', date('Y')) == $y) ? 'active' : '' }}">{{ $y }}</a>
        @endfor
      </div>
    </div>
    <button class="btn btn-secondary" onclick="window.print()">
      <i class="fas fa-print"></i> {{ $reportsPage['print'] }}
    </button>
    <div class="dropdown">
      <button class="btn btn-primary" data-dropdown-toggle>
        <i class="fas fa-arrow-down-to-line"></i> {{ __('invoice::invoices.actions.export') }}
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('invoices.reports.export', ['format'=>'excel']) }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
        <a href="{{ route('invoices.reports.export', ['format'=>'pdf']) }}" data-pdf-export data-pdf-filename="rapport-facturation.pdf" class="dropdown-item"><i class="fas fa-file-pdf"></i>   PDF</a>
      </div>
    </div>
  </div>
</div>

@if($multiCurrency)
  @if($ratesConfigured)
    <div class="report-currency-note">
      <i class="fas fa-circle-info"></i>
      <span>{{ $reportsPage['multi_currency_note'] }} ({{ $baseCurrency }})</span>
    </div>
  @else
    <div class="report-currency-note" style="border-color:rgba(245,158,11,.35);background:rgba(245,158,11,.08);color:#b45309;">
      <i class="fas fa-triangle-exclamation" style="color:#d97706;"></i>
      <span>{{ $reportsPage['rates_missing_note'] }}</span>
    </div>
  @endif
@endif

{{-- KPI Stats --}}
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-chart-line"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="rCA">{{ $eq }}{{ $fmt($kpiRevenueYear['base']) }}</div>
      <div class="stat-label">{{ $reportsPage['annual_revenue'] }}{{ $equivLabel }}</div>
      @if($multiCurrency && $chips($kpiRevenueYear['by']))<div class="stat-breakdown">{{ $chips($kpiRevenueYear['by']) }}</div>@endif
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-circle-check"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="rPaid">{{ $eq }}{{ $fmt($kpiCollected['base']) }}</div>
      <div class="stat-label">{{ $reportsPage['collected'] }}{{ $equivLabel }}</div>
      @if($multiCurrency && $chips($kpiCollected['by']))<div class="stat-breakdown">{{ $chips($kpiCollected['by']) }}</div>@endif
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-danger-lt);color:var(--c-danger)"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="rDue">{{ $eq }}{{ $fmt($kpiDue['base']) }}</div>
      <div class="stat-label">{{ $reportsPage['to_collect'] }}{{ $equivLabel }}</div>
      @if($multiCurrency && $chips($kpiDue['by']))<div class="stat-breakdown">{{ $chips($kpiDue['by']) }}</div>@endif
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-arrow-trend-up"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="rConv">
        @php
          $total    = $stats['quotes']['total'] ?? 0;
          $accepted = $stats['quotes']['accepted'] ?? 0;
          $rate     = $total > 0 ? round($accepted / $total * 100) : 0;
        @endphp
        {{ $rate }}%
      </div>
      <div class="stat-label">{{ $reportsPage['quote_conversion'] }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info)"><i class="fas fa-file-invoice"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="rInvTotal">{{ $stats['invoices']['total'] ?? 0 }}</div>
      <div class="stat-label">{{ $reportsPage['total_invoices'] }}</div>
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">

  {{-- Graphique CA mensuel (en devise de base) --}}
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="chart-card">
      <div class="chart-card-header">
        <span class="chart-card-title">
          <i class="fas fa-chart-bar" style="color:var(--c-accent);margin-right:8px;"></i>
          {{ $reportsPage['monthly_revenue'] }}@if($multiCurrency) <span style="font-weight:var(--fw-normal);color:var(--c-ink-40);font-size:12px;">({{ $baseCurrency }})</span>@endif
        </span>
        <div style="display:flex;gap:12px;font-size:12px;">
          <span style="display:flex;align-items:center;gap:6px;color:var(--c-ink-40);">
            <span style="width:10px;height:10px;border-radius:2px;background:var(--c-accent);display:inline-block;"></span> {{ $reportsPage['billed_revenue'] }}
          </span>
          <span style="display:flex;align-items:center;gap:6px;color:var(--c-ink-40);">
            <span style="width:10px;height:10px;border-radius:2px;background:var(--c-success);display:inline-block;"></span> {{ $reportsPage['collected'] }}
          </span>
        </div>
      </div>
      <div class="chart-body">
        @php
          $months = $reportsPage['months_short'];
          $maxVal = max(1, max(array_merge(array_values($monthlyRevenue ?? [1]), array_values($monthlyPaid ?? [1]))));
        @endphp
        <div style="display:flex;align-items:flex-end;gap:6px;height:200px;padding:0 0 24px;border-bottom:2px solid var(--c-ink-05);position:relative;">
          @foreach($months as $i => $month)
          @php
            $rev  = $monthlyRevenue[$i+1] ?? 0;
            $paid = $monthlyPaid[$i+1]    ?? 0;
            $revH  = max(4, round($rev  / $maxVal * 180));
            $paidH = max(4, round($paid / $maxVal * 180));
          @endphp
          <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
            <div style="display:flex;gap:3px;align-items:flex-end;width:100%;justify-content:center;">
              <div style="width:45%;height:{{ $revH }}px;background:var(--c-accent);border-radius:3px 3px 0 0;opacity:.8;transition:height .6s;" title="{{ $month }}: {{ $fmt($rev) }}"></div>
              <div style="width:45%;height:{{ $paidH }}px;background:var(--c-success);border-radius:3px 3px 0 0;opacity:.8;transition:height .6s;" title="{{ __('invoice::invoices.pages.reports_index.month_paid_title', ['month' => $month, 'amount' => $fmt($paid)]) }}"></div>
            </div>
            <div style="font-size:10px;color:var(--c-ink-40);">{{ $month }}</div>
          </div>
          @endforeach
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:12px;font-size:12px;color:var(--c-ink-40);">
          <span>{{ $reportsPage['billed_revenue'] }} : <strong style="color:var(--c-ink);">{{ $fmt(array_sum($monthlyRevenue ?? [0])) }}</strong></span>
          <span>{{ $reportsPage['collected'] }} : <strong style="color:var(--c-success);">{{ $fmt(array_sum($monthlyPaid ?? [0])) }}</strong></span>
        </div>
      </div>
    </div>

    {{-- Tableau récap par mois (en devise de base) --}}
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">{{ $reportsPage['monthly_summary'] }}@if($multiCurrency) <span style="font-weight:var(--fw-normal);color:var(--c-ink-40);font-size:12px;">({{ $baseCurrency }})</span>@endif</span>
      </div>
      <table class="crm-table">
        <thead>
          <tr>
            <th>{{ $reportsPage['month'] }}</th>
            <th style="text-align:right">{{ $reportsPage['invoices_issued'] }}</th>
            <th style="text-align:right">{{ $reportsPage['billed_revenue'] }}</th>
            <th style="text-align:right">{{ $reportsPage['collected'] }}</th>
            <th style="text-align:right">{{ $reportsPage['rate'] }}</th>
            <th style="text-align:right">{{ $reportsPage['late'] }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($months as $i => $month)
          @php
            $rev     = $monthlyRevenue[$i+1] ?? 0;
            $paid    = $monthlyPaid[$i+1]    ?? 0;
            $count   = $monthlyCount[$i+1]   ?? 0;
            $overdue = $monthlyOverdue[$i+1] ?? 0;
            $rate    = $rev > 0 ? round($paid / $rev * 100) : 0;
          @endphp
          <tr>
            <td style="font-weight:var(--fw-medium);">{{ $month }} {{ date('Y') }}</td>
            <td class="text-right">{{ $count }}</td>
            <td class="text-right fw-semi font-mono">{{ $fmt($rev, null, 2) }}</td>
            <td class="text-right fw-semi font-mono" style="color:var(--c-success);">{{ $fmt($paid, null, 2) }}</td>
            <td class="text-right">
              <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end;">
                <div style="width:50px;height:5px;background:var(--c-ink-05);border-radius:99px;overflow:hidden;">
                  <div style="width:{{ $rate }}%;height:100%;background:{{ $rate >= 80 ? 'var(--c-success)' : ($rate >= 50 ? 'var(--c-warning)' : 'var(--c-danger)') }};border-radius:99px;"></div>
                </div>
                <span>{{ $rate }}%</span>
              </div>
            </td>
            <td class="text-right" style="{{ $overdue > 0 ? 'color:var(--c-danger);' : 'color:var(--c-ink-40);' }}">{{ $overdue > 0 ? $fmt($overdue, null, 2) : '—' }}</td>
          </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr style="background:var(--surface-1);font-weight:var(--fw-semi);">
            <td>{{ __('invoice::invoices.pages.reports_index.year_total', ['year' => date('Y')]) }}</td>
            <td class="text-right">{{ array_sum($monthlyCount ?? [0]) }}</td>
            <td class="text-right font-mono">{{ $fmt(array_sum($monthlyRevenue ?? [0]), null, 2) }}</td>
            <td class="text-right font-mono" style="color:var(--c-success);">{{ $fmt(array_sum($monthlyPaid ?? [0]), null, 2) }}</td>
            <td class="text-right">
              @php $tot = array_sum($monthlyRevenue ?? [0]); $totP = array_sum($monthlyPaid ?? [0]); @endphp
              {{ $tot > 0 ? round($totP / $tot * 100) : 0 }}%
            </td>
            <td class="text-right" style="color:var(--c-danger);">{{ $fmt(array_sum($monthlyOverdue ?? [0]), null, 2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  {{-- SIDEBAR --}}
  <div class="col-4" style="padding:0 0 0 12px;">

    {{-- Répartition par statut --}}
    <div class="chart-card" style="margin-bottom:16px;">
      <div class="chart-card-header">
        <span class="chart-card-title">
          <i class="fas fa-chart-pie" style="color:var(--c-accent);margin-right:8px;"></i>
          {{ $reportsPage['status_breakdown'] }}
        </span>
      </div>
      <div class="chart-body">
        @php
          $statusData = [
            'paid'      => ['label' => $reportsPage['paid_invoices'], 'color'=>'var(--c-success)', 'value'=> $stats['invoices']['paid'] ?? 0],
            'sent'      => ['label' => $reportsPage['sent_invoices'], 'color'=>'var(--c-info)', 'value'=> $stats['invoices']['sent'] ?? 0],
            'overdue'   => ['label' => $reportsPage['overdue_invoices'], 'color'=>'var(--c-danger)', 'value'=> $stats['invoices']['overdue'] ?? 0],
            'draft'     => ['label' => $reportsPage['draft_invoices'], 'color'=>'var(--c-ink-20)', 'value'=> $stats['invoices']['draft'] ?? 0],
          ];
          $totalInv = array_sum(array_column($statusData, 'value')) ?: 1;
        @endphp
        <div class="donut-legend">
          @foreach($statusData as $s)
          <div class="donut-legend-item">
            <div class="donut-dot" style="background:{{ $s['color'] }};"></div>
            <span class="donut-legend-label">{{ $s['label'] }}</span>
            <span class="donut-legend-value">{{ $s['value'] }}</span>
            <div style="margin-left:auto;width:80px;height:5px;background:var(--c-ink-05);border-radius:99px;overflow:hidden;">
              <div style="width:{{ round($s['value']/$totalInv*100) }}%;height:100%;background:{{ $s['color'] }};border-radius:99px;"></div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- Top clients (classement en devise de base) --}}
    <div class="chart-card" style="margin-bottom:16px;">
      <div class="chart-card-header">
        <span class="chart-card-title">
          <i class="fas fa-trophy" style="color:var(--c-warning);margin-right:8px;"></i>
          {{ $reportsPage['top_clients'] }}@if($multiCurrency) <span style="font-weight:var(--fw-normal);color:var(--c-ink-40);font-size:12px;">({{ $baseCurrency }})</span>@endif
        </span>
      </div>
      <div class="chart-body" style="padding:0;">
        @forelse($topClients ?? [] as $i => $c)
        <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--c-ink-05);">
          <div style="width:24px;height:24px;border-radius:50%;background:{{ ['var(--c-warning)','var(--c-ink-40)','var(--c-accent)'][$i] ?? 'var(--c-ink-10)' }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:var(--fw-bold);">{{ $i+1 }}</div>
          <div class="client-avatar-sm" style="width:32px;height:32px;font-size:11px;">{{ strtoupper(substr($c->company_name ?? 'C', 0, 2)) }}</div>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:var(--fw-medium);font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $c->company_name }}</div>
            <div style="font-size:11.5px;color:var(--c-ink-40);">{{ __('invoice::invoices.pages.reports_index.invoice_count_suffix', ['count' => $c->invoice_count]) }}</div>
          </div>
          <div style="font-weight:var(--fw-semi);font-size:13px;font-family: "DM Sans", sans-serif;color:var(--c-ink);">{{ $fmt($c->total_revenue) }}</div>
        </div>
        @empty
        <div style="padding:20px;text-align:center;color:var(--c-ink-40);font-size:13px;">{{ $common['no_data'] }}</div>
        @endforelse
      </div>
    </div>

    {{-- Modes de paiement (en devise de base) --}}
    <div class="chart-card">
      <div class="chart-card-header">
        <span class="chart-card-title">
          <i class="fas fa-credit-card" style="color:var(--c-accent);margin-right:8px;"></i>
          {{ $reportsPage['payment_methods'] }}@if($multiCurrency) <span style="font-weight:var(--fw-normal);color:var(--c-ink-40);font-size:12px;">({{ $baseCurrency }})</span>@endif
        </span>
      </div>
      <div class="chart-body">
        @php
          $payColors = ['#2563eb','#059669','#0891b2','#7c3aed','#d97706','#dc2626'];
        @endphp
        <div class="donut-legend">
          @forelse($paymentMethods ?? [] as $i => $pm)
          <div class="donut-legend-item">
            <div class="donut-dot" style="background:{{ $payColors[$i % count($payColors)] }};"></div>
            <span class="donut-legend-label">{{ config("invoice.payment_methods.{$pm->payment_method}", $pm->payment_method) }}</span>
            <span class="donut-legend-value">{{ $fmt($pm->total) }}</span>
          </div>
          @empty
          <div style="text-align:center;color:var(--c-ink-40);font-size:13px;">{{ $common['no_data'] }}</div>
          @endforelse
        </div>
      </div>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Animate bars
  document.querySelectorAll('[style*="height:"]').forEach(el => {
    const h = el.style.height;
    el.style.height = '0';
    setTimeout(() => el.style.height = h, 100);
  });
});
</script>
@endpush
