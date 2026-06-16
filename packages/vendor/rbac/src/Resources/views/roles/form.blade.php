@extends('layouts.global')

@section('title', isset($role) ? __('rbac::rbac.titles.edit_role') : __('rbac::rbac.titles.new_role'))

@section('breadcrumb')
  <a href="{{ route('rbac.roles.index') }}">{{ __('rbac::rbac.breadcrumbs.roles_permissions') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ isset($role) ? __('rbac::rbac.breadcrumbs.edit_role') : __('rbac::rbac.breadcrumbs.new_role') }}</span>
@endsection

@section('content')
@php
  $isEdit = isset($role);
  $formAction = $isEdit ? route('rbac.roles.update', $role) : route('rbac.roles.store');
  $activePerms = $isEdit ? $role->permissions->pluck('name')->toArray() : [];
  $systemColors = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777','#0f172a'];
  $selectedColor = old('color', $isEdit ? ($role->color ?? '#64748b') : '#2563eb');
  $summaryDefs = collect($permissionsGrouped)->map(fn ($group) => ['label' => $group['label'], 'icon' => $group['icon']]);
  $roleFormI18n = [
      'preview' => __('rbac::rbac.labels.preview'),
      'noneSelected' => __('rbac::rbac.labels.none_selected'),
      'selectedPermissions' => __('rbac::rbac.labels.selected_permissions', ['count' => ':count']),
      'total' => __('rbac::rbac.labels.total', ['count' => ':count']),
      'roleCreated' => __('rbac::rbac.toasts.role_created'),
      'roleUpdated' => __('rbac::rbac.toasts.role_updated'),
  ];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $isEdit ? __('rbac::rbac.headings.edit_role') : __('rbac::rbac.headings.new_role') }}</h1>
    <p>{{ $isEdit ? ($role->label ?? $role->name) : __('rbac::rbac.subtitles.new_role') }}</p>
  </div>
  <a href="{{ $isEdit ? route('rbac.roles.show', $role) : route('rbac.roles.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> {{ __('rbac::rbac.buttons.back') }}
  </a>
</div>

@if($isEdit && $role->is_system)
<div style="background:var(--c-warning-lt);border:1px solid #fcd34d;border-radius:var(--r-md);padding:12px 16px;margin-bottom:20px;font-size:13px;color:#92400e;display:flex;gap:10px;align-items:center;">
  <i class="fas fa-lock"></i>
  <span>{{ __('rbac::rbac.subtitles.system_role_warning') }}</span>
</div>
@endif

<form id="roleForm" action="{{ $formAction }}" method="POST">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <div class="row" style="align-items:flex-start;">
    <div class="col-8" style="padding:0 12px 0 0;">
      <div style="background:var(--surface-0);border:1px solid var(--c-ink-05);border-radius:var(--r-xl);padding:20px 24px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div>
          <div style="font-weight:var(--fw-semi);font-size:14px;color:var(--c-ink);">{{ __('rbac::rbac.headings.quick_permissions_selection') }}</div>
          <div style="font-size:12.5px;color:var(--c-ink-40);margin-top:2px;" id="checkedCountLabel">0 permission(s) sélectionnée(s)</div>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAll(true)">
            <i class="fas fa-check-double"></i> {{ __('rbac::rbac.buttons.select_all') }}
          </button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="toggleAll(false)">
            <i class="fas fa-times"></i> {{ __('rbac::rbac.buttons.deselect_all') }}
          </button>
        </div>
      </div>

      @foreach($permissionsGrouped as $groupKey => $group)
      <div class="form-section" style="margin-bottom:16px;padding:0;overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:var(--surface-1);border-bottom:1px solid var(--c-ink-05);cursor:pointer;" onclick="toggleGroup('{{ $groupKey }}')">
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:34px;height:34px;background:var(--c-accent-lt);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;">
              <i class="fas {{ $group['icon'] }}" style="color:var(--c-accent);font-size:14px;"></i>
            </div>
            <div>
              <div style="font-weight:var(--fw-semi);font-size:14px;color:var(--c-ink);">{{ $group['label'] }}</div>
              <div style="font-size:12px;color:var(--c-ink-40);">
                <span id="group-count-{{ $groupKey }}">0</span> / {{ count($group['permissions']) }} permission(s) activée(s)
              </div>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:10px;">
            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation();toggleGroup('{{ $groupKey }}', true)">
              {{ __('rbac::rbac.buttons.enable_all') }}
            </button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation();toggleGroup('{{ $groupKey }}', false)">
              {{ __('rbac::rbac.buttons.disable_all') }}
            </button>
            <i class="fas fa-chevron-down" id="chevron-{{ $groupKey }}" style="color:var(--c-ink-20);font-size:12px;transition:transform .2s;"></i>
          </div>
        </div>

        <div id="group-{{ $groupKey }}" style="padding:8px 0;">
          @foreach($group['permissions'] as $permission)
          @php
            $isChecked = in_array($permission->name, $activePerms, true);
            $permissionLabel = $permission->display_label ?: config("rbac.permission_groups.{$groupKey}.permissions.{$permission->name}", $permission->name);
          @endphp
          <label style="display:flex;align-items:center;justify-content:space-between;padding:12px 24px;cursor:pointer;transition:background var(--dur-fast);" class="perm-row" data-group="{{ $groupKey }}" onmouseover="this.style.background='var(--c-accent-xl)'" onmouseout="this.style.background=''">
            <div style="display:flex;align-items:center;gap:12px;">
              <div style="width:8px;height:8px;border-radius:50%;background:var(--c-ink-10);flex-shrink:0;" class="perm-dot"></div>
              <div>
                <div style="font-size:13.5px;font-weight:var(--fw-medium);color:var(--c-ink);">
                  {{ $permissionLabel }}
                </div>
              </div>
            </div>
            <div style="position:relative;width:44px;height:24px;flex-shrink:0;">
              <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="perm-checkbox" data-group="{{ $groupKey }}" id="perm_{{ str_replace('.', '_', $permission->name) }}" {{ $isChecked ? 'checked' : '' }} onchange="onPermChange(this)" style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;">
              <div class="toggle-track-rbac" style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ $isChecked ? 'var(--c-accent)' : 'var(--c-ink-10)' }};">
                <div class="toggle-knob-rbac" style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;transition:transform .2s;box-shadow:var(--shadow-sm);transform:translateX({{ $isChecked ? '20px' : '3px' }});"></div>
              </div>
            </div>
          </label>
          @endforeach
        </div>
      </div>
      @endforeach
    </div>

    <div class="col-4" style="padding:0 0 0 12px;">
      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-shield"></i> {{ __('rbac::rbac.headings.role_identity') }}</h3>

        <div class="form-group">
          <label class="form-label">{{ __('rbac::rbac.labels.role_name') }} <span class="required">*</span></label>
          <div class="input-group">
            <i class="fas fa-shield-halved input-icon"></i>
            <input type="text" name="label" class="form-control" value="{{ old('label', $isEdit ? $role->label : '') }}" placeholder="{{ __('rbac::rbac.labels.role_name_placeholder') }}" {{ ($isEdit && $role->is_system) ? 'readonly' : '' }} required>
          </div>
          @if($isEdit)
          <span class="form-hint">{{ __('rbac::rbac.labels.internal_slug') }} : <code>{{ $role->name }}</code></span>
          @else
          <span class="form-hint">{{ __('rbac::rbac.labels.slug_auto') }}</span>
          @endif
        </div>

        <div class="form-group">
          <label class="form-label">{{ __('rbac::rbac.table.description') }}</label>
          <textarea name="description" class="form-control" rows="2" placeholder="{{ __('rbac::rbac.labels.description_placeholder') }}" {{ ($isEdit && $role->is_system) ? 'readonly' : '' }}>{{ old('description', $isEdit ? $role->description : '') }}</textarea>
        </div>

        <div class="form-group">
          <label class="form-label">{{ __('rbac::rbac.labels.identification_color') }}</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
            @foreach($systemColors as $color)
            <label style="cursor:pointer;">
              <input type="radio" name="color" value="{{ $color }}" {{ $selectedColor === $color ? 'checked' : '' }} style="display:none;" class="color-radio" onchange="updateColorPreview('{{ $color }}')">
              <div class="color-swatch" data-color="{{ $color }}" style="width:28px;height:28px;border-radius:50%;background:{{ $color }};cursor:pointer;transition:transform .15s,box-shadow .15s;{{ $selectedColor === $color ? 'box-shadow:0 0 0 3px '.$color.'50,0 0 0 5px '.$color.'30;transform:scale(1.15);' : '' }}" onclick="selectColor('{{ $color }}')"></div>
            </label>
            @endforeach
            <label style="cursor:pointer;position:relative;">
              <input type="color" id="customColor" style="position:absolute;opacity:0;width:28px;height:28px;cursor:pointer;" onchange="selectColor(this.value)">
              <div style="width:28px;height:28px;border-radius:50%;background:conic-gradient(red,orange,yellow,green,blue,violet,red);cursor:pointer;display:flex;align-items:center;justify-content:center;" title="{{ __('rbac::rbac.labels.custom_color') }}">
                <i class="fas fa-plus" style="font-size:10px;color:#fff;text-shadow:0 0 2px rgba(0,0,0,.5);"></i>
              </div>
            </label>
          </div>
          <div style="margin-top:12px;display:flex;align-items:center;gap:10px;">
            <div id="rolePreview" style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:99px;font-size:12px;font-weight:600;transition:all .2s;">
              <i class="fas fa-shield-halved" style="font-size:10px;"></i>
              <span id="previewLabel">{{ __('rbac::rbac.labels.preview') }}</span>
            </div>
          </div>
        </div>

        @if($isEdit && !$role->is_system)
        <div class="form-group" style="margin-bottom:0;">
          <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;">
            <div>
              <div style="font-size:13.5px;font-weight:var(--fw-medium);">{{ __('rbac::rbac.labels.active_role') }}</div>
              <div style="font-size:12px;color:var(--c-ink-40);">{{ __('rbac::rbac.subtitles.role_active_help') }}</div>
            </div>
            <label style="position:relative;width:44px;height:24px;">
              <input type="checkbox" name="is_active" value="1" {{ old('is_active', $role->is_active ?? true) ? 'checked' : '' }} style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0;z-index:1;" onchange="document.getElementById('activeTrack').style.background=this.checked?'var(--c-accent)':'var(--c-ink-10)'">
              <div id="activeTrack" style="position:absolute;inset:0;border-radius:12px;transition:background .2s;background:{{ old('is_active', $role->is_active ?? true) ? 'var(--c-accent)' : 'var(--c-ink-10)' }};">
                <div style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;left:3px;transition:transform .2s;box-shadow:var(--shadow-sm);{{ old('is_active', $role->is_active ?? true) ? 'transform:translateX(20px);' : '' }}"></div>
              </div>
            </label>
          </div>
        </div>
        @endif
      </div>

      <div class="form-section" style="margin-bottom:16px;">
        <h3 class="form-section-title"><i class="fas fa-key"></i> {{ __('rbac::rbac.headings.permissions_summary') }}</h3>
        <div id="permSummary" style="font-size:13px;color:var(--c-ink-60);">
          <div style="color:var(--c-ink-40);font-style:italic;">{{ __('rbac::rbac.labels.none_selected') }}</div>
        </div>
      </div>

      <div class="form-section">
        <div style="display:flex;flex-direction:column;gap:10px;">
          <button type="submit" class="btn btn-primary" id="submitBtn" style="justify-content:center;">
            <i class="fas fa-check"></i> {{ $isEdit ? __('rbac::rbac.buttons.save_changes') : __('rbac::rbac.buttons.create_role') }}
          </button>
          <a href="{{ $isEdit ? route('rbac.roles.show', $role) : route('rbac.roles.index') }}" class="btn btn-secondary" style="justify-content:center;">
            <i class="fas fa-times"></i> {{ __('rbac::rbac.buttons.cancel') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
window.RBAC_ROLE_FORM_I18N = @json($roleFormI18n);
window.RBAC_ROLE_GROUPS = @json($summaryDefs);
let activeColor = @json($selectedColor);
updateColorPreview(activeColor);

function selectColor(color) {
  activeColor = color;
  document.querySelectorAll('input[name=color]').forEach((radio) => {
    radio.checked = radio.value === color;
  });
  document.querySelectorAll('.color-swatch').forEach((swatch) => {
    swatch.style.boxShadow = '';
    swatch.style.transform = '';
  });
  const swatch = document.querySelector(`.color-swatch[data-color="${color}"]`);
  if (swatch) {
    swatch.style.boxShadow = `0 0 0 3px ${color}50,0 0 0 5px ${color}30`;
    swatch.style.transform = 'scale(1.15)';
  }
  updateColorPreview(color);

  let hidden = document.getElementById('colorHidden');
  if (!hidden) {
    hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'color';
    hidden.id = 'colorHidden';
    document.getElementById('roleForm').appendChild(hidden);
  }
  hidden.value = color;
}

function updateColorPreview(color) {
  const preview = document.getElementById('rolePreview');
  const label = document.getElementById('previewLabel');
  const nameInput = document.querySelector('input[name=label]');
  if (preview) {
    preview.style.background = `${color}22`;
    preview.style.color = color;
    preview.style.border = `1px solid ${color}44`;
  }
  if (label && nameInput) {
    label.textContent = nameInput.value || window.RBAC_ROLE_FORM_I18N.preview;
  }
}

document.querySelector('input[name=label]')?.addEventListener('input', function () {
  const label = document.getElementById('previewLabel');
  if (label) {
    label.textContent = this.value || window.RBAC_ROLE_FORM_I18N.preview;
  }
});

function toggleGroup(groupKey, forceState = undefined) {
  const container = document.getElementById(`group-${groupKey}`);
  const chevron = document.getElementById(`chevron-${groupKey}`);

  if (forceState !== undefined) {
    document.querySelectorAll(`.perm-checkbox[data-group="${groupKey}"]`).forEach((checkbox) => {
      checkbox.checked = forceState;
      updateToggleUI(checkbox);
    });
    updateGroupCount(groupKey);
    updateGlobalCount();
    updatePermSummary();
    return;
  }

  const isHidden = container.style.display === 'none';
  container.style.display = isHidden ? '' : 'none';
  if (chevron) {
    chevron.style.transform = isHidden ? '' : 'rotate(-90deg)';
  }
}

function toggleAll(state) {
  document.querySelectorAll('.perm-checkbox').forEach((checkbox) => {
    checkbox.checked = state;
    updateToggleUI(checkbox);
  });
  document.querySelectorAll('[id^="group-count-"]').forEach((element) => {
    const groupKey = element.id.replace('group-count-', '');
    const total = document.querySelectorAll(`.perm-checkbox[data-group="${groupKey}"]`).length;
    element.textContent = state ? total : 0;
  });
  updateGlobalCount();
  updatePermSummary();
}

function onPermChange(checkbox) {
  updateToggleUI(checkbox);
  updateGroupCount(checkbox.dataset.group);
  updateGlobalCount();
  updatePermSummary();
}

function updateToggleUI(checkbox) {
  const track = checkbox.nextElementSibling;
  const knob = track?.querySelector('.toggle-knob-rbac');
  const dot = checkbox.closest('label')?.querySelector('.perm-dot');
  if (track) track.style.background = checkbox.checked ? 'var(--c-accent)' : 'var(--c-ink-10)';
  if (knob) knob.style.transform = checkbox.checked ? 'translateX(20px)' : 'translateX(3px)';
  if (dot) dot.style.background = checkbox.checked ? 'var(--c-accent)' : 'var(--c-ink-10)';
}

function updateGroupCount(groupKey) {
  const checked = document.querySelectorAll(`.perm-checkbox[data-group="${groupKey}"]:checked`).length;
  const element = document.getElementById(`group-count-${groupKey}`);
  if (element) element.textContent = checked;
}

function updateGlobalCount() {
  const total = document.querySelectorAll('.perm-checkbox:checked').length;
  const element = document.getElementById('checkedCountLabel');
  if (element) element.textContent = window.RBAC_ROLE_FORM_I18N.selectedPermissions.replace(':count', total);
}

function updatePermSummary() {
  const summary = document.getElementById('permSummary');
  if (!summary) return;

  const groups = {};
  document.querySelectorAll('.perm-checkbox:checked').forEach((checkbox) => {
    const group = checkbox.dataset.group;
    groups[group] = (groups[group] || 0) + 1;
  });

  if (!Object.keys(groups).length) {
    summary.innerHTML = `<div style="color:var(--c-ink-40);font-style:italic;">${window.RBAC_ROLE_FORM_I18N.noneSelected}</div>`;
    return;
  }

  summary.innerHTML = Object.entries(groups).map(([key, count]) => {
    const definition = window.RBAC_ROLE_GROUPS[key] || { label: key, icon: 'fa-key' };
    return `<div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--c-ink-05);font-size:13px;">
      <div style="display:flex;align-items:center;gap:8px;">
        <i class="fas ${definition.icon}" style="color:var(--c-accent);width:14px;text-align:center;font-size:12px;"></i>
        ${definition.label}
      </div>
      <span style="background:var(--c-success-lt);color:var(--c-success);padding:2px 8px;border-radius:99px;font-size:11px;font-weight:600;">${count}</span>
    </div>`;
  }).join('') + `<div style="padding-top:10px;font-size:12px;color:var(--c-ink-40);">${window.RBAC_ROLE_FORM_I18N.total.replace(':count', Object.values(groups).reduce((sum, value) => sum + value, 0))}</div>`;
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.perm-checkbox').forEach((checkbox) => updateToggleUI(checkbox));
  @foreach($permissionsGrouped as $groupKey => $group)
  updateGroupCount('{{ $groupKey }}');
  @endforeach
  updateGlobalCount();
  updatePermSummary();

  ajaxForm('roleForm', {
    onSuccess: (data) => {
      Toast.success(
        @json($isEdit ? __('rbac::rbac.toasts.role_updated') : __('rbac::rbac.toasts.role_created')),
        data.message
      );
    }
  });
});
</script>
@endpush
