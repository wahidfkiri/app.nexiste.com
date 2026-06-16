@extends('google-drive::layouts.drive')

@section('title', data_get($currentExtensionMeta, 'name', 'Google Drive'))

@section('gd_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ data_get($currentExtensionMeta, 'name', 'Google Drive') }}</span>
@endsection

@section('gd_content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fab fa-google-drive')), 'bg' => '#dcfce7', 'color' => '#0f9d58', 'alt' => data_get($currentExtensionMeta, 'name', 'Google Drive')])
      <h1 style="margin:0;">{{ data_get($currentExtensionMeta, 'name', 'Google Drive') }}</h1>
    </div>
    <p>Centralisez les fichiers de votre entreprise dans Google Drive, sans quitter le CRM.</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled><i class="fas fa-database"></i> Migration requise</button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-drive') }}" class="btn btn-primary"><i class="fas fa-store"></i> Activer depuis Marketplace</a>
    @elseif($connected)
      @if(!empty($dropboxInstalled))
      <a href="{{ route('dropbox.index') }}" class="btn btn-secondary"><i class="fab fa-dropbox"></i> Ouvrir Dropbox</a>
      @endif
      <button class="btn btn-secondary" id="gdRefreshBtn"><i class="fas fa-rotate"></i> Actualiser</button>
      <button class="btn btn-secondary" id="gdTrashBtn"><i class="fas fa-trash"></i> Corbeille</button>
      <button class="btn btn-primary" id="gdCreateFolderBtn" data-modal-open="gdFolderModal"><i class="fas fa-folder-plus"></i> Nouveau dossier</button>
      <label class="btn btn-primary" for="gdUploadInput"><i class="fas fa-upload"></i> Importer</label>
      <input type="file" id="gdUploadInput" style="display:none;">
      <button class="btn btn-danger" id="gdDisconnectBtn"><i class="fas fa-link-slash"></i> Deconnecter</button>
    @else
      <a href="{{ route('google-drive.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google-drive"></i> Connecter Google Drive</a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Migration Google Drive requise</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Les tables Google Drive sont absentes. Lancez la migration avant d'utiliser ce module.</p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family: "DM Sans", sans-serif;font-size:12px;color:var(--c-ink-80);">php artisan migrate</div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>Extension non active</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Google Drive est disponible dans le CRM, mais votre tenant doit encore l'activer depuis Marketplace.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-drive') }}" class="btn btn-primary"><i class="fas fa-store"></i> Ouvrir la fiche application</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Voir toutes les applications</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fab fa-google-drive"></i><h3>Connexion Google Drive</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Aucun compte Google Drive n'est encore relie a cet espace. Connectez votre compte pour gerer vos dossiers, fichiers et partages.</p>
    <a href="{{ route('google-drive.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connecter maintenant</a>
  </div>
</div>
@else
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-hard-drive"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gdUsedStorage">0 GB</div>
      <div class="stat-label">Stockage utilise</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-database"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gdTotalStorage">0 GB</div>
      <div class="stat-label">Quota total</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-folder-open"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="gdCurrentFolderName">Racine</div>
      <div class="stat-label">Dossier courant</div>
    </div>
  </div>
</div>

@if(!empty($dropboxInstalled))
<div class="info-card" style="margin-bottom:20px;">
  <div class="info-card-header"><i class="fab fa-dropbox"></i><h3>Dropbox est aussi disponible</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Si certains documents doivent plutot vivre dans Dropbox, ouvrez l'autre application de stockage pour utiliser ce second espace.</p>
    <a href="{{ route('dropbox.index') }}" class="btn btn-secondary"><i class="fab fa-dropbox"></i> Basculer vers Dropbox</a>
  </div>
</div>
@endif

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Fichiers Google Drive</span>
    <span class="table-count" id="gdCount">0 element(s)</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="gdSearchInput" placeholder="Rechercher un fichier ou dossier...">
    </div>
    <button class="btn btn-ghost btn-sm" id="gdBackBtn"><i class="fas fa-arrow-left"></i> Retour</button>
  </div>

  <table class="crm-table">
    <thead>
      <tr>
        <th>Nom</th>
        <th>Type</th>
        <th>Taille</th>
        <th>Modifie</th>
        <th style="text-align:right;padding-right:20px;">Actions</th>
      </tr>
    </thead>
    <tbody id="gdFilesTableBody"></tbody>
  </table>
</div>
@endif

<div class="modal-overlay" id="gdFolderModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">Nouveau dossier</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Nom du dossier</label>
        <input type="text" class="form-control" id="gdFolderName" maxlength="500" placeholder="Ex: Contrats clients">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="gdSaveFolderBtn"><i class="fas fa-check"></i> Creer</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="gdTrashModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Corbeille</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <table class="crm-table">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Type</th>
            <th>Modifie</th>
            <th style="text-align:right;padding-right:20px;">Actions</th>
          </tr>
        </thead>
        <tbody id="gdTrashTableBody"></tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger" id="gdEmptyTrashBtn"><i class="fas fa-trash-can"></i> Vider la corbeille</button>
      <button class="btn btn-secondary" data-modal-close>Fermer</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.GDRIVE_ROUTES = {
  connect: '{{ route('google-drive.oauth.connect') }}',
  disconnect: '{{ route('google-drive.oauth.disconnect') }}',
  filesData: '{{ route('google-drive.files.data') }}',
  stats: '{{ route('google-drive.stats') }}',
  createFolder: '{{ route('google-drive.folders.store') }}',
  upload: '{{ route('google-drive.files.upload') }}',
  trashData: '{{ route('google-drive.trash.data') }}',
  emptyTrash: '{{ route('google-drive.trash.empty') }}',
  search: '{{ route('google-drive.search') }}',
  fileBase: @json(rtrim(route('google-drive.index'), '/') . '/files'),
};

window.GDRIVE_BOOTSTRAP = {
  connected: @json((bool) $connected),
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.GoogleDriveModule) {
    window.GoogleDriveModule.boot(window.GDRIVE_BOOTSTRAP);
  }

  @if(session('success'))
  Toast.success(@json(__('google-drive::messages.common.success')), @json(session('success')));
  @endif

  @if(session('error'))
  Toast.error(@json(__('google-drive::messages.common.error')), @json(session('error')));
  @endif
});
</script>
@endpush
