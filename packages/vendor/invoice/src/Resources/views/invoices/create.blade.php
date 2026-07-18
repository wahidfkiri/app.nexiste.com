@extends('invoice::layouts.invoice')

@php
  $page = trans('invoice::invoices.pages.invoice_create');
  $common = trans('invoice::invoices.common');
  $tenantCurrency = strtoupper((string) (auth()->user()->tenant->currency ?: config('invoice.default_currency', 'EUR')));
  $currencySymbol = config("invoice.currencies.{$tenantCurrency}.symbol", $tenantCurrency);
@endphp

@section('title', __('invoice::invoices.actions.create_invoice'))

@section('breadcrumb')
  <a href="{{ route('invoices.index') }}">{{ __('invoice::invoices.invoices') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $page['title'] }}</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $page['title'] }}</h1>
    <p>{{ $page['subtitle'] }}</p>
  </div>
  <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> {{ $common['back'] }}
  </a>
</div>

<form id="invoiceForm" action="{{ route('invoices.store') }}" method="POST">
  @csrf

  <div class="invoice-builder-layout">

    {{-- ── COLONNE PRINCIPALE ── --}}
    <div>

      {{-- Informations générales --}}
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-file-invoice"></i> {{ $common['general_information'] }}
          <span class="form-section-badge">{{ $page['step_1'] }}</span>
        </h3>
        <div class="row">
          <div class="col-8">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.client') }} <span class="required">*</span></label>
              <div class="client-select-wrap">
                <div class="input-group">
                  <i class="fas fa-search input-icon"></i>
                  <input type="text" id="clientSearch" class="form-control" placeholder="{{ $common['client_search'] }}" autocomplete="off">
                </div>
                <input type="hidden" name="client_id" id="clientId">
                <div id="clientSuggestions" class="client-suggestions" style="display:none;"></div>
              </div>
              <div id="clientSelected" style="display:none;margin-top:8px;background:var(--c-accent-xl);border-radius:var(--r-sm);padding:10px 14px;display:flex;align-items:center;gap:10px;">
                <div class="client-avatar-sm" id="clientInitials">?</div>
                <div style="flex:1">
                  <div style="font-weight:var(--fw-medium);font-size:13px" id="clientName"></div>
                  <div style="font-size:12px;color:var(--c-ink-40)" id="clientEmail"></div>
                </div>
                <button type="button" class="btn-icon btn-sm" onclick="clearClient()" title="{{ $common['change'] }}"><i class="fas fa-times"></i></button>
              </div>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">{{ $common['internal_reference'] }}</label>
              <input type="text" name="reference" class="form-control" placeholder="PO-2024-001">
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.issue_date') }} <span class="required">*</span></label>
              <input type="date" name="issue_date" id="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">{{ $common['payment_terms'] }}</label>
              <select name="payment_terms" id="payment_terms" class="form-control">
                @foreach($payment_terms as $days => $label)
                  <option value="{{ $days }}" {{ $days == 30 ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.due_date') }}</label>
              <input type="date" name="due_date" id="due_date" class="form-control">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ $common['payment_method'] }}</label>
              <select name="payment_method" class="form-control">
                <option value="">— {{ $common['select'] }} —</option>
                @foreach($payment_methods as $key => $label)
                  <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.currency') }}</label>
              <select name="currency" id="currencySelect" class="form-control">
                @foreach($currencies as $code => $cfg)
                  <option value="{{ $code }}" {{ $code === $tenantCurrency ? 'selected' : '' }}>{{ $code }} — {{ $cfg['name'] ?? $code }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <input type="hidden" name="exchange_rate" value="1">
        </div>
      </div>

      {{-- Lignes de facture --}}
      <div class="form-section" style="padding:0;overflow:hidden;">
        <h3 class="form-section-title" style="margin:0;padding:20px 28px 16px;border-radius:0;border-bottom:1px solid var(--c-ink-05);">
          <i class="fas fa-list"></i> {{ $common['invoice_lines'] }}
          <span class="form-section-badge">{{ $page['step_2'] }}</span>
          <button type="button" class="btn btn-ghost btn-sm" onclick="InvLineItems.addLine()" style="margin-left:auto;color:var(--c-accent);">
            <i class="fas fa-plus"></i> {{ $common['add_line'] }}
          </button>
        </h3>
        <div class="line-items-overflow">
          <table class="line-items-table">
            <thead>
              <tr>
                <th style="width:20px"></th>
                <th>{{ $common['line_description'] }}</th>
                <th style="width:90px">{{ $common['line_quantity'] }}</th>
                <th style="width:70px">{{ $common['line_unit'] }}</th>
                <th style="width:120px">{{ $common['line_unit_price_full'] }}</th>
                <th style="width:110px">{{ $common['line_discount'] }}</th>
                <th style="width:80px">{{ $common['line_tax_rate'] }}</th>
                <th style="width:110px;text-align:right">{{ $common['line_total_ttc'] }}</th>
                <th style="width:36px"></th>
              </tr>
            </thead>
            <tbody id="lineItemsBody"></tbody>
          </table>
        </div>
        <button type="button" class="add-line-btn" onclick="InvLineItems.addLine()">
          <i class="fas fa-plus"></i> {{ $common['add_line'] }}
        </button>
      </div>

      {{-- Notes --}}
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-note-sticky"></i> {{ $common['notes_and_terms'] }}
          <span class="form-section-badge">{{ $page['step_4'] }}</span>
        </h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ $common['notes'] }} <span class="hint">({{ $common['notes_visible'] }})</span></label>
              <textarea name="notes" class="form-control" rows="3" placeholder="{{ $common['notes_placeholder'] }}"></textarea>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ $common['terms_label'] }}</label>
              <textarea name="terms" class="form-control" rows="3" placeholder="{{ $common['terms_placeholder'] }}"></textarea>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ $common['internal_notes'] }} <span class="hint">({{ $common['internal_notes_hidden'] }})</span></label>
              <textarea name="internal_notes" class="form-control" rows="2" placeholder="{{ $common['internal_notes_placeholder'] }}"></textarea>
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- ── SIDEBAR ── --}}
    <div>

      {{-- Actions --}}
      <div class="form-section" style="margin-bottom:16px;">
        <div style="display:flex;flex-direction:column;gap:10px;">
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
            <i class="fas fa-check"></i> {{ $common['save_invoice'] }}
          </button>
          <a href="{{ route('invoices.index') }}" class="btn btn-secondary" style="width:100%;justify-content:center;">
            <i class="fas fa-times"></i> {{ __('invoice::invoices.actions.cancel') }}
          </a>
        </div>
      </div>

      {{-- Remise globale --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title">
          <i class="fas fa-percent"></i> {{ $common['global_discount'] }}
          <span class="form-section-badge">{{ $page['step_3'] }}</span>
        </h3>
        <div class="form-group">
          <label class="form-label">{{ $common['discount_type'] }}</label>
          <select name="discount_type" id="discount_type" class="form-control">
            <option value="none">{{ $common['none'] }}</option>
            @foreach($discount_types as $key => $label)
              <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" id="discountValueGroup" style="display:none;">
          <label class="form-label">{{ $common['discount_value'] }}</label>
          <input type="number" name="discount_value" id="discount_value" class="form-control" value="0" min="0" step="any">
        </div>
      </div>

      {{-- Taxes --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title">
          <i class="fas fa-building-columns"></i> {{ $common['taxes'] }}
        </h3>
        <div class="form-group">
          <label class="form-label">{{ $common['vat_global'] }}</label>
          <select name="tax_rate" id="tax_rate" class="form-control">
            @foreach($tax_rates as $rate)
              <option value="{{ $rate }}" {{ $rate == config('invoice.tax.default_rate', 20) ? 'selected' : '' }}>{{ $rate }} %</option>
            @endforeach
          </select>
        </div>
        @if(config('invoice.withholding_tax.enabled'))
        <div class="form-group">
          <label class="form-label">
            {{ $common['withholding_tax_rate'] }}
            <span class="hint" title="{{ $common['withholding_tax_hint'] }}">ⓘ</span>
          </label>
          <select name="withholding_tax_rate" id="withholding_tax_rate" class="form-control">
            @foreach(config('invoice.withholding_tax.rates') as $r)
              <option value="{{ $r['value'] }}">{{ $r['label'] }}</option>
            @endforeach
          </select>
        </div>
        @endif
      </div>

      {{-- Récapitulatif --}}
      <div class="form-section" style="margin-bottom:0;">
        <h3 class="form-section-title">
          <i class="fas fa-calculator"></i> {{ $common['summary'] }}
        </h3>
        <div class="totals-panel">
          <div class="totals-row">
            <span class="totals-label">{{ $common['subtotal_ht'] }}</span>
            <span class="totals-value" id="tot-subtotal">0,00 {{ $currencySymbol }}</span>
          </div>
          <div class="totals-row discount" id="tot-discount-row" style="display:none;">
            <span class="totals-label">{{ $common['discount'] }}</span>
            <span class="totals-value" id="tot-discount">— {{ $currencySymbol }}</span>
          </div>
          <div class="totals-row">
            <span class="totals-label">{{ $common['vat'] }}</span>
            <span class="totals-value" id="tot-tax">0,00 {{ $currencySymbol }}</span>
          </div>
          @if(config('invoice.withholding_tax.enabled'))
          <div class="totals-row" id="tot-withholding-row" style="display:none;">
            <span class="totals-label">{{ $common['withholding'] }}</span>
            <span class="totals-value" id="tot-withholding">0,00 {{ $currencySymbol }}</span>
          </div>
          @endif
          <div class="totals-row grand-total">
            <span class="totals-label">{{ $common['total_ttc'] }}</span>
            <span class="totals-value" id="tot-grand">0,00 {{ $currencySymbol }}</span>
          </div>
          <div class="withholding-info" id="withholding-info" style="display:none;">
            <i class="fas fa-circle-info"></i>
            {{ $common['net_after_withholding'] }} : <strong id="tot-net">0,00 {{ $currencySymbol }}</strong>
          </div>
        </div>
      </div>

    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
window.INVOICE_CURRENCIES    = @json($currencies);
window.DEFAULT_CURRENCY      = '{{ $tenantCurrency }}';
window.WITHHOLDING_COUNTRIES = @json(config('invoice.withholding_tax.countries', []));


function applyClientSelection(snapshot = {}) {
  const clientId = document.getElementById('clientId');
  const search = document.getElementById('clientSearch');
  const selected = document.getElementById('clientSelected');
  if (!clientId || !search || !selected) return;

  const id = snapshot.id || snapshot.client_id || '';
  if (!id) {
    clearClient(false);
    if (snapshot.search) search.value = snapshot.search;
    return;
  }

  clientId.value = id;
  search.style.display = 'none';
  search.value = snapshot.search || snapshot.company_name || '';
  selected.style.display = 'flex';
  document.getElementById('clientInitials').textContent = (snapshot.company_name || '?').substring(0, 2).toUpperCase();
  document.getElementById('clientName').textContent = snapshot.company_name || '';
  document.getElementById('clientEmail').textContent = snapshot.email || '';
}

function clearClient(triggerChange = true) {
  const clientId = document.getElementById('clientId');
  const search = document.getElementById('clientSearch');
  const selected = document.getElementById('clientSelected');
  if (clientId) clientId.value = '';
  if (search) {
    search.style.display = '';
    search.value = '';
  }
  if (selected) selected.style.display = 'none';

  if (triggerChange && clientId) {
    clientId.dispatchEvent(new Event('change', { bubbles: true }));
  }
}

window.clearClient = clearClient;

document.addEventListener('DOMContentLoaded', () => {
  const issueDate = document.getElementById('issue_date');
  const terms = document.getElementById('payment_terms');
  const dueDate = document.getElementById('due_date');
  const clientIdInput = document.getElementById('clientId');

  function calcDue() {
    if (!issueDate.value || !terms.value) return;
    const d = new Date(issueDate.value);
    d.setDate(d.getDate() + parseInt(terms.value, 10));
    dueDate.value = d.toISOString().split('T')[0];
  }

  issueDate.addEventListener('change', calcDue);
  terms.addEventListener('change', calcDue);
  calcDue();

  document.getElementById('discount_type').addEventListener('change', function() {
    document.getElementById('discountValueGroup').style.display = this.value !== 'none' ? 'block' : 'none';
    InvLineItems.recalc();
  });

  document.getElementById('tax_rate')?.addEventListener('change', () => InvLineItems.recalc());
  document.getElementById('withholding_tax_rate')?.addEventListener('change', () => InvLineItems.recalc());
  document.getElementById('discount_value')?.addEventListener('input', () => InvLineItems.recalc());
  InvLineItems.init({ currency: '{{ $tenantCurrency }}', defaultTaxRate: {{ config('invoice.tax.default_rate', 20) }} });

  const currencySelect = document.getElementById('currencySelect');
  if (currencySelect) {
    currencySelect.addEventListener('change', () => InvLineItems.setCurrency(currencySelect.value));
  }

  InvClientSearch.init('clientSearch', 'clientId', {
    suggestionsEl: 'clientSuggestions',
    onSelect: (c) => {
      applyClientSelection({
        id: c.id,
        company_name: c.company_name,
        email: c.email || '',
        search: c.company_name || '',
      });
      // Devise préférée du client -> pré-remplit la devise du document.
      if (c.currency && currencySelect) {
        currencySelect.value = String(c.currency).toUpperCase();
        InvLineItems.setCurrency(currencySelect.value);
      }
      clientIdInput?.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });

  window.CrmDrafts?.attach('invoiceForm', {
    type: 'invoice',
    label: 'facture',
    collect: (data) => {
      data.__draft_line_items = InvLineItems.getData();
      data.__draft_client = {
        id: document.getElementById('clientId')?.value || '',
        company_name: document.getElementById('clientName')?.textContent || '',
        email: document.getElementById('clientEmail')?.textContent || '',
        search: document.getElementById('clientSearch')?.value || '',
      };
      return data;
    },
    apply: (data) => {
      if (Array.isArray(data.__draft_line_items)) {
        InvLineItems.load(data.__draft_line_items);
      }

      if (data.__draft_client?.id) {
        applyClientSelection(data.__draft_client);
      } else {
        clearClient(false);
        if (data.__draft_client?.search) {
          document.getElementById('clientSearch').value = data.__draft_client.search;
        }
      }

      if (Object.prototype.hasOwnProperty.call(data, 'due_date')) {
        document.getElementById('due_date').value = data.due_date || '';
      }

      InvLineItems.recalc();
    },
  });

  ajaxForm('invoiceForm');
});
</script>
@endpush
