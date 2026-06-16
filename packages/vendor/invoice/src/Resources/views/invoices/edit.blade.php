@extends('invoice::layouts.invoice')

@php
  $page = trans('invoice::invoices.pages.invoice_edit');
  $common = trans('invoice::invoices.common');
@endphp

@section('title', __('invoice::invoices.pages.invoice_edit.title', ['number' => $invoice->number]))

@section('breadcrumb')
  <a href="{{ route('invoices.index') }}">{{ __('invoice::invoices.invoices') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('invoice::invoices.actions.edit') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>{{ __('invoice::invoices.pages.invoice_edit.title', ['number' => $invoice->number]) }}</h1>
    <p>{{ $page['subtitle'] }}</p>
  </div>
  <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> {{ $common['back'] }}</a>
</div>

<form id="invoiceForm" action="{{ route('invoices.update', $invoice) }}" method="POST">
  @csrf
  @method('PUT')

  <div class="invoice-builder-layout">
    <div>
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-file-invoice"></i> {{ $common['general_information'] }}</h3>
        <div class="row">
          <div class="col-8">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.client') }} <span class="required">*</span></label>
              <div class="client-select-wrap">
                <div class="input-group" id="clientSearchWrap" style="display:none;">
                  <i class="fas fa-search input-icon"></i>
                  <input type="text" id="clientSearch" class="form-control" placeholder="{{ $common['client_search'] }}" autocomplete="off">
                </div>
                <input type="hidden" name="client_id" id="clientId" value="{{ $invoice->client_id }}">
                <div id="clientSuggestions" class="client-suggestions" style="display:none;"></div>
              </div>
              <div id="clientSelected" style="margin-top:8px;background:var(--c-accent-xl);border-radius:var(--r-sm);padding:10px 14px;display:flex;align-items:center;gap:10px;">
                <div class="client-avatar-sm" id="clientInitials">{{ strtoupper(substr($invoice->client->company_name ?? 'C', 0, 2)) }}</div>
                <div style="flex:1">
                  <div style="font-weight:var(--fw-medium);font-size:13px" id="clientName">{{ $invoice->client->company_name }}</div>
                  <div style="font-size:12px;color:var(--c-ink-40)" id="clientEmail">{{ $invoice->client->email }}</div>
                </div>
                <button type="button" class="btn-icon btn-sm" onclick="clearClient()" title="{{ $common['change'] }}"><i class="fas fa-times"></i></button>
              </div>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">{{ $common['reference'] }}</label>
              <input type="text" name="reference" class="form-control" value="{{ $invoice->reference }}">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.issue_date') }} <span class="required">*</span></label>
              <input type="date" name="issue_date" id="issue_date" class="form-control" value="{{ optional($invoice->issue_date)->format('Y-m-d') }}" required>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">{{ $common['payment_terms'] }}</label>
              <select name="payment_terms" id="payment_terms" class="form-control">
                @foreach($payment_terms as $days => $label)
                  <option value="{{ $days }}" {{ (int)$invoice->payment_terms === (int)$days ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.due_date') }}</label>
              <input type="date" name="due_date" id="due_date" class="form-control" value="{{ optional($invoice->due_date)->format('Y-m-d') }}">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ $common['payment_method'] }}</label>
              <select name="payment_method" class="form-control">
                <option value="">— {{ $common['select'] }} —</option>
                @foreach($payment_methods as $key => $label)
                  <option value="{{ $key }}" {{ $invoice->payment_method === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.currency') }} <span class="required">*</span></label>
              <select name="currency" id="currency" class="form-control" required>
                @foreach($currencies as $code => $cfg)
                  <option value="{{ $code }}" {{ $invoice->currency === $code ? 'selected' : '' }}>{{ __('invoice::invoices.common.currency_with_name', ['code' => $code, 'name' => $cfg['name']]) }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ $common['exchange_rate'] }}</label>
              <input type="number" name="exchange_rate" class="form-control" step="any" min="0.000001" value="{{ $invoice->exchange_rate ?? 1 }}">
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-list"></i> {{ $common['invoice_lines'] }}</h3>
        <div class="line-items-overflow">
          <table class="line-items-table">
            <thead>
              <tr>
                <th style="width:20px"></th>
                <th>{{ $common['line_description'] }}</th>
                <th style="width:90px">{{ $common['line_quantity'] }}</th>
                <th style="width:70px">{{ $common['line_unit'] }}</th>
                <th style="width:120px">{{ $common['line_unit_price_ht'] }}</th>
                <th style="width:110px">{{ $common['line_discount'] }}</th>
                <th style="width:80px">{{ $common['line_tax_rate'] }}</th>
                <th style="width:110px;text-align:right">{{ $common['line_total'] }}</th>
                <th style="width:36px"></th>
              </tr>
            </thead>
            <tbody id="lineItemsBody"></tbody>
          </table>
        </div>
        <button type="button" class="add-line-btn" onclick="InvLineItems.addLine()"><i class="fas fa-plus"></i> {{ $common['add_line'] }}</button>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> {{ $common['notes'] }}</h3>
        <div class="row">
          <div class="col-6"><div class="form-group"><label class="form-label">{{ $common['notes'] }}</label><textarea name="notes" class="form-control" rows="3">{{ $invoice->notes }}</textarea></div></div>
          <div class="col-6"><div class="form-group"><label class="form-label">{{ $common['terms'] }}</label><textarea name="terms" class="form-control" rows="3">{{ $invoice->terms }}</textarea></div></div>
          <div class="col-12"><div class="form-group"><label class="form-label">{{ $common['internal_notes'] }}</label><textarea name="internal_notes" class="form-control" rows="2">{{ $invoice->internal_notes }}</textarea></div></div>
        </div>
      </div>
    </div>

    <div>
      <div class="form-section" style="margin-bottom:16px;">
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> {{ __('invoice::invoices.actions.save') }}</button>
      </div>

      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-percent"></i> {{ $common['global_discount'] }}</h3>
        <div class="form-group">
          <label class="form-label">{{ $common['type'] }}</label>
          <select name="discount_type" id="discount_type" class="form-control">
            <option value="none" {{ $invoice->discount_type === 'none' ? 'selected' : '' }}>{{ $common['none'] }}</option>
            @foreach($discount_types as $key => $label)
              <option value="{{ $key }}" {{ $invoice->discount_type === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" id="discountValueGroup" style="{{ $invoice->discount_type === 'none' ? 'display:none;' : '' }}">
          <label class="form-label">{{ $common['value'] }}</label>
          <input type="number" name="discount_value" id="discount_value" class="form-control" value="{{ $invoice->discount_value ?? 0 }}" min="0" step="any">
        </div>
      </div>

      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-building-columns"></i> {{ $common['taxes'] }}</h3>
        <div class="form-group">
          <label class="form-label">{{ $common['vat_global'] }}</label>
          <select name="tax_rate" id="tax_rate" class="form-control">
            @foreach($tax_rates as $rate)
              <option value="{{ $rate }}" {{ (float)$invoice->tax_rate === (float)$rate ? 'selected' : '' }}>{{ $rate }} %</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">{{ $common['withholding_tax_rate'] }}</label>
          <select name="withholding_tax_rate" id="withholding_tax_rate" class="form-control">
            @foreach($withholding_rates as $r)
              <option value="{{ is_array($r) ? $r['value'] : $r }}" {{ (float)$invoice->withholding_tax_rate === (float)(is_array($r) ? $r['value'] : $r) ? 'selected' : '' }}>
                {{ is_array($r) ? $r['label'] : $r.'%' }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-calculator"></i> {{ $common['totals'] }}</h3>
        <div class="totals-panel">
          <div class="totals-row"><span class="totals-label">{{ $common['subtotal'] }}</span><span class="totals-value" id="tot-subtotal">0,00 €</span></div>
          <div class="totals-row discount" id="tot-discount-row" style="display:none;"><span class="totals-label">{{ $common['discount'] }}</span><span class="totals-value" id="tot-discount">0,00 €</span></div>
          <div class="totals-row"><span class="totals-label">{{ $common['vat'] }}</span><span class="totals-value" id="tot-tax">0,00 €</span></div>
          <div class="totals-row" id="tot-withholding-row" style="display:none;"><span class="totals-label">{{ $common['withholding'] }}</span><span class="totals-value" id="tot-withholding">0,00 €</span></div>
          <div class="totals-row grand-total"><span class="totals-label">{{ $common['total'] }}</span><span class="totals-value" id="tot-grand">0,00 €</span></div>
          <div class="withholding-info" id="withholding-info" style="display:none;">{{ $common['net_after_withholding'] }} : <strong id="tot-net">0,00 €</strong></div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
window.INVOICE_CURRENCIES = @json($currencies);
const existingItems = @json($invoice->items->map(function ($item) {
  return [
    'description' => $item->description,
    'reference' => $item->reference,
    'quantity' => (float) $item->quantity,
    'unit' => $item->unit,
    'unit_price' => (float) $item->unit_price,
    'discount_type' => $item->discount_type ?? 'none',
    'discount_value' => (float) $item->discount_value,
    'tax_rate' => (float) $item->tax_rate,
  ];
})->values());

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('discount_type').addEventListener('change', function () {
    document.getElementById('discountValueGroup').style.display = this.value !== 'none' ? 'block' : 'none';
    InvLineItems.recalc();
  });
  document.getElementById('tax_rate')?.addEventListener('change', () => InvLineItems.recalc());
  document.getElementById('withholding_tax_rate')?.addEventListener('change', () => InvLineItems.recalc());
  document.getElementById('discount_value')?.addEventListener('input', () => InvLineItems.recalc());
  document.getElementById('currency')?.addEventListener('change', () => InvLineItems.recalc());

  InvLineItems.init({
    currency: '{{ $invoice->currency }}',
    defaultTaxRate: {{ (float) $invoice->tax_rate }},
    withholdingRate: {{ (float) $invoice->withholding_tax_rate }},
    items: existingItems
  });

  InvClientSearch.init('clientSearch', 'clientId', {
    suggestionsEl: 'clientSuggestions',
    onSelect: (c) => {
      document.getElementById('clientSearchWrap').style.display = 'none';
      document.getElementById('clientSelected').style.display = 'flex';
      document.getElementById('clientInitials').textContent = (c.company_name || '?').substring(0, 2).toUpperCase();
      document.getElementById('clientName').textContent = c.company_name;
      document.getElementById('clientEmail').textContent = c.email || '';
    }
  });

  ajaxForm('invoiceForm');
});

function clearClient() {
  document.getElementById('clientId').value = '';
  document.getElementById('clientSearchWrap').style.display = '';
  document.getElementById('clientSearch').value = '';
  document.getElementById('clientSelected').style.display = 'none';
}
</script>
@endpush
