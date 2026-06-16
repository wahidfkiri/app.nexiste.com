@extends('layouts.global')

@section('title', isset($extension) ? __('extensions::extensions.superadmin.form.edit_title', ['name' => $extension->name]) : __('extensions::extensions.superadmin.form.create_title'))

@section('breadcrumb')
  <a href="{{ route('superadmin.extensions.index') }}">{{ __('extensions::extensions.common.extensions') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ isset($extension) ? __('extensions::extensions.common.edit') : __('extensions::extensions.actions.new_extension') }}</span>
@endsection

@section('content')

@php
  $isEdit = isset($extension);
  $act    = $isEdit ? route('superadmin.extensions.update', $extension) : route('superadmin.extensions.store');
  $method = $isEdit ? 'PUT' : 'POST';
  $currentIconValue = old('icon');
  if ($currentIconValue === null && $isEdit) {
      $iconValue = (string) ($extension->icon ?? '');
      $isIconClass = \Illuminate\Support\Str::startsWith($iconValue, ['fa-', 'fas ', 'far ', 'fab ', 'fal ', 'fad ']);
      $currentIconValue = $isIconClass ? $iconValue : '';
  }
@endphp

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $isEdit ? __('extensions::extensions.superadmin.form.edit_heading') : __('extensions::extensions.superadmin.form.create_heading') }}</h1>
    <p>{{ $isEdit ? $extension->name : __('extensions::extensions.superadmin.form.create_description') }}</p>
  </div>
  <a href="{{ $isEdit ? route('superadmin.extensions.show', $extension) : route('superadmin.extensions.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> {{ __('extensions::extensions.common.back') }}
  </a>
</div>

<form id="extForm" action="{{ $act }}" method="POST" enctype="multipart/form-data">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <div class="row" style="align-items:flex-start;">

    {{-- Colonne principale --}}
    <div class="col-8" style="padding:0 12px 0 0;">

      {{-- Infos de base --}}
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-puzzle-piece"></i> {{ __('extensions::extensions.superadmin.form.basic_info') }}</h3>
        <div class="row">
          <div class="col-8">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.name') }} <span class="required">*</span></label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $extension->name : '') }}" placeholder="Ex: Google Drive" required>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.common.version') }}</label>
              <input type="text" name="version" class="form-control" value="{{ old('version', $isEdit ? $extension->version : '1.0.0') }}" placeholder="1.0.0">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.tagline') }}</label>
              <input type="text" name="tagline" class="form-control" value="{{ old('tagline', $isEdit ? $extension->tagline : '') }}" placeholder="Stockez et partagez vos fichiers sans effort" maxlength="255">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.description') }}</label>
              <textarea name="description" class="form-control" rows="2" maxlength="500" placeholder="{{ __('extensions::extensions.superadmin.form.description_placeholder') }}">{{ old('description', $isEdit ? $extension->description : '') }}</textarea>
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.long_description') }} <span class="hint">({{ __('extensions::extensions.superadmin.form.markdown_hint') }})</span></label>
              <textarea name="long_description" class="form-control" rows="6" placeholder="{{ __('extensions::extensions.superadmin.form.long_description_placeholder') }}">{{ old('long_description', $isEdit ? $extension->long_description : '') }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- Icône & Visuels --}}
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-image"></i> {{ __('extensions::extensions.superadmin.form.visuals') }}</h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.fa_icon') }} <span class="hint">({{ __('extensions::extensions.superadmin.form.fa_icon_hint') }})</span></label>
              <div class="input-group">
                <i class="fas fa-icons input-icon"></i>
                <input type="text" name="icon" class="form-control" value="{{ $currentIconValue ?? '' }}" placeholder="fa-puzzle-piece (optionnel)"
                       oninput="updateIconPreview(this.value)">
              </div>
              <span class="form-hint">{{ __('extensions::extensions.superadmin.form.fa_icon_help') }}</span>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.icon_bg') }}</label>
              <div style="display:flex;gap:8px;align-items:center;">
                <input type="color" name="icon_bg_color" id="iconBgColor"
                       value="{{ old('icon_bg_color', $isEdit ? $extension->icon_bg_color : '#3b82f6') }}"
                       style="width:44px;height:38px;border-radius:var(--r-sm);border:1.5px solid var(--c-ink-10);cursor:pointer;padding:2px;"
                       oninput="updateIconPreview()">
                {{-- Aperçu icône --}}
                <div id="iconPreview" style="width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;transition:all .2s;">
                  <i class="fas fa-puzzle-piece" id="previewIcon"></i>
                </div>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.icon_upload') }} <span class="hint">({{ __('extensions::extensions.superadmin.form.icon_upload_hint') }})</span></label>
              <input type="file" name="icon_file" class="form-control" accept="image/*">
              @if($isEdit && $extension->icon_url)
                <div style="margin-top:8px;"><img src="{{ $extension->icon_url }}" style="width:40px;height:40px;border-radius:10px;border:1px solid var(--c-ink-05);"></div>
              @endif
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.banner_upload') }} <span class="hint">({{ __('extensions::extensions.superadmin.form.banner_upload_hint') }})</span></label>
              <input type="file" name="banner_file" class="form-control" accept="image/*">
              @if($isEdit && $extension->banner_url)
                <div style="margin-top:8px;"><img src="{{ $extension->banner_url }}" style="width:100%;border-radius:var(--r-sm);border:1px solid var(--c-ink-05);"></div>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- Éditeur --}}
      <div class="form-section">
        <h3 class="form-section-title"><i class="fas fa-code"></i> {{ __('extensions::extensions.superadmin.form.publisher_links') }}</h3>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.developer_name') }}</label>
              <input type="text" name="developer_name" class="form-control" value="{{ old('developer_name', $isEdit ? $extension->developer_name : '') }}">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.developer_url') }}</label>
              <input type="url" name="developer_url" class="form-control" value="{{ old('developer_url', $isEdit ? $extension->developer_url : '') }}" placeholder="https://…">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.documentation') }}</label>
              <input type="url" name="documentation_url" class="form-control" value="{{ old('documentation_url', $isEdit ? $extension->documentation_url : '') }}" placeholder="https://docs.…">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.support') }}</label>
              <input type="url" name="support_url" class="form-control" value="{{ old('support_url', $isEdit ? $extension->support_url : '') }}" placeholder="https://…">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">{{ __('extensions::extensions.superadmin.form.webhook_url') }} <span class="hint">({{ __('extensions::extensions.superadmin.form.webhook_hint') }})</span></label>
              <input type="url" name="webhook_url" class="form-control" value="{{ old('webhook_url', $isEdit ? $extension->webhook_url : '') }}" placeholder="https://…/webhook">
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- Sidebar --}}
    <div class="col-4" style="padding:0 0 0 12px;">

      {{-- Catégorie & Statut --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-tag"></i> {{ __('extensions::extensions.superadmin.form.classification') }}</h3>
        <div class="form-group">
          <label class="form-label">{{ __('extensions::extensions.common.category') }} <span class="required">*</span></label>
          <select name="category" class="form-control" required>
            @foreach($categories as $key => $cat)
              <option value="{{ $key }}" {{ old('category', $isEdit ? $extension->category : 'other') === $key ? 'selected' : '' }}>
                {{ $cat['label'] }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">{{ __('extensions::extensions.common.status') }} <span class="required">*</span></label>
          <select name="status" class="form-control" required>
            @foreach($statuses as $key => $label)
              <option value="{{ $key }}" {{ old('status', $isEdit ? $extension->status : 'active') === $key ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">{{ __('extensions::extensions.superadmin.form.sort_order') }}</label>
          <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $isEdit ? $extension->sort_order : 0) }}" min="0">
        </div>
      </div>

      {{-- Tarification --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-euro-sign"></i> {{ __('extensions::extensions.superadmin.form.pricing') }}</h3>
        <div class="form-group">
          <label class="form-label">{{ __('extensions::extensions.superadmin.form.pricing_type') }} <span class="required">*</span></label>
          <select name="pricing_type" class="form-control" id="pricingType" onchange="togglePricing()" required>
            @foreach($pricingTypes as $key => $label)
              <option value="{{ $key }}" {{ old('pricing_type', $isEdit ? $extension->pricing_type : 'free') === $key ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>
        <div id="priceFields" style="{{ old('pricing_type', $isEdit ? $extension->pricing_type : 'free') === 'free' ? 'display:none;' : '' }}">
          <div class="form-group">
            <label class="form-label">{{ __('extensions::extensions.superadmin.form.monthly_price') }}</label>
            <div class="input-group input-right">
              <input type="number" name="price" class="form-control" value="{{ old('price', $isEdit ? $extension->price : 0) }}" min="0" step="0.01">
              <i class="fas fa-euro-sign input-icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('extensions::extensions.superadmin.form.annual_price') }} <span class="hint">({{ __('extensions::extensions.superadmin.form.annual_price_hint') }})</span></label>
            <div class="input-group input-right">
              <input type="number" name="yearly_price" class="form-control" value="{{ old('yearly_price', $isEdit ? $extension->yearly_price : '') }}" min="0" step="0.01">
              <i class="fas fa-euro-sign input-icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('extensions::extensions.common.billing_cycle') }}</label>
            <select name="billing_cycle" class="form-control">
              <option value="">{{ __('extensions::extensions.common.select') }}</option>
              @foreach($billingCycles as $key => $label)
                <option value="{{ $key }}" {{ old('billing_cycle', $isEdit ? $extension->billing_cycle : '') === $key ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-top:1px solid var(--c-ink-05);margin-top:4px;">
          <div>
            <div style="font-size:13.5px;font-weight:var(--fw-medium);">{{ __('extensions::extensions.superadmin.form.trial_enabled') }}</div>
            <div style="font-size:12px;color:var(--c-ink-40);">{{ __('extensions::extensions.superadmin.form.trial_enabled_desc') }}</div>
          </div>
          <label style="position:relative;width:44px;height:24px;cursor:pointer;">
            <input type="checkbox" name="has_trial" id="hasTrial" value="1"
                   {{ old('has_trial', $isEdit ? $extension->has_trial : false) ? 'checked' : '' }}
                   onchange="toggleTrial(this.checked)"
                   style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;">
            <div id="trialTrack" style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ old('has_trial', $isEdit ? $extension->has_trial : false) ? 'var(--c-accent)' : 'var(--c-ink-10)' }};">
              <div style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;transition:transform .2s;box-shadow:var(--shadow-sm);{{ old('has_trial', $isEdit ? $extension->has_trial : false) ? 'transform:translateX(20px);' : 'transform:translateX(3px);' }}"></div>
            </div>
          </label>
        </div>
        <div id="trialDaysField" style="{{ old('has_trial', $isEdit ? $extension->has_trial : false) ? '' : 'display:none;' }}">
          <div class="form-group" style="margin-top:10px;">
            <label class="form-label">{{ __('extensions::extensions.superadmin.form.trial_days') }}</label>
            <input type="number" name="trial_days" class="form-control" value="{{ old('trial_days', $isEdit ? $extension->trial_days : 14) }}" min="1" max="365">
          </div>
        </div>
      </div>

      {{-- Badges & Options --}}
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-certificate"></i> {{ __('extensions::extensions.superadmin.form.badges_options') }}</h3>
        @foreach([
          ['name'=>'is_featured', 'label'=>__('extensions::extensions.superadmin.form.featured'), 'desc'=>__('extensions::extensions.superadmin.form.featured_desc')],
          ['name'=>'is_new',      'label'=>__('extensions::extensions.superadmin.form.new_label'), 'desc'=>__('extensions::extensions.superadmin.form.new_desc')],
          ['name'=>'is_verified', 'label'=>__('extensions::extensions.superadmin.form.verified'), 'desc'=>__('extensions::extensions.superadmin.form.verified_desc')],
          ['name'=>'is_official', 'label'=>__('extensions::extensions.superadmin.form.official'),       'desc'=>__('extensions::extensions.superadmin.form.official_desc')],
        ] as $opt)
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--c-ink-05);">
          <div>
            <div style="font-size:13.5px;font-weight:var(--fw-medium);">{{ $opt['label'] }}</div>
            <div style="font-size:12px;color:var(--c-ink-40);">{{ $opt['desc'] }}</div>
          </div>
          @php $optVal = old($opt['name'], $isEdit ? $extension->{$opt['name']} : false); @endphp
          <label style="position:relative;width:44px;height:24px;cursor:pointer;">
            <input type="checkbox" name="{{ $opt['name'] }}" value="1" {{ $optVal ? 'checked' : '' }}
                   onchange="this.nextElementSibling.style.background=this.checked?'var(--c-accent)':'var(--c-ink-10)'; this.nextElementSibling.querySelector('div').style.transform=this.checked?'translateX(20px)':'translateX(3px)'"
                   style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;">
            <div style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ $optVal ? 'var(--c-accent)' : 'var(--c-ink-10)' }};">
              <div style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;transition:transform .2s;box-shadow:var(--shadow-sm);{{ $optVal ? 'transform:translateX(20px);' : 'transform:translateX(3px);' }}"></div>
            </div>
          </label>
        </div>
        @endforeach
      </div>

      {{-- Actions --}}
      <div class="form-section">
        <div style="display:flex;flex-direction:column;gap:10px;">
          <button type="submit" class="btn btn-primary" id="submitBtn" style="justify-content:center;">
            <i class="fas fa-check"></i> {{ $isEdit ? __('extensions::extensions.common.save') : __('extensions::extensions.actions.create_extension') }}
          </button>
          <a href="{{ $isEdit ? route('superadmin.extensions.show', $extension) : route('superadmin.extensions.index') }}" class="btn btn-secondary" style="justify-content:center;">
            <i class="fas fa-times"></i> {{ __('extensions::extensions.common.cancel') }}
          </a>
        </div>
      </div>

    </div>
  </div>

</form>

@endsection

@push('scripts')
<script>
function togglePricing() {
  const type = document.getElementById('pricingType').value;
  document.getElementById('priceFields').style.display = type === 'free' ? 'none' : '';
}

function toggleTrial(checked) {
  document.getElementById('trialDaysField').style.display = checked ? '' : 'none';
  document.getElementById('trialTrack').style.background = checked ? 'var(--c-accent)' : 'var(--c-ink-10)';
  document.getElementById('trialTrack').querySelector('div').style.transform = checked ? 'translateX(20px)' : 'translateX(3px)';
}

function updateIconPreview(iconClass) {
  const icon  = iconClass || document.querySelector('input[name=icon]')?.value || 'fa-puzzle-piece';
  const color = document.getElementById('iconBgColor')?.value || '#3b82f6';
  const wrap  = document.getElementById('iconPreview');
  const el    = document.getElementById('previewIcon');
  if (wrap) wrap.style.background = color + '22';
  if (el)  { el.className = `fas ${icon}`; el.style.color = color; }
}

document.addEventListener('DOMContentLoaded', () => {
  updateIconPreview();

  ajaxForm('extForm', {
    onSuccess: (data) => {
      Toast.success(@json($isEdit ? __('extensions::extensions.superadmin.form.updated_success') : __('extensions::extensions.superadmin.form.created_success')), data.message);
    }
  });
});
</script>
@endpush
