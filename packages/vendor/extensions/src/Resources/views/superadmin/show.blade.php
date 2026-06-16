@extends('layouts.global')

@section('title', $extension->name)

@section('breadcrumb')
  <a href="{{ route('superadmin.extensions.index') }}">{{ __('extensions::extensions.common.extensions') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $extension->name }}</span>
@endsection

@section('content')

@php $color = $extension->category_color; @endphp

<div class="page-header">
  <div class="page-header-left" style="display:flex;align-items:center;gap:16px;">
    <div style="width:56px;height:56px;border-radius:16px;background:{{ $color }}18;display:flex;align-items:center;justify-content:center;font-size:26px;border:1px solid {{ $color }}22;flex-shrink:0;">
      @if($extension->icon)
        <i class="{{ $extension->icon }}" style="color:{{ $color }};"></i>
      @else
        <i class="fas {{ $extension->category_icon }}" style="color:{{ $color }};"></i>
      @endif
    </div>
    <div>
      <h1 style="margin-bottom:6px;">{{ $extension->name }}</h1>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <span class="badge badge-{{ $extension->status === 'active' ? 'actif' : 'inactif' }}">{{ $extension->status_label }}</span>
        @if($extension->is_featured)
          <span style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;"><i class="fas fa-star" style="font-size:9px;"></i> {{ __('extensions::extensions.common.featured') }}</span>
        @endif
        @if($extension->is_official)
          <span style="background:#f3e8ff;color:#7c3aed;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;"><i class="fas fa-certificate" style="font-size:9px;"></i> {{ __('extensions::extensions.common.official') }}</span>
        @endif
        <span style="font-size:12px;color:var(--c-ink-40);">v{{ $extension->version }} · {{ $extension->slug }}</span>
      </div>
    </div>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-secondary" onclick="toggleFeatured()">
      <i class="fas fa-star" style="color:{{ $extension->is_featured ? '#f59e0b' : 'var(--c-ink-40)' }};"></i>
      {{ $extension->is_featured ? __('extensions::extensions.actions.feature_off') : __('extensions::extensions.actions.feature_on') }}
    </button>
    <button class="btn btn-secondary" onclick="toggleStatus()">
      <i class="fas fa-{{ $extension->status === 'active' ? 'pause' : 'play' }}"></i>
      {{ $extension->status === 'active' ? __('extensions::extensions.superadmin.show.toggle_deactivate') : __('extensions::extensions.superadmin.show.toggle_activate') }}
    </button>
    <a href="{{ route('superadmin.extensions.edit', $extension) }}" class="btn btn-primary">
      <i class="fas fa-pen"></i> {{ __('extensions::extensions.common.edit') }}
    </a>
  </div>
</div>

{{-- KPIs --}}
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent);"><i class="fas fa-download"></i></div>
    <div class="stat-body"><div class="stat-value">{{ number_format($extension->installs_count) }}</div><div class="stat-label">{{ __('extensions::extensions.superadmin.show.installs') }}</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success);"><i class="fas fa-plug"></i></div>
    <div class="stat-body"><div class="stat-value">{{ number_format($extension->active_installs_count) }}</div><div class="stat-label">{{ __('extensions::extensions.superadmin.show.active') }}</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fef3c7;color:#92400e;"><i class="fas fa-star"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $extension->rating ?: __('extensions::extensions.common.none_short') }}</div><div class="stat-label">{{ __('extensions::extensions.superadmin.show.rating') }}</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info);"><i class="fas fa-comments"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $extension->approved_reviews_count }}</div><div class="stat-label">{{ __('extensions::extensions.superadmin.show.reviews') }}</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dcfce7;color:#15803d;"><i class="fas fa-euro-sign"></i></div>
    <div class="stat-body">
      <div class="stat-value">{{ $extension->is_free ? __('extensions::extensions.common.free') : number_format($extension->price,2).' '.$extension->currency }}</div>
      <div class="stat-label">{{ $extension->is_free ? __('extensions::extensions.superadmin.show.pricing_label') : config("extensions.billing_cycles.{$extension->billing_cycle}", '') }}</div>
    </div>
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">

    {{-- Activations tenants --}}
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">{{ __('extensions::extensions.superadmin.show.activations_title') }}</span>
        <span class="table-count">{{ $activations->total() }}</span>
      </div>
      <table class="crm-table">
        <thead>
          <tr>
            <th>{{ __('extensions::extensions.superadmin.show.column_tenant') }}</th>
            <th>{{ __('extensions::extensions.superadmin.show.column_status') }}</th>
            <th>{{ __('extensions::extensions.superadmin.show.column_activated_by') }}</th>
            <th>{{ __('extensions::extensions.superadmin.show.column_date') }}</th>
            <th>{{ __('extensions::extensions.superadmin.show.column_price_paid') }}</th>
            <th style="text-align:right;padding-right:20px">{{ __('extensions::extensions.superadmin.show.column_actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($activations as $act)
          @php
            $stMap = ['active'=>['actif', __('extensions::extensions.status.active')],'trial'=>['info', __('extensions::extensions.status.trial')],'inactive'=>['inactif', __('extensions::extensions.status.inactive')],'suspended'=>['inactif', __('extensions::extensions.status.suspended')],'pending'=>['warning', __('extensions::extensions.status.pending')]];
            $stCls = $stMap[$act->status] ?? ['secondary', $act->status];
          @endphp
          <tr>
            <td style="font-weight:var(--fw-semi);">{{ $act->tenant->name ?? __('extensions::extensions.common.none_short') }}</td>
            <td><span class="badge badge-{{ $stCls[0] }}">{{ $stCls[1] }}</span></td>
            <td style="font-size:13px;color:var(--c-ink-60);">{{ $act->activatedByUser->name ?? __('extensions::extensions.common.none_short') }}</td>
            <td style="font-size:13px;color:var(--c-ink-60);">{{ $act->activated_at?->format('d/m/Y') ?? __('extensions::extensions.common.none_short') }}</td>
            <td style="font-size:13px;">
              @if($act->price_paid > 0)
                {{ number_format($act->price_paid,2).' '.$act->currency }}
              @else
                <span style="color:var(--c-ink-40);">{{ __('extensions::extensions.common.free') }}</span>
              @endif
            </td>
            <td>
              <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
                @if(in_array($act->status, ['active','trial']))
                  <button class="btn-icon danger" onclick="suspendTenantAct({{ $act->id }})" title="{{ __('extensions::extensions.actions.suspend') }}">
                    <i class="fas fa-ban"></i>
                  </button>
                @elseif($act->status === 'suspended')
                  <button class="btn-icon" onclick="restoreTenantAct({{ $act->id }})" title="{{ __('extensions::extensions.actions.restore') }}">
                    <i class="fas fa-check-circle"></i>
                  </button>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--c-ink-40);">{{ __('extensions::extensions.superadmin.show.no_activations') }}</td></tr>
          @endforelse
        </tbody>
      </table>
      @if($activations->hasPages())
      <div class="table-pagination">
        {{ $activations->links() }}
      </div>
      @endif
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-circle-info"></i><h3>{{ __('extensions::extensions.superadmin.show.info_title') }}</h3></div>
      <div class="info-card-body">
        <div class="info-row"><span class="info-row-label">{{ __('extensions::extensions.common.category') }}</span>
          <span style="background:{{ $color }}18;color:{{ $color }};padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">
            <i class="fas {{ $extension->category_icon }}" style="font-size:10px;margin-right:4px;"></i>{{ $extension->category_label }}
          </span>
        </div>
        <div class="info-row"><span class="info-row-label">{{ __('extensions::extensions.common.pricing') }}</span><span class="info-row-value">{{ $extension->pricing_label }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('extensions::extensions.common.trial') }}</span><span class="info-row-value">{{ $extension->has_trial ? $extension->trial_days.' '.__('extensions::extensions.common.day_unit', ['count' => $extension->trial_days]) : __('extensions::extensions.common.no') }}</span></div>
        @if($extension->developer_name)
        <div class="info-row"><span class="info-row-label">{{ __('extensions::extensions.common.editor') }}</span><span class="info-row-value">{{ $extension->developer_name }}</span></div>
        @endif
        <div class="info-row"><span class="info-row-label">{{ __('extensions::extensions.common.order') }}</span><span class="info-row-value">{{ $extension->sort_order }}</span></div>
        <div class="info-row"><span class="info-row-label">{{ __('extensions::extensions.superadmin.show.created_at') }}</span><span class="info-row-value">{{ $extension->created_at->format('d/m/Y') }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-bolt"></i><h3>{{ __('extensions::extensions.superadmin.show.quick_actions') }}</h3></div>
      <div class="info-card-body" style="display:flex;flex-direction:column;gap:8px;">
        <a href="{{ route('superadmin.extensions.edit', $extension) }}" class="btn btn-secondary" style="justify-content:flex-start;">
          <i class="fas fa-pen"></i> {{ __('extensions::extensions.superadmin.show.edit_extension') }}
        </a>
        <button class="btn btn-secondary" style="justify-content:flex-start;" onclick="toggleFeatured()">
          <i class="fas fa-star" style="color:{{ $extension->is_featured ? '#f59e0b' : 'var(--c-ink-40)' }};"></i>
          {{ $extension->is_featured ? __('extensions::extensions.actions.feature_remove') : __('extensions::extensions.actions.feature_on') }}
        </button>
        <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-danger);border-color:var(--c-danger-lt);" onclick="deleteExt(@json($extension->name))">
          <i class="fas fa-trash"></i> {{ __('extensions::extensions.common.delete') }}
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Suspend modal --}}
<div class="modal-overlay" id="suspendModal">
  <div class="modal modal-sm">
    <div class="modal-header"><div class="modal-title">{{ __('extensions::extensions.superadmin.show.suspend_title') }}</div><button class="modal-close" data-modal-close>&times;</button></div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">{{ __('extensions::extensions.common.reason') }} <span class="required">*</span></label>
        <textarea id="suspendReason" class="form-control" rows="3" placeholder="{{ __('extensions::extensions.superadmin.show.suspend_reason_placeholder') }}"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('extensions::extensions.common.cancel') }}</button>
      <button class="btn btn-danger" id="confirmSuspend">{{ __('extensions::extensions.actions.suspend') }}</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
let _suspendActId = null;
const EXT_SHOW_ROUTES = {
  featured: @json(route('superadmin.extensions.featured', $extension)),
  status: @json(route('superadmin.extensions.status', $extension)),
  destroy: @json(route('superadmin.extensions.destroy', $extension)),
  index: @json(route('superadmin.extensions.index')),
  suspend: @json(route('superadmin.extensions.activations.suspend', ['activation' => '__ACTIVATION_ID__'])),
  restore: @json(route('superadmin.extensions.activations.restore', ['activation' => '__ACTIVATION_ID__'])),
};

function activationRoute(template, id) {
  return String(template).replace('__ACTIVATION_ID__', encodeURIComponent(String(id)));
}

async function toggleFeatured() {
  const { ok, data } = await Http.post(EXT_SHOW_ROUTES.featured, {});
  if (ok) { Toast.success(@json(__('extensions::extensions.superadmin.show.toggle_featured_updated')), data.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(@json(__('extensions::extensions.common.error')), data.message);
}

async function toggleStatus() {
  const { ok, data } = await Http.post(EXT_SHOW_ROUTES.status, {});
  if (ok) { Toast.success(@json(__('extensions::extensions.superadmin.show.toggle_status_updated')), data.message); setTimeout(() => location.reload(), 800); }
  else Toast.error(@json(__('extensions::extensions.common.error')), data.message);
}

async function deleteExt(name) {
  Modal.confirm({
    title: @json(__('extensions::extensions.superadmin.show.delete_title', ['name' => ':name'])).replace(':name', name),
    message: @json(__('extensions::extensions.superadmin.show.delete_message')),
    confirmText: @json(__('extensions::extensions.common.delete')),
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete(EXT_SHOW_ROUTES.destroy);
      if (ok) { Toast.success(@json(__('extensions::extensions.superadmin.show.deleted')), data.message); setTimeout(() => window.location.href = EXT_SHOW_ROUTES.index, 900); }
      else Toast.error(@json(__('extensions::extensions.common.error')), data.message);
    }
  });
}

function suspendTenantAct(id) {
  _suspendActId = id;
  document.getElementById('suspendReason').value = '';
  Modal.open(document.getElementById('suspendModal'));
}

document.getElementById('confirmSuspend').addEventListener('click', async () => {
  const reason = document.getElementById('suspendReason').value.trim();
  if (!reason) { Toast.warning(@json(__('extensions::extensions.common.required')), @json(__('extensions::extensions.superadmin.show.reason_required'))); return; }
  const { ok, data } = await Http.post(activationRoute(EXT_SHOW_ROUTES.suspend, _suspendActId), { reason });
  Modal.close(document.getElementById('suspendModal'));
  if (ok) { Toast.success(@json(__('extensions::extensions.superadmin.show.suspended')), data.message); setTimeout(() => location.reload(), 900); }
  else Toast.error(@json(__('extensions::extensions.common.error')), data.message);
});

async function restoreTenantAct(id) {
  const { ok, data } = await Http.post(activationRoute(EXT_SHOW_ROUTES.restore, id), {});
  if (ok) { Toast.success(@json(__('extensions::extensions.superadmin.show.restored')), data.message); setTimeout(() => location.reload(), 900); }
  else Toast.error(@json(__('extensions::extensions.common.error')), data.message);
}
</script>
@endpush
