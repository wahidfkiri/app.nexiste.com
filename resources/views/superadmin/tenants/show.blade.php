@extends('layouts.global')

@section('title', $tenant->name)

@section('breadcrumb')
  <a href="{{ route('superadmin.tenants.index') }}">Tenants</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $tenant->name }}</span>
@endsection

@section('content')
@php
  $statusClass = ['active' => 'actif', 'suspended' => 'suspendu', 'pending' => 'en_attente'][$tenant->status] ?? 'inactif';
  $statusLabel = $statuses[$tenant->status] ?? ucfirst((string) $tenant->status);
  $roleLabels = config('crm-core.tenant_roles', []);
  $activationStatuses = config('extensions.activation_statuses', []);
  $showI18n = [
      'error' => 'Erreur',
      'saved' => 'Tenant mis à jour',
      'statusUpdated' => 'Statut mis à jour',
      'activate' => 'Activer',
      'suspend' => 'Suspendre',
      'pending' => 'Mettre en attente',
      'confirm' => 'Confirmer',
      'statusMessage' => 'Cette action change immédiatement l’accès global du tenant.',
  ];
@endphp

<div class="tenant-show-page">
  <div class="page-header">
    <div class="page-header-left tenant-show-hero">
      <div class="tenant-show-logo">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $tenant->name, 0, 2)) }}</div>
      <div>
        <h1 style="margin:0 0 8px;">{{ $tenant->name }}</h1>
        <div class="tenant-show-meta">
          <span class="badge badge-{{ $statusClass }}"><span class="badge-dot" style="background:currentColor"></span>{{ $statusLabel }}</span>
          <span><i class="fas fa-link"></i> {{ $tenant->slug }}</span>
          <span><i class="fas fa-calendar"></i> Créé le {{ $tenant->created_at?->format('d/m/Y') }}</span>
        </div>
      </div>
    </div>
    <div class="page-header-actions">
      <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour
      </a>
      @if($tenant->status === 'active')
        <button class="btn btn-secondary" onclick="changeTenantStatus('suspended')">
          <i class="fas fa-ban"></i> Suspendre
        </button>
      @else
        <button class="btn btn-primary" onclick="changeTenantStatus('active')">
          <i class="fas fa-circle-check"></i> Activer
        </button>
      @endif
    </div>
  </div>

  <div class="stats-grid tenant-show-kpis">
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-users"></i></div>
      <div class="stat-body"><div class="stat-value">{{ $counters['active_members'] ?? 0 }}</div><div class="stat-label">Membres actifs</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-user-group"></i></div>
      <div class="stat-body"><div class="stat-value">{{ $counters['members'] ?? 0 }}</div><div class="stat-label">Membres au total</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#ecfeff;color:#0891b2"><i class="fas fa-puzzle-piece"></i></div>
      <div class="stat-body"><div class="stat-value">{{ $counters['active_apps'] ?? 0 }}</div><div class="stat-label">Applications actives</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#f0fdf4;color:#15803d"><i class="fas fa-address-book"></i></div>
      <div class="stat-body"><div class="stat-value">{{ $counters['clients'] ?? 0 }}</div><div class="stat-label">Clients</div></div>
    </div>
  </div>

  <div class="tenant-show-layout">
    <div class="tenant-show-main">
      <div class="info-card">
        <div class="info-card-header">
          <i class="fas fa-pen-to-square"></i>
          <h3>Informations du tenant</h3>
        </div>
        <div class="info-card-body">
          <form id="tenantForm" action="{{ route('superadmin.tenants.update', $tenant) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="tenant-form-grid">
              <div class="form-group">
                <label class="form-label">Nom de l’entreprise</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $tenant->name) }}" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email principal</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $tenant->email) }}" required>
              </div>
              <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $tenant->phone) }}">
              </div>
              <div class="form-group">
                <label class="form-label">Statut</label>
                <select name="status" class="form-control" required>
                  @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $tenant->status) === $key)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Fuseau horaire</label>
                <input type="text" name="timezone" class="form-control" value="{{ old('timezone', $tenant->timezone ?: 'UTC') }}" required>
              </div>
              <div class="form-group">
                <label class="form-label">Langue</label>
                <input type="text" name="locale" class="form-control" value="{{ old('locale', $tenant->locale ?: 'fr') }}" required>
              </div>
              <div class="form-group">
                <label class="form-label">Devise</label>
                <input type="text" name="currency" class="form-control" value="{{ old('currency', $tenant->currency ?: 'EUR') }}" required>
              </div>
              <div class="form-group">
                <label class="form-label">Fin d’essai</label>
                <input type="date" name="trial_ends_at" class="form-control" value="{{ old('trial_ends_at', $tenant->trial_ends_at?->format('Y-m-d')) }}">
              </div>
              <div class="form-group">
                <label class="form-label">Fin d’abonnement</label>
                <input type="date" name="subscription_ends_at" class="form-control" value="{{ old('subscription_ends_at', $tenant->subscription_ends_at?->format('Y-m-d')) }}">
              </div>
              <div class="form-group tenant-form-full">
                <label class="form-label">Adresse</label>
                <textarea name="address" class="form-control" rows="3">{{ old('address', $tenant->address) }}</textarea>
              </div>
            </div>

            <div class="tenant-risk-note">
              <i class="fas fa-circle-info"></i>
              <span>Le slug n’est pas modifié depuis cette page afin d’éviter de casser les URLs ou intégrations existantes.</span>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:18px;">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="info-card" style="margin-top:18px;">
        <div class="info-card-header">
          <i class="fas fa-users"></i>
          <h3>Membres récents</h3>
        </div>
        <div class="info-card-body">
          @forelse($members as $member)
            <div class="tenant-list-row">
              <div class="tenant-list-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $member->name, 0, 2)) }}</div>
              <div class="tenant-list-copy">
                <strong>{{ $member->name }}</strong>
                <span>{{ $member->email ?: 'Email non disponible' }}</span>
              </div>
              <div class="tenant-list-tags">
                @if($member->is_owner)
                  <span class="tenant-pill tenant-pill-owner"><i class="fas fa-crown"></i> Owner</span>
                @endif
                <span class="tenant-pill">{{ $roleLabels[$member->role] ?? ucfirst($member->role) }}</span>
                <span class="badge badge-{{ $member->status === 'active' ? 'actif' : 'suspendu' }}">{{ ucfirst($member->status) }}</span>
              </div>
            </div>
          @empty
            <div class="tenant-empty-mini">
              <i class="fas fa-user-slash"></i>
              <span>Aucun membre rattaché à ce tenant.</span>
            </div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="tenant-show-side">
      <div class="info-card">
        <div class="info-card-header">
          <i class="fas fa-chart-simple"></i>
          <h3>Résumé</h3>
        </div>
        <div class="info-card-body">
          <div class="info-row">
            <span class="info-row-label">ID tenant</span>
            <span class="info-row-value">#{{ $tenant->id }}</span>
          </div>
          <div class="info-row">
            <span class="info-row-label">Slug</span>
            <span class="info-row-value">{{ $tenant->slug }}</span>
          </div>
          <div class="info-row">
            <span class="info-row-label">Statut</span>
            <span class="badge badge-{{ $statusClass }}">{{ $statusLabel }}</span>
          </div>
          <div class="info-row">
            <span class="info-row-label">Fin d’essai</span>
            <span class="info-row-value">{{ $tenant->trial_ends_at?->format('d/m/Y') ?? 'Non définie' }}</span>
          </div>
          <div class="info-row">
            <span class="info-row-label">Fin abonnement</span>
            <span class="info-row-value">{{ $tenant->subscription_ends_at?->format('d/m/Y') ?? 'Non définie' }}</span>
          </div>
          <div class="info-row">
            <span class="info-row-label">Dernière mise à jour</span>
            <span class="info-row-value">{{ $tenant->updated_at?->format('d/m/Y H:i') }}</span>
          </div>
        </div>
      </div>

      <div class="info-card" style="margin-top:18px;">
        <div class="info-card-header">
          <i class="fas fa-puzzle-piece"></i>
          <h3>Applications</h3>
        </div>
        <div class="info-card-body tenant-app-list">
          @forelse($activations as $activation)
            @php
              $extension = $activation->extension;
              $appStatus = (string) $activation->status;
              $appBadge = in_array($appStatus, ['active', 'trial'], true) ? 'actif' : ($appStatus === 'pending' ? 'en_attente' : 'suspendu');
            @endphp
            <div class="tenant-app-row">
              <div class="tenant-app-icon">
                <i class="{{ $extension?->icon_class ?? 'fas fa-puzzle-piece' }}"></i>
              </div>
              <div class="tenant-list-copy">
                <strong>{{ $extension?->name ?? 'Application supprimée' }}</strong>
                <span>{{ $extension?->slug ?? 'extension inconnue' }}</span>
              </div>
              <span class="badge badge-{{ $appBadge }}">{{ $activationStatuses[$appStatus] ?? ucfirst($appStatus) }}</span>
            </div>
          @empty
            <div class="tenant-empty-mini">
              <i class="fas fa-plug-circle-xmark"></i>
              <span>Aucune application installée.</span>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
.tenant-show-page .tenant-show-hero{display:flex;align-items:center;gap:16px;}
.tenant-show-logo{
  width:64px;
  height:64px;
  border-radius:20px;
  background:linear-gradient(135deg,#2563eb,#0ea5e9);
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:24px;
  font-weight:900;
  box-shadow:0 18px 38px rgba(37,99,235,.2);
}
.tenant-show-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:12px;color:var(--c-ink-45);}
.tenant-show-meta span{display:inline-flex;align-items:center;gap:6px;}
.tenant-show-kpis{grid-template-columns:repeat(4,minmax(0,1fr));}
.tenant-show-layout{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start;}
.tenant-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
.tenant-form-full{grid-column:1 / -1;}
.tenant-risk-note{display:flex;gap:8px;align-items:flex-start;margin-top:14px;padding:12px 14px;border-radius:14px;background:var(--c-accent-xl);color:var(--c-accent);font-size:13px;font-weight:600;}
.tenant-list-row,.tenant-app-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--c-ink-05);}
.tenant-list-row:last-child,.tenant-app-row:last-child{border-bottom:0;}
.tenant-list-avatar,.tenant-app-icon{
  width:40px;
  height:40px;
  border-radius:13px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:var(--c-accent-lt);
  color:var(--c-accent);
  font-weight:800;
  flex:0 0 auto;
}
.tenant-app-icon{background:#eef2ff;color:#2563eb;}
.tenant-list-copy{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;}
.tenant-list-copy strong{font-size:14px;color:var(--c-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.tenant-list-copy span{font-size:12px;color:var(--c-ink-40);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.tenant-list-tags{display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:flex-end;}
.tenant-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 8px;border-radius:999px;background:var(--surface-2);color:var(--c-ink-50);font-size:11px;font-weight:700;}
.tenant-pill-owner{background:#fef3c7;color:#92400e;}
.tenant-empty-mini{display:flex;align-items:center;gap:10px;padding:18px;border:1px dashed var(--c-ink-10);border-radius:14px;color:var(--c-ink-45);background:var(--surface-0);}
@media (max-width:1180px){
  .tenant-show-layout{grid-template-columns:1fr;}
  .tenant-show-side{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;}
  .tenant-show-side .info-card{margin-top:0 !important;}
}
@media (max-width:760px){
  .tenant-show-kpis{grid-template-columns:1fr;}
  .tenant-form-grid{grid-template-columns:1fr;}
  .tenant-show-side{display:block;}
  .tenant-show-side .info-card + .info-card{margin-top:18px !important;}
  .tenant-list-row,.tenant-app-row{align-items:flex-start;flex-wrap:wrap;}
  .tenant-list-tags{justify-content:flex-start;width:100%;}
}
</style>
@endpush

@push('scripts')
<script>
window.TENANT_SHOW_ROUTES = {
  status: @json(route('superadmin.tenants.status', $tenant)),
};
window.TENANT_SHOW_I18N = @json($showI18n);

ajaxForm('tenantForm', {
  onSuccess: () => {
    setTimeout(() => window.location.reload(), 700);
    return false;
  },
});

async function submitTenantStatus(status) {
  const { ok, data } = await Http.post(window.TENANT_SHOW_ROUTES.status, { status });
  if (ok) {
    Toast.success(window.TENANT_SHOW_I18N.statusUpdated, data.message || window.TENANT_SHOW_I18N.statusUpdated);
    setTimeout(() => window.location.reload(), 700);
  } else {
    Toast.error(window.TENANT_SHOW_I18N.error, data.message || window.TENANT_SHOW_I18N.error);
  }
}

function changeTenantStatus(status) {
  const labels = {
    active: window.TENANT_SHOW_I18N.activate,
    suspended: window.TENANT_SHOW_I18N.suspend,
    pending: window.TENANT_SHOW_I18N.pending,
  };

  if (window.Modal?.confirm) {
    Modal.confirm({
      title: `${labels[status] || window.TENANT_SHOW_I18N.statusUpdated} ce tenant ?`,
      message: window.TENANT_SHOW_I18N.statusMessage,
      confirmText: window.TENANT_SHOW_I18N.confirm,
      type: status === 'suspended' ? 'danger' : 'info',
      onConfirm: () => submitTenantStatus(status),
    });
    return;
  }

  if (window.confirm(`${labels[status] || window.TENANT_SHOW_I18N.statusUpdated} ce tenant ?`)) {
    submitTenantStatus(status);
  }
}
</script>
@endpush
