@extends('client::layouts.crm')

@section('title', __('client::clients.pages.create.title'))

@section('breadcrumb')
  <a href="{{ route('clients.index') }}">{{ __('client::clients.pages.index.title') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('client::clients.pages.create.title') }}</span>
@endsection

@section('content')
@php($paymentTerms = trans('client::clients.payment_terms'))

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ __('client::clients.pages.create.title') }}</h1>
    <p>{{ __('client::clients.pages.create.subtitle') }}</p>
  </div>
  <a href="{{ route('clients.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> {{ __('client::clients.actions.back') }}
  </a>
</div>

<form id="clientForm" action="{{ route('clients.store') }}" method="POST">
  @csrf
  <div class="row" style="align-items:flex-start;">
    <div class="col-8" style="padding:0 12px 0 0;">
      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-building"></i> {{ __('client::clients.sections.general') }}
          <span class="form-section-badge">{{ __('client::clients.steps.general') }}</span>
        </h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('client::clients.fields.company_name') }} <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-building input-icon"></i>
                <input type="text" name="company_name" class="form-control" placeholder="{{ __('client::clients.placeholders.company_name') }}" autofocus>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('client::clients.fields.email') }} <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" class="form-control" placeholder="{{ __('client::clients.placeholders.email') }}">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('client::clients.fields.contact_name') }} <span class="required">*</span></label>
              <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="contact_name" class="form-control" placeholder="{{ __('client::clients.placeholders.contact_name') }}" required>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('client::clients.fields.phone') }}</label>
              <div class="input-group">
                <i class="fas fa-phone input-icon"></i>
                <input type="tel" name="phone" class="form-control" placeholder="{{ __('client::clients.placeholders.phone') }}">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('client::clients.fields.mobile') }}</label>
              <div class="input-group">
                <i class="fas fa-mobile input-icon"></i>
                <input type="tel" name="mobile" class="form-control" placeholder="{{ __('client::clients.placeholders.mobile') }}">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('client::clients.fields.website') }}</label>
              <div class="input-group">
                <i class="fas fa-globe input-icon"></i>
                <input type="url" name="website" class="form-control" placeholder="{{ __('client::clients.placeholders.website') }}">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-location-dot"></i> {{ __('client::clients.sections.address') }}
          <span class="form-section-badge">{{ __('client::clients.steps.address') }}</span>
        </h3>
        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('client::clients.fields.address') }}</label>
              <input type="text" name="address" class="form-control" placeholder="{{ __('client::clients.placeholders.address') }}">
            </div>
          </div>
          <div class="col-4"><div class="form-group"><label class="form-label">{{ __('client::clients.fields.city') }}</label><input type="text" name="city" class="form-control" placeholder="{{ __('client::clients.placeholders.city') }}"></div></div>
          <div class="col-4"><div class="form-group"><label class="form-label">{{ __('client::clients.fields.postal_code') }}</label><input type="text" name="postal_code" class="form-control" placeholder="{{ __('client::clients.placeholders.postal_code') }}"></div></div>
          <div class="col-4"><div class="form-group"><label class="form-label">{{ __('client::clients.fields.country') }}</label><input type="text" name="country" class="form-control" placeholder="{{ __('client::clients.placeholders.country') }}"></div></div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-receipt"></i> {{ __('client::clients.sections.tax') }}</h3>
        <div class="row">
          <div class="col-6"><div class="form-group"><label class="form-label">{{ __('client::clients.fields.vat_number') }}</label><input type="text" name="vat_number" class="form-control" placeholder="{{ __('client::clients.placeholders.vat_number') }}"></div></div>
          <div class="col-6"><div class="form-group"><label class="form-label">{{ __('client::clients.fields.siret') }}</label><input type="text" name="siret" class="form-control" placeholder="{{ __('client::clients.placeholders.siret') }}"></div></div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-note-sticky"></i> {{ __('client::clients.sections.notes') }}</h3>
        <div class="form-group">
          <textarea name="notes" class="form-control" rows="4" placeholder="{{ __('client::clients.placeholders.notes') }}"></textarea>
          <span class="form-hint">{{ __('client::clients.hints.notes_private') }}</span>
        </div>
      </div>
    </div>

    <div class="col-4" style="padding:0 0 0 12px;">
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title">
          <i class="fas fa-tag"></i> {{ __('client::clients.sections.categorization') }}
          <span class="form-section-badge">{{ __('client::clients.steps.categorization') }}</span>
        </h3>
        <div class="form-group">
          <label class="form-label">{{ __('client::clients.fields.type') }} <span class="required">*</span></label>
          <select name="type" class="form-control">@foreach($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        </div>
        <div class="form-group">
          <label class="form-label">{{ __('client::clients.fields.status') }} <span class="required">*</span></label>
          <select name="status" class="form-control">@foreach($statuses as $key => $label)<option value="{{ $key }}" {{ $key === 'actif' ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
        </div>
        <div class="form-group">
          <label class="form-label">{{ __('client::clients.fields.source') }}</label>
          <select name="source" class="form-control"><option value="">{{ __('client::clients.placeholders.source') }}</option>@foreach($sources as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        </div>
        <div class="form-group"><label class="form-label">{{ __('client::clients.fields.industry') }}</label><input type="text" name="industry" class="form-control" placeholder="{{ __('client::clients.placeholders.industry') }}"></div>
        <div class="form-group">
          <label class="form-label">{{ __('client::clients.fields.tags') }}</label>
          <div class="tags-input-wrap" id="tags_wrap" data-tags-input="tags">
            <input type="text" class="tags-input" placeholder="{{ __('client::clients.placeholders.tags') }}">
          </div>
          <span class="form-hint">{{ __('client::clients.hints.tags') }}</span>
        </div>
      </div>

      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title">
          <i class="fas fa-chart-line"></i> {{ __('client::clients.sections.finance') }}
          <span class="form-section-badge">{{ __('client::clients.steps.finance') }}</span>
        </h3>
        <div class="form-group">
          <label class="form-label">{{ __('client::clients.fields.revenue') }} (€)</label>
          <div class="input-group input-right"><input type="number" name="revenue" class="form-control" placeholder="{{ __('client::clients.placeholders.revenue') }}" min="0" step="100"><i class="fas fa-euro-sign input-icon"></i></div>
        </div>
        <div class="form-group">
          <label class="form-label">{{ __('client::clients.fields.potential_value') }} (€)</label>
          <div class="input-group input-right"><input type="number" name="potential_value" class="form-control" placeholder="{{ __('client::clients.placeholders.potential_value') }}" min="0" step="100"><i class="fas fa-euro-sign input-icon"></i></div>
        </div>
        <div class="form-group">
          <label class="form-label">{{ __('client::clients.fields.payment_term') }}</label>
          <select name="payment_term" class="form-control"><option value="">{{ __('client::clients.placeholders.payment_term') }}</option>@foreach($paymentTerms as $value => $label)<option value="{{ $value }}" {{ $value === '30j' ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
        </div>
        <div class="form-group"><label class="form-label">{{ __('client::clients.fields.employee_count') }}</label><input type="number" name="employee_count" class="form-control" placeholder="{{ __('client::clients.placeholders.employee_count') }}" min="0"></div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-calendar-check"></i> {{ __('client::clients.sections.follow_up') }}</h3>
        <div class="form-group"><label class="form-label">{{ __('client::clients.fields.next_follow_up_at') }}</label><input type="date" name="next_follow_up_at" class="form-control" min="{{ date('Y-m-d') }}"></div>
      </div>
    </div>
  </div>

  <div class="form-section" style="margin-top:0;">
    <div class="form-actions" style="padding-top:0">
      <a href="{{ route('clients.index') }}" class="btn btn-secondary"><i class="fas fa-times"></i> {{ __('client::clients.actions.cancel') }}</a>
      <button type="submit" class="btn btn-primary" id="submitBtn"><i class="fas fa-check"></i> {{ __('client::clients.actions.create') }}</button>
    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
window.CLIENT_LANG = Object.assign(window.CLIENT_LANG || {}, {
  successTitle: @json(__('client::clients.messages.success_title')),
  createdTitle: @json(__('client::clients.messages.created_title')),
  createdMessage: @json(__('client::clients.messages.created_help')),
});

document.addEventListener('DOMContentLoaded', () => {
  window.CrmDrafts?.attach('clientForm', {
    type: 'client',
    label: 'client',
    collect: (data) => {
      const tags = window.CrmTagsInputs?.tags_wrap?.getTags?.() || [];
      data['tags[]'] = tags;
      return data;
    },
    apply: (data) => {
      const tags = Array.isArray(data['tags[]']) ? data['tags[]'] : [];
      window.CrmTagsInputs?.tags_wrap?.setTags?.(tags);
    },
  });

  ajaxForm('clientForm', {
    onSuccess: () => {
      Toast.success(window.CLIENT_LANG.createdTitle, window.CLIENT_LANG.createdMessage, 3000);
    }
  });
});
</script>
@endpush
