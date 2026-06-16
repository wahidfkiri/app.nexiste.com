<div class="module-toolbar">
  <div class="module-toolbar-title">Administration</div>
  <div class="module-toolbar-links">
    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">Utilisateurs</a>
    <a href="{{ route('rbac.roles.index') }}" class="{{ request()->routeIs('rbac.roles.*') ? 'active' : '' }}">Roles</a>
    <a href="{{ route('rbac.permissions.index') }}" class="{{ request()->routeIs('rbac.permissions.*') ? 'active' : '' }}">Permissions</a>
    <a href="{{ route('users.invitations') }}" class="{{ request()->routeIs('users.invitations*') ? 'active' : '' }}">Invitations</a>
  </div>
</div>

