@extends('layouts.global')

@section('title', __('rbac::rbac.titles.permissions'))

@section('breadcrumb')
  <a href="{{ route('rbac.roles.index') }}">{{ __('rbac::rbac.breadcrumbs.roles_permissions') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('rbac::rbac.breadcrumbs.permissions') }}</span>
@endsection

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-shield-alt', 'bg' => '#dbeafe', 'color' => '#1d4ed8', 'alt' => __('rbac::rbac.titles.permissions')])
      <h1 style="margin:0;">{{ __('rbac::rbac.headings.permissions_available') }}</h1>
    </div>
    <p>{{ __('rbac::rbac.subtitles.permissions_index') }}</p>
  </div>
  <a href="{{ route('rbac.roles.index') }}" class="btn btn-secondary">
    <i class="fas fa-shield-halved"></i> {{ __('rbac::rbac.buttons.view_roles') }}
  </a>
</div>

<div class="form-section" style="margin-bottom:20px;">
  <div class="table-search" style="max-width:400px;">
    <i class="fas fa-search"></i>
    <input type="text" id="permSearch" placeholder="{{ __('rbac::rbac.filters.search_permission') }}" style="width:100%;padding:10px 12px 10px 36px;border:1.5px solid var(--c-ink-10);border-radius:var(--r-md);background:var(--surface-1);outline:none;font-size:14px;" oninput="filterPerms(this.value)">
  </div>
</div>

<div class="row">
  @foreach($permissionsGrouped as $groupKey => $group)
  @php $totalGroup = count($group['permissions']); @endphp
  <div class="col-6" style="margin-bottom:20px;" data-group-block="{{ $groupKey }}">
    <div class="info-card" style="height:100%;">
      <div class="info-card-header" style="justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:34px;height:34px;background:var(--c-accent-lt);border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;">
            <i class="fas {{ $group['icon'] }}" style="color:var(--c-accent);font-size:14px;"></i>
          </div>
          <div>
            <h3 style="margin:0;">{{ $group['label'] }}</h3>
          </div>
        </div>
        <span style="background:var(--c-success-lt);color:var(--c-success);padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;">
          {{ $totalGroup }} permission(s)
        </span>
      </div>
      <div class="info-card-body" style="padding:0;">
        @foreach($group['permissions'] as $permission)
        @php
          $permissionLabel = $permission->display_label ?: config("rbac.permission_groups.{$groupKey}.permissions.{$permission->name}", $permission->name);
        @endphp
        <div class="perm-item" data-name="{{ $permission->name }}" data-label="{{ \Illuminate\Support\Str::lower($permissionLabel) }}" style="display:flex;align-items:center;justify-content:space-between;padding:11px 20px;border-bottom:1px solid var(--c-ink-05);transition:background var(--dur-fast);" onmouseover="this.style.background='var(--c-accent-xl)'" onmouseout="this.style.background=''">
          <div style="display:flex;align-items:center;gap:10px;">
            <i class="fas fa-key" style="color:var(--c-ink-20);font-size:12px;width:14px;text-align:center;"></i>
            <div>
              <div style="font-size:13.5px;font-weight:var(--fw-medium);color:var(--c-ink);">
                {{ $permissionLabel }}
              </div>
            </div>
          </div>
          @php
            $rolesWithPerm = \Spatie\Permission\Models\Role::whereHas('permissions', fn($query) => $query->where('name', $permission->name))
                ->where(function($query) { $query->where('tenant_id', auth()->user()->tenant_id)->orWhereNull('tenant_id'); })
                ->pluck('name')
                ->toArray();
          @endphp
          <div style="display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end;max-width:180px;">
            @forelse($rolesWithPerm as $roleName)
            @php $roleColor = ['owner' => '#7c3aed', 'admin' => '#2563eb', 'manager' => '#0891b2', 'user' => '#059669', 'viewer' => '#64748b'][$roleName] ?? '#64748b'; @endphp
            <span style="background:{{ $roleColor }}18;color:{{ $roleColor }};padding:2px 7px;border-radius:99px;font-size:10.5px;font-weight:600;">
              {{ config("user.tenant_roles.{$roleName}", $roleName) }}
            </span>
            @empty
            <span style="font-size:11.5px;color:var(--c-ink-20);">{{ __('rbac::rbac.labels.no_role_for_permission') }}</span>
            @endforelse
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
  @endforeach
</div>
@endsection

@push('scripts')
<script>
function filterPerms(term) {
  const query = term.toLowerCase().trim();
  document.querySelectorAll('.perm-item').forEach((element) => {
    const match = !query || element.dataset.name.includes(query) || element.dataset.label.includes(query);
    element.style.display = match ? '' : 'none';
  });

  document.querySelectorAll('[data-group-block]').forEach((block) => {
    const visible = [...block.querySelectorAll('.perm-item')].some((element) => element.style.display !== 'none');
    block.style.display = visible ? '' : 'none';
  });
}
</script>
@endpush
