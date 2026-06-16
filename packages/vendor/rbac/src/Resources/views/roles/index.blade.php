@extends('layouts.global')

@section('title', __('rbac::rbac.titles.roles_permissions'))

@section('breadcrumb')
  <span>{{ __('rbac::rbac.breadcrumbs.admin') }}</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('rbac::rbac.breadcrumbs.roles_permissions') }}</span>
@endsection

@section('content')
@php
  $rbacIndexI18n = [
      'loadRolesFailed' => __('rbac::rbac.messages.load_roles_failed'),
      'error' => __('rbac::rbac.toasts.error'),
      'deleted' => __('rbac::rbac.toasts.deleted'),
      'deleteTitle' => __('rbac::rbac.confirmations.delete_role_title', ['label' => ':label']),
      'deleteMessage' => __('rbac::rbac.confirmations.delete_role_message'),
      'deleteButton' => __('rbac::rbac.buttons.delete'),
      'noRole' => __('rbac::rbac.labels.no_role'),
      'createFirstRole' => __('rbac::rbac.labels.create_first_role'),
      'newRole' => __('rbac::rbac.buttons.new_role'),
      'system' => __('rbac::rbac.badges.system'),
      'defaultRole' => __('rbac::rbac.badges.default'),
      'custom' => __('rbac::rbac.badges.custom'),
      'inactive' => __('rbac::rbac.labels.inactive'),
      'view' => __('rbac::rbac.buttons.view'),
      'edit' => __('rbac::rbac.buttons.edit'),
      'actions' => __('rbac::rbac.table.actions'),
      'pagination' => __('rbac::rbac.table.display', ['from' => ':from', 'to' => ':to', 'total' => ':total']),
  ];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-user-shield', 'bg' => '#ede9fe', 'color' => '#7c3aed', 'alt' => __('rbac::rbac.headings.roles_permissions')])
      <h1 style="margin:0;">{{ __('rbac::rbac.headings.roles_permissions') }}</h1>
    </div>
    <p>{{ __('rbac::rbac.subtitles.roles_index') }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('rbac.permissions.index') }}" class="btn btn-secondary">
      <i class="fas fa-shield-halved"></i> {{ __('rbac::rbac.buttons.view_permissions') }}
    </a>
    <a href="{{ route('rbac.roles.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> {{ __('rbac::rbac.buttons.new_role') }}
    </a>
  </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
  <div class="stat-card">
    <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-shield-halved"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiTotalRoles">--</div>
      <div class="stat-label">{{ __('rbac::rbac.stats.total_roles') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-wand-magic-sparkles"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiCustomRoles">--</div>
      <div class="stat-label">{{ __('rbac::rbac.stats.custom_roles') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-key"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiTotalPerms">--</div>
      <div class="stat-label">{{ __('rbac::rbac.stats.total_permissions') }}</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-user-xmark"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="kpiNoRole">--</div>
      <div class="stat-label">{{ __('rbac::rbac.stats.members_without_role') }}</div>
    </div>
  </div>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ __('rbac::rbac.table.roles') }}</span>
    <span class="table-count" id="rolesCount">--</span>
    <div class="table-spacer"></div>
    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="{{ __('rbac::rbac.filters.search_role') }}" autocomplete="off">
    </div>
    <button class="btn btn-ghost btn-sm" id="resetFilters" title="{{ __('rbac::rbac.buttons.cancel') }}"><i class="fas fa-rotate-left"></i></button>
  </div>

  <table class="crm-table" id="rolesTable">
    <thead>
      <tr>
        <th style="width:44px"></th>
        <th data-sort="label" class="sortable">{{ __('rbac::rbac.table.role') }} <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
        <th>{{ __('rbac::rbac.table.description') }}</th>
        <th style="text-align:center">{{ __('rbac::rbac.table.permissions') }}</th>
        <th style="text-align:center">{{ __('rbac::rbac.table.members') }}</th>
        <th>{{ __('rbac::rbac.table.type') }}</th>
        <th style="text-align:right;padding-right:20px">{{ __('rbac::rbac.table.actions') }}</th>
      </tr>
    </thead>
    <tbody id="rolesTableBody"></tbody>
  </table>

  <div class="table-pagination">
    <span class="pagination-info" id="paginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="paginationControls"></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.RBAC_ROUTES = {
  data: @json(route('rbac.roles.data')),
  stats: @json(route('rbac.roles.stats')),
  show: @json(route('rbac.roles.show', ['role' => '__ROLE__'])),
  edit: @json(route('rbac.roles.edit', ['role' => '__ROLE__'])),
  destroy: @json(route('rbac.roles.destroy', ['role' => '__ROLE__'])),
};
window.RBAC_I18N = @json($rbacIndexI18n);
const rbacRoute = (template, id) => String(template).replace('__ROLE__', encodeURIComponent(String(id)));

document.addEventListener('DOMContentLoaded', () => {
  window._rolesTable = new RolesTable({
    tbodyId: 'rolesTableBody',
    dataUrl: window.RBAC_ROUTES.data,
    statsUrl: window.RBAC_ROUTES.stats,
  });
});

class RolesTable {
  constructor(opts) {
    this.opts = Object.assign({ perPage: 15 }, opts);
    this.state = { page: 1, search: '', loading: false };
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

    document.getElementById('resetFilters')?.addEventListener('click', () => {
      this.state.search = '';
      if (document.getElementById('searchInput')) {
        document.getElementById('searchInput').value = '';
      }
      this.state.page = 1;
      this.load();
    });
  }

  async load() {
    if (this.state.loading) return;
    this.state.loading = true;
    const tbody = document.getElementById(this.opts.tbodyId);
    if (tbody) {
      tbody.innerHTML = `<tr>${Array.from({ length: 7 }, () => `<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}</tr>`.repeat(5);
    }

    const params = { page: this.state.page, per_page: this.opts.perPage, search: this.state.search };
    const { ok, data } = await Http.get(this.opts.dataUrl, params);
    this.state.loading = false;

    if (!ok) {
      Toast.error(window.RBAC_I18N.error, window.RBAC_I18N.loadRolesFailed);
      return;
    }

    this._renderRows(data.data || []);
    this._renderPagination(data);
    const cnt = document.getElementById('rolesCount');
    if (cnt) cnt.textContent = `${data.total || 0}`;
  }

  async loadStats() {
    const { ok, data } = await Http.get(this.opts.statsUrl);
    if (!ok || !data.data) return;
    const stats = data.data;
    const set = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    };
    set('kpiTotalRoles', stats.total_roles || 0);
    set('kpiCustomRoles', stats.custom_roles || 0);
    set('kpiTotalPerms', stats.total_permissions || 0);
    set('kpiNoRole', stats.users_without_role || 0);
  }

  _renderRows(rows) {
    const tbody = document.getElementById(this.opts.tbodyId);
    if (!tbody) return;

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-shield-halved"></i></div><h3>${window.RBAC_I18N.noRole}</h3><p>${window.RBAC_I18N.createFirstRole}</p><a href="{{ route('rbac.roles.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> ${window.RBAC_I18N.newRole}</a></div></td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map((role) => this._renderRow(role)).join('');
  }

  _renderRow(role) {
    const color = role.color || '#64748b';
    const label = this._esc(role.label || role.name);
    const isSystem = role.is_system;
    const isDefaultRole = !!role.is_default_role;
    const isDeletable = role.is_deletable !== false && !isSystem && !isDefaultRole;
    const typeBadge = isSystem
      ? `<span style="background:#f3e8ff;color:#7c3aed;border:1px solid #e9d5ff;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;"><i class="fas fa-lock" style="font-size:10px;margin-right:4px;"></i>${window.RBAC_I18N.system}</span>`
      : (isDefaultRole
        ? `<span style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;"><i class="fas fa-shield" style="font-size:10px;margin-right:4px;"></i>${window.RBAC_I18N.defaultRole}</span>`
        : `<span style="background:var(--c-accent-lt);color:var(--c-accent);border:1px solid var(--c-accent-lt);padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;"><i class="fas fa-pen" style="font-size:10px;margin-right:4px;"></i>${window.RBAC_I18N.custom}</span>`);

    return `
      <tr data-id="${role.id}">
        <td style="width:44px;padding:12px 8px 12px 16px;">
          <div style="width:14px;height:14px;border-radius:50%;background:${color};flex-shrink:0;box-shadow:0 0 0 3px ${color}22;"></div>
        </td>
        <td>
          <div style="display:flex;align-items:center;gap:10px;">
            <div>
              <div style="font-weight:var(--fw-semi);color:var(--c-ink);">
                ${label}
                ${!role.is_active ? `<span style="font-size:10px;background:var(--c-danger-lt);color:var(--c-danger);padding:2px 7px;border-radius:99px;margin-left:6px;">${window.RBAC_I18N.inactive}</span>` : ''}
              </div>
              <div style="font-size:11.5px;color:var(--c-ink-40);font-family:'DM Sans', sans-serif;">${this._esc(role.name)}</div>
            </div>
          </div>
        </td>
        <td style="font-size:13px;color:var(--c-ink-60);max-width:220px;">
          <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${this._esc(role.description || '--')}</div>
        </td>
        <td style="text-align:center;">
          <span style="background:var(--c-success-lt);color:var(--c-success);padding:3px 12px;border-radius:99px;font-size:12px;font-weight:600;">
            <i class="fas fa-key" style="font-size:10px;margin-right:4px;"></i>${role.permissions_count}
          </span>
        </td>
        <td style="text-align:center;">
          <span style="background:var(--c-accent-lt);color:var(--c-accent);padding:3px 12px;border-radius:99px;font-size:12px;font-weight:600;">
            <i class="fas fa-users" style="font-size:10px;margin-right:4px;"></i>${role.users_count}
          </span>
        </td>
        <td>${typeBadge}</td>
        <td>
          <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
            <a href="${rbacRoute(window.RBAC_ROUTES.show, role.id)}" class="btn-icon" title="${window.RBAC_I18N.view}"><i class="fas fa-eye"></i></a>
            ${!isSystem ? `
              <a href="${rbacRoute(window.RBAC_ROUTES.edit, role.id)}" class="btn-icon" title="${window.RBAC_I18N.edit}"><i class="fas fa-pen"></i></a>
              ${isDeletable ? `<button class="btn-icon danger" onclick="RolesTable.deleteRole(${role.id}, '${label.replace(/'/g, '&#39;')}')" title="${window.RBAC_I18N.deleteButton}"><i class="fas fa-trash"></i></button>` : `<span style="padding:0 4px;color:var(--c-ink-20);font-size:12px;"><i class="fas fa-shield"></i></span>`}
            ` : `<span style="padding:0 4px;color:var(--c-ink-20);font-size:12px;"><i class="fas fa-lock"></i></span>`}
          </div>
        </td>
      </tr>`;
  }

  _renderPagination(data) {
    const wrap = document.getElementById('paginationControls');
    const info = document.getElementById('paginationInfo');
    if (!wrap) return;

    const currentPage = data.current_page || 1;
    const lastPage = data.last_page || 1;
    const from = data.from || 0;
    const to = data.to || 0;
    const total = data.total || 0;

    if (info) {
      info.textContent = window.RBAC_I18N.pagination
        .replace(':from', from)
        .replace(':to', to)
        .replace(':total', total);
    }

    const pages = [];
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(lastPage, currentPage + 2); i += 1) {
      pages.push(i);
    }

    wrap.innerHTML = `
      <button class="page-btn" ${currentPage <= 1 ? 'disabled' : ''} onclick="window._rolesTable?.goTo(${currentPage - 1})"><i class="fas fa-chevron-left"></i></button>
      ${pages.map((page) => `<button class="page-btn ${page === currentPage ? 'active' : ''}" onclick="window._rolesTable?.goTo(${page})">${page}</button>`).join('')}
      <button class="page-btn" ${currentPage >= lastPage ? 'disabled' : ''} onclick="window._rolesTable?.goTo(${currentPage + 1})"><i class="fas fa-chevron-right"></i></button>`;
  }

  goTo(page) {
    this.state.page = page;
    this.load();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  _esc(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  static async deleteRole(id, label) {
    Modal.confirm({
      title: window.RBAC_I18N.deleteTitle.replace(':label', label),
      message: window.RBAC_I18N.deleteMessage,
      confirmText: window.RBAC_I18N.deleteButton,
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.delete(rbacRoute(window.RBAC_ROUTES.destroy, id));
        if (ok) {
          Toast.success(window.RBAC_I18N.deleted, data.message);
          document.querySelector(`#rolesTableBody tr[data-id="${id}"]`)?.remove();
          const visibleRows = document.querySelectorAll('#rolesTableBody tr[data-id]').length;
          if (!visibleRows && window._rolesTable?.state?.page > 1) {
            window._rolesTable.state.page -= 1;
          }
          await window._rolesTable?.load();
          await window._rolesTable?.loadStats();
        } else {
          Toast.error(window.RBAC_I18N.error, data.message);
        }
      }
    });
  }
}

window.RolesTable = RolesTable;
</script>
@endpush
