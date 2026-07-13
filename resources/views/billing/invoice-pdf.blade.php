<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ __('billing.invoice.title') }} {{ $number }}</title>
    @php
        $primary = '#1d4ed8'; $soft = '#eff6ff'; $muted = '#64748b'; $border = '#e2e8f0'; $text = '#0f172a';
        $isTrial = (bool) $subscription->is_trial;
        $months = (int) ($subscription->planPrice->period_months ?? 0);
        $sym = strtoupper((string) $subscription->currency);
        $money = fn ($v) => number_format((float) $v, 2, ',', ' ') . ' ' . $sym;
    @endphp
    <style>
        * { box-sizing: border-box; }
        @page { margin: 0; }
        body { font-family: "DM Sans", sans-serif; font-size: 10pt; color: {{ $text }}; margin: 0; }
        .accent-bar { height: 7px; background: {{ $primary }}; }
        .wrap { padding: 30px 40px; }
        table { border-collapse: collapse; }
        .layout { width: 100%; }
        .layout td { vertical-align: top; }
        .right { text-align: right; }
        .muted { color: {{ $muted }}; }
        .company-name { font-size: 17pt; font-weight: bold; }
        .company-meta { color: {{ $muted }}; font-size: 8.6pt; line-height: 1.55; margin-top: 5px; }
        .doc-kicker { font-size: 8pt; letter-spacing: .18em; color: {{ $muted }}; text-transform: uppercase; }
        .doc-number { font-size: 20pt; color: {{ $primary }}; font-weight: bold; margin: 2px 0; }
        .parties { width: 100%; margin: 22px 0 10px; }
        .parties td { width: 50%; padding: 14px 16px; background: {{ $soft }}; border-radius: 10px; }
        .parties td.left-cell { padding-right: 8px; background: transparent; }
        .party-title { font-size: 7.4pt; text-transform: uppercase; letter-spacing: .1em; color: {{ $muted }}; margin-bottom: 6px; }
        .party-name { font-size: 11pt; font-weight: bold; margin-bottom: 4px; }
        .party-lines { font-size: 8.8pt; line-height: 1.6; }
        .items { width: 100%; margin: 18px 0; }
        .items thead th { background: {{ $primary }}; color: #fff; padding: 9px; font-size: 7.8pt; text-transform: uppercase; text-align: left; }
        .items thead th:last-child { text-align: right; border-top-right-radius: 7px; }
        .items thead th:first-child { border-top-left-radius: 7px; }
        .items td { padding: 10px 9px; font-size: 9pt; border-bottom: 1px solid {{ $border }}; }
        .totals { width: 46%; margin-left: auto; margin-top: 6px; }
        .totals td { padding: 8px 12px; font-size: 9.5pt; }
        .totals .grand td { background: {{ $primary }}; color: #fff; font-weight: bold; font-size: 12pt; }
        .totals .grand td:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        .totals .grand td:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
        .note { margin-top: 18px; padding: 10px 14px; background: {{ $soft }}; border-left: 3px solid {{ $primary }}; border-radius: 6px; font-size: 8.8pt; }
        .footer { margin-top: 26px; padding-top: 10px; border-top: 1px solid {{ $border }}; font-size: 7.8pt; color: {{ $muted }}; text-align: center; }
    </style>
</head>
<body>
<div class="accent-bar"></div>
<div class="wrap">
    <table class="layout">
        <tr>
            <td style="width:58%;">
                <div class="company-name">{{ config('app.name', 'CRM') }}</div>
                <div class="company-meta">{{ config('mail.from.address') }}</div>
            </td>
            <td class="right" style="width:42%;">
                <div class="doc-kicker">{{ __('billing.invoice.title') }}</div>
                <div class="doc-number">{{ $number }}</div>
                <div class="muted" style="font-size:8.6pt;">{{ __('billing.invoice.date') }} : {{ optional($subscription->created_at)->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td class="left-cell">
                <div class="party-title">{{ __('billing.invoice.title') }}</div>
                <div class="party-name">{{ config('app.name', 'CRM') }}</div>
                <div class="party-lines muted">{{ config('mail.from.address') }}</div>
            </td>
            <td>
                <div class="party-title">Client</div>
                <div class="party-name">{{ $subscription->tenant->name ?? '-' }}</div>
                <div class="party-lines">
                    {{ $subscription->tenant->email ?? '' }}
                    @if(!empty($subscription->tenant->address))<br>{{ $subscription->tenant->address }}@endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead><tr>
            <th>{{ __('billing.invoice.plan') }}</th>
            <th>{{ __('billing.invoice.period') }}</th>
            <th style="text-align:right;">{{ __('billing.invoice.amount') }}</th>
        </tr></thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $subscription->plan->name ?? '-' }}</strong>
                    @if($isTrial)<br><span class="muted">{{ __('billing.common.trial') }}</span>@endif
                </td>
                <td>{{ $isTrial ? __('billing.common.trial') : ($months . ' ' . __('billing.common.months')) }}</td>
                <td class="right">{{ $isTrial ? __('billing.common.free') : $money($subscription->amount) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr class="grand">
            <td>{{ __('billing.invoice.amount') }}</td>
            <td class="right">{{ $isTrial ? __('billing.common.free') : $money($subscription->amount) }}</td>
        </tr>
    </table>

    <div class="note">
        {{ __('billing.invoice.valid_until') }} : <strong>{{ optional($subscription->ends_at)->format('d/m/Y') }}</strong>.
        @if($isTrial) — {{ __('billing.onboarding.trial_badge', ['days' => optional($subscription->trial_ends_at) && $subscription->starts_at ? $subscription->starts_at->diffInDays($subscription->trial_ends_at) : ($subscription->plan->trial_days ?? 0)]) }}@endif
    </div>

    <div class="footer">{{ __('billing.invoice.footer') }}</div>
</div>
</body>
</html>
