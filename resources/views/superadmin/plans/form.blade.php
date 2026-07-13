@extends('layouts.global')

@php $editing = $plan->exists; @endphp

@section('title', $editing ? __('billing.common.edit') : __('billing.plans.add'))

@section('breadcrumb')
  <a href="{{ route('superadmin.plans.index') }}">{{ __('billing.plans.title') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $editing ? $plan->name : __('billing.plans.add') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left"><h1>{{ $editing ? __('billing.common.edit') : __('billing.plans.add') }}</h1></div>
</div>

@if($errors->any())
  <div style="margin-bottom:14px;padding:12px 14px;border-radius:10px;background:#fee2e2;color:#991b1b;"><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ $editing ? route('superadmin.plans.update', $plan) : route('superadmin.plans.store') }}">
  @csrf
  @if($editing) @method('PUT') @endif

  <div class="form-section" style="margin-bottom:16px;">
    <div class="row">
      <div class="col-8"><div class="form-group">
        <label class="form-label">{{ __('billing.plans.name') }} *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required>
      </div></div>
      <div class="col-4"><div class="form-group">
        <label class="form-label">{{ __('billing.common.currency') }} *</label>
        <select name="currency" class="form-control">
          @foreach(($currencies ?? config('onboarding.currencies', ['EUR' => 'Euro (EUR)'])) as $code => $label)
            <option value="{{ $code }}" {{ old('currency', $plan->currency ?? 'EUR') === $code ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div></div>
      <div class="col-12"><div class="form-group">
        <label class="form-label">{{ __('billing.plans.description') }}</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $plan->description) }}</textarea>
      </div></div>
      <div class="col-4"><div class="form-group">
        <label class="form-label">{{ __('billing.plans.monthly_price') }}</label>
        <input type="number" step="0.01" min="0" name="monthly_price" id="monthlyPrice" class="form-control" value="{{ old('monthly_price', $plan->monthly_price ?? 0) }}">
        <span class="form-hint">{{ __('billing.plans.monthly_price_hint') }}</span>
      </div></div>
      <div class="col-4"><div class="form-group">
        <label class="form-label">{{ __('billing.plans.trial_days') }}</label>
        <input type="number" min="0" max="365" name="trial_days" class="form-control" value="{{ old('trial_days', $plan->trial_days ?? 0) }}">
        <span class="form-hint">{{ __('billing.plans.trial_days_hint') }}</span>
      </div></div>
      <div class="col-4"><div class="form-group">
        <label class="form-label">&nbsp;</label>
        <div style="display:flex;gap:18px;padding-top:8px;">
          <label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="is_free" value="1" {{ old('is_free', $plan->is_free) ? 'checked' : '' }}> {{ __('billing.plans.is_free') }}</label>
          <label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}> {{ __('billing.common.active') }}</label>
        </div>
      </div></div>
      <div class="col-12"><div class="form-group">
        <label class="form-label">{{ __('billing.plans.features') }}</label>
        <textarea name="features" class="form-control" rows="3">{{ old('features', is_array($plan->features) ? implode("\n", $plan->features) : '') }}</textarea>
      </div></div>
    </div>
  </div>

  <div class="form-section" style="margin-bottom:16px;">
    <h3 class="form-section-title"><i class="fas fa-clock"></i> {{ __('billing.plans.periods') }}</h3>
    <p style="margin:0 0 12px;color:var(--c-ink-40);font-size:12.5px;">{{ __('billing.plans.periods_hint') }}</p>
    <table class="crm-table" id="periodsTable">
      <thead><tr>
        <th style="width:140px;">{{ __('billing.plans.period_months') }}</th>
        <th style="width:140px;">{{ __('billing.common.discount') }} (%)</th>
        <th style="width:180px;">{{ __('billing.plans.final_price') }}</th>
        <th style="width:60px;"></th>
      </tr></thead>
      <tbody id="periodsBody">
        @php $rows = old('periods', $editing ? $plan->prices->map(fn($p)=>['period_months'=>$p->period_months,'discount_percent'=>$p->discount_percent,'price'=>$p->price])->all() : [['period_months'=>1,'discount_percent'=>0,'price'=>'']]); @endphp
        @foreach($rows as $i => $row)
        <tr class="period-row">
          <td><input type="number" min="1" max="120" name="periods[{{ $i }}][period_months]" class="form-control period-months" value="{{ $row['period_months'] ?? 1 }}"></td>
          <td><input type="number" min="0" max="100" step="0.01" name="periods[{{ $i }}][discount_percent]" class="form-control period-discount" value="{{ $row['discount_percent'] ?? 0 }}"></td>
          <td><input type="number" min="0" step="0.01" name="periods[{{ $i }}][price]" class="form-control period-price" value="{{ $row['price'] ?? '' }}"></td>
          <td><button type="button" class="btn-icon danger period-remove"><i class="fas fa-times"></i></button></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <button type="button" class="btn btn-secondary btn-sm" id="addPeriod" style="margin-top:10px;"><i class="fas fa-plus"></i> {{ __('billing.plans.add_period') }}</button>
  </div>

  <div class="form-section" style="display:flex;justify-content:flex-end;gap:10px;">
    <a href="{{ route('superadmin.plans.index') }}" class="btn btn-secondary">{{ __('billing.common.cancel') }}</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('billing.common.save') }}</button>
  </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  var body = document.getElementById('periodsBody');
  var monthly = document.getElementById('monthlyPrice');
  var index = body.querySelectorAll('.period-row').length;

  function compute(row) {
    var months = parseFloat(row.querySelector('.period-months')?.value) || 0;
    var discount = parseFloat(row.querySelector('.period-discount')?.value) || 0;
    var m = parseFloat(monthly.value) || 0;
    var priceInput = row.querySelector('.period-price');
    // Calcul auto uniquement si le prix n'a pas été édité manuellement.
    if (priceInput.dataset.touched === '1') return;
    var gross = m * Math.max(1, months);
    var net = gross * (1 - Math.max(0, Math.min(100, discount)) / 100);
    priceInput.value = (Math.round(net * 100) / 100).toFixed(2);
  }

  function bindRow(row) {
    row.querySelector('.period-months')?.addEventListener('input', function () { compute(row); });
    row.querySelector('.period-discount')?.addEventListener('input', function () { compute(row); });
    var price = row.querySelector('.period-price');
    price?.addEventListener('input', function () { price.dataset.touched = '1'; });
    row.querySelector('.period-remove')?.addEventListener('click', function () {
      if (body.querySelectorAll('.period-row').length > 1) row.remove();
    });
  }

  body.querySelectorAll('.period-row').forEach(bindRow);
  monthly.addEventListener('input', function () {
    body.querySelectorAll('.period-row').forEach(compute);
  });

  document.getElementById('addPeriod').addEventListener('click', function () {
    var tr = document.createElement('tr');
    tr.className = 'period-row';
    tr.innerHTML = '<td><input type="number" min="1" max="120" name="periods[' + index + '][period_months]" class="form-control period-months" value="1"></td>'
      + '<td><input type="number" min="0" max="100" step="0.01" name="periods[' + index + '][discount_percent]" class="form-control period-discount" value="0"></td>'
      + '<td><input type="number" min="0" step="0.01" name="periods[' + index + '][price]" class="form-control period-price" value=""></td>'
      + '<td><button type="button" class="btn-icon danger period-remove"><i class="fas fa-times"></i></button></td>';
    body.appendChild(tr);
    bindRow(tr);
    compute(tr);
    index++;
  });
})();
</script>
@endpush
