@php
    $name = $subscription->tenant->name ?? '';
    $date = optional($subscription->ends_at)->format('d/m/Y');
    $days = $subscription->ends_at ? max(0, now()->startOfDay()->diffInDays($subscription->ends_at->copy()->startOfDay(), false)) : 0;
    $renewUrl = url('/subscription');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#f1f5f9;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;color:#0f172a;">
  <div style="max-width:560px;margin:0 auto;padding:28px 16px;">
    <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 12px 34px rgba(15,23,42,.08);">
      <div style="height:6px;background:#b45309;"></div>
      <div style="padding:30px 30px 26px;">
        <div style="font-size:18px;font-weight:700;color:#b45309;margin-bottom:18px;">{{ config('app.name', 'CRM') }}</div>
        <p style="font-size:15px;margin:0 0 12px;">{{ __('billing.reminder.greeting', ['name' => $name]) }}</p>
        <p style="font-size:14.5px;line-height:1.65;color:#475569;margin:0 0 22px;">
          {{ __('billing.reminder.body', ['date' => $date, 'days' => $days]) }}
        </p>
        <a href="{{ $renewUrl }}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;font-weight:600;font-size:14px;padding:12px 24px;border-radius:11px;">
          {{ __('billing.reminder.cta') }}
        </a>
      </div>
    </div>
  </div>
</body>
</html>
