@extends('invoice::layouts.invoice')

@php
  $settingsPage = trans('invoice::invoices.pages.settings_page');
  $taxRateLabels = [
    '0' => $settingsPage['vat_exempt'],
    '20' => $settingsPage['normal_rate'],
    '10' => $settingsPage['intermediate_rate'],
    '5.5' => $settingsPage['reduced_rate'],
  ];
@endphp

@section('title', __('invoice::invoices.pages.settings_page.title'))

@section('breadcrumb')
  <span>{{ $settingsPage['breadcrumb_configuration'] }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $settingsPage['title'] }}</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $settingsPage['title'] }}</h1>
    <p>{{ $settingsPage['subtitle'] }}</p>
  </div>
</div>

{{-- Tabs --}}
<div style="display:flex;gap:4px;margin-bottom:24px;background:var(--surface-0);border:1px solid var(--c-ink-05);border-radius:var(--r-lg);padding:6px;width:fit-content;box-shadow:var(--shadow-xs);">
  @foreach([
    ['id'=>'currency',    'icon'=>'fa-coins',               'label'=>__('invoice::invoices.settings_tabs.currency')],
    ['id'=>'numbering',   'icon'=>'fa-hashtag',             'label'=>__('invoice::invoices.settings_tabs.numbering')],
    ['id'=>'taxes',       'icon'=>'fa-percent',              'label'=>__('invoice::invoices.settings_tabs.taxes')],
    ['id'=>'withholding', 'icon'=>'fa-building-columns',     'label'=>__('invoice::invoices.settings_tabs.withholding')],
    ['id'=>'signature',   'icon'=>'fa-signature',            'label'=>__('invoice::invoices.settings_tabs.signature')],
    ['id'=>'accounting',  'icon'=>'fa-book',                 'label'=>__('invoice::invoices.settings_tabs.accounting')],
    ['id'=>'reminders',   'icon'=>'fa-bell',                 'label'=>__('invoice::invoices.settings_tabs.reminders')],
    ['id'=>'templates',   'icon'=>'fa-palette',              'label'=>__('invoice::invoices.settings_tabs.templates')],
  ] as $tab)
  <button class="tab-btn {{ $loop->first ? 'active' : '' }}" onclick="switchTab('{{ $tab['id'] }}')" id="tab-btn-{{ $tab['id'] }}"
    style="padding:8px 14px;border:none;background:{{ $loop->first ? 'var(--c-accent)' : 'transparent' }};color:{{ $loop->first ? '#fff' : 'var(--c-ink-60)' }};border-radius:var(--r-sm);font-size:13px;font-weight:var(--fw-medium);cursor:pointer;display:flex;align-items:center;gap:7px;transition:all var(--dur-fast);white-space:nowrap;">
    <i class="fas {{ $tab['icon'] }}" style="font-size:12px;"></i> {{ $tab['label'] }}
  </button>
  @endforeach
</div>

<form id="settingsForm" action="{{ route('invoices.settings.update') }}" method="POST" enctype="multipart/form-data">
@csrf
@method('PUT')

{{-- --- DEVISE --- --}}
<div id="tab-currency" class="tab-panel">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-coins"></i> {{ $settingsPage['currency_configuration'] }}
    </h3>

    <div style="background:var(--c-accent-xl);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:12px 16px;margin-bottom:16px;font-size:13px;color:var(--c-ink-60);">
      <i class="fas fa-circle-info" style="color:var(--c-accent);"></i>
      {{ $settingsPage['currency_sync_notice'] }}
    </div>

    <div class="row">
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['currency_label'] }} <span class="required">*</span></label>
          <select name="tenant_currency" class="form-control">
            @foreach(($currencies ?? []) as $code => $label)
              <option value="{{ $code }}" {{ strtoupper($currentCurrency ?? 'EUR') === strtoupper($code) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          <span class="form-hint">{{ $settingsPage['currency_hint'] }}</span>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- --- NUMEROTATION --- --}}
<div id="tab-numbering" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-hashtag"></i> {{ $settingsPage['numbering_configuration'] }}
    </h3>
    <div class="row">
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['invoice_prefix'] }} <span class="required">*</span></label>
          <input type="text" name="invoice_prefix" class="form-control" value="{{ config('invoice.numbering.invoice_prefix', 'FAC') }}" placeholder="FAC">
          <span class="form-hint">{{ $settingsPage['invoice_prefix_hint'] }}</span>
        </div>
      </div>
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['quote_prefix'] }} <span class="required">*</span></label>
          <input type="text" name="quote_prefix" class="form-control" value="{{ config('invoice.numbering.quote_prefix', 'DEV') }}" placeholder="DEV">
          <span class="form-hint">{{ $settingsPage['quote_prefix_hint'] }}</span>
        </div>
      </div>
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['separator'] }}</label>
          <select name="numbering_separator" class="form-control">
            <option value="-" selected>{{ $settingsPage['separator_dash'] }}</option>
            <option value="/">{{ $settingsPage['separator_slash'] }}</option>
            <option value=".">{{ $settingsPage['separator_dot'] }}</option>
          </select>
        </div>
      </div>
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['digits_count'] }}</label>
          <select name="numbering_digits" class="form-control">
            @foreach([3,4,5,6] as $d)
              <option value="{{ $d }}" {{ $d == 4 ? 'selected' : '' }}>{{ $d }} ({{ str_pad('1', $d, '0', STR_PAD_LEFT) }})</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['yearly_reset'] }}</label>
          <div style="padding-top:8px;">
            <label class="toggle-switch">
              <input type="checkbox" name="reset_yearly" checked>
              <span class="toggle-slider"></span>
            </label>
            <span style="margin-left:10px;font-size:13px;color:var(--c-ink-60);">{{ $settingsPage['enable'] }}</span>
          </div>
        </div>
      </div>
    </div>
    <div style="background:var(--c-accent-xl);border-radius:var(--r-sm);padding:12px 16px;font-size:13px;color:var(--c-ink-60);">
      <i class="fas fa-eye" style="color:var(--c-accent);"></i>
      {{ $settingsPage['preview'] }} : <strong style="font-family:'DM Sans', sans-serif;">FAC-{{ date('Y') }}-0001</strong>
    </div>
  </div>
</div>

{{-- ── TVA ── --}}
<div id="tab-taxes" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-percent"></i> {{ $settingsPage['vat_rates'] }}
    </h3>
    <div style="margin-bottom:16px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <label class="toggle-switch">
          <input type="checkbox" name="tax_enabled" {{ config('invoice.tax.enabled') ? 'checked' : '' }}>
          <span class="toggle-slider"></span>
        </label>
        <span style="font-size:13.5px;font-weight:var(--fw-medium);">{{ $settingsPage['enable_vat'] }}</span>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">{{ $settingsPage['default_vat_rate'] }}</label>
      <select name="default_tax_rate" class="form-control" style="max-width:200px;">
        @foreach(config('invoice.tax.rates', [0,5,10,20]) as $rate)
          <option value="{{ $rate }}" {{ $rate == config('invoice.tax.default_rate', 20) ? 'selected' : '' }}>{{ $rate }} %</option>
        @endforeach
      </select>
    </div>

    <div style="margin-top:20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <label class="form-label" style="margin:0;">{{ $settingsPage['available_rates'] }}</label>
        <button type="button" class="btn btn-sm btn-secondary" onclick="addTaxRate()">
          <i class="fas fa-plus"></i> {{ $settingsPage['add_rate'] }}
        </button>
      </div>
      <div id="taxRatesList">
        @foreach(config('invoice.tax.rates', [0,5,10,20]) as $rate)
        <div class="tax-rate-item" id="tax-{{ $rate }}">
          <div class="tax-badge">{{ $rate }} %</div>
          <span style="flex:1;font-size:13px;color:var(--c-ink-60);">
            {{ $taxRateLabels[(string) $rate] ?? $settingsPage['custom_rate'] }}
          </span>
          <input type="hidden" name="tax_rates[]" value="{{ $rate }}">
          @if($rate !== 0 && $rate !== 20)
          <button type="button" class="btn-icon danger btn-sm" onclick="removeTaxRate({{ $rate }})">
            <i class="fas fa-times"></i>
          </button>
          @endif
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

{{-- ── RETENUE ── --}}
<div id="tab-withholding" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-building-columns"></i> {{ __('invoice::invoices.settings_tabs.withholding') }}
    </h3>

    <div style="background:var(--c-warning-lt);border:1px solid #fcd34d;border-radius:var(--r-sm);padding:14px 16px;margin-bottom:20px;font-size:13px;color:#92400e;">
      <i class="fas fa-triangle-exclamation"></i>
      {{ $settingsPage['withholding_notice'] }}
    </div>

    <div class="settings-row">
      <div class="settings-row-info">
        <h4>{{ $settingsPage['enable_withholding'] }}</h4>
        <p>{{ $settingsPage['enable_withholding_help'] }}</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="withholding_enabled" {{ config('invoice.withholding_tax.enabled') ? 'checked' : '' }}>
        <span class="toggle-slider"></span>
      </label>
    </div>

    <div class="form-group" style="margin-top:16px;">
      <label class="form-label">{{ $settingsPage['default_rate'] }}</label>
      <select name="default_withholding_rate" class="form-control" style="max-width:200px;">
        @foreach(config('invoice.withholding_tax.rates', []) as $r)
          <option value="{{ $r['value'] }}" {{ $r['value'] == config('invoice.withholding_tax.default_rate', 0) ? 'selected' : '' }}>{{ $r['label'] }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">{{ $settingsPage['countries'] }}</label>
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;">
        @foreach(config('invoice.withholding_tax.countries', []) as $country)
        <span style="background:var(--c-ink-02);border:1px solid var(--c-ink-05);border-radius:var(--r-full);padding:4px 12px;font-size:12px;font-weight:var(--fw-medium);">
          {{ $country }}
        </span>
        @endforeach
      </div>
      <span class="form-hint">{{ $settingsPage['countries_hint'] }}</span>
    </div>

    <div style="margin-top:20px;">
      <label class="form-label">{{ $settingsPage['available_rates'] }}</label>
      @foreach(config('invoice.withholding_tax.rates', []) as $r)
      @if($r['value'] > 0)
      <div class="tax-rate-item">
        <div class="tax-badge" style="background:#fef3c7;color:#92400e;">{{ $r['label'] }}</div>
        <span style="flex:1;font-size:13px;color:var(--c-ink-60);">{{ __('invoice::invoices.pages.settings_page.withholding_rate_label', ['rate' => $r['label']]) }}</span>
        <input type="hidden" name="withholding_rates[]" value="{{ $r['value'] }}">
      </div>
      @endif
      @endforeach
    </div>
  </div>
</div>

{{-- ── SIGNATURE ÉLECTRONIQUE ── --}}
<div id="tab-signature" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-signature"></i> {{ __('invoice::invoices.settings_tabs.signature') }}
    </h3>

    <div class="settings-row">
      <div class="settings-row-info">
        <h4>{{ $settingsPage['enable_signature'] }}</h4>
        <p>{{ $settingsPage['enable_signature_help'] }}</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="signature_enabled" id="signatureEnabled" {{ ($settings['signature_enabled'] ?? false) ? 'checked' : '' }}>
        <span class="toggle-slider"></span>
      </label>
    </div>

    <div id="signatureConfig" style="margin-top:24px;{{ ($settings['signature_enabled'] ?? false) ? '' : 'display:none;' }}">
      <div class="row">
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">{{ $settingsPage['your_signature'] }}</label>
            <div class="signature-pad-wrap">
              <canvas id="signaturePad" width="400" height="160"></canvas>
              <div class="signature-pad-controls">
                <div class="signature-pad-colors" style="display:flex;gap:8px;align-items:center;">
                  <span style="font-size:12px;color:var(--c-ink-40);font-weight:700;text-transform:uppercase;letter-spacing:.04em;">{{ $settingsPage['signature_color'] }}</span>
                  <button type="button" class="btn btn-ghost btn-sm sig-color-btn active" data-color="#111827" onclick="setSignatureColor('#111827', this)">
                    <span style="display:inline-flex;align-items:center;gap:8px;">
                      <span style="width:10px;height:10px;border-radius:999px;background:#111827;display:inline-block;"></span>
                      {{ $settingsPage['black'] }}
                    </span>
                  </button>
                  <button type="button" class="btn btn-ghost btn-sm sig-color-btn" data-color="#1d4ed8" onclick="setSignatureColor('#1d4ed8', this)">
                    <span style="display:inline-flex;align-items:center;gap:8px;">
                      <span style="width:10px;height:10px;border-radius:999px;background:#1d4ed8;display:inline-block;"></span>
                      {{ $settingsPage['blue'] }}
                    </span>
                  </button>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="clearSignature()">
                  <i class="fas fa-eraser"></i> {{ $settingsPage['clear'] }}
                </button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="saveSignature()">
                  <i class="fas fa-save"></i> {{ $settingsPage['save_signature'] }}
                </button>
              </div>
            </div>
            <input type="hidden" name="signature_data" id="signature_data">
            <span class="form-hint">{{ $settingsPage['signature_hint'] }}</span>
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">{{ $settingsPage['current_signature'] }}</label>
            @if($settings['signature_data'] ?? false)
              <img src="{{ $settings['signature_data'] }}" alt="{{ $settingsPage['current_signature'] }}" style="max-width:100%;border:1px solid var(--c-ink-05);border-radius:var(--r-md);padding:10px;background:var(--surface-0);">
            @else
              <div style="height:160px;background:var(--surface-1);border:1px dashed var(--c-ink-10);border-radius:var(--r-md);display:flex;align-items:center;justify-content:center;color:var(--c-ink-20);">
                <span style="font-size:13px;">{{ $settingsPage['no_signature'] }}</span>
              </div>
            @endif
          </div>
          <div class="form-group">
            <label class="form-label">{{ $settingsPage['signer_name'] }}</label>
            <input type="text" name="signer_name" class="form-control" placeholder="{{ $settingsPage['signer_name_placeholder'] }}" value="{{ $settings['signer_name'] ?? auth()->user()->name }}">
          </div>
          <div class="form-group">
            <label class="form-label">{{ $settingsPage['signer_title'] }}</label>
            <input type="text" name="signer_title" class="form-control" placeholder="{{ $settingsPage['signer_title_placeholder'] }}" value="{{ $settings['signer_title'] ?? '' }}">
          </div>
        </div>
      </div>

      <div class="settings-row">
        <div class="settings-row-info">
          <h4>{{ $settingsPage['show_on_invoices'] }}</h4>
          <p>{{ $settingsPage['show_on_invoices_help'] }}</p>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="signature_on_invoice" {{ ($settings['signature_on_invoice'] ?? true) ? 'checked' : '' }}>
          <span class="toggle-slider"></span>
        </label>
      </div>
      <div class="settings-row">
        <div class="settings-row-info">
          <h4>{{ $settingsPage['show_on_quotes'] }}</h4>
          <p>{{ $settingsPage['show_on_quotes_help'] }}</p>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" name="signature_on_quote" {{ ($settings['signature_on_quote'] ?? true) ? 'checked' : '' }}>
          <span class="toggle-slider"></span>
        </label>
      </div>
    </div>
  </div>
</div>

{{-- ── COMPTABILITÉ ── --}}
<div id="tab-accounting" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-book"></i> {{ $settingsPage['accounting_settings'] }}
    </h3>

    <div class="row">
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['fiscal_year_start'] }}</label>
          <select name="fiscal_year_start" class="form-control">
            @foreach($settingsPage['months'] as $i => $m)
              <option value="{{ $i + 1 }}" {{ ($i + 1) == 1 ? 'selected' : '' }}>{{ $m }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="col-6">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['accounting_method'] }}</label>
          <select name="accounting_method" class="form-control">
            <option value="accrual">{{ $settingsPage['accrual_accounting'] }}</option>
            <option value="cash">{{ $settingsPage['cash_accounting'] }}</option>
          </select>
        </div>
      </div>
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['default_payment_terms'] }}</label>
          <select name="default_payment_terms" class="form-control">
            @foreach(config('invoice.payment_terms') as $days => $label)
              <option value="{{ $days }}" {{ $days == 30 ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    <h4 style="font-size:14px;font-weight:var(--fw-semi);color:var(--c-ink);margin:20px 0 12px;padding-top:16px;border-top:1px solid var(--c-ink-05);">
      {{ $settingsPage['accounting_accounts'] }}
    </h4>
    <div class="row">
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['account_customers'] }}</label>
          <input type="text" name="account_customers" class="form-control font-mono" placeholder="411000" value="{{ $settings['account_customers'] ?? '411000' }}">
        </div>
      </div>
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['account_sales'] }}</label>
          <input type="text" name="account_sales" class="form-control font-mono" placeholder="706000" value="{{ $settings['account_sales'] ?? '706000' }}">
        </div>
      </div>
      <div class="col-4">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['account_vat'] }}</label>
          <input type="text" name="account_vat" class="form-control font-mono" placeholder="445710" value="{{ $settings['account_vat'] ?? '445710' }}">
        </div>
      </div>
    </div>

    <div class="settings-row">
      <div class="settings-row-info">
        <h4>{{ $settingsPage['auto_accounting_export'] }}</h4>
        <p>{{ $settingsPage['auto_accounting_export_help'] }}</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="auto_accounting_export">
        <span class="toggle-slider"></span>
      </label>
    </div>
  </div>
</div>

{{-- ── RAPPELS ── --}}
<div id="tab-reminders" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-bell"></i> {{ $settingsPage['automatic_reminders'] }}
    </h3>

    <div class="settings-row">
      <div class="settings-row-info">
        <h4>{{ $settingsPage['enable_reminders'] }}</h4>
        <p>{{ $settingsPage['enable_reminders_help'] }}</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="reminders_enabled" {{ config('invoice.reminders.enabled') ? 'checked' : '' }}>
        <span class="toggle-slider"></span>
      </label>
    </div>

    <div class="form-group" style="margin-top:16px;">
      <label class="form-label">{{ $settingsPage['max_reminders'] }}</label>
      <select name="max_reminders" class="form-control" style="max-width:200px;">
        @foreach([1,2,3,4,5] as $n)
          <option value="{{ $n }}" {{ $n == config('invoice.reminders.max_reminders', 3) ? 'selected' : '' }}>{{ __('invoice::invoices.pages.settings_page.reminder_count', ['count' => $n]) }}</option>
        @endforeach
      </select>
    </div>

    <div style="margin-top:16px;">
      <label class="form-label">{{ $settingsPage['reminders_before_due'] }}</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
        @foreach(config('invoice.reminders.days_before', [7,3,1]) as $d)
        <div style="display:flex;align-items:center;gap:8px;background:var(--c-ink-02);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:8px 12px;">
          <i class="fas fa-clock" style="color:var(--c-warning);"></i>
          <span style="font-size:13px;font-weight:var(--fw-medium);">J-{{ $d }}</span>
          <input type="hidden" name="reminder_days_before[]" value="{{ $d }}">
        </div>
        @endforeach
      </div>
    </div>

    <div style="margin-top:12px;">
      <label class="form-label">{{ $settingsPage['reminders_after_due'] }}</label>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
        @foreach(config('invoice.reminders.days_after', [1,7,14,30]) as $d)
        <div style="display:flex;align-items:center;gap:8px;background:var(--c-danger-lt);border:1px solid var(--c-danger-lt);border-radius:var(--r-sm);padding:8px 12px;">
          <i class="fas fa-triangle-exclamation" style="color:var(--c-danger);"></i>
          <span style="font-size:13px;font-weight:var(--fw-medium);">J+{{ $d }}</span>
          <input type="hidden" name="reminder_days_after[]" value="{{ $d }}">
        </div>
        @endforeach
      </div>
    </div>

    <div class="form-group" style="margin-top:20px;">
      <label class="form-label">{{ $settingsPage['reminder_message'] }}</label>
      <textarea name="reminder_message" class="form-control" rows="4" placeholder="{{ $settingsPage['reminder_message_placeholder'] }}">{{ $settings['reminder_message'] ?? '' }}</textarea>
      <span class="form-hint">{{ $settingsPage['reminder_variables'] }}</span>
    </div>
  </div>
</div>

{{-- ── TEMPLATES PDF ── --}}
<div id="tab-templates" class="tab-panel" style="display:none;">
  <div class="form-section">
    <h3 class="form-section-title">
      <i class="fas fa-palette"></i> {{ $settingsPage['pdf_customization'] }}
    </h3>
    <div class="row">
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['invoice_pdf_template'] }}</label>
          @php($tpl = $settings['pdf_invoice_template'] ?? 'classic')
          <select name="pdf_invoice_template" class="form-control">
            <option value="classic" {{ $tpl === 'classic' ? 'selected' : '' }}>{{ $settingsPage['template_classic'] }}</option>
            <option value="modern"  {{ $tpl === 'modern' ? 'selected' : '' }}>{{ $settingsPage['template_modern'] }}</option>
            <option value="minimal" {{ $tpl === 'minimal' ? 'selected' : '' }}>{{ $settingsPage['template_minimal'] }}</option>
          </select>
          <span class="form-hint">{{ $settingsPage['invoice_pdf_hint'] }}</span>
        </div>
      </div>
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['quote_pdf_template'] }}</label>
          @php($tplq = $settings['pdf_quote_template'] ?? 'classic')
          <select name="pdf_quote_template" class="form-control">
            <option value="classic" {{ $tplq === 'classic' ? 'selected' : '' }}>{{ $settingsPage['template_classic'] }}</option>
            <option value="modern"  {{ $tplq === 'modern' ? 'selected' : '' }}>{{ $settingsPage['template_modern'] }}</option>
            <option value="minimal" {{ $tplq === 'minimal' ? 'selected' : '' }}>{{ $settingsPage['template_minimal'] }}</option>
          </select>
          <span class="form-hint">{{ $settingsPage['quote_pdf_hint'] }}</span>
        </div>
      </div>
      <div class="col-12">
        <div class="template-preview-panel">
          <div class="template-preview-head">
            <h4><i class="fas fa-eye"></i> {{ $settingsPage['template_preview'] }}</h4>
            <p>{{ $settingsPage['template_preview_help'] }}</p>
          </div>

          <div class="template-preview-block">
            <div class="template-preview-title">{{ __('invoice::invoices.common.invoice') }}</div>
            <div class="template-preview-grid" id="invoiceTemplatePreview">
              <button type="button" class="template-card" data-template-value="classic" data-template-select="pdf_invoice_template">
                <div class="template-card-mini classic">
                  <div class="mini-band"></div>
                  <div class="mini-lines"></div>
                </div>
                <div class="template-card-meta">
                  <strong>{{ $settingsPage['template_classic_short'] }}</strong>
                  <span>{{ $settingsPage['template_classic_desc'] }}</span>
                </div>
              </button>
              <button type="button" class="template-card" data-template-value="modern" data-template-select="pdf_invoice_template">
                <div class="template-card-mini modern">
                  <div class="mini-topline"></div>
                  <div class="mini-block"></div>
                </div>
                <div class="template-card-meta">
                  <strong>{{ $settingsPage['template_modern_short'] }}</strong>
                  <span>{{ $settingsPage['template_modern_desc'] }}</span>
                </div>
              </button>
              <button type="button" class="template-card" data-template-value="minimal" data-template-select="pdf_invoice_template">
                <div class="template-card-mini minimal">
                  <div class="mini-thin"></div>
                  <div class="mini-lines"></div>
                </div>
                <div class="template-card-meta">
                  <strong>{{ $settingsPage['template_minimal_short'] }}</strong>
                  <span>{{ $settingsPage['template_minimal_desc'] }}</span>
                </div>
              </button>
            </div>
          </div>

          <div class="template-preview-block">
            <div class="template-preview-title">{{ __('invoice::invoices.common.quote') }}</div>
            <div class="template-preview-grid" id="quoteTemplatePreview">
              <button type="button" class="template-card" data-template-value="classic" data-template-select="pdf_quote_template">
                <div class="template-card-mini classic">
                  <div class="mini-band"></div>
                  <div class="mini-lines"></div>
                </div>
                <div class="template-card-meta">
                  <strong>{{ $settingsPage['template_classic_short'] }}</strong>
                  <span>{{ $settingsPage['template_classic_desc'] }}</span>
                </div>
              </button>
              <button type="button" class="template-card" data-template-value="modern" data-template-select="pdf_quote_template">
                <div class="template-card-mini modern">
                  <div class="mini-topline"></div>
                  <div class="mini-block"></div>
                </div>
                <div class="template-card-meta">
                  <strong>{{ $settingsPage['template_modern_short'] }}</strong>
                  <span>{{ $settingsPage['template_modern_desc'] }}</span>
                </div>
              </button>
              <button type="button" class="template-card" data-template-value="minimal" data-template-select="pdf_quote_template">
                <div class="template-card-mini minimal">
                  <div class="mini-thin"></div>
                  <div class="mini-lines"></div>
                </div>
                <div class="template-card-meta">
                  <strong>{{ $settingsPage['template_minimal_short'] }}</strong>
                  <span>{{ $settingsPage['template_minimal_desc'] }}</span>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['primary_color'] }}</label>
          @php($pdfPrimary = $settings['pdf_primary_color'] ?? '#2563eb')
          <div style="display:flex;gap:8px;align-items:center;">
            <input type="color" name="pdf_primary_color" value="{{ $pdfPrimary }}" style="width:44px;height:38px;border-radius:var(--r-sm);border:1.5px solid var(--c-ink-10);cursor:pointer;padding:2px;">
            <input type="text" name="pdf_primary_color_hex" class="form-control font-mono" value="{{ $pdfPrimary }}" style="max-width:100px;" placeholder="#2563eb">
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['paper_format'] }}</label>
          @php($pdfPaper = $settings['pdf_paper'] ?? 'A4')
          <select name="pdf_paper" class="form-control">
            <option value="A4" {{ $pdfPaper === 'A4' ? 'selected' : '' }}>A4 (210 x 297 mm)</option>
            <option value="Letter" {{ $pdfPaper === 'Letter' ? 'selected' : '' }}>Letter (216 x 279 mm)</option>
            <option value="Legal" {{ $pdfPaper === 'Legal' ? 'selected' : '' }}>Legal (216 x 356 mm)</option>
          </select>
        </div>
      </div>
      <div class="col-12">
        <div class="form-group">
          <label class="form-label">{{ $settingsPage['legal_mentions'] }}</label>
          <textarea name="pdf_legal_mentions" class="form-control" rows="3" placeholder="{{ $settingsPage['legal_mentions_placeholder'] }}">{{ $settings['pdf_legal_mentions'] ?? '' }}</textarea>
        </div>
      </div>
    </div>

    <div class="settings-row">
      <div class="settings-row-info">
        <h4>{{ $settingsPage['draft_watermark'] }}</h4>
        <p>{{ $settingsPage['draft_watermark_help'] }}</p>
      </div>
      <label class="toggle-switch">
      <input type="checkbox" name="pdf_watermark_draft" {{ ($settings['pdf_watermark_draft'] ?? true) ? 'checked' : '' }}>
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div class="settings-row">
      <div class="settings-row-info">
        <h4>{{ $settingsPage['show_bank_details'] }}</h4>
        <p>{{ $settingsPage['show_bank_details_help'] }}</p>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" name="pdf_show_bank" {{ config('invoice.pdf.show_bank') ? 'checked' : '' }}>
        <span class="toggle-slider"></span>
      </label>
    </div>
  </div>
</div>

{{-- --- IDENTITE PDF & THEMES (nouveau) --- --}}
<div class="form-section" style="margin-top:16px;">
  <h3 class="form-section-title">
    <i class="fas fa-wand-magic-sparkles"></i> {{ $settingsPage['pdf_identity'] }}
  </h3>
  <div class="row">
    <div class="col-6">
      <div class="form-group">
        <label class="form-label">{{ $settingsPage['pdf_theme'] }}</label>
        <select name="pdf_theme" class="form-control">
          <option value="ocean" {{ ($settings['pdf_theme'] ?? 'ocean') === 'ocean' ? 'selected' : '' }}>{{ $settingsPage['theme_ocean'] }}</option>
          <option value="emerald" {{ ($settings['pdf_theme'] ?? '') === 'emerald' ? 'selected' : '' }}>{{ $settingsPage['theme_emerald'] }}</option>
          <option value="sunset" {{ ($settings['pdf_theme'] ?? '') === 'sunset' ? 'selected' : '' }}>{{ $settingsPage['theme_sunset'] }}</option>
          <option value="mono" {{ ($settings['pdf_theme'] ?? '') === 'mono' ? 'selected' : '' }}>{{ $settingsPage['theme_mono'] }}</option>
        </select>
      </div>
    </div>
    <div class="col-6">
      <div class="form-group">
        <label class="form-label">{{ $settingsPage['pdf_logo'] }}</label>
        <input type="file" name="pdf_logo" class="form-control" accept=".png,.jpg,.jpeg,.svg,.webp">
        <span class="form-hint">{{ $settingsPage['pdf_logo_hint'] }}</span>
      </div>
    </div>
    @if(!empty($settings['pdf_logo']))
    <div class="col-12">
      <div class="form-group">
        <label class="form-label">{{ $settingsPage['current_logo'] }}</label>
        <div style="display:flex;align-items:center;gap:14px;padding:10px;border:1px solid var(--c-ink-05);border-radius:var(--r-md);background:var(--surface-1);">
          <img src="{{ asset('storage/' . ltrim($settings['pdf_logo'], '/')) }}" alt="{{ $settingsPage['pdf_logo'] }}" style="max-height:56px;max-width:220px;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--c-ink-60);cursor:pointer;">
            <input type="checkbox" name="pdf_logo_remove" value="1"> {{ $settingsPage['remove_logo'] }}
          </label>
        </div>
      </div>
    </div>
    @endif
    <div class="col-12">
      <div class="form-group">
        <label class="form-label">{{ $settingsPage['pdf_footer_text'] }}</label>
        <input type="text" name="pdf_footer" class="form-control" placeholder="{{ __('invoice::invoices.pages.settings_page.pdf_footer_placeholder', ['app' => config('app.name')]) }}" value="{{ $settings['pdf_footer'] ?? '' }}">
      </div>
    </div>
  </div>

  <div class="settings-row">
    <div class="settings-row-info">
      <h4>{{ $settingsPage['show_logo_header'] }}</h4>
      <p>{{ $settingsPage['show_logo_header_help'] }}</p>
    </div>
    <label class="toggle-switch">
      <input type="checkbox" name="pdf_show_logo" {{ ($settings['pdf_show_logo'] ?? true) ? 'checked' : '' }}>
      <span class="toggle-slider"></span>
    </label>
  </div>

  <div class="settings-row">
    <div class="settings-row-info">
      <h4>{{ $settingsPage['show_pdf_footer'] }}</h4>
      <p>{{ $settingsPage['show_pdf_footer_help'] }}</p>
    </div>
    <label class="toggle-switch">
      <input type="checkbox" name="pdf_show_footer" {{ ($settings['pdf_show_footer'] ?? true) ? 'checked' : '' }}>
      <span class="toggle-slider"></span>
    </label>
  </div>
</div>

{{-- Save button --}}
<div class="form-section" style="margin-top:0;display:flex;justify-content:flex-end;gap:10px;padding:20px 28px;">
  <button type="reset" class="btn btn-secondary">
    <i class="fas fa-rotate-left"></i> {{ __('invoice::invoices.actions.cancel') }}
  </button>
  <button type="submit" class="btn btn-primary" id="saveBtn">
    <i class="fas fa-check"></i> {{ $settingsPage['save_settings'] }}
  </button>
</div>

</form>

@endsection

@push('styles')
<style>
.template-preview-panel{
  border:1px solid var(--c-ink-05);
  border-radius:var(--r-lg);
  background:linear-gradient(180deg,#fff 0%,#f8fafc 100%);
  padding:16px;
}
.template-preview-head{margin-bottom:14px}
.template-preview-head h4{
  margin:0 0 4px;
  font-size:14px;
  font-weight:var(--fw-semi);
  color:var(--c-ink);
  display:flex;
  gap:8px;
  align-items:center;
}
.template-preview-head h4 i{color:var(--c-accent)}
.template-preview-head p{margin:0;font-size:12.5px;color:var(--c-ink-40)}
.template-preview-block + .template-preview-block{margin-top:14px}
.template-preview-title{
  font-size:12px;
  font-weight:700;
  color:var(--c-ink-60);
  text-transform:uppercase;
  letter-spacing:.04em;
  margin-bottom:8px;
}
.template-preview-grid{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:10px;
}
.template-card{
  border:1px solid var(--c-ink-10);
  border-radius:12px;
  background:#fff;
  padding:10px;
  text-align:left;
  cursor:pointer;
  transition:all .2s ease;
}
.template-card:hover{
  border-color:var(--c-accent);
  box-shadow:0 8px 20px rgba(37,99,235,.14);
  transform:translateY(-1px);
}
.template-card.active{
  border-color:var(--c-accent);
  box-shadow:0 0 0 2px rgba(37,99,235,.2),0 12px 26px rgba(15,23,42,.15);
}
.template-card-mini{
  border:1px solid var(--c-ink-05);
  border-radius:10px;
  height:92px;
  background:#fff;
  position:relative;
  overflow:hidden;
  margin-bottom:8px;
}
.template-card-mini::before{
  content:'';
  position:absolute;
  left:8px;
  right:8px;
  bottom:8px;
  height:12px;
  border-top:1px solid var(--c-ink-10);
}
.template-card-mini .mini-band{
  height:18px;
  background:#1d4ed8;
}
.template-card-mini .mini-lines{
  margin:10px 8px 0;
  height:36px;
  background:repeating-linear-gradient(180deg,#e2e8f0 0,#e2e8f0 2px,transparent 2px,transparent 8px);
}
.template-card-mini.modern{background:#f8fafc}
.template-card-mini .mini-topline{height:8px;background:#0f172a}
.template-card-mini .mini-block{
  margin:10px 8px 0;
  height:46px;
  border:1px solid #cbd5e1;
  border-radius:8px;
  background:linear-gradient(180deg,#fff 0%,#eef2ff 100%);
}
.template-card-mini.minimal{
  background:#fff;
  border-color:#d1d5db;
}
.template-card-mini .mini-thin{
  height:2px;
  background:#0f172a;
  margin-top:10px;
}
.template-card-meta{
  display:flex;
  flex-direction:column;
  gap:2px;
}
.template-card-meta strong{
  font-size:12.5px;
  color:var(--c-ink);
}
.template-card-meta span{
  font-size:11.5px;
  color:var(--c-ink-40);
}
@media (max-width: 992px){
  .template-preview-grid{grid-template-columns:1fr}
}
</style>
@endpush

@push('scripts')
<script>
const invoiceSettingsLang = {
  signatureSavedTitle: @json(__('invoice::invoices.js.signature_saved_title')),
  signatureSavedHelp: @json(__('invoice::invoices.js.signature_saved_help')),
  settingsSavedTitle: @json(__('invoice::invoices.js.settings_saved_title')),
  settingsSavedHelp: @json(__('invoice::invoices.js.settings_saved_help')),
  validationTitle: @json(__('invoice::invoices.js.validation_title')),
  validationHelp: @json(__('invoice::invoices.validation.fix_errors')),
  errorTitle: @json(__('invoice::invoices.js.error_title')),
  saveError: @json(__('invoice::invoices.alerts.unable_to_save_settings')),
  taxRatePrompt: @json(__('invoice::invoices.js.tax_rate_prompt')),
  taxRateCustom: @json(__('invoice::invoices.js.tax_rate_custom')),
};

// Tabs
function switchTab(id) {
  document.querySelectorAll('.tab-panel').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.style.background = 'transparent';
    btn.style.color = 'var(--c-ink-60)';
  });
  document.getElementById('tab-' + id).style.display = '';
  const activeBtn = document.getElementById('tab-btn-' + id);
  if (activeBtn) { activeBtn.style.background = 'var(--c-accent)'; activeBtn.style.color = '#fff'; }
}

// Signature pad
let isDrawing = false, lastX = 0, lastY = 0;
let signatureColor = '#111827';
const canvas = document.getElementById('signaturePad');
const ctx    = canvas ? canvas.getContext('2d') : null;

function setSignatureColor(color, btn) {
  signatureColor = color;
  document.querySelectorAll('.sig-color-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  if (ctx) ctx.strokeStyle = signatureColor;
}

if (ctx) {
  ctx.strokeStyle = signatureColor;
  ctx.lineWidth   = 2.5;
  ctx.lineCap     = 'round';
  ctx.lineJoin    = 'round';

  const getPos = (e) => {
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
      x: (clientX - rect.left) * (canvas.width / rect.width),
      y: (clientY - rect.top)  * (canvas.height / rect.height)
    };
  };

  const startDraw = (e) => { e.preventDefault(); isDrawing = true; const p = getPos(e); lastX = p.x; lastY = p.y; };
  const draw = (e) => {
    if (!isDrawing) return;
    e.preventDefault();
    const p = getPos(e);
    ctx.strokeStyle = signatureColor;
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    lastX = p.x; lastY = p.y;
  };
  const stopDraw = () => { isDrawing = false; };

  canvas.addEventListener('mousedown',  startDraw);
  canvas.addEventListener('mousemove',  draw);
  canvas.addEventListener('mouseup',    stopDraw);
  canvas.addEventListener('mouseleave', stopDraw);
  canvas.addEventListener('touchstart', startDraw, { passive: false });
  canvas.addEventListener('touchmove',  draw,      { passive: false });
  canvas.addEventListener('touchend',   stopDraw);
}

function clearSignature() {
  if (!ctx) return;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  document.getElementById('signature_data').value = '';
}

function saveSignature() {
  if (!canvas) return;
  document.getElementById('signature_data').value = canvas.toDataURL('image/png');
  Toast.success(invoiceSettingsLang.signatureSavedTitle, invoiceSettingsLang.signatureSavedHelp);
}

// Toggle signature config when enabled/disabled
(function initSignatureToggle(){
  const chk = document.getElementById('signatureEnabled');
  const box = document.getElementById('signatureConfig');
  if (!chk || !box) return;
  chk.addEventListener('change', () => {
    box.style.display = chk.checked ? '' : 'none';
  });
})();

// Template preview cards
(function initTemplatePreviewCards(){
  const bind = (selectName, wrapperId) => {
    const select = document.querySelector(`select[name="${selectName}"]`);
    const wrap = document.getElementById(wrapperId);
    if (!select || !wrap) return;

    const cards = Array.from(wrap.querySelectorAll('.template-card'));
    const sync = () => {
      cards.forEach((card) => {
        card.classList.toggle('active', card.dataset.templateValue === select.value);
      });
    };

    cards.forEach((card) => {
      card.addEventListener('click', () => {
        const value = card.dataset.templateValue;
        if (!value) return;
        select.value = value;
        sync();
      });
    });

    select.addEventListener('change', sync);
    sync();
  };

  bind('pdf_invoice_template', 'invoiceTemplatePreview');
  bind('pdf_quote_template', 'quoteTemplatePreview');
})();

// Sync color picker + hex input for PDF theme
(function initPdfColorFields(){
  const color = document.querySelector('input[name="pdf_primary_color"]');
  const hex = document.querySelector('input[name="pdf_primary_color_hex"]');
  if (!color || !hex) return;

  color.addEventListener('input', () => {
    hex.value = color.value;
  });

  hex.addEventListener('blur', () => {
    const value = (hex.value || '').trim();
    if (!/^#[0-9a-fA-F]{6}$/.test(value)) return;
    color.value = value;
  });
})();

// Tax rate management
function addTaxRate() {
  const rate = prompt(invoiceSettingsLang.taxRatePrompt);
  if (rate === null || isNaN(rate) || rate < 0 || rate > 100) return;
  const r = parseFloat(rate);
  const list = document.getElementById('taxRatesList');
  const item = document.createElement('div');
  item.className = 'tax-rate-item';
  item.id = `tax-${r}`;
  item.innerHTML = `
    <div class="tax-badge">${r} %</div>
    <span style="flex:1;font-size:13px;color:var(--c-ink-60);">${invoiceSettingsLang.taxRateCustom}</span>
    <input type="hidden" name="tax_rates[]" value="${r}">
    <button type="button" class="btn-icon danger btn-sm" onclick="removeTaxRate(${r})"><i class="fas fa-times"></i></button>
  `;
  list.appendChild(item);
}

function removeTaxRate(rate) {
  document.getElementById(`tax-${rate}`)?.remove();
}

// Form submit (multipart: logo upload + booleans)
document.getElementById('settingsForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const form = e.currentTarget;
  const btn = document.getElementById('saveBtn');
  if (!form) return;

  CrmForm.clearErrors(form);
  if (btn) CrmForm.setLoading(btn, true);

  const formData = new FormData(form);

  // Laravel method spoofing while keeping multipart request
  formData.set('_method', 'PUT');

  const { ok, data } = await Http.post(form.action, formData);

  if (btn) CrmForm.setLoading(btn, false);

  if (ok && data.success) {
    Toast.success(invoiceSettingsLang.settingsSavedTitle, data.message || invoiceSettingsLang.settingsSavedHelp);
    return;
  }

  if (data?.errors) {
    CrmForm.showErrors(form, data.errors);
    Toast.error(invoiceSettingsLang.validationTitle, invoiceSettingsLang.validationHelp);
    return;
  }

  Toast.error(invoiceSettingsLang.errorTitle, data?.message || invoiceSettingsLang.saveError);
});
</script>
@endpush
