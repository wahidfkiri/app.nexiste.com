@extends('layouts.global')

@section('title', $user->name)

@section('breadcrumb')
  <a href="{{ route('users.index') }}">{{ __('user::users.breadcrumbs.team') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $user->name }}</span>
@endsection

@section('content')
@php
  $currentTenantId = (int) session('current_tenant_id', auth()->user()->tenant_id);
  $roleColors = ['owner' => '#7c3aed', 'admin' => '#2563eb', 'manager' => '#0891b2', 'user' => '#059669', 'viewer' => '#64748b'];
  $tenantRole = $user->tenantRole($currentTenantId);
  $roleColor = $roleColors[$user->role_in_tenant] ?? ($tenantRole?->color ?? '#64748b');
  $roleLabel = $tenantRole?->label ?? ($roles[$user->role_in_tenant] ?? $user->role_in_tenant);
  $statusCls = ['active' => 'actif', 'inactive' => 'inactif', 'invited' => 'info', 'suspended' => 'suspendu'][$user->status] ?? 'inactif';
  $statusLabel = config("user.user_statuses.{$user->status}", $user->status);
  $avatarColors = ['#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706', '#dc2626'];
  $avatarSeed = abs(crc32((string) ($user->name ?: $user->email ?: 'U')));
  $avatarColor = $avatarColors[$avatarSeed % count($avatarColors)] ?? '#2563eb';
  $avatarInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) ($user->name ?: 'U'), 0, 2));
  $permissions = $tenantRole?->permissions?->pluck('name')->all() ?? [];
  $permissionLabels = collect(config('rbac.permission_groups', []))
      ->flatMap(fn ($group) => $group['permissions'] ?? [])
      ->all();
  $formatPermissionLabel = static function (string $permission) use ($permissionLabels): string {
      return $permissionLabels[$permission]
          ?? \Illuminate\Support\Str::headline(str_replace(['.', '-', '_'], ' ', $permission));
  };
  $showI18n = [
      'suspendTitle' => __('user::users.confirmations.suspend_user_title', ['name' => $user->name]),
      'suspendMessage' => __('user::users.confirmations.suspend_user_message'),
      'suspendText' => __('user::users.actions.suspend'),
      'deleteTitle' => __('user::users.confirmations.delete_user_title', ['name' => $user->name]),
      'deleteMessage' => __('user::users.confirmations.delete_user_message'),
      'deleteText' => __('user::users.actions.delete'),
      'suspendSuccess' => __('user::users.messages.suspend_success'),
      'activateSuccess' => __('user::users.messages.activate_success'),
      'deleteSuccess' => __('user::users.messages.delete_success'),
      'error' => __('user::users.messages.error'),
  ];
@endphp

<div class="page-header">
  <div class="page-header-left" style="display:flex;align-items:center;gap:16px;">
    @if($user->avatar)
      <img src="{{ asset('storage/' . $user->avatar) }}" style="width:56px;height:56px;border-radius:var(--r-md);object-fit:cover;">
    @else
      <div style="width:56px;height:56px;border-radius:var(--r-md);background:{{ $avatarColor }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;flex-shrink:0;">
        {{ $avatarInitials }}
      </div>
    @endif
    <div>
      <h1 style="margin-bottom:6px;">
        {{ $user->name }}
        @if($user->is_tenant_owner)
          <span style="font-size:12px;background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:99px;margin-left:8px;font-weight:600;vertical-align:middle;">
            <i class="fas fa-crown"></i> {{ __('user::users.badges.owner') }}
          </span>
        @endif
      </h1>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span class="badge badge-{{ $statusCls }}">
          <span class="badge-dot" style="background:currentColor"></span>{{ $statusLabel }}
        </span>
        <span style="background:{{ $roleColor }}18;color:{{ $roleColor }};border:1px solid {{ $roleColor }}30;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">
          {{ $roleLabel }}
        </span>
        <span style="font-size:12px;color:var(--c-ink-40);">
          <i class="fas fa-calendar" style="margin-right:4px;"></i>{{ __('user::users.fields.created_at') }} {{ $user->created_at->format('M Y') }}
        </span>
      </div>
    </div>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
      <i class="fas fa-pen"></i> {{ __('user::users.actions.edit') }}
    </a>
    @if(!$user->is_tenant_owner && $user->id !== auth()->id())
      <div class="dropdown">
        <button class="btn btn-secondary" data-dropdown-toggle>
          <i class="fas fa-ellipsis"></i>
        </button>
        <div class="dropdown-menu">
          @if($user->status === 'active')
            <button class="dropdown-item danger" onclick="suspendUser()"><i class="fas fa-ban"></i> {{ __('user::users.actions.suspend') }}</button>
          @else
            <button class="dropdown-item" onclick="activateUser()"><i class="fas fa-check-circle"></i> {{ __('user::users.actions.activate') }}</button>
          @endif
          <div class="dropdown-divider"></div>
          <button class="dropdown-item danger" onclick="deleteUser()"><i class="fas fa-trash"></i> {{ __('user::users.actions.delete') }}</button>
        </div>
      </div>
    @endif
  </div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-8" style="padding:0 12px 0 0;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-address-card"></i>
        <h3>{{ __('user::users.headings.coordinates') }}</h3>
      </div>
      <div class="info-card-body">
        <div class="info-row">
          <span class="info-row-label"><i class="fas fa-envelope" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>{{ __('user::users.fields.email') }}</span>
          <span class="info-row-value"><a href="mailto:{{ $user->email }}" style="color:var(--c-accent);text-decoration:none;">{{ $user->email }}</a></span>
        </div>
        @if($user->phone)
          <div class="info-row">
            <span class="info-row-label"><i class="fas fa-phone" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>{{ __('user::users.fields.phone') }}</span>
            <span class="info-row-value"><a href="tel:{{ $user->phone }}" style="color:inherit;text-decoration:none;">{{ $user->phone }}</a></span>
          </div>
        @endif
        @if($user->job_title)
          <div class="info-row">
            <span class="info-row-label"><i class="fas fa-briefcase" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>{{ __('user::users.fields.job_title') }}</span>
            <span class="info-row-value">{{ $user->job_title }}</span>
          </div>
        @endif
        @if($user->department)
          <div class="info-row">
            <span class="info-row-label"><i class="fas fa-building" style="color:var(--c-accent);margin-right:6px;width:14px;text-align:center;"></i>{{ __('user::users.fields.department') }}</span>
            <span class="info-row-value">{{ $user->department }}</span>
          </div>
        @endif
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header">
        <i class="fas fa-shield-halved"></i>
        <h3>{{ __('user::users.headings.role_permissions', ['role' => $roleLabel]) }}</h3>
      </div>
      <div class="info-card-body">
        @if($user->role_in_tenant === 'owner')
          <div style="display:flex;align-items:center;gap:8px;padding:8px 0;font-size:13px;color:var(--c-success);">
            <i class="fas fa-circle-check"></i> {{ __('user::users.subtitles.total_access') }}
          </div>
        @elseif(count($permissions))
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;">
            @foreach($permissions as $permission)
              <span style="background:var(--c-accent-lt);color:var(--c-accent);padding:3px 10px;border-radius:var(--r-full);font-size:12px;font-weight:600;">
                <i class="fas fa-check" style="font-size:10px;margin-right:4px;"></i>{{ $formatPermissionLabel($permission) }}
              </span>
            @endforeach
          </div>
        @else
          <div style="font-size:13px;color:var(--c-ink-50);">{{ __('user::users.subtitles.no_role_permissions') }}</div>
        @endif
        <div style="margin-top:12px;font-size:12px;color:var(--c-ink-40);">
          <i class="fas fa-circle-info" style="margin-right:4px;"></i>
          {{ __('user::users.subtitles.permissions_context') }}
        </div>
      </div>
    </div>
  </div>

  <div class="col-4" style="padding:0 0 0 12px;">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header">
        <i class="fas fa-chart-bar"></i>
        <h3>{{ __('user::users.headings.account_information') }}</h3>
      </div>
      <div class="info-card-body">
        <div class="info-row">
          <span class="info-row-label">{{ __('user::users.fields.status') }}</span>
          <span class="badge badge-{{ $statusCls }}">{{ $statusLabel }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">{{ __('user::users.fields.role') }}</span>
          <span class="info-row-value" style="color:{{ $roleColor }};font-weight:var(--fw-semi);">{{ $roleLabel }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">{{ __('user::users.fields.type') }}</span>
          <span class="info-row-value">{{ $user->is_tenant_owner ? __('user::users.roles.owner') : __('user::users.badges.invited_member') }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">{{ __('user::users.exports.created_at') }}</span>
          <span class="info-row-value">{{ $user->created_at->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
          <span class="info-row-label">{{ __('user::users.fields.last_login') }}</span>
          <span class="info-row-value">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : __('user::users.exports.never') }}</span>
        </div>
        @if($user->last_login_ip)
          <div class="info-row">
            <span class="info-row-label">{{ __('user::users.fields.last_ip') }}</span>
            <span class="info-row-value" style="font-family: "DM Sans", sans-serif;font-size:12px;">{{ $user->last_login_ip }}</span>
          </div>
        @endif
      </div>
    </div>

    @if(!$user->is_tenant_owner && $user->id !== auth()->id())
      <div class="info-card">
        <div class="info-card-header">
          <i class="fas fa-bolt"></i>
          <h3>{{ __('user::users.headings.quick_actions') }}</h3>
        </div>
        <div class="info-card-body" style="display:flex;flex-direction:column;gap:8px;">
          <a href="mailto:{{ $user->email }}" class="btn btn-secondary" style="justify-content:flex-start;">
            <i class="fas fa-envelope"></i> {{ __('user::users.actions.send_email') }}
          </a>
          <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary" style="justify-content:flex-start;">
            <i class="fas fa-pen"></i> {{ __('user::users.actions.update_profile') }}
          </a>
          @if($user->status === 'active')
            <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-warning);border-color:var(--c-warning-lt);" onclick="suspendUser()">
              <i class="fas fa-ban"></i> {{ __('user::users.actions.suspend') }}
            </button>
          @else
            <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-success);border-color:var(--c-success-lt);" onclick="activateUser()">
              <i class="fas fa-check-circle"></i> {{ __('user::users.actions.activate') }}
            </button>
          @endif
          <button class="btn btn-secondary" style="justify-content:flex-start;color:var(--c-danger);border-color:var(--c-danger-lt);" onclick="deleteUser()">
            <i class="fas fa-trash"></i> {{ __('user::users.actions.delete') }}
          </button>
        </div>
      </div>
    @endif
  </div>
</div>

@endsection

@push('scripts')
<script>
window.SHOW_USER_I18N = @json($showI18n);
async function suspendUser() {
  Modal.confirm({
    title: window.SHOW_USER_I18N.suspendTitle,
    message: window.SHOW_USER_I18N.suspendMessage,
    confirmText: window.SHOW_USER_I18N.suspendText,
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.post('{{ route("users.suspend", $user) }}', {});
      if (ok) { Toast.success(window.SHOW_USER_I18N.suspendSuccess, data.message); setTimeout(() => location.reload(), 900); }
      else Toast.error(window.SHOW_USER_I18N.error, data.message);
    }
  });
}

async function activateUser() {
  const { ok, data } = await Http.post('{{ route("users.activate", $user) }}', {});
  if (ok) { Toast.success(window.SHOW_USER_I18N.activateSuccess, data.message); setTimeout(() => location.reload(), 900); }
  else Toast.error(window.SHOW_USER_I18N.error, data.message);
}

async function deleteUser() {
  Modal.confirm({
    title: window.SHOW_USER_I18N.deleteTitle,
    message: window.SHOW_USER_I18N.deleteMessage,
    confirmText: window.SHOW_USER_I18N.deleteText,
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete('{{ route("users.destroy", $user) }}');
      if (ok) { Toast.success(window.SHOW_USER_I18N.deleteSuccess, data.message); setTimeout(() => window.location.href = '{{ route("users.index") }}', 900); }
      else Toast.error(window.SHOW_USER_I18N.error, data.message);
    }
  });
}
</script>
@endpush
