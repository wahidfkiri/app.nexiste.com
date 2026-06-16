@extends('notion-workspace::layouts.notion')

@section('title', data_get($currentExtensionMeta, 'name', 'Notion Workspace'))

@section('notion_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:#9ca3af"></i>
  <span style="color:#111827">{{ data_get($currentExtensionMeta, 'name', 'Notion Workspace') }}</span>
@endsection

@section('notion_content')
@php
  $linkedPagesCount = $linkedPages->count();
  $linkedClientsCount = $linkedPages->whereNotNull('client_id')->count();
  $linkedProjectsCount = $linkedPages->whereNotNull('project_id')->count();
@endphp

<div class="nw-notion-surface">
  <section class="nw-hero">
    <div class="nw-hero-copy">
      <div class="nw-kicker">Espace documentaire</div>
      <div class="nw-hero-title-row">
        @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fas fa-book-open')), 'bg' => '#f2ede3', 'color' => '#111827', 'alt' => data_get($currentExtensionMeta, 'name', 'Notion Workspace')])
        <div class="nw-hero-title-stack">
          <h1>{{ data_get($currentExtensionMeta, 'name', 'Notion Workspace') }}</h1>
          <div class="nw-state-row">
            @if($connected)
              <span class="nw-state-pill is-live">Connecté</span>
            @else
              <span class="nw-state-pill">Non connecté</span>
            @endif
            <span class="nw-state-text">Pages Notion réelles, contenu bloc par bloc, et liaisons CRM sans espace local dupliqué.</span>
          </div>
        </div>
      </div>
      <p class="nw-hero-lead">
        Un espace de documentation vivant pour vos clients, projets et rituels internes. Recherche rapide, lecture propre des blocs, et contexte CRM rattaché au bon endroit.
      </p>
      <div class="nw-hero-notes">
        <span><i class="fas fa-wand-magic-sparkles"></i> UX orientée contenu</span>
        <span><i class="fas fa-link"></i> Connexion OAuth réelle</span>
        <span><i class="fas fa-diagram-project"></i> Liaison client et projet</span>
      </div>
    </div>

    <div class="nw-hero-actions-panel">
      <div class="nw-actions-caption">Actions de l espace</div>
      <div class="nw-actions-stack">
        @if(!$storageReady)
          <button class="btn btn-warning" disabled>
            <i class="fas fa-database"></i> Migration requise
          </button>
        @elseif(!$extensionActive)
          <a class="btn btn-primary" href="{{ route('marketplace.show', 'notion-workspace') }}">
            <i class="fas fa-store"></i> Activer depuis le Marketplace
          </a>
        @elseif(!$oauthConfigured && !$connected)
          <button class="btn btn-warning" disabled>
            <i class="fas fa-key"></i> OAuth requis
          </button>
        @elseif($connected)
          <button class="btn btn-primary" type="button" data-modal-open="notionCreatePageModal" id="notionOpenCreateModalBtn">
            <i class="fas fa-plus"></i> Nouvelle page Notion
          </button>
          <a class="btn btn-secondary" href="{{ route('notion-workspace.index') }}">
            <i class="fas fa-rotate"></i> Rafraîchir la vue
          </a>
          <button class="btn btn-danger" type="button" id="notionDisconnectBtn">
            <i class="fas fa-unlink"></i> Déconnecter
          </button>
        @else
          <a class="btn btn-primary" href="{{ route('notion-workspace.connect') }}">
            <i class="fas fa-link"></i> Connecter Notion
          </a>
        @endif
      </div>
    </div>
  </section>

  <div class="nw-api-shell" id="notionWorkspaceApp">
    @if(!$storageReady)
      <section class="nw-empty-stage">
        <div class="nw-empty-stage-icon"><i class="fas fa-database"></i></div>
        <div>
          <h2>Migration base de données requise</h2>
          <p>Les tables Notion Workspace sont absentes. Lancez les migrations avant utilisation.</p>
          <div class="nw-code-chip">php artisan migrate</div>
        </div>
      </section>
    @elseif(!$extensionActive)
      <section class="nw-empty-stage">
        <div class="nw-empty-stage-icon"><i class="fas fa-lock"></i></div>
        <div>
          <h2>Application non activée</h2>
          <p>Cette extension n'est pas encore active pour ce tenant. Activez-la depuis le Marketplace avant d'autoriser une connexion Notion.</p>
          <div class="nw-inline-actions">
            <a href="{{ route('marketplace.show', 'notion-workspace') }}" class="btn btn-primary"><i class="fas fa-store"></i> Ouvrir la fiche application</a>
            <a href="{{ route('marketplace.index') }}" class="btn btn-secondary"><i class="fas fa-puzzle-piece"></i> Parcourir les applications</a>
          </div>
        </div>
      </section>
    @elseif(!$oauthConfigured && !$connected)
      <section class="nw-empty-stage">
        <div class="nw-empty-stage-icon"><i class="fas fa-key"></i></div>
        <div>
          <h2>Configuration OAuth Notion requise</h2>
          <p>La connexion Notion n'est pas encore configuree pour cette instance. Un administrateur doit finaliser les parametres OAuth avant que les utilisateurs puissent connecter leur espace Notion.</p>
        </div>
      </section>
    @elseif(!$connected)
      <section class="nw-empty-stage is-welcome">
        <div class="nw-empty-stage-icon"><i class="fas fa-book-open"></i></div>
        <div>
          <h2>Connecter votre vrai espace Notion</h2>
          <p>Cette page est pensée comme une porte d'entrée vers votre documentation réelle : recherche de pages partagées, lecture des blocs et liaisons CRM, sans dupliquer Notion dans le CRM.</p>
          <div class="nw-inline-actions">
            <a class="btn btn-primary" href="{{ route('notion-workspace.connect') }}">
              <i class="fas fa-link"></i> Connecter maintenant
            </a>
            <a href="{{ route('marketplace.show', 'notion-workspace') }}" class="btn btn-secondary">
              <i class="fas fa-store"></i> Ouvrir la fiche application
            </a>
          </div>
        </div>
      </section>
    @else
      <section class="nw-overview-band">
        <article class="nw-overview-metric">
          <span class="nw-overview-label">Pages reliées</span>
          <strong id="notionStatLinkedPages">{{ $linkedPagesCount }}</strong>
        </article>
        <article class="nw-overview-metric">
          <span class="nw-overview-label">Clients reliés</span>
          <strong id="notionStatLinkedClients">{{ $linkedClientsCount }}</strong>
        </article>
        <article class="nw-overview-metric">
          <span class="nw-overview-label">Projets reliés</span>
          <strong id="notionStatLinkedProjects">{{ $linkedProjectsCount }}</strong>
        </article>
        <article class="nw-overview-metric">
          <span class="nw-overview-label">Pages chargées</span>
          <strong id="notionStatLoadedPages">0</strong>
        </article>
      </section>

      <section class="nw-workbench">
        <aside class="nw-rail">
          <section class="nw-rail-card nw-identity-card">
            <div class="nw-rail-card-head">
              <span>Espace connecte</span>
              <small>Source officielle</small>
            </div>
            <div class="nw-identity-media">
              @if($workspaceImageUrl)
                <img src="{{ $workspaceImageUrl }}" alt="{{ $workspaceImageAlt }}">
              @else
                <div class="nw-identity-fallback">{{ $workspaceInitials }}</div>
              @endif
            </div>
            <div class="nw-identity-name">{{ $token?->notion_workspace_name ?? 'Espace Notion' }}</div>
            <div class="nw-identity-meta">{{ $token?->notion_user_name ?? 'Inconnu' }}</div>
            <div class="nw-identity-meta is-muted">{{ $token?->notion_user_email ?? '-' }}</div>
            @if($workspaceUserAvatarUrl)
              <div class="nw-identity-account">
                <img src="{{ $workspaceUserAvatarUrl }}" alt="{{ $token?->notion_user_name ?? 'Compte connecté' }}">
                <span>Compte connecté</span>
              </div>
            @endif
            <dl class="nw-facts">
              <div><dt>Connecté le</dt><dd>{{ $token?->connected_at?->format('d/m/Y H:i') ?? '-' }}</dd></div>
              <div><dt>Dernière synchro</dt><dd>{{ $token?->last_synced_at?->format('d/m/Y H:i') ?? 'Jamais' }}</dd></div>
            </dl>
          </section>

          <section class="nw-rail-card">
            <div class="nw-rail-card-head">
              <span>Pages liées au CRM</span>
              <small>Mémo contextuel</small>
            </div>
            <div class="nw-api-linked-list" id="notionLinkedList"></div>
          </section>
        </aside>

        <section class="nw-mainstage">
          <div class="nw-browser-head">
            <div>
              <h2>Bibliotheque partagee</h2>
              <p>Choisissez une page à gauche pour la lire comme un document de référence, puis rattachez-la au bon contexte CRM.</p>
            </div>
            <div class="nw-browser-tools">
              <div class="table-search nw-search-shell">
                <i class="fas fa-search"></i>
                <input type="text" id="notionSearchInput" placeholder="Rechercher une page Notion par titre..." autocomplete="off">
              </div>
              <button class="btn btn-ghost btn-sm" type="button" id="notionRefreshPagesBtn" title="Rafraîchir les pages partagées">
                <i class="fas fa-rotate"></i>
              </button>
            </div>
          </div>

          <div class="nw-browser-status-row">
            <div class="nw-api-status" id="notionSearchStatus">Prêt à charger les pages Notion.</div>
            <div class="nw-api-search-note">Le bouton de rafraîchissement reste utile : l'indexation Notion n'est pas toujours immédiate après partage.</div>
            <div class="nw-pages-result" id="notionPagesCountLabel">0 résultat</div>
          </div>

          <div class="nw-browser-grid">
            <aside class="nw-library-panel">
              <div class="nw-api-page-list" id="notionPageList"></div>
              <button class="btn btn-secondary nw-api-more" type="button" id="notionLoadMoreBtn" style="display:none;">
                Charger plus de pages
              </button>
            </aside>

            <section class="nw-paper-panel">
              <div class="nw-api-empty" id="notionPreviewEmpty">
                <div class="nw-api-empty-icon"><i class="fas fa-file-lines"></i></div>
                <h3>Aucune page chargée</h3>
                <p>Commencez par ouvrir une page de la bibliothèque. Son contenu s'affichera ici dans une lecture propre, avec son contexte CRM associé.</p>
              </div>

              <div class="nw-api-preview" id="notionPreview" style="display:none;">
                <div class="nw-api-preview-cover" id="notionPreviewCover"></div>

                <div class="nw-paper-sheet">
                  <div class="nw-paper-head">
                    <div class="nw-api-preview-title-wrap">
                      <div class="nw-api-preview-icon" id="notionPreviewIcon"></div>
                      <div>
                        <div class="nw-paper-kicker">Page Notion</div>
                        <h2 id="notionPreviewTitle">Sans titre</h2>
                        <div class="nw-api-preview-meta" id="notionPreviewMeta"></div>
                      </div>
                    </div>
                    <div class="nw-api-preview-actions">
                      <a class="btn btn-secondary" href="#" target="_blank" rel="noopener" id="notionOpenExternalBtn">
                        <i class="fas fa-arrow-up-right-from-square"></i> Ouvrir dans Notion
                      </a>
                    </div>
                  </div>

                  <div class="nw-context-card">
                    <div class="nw-context-card-head">
                      <div>
                        <h3>Liaison CRM</h3>
                        <p>Associez cette page à un client, un projet, ou un usage interne clairement nommé.</p>
                      </div>
                    </div>
                    <form id="notionLinkForm" class="nw-api-link-form">
                      <input type="hidden" id="notionLinkId">
                      <div class="nw-api-grid">
                        <label class="nw-field">
                          <span>Client</span>
                          <select id="notionLinkClientId" class="form-control">
                            <option value="">Aucun</option>
                            @foreach($clients as $client)
                              <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                            @endforeach
                          </select>
                        </label>
                        <label class="nw-field">
                          <span>Projet</span>
                          <select id="notionLinkProjectId" class="form-control">
                            <option value="">Aucun</option>
                            @foreach($projects as $project)
                              <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                          </select>
                        </label>
                        <label class="nw-field">
                          <span>Contexte</span>
                          <input type="text" id="notionLinkContextLabel" class="form-control" maxlength="120" placeholder="Client notes, SOP, onboarding...">
                        </label>
                      </div>
                      <label class="nw-field">
                        <span>Notes internes</span>
                        <textarea id="notionLinkNotes" class="form-control" rows="3" maxlength="4000" placeholder="Pourquoi cette page est utile dans le CRM, responsable interne, point de vigilance..."></textarea>
                      </label>
                      <div class="nw-api-link-actions">
                        <span class="nw-api-status" id="notionLinkStatus">Aucun lien CRM sur cette page pour l'instant.</span>
                        <div>
                          <button class="btn btn-danger" type="button" id="notionDeleteLinkBtn" style="display:none;">
                            <i class="fas fa-trash"></i> Supprimer le lien
                          </button>
                          <button class="btn btn-primary" type="submit" id="notionSaveLinkBtn">
                            <i class="fas fa-link"></i> Enregistrer le lien CRM
                          </button>
                        </div>
                      </div>
                    </form>
                  </div>

                  <div class="nw-api-blocks-card">
                    <div class="nw-context-card-head">
                      <div>
                        <h3>Lecture bloc par bloc</h3>
                        <p>Récupération des blocs depuis l'API officielle Notion, pour lire la page sans sortir du CRM.</p>
                      </div>
                    </div>
                    <div class="nw-api-blocks" id="notionBlocksPreview"></div>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </section>
      </section>
    @endif
  </div>
</div>

<div class="modal-overlay" id="notionCreatePageModal">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:#f2ede3;color:#111827"><i class="fas fa-plus"></i></div>
      <div>
        <div class="modal-title">Créer une page dans Notion</div>
        <div class="modal-subtitle">La page est creee dans votre vrai espace Notion, avec un premier contenu pour lancer la note.</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Titre <span class="required">*</span></label>
        <input type="text" class="form-control" id="notionCreateTitle" maxlength="200" placeholder="Ex: Client ACME - Notes internes">
      </div>
      <div class="form-group">
        <label class="form-label">Parent Notion</label>
        <input type="text" class="form-control" id="notionCreateParentLabel" readonly placeholder="Aucun parent selectionne, la page sera creee a la racine de l espace">
      </div>
      <div class="form-group">
        <label class="form-label">Icône emoji</label>
        <input type="text" class="form-control" id="notionCreateIcon" maxlength="10" placeholder=":memo: ou emoji">
      </div>
      <div class="form-group">
        <label class="form-label">Premier contenu</label>
        <textarea class="form-control" id="notionCreateContent" rows="5" maxlength="20000" placeholder="Chaque ligne non vide sera envoyée comme paragraphe initial dans la page Notion."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" type="button" id="notionCreatePageSubmitBtn">
        <i class="fas fa-check"></i> Créer la page dans Notion
      </button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.NOTION_WORKSPACE_ROUTES = {
  connect: '{{ route('notion-workspace.connect') }}',
  disconnect: '{{ route('notion-workspace.disconnect') }}',
  pagesSearch: '{{ route('notion-workspace.pages.search') }}',
  pagesStore: '{{ route('notion-workspace.pages.store') }}',
  pageShowBase: @json(route('notion-workspace.pages.show', ['pageId' => '__PAGE_ID__'])),
  linksIndex: '{{ route('notion-workspace.links.index') }}',
  linksStore: '{{ route('notion-workspace.links.store') }}',
  linksBase: @json(route('notion-workspace.links.update', ['link' => '__LINK_ID__'])),
};

window.NOTION_WORKSPACE_BOOTSTRAP = {
  connected: @json($connected),
  workspaceName: @json($token?->notion_workspace_name),
  workspaceIcon: @json($token?->notion_workspace_icon),
  workspaceUser: @json($token?->notion_user_name),
  workspaceEmail: @json($token?->notion_user_email),
  links: @json($linkedPagesBootstrap),
  clients: @json($clientsBootstrap),
  projects: @json($projectsBootstrap),
};
</script>
@endpush
