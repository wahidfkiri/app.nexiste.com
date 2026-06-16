@extends('trello-integration::layouts.trello')

@section('title', data_get($currentExtensionMeta, 'name', 'Trello Integration'))

@section('trello_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:#94a3b8"></i>
  <span style="color:#0f172a">{{ data_get($currentExtensionMeta, 'name', 'Trello Integration') }}</span>
@endsection

@section('trello_content')
<div class="ti-shell">
  <section class="ti-hero">
    <div class="ti-hero-copy">
      <div class="ti-kicker">Board collaboration</div>
      <div class="ti-title-row">
        @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fab fa-trello')), 'bg' => '#e0f2fe', 'color' => '#0369a1', 'alt' => data_get($currentExtensionMeta, 'name', 'Trello Integration')])
        <div>
          <h1>{{ data_get($currentExtensionMeta, 'name', 'Trello Integration') }}</h1>
          <div class="ti-hero-status">
            @if($connected)
              <span class="ti-status-pill is-live">Connecte</span>
            @else
              <span class="ti-status-pill">Non connecte</span>
            @endif
            <span>Boards, listes et cartes Trello dans une interface CRM fluide, sans toucher au module Projet interne.</span>
          </div>
        </div>
      </div>
      <p class="ti-hero-lead">
        Une vue Trello dediee, pensee productivite: boards en galerie, colonnes horizontales, cartes editables, drag and drop et liaison optionnelle vers vos projets CRM.
      </p>
      <div class="ti-hero-notes">
        <span><i class="fas fa-table-cells-large"></i> Vue boards moderne</span>
        <span><i class="fas fa-grip"></i> Colonnes et cartes vivantes</span>
        <span><i class="fas fa-link"></i> Liaison Trello vers projet CRM</span>
      </div>
    </div>

    <div class="ti-hero-panel">
      <div class="ti-panel-label">Actions workspace</div>
      <div class="ti-panel-actions">
        @if(!$storageReady)
          <button class="btn btn-warning" disabled><i class="fas fa-database"></i> Migration requise</button>
        @elseif(!$extensionActive)
          <a class="btn btn-primary" href="{{ route('marketplace.show', 'trello-integration') }}">
            <i class="fas fa-store"></i> Activer depuis le Marketplace
          </a>
        @elseif(!$oauthConfigured && !$connected)
          <button class="btn btn-warning" disabled><i class="fas fa-key"></i> Cle API requise</button>
        @elseif(!$oauthReady && !$connected)
          <button class="btn btn-warning" disabled><i class="fas fa-circle-exclamation"></i> Configuration a corriger</button>
        @elseif($connected)
          <button class="btn btn-primary" type="button" id="trelloSyncBtn">
            <i class="fas fa-rotate"></i> Synchroniser Trello
          </button>
          <button class="btn btn-secondary" type="button" id="trelloClearBoardBtn">
            <i class="fas fa-grip"></i> Voir tous les boards
          </button>
          <button class="btn btn-danger" type="button" id="trelloDisconnectBtn">
            <i class="fas fa-unlink"></i> Deconnecter
          </button>
        @else
          <a class="btn btn-primary" href="{{ route('trello-integration.connect') }}">
            <i class="fas fa-link"></i> Connecter Trello
          </a>
        @endif
      </div>
    </div>
  </section>

  <div class="ti-workspace" id="trelloWorkspaceApp">
    @if(!$storageReady)
      <section class="ti-empty-stage">
        <div class="ti-empty-icon"><i class="fas fa-database"></i></div>
        <div>
          <h2>Migration requise</h2>
          <p>Les tables locales Trello ne sont pas encore presentes. Lancez les migrations avant d utiliser cette extension.</p>
          <div class="ti-code-chip">php artisan migrate</div>
        </div>
      </section>
    @elseif(!$extensionActive)
      <section class="ti-empty-stage">
        <div class="ti-empty-icon"><i class="fas fa-lock"></i></div>
        <div>
          <h2>Extension non active</h2>
          <p>Cette integration Trello doit d abord etre activee pour le tenant courant depuis le Marketplace.</p>
          <div class="ti-inline-actions">
            <a href="{{ route('marketplace.show', 'trello-integration') }}" class="btn btn-primary"><i class="fas fa-store"></i> Ouvrir la fiche application</a>
            <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Parcourir les applications</a>
          </div>
        </div>
      </section>
    @elseif(!$oauthConfigured && !$connected)
      <section class="ti-empty-stage">
        <div class="ti-empty-icon"><i class="fas fa-key"></i></div>
        <div>
          <h2>Configuration Trello requise</h2>
          <p>La cle API Trello n est pas encore configuree. Un administrateur doit finaliser la configuration avant la connexion utilisateur.</p>
        </div>
      </section>
    @elseif(!$oauthReady && !$connected)
      <section class="ti-empty-stage">
        <div class="ti-empty-icon"><i class="fas fa-circle-exclamation"></i></div>
        <div>
          <h2>Configuration Trello invalide</h2>
          <p>{{ data_get($configurationStatus, 'message', 'La configuration Trello doit etre corrigee avant de continuer.') }}</p>
          @if(data_get($configurationStatus, 'status') === 'invalid_key')
            <div class="ti-code-chip">Verifiez Trello Power-Ups Admin &gt; votre Power-Up &gt; API Key</div>
          @elseif(data_get($configurationStatus, 'status') === 'invalid_return_url')
            <div class="ti-code-chip">Allowed Origins: https://localhost</div>
            <div class="ti-code-chip">{{ data_get($configurationStatus, 'details.redirect_uri') }}</div>
          @endif
        </div>
      </section>
    @elseif(!$connected)
      <section class="ti-empty-stage is-welcome">
        <div class="ti-empty-icon"><i class="fab fa-trello"></i></div>
        <div>
          <h2>Connecter votre workspace Trello</h2>
          <p>Apportez vos boards dans le CRM avec une interface SaaS dediee: galerie de boards, colonnes horizontales, cartes drag and drop et edition rapide sans toucher au module Projet interne.</p>
          <div class="ti-inline-actions">
            <a class="btn btn-primary" href="{{ route('trello-integration.connect') }}">
              <i class="fas fa-link"></i> Connecter maintenant
            </a>
            <a href="{{ route('marketplace.show', 'trello-integration') }}" class="btn btn-secondary">
              <i class="fas fa-store"></i> Ouvrir la fiche application
            </a>
          </div>
        </div>
      </section>
    @else
      <section class="ti-overview-band">
        <article class="ti-overview-card">
          <span>Boards synchronises</span>
          <strong id="trelloBoardsCount">{{ count($trelloBootstrap['boards'] ?? []) }}</strong>
        </article>
        <article class="ti-overview-card">
          <span>Board courant</span>
          <strong id="trelloCurrentBoardLabel">{{ $selectedBoard?->name ?? 'Aucun' }}</strong>
        </article>
        <article class="ti-overview-card">
          <span>Workspace connecte</span>
          <strong>{{ $token?->trello_full_name ?: ($token?->trello_username ?: 'Trello') }}</strong>
        </article>
        <article class="ti-overview-card">
          <span>Derniere synchro</span>
          <strong id="trelloLastSyncLabel">{{ $token?->last_synced_at?->format('d/m/Y H:i') ?? 'Jamais' }}</strong>
        </article>
      </section>

      <section class="ti-grid">
        <aside class="ti-sidebar">
          <section class="ti-side-card ti-account-card">
            <div class="ti-side-head">
              <span>Compte connecte</span>
              <small>Trello API</small>
            </div>
            <div class="ti-account-row">
              @if($token?->trello_avatar_url)
                <img src="{{ $token->trello_avatar_url }}" alt="{{ $token->trello_full_name ?? 'Trello' }}" class="ti-account-avatar">
              @else
                <div class="ti-account-avatar is-fallback">{{ strtoupper(substr((string) ($token?->trello_full_name ?: $token?->trello_username ?: 'T'), 0, 1)) }}</div>
              @endif
              <div class="ti-account-copy">
                <strong>{{ $token?->trello_full_name ?: 'Compte Trello' }}</strong>
                <span>{{ $token?->trello_username ?: 'workspace' }}</span>
              </div>
            </div>
            <dl class="ti-facts">
              <div><dt>Connecte le</dt><dd>{{ $token?->connected_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
              <div><dt>Expiration</dt><dd>{{ $token?->token_expires_at?->format('d/m/Y H:i') ?? 'Session longue' }}</dd></div>
            </dl>
          </section>

          <section class="ti-side-card">
            <div class="ti-side-head">
              <span>Vos boards</span>
              <small>Naviguer vite</small>
            </div>
            <div class="table-search ti-board-search">
              <i class="fas fa-search"></i>
              <input type="text" id="trelloBoardSearchInput" placeholder="Filtrer les boards..." autocomplete="off">
            </div>
            <div class="ti-board-nav" id="trelloBoardNav"></div>
          </section>
        </aside>

        <section class="ti-main">
          <div class="ti-toolbar">
            <div>
              <h2>Boards Trello</h2>
              <p>Choisissez un board, ouvrez ses cartes et deplacez-les comme dans Trello, avec une experience plus integree au CRM.</p>
            </div>
            <div class="ti-toolbar-actions">
              <a class="btn btn-ghost btn-sm" href="{{ $selectedBoard?->url ?? '#' }}" target="_blank" rel="noopener" id="trelloOpenBoardLink" @if(!$selectedBoard) style="display:none" @endif>
                <i class="fas fa-arrow-up-right-from-square"></i> Ouvrir dans Trello
              </a>
            </div>
          </div>

          <div class="ti-status-row">
            <div class="ti-status" id="trelloStatus">Pret a afficher vos boards Trello.</div>
            <div class="ti-status-note">Le deplacement de cartes se synchronise avec Trello puis recharge le board pour garantir un etat fiable.</div>
          </div>

          <section class="ti-board-gallery-shell">
            <div class="ti-section-head">
              <div>
                <h3>Galerie des boards</h3>
                <p>Un acces rapide a tous vos espaces ouverts, avec compteur de listes et de cartes.</p>
              </div>
            </div>
            <div class="ti-board-gallery" id="trelloBoardGallery"></div>
          </section>

          <section class="ti-board-workspace" id="trelloBoardWorkspace">
            <div class="ti-board-empty" id="trelloBoardEmpty" @if($selectedBoard) style="display:none" @endif>
              <div class="ti-empty-icon"><i class="fas fa-table-columns"></i></div>
              <h3>Selectionnez un board</h3>
              <p>Choisissez un board dans la galerie ou la sidebar pour charger ses listes et ses cartes.</p>
            </div>

            <div class="ti-board-stage" id="trelloBoardStage" @if(!$selectedBoard) style="display:none" @endif>
              <div class="ti-board-header" id="trelloBoardHeader"></div>
              <div class="ti-lists-scroller" id="trelloListsScroller"></div>
            </div>
          </section>
        </section>
      </section>
    @endif
  </div>
</div>

<div class="modal-overlay" id="trelloCardModal" aria-hidden="true">
  <div class="modal" style="max-width:760px">
    <div class="modal-header">
      <div class="modal-header-copy">
        <h3 id="trelloCardModalTitle">Carte Trello</h3>
        <p class="text-muted" id="trelloCardModalMeta">Details et liaison CRM</p>
      </div>
      <div class="modal-header-actions">
        <a href="#" class="modal-icon-link" id="trelloCardModalOpenLink" target="_blank" rel="noopener" title="Ouvrir dans Trello">
          <i class="fas fa-arrow-up-right-from-square"></i>
        </a>
        <button class="modal-close" type="button" data-modal-close>&times;</button>
      </div>
    </div>
    <div class="modal-body">
      <form id="trelloCardForm" class="form-grid">
        <input type="hidden" name="card_id" id="trelloCardId">
        <div class="form-group">
          <label for="trelloCardName">Titre</label>
          <input type="text" id="trelloCardName" name="name" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="trelloCardDue">Echeance</label>
          <input type="datetime-local" id="trelloCardDue" name="due" class="form-control">
        </div>
        <div class="form-group full">
          <label for="trelloCardDescription">Description</label>
          <textarea id="trelloCardDescription" name="description" class="form-control" rows="7" placeholder="Ajoutez du contexte, des notes ou des instructions."></textarea>
        </div>
        <div class="form-group">
          <label for="trelloCardProject">Projet CRM lie</label>
          <select id="trelloCardProject" name="project_id" class="form-control">
            <option value="">Aucun projet lie</option>
          </select>
        </div>
        <div class="form-group full">
          <label for="trelloCardLinkNotes">Notes de liaison CRM</label>
          <textarea id="trelloCardLinkNotes" name="link_notes" class="form-control" rows="3" placeholder="Contexte interne visible dans le CRM uniquement."></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger" type="button" id="trelloArchiveCardBtn">
        <i class="fas fa-box-archive"></i> Archiver
      </button>
      <button class="btn btn-secondary" type="button" data-modal-close>Fermer</button>
      <button class="btn btn-primary" type="button" id="trelloSaveCardBtn">
        <i class="fas fa-save"></i> Enregistrer
      </button>
    </div>
  </div>
</div>
@endsection
