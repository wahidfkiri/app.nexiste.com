@extends('layouts.global')

@section('title', __('user::users.titles.invitations'))

@section('breadcrumb')
  <a href="{{ route('users.index') }}">{{ __('user::users.breadcrumbs.team') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('user::users.breadcrumbs.invitations') }}</span>
@endsection

@section('content')
@php
  $invitationI18n = [
      'error' => __('user::users.messages.error'),
      'loadFailed' => __('user::users.messages.load_invitations_failed'),
      'noInvitation' => __('user::users.empty.no_invitation'),
      'inviteFirst' => __('user::users.empty.invite_first_collaborator'),
      'pending' => __('user::users.statuses.pending'),
      'accepted' => __('user::users.statuses.accepted'),
      'expired' => __('user::users.statuses.expired'),
      'revoked' => __('user::users.statuses.revoked'),
      'resend' => __('user::users.actions.resend'),
      'revoke' => __('user::users.actions.revoke'),
      'revokeTitle' => __('user::users.confirmations.revoke_invitation_title'),
      'revokeMessage' => __('user::users.confirmations.revoke_invitation_message'),
      'resent' => __('user::users.messages.resend_success'),
      'revokedToast' => __('user::users.messages.revoke_success'),
      'display' => __('user::users.table.display_invitations', ['from' => ':from', 'to' => ':to', 'total' => ':total']),
      'never' => __('user::users.exports.never'),
  ];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ __('user::users.titles.invitation_history') }}</h1>
    <p>{{ __('user::users.subtitles.invitation_history') }}</p>
  </div>
  <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> {{ __('user::users.actions.new_invitation') }}</a>
</div>

<div class="users-top-tabs" role="tablist" aria-label="{{ __('user::users.breadcrumbs.team') }}">
  <a href="{{ route('users.index') }}" class="users-top-tab"><i class="fas fa-users"></i><span>{{ __('user::users.tabs.users') }}</span></a>
  <a href="{{ route('rbac.roles.index') }}" class="users-top-tab"><i class="fas fa-shield-halved"></i><span>{{ __('user::users.tabs.roles') }}</span></a>
  <a href="{{ route('rbac.permissions.index') }}" class="users-top-tab"><i class="fas fa-key"></i><span>{{ __('user::users.tabs.permissions') }}</span></a>
  <a href="{{ route('users.invitations') }}" class="users-top-tab is-active"><i class="fas fa-envelope-open-text"></i><span>{{ __('user::users.tabs.invitations') }}</span></a>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">{{ __('user::users.tabs.invitations') }}</span>
    <span class="table-count" id="invCount">--</span>
    <div class="table-spacer"></div>
    <div class="table-search"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="{{ __('user::users.filters.search_email') }}" autocomplete="off"></div>
    <select class="filter-select" data-filter="status">
      <option value="">{{ __('user::users.filters.all_statuses') }}</option>
      <option value="pending">{{ __('user::users.statuses.pending') }}</option>
      <option value="accepted">{{ __('user::users.statuses.accepted') }}</option>
      <option value="expired">{{ __('user::users.statuses.expired') }}</option>
      <option value="revoked">{{ __('user::users.statuses.revoked') }}</option>
    </select>
    <button class="btn btn-ghost btn-sm" id="resetFilters" title="{{ __('user::users.filters.reset') }}"><i class="fas fa-rotate-left"></i></button>
  </div>

  <table class="crm-table" id="invTable">
    <thead>
      <tr>
        <th>{{ __('user::users.table.invited_email') }}</th>
        <th>{{ __('user::users.fields.assigned_role') }}</th>
        <th>{{ __('user::users.fields.invited_by') }}</th>
        <th>{{ __('user::users.fields.sent_at') }}</th>
        <th>{{ __('user::users.fields.expires_at') }}</th>
        <th>{{ __('user::users.fields.status') }}</th>
        <th style="text-align:right;padding-right:20px">{{ __('user::users.table.actions') }}</th>
      </tr>
    </thead>
    <tbody id="invTableBody"></tbody>
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
window.INV_ROUTES = {
  data: @json(route('users.invitations.data')),
  resend: @json(route('users.invitations.resend', ['invitation' => '__INVITATION__'])),
  revoke: @json(route('users.invitations.revoke', ['invitation' => '__INVITATION__'])),
};
window.INV_I18N = @json($invitationI18n);
const ROLE_LABELS = @json($roles);
const invitationRoute = (template, id) => String(template).replace('__INVITATION__', encodeURIComponent(String(id)));

class InvTable {
  constructor() {
    this.state = { page: 1, search: '', filters: {}, loading: false };
    this._debounce = null;
    this._bindEvents();
    this.load();
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
  }

  async load() {
    if (this.state.loading) return;
    this.state.loading = true;
    const tbody = document.getElementById('invTableBody');
    if (tbody) tbody.innerHTML = `<tr>${Array.from({ length: 7 }, () => `<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}</tr>`.repeat(4);
    const params = { page: this.state.page, per_page: 15, search: this.state.search, ...this.state.filters };
    const { ok, data } = await Http.get(window.INV_ROUTES.data, params);
    this.state.loading = false;
    if (!ok) { Toast.error(window.INV_I18N.error, window.INV_I18N.loadFailed); return; }
    this._renderRows(data.data || []);
    this._renderPagination(data);
    const countEl = document.getElementById('invCount');
    if (countEl) countEl.textContent = `${data.total || 0}`;
  }

  _renderRows(rows) {
    const tbody = document.getElementById('invTableBody');
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="7"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-envelope-open"></i></div><h3>${window.INV_I18N.noInvitation}</h3><p>${window.INV_I18N.inviteFirst}</p></div></td></tr>`;
      return;
    }
    const statusMap = {
      pending: ['info', window.INV_I18N.pending],
      accepted: ['actif', window.INV_I18N.accepted],
      expired: ['warning', window.INV_I18N.expired],
      revoked: ['inactif', window.INV_I18N.revoked],
    };
    tbody.innerHTML = rows.map((invitation) => {
      const status = invitation.is_accepted ? 'accepted' : invitation.is_revoked ? 'revoked' : invitation.is_expired ? 'expired' : 'pending';
      const [badgeClass, label] = statusMap[status] || ['secondary', status];
      const role = ROLE_LABELS[invitation.role_in_tenant] || invitation.role_in_tenant;
      const sentAt = invitation.created_at ? new Date(invitation.created_at).toLocaleDateString('fr-FR') : window.INV_I18N.never;
      const expiresAt = invitation.expires_at ? new Date(invitation.expires_at).toLocaleDateString('fr-FR') : window.INV_I18N.never;
      const invitedBy = invitation.invited_by?.name || '--';
      return `<tr>
        <td><div style="font-weight:var(--fw-medium);">${this._esc(invitation.email)}</div>${invitation.resend_count > 0 ? `<div style="font-size:11.5px;color:var(--c-ink-40);">${window.INV_I18N.resent.replace(' !', '')} ${invitation.resend_count}x</div>` : ''}</td>
        <td><span class="badge badge-info">${role}</span></td>
        <td style="font-size:13px;color:var(--c-ink-60);">${this._esc(invitedBy)}</td>
        <td style="font-size:13px;color:var(--c-ink-60);">${sentAt}</td>
        <td style="font-size:13px;color:var(--c-ink-60);">${expiresAt}</td>
        <td><span class="badge badge-${badgeClass}">${label}</span></td>
        <td><div class="row-actions" style="justify-content:flex-end;padding-right:4px;">${status === 'pending' ? `<button class="btn-icon" title="${window.INV_I18N.resend}" onclick="resendInv(${invitation.id})"><i class="fas fa-paper-plane"></i></button><button class="btn-icon danger" title="${window.INV_I18N.revoke}" onclick="revokeInv(${invitation.id})"><i class="fas fa-ban"></i></button>` : ''}</div></td>
      </tr>`;
    }).join('');
  }

  _renderPagination(data) {
    const wrap = document.getElementById('paginationControls');
    const info = document.getElementById('paginationInfo');
    if (!wrap) return;
    const { current_page, last_page, from, to, total } = data;
    if (info) info.textContent = window.INV_I18N.display.replace(':from', from || 0).replace(':to', to || 0).replace(':total', total || 0);
    const pages = [];
    for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page || 1, current_page + 2); i += 1) pages.push(i);
    wrap.innerHTML = `<button class="page-btn" ${current_page <= 1 ? 'disabled' : ''} onclick="window._invTable?.goTo(${current_page - 1})"><i class="fas fa-chevron-left"></i></button>${pages.map((page) => `<button class="page-btn ${page === current_page ? 'active' : ''}" onclick="window._invTable?.goTo(${page})">${page}</button>`).join('')}<button class="page-btn" ${current_page >= last_page ? 'disabled' : ''} onclick="window._invTable?.goTo(${current_page + 1})"><i class="fas fa-chevron-right"></i></button>`;
  }

  goTo(page) { this.state.page = page; this.load(); }
  _esc(value) { const div = document.createElement('div'); div.textContent = value || ''; return div.innerHTML; }
}

window._invTable = new InvTable();

async function resendInv(id) {
  const { ok, data } = await Http.post(invitationRoute(window.INV_ROUTES.resend, id), {});
  if (ok) { Toast.success(window.INV_I18N.resent, data.message); window._invTable?.load(); }
  else Toast.error(window.INV_I18N.error, data.message);
}

async function revokeInv(id) {
  Modal.confirm({
    title: window.INV_I18N.revokeTitle,
    message: window.INV_I18N.revokeMessage,
    confirmText: window.INV_I18N.revoke,
    type: 'danger',
    onConfirm: async () => {
      const { ok, data } = await Http.delete(invitationRoute(window.INV_ROUTES.revoke, id));
      if (ok) { Toast.success(window.INV_I18N.revokedToast, data.message); window._invTable?.load(); }
      else Toast.error(window.INV_I18N.error, data.message);
    }
  });
}
</script>
@endpush
