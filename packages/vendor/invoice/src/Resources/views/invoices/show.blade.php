@extends('invoice::layouts.invoice')

@section('title', __('invoice::invoices.pages.invoice_show.title', ['number' => $invoice->number]))

@section('breadcrumb')
  <a href="{{ route('invoices.index') }}">{{ __('invoice::invoices.invoices') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $invoice->number }}</span>
@endsection

@section('content')

<div class="page-header">
  <div class="page-header-left" style="display:flex;align-items:center;gap:16px;">
    @php
      $colors = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706'];
      $color  = $colors[ord($invoice->number[0] ?? 'F') % count($colors)];
    @endphp
    <div style="width:56px;height:56px;border-radius:var(--r-md);background:{{ $color }};color:#fff;display:flex;align-items:center;justify-content:center;font-family: "DM Sans", sans-serif;font-size:20px;font-weight:700;flex-shrink:0;">
      <i class="fas fa-file-invoice" style="font-size:22px;"></i>
    </div>
    <div>
      <h1 style="margin-bottom:6px;">{{ $invoice->number }}</h1>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span class="badge badge-{{ $invoice->status }}">
          <span class="badge-dot" style="background:currentColor"></span>
          {{ $invoice->status_label }}
        </span>
        @if($invoice->is_overdue)
          <span class="badge" style="background:var(--c-danger-lt);color:var(--c-danger)">
            <i class="fas fa-exclamation-triangle"></i> {{ __('invoice::invoices.status.overdue') }}
          </span>
        @endif
        <span style="font-size:12px;color:var(--c-ink-40)">
          <i class="fas fa-calendar" style="margin-right:4px;"></i>
          {{ __('invoice::invoices.pages.invoice_show.issued_on', ['date' => $invoice->issue_date->format('d/m/Y')]) }}
        </span>
        <span style="font-size:12px;color:var(--c-ink-40)">
          <i class="fas fa-money-bill" style="margin-right:4px;"></i>
          {{ $invoice->currency }} {{ $invoice->currency_symbol }}
        </span>
      </div>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('invoices.pdf', $invoice) }}" data-pdf-export data-pdf-filename="facture-{{ $invoice->number }}.pdf" class="btn btn-secondary">
      <i class="fas fa-file-pdf"></i> PDF
    </a>
    @if(!in_array($invoice->status, ['paid','cancelled']))
      <button class="btn btn-secondary" onclick="sendInvoice({{ $invoice->id }})">
        <i class="fas fa-paper-plane"></i> {{ __('invoice::invoices.actions.mark_sent') }}
      </button>
    @endif
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-ellipsis"></i>
      </button>
      <div class="dropdown-menu">
        <a href="#" class="dropdown-item" onclick="duplicateInvoice({{ $invoice->id }})">
          <i class="fas fa-copy"></i> {{ __('invoice::invoices.actions.duplicate') }}
        </a>
        @if(!in_array($invoice->status, ['paid','cancelled']))
          <a href="{{ route('invoices.edit', $invoice) }}" class="dropdown-item">
            <i class="fas fa-pen"></i> {{ __('invoice::invoices.actions.edit') }}
          </a>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item danger" onclick="deleteInvoice({{ $invoice->id }})">
            <i class="fas fa-trash"></i> {{ __('invoice::invoices.actions.delete') }}
          </button>
        @endif
      </div>
    </div>
    @if(!in_array($invoice->status, ['paid','cancelled']))
      <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-primary">
        <i class="fas fa-pen"></i> {{ __('invoice::invoices.actions.edit') }}
      </a>
    @endif
  </div>
</div>

{{-- KPIs --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-receipt"></i></div>
    <div class="stat-body">
      <div class="stat-value">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</div>
      <div class="stat-label">{{ __('invoice::invoices.common.total_ttc') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-circle-check"></i></div>
    <div class="stat-body">
      <div class="stat-value">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</div>
      <div class="stat-label">{{ __('invoice::invoices.stats.paid') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:{{ $invoice->amount_due > 0 ? 'var(--c-danger-lt)' : 'var(--c-success-lt)' }};color:{{ $invoice->amount_due > 0 ? 'var(--c-danger)' : 'var(--c-success)' }}">
      <i class="fas fa-{{ $invoice->amount_due > 0 ? 'clock-rotate-left' : 'check-double' }}"></i>
    </div>
    <div class="stat-body">
      <div class="stat-value" style="color:{{ $invoice->amount_due > 0 ? 'var(--c-danger)' : 'var(--c-success)' }}">
        {{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $invoice->currency_symbol }}
      </div>
      <div class="stat-label">{{ __('invoice::invoices.fields.amount_due') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-calendar-days"></i></div>
    <div class="stat-body">
      <div class="stat-value" style="font-size:16px;{{ $invoice->is_overdue ? 'color:var(--c-danger)' : '' }}">
        {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
      </div>
      <div class="stat-label">{{ __('invoice::invoices.fields.due_date') }}</div>
      @if($invoice->is_overdue)
        <span class="stat-trend down"><i class="fas fa-exclamation"></i> {{ __('invoice::invoices.pages.invoice_show.late_days', ['days' => abs($invoice->due_date->diffInDays(now()))]) }}</span>
      @endif
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">

  {{-- COLONNE PRINCIPALE --}}
  <div class="col-8" style="padding:0 12px 0 0;">

    {{-- Infos générales --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-circle-info"></i>
        <h3>{{ __('invoice::invoices.pages.invoice_show.invoice_information') }}</h3>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
        <div style="padding:20px;border-right:1px solid var(--c-ink-05);">
          <div style="font-size:11px;font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.07em;color:var(--c-ink-40);margin-bottom:10px;">{{ __('invoice::invoices.pages.invoice_show.issuer') }}</div>
          <div style="font-weight:var(--fw-semi);font-size:14px;margin-bottom:4px;">{{ $invoice->tenant->name ?? config('app.name') }}</div>
          <div style="font-size:12.5px;color:var(--c-ink-40);line-height:1.7;">
            {{ $invoice->tenant->email ?? '' }}<br>
            {{ $invoice->tenant->address ?? '' }}
          </div>
        </div>
        <div style="padding:20px;">
          <div style="font-size:11px;font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.07em;color:var(--c-ink-40);margin-bottom:10px;">{{ __('invoice::invoices.pages.invoice_show.billed_to') }}</div>
          <div style="display:flex;gap:10px;align-items:flex-start;">
            @php $initials = strtoupper(substr($invoice->client?->company_name ?: 'C', 0, 2)); @endphp
            <div class="client-avatar-sm" style="width:38px;height:38px;font-size:14px;">{{ $initials }}</div>
            <div>
              <div style="font-weight:var(--fw-semi);font-size:14px;margin-bottom:3px;">{{ $invoice->client->company_name }}</div>
              <div style="font-size:12.5px;color:var(--c-ink-40);line-height:1.7;">
                {{ $invoice->client->contact_name }}<br>
                {{ $invoice->client->email }}<br>
                {{ $invoice->client->full_address }}
              </div>
            </div>
          </div>
        </div>
      </div>
      {{-- Dates strip --}}
      <div style="display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--c-ink-05);">
        <div style="padding:14px 20px;border-right:1px solid var(--c-ink-05);">
          <div style="font-size:11px;color:var(--c-ink-40);margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em;">{{ __('invoice::invoices.pages.invoice_show.issue_date_short') }}</div>
          <div style="font-weight:var(--fw-semi);">{{ $invoice->issue_date->format('d/m/Y') }}</div>
        </div>
        <div style="padding:14px 20px;border-right:1px solid var(--c-ink-05);">
          <div style="font-size:11px;color:var(--c-ink-40);margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em;">{{ __('invoice::invoices.fields.due_date') }}</div>
          <div style="font-weight:var(--fw-semi);{{ $invoice->is_overdue ? 'color:var(--c-danger)' : '' }}">
            {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
          </div>
        </div>
        <div style="padding:14px 20px;border-right:1px solid var(--c-ink-05);">
          <div style="font-size:11px;color:var(--c-ink-40);margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em;">{{ __('invoice::invoices.pages.invoice_show.payment') }}</div>
          <div style="font-weight:var(--fw-semi);">{{ config("invoice.payment_terms.{$invoice->payment_terms}", $invoice->payment_terms.'j') }}</div>
        </div>
        <div style="padding:14px 20px;">
          <div style="font-size:11px;color:var(--c-ink-40);margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em;">{{ __('invoice::invoices.pages.invoice_show.mode') }}</div>
          <div style="font-weight:var(--fw-semi);">{{ config("invoice.payment_methods.{$invoice->payment_method}", $invoice->payment_method ?? '-') }}</div>
        </div>
      </div>
    </div>

    {{-- Lignes --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-list"></i>
        <h3>{{ __('invoice::invoices.common.invoice_lines') }}</h3>
      </div>
      <div style="overflow-x:auto;">
        <table class="crm-table">
          <thead>
            <tr>
              <th style="width:30px">#</th>
              <th>{{ __('invoice::invoices.fields.description') }}</th>
              <th style="text-align:right;width:80px">{{ __('invoice::invoices.common.line_quantity') }}</th>
              <th style="text-align:right;width:80px">{{ __('invoice::invoices.fields.unit') }}</th>
              <th style="text-align:right;width:110px">{{ __('invoice::invoices.common.line_unit_price_ht') }}</th>
              <th style="text-align:right;width:100px">{{ __('invoice::invoices.fields.discount') }}</th>
              <th style="text-align:right;width:80px">{{ __('invoice::invoices.common.vat') }}</th>
              <th style="text-align:right;width:120px">{{ __('invoice::invoices.common.total_ttc') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($invoice->items as $i => $item)
            <tr>
              <td style="color:var(--c-ink-40);font-size:12px;">{{ $i + 1 }}</td>
              <td>
                <div style="font-weight:var(--fw-medium);">{{ $item->description }}</div>
                @if($item->reference)
                  <div style="font-size:11.5px;color:var(--c-ink-40);">{{ __('invoice::invoices.fields.reference') }} : {{ $item->reference }}</div>
                @endif
              </td>
              <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
              <td class="text-right" style="color:var(--c-ink-40);">{{ $item->unit ?: '—' }}</td>
              <td class="text-right font-mono">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
              <td class="text-right" style="color:var(--c-danger);">
                @if($item->discount_amount > 0) -{{ number_format($item->discount_amount, 2, ',', ' ') }} @else — @endif
              </td>
              <td class="text-right">{{ $item->tax_rate }}%</td>
              <td class="text-right fw-semi font-mono">{{ number_format($item->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      {{-- Totaux --}}
      <div style="display:flex;justify-content:flex-end;padding:20px;">
        <div style="width:280px;">
          <div class="totals-panel">
            <div class="totals-row">
              <span class="totals-label">{{ __('invoice::invoices.common.subtotal_ht') }}</span>
              <span class="totals-value">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="totals-row discount">
              <span class="totals-label">{{ __('invoice::invoices.fields.discount') }}</span>
              <span class="totals-value">-{{ number_format($invoice->discount_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @endif
            <div class="totals-row">
              <span class="totals-label">TVA ({{ $invoice->tax_rate }}%)</span>
              <span class="totals-value">{{ number_format($invoice->tax_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @if($invoice->withholding_tax_rate > 0)
            <div class="totals-row" style="color:var(--c-warning);">
              <span class="totals-label">{{ __('invoice::invoices.withholding.label') }} ({{ $invoice->withholding_tax_rate }}%)</span>
              <span class="totals-value">-{{ number_format($invoice->withholding_tax_amount, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @endif
            <div class="totals-row grand-total">
              <span class="totals-label">{{ __('invoice::invoices.common.total_ttc') }}</span>
              <span class="totals-value">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @php
              $__base = strtoupper((string) ($invoice->tenant->currency ?? config('invoice.default_currency', 'EUR')));
              $__rate = (float) ($invoice->exchange_rate ?? 1);
            @endphp
            @if(strtoupper((string) $invoice->currency) !== $__base && $__rate > 0 && abs($__rate - 1.0) > 0.0000001)
            <div class="totals-row grand-total" style="color:var(--c-ink-60);">
              <span class="totals-label">{{ __('invoice::invoices.common.equivalent_total') }} {{ $__base }}</span>
              <span class="totals-value">{{ \Vendor\Invoice\Support\Money::format((float) $invoice->total * $__rate, $__base) }}</span>
            </div>
            @endif
            @if($invoice->amount_paid > 0)
            <div class="totals-row" style="color:var(--c-success);">
              <span class="totals-label">{{ __('invoice::invoices.fields.amount_paid') }}</span>
              <span class="totals-value">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @endif
            @if($invoice->amount_due > 0)
            <div class="totals-row due-amount">
              <span class="totals-label"><i class="fas fa-coins"></i> {{ __('invoice::invoices.fields.amount_due') }}</span>
              <span class="totals-value">{{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $invoice->currency_symbol }}</span>
            </div>
            @endif
          </div>
          {{-- Progress bar --}}
          @if($invoice->total > 0)
          <div style="margin-top:16px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--c-ink-40);margin-bottom:6px;">
              <span>{{ __('invoice::invoices.pages.invoice_show.settlement') }}</span><span>{{ $invoice->progress_percent }}%</span>
            </div>
            <div class="progress-bar-wrap">
              <div class="progress-bar-fill" style="width:{{ $invoice->progress_percent }}%"></div>
            </div>
          </div>
          @endif
        </div>
      </div>

      @if($invoice->notes)
      <div style="padding:16px 20px;border-top:1px solid var(--c-ink-05);">
        <div style="font-size:11px;font-weight:var(--fw-bold);text-transform:uppercase;letter-spacing:.05em;color:var(--c-ink-40);margin-bottom:6px;">{{ __('invoice::invoices.fields.notes') }}</div>
        <p style="font-size:13.5px;color:var(--c-ink-60);line-height:1.7;margin:0;">{{ $invoice->notes }}</p>
      </div>
      @endif
    </div>

    {{-- Paiements --}}
    <div class="info-card">
      <div class="info-card-header">
        <i class="fas fa-credit-card"></i>
        <h3>{{ __('invoice::invoices.pages.invoice_show.received_payments') }}</h3>
        @if(!in_array($invoice->status, ['paid','cancelled']))
          <button class="btn btn-sm btn-success" style="margin-left:auto;" data-modal-open="paymentModal">
            <i class="fas fa-plus"></i> {{ __('invoice::invoices.actions.add_payment') }}
          </button>
        @endif
      </div>
      <div class="info-card-body">
        @if($invoice->payments->isEmpty())
          <div style="text-align:center;padding:24px;color:var(--c-ink-40);">
            <i class="fas fa-credit-card" style="font-size:24px;margin-bottom:10px;display:block;opacity:.3;"></i>
            {{ __('invoice::invoices.pages.invoice_show.no_payment') }}
          </div>
        @else
          @foreach($invoice->payments as $payment)
          <div class="payment-item">
            <div class="payment-icon"><i class="fas fa-circle-check"></i></div>
            <div style="flex:1;">
              <div class="payment-amount">{{ number_format($payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</div>
              <div class="payment-meta">
                {{ $payment->payment_date->format('d/m/Y') }} —
                {{ $payment->method_label }}
                @if($payment->reference) · {{ __('invoice::invoices.fields.reference') }} : {{ $payment->reference }} @endif
                @if($payment->bank_name) · {{ $payment->bank_name }} @endif
              </div>
            </div>
            @if(!$invoice->is_paid)
              <button class="btn-icon danger btn-sm" onclick="deletePayment({{ $payment->id }})" title="{{ __('invoice::invoices.actions.delete') }}">
                <i class="fas fa-trash"></i>
              </button>
            @endif
          </div>
          @endforeach
        @endif
      </div>
    </div>

  </div>

  {{-- SIDEBAR --}}
  <div class="col-4" style="padding:0 0 0 12px;">

    {{-- Activité --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-clock-rotate-left"></i>
        <h3>{{ __('invoice::invoices.pages.invoice_show.activity') }}</h3>
      </div>
      <div class="info-card-body">
        <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;color:var(--c-ink-60);">
          <div><i class="fas fa-file-circle-plus" style="color:var(--c-accent);width:16px;"></i> {{ __('invoice::invoices.pages.invoice_show.created_on', ['date' => $invoice->created_at->format('d/m/Y H:i')]) }}</div>
          @if($invoice->sent_at)
            <div><i class="fas fa-paper-plane" style="color:var(--c-info);width:16px;"></i> {{ __('invoice::invoices.pages.invoice_show.sent_on', ['date' => $invoice->sent_at->format('d/m/Y H:i')]) }}</div>
          @endif
          @if($invoice->viewed_at)
            <div><i class="fas fa-eye" style="color:var(--c-purple);width:16px;"></i> {{ __('invoice::invoices.pages.invoice_show.viewed_on', ['date' => $invoice->viewed_at->format('d/m/Y H:i')]) }}</div>
          @endif
          @if($invoice->payment_date)
            <div><i class="fas fa-circle-check" style="color:var(--c-success);width:16px;"></i> {{ __('invoice::invoices.pages.invoice_show.paid_on', ['date' => $invoice->payment_date->format('d/m/Y')]) }}</div>
          @endif
        </div>
      </div>
    </div>

    {{-- Devis source --}}
    @if($invoice->quote)
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-file-signature"></i>
        <h3>{{ __('invoice::invoices.pages.invoice_show.source_quote') }}</h3>
      </div>
      <div class="info-card-body">
        <a href="{{ route('invoices.quotes.show', $invoice->quote) }}" style="color:var(--c-accent);font-weight:var(--fw-semi);">
          {{ $invoice->quote->number }}
        </a>
        <div style="font-size:12px;color:var(--c-ink-40);margin-top:4px;">
          {{ __('invoice::invoices.pages.quote_show.issued_on', ['date' => $invoice->quote->issue_date->format('d/m/Y')]) }}
        </div>
      </div>
    </div>
    @endif

    {{-- Infos commerciales --}}
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-chart-bar"></i>
        <h3>{{ __('invoice::invoices.pages.invoice_show.commercial_information') }}</h3>
      </div>
      <div class="info-card-body">
        <div class="info-row">
          <span class="info-row-label">{{ __('invoice::invoices.fields.reference') }}</span>
          <span class="info-row-value">{{ $invoice->reference ?: '—' }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">{{ __('invoice::invoices.pages.invoice_show.created_by') }}</span>
          <span class="info-row-value">{{ $invoice->user?->name ?: '—' }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">{{ __('invoice::invoices.pages.invoice_show.reminders') }}</span>
          <span class="info-row-value">{{ __('invoice::invoices.pages.invoice_show.reminders_sent', ['count' => $invoice->reminder_count]) }}</span>
        </div>
        @if($invoice->last_reminder_at)
        <div class="info-row">
          <span class="info-row-label">{{ __('invoice::invoices.pages.invoice_show.last_reminder') }}</span>
          <span class="info-row-value">{{ $invoice->last_reminder_at->format('d/m/Y') }}</span>
        </div>
        @endif
      </div>
    </div>

    {{-- Actions rapides --}}
    <div class="info-card">
      <div class="info-card-header">
        <i class="fas fa-bolt"></i>
        <h3>{{ __('invoice::invoices.pages.invoice_show.quick_actions') }}</h3>
      </div>
      <div class="info-card-body" style="display:flex;flex-direction:column;gap:8px;">
        <a href="{{ route('invoices.pdf', $invoice) }}" data-pdf-export data-pdf-filename="facture-{{ $invoice->number }}.pdf" class="btn btn-secondary" style="justify-content:flex-start;">
          <i class="fas fa-file-pdf"></i> {{ __('invoice::invoices.pages.invoice_show.download_pdf') }}
        </a>
        @if(!in_array($invoice->status, ['paid','cancelled']))
          <button class="btn btn-secondary" style="justify-content:flex-start;" onclick="sendInvoice({{ $invoice->id }})">
            <i class="fas fa-paper-plane"></i> {{ __('invoice::invoices.pages.invoice_show.mark_as_sent') }}
          </button>
          <button class="btn btn-secondary" style="justify-content:flex-start;" data-modal-open="paymentModal">
            <i class="fas fa-credit-card"></i> {{ __('invoice::invoices.actions.add_payment') }}
          </button>
          <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary" style="justify-content:flex-start;">
            <i class="fas fa-pen"></i> {{ __('invoice::invoices.actions.edit') }}
          </a>
        @endif
        <button class="btn btn-secondary" style="justify-content:flex-start;" onclick="duplicateInvoice({{ $invoice->id }})">
          <i class="fas fa-copy"></i> {{ __('invoice::invoices.actions.duplicate') }}
        </button>
        @if(!in_array($invoice->status, ['paid']))
        <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-danger);border-color:var(--c-danger-lt);" onclick="deleteInvoice({{ $invoice->id }})">
          <i class="fas fa-trash"></i> {{ __('invoice::invoices.actions.delete') }}
        </button>
        @endif
      </div>
    </div>

  </div>
</div>

{{-- Payment Modal --}}
<div class="modal-overlay" id="paymentModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-success-lt);color:var(--c-success)">
        <i class="fas fa-credit-card"></i>
      </div>
      <div>
        <div class="modal-title">{{ __('invoice::invoices.actions.add_payment') }}</div>
        <div class="modal-subtitle">{{ __('invoice::invoices.pages.invoice_show.payment_modal_subtitle', ['number' => $invoice->number, 'amount' => number_format($invoice->amount_due, 2, ',', ' ') . ' ' . $invoice->currency_symbol]) }}</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="paymentForm" action="{{ route('invoices.payments.store', $invoice) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.amount') }} <span class="required">*</span></label>
              <div class="input-group input-right">
                <input type="number" name="amount" class="form-control" value="{{ $invoice->amount_due }}" min="0.01" step="any" required>
                <span class="input-icon" style="font-weight:700;font-size:14px;">{{ $invoice->currency_symbol }}</span>
              </div>
              <input type="hidden" name="currency" value="{{ $invoice->currency }}">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.payment_date') }} <span class="required">*</span></label>
              <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.payment_method') }} <span class="required">*</span></label>
              <select name="payment_method" class="form-control" required>
                @foreach(config('invoice.payment_methods') as $key => $label)
                  <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.reference') }}</label>
              <input type="text" name="reference" class="form-control" placeholder="{{ __('invoice::invoices.fields.reference') }}">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.bank_name') }}</label>
              <input type="text" name="bank_name" class="form-control" placeholder="{{ __('invoice::invoices.fields.bank_name') }}">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.notes') }}</label>
              <textarea name="notes" class="form-control" rows="2" style="min-height:70px;"></textarea>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('invoice::invoices.fields.attachment') }} <span class="hint">(PDF, image)</span></label>
              <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('invoice::invoices.actions.cancel') }}</button>
      <button class="btn btn-success" id="paymentSubmitBtn" onclick="submitPayment()">
        <i class="fas fa-circle-check"></i> {{ __('invoice::invoices.actions.add_payment') }}
      </button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
const invoiceShowLang = {
  successTitle: @json(__('invoice::invoices.js.success_title')),
  errorTitle: @json(__('invoice::invoices.js.error_title')),
  validationTitle: @json(__('invoice::invoices.js.validation_title')),
  invoiceSentTitle: @json(__('invoice::invoices.js.invoice_sent_title')),
  invoiceDuplicatedTitle: @json(__('invoice::invoices.js.invoice_duplicated_title')),
  invoiceDeletedTitle: @json(__('invoice::invoices.js.invoice_deleted_title')),
  paymentDeletedTitle: @json(__('invoice::invoices.js.payment_deleted_title')),
  paymentSavedTitle: @json(__('invoice::invoices.js.payment_saved_title')),
  sendConfirm: @json(__('invoice::invoices.messages.invoice_mark_sent_confirm')),
  irreversibleAction: @json(__('invoice::invoices.alerts.irreversible')),
  paymentRecalculation: @json(__('invoice::invoices.alerts.payment_recalculated')),
  deleteLabel: @json(__('invoice::invoices.actions.delete')),
  invoiceDeleteTitle: @json(__('invoice::invoices.js.invoice_delete_title', ['number' => $invoice->number])),
  paymentDeleteTitle: @json(__('invoice::invoices.js.payment_delete_title')),
};
const invoiceShowRoutes = {
  send: @json(route('invoices.send', $invoice)),
  duplicate: @json(route('invoices.duplicate', $invoice)),
  destroy: @json(route('invoices.destroy', $invoice)),
  index: @json(route('invoices.index')),
  paymentDestroy: @json(route('invoices.payments.destroy', ['payment' => '__PAYMENT__'])),
};
const invoiceShowRoute = (template, id) => String(template).replace('__PAYMENT__', encodeURIComponent(String(id)));

async function sendInvoice(id) {
  if (!confirm(invoiceShowLang.sendConfirm)) return;
  const { ok, data } = await Http.post(invoiceShowRoutes.send, {});
  if (ok) { Toast.success(invoiceShowLang.invoiceSentTitle, data.message); setTimeout(() => location.reload(), 1000); }
  else Toast.error(invoiceShowLang.errorTitle, data.message);
}

async function duplicateInvoice(id) {
  const { ok, data } = await Http.post(invoiceShowRoutes.duplicate, {});
  if (ok) { Toast.success(invoiceShowLang.invoiceDuplicatedTitle, data.message); setTimeout(() => window.location.href = data.redirect, 1000); }
  else Toast.error(invoiceShowLang.errorTitle, data.message);
}

async function deleteInvoice(id) {
  Modal.confirm({
    title: invoiceShowLang.invoiceDeleteTitle,
    message: invoiceShowLang.irreversibleAction,
    confirmText: invoiceShowLang.deleteLabel,
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete(invoiceShowRoutes.destroy);
      if (ok) { Toast.success(invoiceShowLang.invoiceDeletedTitle, data.message); setTimeout(() => window.location.href = invoiceShowRoutes.index, 1000); }
      else Toast.error(invoiceShowLang.errorTitle, data.message);
    }
  });
}

async function deletePayment(id) {
  Modal.confirm({
    title: invoiceShowLang.paymentDeleteTitle,
    message: invoiceShowLang.paymentRecalculation,
    confirmText: invoiceShowLang.deleteLabel,
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete(invoiceShowRoute(invoiceShowRoutes.paymentDestroy, id));
      if (ok) { Toast.success(invoiceShowLang.paymentDeletedTitle, data.message); setTimeout(() => location.reload(), 1000); }
      else Toast.error(invoiceShowLang.errorTitle, data.message);
    }
  });
}

async function submitPayment() {
  const btn  = document.getElementById('paymentSubmitBtn');
  const form = document.getElementById('paymentForm');
  CrmForm.clearErrors(form);
  CrmForm.setLoading(btn, true);
  const { ok, data, status } = await Http.post(form.action, new FormData(form));
  CrmForm.setLoading(btn, false);
  if (ok) {
    Toast.success(invoiceShowLang.paymentSavedTitle, data.message);
    Modal.close(document.getElementById('paymentModal'));
    setTimeout(() => location.reload(), 1000);
  } else if (status === 422) {
    CrmForm.showErrors(form, data.errors || {});
    Toast.error(invoiceShowLang.validationTitle, data.message);
  } else {
    Toast.error(invoiceShowLang.errorTitle, data.message);
  }
}
</script>
@endpush

