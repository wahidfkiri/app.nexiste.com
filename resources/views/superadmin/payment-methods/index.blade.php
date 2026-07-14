@extends('layouts.global')

@section('title', __('billing.payments.title'))

@section('breadcrumb')
  <a href="{{ route('superadmin.tenants.index') }}">Administration</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('billing.payments.title') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>{{ __('billing.payments.title') }}</h1>
    <p>{{ __('billing.payments.subtitle') }}</p>
  </div>
</div>

@if(session('success'))
  <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:#dcfce7;color:#166534;"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
@endif

<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13px;color:#1e40af;">
  <i class="fas fa-circle-info"></i> {{ __('billing.payments.keys_notice') }}
</div>

<div class="form-section" style="margin-bottom:16px;">
  <h3 class="form-section-title"><i class="fas fa-plus"></i> {{ __('billing.payments.add') }}</h3>
  <form method="POST" action="{{ route('superadmin.payment-methods.store') }}">
    @csrf
    <div class="row">
      <div class="col-5"><div class="form-group">
        <label class="form-label">{{ __('billing.payments.name') }} *</label>
        <input type="text" name="name" class="form-control" value="PayPal" required>
      </div></div>
      <div class="col-4"><div class="form-group">
        <label class="form-label">{{ __('billing.payments.provider') }} *</label>
        <select name="provider" class="form-control">
          <option value="paypal" selected>{{ __('billing.payments.provider_paypal') }}</option>
          <option value="manual">{{ __('billing.payments.provider_manual') }}</option>
          <option value="stripe">{{ __('billing.payments.provider_stripe') }}</option>
        </select>
      </div></div>
      <div class="col-3"><div class="form-group">
        <label class="form-label">&nbsp;</label>
        <div style="padding-top:6px;"><label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="is_default" value="1"> {{ __('billing.common.default') }}</label></div>
      </div></div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('billing.payments.add') }}</button>
  </form>
</div>

<div class="table-wrapper">
  <table class="crm-table">
    <thead><tr>
      <th>{{ __('billing.payments.name') }}</th>
      <th>{{ __('billing.payments.provider') }}</th>
      <th>{{ __('billing.common.active') }}</th>
      <th></th>
    </tr></thead>
    <tbody>
      @forelse($methods as $method)
        <tr>
          <td>
            <strong>{{ $method->name }}</strong>
            @if($method->is_default)<span class="badge" style="background:#dbeafe;color:#1e40af;margin-left:8px;">{{ __('billing.common.default') }}</span>@endif
          </td>
          <td style="text-transform:capitalize;">{{ $method->provider }}</td>
          <td>
            @if($method->is_active)<span class="badge" style="background:#dcfce7;color:#15803d;">{{ __('billing.common.active') }}</span>
            @else<span class="badge" style="background:#f1f5f9;color:#64748b;">{{ __('billing.common.inactive') }}</span>@endif
          </td>
          <td class="text-right" style="white-space:nowrap;">
            <button type="button" class="btn btn-secondary btn-sm test-payment" data-url="{{ route('superadmin.payment-methods.test', $method) }}"><i class="fas fa-vial"></i> {{ __('billing.payments.test') }}</button>
            @unless($method->is_default)
              <form method="POST" action="{{ route('superadmin.payment-methods.default', $method) }}" style="display:inline;">@csrf
                <button class="btn btn-ghost btn-sm">{{ __('billing.payments.set_default') }}</button>
              </form>
            @endunless
            <form method="POST" action="{{ route('superadmin.payment-methods.toggle', $method) }}" style="display:inline;">@csrf
              <button class="btn btn-ghost btn-sm">{{ $method->is_active ? __('billing.common.deactivate') : __('billing.common.activate') }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" style="text-align:center;color:var(--c-ink-40);padding:30px;">{{ __('billing.payments.empty') }}</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.test-payment').forEach(function (btn) {
  btn.addEventListener('click', async function () {
    btn.disabled = true;
    var original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
      var res = await fetch(btn.dataset.url, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}' },
        credentials: 'same-origin'
      });
      var data = await res.json().catch(function () { return {}; });
      var ok = res.ok && data.success;
      if (window.Toast) {
        ok ? Toast.success('{{ __('billing.payments.test') }}', data.message) : Toast.error('{{ __('billing.payments.test') }}', data.message || '');
      } else {
        alert(data.message || (ok ? 'OK' : 'KO'));
      }
    } catch (e) {
      if (window.Toast) Toast.error('{{ __('billing.payments.test') }}', '{{ __('billing.payments.test_failed') }}');
    } finally {
      btn.disabled = false;
      btn.innerHTML = original;
    }
  });
});
</script>
@endpush
