@extends('projects::layouts.projects')

@php
  $tenantCurrency = strtoupper((string) (auth()->user()->tenant->currency ?: config('invoice.default_currency', 'EUR')));
  $currencySymbol = config("invoice.currencies.{$tenantCurrency}.symbol", $tenantCurrency);
@endphp

@section('title', 'Projet: ' . $project->name)

@section('projects_breadcrumb')
  <a href="{{ route('projects.index') }}">Projets</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ $project->name }}</span>
@endsection

@section('projects_content')
<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $project->name }}</h1>
    <p>{{ strip_tags($project->description ?: '') ?: 'Aucune description.' }}</p>
  </div>
  <div class="page-header-actions">
    @if($googleCalendarInstalled)
      <button class="btn btn-secondary" id="projectScheduleBtn" data-loading-text="Planification..."><i class="fas fa-calendar-plus"></i> Planifier projet</button>
    @else
      <a class="btn btn-secondary" href="{{ $googleCalendarTargetUrl }}"><i class="fas fa-store"></i> Installer Google Calendar</a>
    @endif
    <button class="btn btn-secondary" data-modal-open="projectMembersModal"><i class="fas fa-users"></i> Membres</button>
    <button class="btn btn-primary" id="taskCreateBtn"><i class="fas fa-plus"></i> Nouvelle tache</button>
  </div>
</div>

@if(!$clientsInstalled || !$googleCalendarInstalled)
<div class="integration-hints">
  @if(!$clientsInstalled)
    <div class="integration-hint-item">
      <div class="integration-hint-title"><i class="fas fa-building"></i> Module Clients non installe</div>
      <p>Installez le module Clients pour associer facilement vos projets et taches a des clients.</p>
      <a class="btn btn-secondary btn-sm" href="{{ $clientsTargetUrl }}"><i class="fas fa-store"></i> Installer Clients</a>
    </div>
  @endif
  @if(!$googleCalendarInstalled)
    <div class="integration-hint-item">
      <div class="integration-hint-title"><i class="fas fa-calendar-days"></i> Google Calendar non installe</div>
      <p>Activez Google Calendar pour planifier ce projet et ses taches en un clic.</p>
      <a class="btn btn-secondary btn-sm" href="{{ $googleCalendarTargetUrl }}"><i class="fas fa-store"></i> Installer Google Calendar</a>
    </div>
  @endif
</div>
@endif

<div class="projects-show-grid">
  <div class="info-card">
    <div class="info-card-header"><i class="fas fa-building"></i><h3>Client</h3></div>
    <div class="info-card-body">
      <div class="info-row"><span class="info-row-label">Entreprise</span><span class="info-row-value">{{ $project->client?->company_name ?: 'Aucun client' }}</span></div>
      <div class="info-row"><span class="info-row-label">Contact</span><span class="info-row-value">{{ $project->client?->contact_name ?: '-' }}</span></div>
      <div class="info-row"><span class="info-row-label">Email</span><span class="info-row-value">{{ $project->client?->email ?: '-' }}</span></div>
    </div>
  </div>

  <div class="info-card">
    <div class="info-card-header"><i class="fas fa-user-tie"></i><h3>Pilotage</h3></div>
    <div class="info-card-body">
      <div class="info-row"><span class="info-row-label">Owner</span><span class="info-row-value">{{ $project->owner?->name ?: '-' }}</span></div>
      <div class="info-row"><span class="info-row-label">Statut</span><span class="info-row-value">{{ config('projects.project_statuses.' . $project->status, $project->status) }}</span></div>
      <div class="info-row"><span class="info-row-label">Priorite</span><span class="info-row-value">{{ config('projects.priorities.' . $project->priority, $project->priority) }}</span></div>
      <div class="info-row"><span class="info-row-label">Progression</span><span class="info-row-value"><strong id="projectProgressValue">{{ (int) $project->progress }}%</strong></span></div>
    </div>
  </div>

  <div class="info-card">
    <div class="info-card-header"><i class="fas fa-calendar"></i><h3>Planning</h3></div>
    <div class="info-card-body">
      <div class="info-row"><span class="info-row-label">Debut</span><span class="info-row-value">{{ optional($project->start_date)->format('d/m/Y') ?: '-' }}</span></div>
      <div class="info-row"><span class="info-row-label">Echeance</span><span class="info-row-value">{{ optional($project->due_date)->format('d/m/Y') ?: '-' }}</span></div>
      <div class="info-row"><span class="info-row-label">Budget</span><span class="info-row-value">{{ $project->budget ? number_format((float)$project->budget, 2, ',', ' ') . ' ' . $currencySymbol : '-' }}</span></div>
      <div class="info-row"><span class="info-row-label">Membres</span><span class="info-row-value" id="projectMembersCount">{{ $project->members->count() }}</span></div>
    </div>
  </div>
</div>

<div class="project-board-toolbar">
  <div class="table-search" style="min-width:320px;">
    <i class="fas fa-search"></i>
    <input type="text" id="projectTasksSearch" placeholder="Rechercher tache, description, assigne..." autocomplete="off">
  </div>

  <select class="filter-select" id="projectTasksAssigneeFilter">
    <option value="">Tous assignees</option>
    @foreach($users as $user)
      <option value="{{ $user->id }}">{{ $user->name }}</option>
    @endforeach
  </select>

  <button class="btn btn-ghost" id="projectTasksReloadBtn"><i class="fas fa-rotate"></i> Rafraichir</button>

  <div class="board-switch">
    <i class="fas fa-table-columns"></i>
    <select class="filter-select" id="projectBoardSelect">
      <option value="default">Board principal</option>
    </select>
  </div>

  <div class="view-switch">
    <button class="btn btn-secondary btn-sm active" type="button" id="projectViewKanbanBtn"><i class="fas fa-table-columns"></i> Kanban</button>
    <button class="btn btn-secondary btn-sm" type="button" id="projectViewListBtn"><i class="fas fa-list"></i> Liste</button>
  </div>
</div>

<div class="project-files-panel">
  <div class="project-files-head">
    <div>
      <div class="project-files-title"><i class="fas fa-paperclip"></i> Fichiers du projet</div>
      <div class="project-files-sub">Stockage via Google Drive (application requise).</div>
    </div>
    <div class="project-files-actions">
      <div class="file-picker">
        <input type="file" id="projectFileInput">
        <button class="btn btn-secondary" type="button" id="projectFilePickBtn"><i class="fas fa-paperclip"></i> Choisir</button>
        <span class="file-picker-name" id="projectFileName">Aucun fichier</span>
      </div>
      <button class="btn btn-primary" id="projectFileUploadBtn" data-loading-text="Ajout..."><i class="fas fa-upload"></i> Ajouter</button>
      <a class="btn btn-secondary" href="{{ route('google-drive.index') }}"><i class="fas fa-link"></i> Google Drive</a>
    </div>
  </div>

  <div class="project-files-body" id="projectFilesList">
    <div class="skeleton" style="height:44px;border-radius:10px;"></div>
  </div>
</div>

<div class="project-board-wrap">
  <div class="project-board" id="projectBoard">
    @foreach($taskStatuses as $status => $label)
      <section class="project-column" data-column="{{ $status }}">
        <header class="project-column-head">
          <h3>{{ $label }}</h3>
          <span class="project-column-count" data-count="{{ $status }}">0</span>
        </header>
        <div class="project-column-body" data-dropzone="{{ $status }}"></div>
      </section>
    @endforeach
  </div>
</div>

<div class="project-tasks-list" id="projectTasksList" style="display:none;"></div>

<div class="task-drawer-overlay" id="taskDrawer">
  <div class="task-drawer">
    <div class="task-drawer-header">
      <div>
        <div class="task-drawer-title" id="taskDrawerTitle">Tache</div>
        <div class="task-drawer-sub" id="taskDrawerSub">Details, checklist, commentaires et fichiers.</div>
      </div>
      <button class="btn-icon" type="button" data-task-drawer-close title="Fermer"><i class="fas fa-xmark"></i></button>
    </div>

    <div class="task-drawer-tabs">
      <button type="button" class="tab active" data-task-tab="details"><i class="fas fa-pen"></i> Details</button>
      <button type="button" class="tab" data-task-tab="checklist"><i class="fas fa-list-check"></i> Checklist</button>
      <button type="button" class="tab" data-task-tab="comments"><i class="fas fa-comment"></i> Commentaires</button>
      <button type="button" class="tab" data-task-tab="files"><i class="fas fa-paperclip"></i> Fichiers</button>
    </div>

    <div class="task-drawer-body">
      <form id="taskDrawerForm">
        <input type="hidden" id="taskId" name="task_id">

        <section class="task-tab-panel" data-task-panel="details">
          <div class="form-group">
            <label class="form-label">Titre <span class="required">*</span></label>
            <input type="text" class="form-control" id="taskTitle" name="title" maxlength="220" required>
          </div>

          <div class="row">
            <div class="col-6">
              <div class="form-group">
                <label class="form-label">Assigne a</label>
                <div class="select-search-box">
                  <i class="fas fa-user"></i>
                  <input type="text" class="select-search-input" id="taskAssigneeSearch" placeholder="Rechercher utilisateur...">
                </div>
                <select class="form-control" id="taskAssignedTo" name="assigned_to">
                  <option value="">Non assigne</option>
                  @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-6">
              <div class="form-group">
                <label class="form-label">Client</label>
                @if($clientsInstalled)
                  <div class="select-search-box">
                    <i class="fas fa-building"></i>
                    <input type="text" class="select-search-input" id="taskClientSearch" placeholder="Rechercher client...">
                  </div>
                  <select class="form-control" id="taskClientId" name="client_id">
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
          </div>

          <div class="row">
            <div class="col-6">
              <div class="form-group">
                <label class="form-label">Statut</label>
                <select class="form-control" id="taskStatus" name="status">
                  @foreach($taskStatuses as $key => $label)
                    <option value="{{ $key }}" @selected($key === 'todo')>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-6">
              <div class="form-group">
                <label class="form-label">Priorite</label>
                <select class="form-control" id="taskPriority" name="priority">
                  @foreach($priorities as $key => $label)
                    <option value="{{ $key }}" @selected($key === 'medium')>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-6">
              <div class="form-group">
                <label class="form-label">Debut <span class="required">*</span></label>
                <input type="date" class="form-control" id="taskStartDate" name="start_date" required>
              </div>
            </div>
            <div class="col-6">
              <div class="form-group">
                <label class="form-label">Echeance</label>
                <input type="date" class="form-control" id="taskDueDate" name="due_date">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-6">
              <div class="form-group">
                <label class="form-label">Estimation (h)</label>
                <input type="number" class="form-control" id="taskEstimate" name="estimate_hours" min="0" step="0.25">
              </div>
            </div>
            <div class="col-6">
              <div class="form-group">
                <label class="form-label">Passe (h)</label>
                <input type="number" class="form-control" id="taskSpent" name="spent_hours" min="0" step="0.25">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Google Calendar</label>
            @if($googleCalendarInstalled)
              <label class="calendar-sync-check" for="taskSyncGoogleCalendar">
                <input type="checkbox" id="taskSyncGoogleCalendar" name="sync_google_calendar" value="1">
                <span>
                  Inclure cette tache dans Google Calendar
                  <small>Lors de l'enregistrement, la tache est planifiee automatiquement.</small>
                </span>
              </label>
            @else
              <div class="integration-inline-note">
                Google Calendar non installe.
                <a href="{{ $googleCalendarTargetUrl }}">Installer maintenant</a>
              </div>
            @endif
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <div class="wysiwyg">
              <div class="wysiwyg-toolbar" data-wysiwyg-toolbar="taskDescriptionEditor">
                <button type="button" class="wys-btn" data-wcmd="bold" title="Gras"><i class="fas fa-bold"></i></button>
                <button type="button" class="wys-btn" data-wcmd="italic" title="Italique"><i class="fas fa-italic"></i></button>
                <button type="button" class="wys-btn" data-wcmd="underline" title="Souligne"><i class="fas fa-underline"></i></button>
                <span class="wys-sep"></span>
                <button type="button" class="wys-btn" data-wcmd="justifyLeft" title="Gauche"><i class="fas fa-align-left"></i></button>
                <button type="button" class="wys-btn" data-wcmd="justifyCenter" title="Centre"><i class="fas fa-align-center"></i></button>
                <button type="button" class="wys-btn" data-wcmd="justifyRight" title="Droite"><i class="fas fa-align-right"></i></button>
                <span class="wys-sep"></span>
                <input type="color" class="wys-color" data-wcmd="foreColor" title="Couleur texte" value="#111827">
                <input type="color" class="wys-color" data-wcmd="hiliteColor" title="Surlignage" value="#fde68a">
                <button type="button" class="wys-btn" data-wcmd="insertUnorderedList" title="Liste"><i class="fas fa-list-ul"></i></button>
                <button type="button" class="wys-btn" data-wcmd="removeFormat" title="Nettoyer"><i class="fas fa-eraser"></i></button>
              </div>
              <div class="wysiwyg-editor" id="taskDescriptionEditor" contenteditable="true" data-placeholder="Description de la tache..."></div>
              <textarea class="form-control" id="taskDescription" name="description" rows="4" maxlength="7000" style="display:none;"></textarea>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Tags</label>
            <div class="tag-input" id="taskTagsInput" data-target="taskTagsHidden">
              <div class="tag-badges" data-tag-badges></div>
              <input type="text" class="tag-field" id="taskTags" placeholder="ux, backend, urgent">
            </div>
            <input type="hidden" id="taskTagsHidden" name="tags">
          </div>
        </section>

        <section class="task-tab-panel" data-task-panel="checklist" style="display:none;">
          <div class="task-inline-form">
            <input type="text" class="form-control" id="taskChecklistTitle" placeholder="Nouvel item checklist">
            <button class="btn btn-secondary" id="taskChecklistAddBtn" type="button" data-loading-text="Ajout..."><i class="fas fa-plus"></i> Ajouter</button>
          </div>
          <div id="taskChecklistList"></div>
        </section>

        <section class="task-tab-panel" data-task-panel="comments" style="display:none;">
          <div class="task-inline-form">
            <input type="text" class="form-control" id="taskCommentBody" placeholder="Ajouter un commentaire...">
            <button class="btn btn-secondary" id="taskCommentAddBtn" type="button" data-loading-text="Envoi..."><i class="fas fa-paper-plane"></i> Envoyer</button>
          </div>
          <div id="taskCommentsList"></div>
        </section>

        <section class="task-tab-panel" data-task-panel="files" style="display:none;">
          <div class="file-picker" style="margin-bottom:10px;">
            <input type="file" id="taskFileInput">
            <button class="btn btn-secondary" type="button" id="taskFilePickBtn"><i class="fas fa-paperclip"></i> Choisir</button>
            <span class="file-picker-name" id="taskFileName">Aucun fichier</span>
          </div>
          <button class="btn btn-primary" type="button" id="taskFileUploadBtn" data-loading-text="Ajout..."><i class="fas fa-upload"></i> Ajouter fichier</button>
          <div style="height:10px;"></div>
          <div id="taskFilesList"></div>
        </section>
      </form>
    </div>

    <div class="task-drawer-footer">
      <button class="btn btn-secondary" type="button" id="taskScheduleBtn" data-loading-text="Planification..."><i class="fas fa-calendar-plus"></i> Planifier tache</button>
      <button class="btn btn-secondary" type="button" data-task-drawer-close>Fermer</button>
      <button class="btn btn-primary" type="button" id="taskSaveBtn" data-loading-text="Enregistrement..."><i class="fas fa-check"></i> Enregistrer</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="taskModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-accent-lt);color:var(--c-accent)"><i class="fas fa-list-check"></i></div>
      <div>
        <div class="modal-title" id="taskModalTitle">Nouvelle tache</div>
        <div class="modal-subtitle">Kanban, assignation, priorite et suivi checklists/commentaires.</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>

    <div class="modal-body">
      <form id="taskForm">
        <input type="hidden" id="taskId" name="task_id">

        <div class="row">
          <div class="col-8">
            <div class="form-group">
              <label class="form-label">Titre <span class="required">*</span></label>
              <input type="text" class="form-control" id="taskTitle" name="title" maxlength="220" required>
            </div>
          </div>

          <div class="col-4">
            <div class="form-group">
              <label class="form-label">Assigne a</label>
              <div class="select-search-box">
                <i class="fas fa-user"></i>
                <input type="text" class="select-search-input" id="taskAssigneeSearch" placeholder="Rechercher utilisateur...">
              </div>
              <select class="form-control" id="taskAssignedTo" name="assigned_to">
                <option value="">Non assigne</option>
                @foreach($users as $user)
                  <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-12">
            <div class="form-group">
              <label class="form-label">Description</label>
              <div class="wysiwyg">
                <div class="wysiwyg-toolbar" data-wysiwyg-toolbar="taskDescriptionEditor">
                  <button type="button" class="wys-btn" data-wcmd="bold" title="Gras"><i class="fas fa-bold"></i></button>
                  <button type="button" class="wys-btn" data-wcmd="italic" title="Italique"><i class="fas fa-italic"></i></button>
                  <button type="button" class="wys-btn" data-wcmd="underline" title="Souligne"><i class="fas fa-underline"></i></button>
                  <span class="wys-sep"></span>
                  <button type="button" class="wys-btn" data-wcmd="insertUnorderedList" title="Liste"><i class="fas fa-list-ul"></i></button>
                  <button type="button" class="wys-btn" data-wcmd="insertOrderedList" title="Liste numerotee"><i class="fas fa-list-ol"></i></button>
                  <button type="button" class="wys-btn" data-wcmd="formatBlock" data-wval="blockquote" title="Citation"><i class="fas fa-quote-left"></i></button>
                  <button type="button" class="wys-btn" data-wcmd="removeFormat" title="Nettoyer"><i class="fas fa-eraser"></i></button>
                </div>
                <div class="wysiwyg-editor" id="taskDescriptionEditor" contenteditable="true" data-placeholder="Description de la tache..."></div>
                <textarea class="form-control" id="taskDescription" name="description" rows="4" maxlength="7000" style="display:none;"></textarea>
              </div>
            </div>
          </div>

          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Statut</label>
              <select class="form-control" id="taskStatus" name="status">
                @foreach($taskStatuses as $key => $label)
                  <option value="{{ $key }}" @selected($key === 'todo')>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Priorite</label>
              <select class="form-control" id="taskPriority" name="priority">
                @foreach($priorities as $key => $label)
                  <option value="{{ $key }}" @selected($key === 'medium')>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Debut <span class="required">*</span></label>
              <input type="date" class="form-control" id="taskStartDate" name="start_date" required>
            </div>
          </div>

          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Echeance</label>
              <input type="date" class="form-control" id="taskDueDate" name="due_date">
            </div>
          </div>

          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Estimation (h)</label>
              <input type="number" class="form-control" id="taskEstimate" name="estimate_hours" min="0" step="0.25">
            </div>
          </div>

          <div class="col-3">
            <div class="form-group">
              <label class="form-label">Passe (h)</label>
              <input type="number" class="form-control" id="taskSpent" name="spent_hours" min="0" step="0.25">
            </div>
          </div>

          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Client</label>
              @if($clientsInstalled)
                <div class="select-search-box">
                  <i class="fas fa-building"></i>
                  <input type="text" class="select-search-input" id="taskClientSearch" placeholder="Rechercher client...">
                </div>
                <select class="form-control" id="taskClientId" name="client_id">
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
              <label class="form-label">Tags (separes par virgule)</label>
              <div class="tag-input" id="taskTagsInput" data-target="taskTagsHidden">
                <div class="tag-badges" data-tag-badges></div>
                <input type="text" class="tag-field" id="taskTags" placeholder="ux, backend, urgent">
              </div>
              <input type="hidden" id="taskTagsHidden" name="tags">
            </div>
          </div>
        </div>
      </form>

      <div class="task-extra" id="taskExtraPanel" style="display:none;">
        <div class="task-extra-col">
          <h4>Checklist</h4>
          <div class="task-inline-form">
            <input type="text" class="form-control" id="taskChecklistTitle" placeholder="Nouvel item checklist">
            <button class="btn btn-secondary" id="taskChecklistAddBtn" type="button">Ajouter</button>
          </div>
          <div id="taskChecklistList"></div>
        </div>

        <div class="task-extra-col">
          <h4>Commentaires</h4>
          <div class="task-inline-form">
            <input type="text" class="form-control" id="taskCommentBody" placeholder="Ajouter un commentaire...">
            <button class="btn btn-secondary" id="taskCommentAddBtn" type="button">Envoyer</button>
          </div>
          <div id="taskCommentsList"></div>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Fermer</button>
      <button class="btn btn-primary" id="taskSaveBtn"><i class="fas fa-check"></i> Enregistrer tache</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="projectMembersModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-header-icon" style="background:var(--c-success-lt);color:var(--c-success)"><i class="fas fa-users"></i></div>
      <div>
        <div class="modal-title">Membres du projet</div>
        <div class="modal-subtitle">Gestion des roles projet: owner/manager/member/viewer.</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>

    <div class="modal-body">
      <div class="members-grid" id="projectMembersRows">
        @foreach($project->members as $member)
          <div class="member-row" data-member-row>
            <select class="form-control" data-member-user>
              @foreach($users as $user)
                <option value="{{ $user->id }}" @selected((int)$member->user_id === (int)$user->id)>{{ $user->name }}</option>
              @endforeach
            </select>
            <select class="form-control" data-member-role>
              @foreach(['owner' => 'Owner', 'manager' => 'Manager', 'member' => 'Member', 'viewer' => 'Viewer'] as $roleKey => $roleLabel)
                <option value="{{ $roleKey }}" @selected($member->role === $roleKey)>{{ $roleLabel }}</option>
              @endforeach
            </select>
            <button class="btn btn-danger" type="button" data-member-remove>
              <i class="fas fa-trash"></i>
            </button>
          </div>
        @endforeach
      </div>

      <button class="btn btn-ghost" type="button" id="projectMembersAddRowBtn"><i class="fas fa-plus"></i> Ajouter membre</button>
    </div>

    <div class="modal-footer">
      <button class="btn btn-secondary" data-modal-close>Annuler</button>
      <button class="btn btn-primary" id="projectMembersSaveBtn"><i class="fas fa-check"></i> Sauvegarder membres</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
window.PROJECTS_SHOW_BOOTSTRAP = {
  projectId: {{ (int) $project->id }},
  taskStatuses: @json($taskStatuses),
  clientsInstalled: @json((bool) $clientsInstalled),
  clientsTargetUrl: @json($clientsTargetUrl),
  googleCalendarInstalled: @json((bool) $googleCalendarInstalled),
  googleCalendarTargetUrl: @json($googleCalendarTargetUrl),
};

window.PROJECTS_ROUTES = {
  base: @json(route('projects.index')),
  scheduleProject: @json(route('projects.calendar.schedule-project', $project)),
  scheduleTaskBase: @json(route('projects.tasks.store', $project)),
  boardsData: @json(route('projects.boards.data', $project)),
};

window.PROJECTS_USERS = @json($users->values()->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]));
</script>
@endpush

