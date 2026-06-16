@extends('google-docx::layouts.docx')

@section('title', data_get($currentExtensionMeta, 'name', 'Google Docs'))

@section('gdx_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ data_get($currentExtensionMeta, 'name', 'Google Docs') }}</span>
@endsection

@section('gdx_content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fas fa-file-word')), 'bg' => '#dbeafe', 'color' => '#1a73e8', 'alt' => data_get($currentExtensionMeta, 'name', 'Google Docs')])
      <h1 style="margin:0;">{{ data_get($currentExtensionMeta, 'name', 'Google Docs') }}</h1>
    </div>
    <p>Créez, lisez et modifiez vos documents Google Docs depuis le CRM.</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled><i class="fas fa-database"></i> Migration requise</button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-docx') }}" class="btn btn-primary"><i class="fas fa-store"></i> Activer depuis le Marketplace</a>
    @elseif($connected)
      <button class="btn btn-secondary" id="gdxRefreshBtn"><i class="fas fa-rotate"></i> Actualiser</button>
      <button class="btn btn-primary" id="gdxCreateBtn" data-modal-open="gdxCreateModal"><i class="fas fa-plus"></i> Nouveau document</button>
      <button class="btn btn-danger" id="gdxDisconnectBtn"><i class="fas fa-link-slash"></i> Déconnecter</button>
    @else
      <a href="{{ route('google-docx.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connecter Google Docs</a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Migration base de données requise</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Les tables Google Docs sont absentes. Lancez les migrations avant d’utiliser ce module.</p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family: "DM Sans", sans-serif;font-size:12px;color:var(--c-ink-80);">php artisan migrate</div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>Application non activée</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Google Docs est installée mais non activée pour ce tenant. Activez d’abord l’application depuis le Marketplace.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-docx') }}" class="btn btn-primary"><i class="fas fa-store"></i> Ouvrir la fiche application</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Parcourir les applications</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-file-word" style="color:#1a73e8;"></i><h3>Connexion Google Docs</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Ce tenant n’est pas encore connecté à Google Docs. Lancez OAuth pour activer toutes les fonctionnalités du module.</p>
    <a href="{{ route('google-docx.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connecter maintenant</a>
  </div>
</div>
@else
<div class="row" style="align-items:flex-start;">
  <div class="col-3">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user-circle"></i><h3>Compte connecté</h3></div>
      <div class="info-card-body">
        @if($token?->google_avatar_url)
          <div style="text-align:center;margin-bottom:12px;"><img src="{{ $token->google_avatar_url }}" style="width:56px;height:56px;border-radius:50%;border:2px solid var(--c-ink-05);" alt=""></div>
        @endif
        <div class="info-row"><span class="info-row-label">Nom</span><span class="info-row-value">{{ $token?->google_name ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Email</span><span class="info-row-value" style="font-size:12px;">{{ $token?->google_email ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Connecté le</span><span class="info-row-value">{{ $token?->connected_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Dernière synchro</span><span class="info-row-value" id="gdxLastSyncLabel">{{ $token?->last_sync_at?->format('d/m/Y H:i') ?? 'Jamais' }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-chart-bar"></i><h3>Statistiques</h3></div>
      <div class="info-card-body">
        <div class="stat-card" style="padding:12px;">
          <div class="stat-icon" style="background:#1a73e818;color:#1a73e8;"><i class="fas fa-file-word"></i></div>
          <div class="stat-body">
            <div class="stat-value" id="gdxStatDocuments">0</div>
            <div class="stat-label">Documents</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-9">
    <div class="table-wrapper">
      <div class="table-header">
        <span class="table-title">Documents</span>
        <span class="table-count" id="gdxCount">0 résultat(s)</span>
        <div class="table-spacer"></div>
        <div class="table-search">
          <i class="fas fa-search"></i>
          <input type="text" id="gdxSearchInput" placeholder="Rechercher un document..." autocomplete="off">
        </div>
      </div>

      <table class="crm-table">
        <thead>
          <tr>
            <th>Titre</th>
            <th>Création</th>
            <th>Modification</th>
            <th>Partage</th>
            <th style="text-align:right;padding-right:20px;">Actions</th>
          </tr>
        </thead>
        <tbody id="gdxDocumentsTableBody"></tbody>
      </table>
    </div>
  </div>
</div>
@endif

<div class="modal-overlay" id="gdxCreateModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:#1a73e818;color:#1a73e8;"><i class="fas fa-file-word"></i></div>
      <div>
        <div class="modal-title">Nouveau document</div>
        <div class="modal-subtitle">Créer un document Google Docs</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Titre <span class="required">*</span></label>
        <input type="text" class="form-control" id="gdxDocumentTitle" maxlength="500" placeholder="Mon document">
      </div>
      <div class="form-group">
        <label class="form-label">Contenu initial</label>
        <textarea class="form-control" id="gdxDocumentContent" rows="6" placeholder="Contenu à insérer dans le document..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="gdxSaveDocumentBtn"><i class="fas fa-check"></i> Créer</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="gdxEditorModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:#1a73e818;color:#1a73e8;"><i class="fas fa-pen"></i></div>
      <div>
        <div class="modal-title" id="gdxEditorTitle">Document</div>
        <div class="modal-subtitle">Lecture et modifications rapides</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Contenu actuel</label>
        <textarea class="form-control" id="gdxCurrentContent" rows="9" readonly></textarea>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="form-group">
            <label class="form-label">Texte à ajouter</label>
            <textarea class="form-control" id="gdxAppendText" rows="4" placeholder="Ajouter du texte à la fin du document..."></textarea>
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Rechercher</label>
            <input type="text" class="form-control" id="gdxSearchText" maxlength="500" placeholder="Texte à remplacer">
          </div>
        </div>
        <div class="col-6">
          <div class="form-group">
            <label class="form-label">Remplacer par</label>
            <input type="text" class="form-control" id="gdxReplaceText" maxlength="5000" placeholder="Nouveau texte">
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" id="gdxAppendBtn"><i class="fas fa-plus"></i> Ajouter</button>
      <button class="btn btn-primary" id="gdxReplaceBtn"><i class="fas fa-repeat"></i> Remplacer</button>
      <button class="btn btn-secondary" data-modal-close>Fermer</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.GDOCX_ROUTES = {
  connect: '{{ route('google-docx.oauth.connect') }}',
  disconnect: '{{ route('google-docx.oauth.disconnect') }}',
  documentsData: '{{ route('google-docx.documents.data') }}',
  createDocument: '{{ route('google-docx.documents.store') }}',
  stats: '{{ route('google-docx.stats') }}',
  documentBase: @json(rtrim(route('google-docx.index'), '/') . '/documents'),
};

window.GDOCX_BOOTSTRAP = {
  connected: @json((bool) $connected),
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.GoogleDocxModule) {
    window.GoogleDocxModule.boot(window.GDOCX_BOOTSTRAP);
  }

  @if(session('success'))
  Toast.success(@json(__('google-docx::messages.common.success')), @json(session('success')));
  @endif

  @if(session('error'))
  if (window.GoogleDocxModule?.handleFailure) {
    window.GoogleDocxModule.handleFailure(
      @json(__('google-docx::messages.common.error')),
      @json(session('error')),
      @json(__('google-docx::messages.errors.unexpected'))
    );
  } else {
    Toast.error(@json(__('google-docx::messages.common.error')), @json(session('error')));
  }
  @endif
});
</script>
@endpush
