@php
    $isTrial = (bool) $subscription->is_trial;
    $name = $subscription->tenant->name ?? '';
    $app = config('app.name', 'CRM');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#f1f5f9;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;color:#0f172a;">
  <div style="max-width:560px;margin:0 auto;padding:28px 16px;">
    <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 12px 34px rgba(15,23,42,.08);">
      <div style="height:6px;background:#1d4ed8;"></div>
      <div style="padding:30px 30px 26px;">
        <div style="font-size:18px;font-weight:700;color:#1d4ed8;margin-bottom:18px;">{{ $app }}</div>

        <p style="font-size:15px;margin:0 0 12px;">{{ __('billing.invoice.greeting', ['name' => $name]) }}</p>
        <p style="font-size:14.5px;line-height:1.65;color:#475569;margin:0 0 20px;">
          {{ $isTrial ? __('billing.invoice.trial_intro') : __('billing.invoice.intro') }}
        </p>

        <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:20px;">
          <tr>
            <td style="padding:8px 0;color:#64748b;">{{ __('billing.invoice.plan') }}</td>
            <td style="padding:8px 0;text-align:right;font-weight:600;">{{ $subscription->plan->name ?? '-' }}</td>
          </tr>
          <tr>
            <td style="padding:8px 0;color:#64748b;border-top:1px solid #eef2f7;">{{ __('billing.invoice.amount') }}</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">
              {{ $isTrial ? __('billing.common.free') : number_format((float) $subscription->amount, 2, ',', ' ') . ' ' . strtoupper($subscription->currency) }}
            </td>
          </tr>
          <tr>
            <td style="padding:8px 0;color:#64748b;border-top:1px solid #eef2f7;">{{ __('billing.invoice.valid_until') }}</td>
            <td style="padding:8px 0;text-align:right;font-weight:600;border-top:1px solid #eef2f7;">{{ optional($subscription->ends_at)->format('d/m/Y') }}</td>
          </tr>
        </table>

        <p style="font-size:13.5px;color:#94a3b8;margin:0 0 6px;">{{ __('billing.invoice.thanks') }}</p>
        <p style="font-size:12px;color:#cbd5e1;margin:0;">{{ __('billing.invoice.footer') }}</p>
      </div>
    </div>
  </div>
</body>
</html>
