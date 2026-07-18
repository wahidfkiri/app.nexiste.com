<div class="module-toolbar">
  <div class="module-toolbar-title">{{ __('common.nav.administration') }}</div>
  <div class="module-toolbar-links">
    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">{{ __('common.nav.users') }}</a>
    <a href="{{ route('rbac.roles.index') }}" class="{{ request()->routeIs('rbac.roles.*') ? 'active' : '' }}">{{ __('common.nav.roles') }}</a>
    <a href="{{ route('rbac.permissions.index') }}" class="{{ request()->routeIs('rbac.permissions.*') ? 'active' : '' }}">{{ __('common.nav.permissions') }}</a>
    <a href="{{ route('users.invitations') }}" class="{{ request()->routeIs('users.invitations*') ? 'active' : '' }}">{{ __('common.nav.invitations') }}</a>
  </div>
</div>
