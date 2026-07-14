@extends('layouts.global')

@section('title', 'Tenants actifs')

@section('breadcrumb')
  <span>Administration</span>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">Tenants</span>
@endsection

@section('content')
@php
  $tenantIndexI18n = [
      'error' => 'Erreur',
      'loadFailed' => 'Impossible de charger les tenants.',
      'emptyTitle' => 'Aucun tenant trouvé',
      'emptyText' => 'Ajustez vos filtres ou recherchez une autre entreprise.',
      'display' => 'Affichage de :from à :to sur :total tenant(s)',
      'view' => 'Consulter',
      'activate' => 'Activer',
      'suspend' => 'Suspendre',
      'setPending' => 'Mettre en attente',
      'statusUpdated' => 'Statut mis à jour',
      'activateTitle' => 'Activer :name ?',
      'suspendTitle' => 'Suspendre :name ?',
      'pendingTitle' => 'Mettre :name en attente ?',
      'statusMessage' => 'Cette action change immédiatement l’accès global du tenant.',
      'confirm' => 'Confirmer',
      'reset' => 'Réinitialiser',
      'never' => 'Non défini',
      'created' => 'Tenant créé',
      'createFailed' => 'Impossible de créer le tenant.',
  ];
@endphp

<div class="tenant-admin-page">
  <div class="page-header">
    <div class="page-header-left">
      <div class="page-title-heading">
        @include('layouts.partials.page-title-icon', ['icon' => 'fas fa-building-user', 'bg' => '#dbeafe', 'color' => '#2563eb', 'alt' => 'Tenants'])
        <h1 style="margin:0;">Tenants actifs</h1>
      </div>
      <p>Consultez et gérez les entreprises actives de la plateforme depuis un espace super-admin dédié.</p>
    </div>
    <div class="page-header-actions">
      <a href="{{ route('superadmin.plans.index') }}" class="btn btn-secondary">
        <i class="fas fa-layer-group"></i> Forfaits
      </a>
      <a href="{{ route('superadmin.payment-methods.index') }}" class="btn btn-secondary">
        <i class="fas fa-credit-card"></i> Moyens de paiement
      </a>
      <button class="btn btn-primary" data-modal-open="createTenantModal">
        <i class="fas fa-plus"></i> Nouveau tenant
      </button>
      <button class="btn btn-secondary" id="refreshTenants">
        <i class="fas fa-rotate"></i> Actualiser
      </button>
    </div>
  </div>

  <div class="stats-grid tenant-kpis">
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-layer-group"></i></div>
      <div class="stat-body"><div class="stat-value" id="kpiTotal">--</div><div class="stat-label">Tenants au total</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-circle-check"></i></div>
      <div class="stat-body"><div class="stat-value" id="kpiActive">--</div><div class="stat-label">Actifs</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-hourglass-half"></i></div>
      <div class="stat-body"><div class="stat-value" id="kpiPending">--</div><div class="stat-label">En attente</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--c-danger-lt);color:var(--c-danger)"><i class="fas fa-ban"></i></div>
      <div class="stat-body"><div class="stat-value" id="kpiSuspended">--</div><div class="stat-label">Suspendus</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#ecfeff;color:#0891b2"><i class="fas fa-user-check"></i></div>
      <div class="stat-body"><div class="stat-value" id="kpiActiveMembers">--</div><div class="stat-label">Membres actifs</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#f0fdf4;color:#15803d"><i class="fas fa-credit-card"></i></div>
      <div class="stat-body"><div class="stat-value" id="kpiSubscribed">--</div><div class="stat-label">Abonnements valides</div></div>
    </div>
  </div>

  <div class="table-wrapper">
    <div class="table-header">
      <span class="table-title">Liste des tenants</span>
      <span class="table-count" id="tenantsCount">--</span>
      <div class="table-spacer"></div>

      <div class="table-search">
        <i class="fas fa-search"></i>
        <input type="text" id="tenantSearchInput" placeholder="Rechercher nom, email, slug..." autocomplete="off">
      </div>

      <select class="filter-select" id="tenantStatusFilter" data-filter="status">
        <option value="active" selected>Tenants actifs</option>
        <option value="all">Tous les statuts</option>
        @foreach($statuses as $key => $label)
          @if($key !== 'active')
            <option value="{{ $key }}">{{ $label }}</option>
          @endif
        @endforeach
      </select>

      <button class="btn btn-ghost btn-sm" id="resetTenantFilters" title="{{ $tenantIndexI18n['reset'] }}">
        <i class="fas fa-rotate-left"></i>
      </button>
    </div>

    <table class="crm-table tenant-table" id="tenantsTable">
      <thead>
        <tr>
          <th data-sort="name" class="sortable">Tenant <i class="fas fa-sort" style="font-size:10px;opacity:.4"></i></th>
          <th>Contact</th>
          <th data-sort="active_members_count" class="sortable" style="text-align:center">Membres</th>
          <th data-sort="active_apps_count" class="sortable" style="text-align:center">Apps</th>
          <th data-sort="clients_count" class="sortable" style="text-align:center">Clients</th>
          <th data-sort="subscription_ends_at" class="sortable">Abonnement</th>
          <th data-sort="status" class="sortable">Statut</th>
          <th data-sort="created_at" class="sortable">Création</th>
          <th style="text-align:right;padding-right:20px">Actions</th>
        </tr>
      </thead>
      <tbody id="tenantsTableBody"></tbody>
    </table>

    <div class="table-pagination">
      <span class="pagination-info" id="tenantPaginationInfo"></span>
      <div class="pagination-spacer"></div>
      <div class="pagination-pages" id="tenantPaginationControls"></div>
    </div>
  </div>

  <div class="modal-overlay" id="createTenantModal">
    <div class="modal modal-lg">
      <div class="modal-header">
        <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)">
          <i class="fas fa-building-circle-check"></i>
        </div>
        <div>
          <div class="modal-title">Créer un tenant</div>
          <div class="modal-subtitle">Ajoutez une entreprise et assignez son administrateur tenant.</div>
        </div>
        <button type="button" class="modal-close" data-modal-close aria-label="Fermer">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form id="createTenantForm" action="{{ route('superadmin.tenants.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="tenant-create-section">
            <div class="tenant-create-section-head">
              <span><i class="fas fa-building"></i></span>
              <div>
                <strong>Entreprise</strong>
                <small>Identité, statut et paramètres régionaux.</small>
              </div>
            </div>
            <div class="tenant-create-grid">
              <div class="form-group">
                <label class="form-label">Nom de l’entreprise</label>
                <input type="text" name="tenant_name" class="form-control" required autocomplete="organization">
              </div>
              <div class="form-group">
                <label class="form-label">Email principal</label>
                <input type="email" name="tenant_email" class="form-control" required autocomplete="email">
              </div>
              <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input type="text" name="tenant_phone" class="form-control" autocomplete="tel">
              </div>
              <div class="form-group">
                <label class="form-label">Statut</label>
                <select name="tenant_status" class="form-control" required>
                  @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected($key === 'active')>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Fuseau horaire</label>
                <input type="text" name="timezone" class="form-control" value="Europe/Paris" required>
              </div>
              <div class="form-group">
                <label class="form-label">Langue</label>
                <input type="text" name="locale" class="form-control" value="fr" required>
              </div>
              <div class="form-group">
                <label class="form-label">Devise</label>
                <input type="text" name="currency" class="form-control" value="EUR" required>
              </div>
              <div class="form-group">
                <label class="form-label">Fin d’essai</label>
                <input type="date" name="trial_ends_at" class="form-control">
              </div>
              <div class="form-group">
                <label class="form-label">Fin d’abonnement</label>
                <input type="date" name="subscription_ends_at" class="form-control">
              </div>
              <div class="form-group tenant-create-full">
                <label class="form-label">Adresse</label>
                <textarea name="tenant_address" class="form-control" rows="3"></textarea>
              </div>
            </div>
          </div>

          <div class="tenant-create-section">
            <div class="tenant-create-section-head">
              <span><i class="fas fa-user-shield"></i></span>
              <div>
                <strong>Administrateur du tenant</strong>
                <small>Rôle tenant <b>admin</b>, sans droits super-admin plateforme.</small>
              </div>
            </div>
            <div class="tenant-create-grid">
              <div class="form-group">
                <label class="form-label">Nom complet</label>
                <input type="text" name="admin_name" class="form-control" required autocomplete="name">
              </div>
              <div class="form-group">
                <label class="form-label">Email administrateur</label>
                <input type="email" name="admin_email" class="form-control" required autocomplete="email">
                <span class="form-hint">Si l’utilisateur existe déjà, il sera simplement associé à ce tenant.</span>
              </div>
              <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="admin_password" class="form-control" autocomplete="new-password" minlength="8">
                <span class="form-hint">Obligatoire uniquement pour un nouvel utilisateur.</span>
              </div>
              <div class="form-group">
                <label class="form-label">Confirmation</label>
                <input type="password" name="admin_password_confirmation" class="form-control" autocomplete="new-password" minlength="8">
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-modal-close>Annuler</button>
          <button type="submit" class="btn btn-primary" data-loading-text="Création...">
            <i class="fas fa-circle-plus"></i> Créer le tenant
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
.tenant-admin-page .tenant-kpis{grid-template-columns:repeat(6,minmax(0,1fr));}
.tenant-admin-page .tenant-logo{
  width:42px;
  height:42px;
  border-radius:14px;
  background:linear-gradient(135deg,#2563eb,#0ea5e9);
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:800;
  letter-spacing:-.04em;
  box-shadow:0 14px 28px rgba(37,99,235,.18);
  flex:0 0 auto;
}
.tenant-admin-page .tenant-cell{display:flex;align-items:center;gap:12px;min-width:230px;}
.tenant-admin-page .tenant-name{font-weight:800;color:var(--c-ink);}
.tenant-admin-page .tenant-sub{font-size:12px;color:var(--c-ink-40);margin-top:2px;}
.tenant-admin-page .tenant-metric{font-weight:800;color:var(--c-ink);display:block;}
.tenant-admin-page .tenant-metric-sub{font-size:11.5px;color:var(--c-ink-40);}
.tenant-admin-page .tenant-table td{vertical-align:middle;}
.tenant-admin-page .tenant-create-section{
  border:1px solid var(--c-ink-08);
  border-radius:18px;
  padding:16px;
  background:linear-gradient(180deg,#fff,rgba(248,250,252,.74));
}
.tenant-admin-page .tenant-create-section + .tenant-create-section{margin-top:16px;}
.tenant-admin-page .tenant-create-section-head{
  display:flex;
  align-items:flex-start;
  gap:12px;
  margin-bottom:14px;
}
.tenant-admin-page .tenant-create-section-head > span{
  width:38px;
  height:38px;
  border-radius:13px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:var(--c-accent-lt);
  color:var(--c-accent);
}
.tenant-admin-page .tenant-create-section-head strong{
  display:block;
  font-size:14px;
  color:var(--c-ink);
}
.tenant-admin-page .tenant-create-section-head small{
  display:block;
  margin-top:2px;
  color:var(--c-ink-45);
  font-size:12px;
}
.tenant-admin-page .tenant-create-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:14px;
}
.tenant-admin-page .tenant-create-full{grid-column:1 / -1;}
@media (max-width:1200px){
  .tenant-admin-page .tenant-kpis{grid-template-columns:repeat(3,minmax(0,1fr));}
}
@media (max-width:720px){
  .tenant-admin-page .tenant-kpis{grid-template-columns:1fr;}
  .tenant-admin-page .page-header{align-items:flex-start;}
  .tenant-admin-page .table-header{align-items:stretch;}
  .tenant-admin-page .table-search{width:100%;}
  .tenant-admin-page .tenant-create-grid{grid-template-columns:1fr;}
}
</style>
@endpush

@push('scripts')
<script>
window.TENANT_ROUTES = {
  store: @json(route('superadmin.tenants.store')),
  data: @json(route('superadmin.tenants.data')),
  stats: @json(route('superadmin.tenants.stats')),
  status: @json(route('superadmin.tenants.status', '__TENANT__')),
};
window.TENANT_I18N = @json($tenantIndexI18n);
window.TENANT_STATUS_LABELS = @json($statuses);

document.addEventListener('DOMContentLoaded', () => {
  window._tenantTable = new TenantAdminTable();
  ajaxForm('createTenantForm', {
    onSuccess: () => {
      window._tenantTable?.load();
      window._tenantTable?.loadStats();
    },
  });
  document.getElementById('refreshTenants')?.addEventListener('click', () => {
    window._tenantTable?.load();
    window._tenantTable?.loadStats();
  });
});

class TenantAdminTable {
  constructor() {
    this.state = { page: 1, search: '', filters: { status: 'active' }, sort: 'created_at', dir: 'desc', loading: false };
    this._debounce = null;
    this._bindEvents();
    this.load();
    this.loadStats();
  }

  _bindEvents() {
    document.getElementById('tenantSearchInput')?.addEventListener('input', () => {
      clearTimeout(this._debounce);
      this._debounce = setTimeout(() => {
        this.state.search = document.getElementById('tenantSearchInput').value.trim();
        this.state.page = 1;
        this.load();
      }, 320);
    });

    document.querySelectorAll('.tenant-admin-page [data-filter]').forEach((element) => {
      element.addEventListener('change', () => {
        this.state.filters[element.dataset.filter] = element.value;
        this.state.page = 1;
        this.load();
      });
    });

    document.getElementById('resetTenantFilters')?.addEventListener('click', () => {
      this.state.search = '';
      this.state.filters = { status: 'active' };
      this.state.page = 1;
      const search = document.getElementById('tenantSearchInput');
      const status = document.getElementById('tenantStatusFilter');
      if (search) search.value = '';
      if (status) status.value = 'active';
      this.load();
    });

    document.querySelectorAll('.tenant-admin-page [data-sort]').forEach((header) => {
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
    const params = {
      page: this.state.page,
      per_page: 20,
      search: this.state.search,
      sort_by: this.state.sort,
      sort_dir: this.state.dir,
      ...this.state.filters,
    };
    const { ok, data } = await Http.get(window.TENANT_ROUTES.data, params);
    this.state.loading = false;
    if (!ok) {
      Toast.error(window.TENANT_I18N.error, data.message || window.TENANT_I18N.loadFailed);
      return;
    }
    this._renderRows(data.data || []);
    this._renderPagination(data);
    const countEl = document.getElementById('tenantsCount');
    if (countEl) countEl.textContent = `${data.total || 0}`;
  }

  async loadStats() {
    const { ok, data } = await Http.get(window.TENANT_ROUTES.stats);
    if (!ok || !data.data) return;
    const stats = data.data;
    this._setText('kpiTotal', stats.total || 0);
    this._setText('kpiActive', stats.active || 0);
    this._setText('kpiPending', stats.pending || 0);
    this._setText('kpiSuspended', stats.suspended || 0);
    this._setText('kpiActiveMembers', stats.active_members || 0);
    this._setText('kpiSubscribed', stats.subscribed || 0);
  }

  _showSkeletons(count = 6) {
    const tbody = document.getElementById('tenantsTableBody');
    if (!tbody) return;
    tbody.innerHTML = Array.from({ length: count }, () => `<tr>${Array.from({ length: 9 }, () => `<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}</tr>`).join('');
  }

  _renderRows(rows) {
    const tbody = document.getElementById('tenantsTableBody');
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="9"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-building-user"></i></div><h3>${window.TENANT_I18N.emptyTitle}</h3><p>${window.TENANT_I18N.emptyText}</p></div></td></tr>`;
      return;
    }
    tbody.innerHTML = rows.map((tenant) => this._row(tenant)).join('');
  }

  _row(tenant) {
    const initials = this._initials(tenant.name);
    const phone = tenant.phone ? `<span>${this._esc(tenant.phone)}</span>` : `<span style="color:var(--c-ink-20);">--</span>`;
    const subscription = tenant.subscription_ends_at || tenant.trial_ends_at || `<span style="color:var(--c-ink-20);">${window.TENANT_I18N.never}</span>`;
    const nextStatus = tenant.status === 'active' ? 'suspended' : 'active';
    const nextIcon = tenant.status === 'active' ? 'ban' : 'circle-check';
    const nextLabel = tenant.status === 'active' ? window.TENANT_I18N.suspend : window.TENANT_I18N.activate;

    return `
      <tr>
        <td>
          <div class="tenant-cell">
            <div class="tenant-logo">${initials}</div>
            <div>
              <div class="tenant-name">${this._esc(tenant.name)}</div>
              <div class="tenant-sub">${this._esc(tenant.slug)}</div>
            </div>
          </div>
        </td>
        <td>
          <div style="font-size:13px;color:var(--c-ink);">${this._esc(tenant.email)}</div>
          <div style="font-size:12px;color:var(--c-ink-40);margin-top:2px;">${phone}</div>
        </td>
        <td style="text-align:center"><span class="tenant-metric">${tenant.active_members_count || 0}</span><span class="tenant-metric-sub">sur ${tenant.members_count || 0}</span></td>
        <td style="text-align:center"><span class="tenant-metric">${tenant.active_apps_count || 0}</span><span class="tenant-metric-sub">sur ${tenant.apps_count || 0}</span></td>
        <td style="text-align:center"><span class="tenant-metric">${tenant.clients_count || 0}</span></td>
        <td style="font-size:13px;color:var(--c-ink-60);">${subscription}</td>
        <td><span class="badge badge-${tenant.status_class}"><span class="badge-dot" style="background:currentColor"></span>${this._esc(tenant.status_label)}</span></td>
        <td style="font-size:13px;color:var(--c-ink-60);">${tenant.created_at || '--'}</td>
        <td>
          <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
            <a href="${tenant.show_url}" class="btn-icon" title="${window.TENANT_I18N.view}"><i class="fas fa-eye"></i></a>
            <button class="btn-icon" title="${nextLabel}" onclick="TenantAdminTable.changeStatus(${tenant.id}, '${nextStatus}')"><i class="fas fa-${nextIcon}"></i></button>
            ${tenant.status !== 'pending' ? `<button class="btn-icon" title="${window.TENANT_I18N.setPending}" onclick="TenantAdminTable.changeStatus(${tenant.id}, 'pending')"><i class="fas fa-hourglass-half"></i></button>` : ''}
          </div>
        </td>
      </tr>`;
  }

  _renderPagination(data) {
    const wrap = document.getElementById('tenantPaginationControls');
    const info = document.getElementById('tenantPaginationInfo');
    if (!wrap) return;
    const current = data.current_page || 1;
    const last = data.last_page || 1;
    if (info) {
      info.textContent = window.TENANT_I18N.display
        .replace(':from', data.from || 0)
        .replace(':to', data.to || 0)
        .replace(':total', data.total || 0);
    }
    const pages = [];
    for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i += 1) pages.push(i);
    wrap.innerHTML = `<button class="page-btn" ${current <= 1 ? 'disabled' : ''} onclick="window._tenantTable?.goTo(${current - 1})"><i class="fas fa-chevron-left"></i></button>${pages.map((page) => `<button class="page-btn ${page === current ? 'active' : ''}" onclick="window._tenantTable?.goTo(${page})">${page}</button>`).join('')}<button class="page-btn" ${current >= last ? 'disabled' : ''} onclick="window._tenantTable?.goTo(${current + 1})"><i class="fas fa-chevron-right"></i></button>`;
  }

  goTo(page) {
    this.state.page = page;
    this.load();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  _setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
  }

  _initials(value) {
    return String(value || 'T').trim().split(/\s+/).slice(0, 2).map((part) => part[0] || '').join('').toUpperCase() || 'T';
  }

  _esc(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  static changeStatus(id, status) {
    const cleanName = 'ce tenant';
    const titleMap = {
      active: window.TENANT_I18N.activateTitle,
      suspended: window.TENANT_I18N.suspendTitle,
      pending: window.TENANT_I18N.pendingTitle,
    };
    const title = (titleMap[status] || window.TENANT_I18N.statusUpdated).replace(':name', cleanName);
    const submit = async () => {
      const url = window.TENANT_ROUTES.status.replace('__TENANT__', id);
      const { ok, data } = await Http.post(url, { status });
      if (ok) {
        Toast.success(window.TENANT_I18N.statusUpdated, data.message || window.TENANT_I18N.statusUpdated);
        window._tenantTable?.load();
        window._tenantTable?.loadStats();
      } else {
        Toast.error(window.TENANT_I18N.error, data.message || window.TENANT_I18N.loadFailed);
      }
    };

    if (window.Modal?.confirm) {
      Modal.confirm({
        title,
        message: window.TENANT_I18N.statusMessage,
        confirmText: window.TENANT_I18N.confirm,
        type: status === 'suspended' ? 'danger' : 'info',
        onConfirm: submit,
      });
      return;
    }

    if (window.confirm(title)) submit();
  }
}
</script>
@endpush
