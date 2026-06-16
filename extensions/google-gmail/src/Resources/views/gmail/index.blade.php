@extends('google-gmail::layouts.gmail')

@section('title', data_get($currentExtensionMeta, 'name', __('google-gmail::messages.page.title')))

@section('ggm_breadcrumb')
  <a href="{{ route('marketplace.index') }}">{{ __('google-gmail::messages.breadcrumb.applications') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ data_get($currentExtensionMeta, 'name', __('google-gmail::messages.page.title')) }}</span>
@endsection

@section('ggm_content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fas fa-envelope-open-text')), 'bg' => '#fee2e2', 'color' => '#ea4335', 'alt' => data_get($currentExtensionMeta, 'name', 'Google Gmail')])
      <h1 style="margin:0;">{{ data_get($currentExtensionMeta, 'name', 'Google Gmail') }}</h1>
    </div>
    <p>Centralisez vos emails Gmail dans votre CRM avec une interface mail moderne.</p>
  </div>
  <div class="page-header-actions">
    @if(!$storageReady)
      <button class="btn btn-warning" disabled><i class="fas fa-database"></i> Migration requise</button>
    @elseif(!$extensionActive)
      <a href="{{ route('marketplace.show', 'google-gmail') }}" class="btn btn-primary"><i class="fas fa-store"></i> Activer depuis le Marketplace</a>
    @elseif($connected)
      <button class="btn btn-secondary" id="ggmRefreshBtn" data-loading-text="Actualisation..."><i class="fas fa-rotate"></i> Actualiser</button>
      <button class="btn btn-danger" id="ggmDisconnectBtn"><i class="fas fa-link-slash"></i> Deconnecter</button>
    @else
      <a href="{{ route('google-gmail.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connecter Google Gmail</a>
    @endif
  </div>
</div>

@if(!$storageReady)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-database"></i><h3>Migration base de donnees requise</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Les tables Google Gmail sont absentes. Lancez les migrations avant utilisation.</p>
    <div style="background:var(--surface-2);border:1px solid var(--c-ink-05);border-radius:var(--r-sm);padding:10px 12px;font-family: "DM Sans", sans-serif;font-size:12px;color:var(--c-ink-80);">php artisan migrate</div>
  </div>
</div>
@elseif(!$extensionActive)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-lock"></i><h3>Application non activee</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Google Gmail est installee mais pas encore activee pour ce tenant. Activez cette application depuis le Marketplace.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="{{ route('marketplace.show', 'google-gmail') }}" class="btn btn-primary"><i class="fas fa-store"></i> Ouvrir la fiche application</a>
      <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Parcourir les applications</a>
    </div>
  </div>
</div>
@elseif(!$connected)
<div class="info-card" style="max-width:920px;">
  <div class="info-card-header"><i class="fas fa-envelope-open-text" style="color:#ea4335;"></i><h3>Connexion Google Gmail</h3></div>
  <div class="info-card-body">
    <p style="margin-top:0;color:var(--c-ink-60);font-size:14px;line-height:1.7;">Le tenant n est pas encore connecte a Gmail. Lancez OAuth pour activer la boite mail complete.</p>
    <a href="{{ route('google-gmail.oauth.connect') }}" class="btn btn-primary"><i class="fab fa-google"></i> Connecter maintenant</a>
  </div>
</div>
@else
<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon" style="background:#ea433518;color:#ea4335;"><i class="fas fa-inbox"></i></div><div class="stat-body"><div class="stat-value" id="ggmStatInbox">0</div><div class="stat-label">Inbox</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#f59e0b18;color:#f59e0b;"><i class="fas fa-envelope-open"></i></div><div class="stat-body"><div class="stat-value" id="ggmStatUnread">0</div><div class="stat-label">Non lus</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#10b98118;color:#10b981;"><i class="fas fa-paper-plane"></i></div><div class="stat-body"><div class="stat-value" id="ggmStatSent">0</div><div class="stat-label">Envoyes</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#6366f118;color:#6366f1;"><i class="fas fa-file-lines"></i></div><div class="stat-body"><div class="stat-value" id="ggmStatDraft">0</div><div class="stat-label">Brouillons</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#ef444418;color:#ef4444;"><i class="fas fa-trash"></i></div><div class="stat-body"><div class="stat-value" id="ggmStatTrash">0</div><div class="stat-label">Corbeille</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:#facc1518;color:#ca8a04;"><i class="fas fa-star"></i></div><div class="stat-body"><div class="stat-value" id="ggmStatStarred">0</div><div class="stat-label">Favoris</div></div></div>
</div>

<div class="row" style="align-items:flex-start;">
  <div class="col-3">
    <div class="info-card" style="margin-bottom:16px;">
      <div class="info-card-header"><i class="fas fa-user-circle"></i><h3>Compte connecte</h3></div>
      <div class="info-card-body">
        @if($token?->google_avatar_url)
          <div style="text-align:center;margin-bottom:12px;"><img src="{{ $token->google_avatar_url }}" style="width:56px;height:56px;border-radius:50%;border:2px solid var(--c-ink-05);" alt=""></div>
        @endif
        <div class="info-row"><span class="info-row-label">Nom</span><span class="info-row-value">{{ $token?->google_name ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Email</span><span class="info-row-value" style="font-size:12px;">{{ $token?->google_email ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Connecte le</span><span class="info-row-value">{{ $token?->connected_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
        <div class="info-row"><span class="info-row-label">Derniere synchro</span><span class="info-row-value" id="ggmLastSyncLabel">{{ $token?->last_sync_at?->format('d/m/Y H:i') ?? 'Jamais' }}</span></div>
      </div>
    </div>

    <div class="info-card">
      <div class="info-card-header"><i class="fas fa-folder-tree"></i><h3>Dossiers Gmail</h3></div>
      <div class="info-card-body" style="padding:10px;">
        <div id="ggmLabelsList" class="ggm-labels-list"></div>
      </div>
    </div>
  </div>

  <div class="col-9">
    <div class="ggm-workspace">
      <div class="ggm-toolbar">
        <div class="table-search ggm-search">
          <i class="fas fa-search"></i>
          <input type="text" id="ggmSearchInput" placeholder="Rechercher dans Gmail (expediteur, objet, contenu)..." autocomplete="off">
        </div>
        <button class="btn btn-secondary btn-sm" id="ggmSettingsBtn" data-modal-open="ggmSettingsModal"><i class="fas fa-gear"></i> Parametres</button>
        <button class="btn btn-primary btn-sm" id="ggmComposeBtn" data-modal-open="ggmComposeModal"><i class="fas fa-pen"></i> Composer</button>
        <button class="btn btn-secondary btn-sm" id="ggmPrevPageBtn" disabled><i class="fas fa-chevron-left"></i></button>
        <button class="btn btn-secondary btn-sm" id="ggmNextPageBtn" disabled><i class="fas fa-chevron-right"></i></button>
      </div>

      <div class="ggm-layout">
        <div class="ggm-list-panel">
          <div class="ggm-list-header">
            <span id="ggmListTitle">Messages</span>
            <span id="ggmListCount">0</span>
          </div>
          <div id="ggmMessagesList" class="ggm-messages-list"></div>
        </div>

        <div class="ggm-view-panel">
          <div id="ggmEmptyState" class="table-empty" style="height:100%;justify-content:center;">
            <div class="table-empty-icon"><i class="fas fa-envelope-open"></i></div>
            <h3>Aucun message selectionne</h3>
            <p>Selectionnez un email depuis la liste pour lire le contenu.</p>
          </div>

          <div id="ggmMessageView" style="display:none;">
            <div class="ggm-message-top">
              <div>
                <h3 id="ggmMessageSubject">Sujet</h3>
                <div class="ggm-message-meta">
                  <span id="ggmMessageFrom"></span>
                  <span id="ggmMessageDate"></span>
                </div>
              </div>
              <div class="ggm-message-actions">
                <button class="btn btn-secondary btn-sm" id="ggmReplyBtn" data-modal-open="ggmReplyModal"><i class="fas fa-reply"></i> Repondre</button>
                <button class="btn btn-secondary btn-sm" id="ggmForwardBtn" data-modal-open="ggmForwardModal"><i class="fas fa-share"></i> Transferer</button>
                <button class="btn btn-secondary btn-sm" id="ggmToggleReadBtn"><i class="fas fa-envelope-open"></i> Lu/Non lu</button>
                <button class="btn btn-secondary btn-sm" id="ggmToggleStarBtn"><i class="fas fa-star"></i> Favori</button>
                <button class="btn btn-secondary btn-sm" id="ggmArchiveBtn"><i class="fas fa-box-archive"></i> Archiver</button>
                <button class="btn btn-danger btn-sm" id="ggmTrashBtn"><i class="fas fa-trash"></i> Corbeille</button>
              </div>
            </div>

            <div class="ggm-recipients">
              <div><strong>A:</strong> <span id="ggmMessageTo">-</span></div>
              <div><strong>Cc:</strong> <span id="ggmMessageCc">-</span></div>
            </div>

            <div id="ggmAttachmentsWrap" class="ggm-attachments" style="display:none;">
              <div class="ggm-attachments-title"><i class="fas fa-paperclip"></i> Pieces jointes</div>
              <div id="ggmAttachmentsList" class="ggm-attachments-list"></div>
            </div>

            <div id="ggmMessageBody" class="ggm-message-body"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

<div class="modal-overlay" id="ggmAttachmentPreviewModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="ggmAttachmentPreviewTitle">{{ __('google-gmail::messages.attachments.preview_file') }}</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div id="ggmAttachmentPreviewBody" class="ggm-attachment-preview-body"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>{{ __('google-gmail::messages.common.close') }}</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="ggmComposeModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:#ea433518;color:#ea4335;"><i class="fas fa-pen"></i></div>
      <div><div class="modal-title">Nouveau message</div><div class="modal-subtitle">Envoyer un email depuis Gmail</div></div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="ggmComposeForm">
        <div class="row">
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">A <span class="required">*</span></label>
              <div class="ggm-tags" data-tags="ggmComposeTo">
                <div class="ggm-tags-list" id="ggmComposeToChips"></div>
                <input type="text" class="ggm-tags-input" id="ggmComposeToInput" placeholder="Ajoutez un email puis tapez virgule, Entrée ou Tab">
                <input type="hidden" name="to" id="ggmComposeTo" required>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Cc</label>
              <div class="ggm-tags" data-tags="ggmComposeCc">
                <div class="ggm-tags-list" id="ggmComposeCcChips"></div>
                <input type="text" class="ggm-tags-input" id="ggmComposeCcInput" placeholder="Copie">
                <input type="hidden" name="cc" id="ggmComposeCc">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Cci</label>
              <div class="ggm-tags" data-tags="ggmComposeBcc">
                <div class="ggm-tags-list" id="ggmComposeBccChips"></div>
                <input type="text" class="ggm-tags-input" id="ggmComposeBccInput" placeholder="Copie cachee">
                <input type="hidden" name="bcc" id="ggmComposeBcc">
              </div>
            </div>
          </div>
          <div class="col-12"><div class="form-group"><label class="form-label">Sujet <span class="required">*</span></label><input type="text" class="form-control" name="subject" id="ggmComposeSubject" maxlength="500" required></div></div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Message</label>
              <div class="ggm-editor" data-editor-root data-editor-id="ggmComposeBodyEditor" data-input-id="ggmComposeBody" data-attachment-input="ggmComposeAttachments">
                <div class="ggm-editor-toolbar">
                  <button type="button" class="ggm-editor-btn" data-cmd="bold"><i class="fas fa-bold"></i></button>
                  <button type="button" class="ggm-editor-btn" data-cmd="italic"><i class="fas fa-italic"></i></button>
                  <button type="button" class="ggm-editor-btn" data-cmd="underline"><i class="fas fa-underline"></i></button>
                  <button type="button" class="ggm-editor-btn" data-cmd="insertUnorderedList"><i class="fas fa-list-ul"></i></button>
                  <button type="button" class="ggm-editor-btn" data-cmd="insertOrderedList"><i class="fas fa-list-ol"></i></button>
                  <button type="button" class="ggm-editor-btn" data-cmd="createLink"><i class="fas fa-link"></i></button>
                  <input type="color" class="ggm-editor-color" data-cmd="foreColor" value="#0f172a" title="Couleur texte">
                  <input type="color" class="ggm-editor-color" data-cmd="hiliteColor" value="#fff59d" title="Surlignage">
                  <button type="button" class="ggm-editor-btn" data-cmd="removeFormat"><i class="fas fa-eraser"></i></button>
                  <span class="ggm-editor-spacer"></span>
                  <button type="button" class="ggm-editor-btn" data-editor-action="attach-file" data-input-target="ggmComposeAttachments" title="Joindre des fichiers">
                    <i class="fas fa-paperclip"></i>
                  </button>
                  <button type="button" class="ggm-editor-btn" data-editor-action="insert-signature" title="Inserer la signature">
                    <i class="fas fa-signature"></i>
                  </button>
                </div>
                <div id="ggmComposeBodyEditor" class="ggm-editor-area" contenteditable="true" data-placeholder="Ecrivez votre email..."></div>
              </div>
              <textarea class="form-control ggm-editor-source" name="body_html" id="ggmComposeBody" rows="10" hidden></textarea>
              <div class="ggm-editor-footer">
                <input type="file" class="ggm-file-input-hidden" id="ggmComposeAttachments" name="attachments[]" multiple>
                <div class="ggm-editor-footer-row">
                  <div class="ggm-editor-help">
                    <span class="ggm-attach-indicator">
                      <i class="fas fa-paperclip"></i>
                      <span data-attachment-summary-for="ggmComposeAttachments">Aucune piece jointe</span>
                    </span>
                    <span>Utilisez l icone trombone pour ajouter des pieces jointes.</span>
                  </div>
                  <div class="ggm-signature-select-wrap">
                    <label class="ggm-signature-select-label" for="ggmComposeSignatureMode">Signature</label>
                    <select class="form-control ggm-signature-select" id="ggmComposeSignatureMode" name="signature_mode">
                      <option value="auto">Auto</option>
                      <option value="with_signature">Avec signature</option>
                      <option value="without_signature">Sans signature</option>
                    </select>
                  </div>
                </div>
                <div class="ggm-file-list" id="ggmComposeAttachmentsList"></div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="ggmComposeSendBtn" data-loading-text="Envoi..."><i class="fas fa-paper-plane"></i> Envoyer</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="ggmReplyModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title">Repondre</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="ggmReplyForm">
        <div class="form-group">
          <label class="form-label">Message</label>
          <div class="ggm-editor" data-editor-root data-editor-id="ggmReplyBodyEditor" data-input-id="ggmReplyBody">
            <div class="ggm-editor-toolbar">
              <button type="button" class="ggm-editor-btn" data-cmd="bold"><i class="fas fa-bold"></i></button>
              <button type="button" class="ggm-editor-btn" data-cmd="italic"><i class="fas fa-italic"></i></button>
              <button type="button" class="ggm-editor-btn" data-cmd="underline"><i class="fas fa-underline"></i></button>
              <button type="button" class="ggm-editor-btn" data-cmd="insertUnorderedList"><i class="fas fa-list-ul"></i></button>
              <button type="button" class="ggm-editor-btn" data-cmd="insertOrderedList"><i class="fas fa-list-ol"></i></button>
              <button type="button" class="ggm-editor-btn" data-cmd="createLink"><i class="fas fa-link"></i></button>
              <input type="color" class="ggm-editor-color" data-cmd="foreColor" value="#0f172a" title="Couleur texte">
              <input type="color" class="ggm-editor-color" data-cmd="hiliteColor" value="#fff59d" title="Surlignage">
              <button type="button" class="ggm-editor-btn" data-cmd="removeFormat"><i class="fas fa-eraser"></i></button>
            </div>
            <div id="ggmReplyBodyEditor" class="ggm-editor-area" contenteditable="true" data-placeholder="Ecrivez votre reponse..."></div>
          </div>
          <textarea class="form-control ggm-editor-source" name="body_html" id="ggmReplyBody" rows="8" hidden></textarea>
        </div>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Cc</label>
              <div class="ggm-tags" data-tags="ggmReplyCc">
                <div class="ggm-tags-list" id="ggmReplyCcChips"></div>
                <input type="text" class="ggm-tags-input" id="ggmReplyCcInput" placeholder="Copie">
                <input type="hidden" name="cc" id="ggmReplyCc">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Cci</label>
              <div class="ggm-tags" data-tags="ggmReplyBcc">
                <div class="ggm-tags-list" id="ggmReplyBccChips"></div>
                <input type="text" class="ggm-tags-input" id="ggmReplyBccInput" placeholder="Copie cachee">
                <input type="hidden" name="bcc" id="ggmReplyBcc">
              </div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Pieces jointes</label>
          <input type="file" class="form-control ggm-file-input" id="ggmReplyAttachments" name="attachments[]" multiple>
          <div class="ggm-file-list" id="ggmReplyAttachmentsList"></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="ggmReplySendBtn" data-loading-text="Envoi..."><i class="fas fa-paper-plane"></i> Envoyer la reponse</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="ggmForwardModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title">Transferer</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="ggmForwardForm">
        <div class="form-group">
          <label class="form-label">A <span class="required">*</span></label>
          <div class="ggm-tags" data-tags="ggmForwardTo">
            <div class="ggm-tags-list" id="ggmForwardToChips"></div>
            <input type="text" class="ggm-tags-input" id="ggmForwardToInput" placeholder="Ajoutez un email puis tapez virgule, Entrée ou Tab">
            <input type="hidden" name="to" id="ggmForwardTo" required>
          </div>
        </div>
        <div class="row">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Cc</label>
              <div class="ggm-tags" data-tags="ggmForwardCc">
                <div class="ggm-tags-list" id="ggmForwardCcChips"></div>
                <input type="text" class="ggm-tags-input" id="ggmForwardCcInput" placeholder="Copie">
                <input type="hidden" name="cc" id="ggmForwardCc">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Cci</label>
              <div class="ggm-tags" data-tags="ggmForwardBcc">
                <div class="ggm-tags-list" id="ggmForwardBccChips"></div>
                <input type="text" class="ggm-tags-input" id="ggmForwardBccInput" placeholder="Copie cachee">
                <input type="hidden" name="bcc" id="ggmForwardBcc">
              </div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Message additionnel</label>
          <div class="ggm-editor" data-editor-root data-editor-id="ggmForwardBodyEditor" data-input-id="ggmForwardBody">
            <div class="ggm-editor-toolbar">
              <button type="button" class="ggm-editor-btn" data-cmd="bold"><i class="fas fa-bold"></i></button>
              <button type="button" class="ggm-editor-btn" data-cmd="italic"><i class="fas fa-italic"></i></button>
              <button type="button" class="ggm-editor-btn" data-cmd="underline"><i class="fas fa-underline"></i></button>
              <button type="button" class="ggm-editor-btn" data-cmd="insertUnorderedList"><i class="fas fa-list-ul"></i></button>
              <button type="button" class="ggm-editor-btn" data-cmd="insertOrderedList"><i class="fas fa-list-ol"></i></button>
              <button type="button" class="ggm-editor-btn" data-cmd="createLink"><i class="fas fa-link"></i></button>
              <input type="color" class="ggm-editor-color" data-cmd="foreColor" value="#0f172a" title="Couleur texte">
              <input type="color" class="ggm-editor-color" data-cmd="hiliteColor" value="#fff59d" title="Surlignage">
              <button type="button" class="ggm-editor-btn" data-cmd="removeFormat"><i class="fas fa-eraser"></i></button>
            </div>
            <div id="ggmForwardBodyEditor" class="ggm-editor-area" contenteditable="true" data-placeholder="Ajoutez un message pour le transfert..."></div>
          </div>
          <textarea class="form-control ggm-editor-source" name="body_html" id="ggmForwardBody" rows="6" hidden></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Pieces jointes</label>
          <input type="file" class="form-control ggm-file-input" id="ggmForwardAttachments" name="attachments[]" multiple>
          <div class="ggm-file-list" id="ggmForwardAttachmentsList"></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="ggmForwardSendBtn" data-loading-text="Transfert..."><i class="fas fa-share"></i> Transferer</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="ggmSettingsModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:#0ea5e918;color:#0284c7;"><i class="fas fa-gear"></i></div>
      <div><div class="modal-title">Parametres Gmail</div><div class="modal-subtitle">Signature, Cc/Cci par defaut et affichage dossiers</div></div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <form id="ggmSettingsForm">
        <div class="row">
          <div class="col-12">
            <label class="checkbox-item" style="margin:0 0 10px;">
              <input type="checkbox" id="ggmSettingsSignatureEnabled" name="signature_enabled" value="1">
              <span>Activer la signature email</span>
            </label>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Signature (HTML)</label>
              <div class="ggm-editor" data-editor-root data-editor-id="ggmSettingsSignatureEditor" data-input-id="ggmSettingsSignatureHtml">
                <div class="ggm-editor-toolbar">
                  <button type="button" class="ggm-editor-btn" data-cmd="bold"><i class="fas fa-bold"></i></button>
                  <button type="button" class="ggm-editor-btn" data-cmd="italic"><i class="fas fa-italic"></i></button>
                  <button type="button" class="ggm-editor-btn" data-cmd="underline"><i class="fas fa-underline"></i></button>
                  <button type="button" class="ggm-editor-btn" data-cmd="insertUnorderedList"><i class="fas fa-list-ul"></i></button>
                  <button type="button" class="ggm-editor-btn" data-cmd="createLink"><i class="fas fa-link"></i></button>
                  <button type="button" class="ggm-editor-btn" data-cmd="removeFormat"><i class="fas fa-eraser"></i></button>
                </div>
                <div id="ggmSettingsSignatureEditor" class="ggm-editor-area ggm-editor-area-sm" contenteditable="true" data-placeholder="Ex: Cordialement, Nom - Entreprise"></div>
              </div>
              <textarea id="ggmSettingsSignatureHtml" name="signature_html" hidden></textarea>
            </div>
          </div>
          <div class="col-6">
            <label class="checkbox-item" style="margin:0;">
              <input type="checkbox" id="ggmSettingsSignatureReplies" name="signature_on_replies" value="1" checked>
              <span>Ajouter dans les reponses</span>
            </label>
          </div>
          <div class="col-6">
            <label class="checkbox-item" style="margin:0;">
              <input type="checkbox" id="ggmSettingsSignatureForwards" name="signature_on_forwards" value="1" checked>
              <span>Ajouter dans les transferts</span>
            </label>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Cc par defaut</label>
              <div class="ggm-tags" data-tags="ggmSettingsDefaultCc">
                <div class="ggm-tags-list" id="ggmSettingsDefaultCcChips"></div>
                <input type="text" class="ggm-tags-input" id="ggmSettingsDefaultCcInput" placeholder="emails en copie">
                <input type="hidden" name="default_cc" id="ggmSettingsDefaultCc">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Cci par defaut</label>
              <div class="ggm-tags" data-tags="ggmSettingsDefaultBcc">
                <div class="ggm-tags-list" id="ggmSettingsDefaultBccChips"></div>
                <input type="text" class="ggm-tags-input" id="ggmSettingsDefaultBccInput" placeholder="emails en copie cachee">
                <input type="hidden" name="default_bcc" id="ggmSettingsDefaultBcc">
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Intervalle de rafraichissement (sec)</label>
              <select class="form-control" name="polling_interval_seconds" id="ggmSettingsPolling">
                <option value="15">15s</option>
                <option value="30">30s</option>
                <option value="45">45s</option>
                <option value="60">60s</option>
                <option value="90">90s</option>
                <option value="120">120s</option>
              </select>
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Dossiers principaux (max 10)</label>
              <div class="ggm-tags" data-tags="ggmSettingsMainLabels">
                <div class="ggm-tags-list" id="ggmSettingsMainLabelsChips"></div>
                <input type="text" class="ggm-tags-input" id="ggmSettingsMainLabelsInput" placeholder="INBOX, SENT, ...">
                <input type="hidden" name="main_labels_csv" id="ggmSettingsMainLabels">
              </div>
              <small style="color:var(--c-ink-40);">Utilisez les IDs Gmail: INBOX, STARRED, SENT, DRAFT, IMPORTANT, TRASH...</small>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="ggmSettingsSaveBtn" data-loading-text="Enregistrement..."><i class="fas fa-floppy-disk"></i> Enregistrer</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.GGMAIL_ROUTES = {
  connect: '{{ route('google-gmail.oauth.connect') }}',
  disconnect: '{{ route('google-gmail.oauth.disconnect') }}',
  stats: '{{ route('google-gmail.stats') }}',
  snapshotData: '{{ route('google-gmail.snapshot.data') }}',
  labelsData: '{{ route('google-gmail.labels.data') }}',
  messagesData: '{{ route('google-gmail.messages.data') }}',
  settingsData: '{{ route('google-gmail.settings.data') }}',
  settingsSave: '{{ route('google-gmail.settings.save') }}',
  send: '{{ route('google-gmail.messages.send') }}',
  threadsBase: @json(rtrim(route('google-gmail.index'), '/') . '/threads'),
  messageBase: @json(rtrim(route('google-gmail.index'), '/') . '/messages'),
};

window.GGMAIL_BOOTSTRAP = {
  connected: @json((bool) $connected),
  tenantId: @json((int) (auth()->user()->tenant_id ?? 0)),
  userId: @json((int) (auth()->id() ?? 0)),
  settings: @json($settings ?? []),
  socket: {
    enabled: @json((bool) $socketEnabled),
    clientUrl: @json((string) $socketClientUrl),
    path: @json((string) $socketPath),
    namespace: @json((string) $socketNamespace),
    transports: @json((array) config('google-gmail.socket.transports', ['websocket', 'polling'])),
  },
  i18n: @json($jsI18n ?? []),
};

document.addEventListener('DOMContentLoaded', function () {
  if (window.GoogleGmailModule) {
    window.GoogleGmailModule.boot(window.GGMAIL_BOOTSTRAP);
  }

  @if(session('success'))
  Toast.success('Succes', @json(session('success')));
  @endif

  @if(session('error'))
  Toast.error('Erreur', @json(session('error')));
  @endif
});
</script>
@endpush
