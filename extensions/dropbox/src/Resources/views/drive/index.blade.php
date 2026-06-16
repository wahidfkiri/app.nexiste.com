@extends('dropbox::layouts.drive')

@section('title', data_get($currentExtensionMeta, 'name', 'Dropbox'))

@section('dbx_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ data_get($currentExtensionMeta, 'name', 'Dropbox') }}</span>
@endsection

@section('dbx_content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fab fa-dropbox')), 'bg' => '#dbeafe', 'color' => '#2563eb', 'alt' => data_get($currentExtensionMeta, 'name', 'Dropbox')])
      <h1 style="margin:0;">{{ data_get($currentExtensionMeta, 'name', 'Dropbox') }}</h1>
    </div>
    <p>Centralisez les fichiers de votre entreprise dans Dropbox avec une experience similaire a Google Drive.</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled><i class="fas fa-database"></i> Migration requise</button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'dropbox') }}" class="btn btn-primary"><i class="fas fa-store"></i> Activer depuis Marketplace</a>
    @elseif($connected)
      @if($googleDriveInstalled)
      <a href="{{ route('google-drive.index') }}" class="btn btn-secondary"><i class="fab fa-google-drive"></i> Ouvrir Google Drive</a>
      @endif
      <button class="btn btn-secondary" id="dbxRefreshBtn"><i class="fas fa-rotate"></i> Actualiser</button>
      <button class="btn btn-secondary" id="dbxTrashBtn"><i class="fas fa-trash"></i> Corbeille</button>
      <button class="btn btn-primary" data-modal-open="dbxFolderModal"><i class="fas fa-folder-plus"></i> Nouveau dossier</button>
      <button class="btn btn-primary" data-modal-open="dbxUploadModal"><i class="fas fa-upload"></i> Importer</button>
      <button class="btn btn-danger" id="dbxDisconnectBtn"><i class="fas fa-link-slash"></i> Deconnecter Dropbox</button>
    @else
      <a href="{{ route('dropbox.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-dropbox"></i> Connecter Dropbox</a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Migration Dropbox requise</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Les tables Dropbox sont absentes. Lancez la migration avant d'utiliser ce module.</p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family: "DM Sans", sans-serif;font-size:12px;color:var(--c-ink-80);">php artisan migrate</div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>Extension non active</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Dropbox est disponible dans le CRM, mais votre tenant doit encore l'activer depuis Marketplace.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'dropbox') }}" class="btn btn-primary"><i class="fas fa-store"></i> Ouvrir la fiche application</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Voir toutes les applications</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fab fa-dropbox"></i><h3>Connexion Dropbox</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Aucun compte Dropbox n'est encore relie a cet espace. Connectez votre compte pour gerer vos dossiers, vos fichiers et vos liens de partage.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('dropbox.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-dropbox"></i> Connecter maintenant</a>
      @if($googleDriveInstalled)
      <a href="{{ route('google-drive.index') }}" class="btn btn-secondary"><i class="fab fa-google-drive"></i> Utiliser Google Drive a la place</a>
      @endif
    </div>
  </div>
</div>
@else
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(0,97,255,.12);color:#0061ff"><i class="fas fa-hard-drive"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="dbxUsedStorage">0 GB</div>
      <div class="stat-label">Stockage utilise</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-database"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="dbxTotalStorage">0 GB</div>
      <div class="stat-label">Quota total</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(0,97,255,.08);color:#0061ff"><i class="fas fa-folder-tree"></i></div>
    <div class="stat-body">
      <div class="stat-value" id="dbxCurrentFolderName">Racine</div>
      <div class="stat-label">Dossier courant</div>
    </div>
  </div>
</div>

@if($googleDriveInstalled)
<div class="info-card" style="margin-bottom:20px;">
  <div class="info-card-header"><i class="fab fa-google-drive"></i><h3>Google Drive est aussi disponible</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Si vous preferez sauvegarder certains documents dans Google Drive, ouvrez l'application correspondante pour utiliser l'autre stockage du CRM.</p>
    <a href="{{ route('google-drive.index') }}" class="btn btn-secondary"><i class="fab fa-google-drive"></i> Basculer vers Google Drive</a>
  </div>
</div>
@endif

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Fichiers Dropbox</span>
    <span class="table-count" id="dbxCount">0 element(s)</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="dbxSearchInput" placeholder="Rechercher un fichier ou dossier...">
    </div>
    <button class="btn btn-ghost btn-sm" id="dbxBackBtn"><i class="fas fa-arrow-left"></i> Retour</button>
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
    <tbody id="dbxFilesTableBody"></tbody>
  </table>
</div>
@endif

<div class="modal-overlay" id="dbxFolderModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">Nouveau dossier Dropbox</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Nom du dossier</label>
        <input type="text" class="form-control" id="dbxFolderName" maxlength="255" placeholder="Ex: Contrats clients">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="dbxSaveFolderBtn"><i class="fas fa-check"></i> Creer</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="dbxUploadModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title">Importer un fichier dans Dropbox</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="dbx-upload-modal">
        <div class="dbx-upload-summary">
          <div class="dbx-upload-summary-icon"><i class="fas fa-folder-open"></i></div>
          <div>
            <div class="dbx-upload-summary-label">Destination</div>
            <div class="dbx-upload-summary-value" id="dbxUploadTargetName">Racine</div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="dbxUploadModalInput">Fichier a importer</label>
          <label class="dbx-upload-picker" for="dbxUploadModalInput">
            <span class="dbx-upload-picker-icon"><i class="fas fa-cloud-arrow-up"></i></span>
            <span class="dbx-upload-picker-body">
              <strong>Choisir un ou plusieurs fichiers</strong>
              <small>Selection multiple activee. Taille max par fichier: 100 MB.</small>
            </span>
          </label>
          <input type="file" id="dbxUploadModalInput" class="dbx-upload-native-input" hidden multiple>
          <div class="dbx-upload-selected" id="dbxUploadSelectedName">Aucun fichier selectionne</div>
          <div class="dbx-upload-errors" id="dbxUploadErrors" style="display:none;"></div>
          <div class="dbx-upload-files" id="dbxUploadFilesList"></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="dbxSaveUploadBtn" data-loading-text="Import en cours..."><i class="fas fa-upload"></i> Importer</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="dbxShareModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title">Partager un element Dropbox</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="dbx-share-modal">
        <div class="dbx-upload-summary">
          <div class="dbx-upload-summary-icon"><i class="fas fa-share-nodes"></i></div>
          <div>
            <div class="dbx-upload-summary-label">Element selectionne</div>
            <div class="dbx-upload-summary-value" id="dbxShareTargetName">Aucun element selectionne</div>
          </div>
        </div>

        <div class="dbx-share-grid">
          <div class="form-group">
            <label class="form-label" for="dbxShareType">Type de partage</label>
            <select class="form-control" id="dbxShareType">
              <option value="anyone">Lien public</option>
              <option value="user">Utilisateur specifique</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="dbxShareRole">Niveau d acces</label>
            <select class="form-control" id="dbxShareRole">
              <option value="reader">Lecture</option>
              <option value="editor">Edition</option>
            </select>
          </div>
        </div>

        <div class="form-group" id="dbxShareEmailGroup" style="display:none;">
          <label class="form-label" for="dbxShareEmail">Email du destinataire</label>
          <input type="email" class="form-control" id="dbxShareEmail" maxlength="255" placeholder="contact@entreprise.com">
          <div class="dbx-share-help">L'email est obligatoire si vous partagez avec un utilisateur specifique.</div>
        </div>

        <div class="dbx-share-help">
          Le CRM cree un lien Dropbox et tente de le copier automatiquement dans le presse-papiers apres validation.
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="dbxSaveShareBtn" data-loading-text="Partage en cours..."><i class="fas fa-share-nodes"></i> Creer le lien</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="dbxTrashModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Corbeille Dropbox</div>
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
        <tbody id="dbxTrashTableBody"></tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger" id="dbxEmptyTrashBtn"><i class="fas fa-trash-can"></i> Vider la corbeille</button>
      <button class="btn btn-secondary" data-modal-close>Fermer</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.DROPBOX_ROUTES = {
  connect: '{{ route('dropbox.oauth.connect') }}',
  disconnect: '{{ route('dropbox.oauth.disconnect') }}',
  filesData: '{{ route('dropbox.files.data') }}',
  stats: '{{ route('dropbox.stats') }}',
  createFolder: '{{ route('dropbox.folders.store') }}',
  upload: '{{ route('dropbox.files.upload') }}',
  trashData: '{{ route('dropbox.trash.data') }}',
  emptyTrash: '{{ route('dropbox.trash.empty') }}',
  search: '{{ route('dropbox.search') }}',
  fileBase: @json(rtrim(route('dropbox.index'), '/') . '/files'),
};

window.DROPBOX_BOOTSTRAP = {
  connected: @json((bool) $connected),
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.DropboxModule) {
    window.DropboxModule.boot(window.DROPBOX_BOOTSTRAP);
  }

  @if(session('success'))
  Toast.success(@json(__('dropbox::messages.common.success')), @json(session('success')));
  @endif

  @if(session('error'))
  Toast.error(@json(__('dropbox::messages.common.error')), @json(session('error')));
  @endif
});
</script>
@endpush
