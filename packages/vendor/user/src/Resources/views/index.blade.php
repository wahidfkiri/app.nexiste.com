@extends('layouts.global')

@section('title', __('user::users.titles.team'))

@section('breadcrumb')
  <span>{{ __('user::users.breadcrumbs.crm') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('user::users.breadcrumbs.team') }}</span>
@endsection

@section('content')
@php
  $userIndexI18n = [
      'success' => __('user::users.messages.success'),
      'error' => __('user::users.messages.error'),
      'loadFailed' => __('user::users.messages.load_users_failed'),
      'noMemberFound' => __('user::users.empty.no_member_found'),
      'inviteFirst' => __('user::users.empty.invite_first_member'),
      'inviteMember' => __('user::users.actions.invite'),
      'never' => __('user::users.exports.never'),
      'owner' => __('user::users.badges.owner'),
      'view' => __('user::users.actions.view'),
      'edit' => __('user::users.actions.edit'),
      'suspend' => __('user::users.actions.suspend'),
      'activate' => __('user::users.actions.activate'),
      'delete' => __('user::users.actions.delete'),
      'reset' => __('user::users.filters.reset'),
      'bulkDeleteTitle' => __('user::users.confirmations.delete_many_title', ['count' => ':count']),
      'bulkDeleteMessage' => __('user::users.confirmations.delete_many_message'),
      'toggleTitle' => __('user::users.confirmations.toggle_status_title', ['action' => ':action', 'name' => ':name']),
      'toggleMessage' => __('user::users.confirmations.toggle_status_message', ['action' => ':action']),
      'deleteUserTitle' => __('user::users.confirmations.delete_user_title', ['name' => ':name']),
      'deleteUserMessage' => __('user::users.confirmations.delete_user_message'),
      'display' => __('user::users.table.display_members', ['from' => ':from', 'to' => ':to', 'total' => ':total']),
      'membersSuffix' => __('user::users.table.member'),
  ];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-users-cog', 'bg' => '#dbeafe', 'color' => '#1d4ed8', 'alt' => __('user::users.headings.manage_team')])
      <h1 style="margin:0;">{{ __('user::users.headings.manage_team') }}</h1>
    </div>
    <p>{{ __('user::users.subtitles.manage_team') }}</p>
  </div>
  <div class="page-header-actions">
    <div class="dropdown">
      <button class="btn btn-secondary" data-dropdown-toggle>
        <i class="fas fa-arrow-down-to-line"></i> {{ __('user::users.actions.export') }}
        <i class="fas fa-chevron-down" style="font-size:10px;margin-left:2px;"></i>
      </button>
      <div class="dropdown-menu">
        <a href="{{ route('users.export.csv') }}" class="dropdown-item"><i class="fas fa-file-csv"></i> CSV</a>
        <a href="{{ route('users.export.excel') }}" class="dropdown-item"><i class="fas fa-file-excel"></i> Excel</a>
      </div>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary">
      <i class="fas fa-user-plus"></i> {{ __('user::users.actions.invite') }}
    </a>
  </div>
</div>

<div class="users-top-tabs" role="tablist" aria-label="{{ __('user::users.breadcrumbs.team') }}">
  <a href="{{ route('users.index') }}" class="users-top-tab is-active"><i class="fas fa-users"></i><span>{{ __('user::users.tabs.users') }}</span></a>
  <a href="{{ route('rbac.roles.index') }}" class="users-top-tab"><i class="fas fa-shield-halved"></i><span>{{ __('user::users.tabs.roles') }}</span></a>
  <a href="{{ route('rbac.permissions.index') }}" class="users-top-tab"><i class="fas fa-key"></i><span>{{ __('user::users.tabs.permissions') }}</span></a>
  <a href="{{ route('users.invitations') }}" class="users-top-tab"><i class="fas fa-envelope-open-text"></i><span>{{ __('user::users.tabs.invitations') }}</span></a>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-users"></i></div>
    <div class="stat-body"><div class="stat-value" id="kpiTotal">--</div><div class="stat-label">{{ __('user::users.stats.total_members') }}</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-user-check"></i></div>
    <div class="stat-body"><div class="stat-value" id="kpiActive">--</div><div class="stat-label">{{ __('user::users.stats.active_members') }}</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-info-lt);color:var(--c-info)"><i class="fas fa-envelope"></i></div>
    <div class="stat-body"><div class="stat-value" id="kpiInvited">--</div><div class="stat-label">{{ __('user::users.stats.sent_invitations') }}</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-danger-lt);color:var(--c-danger)"><i class="fas fa-user-slash"></i></div>
    <div class="stat-body"><div class="stat-value" id="kpiSuspended">--</div><div class="stat-label">{{ __('user::users.stats.suspended_members') }}</div></div>
  </div>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ __('user::users.headings.team_members') }}</span>
    <span class="table-count" id="usersCount">--</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="{{ __('user::users.filters.search_member') }}" autocomplete="off">
    </div>

    <select class="filter-select" data-filter="role_in_tenant">
      <option value="">{{ __('user::users.filters.all_roles') }}</option>
      @foreach($roles as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" data-filter="status">
      <option value="">{{ __('user::users.filters.all_statuses') }}</option>
      @foreach($statuses as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <button class="btn btn-ghost btn-sm" id="resetFilters" title="{{ __('user::users.filters.reset') }}">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <div class="bulk-bar" id="bulkBar">
    <span><strong id="selectedCount">0</strong> {{ __('user::users.table.member') }}(s) sélectionné(s)</span>
    <div class="bulk-bar-actions">
      <button class="btn btn-sm btn-secondary" onclick="bulkUserStatus('active')"><i class="fas fa-check-circle"></i> {{ __('user::users.actions.activate') }}</button>
      <button class="btn btn-sm btn-secondary" onclick="bulkUserStatus('suspended')"><i class="fas fa-ban"></i> {{ __('user::users.actions.suspend') }}</button>
      <button class="btn btn-sm btn-danger" onclick="bulkUserDelete()"><i class="fas fa-trash"></i> {{ __('user::users.actions.delete') }}</button>
    </div>
  </div>

  <table class="crm-table" id="usersTable">
    <thead>
      <tr>
        <th style="width:40px"><input type="checkbox" id="selectAll"></th>
        <th data-sort="name" class="sortable">{{ __('user::users.table.member') }} <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th data-sort="role_in_tenant" class="sortable">{{ __('user::users.table.role') }}</th>
        <th>{{ __('user::users.table.department') }}</th>
        <th data-sort="status" class="sortable">{{ __('user::users.table.status') }}</th>
        <th data-sort="last_login_at" class="sortable">{{ __('user::users.table.last_login') }}</th>
        <th style="text-align:right;padding-right:20px">{{ __('user::users.table.actions') }}</th>
      </tr>
    </thead>
    <tbody id="usersTableBody"></tbody>
  </table>

  <div class="table-pagination">
    <span class="pagination-info" id="paginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="paginationControls"></div>
  </div>
</div>
@endsection

@push('styles')
<style>
.users-top-tabs{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 16px;}
.users-top-tab{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:12px;border:1px solid var(--c-line);color:var(--c-ink-60);background:var(--c-surface);font-weight:600;text-decoration:none;transition:all .2s ease;}
.users-top-tab:hover{border-color:var(--c-accent);color:var(--c-accent);transform:translateY(-1px);}
.users-top-tab.is-active{border-color:var(--c-accent);background:var(--c-accent-lt);color:var(--c-accent);}
</style>
@endpush

@push('scripts')
<script>
window.USER_ROUTES = {
  data: @json(route('users.data')),
  stats: @json(route('users.stats')),
  bulkDelete: @json(route('users.bulk.delete')),
  bulkStatus: @json(route('users.bulk.status')),
  show: @json(route('users.show', ['user' => '__USER__'])),
  edit: @json(route('users.edit', ['user' => '__USER__'])),
  suspend: @json(route('users.suspend', ['user' => '__USER__'])),
  activate: @json(route('users.activate', ['user' => '__USER__'])),
  destroy: @json(route('users.destroy', ['user' => '__USER__'])),
};
window.USER_I18N = @json($userIndexI18n);
const ROLE_LABELS = @json($roles);
const STATUS_LABELS = @json(config('user.user_statuses'));
const ROLE_COLORS = { owner: '#7c3aed', admin: '#2563eb', manager: '#0891b2', user: '#059669', viewer: '#64748b' };
const STATUS_COLORS = { active: 'success', inactive: 'danger', invited: 'info', suspended: 'secondary' };
const userRoute = (template, id) => String(template).replace('__USER__', encodeURIComponent(String(id)));

document.addEventListener('DOMContentLoaded', () => {
  window._userTable = new UserTable({
    tbodyId: 'usersTableBody',
    dataUrl: window.USER_ROUTES.data,
    statsUrl: window.USER_ROUTES.stats,
    countEl: 'usersCount',
  });
});

async function bulkUserDelete() {
  const ids = window._userTable?.getSelectedIds();
  if (!ids?.length) return;
  Modal.confirm({
    title: window.USER_I18N.bulkDeleteTitle.replace(':count', ids.length),
    message: window.USER_I18N.bulkDeleteMessage,
    confirmText: window.USER_I18N.delete,
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.post(window.USER_ROUTES.bulkDelete, { ids });
      if (ok) { Toast.success(window.USER_I18N.success, data.message); window._userTable?.load(); window._userTable?.loadStats(); }
      else Toast.error(window.USER_I18N.error, data.message);
    }
  });
}

async function bulkUserStatus(status) {
  const ids = window._userTable?.getSelectedIds();
  if (!ids?.length) return;
  const { ok, data } = await Http.post(window.USER_ROUTES.bulkStatus, { ids, status });
  if (ok) { Toast.success(window.USER_I18N.success, data.message); window._userTable?.load(); window._userTable?.loadStats(); }
  else Toast.error(window.USER_I18N.error, data.message);
}

class UserTable {
  constructor(opts) {
    this.opts = Object.assign({ perPage: 15 }, opts);
    this.state = { page: 1, search: '', filters: {}, sort: '', dir: 'asc', loading: false };
    this.selectedIds = new Set();
    this._debounce = null;
    this._bindEvents();
    this.load();
    if (this.opts.statsUrl) this.loadStats();
  }

  _bindEvents() {
    document.getElementById('searchInput')?.addEventListener('input', () => {
      clearTimeout(this._debounce);
      this._debounce = setTimeout(() => {
        this.state.search = document.getElementById('searchInput').value.trim();
        this.state.page = 1;
        this.load();
      }, 350);
    });
    document.querySelectorAll('[data-filter]').forEach((element) => {
      element.addEventListener('change', () => {
        this.state.filters[element.dataset.filter] = element.value;
        this.state.page = 1;
        this.load();
      });
    });
    document.getElementById('resetFilters')?.addEventListener('click', () => {
      this.state.search = '';
      this.state.filters = {};
      const search = document.getElementById('searchInput');
      if (search) search.value = '';
      document.querySelectorAll('[data-filter]').forEach((select) => { select.value = ''; });
      this.state.page = 1;
      this.load();
    });
    document.getElementById('selectAll')?.addEventListener('change', (event) => {
      document.querySelectorAll('.row-check').forEach((checkbox) => {
        checkbox.checked = event.target.checked;
        event.target.checked ? this.selectedIds.add(+checkbox.dataset.id) : this.selectedIds.delete(+checkbox.dataset.id);
      });
      this._updateBulkBar();
    });
    document.getElementById(this.opts.tbodyId)?.addEventListener('change', (event) => {
      if (event.target.classList.contains('row-check')) {
        const id = +event.target.dataset.id;
        event.target.checked ? this.selectedIds.add(id) : this.selectedIds.delete(id);
        this._updateBulkBar();
      }
    });
    document.querySelectorAll('[data-sort]').forEach((header) => {
      header.addEventListener('click', () => {
        const column = header.dataset.sort;
        if (this.state.sort === column) {
          this.state.dir = this.state.dir === 'asc' ? 'desc' : 'asc';
        } else {
          this.state.sort = column;
          this.state.dir = 'asc';
        }
        this.load();
      });
    });
  }

  async load() {
    if (this.state.loading) return;
    this.state.loading = true;
    this._showSkeletons();
    const params = { page: this.state.page, per_page: this.opts.perPage, search: this.state.search, sort_by: this.state.sort, sort_dir: this.state.dir, ...this.state.filters };
    const { ok, data } = await Http.get(this.opts.dataUrl, params);
    this.state.loading = false;
    if (!ok) { Toast.error(window.USER_I18N.error, window.USER_I18N.loadFailed); return; }
    this._renderRows(data.data || []);
    this._renderPagination(data);
    const countEl = document.getElementById(this.opts.countEl);
    if (countEl) countEl.textContent = `${data.total || 0}`;
  }

  async loadStats() {
    const { ok, data } = await Http.get(this.opts.statsUrl);
    if (!ok || !data.data) return;
    const stats = data.data;
    const set = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
    set('kpiTotal', stats.total || 0);
    set('kpiActive', stats.active || 0);
    set('kpiInvited', stats.invited || 0);
    set('kpiSuspended', stats.suspended || 0);
  }

  _showSkeletons(count = 5) {
    const tbody = document.getElementById(this.opts.tbodyId);
    if (!tbody) return;
    tbody.innerHTML = Array.from({ length: count }, () => `<tr>${Array.from({ length: 7 }, () => `<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}</tr>`).join('');
  }

  _renderRows(rows) {
    const tbody = document.getElementById(this.opts.tbodyId);
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-users"></i></div><h3>${window.USER_I18N.noMemberFound}</h3><p>${window.USER_I18N.inviteFirst}</p><a href="{{ route('users.create') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> ${window.USER_I18N.inviteMember}</a></div></td></tr>`;
      return;
    }
    tbody.innerHTML = rows.map((user) => this._renderRow(user)).join('');
  }

  _renderRow(user) {
    const roleColor = ROLE_COLORS[user.role_in_tenant] || '#64748b';
    const roleLabel = ROLE_LABELS[user.role_in_tenant] || user.role_in_tenant;
    const statusClass = STATUS_COLORS[user.status] || 'secondary';
    const statusLabel = STATUS_LABELS[user.status] || user.status;
    const initials = (user.name || 'U').substring(0, 2).toUpperCase();
    const avatarColors = ['#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706', '#dc2626'];
    const bgColor = avatarColors[(user.name?.charCodeAt(0) || 0) % avatarColors.length];
    const avatar = user.avatar
      ? `<img src="/storage/${user.avatar}" style="width:38px;height:38px;border-radius:var(--r-sm);object-fit:cover;">`
      : `<div class="client-avatar" style="background:${bgColor};width:38px;height:38px;font-size:13px;">${initials}</div>`;
    const isOwner = user.is_tenant_owner;
    const isSelf = user.id === window._currentUserId;
    const lastLogin = user.last_login_at
      ? new Date(user.last_login_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
      : `<span style="color:var(--c-ink-20);">${window.USER_I18N.never}</span>`;

    return `
      <tr data-id="${user.id}" class="${this.selectedIds.has(user.id) ? 'selected' : ''}">
        <td style="width:40px">${!isOwner && !isSelf ? `<input type="checkbox" class="row-check" data-id="${user.id}" ${this.selectedIds.has(user.id) ? 'checked' : ''}>` : ''}</td>
        <td>
          <div class="client-cell">
            ${avatar}
            <div>
              <div class="client-name">${this._esc(user.name)}${isOwner ? ` <span style="font-size:10px;background:#fef3c7;color:#92400e;padding:2px 7px;border-radius:99px;margin-left:6px;font-weight:600;">${window.USER_I18N.owner}</span>` : ''}</div>
              <div class="client-sub">${this._esc(user.email)}</div>
            </div>
          </div>
        </td>
        <td><span style="background:${roleColor}18;color:${roleColor};border:1px solid ${roleColor}30;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;">${roleLabel}</span></td>
        <td style="font-size:13px;color:var(--c-ink-60);">${user.department ? `<div>${this._esc(user.department)}</div>` : ''}${user.job_title ? `<div style="font-size:11.5px;color:var(--c-ink-40);">${this._esc(user.job_title)}</div>` : ''}${!user.department && !user.job_title ? '<span style="color:var(--c-ink-20);">--</span>' : ''}</td>
        <td><span class="badge badge-${statusClass}"><span class="badge-dot" style="background:currentColor"></span>${statusLabel}</span></td>
        <td style="font-size:13px;color:var(--c-ink-60);">${lastLogin}</td>
        <td>
          <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
            <a href="${userRoute(window.USER_ROUTES.show, user.uuid ?? user.id)}" class="btn-icon" title="${window.USER_I18N.view}"><i class="fas fa-eye"></i></a>
            <a href="${userRoute(window.USER_ROUTES.edit, user.uuid ?? user.id)}" class="btn-icon" title="${window.USER_I18N.edit}"><i class="fas fa-pen"></i></a>
            ${!isOwner && !isSelf ? `
            <button class="btn-icon" title="${user.status === 'active' ? window.USER_I18N.suspend : window.USER_I18N.activate}" onclick="UserTable.toggleStatus(${user.id}, '${user.status}', '${this._esc(user.name)}')"><i class="fas fa-${user.status === 'active' ? 'ban' : 'check-circle'}"></i></button>
            <button class="btn-icon danger" onclick="UserTable.deleteUser(${user.id}, '${this._esc(user.name)}')" title="${window.USER_I18N.delete}"><i class="fas fa-trash"></i></button>` : ''}
          </div>
        </td>
      </tr>`;
  }

  _renderPagination(data) {
    const wrap = document.getElementById('paginationControls');
    const info = document.getElementById('paginationInfo');
    if (!wrap) return;
    const { current_page, last_page, from, to, total } = data;
    if (info) {
      info.textContent = window.USER_I18N.display.replace(':from', from || 0).replace(':to', to || 0).replace(':total', total || 0);
    }
    const pages = [];
    for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page || 1, current_page + 2); i += 1) pages.push(i);
    wrap.innerHTML = `<button class="page-btn" ${current_page <= 1 ? 'disabled' : ''} onclick="window._userTable?.goTo(${current_page - 1})"><i class="fas fa-chevron-left"></i></button>${pages.map((page) => `<button class="page-btn ${page === current_page ? 'active' : ''}" onclick="window._userTable?.goTo(${page})">${page}</button>`).join('')}<button class="page-btn" ${current_page >= last_page ? 'disabled' : ''} onclick="window._userTable?.goTo(${current_page + 1})"><i class="fas fa-chevron-right"></i></button>`;
  }

  goTo(page) { this.state.page = page; this.load(); window.scrollTo({ top: 0, behavior: 'smooth' }); }

  _updateBulkBar() {
    const bar = document.getElementById('bulkBar');
    if (!bar) return;
    const total = this.selectedIds.size;
    bar.classList.toggle('visible', total > 0);
    const count = document.getElementById('selectedCount');
    if (count) count.textContent = total;
  }

  getSelectedIds() { return [...this.selectedIds]; }
  _esc(value) { const div = document.createElement('div'); div.textContent = value || ''; return div.innerHTML; }

  static async toggleStatus(id, current, name) {
    const newStatus = current === 'active' ? 'suspended' : 'active';
    const action = newStatus === 'active' ? window.USER_I18N.activate : window.USER_I18N.suspend;
    Modal.confirm({
      title: window.USER_I18N.toggleTitle.replace(':action', action).replace(':name', name),
      message: window.USER_I18N.toggleMessage.replace(':action', action.toLowerCase()),
      confirmText: action,
      type: newStatus === 'active' ? 'success' : 'danger',
      onConfirm: async () => {
        const url = newStatus === 'suspended' ? userRoute(window.USER_ROUTES.suspend, id) : userRoute(window.USER_ROUTES.activate, id);
        const { ok, data } = await Http.post(url, {});
        if (ok) { Toast.success(window.USER_I18N.success, data.message); window._userTable?.load(); window._userTable?.loadStats(); }
        else Toast.error(window.USER_I18N.error, data.message);
      }
    });
  }

  static async deleteUser(id, name) {
    Modal.confirm({
      title: window.USER_I18N.deleteUserTitle.replace(':name', name),
      message: window.USER_I18N.deleteUserMessage,
      confirmText: window.USER_I18N.delete,
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.delete(userRoute(window.USER_ROUTES.destroy, id));
        if (ok) { Toast.success(window.USER_I18N.success, data.message); window._userTable?.load(); window._userTable?.loadStats(); }
        else Toast.error(window.USER_I18N.error, data.message);
      }
    });
  }
}

window.UserTable = UserTable;
window._currentUserId = {{ auth()->id() }};
</script>
@endpush
