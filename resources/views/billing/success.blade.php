@extends('layouts.billing')

@section('title', __('billing.success.title'))

@section('content')
<div style="max-width:560px;margin:40px auto 0;">
  {{-- Alerte de succès --}}
  <div role="alert" style="background:#f0fdf4;border:1px solid #86efac;border-radius:18px;padding:34px 30px;text-align:center;box-shadow:0 12px 34px rgba(22,101,52,.08);">
    <div style="width:78px;height:78px;margin:0 auto 18px;border-radius:50%;background:#dcfce7;border:1px solid #86efac;display:flex;align-items:center;justify-content:center;">
      <i class="fas fa-check" style="font-size:34px;color:#16a34a;"></i>
    </div>

    <h1 style="font-size:24px;font-weight:800;color:#166534;margin:0 0 10px;">{{ __('billing.success.title') }}</h1>
    <p style="font-size:15px;color:#15803d;line-height:1.6;margin:0 0 6px;">{{ __('billing.success.message') }}</p>

    <p style="font-size:13.5px;color:#166534;margin:0 0 4px;">
      <i class="fas fa-envelope"></i> {{ __('billing.success.invoice_sent') }}
    </p>
    @if($subscription->ends_at)
      <p style="font-size:13px;color:#15803d;opacity:.85;margin:0 0 22px;">{{ __('billing.success.valid_until', ['date' => $subscription->ends_at->format('d/m/Y')]) }}</p>
    @endif

    <a href="{{ route('dashboard') }}" class="btn btn-primary" style="padding:12px 26px;font-size:15px;">
      <i class="fas fa-gauge-high"></i> {{ __('billing.success.go_dashboard') }}
    </a>
  </div>
</div>
@endsection
