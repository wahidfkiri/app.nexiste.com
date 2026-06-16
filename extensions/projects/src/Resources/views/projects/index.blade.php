@extends('projects::layouts.projects')

@section('title', data_get($currentExtensionMeta, 'name', 'Gestion Projets'))

@section('projects_breadcrumb')
  <a href="{{ route('marketplace.index') }}">Applications</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ data_get($currentExtensionMeta, 'name', 'Gestion Projets') }}</span>
@endsection

@section('projects_content')
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title-heading">
      @include('layouts.partials.page-title-icon', ['icon' => (data_get($currentExtensionMeta, 'icon_url') ?: data_get($currentExtensionMeta, 'icon', 'fas fa-project-diagram')), 'bg' => '#e0f2fe', 'color' => '#0ea5e9', 'alt' => data_get($currentExtensionMeta, 'name', 'Gestion Projets')])
      <h1 style="margin:0;">{{ data_get($currentExtensionMeta, 'name', 'Gestion Projets') }}</h1>
    </div>
    <p>Pilotez vos projets, taches, membres et clients dans un workflow type Asana.</p>
  </div>
  <div class="page-header-actions">
    <button class="btn btn-primary" data-modal-open="projectModal" id="projectCreateBtn">
      <i class="fas fa-plus"></i> Nouveau projet
    </button>
  </div>
</div>

@if(!$clientsInstalled || !$googleCalendarInstalled)
<div class="integration-hints">
  @if(!$clientsInstalled)
    <div class="integration-hint-item">
      <div class="integration-hint-title"><i class="fas fa-building"></i> Module Clients non installe</div>
      <p>Installez le module Clients pour lier vos projets a des fiches clients.</p>
      <a class="btn btn-secondary btn-sm" href="{{ $clientsTargetUrl }}"><i class="fas fa-store"></i> Installer Clients</a>
    </div>
  @endif
  @if(!$googleCalendarInstalled)
    <div class="integration-hint-item">
      <div class="integration-hint-title"><i class="fas fa-calendar-days"></i> Google Calendar non installe</div>
      <p>Installez Google Calendar pour planifier directement vos projets et taches.</p>
      <a class="btn btn-secondary btn-sm" href="{{ $googleCalendarTargetUrl }}"><i class="fas fa-store"></i> Installer Google Calendar</a>
    </div>
  @endif
</div>
@endif

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-diagram-project"></i></div>
    <div class="stat-body"><div class="stat-value" id="projectsStatTotal">0</div><div class="stat-label">Total projets</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-play"></i></div>
    <div class="stat-body"><div class="stat-value" id="projectsStatActive">0</div><div class="stat-label">Actifs</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-warning-lt);color:var(--c-warning)"><i class="fas fa-list-check"></i></div>
    <div class="stat-body"><div class="stat-value" id="projectsStatPlanning">0</div><div class="stat-label">Planification</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-circle-check"></i></div>
    <div class="stat-body"><div class="stat-value" id="projectsStatCompleted">0</div><div class="stat-label">Termines</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:var(--c-danger-lt);color:var(--c-danger)"><i class="fas fa-clock"></i></div>
    <div class="stat-body"><div class="stat-value" id="projectsStatDelayed">0</div><div class="stat-label">En retard</div></div>
  </div>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Liste projets</span>
    <span class="table-count" id="projectsCount">0 projet</span>
    <div class="table-spacer"></div>

    <div class="table-search">
      <i class="fas fa-search"></i>
      <input type="text" id="projectsSearch" placeholder="Rechercher un projet, client, description..." autocomplete="off">
    </div>

    <select class="filter-select" id="projectsFilterStatus">
      <option value="">Tous statuts</option>
      @foreach($statuses as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    <select class="filter-select" id="projectsFilterPriority">
      <option value="">Toutes priorites</option>
      @foreach($priorities as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
      @endforeach
    </select>

    @if($clientsInstalled)
      <select class="filter-select" id="projectsFilterClient">
        <option value="">Tous clients</option>
        @foreach($clients as $client)
          <option value="{{ $client->id }}">{{ $client->company_name }}</option>
        @endforeach
      </select>
    @endif

    <button class="btn btn-ghost btn-sm" id="projectsResetFilters" title="Reinitialiser">
      <i class="fas fa-rotate-left"></i>
    </button>
  </div>

  <table class="crm-table">
    <thead>
      <tr>
        <th>Projet</th>
        <th>Client</th>
        <th>Responsable</th>
        <th>Statut</th>
        <th>Priorite</th>
        <th>Progression</th>
        <th>Echeance</th>
        <th style="text-align:right;padding-right:20px;">Actions</th>
      </tr>
    </thead>
    <tbody id="projectsTableBody"></tbody>
  </table>

  <div class="table-pagination">
    <span class="pagination-info" id="projectsPaginationInfo"></span>
    <div class="pagination-spacer"></div>
    <div class="pagination-pages" id="projectsPaginationControls"></div>
  </div>
</div>

<div class="modal-overlay" id="projectModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-diagram-project"></i></div>
      <div>
        <div class="modal-title" id="projectModalTitle">Nouveau projet</div>
        <div class="modal-subtitle">Creation et edition projet avec liaison client/membres.</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>

    <div class="modal-body">
      <form id="projectForm">
        <input type="hidden" id="projectId" name="project_id">

        <div class="row">
          <div class="col-8">
            <div class="form-group">
              <label class="form-label">Nom projet <span class="required">*</span></label>
              <input type="text" class="form-control" name="name" id="projectName" maxlength="180" required>
            </div>
          </div>
          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Client</label>
              @if($clientsInstalled)
                <select class="form-control" name="client_id" id="projectClientId">
                  <option value="">Aucun</option>
                  @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                  @endforeach
                </select>
              @else
                <div class="integration-inline-note">
                  Module Clients non installe.
                  <a href="{{ $clientsTargetUrl }}">Installer maintenant</a>
                </div>
              @endif
            </div>
          </div>

          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Description</label>
              <div class="wysiwyg">
                <div class="wysiwyg-toolbar" data-wysiwyg-toolbar="projectDescriptionEditor">
                  <button type="button" class="wys-btn" data-wcmd="bold" title="Gras"><i class="fas fa-bold"></i></button>
                  <button type="button" class="wys-btn" data-wcmd="italic" title="Italique"><i class="fas fa-italic"></i></button>
                  <button type="button" class="wys-btn" data-wcmd="underline" title="Souligne"><i class="fas fa-underline"></i></button>
                  <span class="wys-sep"></span>
                  <button type="button" class="wys-btn" data-wcmd="insertUnorderedList" title="Liste"><i class="fas fa-list-ul"></i></button>
                  <button type="button" class="wys-btn" data-wcmd="insertOrderedList" title="Liste numerotee"><i class="fas fa-list-ol"></i></button>
                  <button type="button" class="wys-btn" data-wcmd="formatBlock" data-wval="blockquote" title="Citation"><i class="fas fa-quote-left"></i></button>
                  <button type="button" class="wys-btn" data-wcmd="removeFormat" title="Nettoyer"><i class="fas fa-eraser"></i></button>
                </div>
                <div class="wysiwyg-editor" id="projectDescriptionEditor" contenteditable="true" data-placeholder="Description du projet..."></div>
                <textarea class="form-control" name="description" id="projectDescription" rows="4" maxlength="5000" style="display:none;"></textarea>
              </div>
            </div>
          </div>

          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Statut</label>
              <select class="form-control" name="status" id="projectStatus">
                @foreach($statuses as $key => $label)
                  <option value="{{ $key }}" @selected($key === 'planning')>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Priorite</label>
              <select class="form-control" name="priority" id="projectPriority">
                @foreach($priorities as $key => $label)
                  <option value="{{ $key }}" @selected($key === 'medium')>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Date debut <span class="required">*</span></label>
              <input type="date" class="form-control" name="start_date" id="projectStartDate" required>
            </div>
          </div>

          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Date fin</label>
              <input type="date" class="form-control" name="due_date" id="projectDueDate">
            </div>
          </div>

          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Budget</label>
              <input type="number" class="form-control" name="budget" id="projectBudget" min="0" step="0.01" placeholder="0.00">
            </div>
          </div>

          <div class="col-8">
            <div class="form-group">
              <label class="form-label">Membres du projet</label>
              <select class="form-control" id="projectMemberIds" name="member_ids[]" multiple size="4">
                @foreach($users as $user)
                  <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
              </select>
              <small style="color:var(--c-ink-40)">Ctrl/Cmd + clic pour selection multiple.</small>
            </div>
          </div>

          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Google Calendar</label>
              @if($googleCalendarInstalled)
                <label class="calendar-sync-check" for="projectSyncGoogleCalendar">
                  <input type="checkbox" id="projectSyncGoogleCalendar" name="sync_google_calendar" value="1">
                  <span>
                    Inclure ce projet dans Google Calendar
                    <small>Un evenement est cree (ou mis a jour) a l'enregistrement.</small>
                  </span>
                </label>
              @else
                <div class="integration-inline-note">
                  Google Calendar non installe.
                  <a href="{{ $googleCalendarTargetUrl }}">Installer maintenant</a>
                </div>
              @endif
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="projectSaveBtn"><i class="fas fa-check"></i> Enregistrer</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
window.PROJECTS_ROUTES = {
  data: @json(route('projects.data')),
  stats: @json(route('projects.stats')),
  store: @json(route('projects.store')),
  base: @json(route('projects.index')),
};

window.PROJECTS_BOOTSTRAP = {
  clientsInstalled: @json((bool) $clientsInstalled),
  clientsTargetUrl: @json($clientsTargetUrl),
  googleCalendarInstalled: @json((bool) $googleCalendarInstalled),
  googleCalendarTargetUrl: @json($googleCalendarTargetUrl),
};
</script>
@endpush
