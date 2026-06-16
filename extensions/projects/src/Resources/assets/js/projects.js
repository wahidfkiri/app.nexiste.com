'use strict';

const ProjectsModule = (() => {
  const state = {
    mode: document.getElementById('projectBoard') ? 'show' : 'index',
    loading: false,
    page: 1,
    perPage: 15,
    search: '',
    status: '',
    priority: '',
    clientId: '',
    projects: [],
    editingProjectId: null,
    projectId: window.PROJECTS_SHOW_BOOTSTRAP?.projectId || null,
    tasks: [],
    groupedTasks: {},
    taskStatuses: { ...(window.PROJECTS_SHOW_BOOTSTRAP?.taskStatuses || {}) },
    taskSearch: '',
    taskAssignedTo: '',
    editingTask: null,
    projectDraftManager: null,
    taskDraftManager: null,
    tagInput: null,
    activeTaskTab: 'details',
    viewMode: 'kanban',
    boards: [],
    activeBoardId: 'default',
    clientsInstalled: !!(window.PROJECTS_SHOW_BOOTSTRAP?.clientsInstalled ?? window.PROJECTS_BOOTSTRAP?.clientsInstalled ?? true),
    clientsTargetUrl: window.PROJECTS_SHOW_BOOTSTRAP?.clientsTargetUrl || window.PROJECTS_BOOTSTRAP?.clientsTargetUrl || '',
    googleCalendarInstalled: !!(window.PROJECTS_SHOW_BOOTSTRAP?.googleCalendarInstalled ?? window.PROJECTS_BOOTSTRAP?.googleCalendarInstalled ?? false),
    googleCalendarTargetUrl: window.PROJECTS_SHOW_BOOTSTRAP?.googleCalendarTargetUrl || window.PROJECTS_BOOTSTRAP?.googleCalendarTargetUrl || '',
  };

  function boot() {
    if (state.mode === 'index') {
      initIndex();
    } else {
      initShow();
    }
  }

  function initIndex() {
    bindIndexFilters();
    bindProjectModal();
    bindWysiwyg();
    initProjectDrafts();
    loadProjects();
    loadStats();
  }

  function bindIndexFilters() {
    const search = document.getElementById('projectsSearch');
    const status = document.getElementById('projectsFilterStatus');
    const priority = document.getElementById('projectsFilterPriority');
    const client = document.getElementById('projectsFilterClient');

    let timer = null;
    search?.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        state.search = search.value.trim();
        state.page = 1;
        loadProjects();
      }, 300);
    });

    status?.addEventListener('change', () => {
      state.status = status.value;
      state.page = 1;
      loadProjects();
    });

    priority?.addEventListener('change', () => {
      state.priority = priority.value;
      state.page = 1;
      loadProjects();
    });

    client?.addEventListener('change', () => {
      state.clientId = client.value;
      state.page = 1;
      loadProjects();
    });

    document.getElementById('projectsResetFilters')?.addEventListener('click', () => {
      state.search = '';
      state.status = '';
      state.priority = '';
      state.clientId = '';
      state.page = 1;

      if (search) search.value = '';
      if (status) status.value = '';
      if (priority) priority.value = '';
      if (client) client.value = '';

      loadProjects();
    });
  }

  function bindProjectModal() {
    const createBtn = document.getElementById('projectCreateBtn');
    const saveBtn = document.getElementById('projectSaveBtn');

    createBtn?.addEventListener('click', () => {
      state.editingProjectId = null;
      resetProjectForm();
      setText('projectModalTitle', 'Nouveau projet');
      window.setTimeout(async () => {
        await state.projectDraftManager?.load({ prompt: false });
        state.projectDraftManager?.promptResumeIfAvailable();
      }, 0);
    });

    saveBtn?.addEventListener('click', saveProject);

    document.getElementById('projectsTableBody')?.addEventListener('click', (e) => {
      const openBtn = e.target.closest('[data-project-open]');
      if (openBtn) {
        const id = openBtn.dataset.projectOpen;
        if (id) window.location.href = `${window.PROJECTS_ROUTES.base}/${id}`;
        return;
      }

      const editBtn = e.target.closest('[data-project-edit]');
      if (editBtn) {
        const id = parseInt(editBtn.dataset.projectEdit, 10);
        if (!Number.isNaN(id)) editProject(id);
        return;
      }

      const delBtn = e.target.closest('[data-project-delete]');
      if (delBtn) {
        const id = parseInt(delBtn.dataset.projectDelete, 10);
        if (!Number.isNaN(id)) deleteProject(id);
      }
    });
  }

  function bindWysiwyg() {
    document.querySelectorAll('[data-wysiwyg-toolbar]').forEach((toolbar) => {
      const exec = (el) => {
        const targetId = toolbar.getAttribute('data-wysiwyg-toolbar');
        const editor = targetId ? document.getElementById(targetId) : null;
        if (!editor) return;

        const cmd = el.getAttribute('data-wcmd');
        const val = el.getAttribute('data-wval') || (el.tagName === 'INPUT' ? el.value : null);

        editor.focus();
        if (cmd === 'formatBlock' && val) document.execCommand('formatBlock', false, `<${val}>`);
        else if (cmd) document.execCommand(cmd, false, val || null);
      };

      toolbar.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-wcmd]');
        if (!btn || btn.tagName === 'INPUT') return;
        exec(btn);
      });

      toolbar.querySelectorAll('input[data-wcmd]').forEach((input) => {
        input.addEventListener('change', () => exec(input));
      });
    });
  }

  async function loadProjects() {
    if (state.loading) return;
    state.loading = true;

    const tbody = document.getElementById('projectsTableBody');
    if (tbody) tbody.innerHTML = skeletonRows(6, 8);

    const { ok, data } = await Http.get(window.PROJECTS_ROUTES.data, {
      page: state.page,
      per_page: state.perPage,
      search: state.search,
      status: state.status,
      priority: state.priority,
      client_id: state.clientId,
      sort_by: 'created_at',
      sort_dir: 'desc',
    });

    state.loading = false;

    if (!ok) {
      Toast.error('Erreur', data.message || 'Impossible de charger les projets.');
      if (tbody) tbody.innerHTML = emptyRow(8, 'Erreur de chargement.');
      return;
    }

    state.projects = data.data || [];
    renderProjectsRows();
    renderProjectsPagination(data);
    setText('projectsCount', `${data.total || 0} projet(s)`);
  }

  async function loadStats() {
    const { ok, data } = await Http.get(window.PROJECTS_ROUTES.stats);
    if (!ok || !data.success) return;

    setText('projectsStatTotal', data.data.total || 0);
    setText('projectsStatActive', data.data.active || 0);
    setText('projectsStatPlanning', data.data.planning || 0);
    setText('projectsStatCompleted', data.data.completed || 0);
    setText('projectsStatDelayed', data.data.delayed || 0);
  }

  function renderProjectsRows() {
    const tbody = document.getElementById('projectsTableBody');
    if (!tbody) return;

    if (!state.projects.length) {
      tbody.innerHTML = emptyRow(8, 'Aucun projet trouve avec ces filtres.');
      return;
    }

    tbody.innerHTML = state.projects.map((project) => {
      const progress = Number(project.progress || 0);
      return `
      <tr>
        <td>
          <div style="font-weight:700;color:var(--c-ink);">${esc(project.name)}</div>
          <div style="font-size:12px;color:var(--c-ink-40);">${esc(stripHtml(project.description || ''))}</div>
        </td>
        <td>${esc(project.client_name || '-')}</td>
        <td>${esc(project.owner_name || '-')}</td>
        <td>${projectStatusBadge(project.status)}</td>
        <td>${priorityBadge(project.priority)}</td>
        <td>
          <div class="project-progress"><span style="width:${Math.max(0, Math.min(100, progress))}%;"></span></div>
          <div style="font-size:11px;color:var(--c-ink-40);margin-top:3px;">${progress}%</div>
        </td>
        <td>${project.due_date ? formatDate(project.due_date) : '-'}</td>
        <td>
          <div class="row-actions" style="justify-content:flex-end;padding-right:4px;opacity:1;">
            <button class="btn-icon" data-project-open="${project.id}" title="Ouvrir"><i class="fas fa-eye"></i></button>
            <button class="btn-icon" data-project-edit="${project.id}" title="Modifier"><i class="fas fa-pen"></i></button>
            <button class="btn-icon danger" data-project-delete="${project.id}" title="Supprimer"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>`;
    }).join('');
  }

  function renderProjectsPagination(payload) {
    const wrap = document.getElementById('projectsPaginationControls');
    const info = document.getElementById('projectsPaginationInfo');
    if (!wrap) return;

    const current = payload.current_page || 1;
    const last = payload.last_page || 1;
    if (info) info.textContent = `Affichage ${payload.from || 0} - ${payload.to || 0} sur ${payload.total || 0}`;

    const pages = [];
    for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i += 1) pages.push(i);

    wrap.innerHTML = `
      <button class="page-btn" ${current <= 1 ? 'disabled' : ''} data-page="${current - 1}"><i class="fas fa-chevron-left"></i></button>
      ${pages.map((p) => `<button class="page-btn ${p === current ? 'active' : ''}" data-page="${p}">${p}</button>`).join('')}
      <button class="page-btn" ${current >= last ? 'disabled' : ''} data-page="${current + 1}"><i class="fas fa-chevron-right"></i></button>
    `;

    wrap.querySelectorAll('[data-page]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = parseInt(btn.dataset.page, 10);
        if (!Number.isNaN(target) && target > 0 && target !== state.page) {
          state.page = target;
          loadProjects();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
    });
  }

  function editProject(id) {
    const project = state.projects.find((p) => Number(p.id) === Number(id));
    if (!project) return;

    state.projectDraftManager?.resetLocal();
    state.editingProjectId = project.id;
    setText('projectModalTitle', 'Modifier projet');
    setField('projectId', project.id);
    setField('projectName', project.name || '');
    setField('projectClientId', project.client_id || '');
    setField('projectDescription', project.description || '');
    setWysiwygHtml('projectDescriptionEditor', project.description || '');
    setField('projectStatus', project.status || 'planning');
    setField('projectPriority', project.priority || 'medium');
    setField('projectStartDate', project.start_date || '');
    setField('projectDueDate', project.due_date || '');
    setField('projectBudget', project.budget || '');
    setCheckbox('projectSyncGoogleCalendar', !!(state.googleCalendarInstalled && project.google_calendar?.event_id));
    Modal.open(document.getElementById('projectModal'));
  }

  async function saveProject() {
    const form = document.getElementById('projectForm');
    const btn = document.getElementById('projectSaveBtn');
    if (!form || !btn) return;

    CrmForm.clearErrors(form);

    const payload = {
      name: (getField('projectName') || '').trim(),
      client_id: normalizeNullable(getField('projectClientId')),
      description: getWysiwygHtml('projectDescriptionEditor'),
      status: getField('projectStatus') || 'planning',
      priority: getField('projectPriority') || 'medium',
      start_date: normalizeNullable(getField('projectStartDate')),
      due_date: normalizeNullable(getField('projectDueDate')),
      budget: normalizeNullable(getField('projectBudget')),
      member_ids: getMultiValues('projectMemberIds').map((v) => Number(v)).filter((v) => !Number.isNaN(v)),
      sync_google_calendar: getCheckbox('projectSyncGoogleCalendar'),
      draft_id: normalizeNullable(form.querySelector('[name="draft_id"]')?.value || ''),
    };
    setField('projectDescription', payload.description || '');

    const errors = validateProjectPayload(payload);
    if (Object.keys(errors).length > 0) {
      CrmForm.showErrors(form, errors);
      Toast.error('Validation', 'Veuillez corriger les erreurs du formulaire.');
      return;
    }

    CrmForm.setLoading(btn, true);
    let response;
    if (state.editingProjectId) response = await Http.put(`${window.PROJECTS_ROUTES.base}/${state.editingProjectId}`, payload);
    else response = await Http.post(window.PROJECTS_ROUTES.store, payload);
    CrmForm.setLoading(btn, false);

    if (!response.ok) {
      if (response.status === 422 && response.data?.errors) CrmForm.showErrors(form, response.data.errors);
      Toast.error('Erreur', response.data?.message || 'Operation impossible.');
      return;
    }

    const automationFlow = !state.editingProjectId
      && window.AutomationSuggestions
      && response.data?.automation?.should_prompt
      ? window.AutomationSuggestions.open(response.data.automation)
      : null;

    Toast.success('Succes', response.data?.message || 'Projet enregistre.');
    state.projectDraftManager?.complete();
    Modal.close(document.getElementById('projectModal'));
    resetProjectForm();
    state.editingProjectId = null;
    loadProjects();
    loadStats();
    handleCalendarSyncFeedback(response.data, 'Le projet');

    if (automationFlow && typeof automationFlow.finally === 'function') {
      automationFlow.finally(() => {
        loadProjects();
        loadStats();
      });
    }
  }

  function validateProjectPayload(payload) {
    const errors = {};
    if (!payload.name) errors.name = ['Le nom projet est obligatoire.'];
    if (!payload.start_date) errors.start_date = ['La date debut est obligatoire.'];
    if (payload.start_date && payload.due_date) {
      const start = new Date(payload.start_date);
      const due = new Date(payload.due_date);
      if (!Number.isNaN(start.getTime()) && !Number.isNaN(due.getTime()) && due < start) {
        errors.due_date = ['La date fin doit etre >= date debut.'];
      }
    }
    return errors;
  }

  async function deleteProject(id) {
    const project = state.projects.find((p) => Number(p.id) === Number(id));
    if (!project) return;

    Modal.confirm({
      title: 'Supprimer ce projet ?',
      message: `Le projet "${project.name}" et ses taches seront supprimes.`,
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const response = await Http.delete(`${window.PROJECTS_ROUTES.base}/${id}`);
        if (!response.ok) {
          Toast.error('Erreur', response.data?.message || 'Suppression impossible.');
          return;
        }
        Toast.success('Supprime', response.data?.message || 'Projet supprime.');
        loadProjects();
        loadStats();
      },
    });
  }

  function resetProjectForm() {
    const form = document.getElementById('projectForm');
    if (!form) return;
    form.reset();
    setField('projectId', '');
    setField('projectStatus', 'planning');
    setField('projectPriority', 'medium');
    setWysiwygHtml('projectDescriptionEditor', '');
    setField('projectDescription', '');
    setCheckbox('projectSyncGoogleCalendar', false);
  }

  function initProjectDrafts() {
    state.projectDraftManager = window.CrmDrafts?.attach('projectForm', {
      type: 'project',
      label: 'projet',
      promptOnLoad: false,
      collect: (data) => {
        data.description = getWysiwygHtml('projectDescriptionEditor');
        return data;
      },
      apply: (data) => {
        setWysiwygHtml('projectDescriptionEditor', data.description || '');
        setField('projectDescription', data.description || '');
      },
      shouldSave: () => !state.editingProjectId,
      onStateChange: () => updateProjectDraftCta(),
    });

    updateProjectDraftCta();
  }

  function updateProjectDraftCta() {
    const btn = document.getElementById('projectCreateBtn');
    if (!btn) return;

    btn.innerHTML = state.projectDraftManager?.hasDraft()
      ? '<i class="fas fa-rotate-left"></i> Reprendre brouillon'
      : '<i class="fas fa-plus"></i> Nouveau projet';
  }

  function initShow() {
    bindShowToolbar();
    bindBoards();
    bindTaskDrawer();
    bindMembersModal();
    bindFilesPanel();
    bindWysiwyg();
    state.tagInput = createTagInput(document.getElementById('taskTagsInput'));
    initTaskDrafts();
    renderTaskStatusOptions();
    updateTaskScheduleButton();
    loadBoards().finally(loadTasks);
    loadProjectFiles();

    const boardWrap = document.querySelector('.project-board-wrap');
    if (boardWrap) {
      let boardScrollHideTimer = null;
      boardWrap.addEventListener('scroll', () => {
        boardWrap.classList.add('is-scrolling');
        clearTimeout(boardScrollHideTimer);
        boardScrollHideTimer = setTimeout(() => {
          boardWrap.classList.remove('is-scrolling');
        }, 700);
      }, { passive: true });
    }
  }

  function bindShowToolbar() {
    const search = document.getElementById('projectTasksSearch');
    const assignee = document.getElementById('projectTasksAssigneeFilter');
    const reload = document.getElementById('projectTasksReloadBtn');
    let timer = null;

    search?.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        state.taskSearch = search.value.trim();
        loadTasks();
      }, 250);
    });

    assignee?.addEventListener('change', () => {
      state.taskAssignedTo = assignee.value;
      loadTasks();
    });

    reload?.addEventListener('click', loadTasks);
    document.getElementById('projectScheduleBtn')?.addEventListener('click', scheduleProjectInCalendar);

    document.getElementById('projectViewKanbanBtn')?.addEventListener('click', () => setViewMode('kanban'));
    document.getElementById('projectViewListBtn')?.addEventListener('click', () => setViewMode('list'));
  }

  function bindBoards() {
    const select = document.getElementById('projectBoardSelect');

    select?.addEventListener('change', () => {
      state.activeBoardId = select.value || 'default';
      saveBoardPreference(state.activeBoardId);
      renderTaskStatusOptions();
      applyBoardColumns();
      renderBoard();
    });
  }

  function setViewMode(mode) {
    state.viewMode = mode;
    const boardWrap = document.querySelector('.project-board-wrap');
    const list = document.getElementById('projectTasksList');
    const kBtn = document.getElementById('projectViewKanbanBtn');
    const lBtn = document.getElementById('projectViewListBtn');

    if (kBtn) kBtn.classList.toggle('active', mode === 'kanban');
    if (lBtn) lBtn.classList.toggle('active', mode === 'list');

    if (boardWrap) boardWrap.style.display = mode === 'kanban' ? '' : 'none';
    if (list) list.style.display = mode === 'list' ? '' : 'none';

    if (mode === 'list') renderTasksList();
  }

  function bindTaskDrawer() {
    document.getElementById('taskCreateBtn')?.addEventListener('click', () => openTaskDrawer(null));
    document.getElementById('taskSaveBtn')?.addEventListener('click', saveTask);
    document.getElementById('taskScheduleBtn')?.addEventListener('click', scheduleTaskInCalendar);
    document.getElementById('taskChecklistAddBtn')?.addEventListener('click', addChecklistItem);
    document.getElementById('taskCommentAddBtn')?.addEventListener('click', addTaskComment);
    document.getElementById('taskFileUploadBtn')?.addEventListener('click', uploadTaskFile);

    document.querySelectorAll('[data-task-drawer-close]').forEach((btn) => btn.addEventListener('click', closeTaskDrawer));
    document.getElementById('taskDrawer')?.addEventListener('click', (e) => {
      const overlay = e.target.closest('#taskDrawer');
      if (overlay && e.target === overlay) closeTaskDrawer();
    });

    document.querySelectorAll('[data-task-tab]').forEach((btn) => {
      btn.addEventListener('click', () => setTaskTab(btn.getAttribute('data-task-tab') || 'details'));
    });

    document.getElementById('taskChecklistList')?.addEventListener('click', (e) => {
      const editBtn = e.target.closest('[data-check-edit]');
      if (editBtn) {
        const itemId = parseInt(editBtn.dataset.checkEdit, 10);
        if (!Number.isNaN(itemId)) editChecklistItem(itemId);
        return;
      }

      const toggleBtn = e.target.closest('[data-check-toggle]');
      if (toggleBtn) {
        const itemId = parseInt(toggleBtn.dataset.checkToggle, 10);
        if (!Number.isNaN(itemId)) toggleChecklistItem(itemId);
        return;
      }

      const deleteBtn = e.target.closest('[data-check-delete]');
      if (deleteBtn) {
        const itemId = parseInt(deleteBtn.dataset.checkDelete, 10);
        if (!Number.isNaN(itemId)) deleteChecklistItem(itemId);
      }
    });

    document.getElementById('taskCommentsList')?.addEventListener('click', (e) => {
      const editBtn = e.target.closest('[data-comment-edit]');
      if (editBtn) {
        const commentId = parseInt(editBtn.dataset.commentEdit, 10);
        if (!Number.isNaN(commentId)) editTaskComment(commentId, editBtn.dataset.commentBody || '');
        return;
      }

      const deleteBtn = e.target.closest('[data-comment-delete]');
      if (deleteBtn) {
        const commentId = parseInt(deleteBtn.dataset.commentDelete, 10);
        if (!Number.isNaN(commentId)) deleteTaskComment(commentId);
      }
    });

    document.getElementById('taskFilesList')?.addEventListener('click', (e) => {
      const del = e.target.closest('[data-task-file-delete]');
      if (!del) return;
      const id = parseInt(del.dataset.taskFileDelete, 10);
      if (!Number.isNaN(id)) deleteTaskFile(id);
    });

    document.getElementById('taskFilePickBtn')?.addEventListener('click', () => document.getElementById('taskFileInput')?.click());
    document.getElementById('taskFileInput')?.addEventListener('change', () => {
      const input = document.getElementById('taskFileInput');
      const nameEl = document.getElementById('taskFileName');
      const file = input?.files && input.files[0] ? input.files[0] : null;
      if (nameEl) nameEl.textContent = file ? file.name : 'Aucun fichier';
    });

    bindSelectFilter('taskAssigneeSearch', 'taskAssignedTo');
    bindSelectFilter('taskClientSearch', 'taskClientId');
  }

  async function loadTasks() {
    const board = document.getElementById('projectBoard');
    if (!board) return;

    applyBoardColumns();

    board.querySelectorAll('[data-dropzone]').forEach((zone) => {
      zone.innerHTML = `<div class="skeleton" style="height:52px;border-radius:8px;"></div><div class="skeleton" style="height:52px;border-radius:8px;"></div>`;
    });

    const { ok, data } = await Http.get(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/data`, {
      search: state.taskSearch,
      assigned_to: state.taskAssignedTo,
    });

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Chargement des taches impossible.');
      return;
    }

    state.tasks = data.data || [];
    state.groupedTasks = data.grouped || {};
    if (data.status_map && typeof data.status_map === 'object') {
      setTaskStatuses(data.status_map);
    }
    renderBoard();
    if (state.viewMode === 'list') renderTasksList();
    updateProjectProgress();
  }

  function renderTasksList() {
    const list = document.getElementById('projectTasksList');
    if (!list) return;

    const activeStatuses = new Set(getActiveBoardStatuses());
    const rows = state.tasks.filter((task) => activeStatuses.has(task.status));

    if (!rows.length) {
      list.innerHTML = '<div style="padding:12px;font-size:12px;color:var(--c-ink-40);">Aucune tache.</div>';
      return;
    }

    list.innerHTML = rows.map((t) => `
      <div class="tasks-list-row" data-task-row="${t.id}">
        <div>
          <div class="tasks-list-title">${esc(t.title || '')}</div>
          <div class="tasks-list-sub">${esc(stripHtml(t.description || ''))}</div>
        </div>
        <div>${priorityBadge(t.priority)}</div>
        <div><span class="badge badge-draft">${esc(state.taskStatuses[t.status] || t.status || '-')}</span></div>
        <div style="font-size:12px;color:var(--c-ink-60);"><i class="fas fa-user"></i> ${esc(t.assignee_name || 'Non assigne')}</div>
        <div style="font-size:12px;color:var(--c-ink-60);"><i class="fas fa-calendar"></i> ${t.due_date ? formatDate(t.due_date) : '-'}</div>
        <div class="tasks-list-actions">
          <button class="btn-icon" type="button" data-task-edit="${t.id}" title="Ouvrir"><i class="fas fa-arrow-right"></i></button>
          <button class="btn-icon danger" type="button" data-task-delete="${t.id}" title="Supprimer"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    `).join('');

    list.querySelectorAll('[data-task-edit]').forEach((btn) => btn.addEventListener('click', () => openTaskDrawer(parseInt(btn.dataset.taskEdit, 10))));
    list.querySelectorAll('[data-task-delete]').forEach((btn) => btn.addEventListener('click', () => deleteTask(parseInt(btn.dataset.taskDelete, 10))));
  }

  async function loadBoards() {
    if (!state.projectId || !window.PROJECTS_ROUTES.boardsData) return;

    const { ok, data } = await Http.get(window.PROJECTS_ROUTES.boardsData);
    if (!ok || !data?.success) {
      return;
    }

    state.boards = Array.isArray(data.data) ? data.data : [];
    if (data.status_map && typeof data.status_map === 'object') {
      setTaskStatuses(data.status_map);
    }

    const savedBoardId = loadBoardPreference();
    if (savedBoardId && state.boards.find((b) => String(b.id) === String(savedBoardId))) {
      state.activeBoardId = savedBoardId;
    } else if (!state.boards.find((b) => b.id === state.activeBoardId)) {
      state.activeBoardId = state.boards[0]?.id || 'default';
    }

    saveBoardPreference(state.activeBoardId);
    renderBoardSelector();
    renderTaskStatusOptions();
    applyBoardColumns();
  }

  function renderBoardSelector() {
    const select = document.getElementById('projectBoardSelect');
    if (!select) return;

    if (!Array.isArray(state.boards) || !state.boards.length) {
      select.innerHTML = '<option value="default">Board principal</option>';
      select.value = 'default';
      return;
    }

    select.innerHTML = state.boards.map((board) => `<option value="${esc(board.id)}">${esc(board.name || 'Board')}</option>`).join('');
    select.value = state.activeBoardId;
  }

  function getActiveBoard() {
    if (Array.isArray(state.boards)) {
      const found = state.boards.find((board) => String(board.id) === String(state.activeBoardId));
      if (found) return found;
    }

    const fallbackColumns = Object.keys(state.taskStatuses || {}).map((key) => ({
      key,
      label: state.taskStatuses[key] || key,
    }));
    return {
      id: 'default',
      name: 'Board principal',
      statuses: fallbackColumns.map((column) => column.key),
      columns: fallbackColumns,
    };
  }

  function getActiveBoardColumns() {
    const active = getActiveBoard();
    const explicitColumns = Array.isArray(active.columns) ? active.columns : [];
    if (explicitColumns.length) {
      return explicitColumns
        .map((column) => ({
          key: String(column.key || column.status || '').trim(),
          label: String(column.label || '').trim() || state.taskStatuses[String(column.key || column.status || '').trim()] || String(column.key || column.status || '').trim(),
        }))
        .filter((column) => !!column.key);
    }

    const statuses = Array.isArray(active.statuses) ? active.statuses : Object.keys(state.taskStatuses || {});
    return statuses
      .map((status) => ({
        key: String(status || '').trim(),
        label: state.taskStatuses[String(status || '').trim()] || String(status || '').trim(),
      }))
      .filter((column) => !!column.key);
  }

  function getActiveBoardStatuses() {
    return getActiveBoardColumns().map((column) => column.key);
  }

  function boardPreferenceStorageKey() {
    if (!state.projectId) return '';
    return `projects_active_board_${state.projectId}`;
  }

  function saveBoardPreference(boardId) {
    const key = boardPreferenceStorageKey();
    if (!key) return;
    try {
      window.localStorage.setItem(key, String(boardId || 'default'));
    } catch (_e) {
      // Ignore storage failures (private mode, restricted browser policy, etc.).
    }
  }

  function loadBoardPreference() {
    const key = boardPreferenceStorageKey();
    if (!key) return '';
    try {
      return window.localStorage.getItem(key) || '';
    } catch (_e) {
      return '';
    }
  }

  function setTaskStatuses(statusMap) {
    state.taskStatuses = { ...(statusMap || {}) };
    if (window.PROJECTS_SHOW_BOOTSTRAP) {
      window.PROJECTS_SHOW_BOOTSTRAP.taskStatuses = { ...state.taskStatuses };
    }
    renderTaskStatusOptions();
  }

  function renderTaskStatusOptions() {
    const select = document.getElementById('taskStatus');
    if (!select) return;

    const current = String(select.value || state.editingTask?.status || '');
    const statuses = state.taskStatuses || {};
    const keys = Object.keys(statuses);
    if (!keys.length) return;

    select.innerHTML = keys.map((key) => `
      <option value="${esc(key)}">${esc(statuses[key] || key)}</option>
    `).join('');

    if (current && statuses[current]) {
      select.value = current;
      return;
    }

    select.value = statuses.todo ? 'todo' : keys[0];
  }

  function applyBoardColumns() {
    const board = document.getElementById('projectBoard');
    if (!board) return;

    const columns = getActiveBoardColumns();
    if (!columns.length) {
      board.innerHTML = '';
      return;
    }
    board.innerHTML = columns.map((column) => `
      <section class="project-column" data-column="${esc(column.key)}">
        <header class="project-column-head">
          <h3>${esc(column.label || column.key)}</h3>
          <span class="project-column-count" data-count="${esc(column.key)}">0</span>
        </header>
        <div class="project-column-body" data-dropzone="${esc(column.key)}"></div>
      </section>
    `).join('');
  }

  function renderBoard() {
    applyBoardColumns();
    const columns = getActiveBoardColumns();
    columns.forEach((column) => {
      const status = column.key;
      const safeStatus = escapeSelectorValue(status);
      const zone = document.querySelector(`[data-dropzone="${safeStatus}"]`);
      const countEl = document.querySelector(`[data-count="${safeStatus}"]`);
      if (!zone) return;

      const tasks = state.groupedTasks[status] || [];
      if (countEl) countEl.textContent = String(tasks.length);
      if (!tasks.length) zone.innerHTML = `<div style="font-size:12px;color:var(--c-ink-40);padding:4px;">Aucune tache</div>`;
      else zone.innerHTML = tasks.map(renderTaskCard).join('');

      wireDropzone(zone, status);
      zone.querySelectorAll('[data-task-edit]').forEach((btn) => btn.addEventListener('click', () => openTaskDrawer(parseInt(btn.dataset.taskEdit, 10))));
      zone.querySelectorAll('[data-task-delete]').forEach((btn) => btn.addEventListener('click', () => deleteTask(parseInt(btn.dataset.taskDelete, 10))));
    });

    wireDraggables();
  }

  function renderTaskCard(task) {
    const dueLabel = task.due_date ? formatDate(task.due_date) : '-';
    const tags = Array.isArray(task.tags) ? task.tags : [];
    return `
      <article class="project-task-card" draggable="true" data-task-id="${task.id}">
        <h4 class="project-task-title">${esc(task.title)}</h4>
        <p class="project-task-desc">${esc(stripHtml(task.description || ''))}</p>
        <div class="project-task-meta">
          ${priorityBadge(task.priority)}
          <span class="badge badge-draft"><i class="fas fa-comment"></i> ${task.comments_count || 0}</span>
          <span class="badge badge-sent"><i class="fas fa-list-check"></i> ${task.checklist_done || 0}/${task.checklist_total || 0}</span>
        </div>
        ${tags.length ? `<div class="project-task-meta" style="margin-top:6px;">${tags.map((t) => `<span class="task-tag">${esc(t)}</span>`).join('')}</div>` : ''}
        <div class="project-task-footer">
          <span style="font-size:11px;color:var(--c-ink-40);"><i class="fas fa-user"></i> ${esc(task.assignee_name || 'Non assigne')}</span>
          <span style="font-size:11px;color:var(--c-ink-40);"><i class="fas fa-calendar"></i> ${dueLabel}</span>
        </div>
        <div class="row-actions" style="justify-content:flex-end;padding-right:0;margin-top:8px;opacity:1;">
          <button class="btn-icon" data-task-edit="${task.id}" title="Modifier"><i class="fas fa-pen"></i></button>
          <button class="btn-icon danger" data-task-delete="${task.id}" title="Supprimer"><i class="fas fa-trash"></i></button>
        </div>
      </article>
    `;
  }

  function wireDraggables() {
    document.querySelectorAll('.project-task-card[data-task-id]').forEach((card) => {
      card.addEventListener('dragstart', (e) => {
        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.dataset.taskId);
      });
      card.addEventListener('dragend', () => card.classList.remove('dragging'));
    });
  }

  function wireDropzone(zone, status) {
    zone.addEventListener('dragover', (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
    });

    zone.addEventListener('drop', async (e) => {
      e.preventDefault();
      const taskId = parseInt(e.dataTransfer.getData('text/plain'), 10);
      if (Number.isNaN(taskId)) return;

      const siblings = Array.from(zone.querySelectorAll('.project-task-card[data-task-id]'));
      const position = siblings.length;
      await moveTask(taskId, status, position);
      loadTasks();
    });
  }

  async function moveTask(taskId, status, position) {
    const res = await fetch(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${taskId}/move`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify({ status, position }),
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      Toast.error('Erreur', data.message || 'Deplacement impossible.');
    } else {
      Toast.success('Succes', 'Tache deplacee.');
    }
  }

  function openTaskDrawer(taskId) {
    const overlay = document.getElementById('taskDrawer');
    if (!overlay) return;

    state.editingTask = null;
    resetTaskForm();
    renderTaskStatusOptions();
    setTaskTab('details');

    if (taskId) {
      const task = state.tasks.find((t) => Number(t.id) === Number(taskId));
      if (!task) return;
      state.taskDraftManager?.resetLocal();
      state.editingTask = task;

      setText('taskDrawerTitle', 'Modifier tache');
      setField('taskId', task.id);
      setField('taskTitle', task.title || '');
      setField('taskDescription', task.description || '');
      setWysiwygHtml('taskDescriptionEditor', task.description || '');
      setField('taskStatus', task.status || 'todo');
      setField('taskPriority', task.priority || 'medium');
      setField('taskAssignedTo', task.assigned_to || '');
      setField('taskStartDate', task.start_date || '');
      setField('taskDueDate', task.due_date || '');
      setField('taskEstimate', task.estimate_hours || '');
      setField('taskSpent', task.spent_hours || '');
      setField('taskClientId', task.client_id || '');
      setCheckbox('taskSyncGoogleCalendar', !!(state.googleCalendarInstalled && task.google_calendar?.event_id));
      if (state.tagInput) state.tagInput.setTags(Array.isArray(task.tags) ? task.tags : []);
      setField('taskTagsHidden', Array.isArray(task.tags) ? task.tags.join(',') : '');

      loadTaskComments(task.id);
      loadTaskChecklist(task.id);
      loadTaskFiles(task.id);
    } else {
      setText('taskDrawerTitle', 'Nouvelle tache');
      window.setTimeout(async () => {
        await state.taskDraftManager?.load({ prompt: false });
        state.taskDraftManager?.promptResumeIfAvailable();
      }, 0);
    }

    updateTaskScheduleButton();

    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeTaskDrawer() {
    const overlay = document.getElementById('taskDrawer');
    if (!overlay) return;
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  function setTaskTab(tab) {
    state.activeTaskTab = tab;
    document.querySelectorAll('[data-task-tab]').forEach((b) => b.classList.toggle('active', b.getAttribute('data-task-tab') === tab));
    document.querySelectorAll('[data-task-panel]').forEach((p) => {
      p.style.display = p.getAttribute('data-task-panel') === tab ? '' : 'none';
    });
  }

  async function saveTask() {
    const form = document.getElementById('taskForm');
    const btn = document.getElementById('taskSaveBtn');
    if (!btn) return;

    const drawerForm = document.getElementById('taskDrawerForm');
    if (drawerForm) CrmForm.clearErrors(drawerForm);

    const payload = {
      title: (getField('taskTitle') || '').trim(),
      description: getWysiwygHtml('taskDescriptionEditor'),
      status: getField('taskStatus') || 'todo',
      priority: getField('taskPriority') || 'medium',
      assigned_to: normalizeNullable(getField('taskAssignedTo')),
      start_date: normalizeNullable(getField('taskStartDate')),
      due_date: normalizeNullable(getField('taskDueDate')),
      estimate_hours: normalizeNullable(getField('taskEstimate')),
      spent_hours: normalizeNullable(getField('taskSpent')),
      client_id: normalizeNullable(getField('taskClientId')),
      tags: state.tagInput ? state.tagInput.getTags() : normalizeTags(getField('taskTagsHidden')),
      sync_google_calendar: getCheckbox('taskSyncGoogleCalendar'),
      draft_id: normalizeNullable(drawerForm?.querySelector('[name="draft_id"]')?.value || ''),
    };
    setField('taskDescription', payload.description || '');
    setField('taskTagsHidden', Array.isArray(payload.tags) ? payload.tags.join(',') : '');

    const errors = validateTaskPayload(payload);
    if (Object.keys(errors).length > 0) {
      if (drawerForm) CrmForm.showErrors(drawerForm, errors);
      Toast.error('Validation', 'Merci de corriger les erreurs de tache.');
      return;
    }

    CrmForm.setLoading(btn, true);
    let response;
    if (state.editingTask) response = await Http.put(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${state.editingTask.id}`, payload);
    else response = await Http.post(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks`, payload);
    CrmForm.setLoading(btn, false);

    if (!response.ok) {
      if (drawerForm && response.status === 422 && response.data?.errors) CrmForm.showErrors(drawerForm, response.data.errors);
      Toast.error('Erreur', response.data?.message || 'Enregistrement impossible.');
      return;
    }

    Toast.success('Succes', response.data?.message || 'Tache enregistree.');
    state.taskDraftManager?.complete();
    handleCalendarSyncFeedback(response.data, 'La tache');
    closeTaskDrawer();
    resetTaskForm();
    loadTasks();
  }

  function updateTaskScheduleButton() {
    const btn = document.getElementById('taskScheduleBtn');
    if (!btn) return;

    if (!state.googleCalendarInstalled) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-store"></i> Installer Calendar';
      return;
    }

    if (!state.editingTask || !state.editingTask.id) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-calendar-plus"></i> Planifier tache';
      return;
    }

    btn.disabled = false;
    const hasCalendarLink = !!(state.editingTask.google_calendar && state.editingTask.google_calendar.event_id);
    btn.innerHTML = hasCalendarLink
      ? '<i class="fas fa-calendar-check"></i> Replanifier tache'
      : '<i class="fas fa-calendar-plus"></i> Planifier tache';
  }

  function validateTaskPayload(payload) {
    const errors = {};
    if (!payload.title) errors.title = ['Le titre est obligatoire.'];
    if (!payload.status || !state.taskStatuses[payload.status]) {
      errors.status = ['Le statut selectionne est invalide.'];
    }
    if (!payload.start_date) errors.start_date = ['La date debut est obligatoire.'];
    if (payload.start_date && payload.due_date) {
      const start = new Date(payload.start_date);
      const due = new Date(payload.due_date);
      if (!Number.isNaN(start.getTime()) && !Number.isNaN(due.getTime()) && due < start) {
        errors.due_date = ['La date echeance doit etre >= date debut.'];
      }
    }
    return errors;
  }

  async function deleteTask(taskId) {
    const task = state.tasks.find((t) => Number(t.id) === Number(taskId));
    if (!task) return;

    Modal.confirm({
      title: 'Supprimer cette tache ?',
      message: `La tache "${task.title}" sera supprimee.`,
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const response = await Http.delete(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${taskId}`);
        if (!response.ok) {
          Toast.error('Erreur', response.data?.message || 'Suppression impossible.');
          return;
        }
        Toast.success('Supprime', response.data?.message || 'Tache supprimee.');
        loadTasks();
      },
    });
  }

  async function scheduleProjectInCalendar() {
    if (!state.projectId) return;

    if (!state.googleCalendarInstalled) {
      suggestCalendarInstall('Google Calendar est requis pour planifier un projet.');
      return;
    }

    const btn = document.getElementById('projectScheduleBtn');
    if (btn) CrmForm.setLoading(btn, true);

    const response = await Http.post(window.PROJECTS_ROUTES.scheduleProject || `${window.PROJECTS_ROUTES.base}/${state.projectId}/calendar/schedule`, {});

    if (btn) CrmForm.setLoading(btn, false);

    if (!response.ok || !response.data?.success) {
      handleCalendarScheduleError(response, 'Planification du projet impossible.');
      return;
    }

    Toast.success('Succes', response.data?.message || 'Projet planifie dans Google Calendar.');

    const link = response.data?.data?.html_link;
    if (link) {
      window.open(link, '_blank', 'noopener,noreferrer');
    }
  }

  async function scheduleTaskInCalendar() {
    if (!state.editingTask || !state.editingTask.id) {
      Toast.warning('Info', 'Enregistrez la tache avant de la planifier dans Google Calendar.');
      return;
    }

    if (!state.googleCalendarInstalled) {
      suggestCalendarInstall('Google Calendar est requis pour planifier une tache.');
      return;
    }

    const btn = document.getElementById('taskScheduleBtn');
    if (btn) CrmForm.setLoading(btn, true);

    const base = window.PROJECTS_ROUTES.scheduleTaskBase || `${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks`;
    const response = await Http.post(`${base}/${state.editingTask.id}/calendar/schedule`, {});

    if (btn) CrmForm.setLoading(btn, false);

    if (!response.ok || !response.data?.success) {
      handleCalendarScheduleError(response, 'Planification de la tache impossible.');
      return;
    }

    if (response.data?.task) {
      state.editingTask = response.data.task;
      updateTaskScheduleButton();
    }

    Toast.success('Succes', response.data?.message || 'Tache planifiee dans Google Calendar.');
    loadTasks();

    const link = response.data?.data?.html_link;
    if (link) {
      window.open(link, '_blank', 'noopener,noreferrer');
    }
  }

  function handleCalendarScheduleError(response, fallbackMessage) {
    const message = response?.data?.message || fallbackMessage || 'Operation impossible.';
    const actionUrl = response?.data?.action_url || state.googleCalendarTargetUrl || '';

    if (actionUrl) {
      Modal.confirm({
        title: 'Configuration Google Calendar requise',
        message: `${message}\n\nVoulez-vous ouvrir la page de configuration maintenant ?`,
        confirmText: 'Ouvrir',
        type: 'warning',
        onConfirm: () => { window.location.href = actionUrl; },
      });
      return;
    }

    Toast.error('Erreur', message);
  }

  function handleCalendarSyncFeedback(payload, subjectLabel) {
    const warning = payload?.calendar_warning || '';
    const actionUrl = payload?.calendar_action_url || state.googleCalendarTargetUrl || '';
    const event = payload?.calendar || null;

    if (warning) {
      if (actionUrl) {
        Modal.confirm({
          title: 'Synchronisation Google Calendar partielle',
          message: `${warning}\n\nVoulez-vous ouvrir la page Google Calendar maintenant ?`,
          confirmText: 'Ouvrir',
          type: 'warning',
          onConfirm: () => { window.location.href = actionUrl; },
        });
      } else {
        Toast.warning('Google Calendar', warning);
      }
      return;
    }

    if (event?.event_id) {
      Toast.success('Google Calendar', `${subjectLabel} est synchronise(e) avec Google Calendar.`);
    }
  }

  function suggestCalendarInstall(message) {
    if (!state.googleCalendarTargetUrl) {
      Toast.warning('Info', message);
      return;
    }

    Modal.confirm({
      title: 'Extension Google Calendar non installee',
      message: `${message}\n\nVoulez-vous ouvrir Marketplace pour l\'installer ?`,
      confirmText: 'Installer',
      type: 'warning',
      onConfirm: () => { window.location.href = state.googleCalendarTargetUrl; },
    });
  }

  async function loadTaskComments(taskId) {
    const list = document.getElementById('taskCommentsList');
    if (!list) return;
    list.innerHTML = '<div class="skeleton" style="height:44px;border-radius:8px;"></div>';

    const response = await Http.get(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${taskId}/comments`);
    if (!response.ok || !response.data?.success) {
      list.innerHTML = '<div style="font-size:12px;color:var(--c-danger);">Impossible de charger les commentaires.</div>';
      return;
    }

    const rows = response.data.data || [];
    if (!rows.length) {
      list.innerHTML = '<div style="font-size:12px;color:var(--c-ink-40);">Aucun commentaire.</div>';
      return;
    }

    list.innerHTML = rows.map((row) => `
      <div class="task-comment-item">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
          <div style="font-weight:700;color:var(--c-ink);">${esc(row.user?.name || 'Utilisateur')}</div>
          ${row.can_edit ? `
            <div class="row-actions" style="opacity:1;display:flex;gap:6px;">
              <button class="btn-icon" type="button" data-comment-edit="${row.id}" data-comment-body="${esc(row.comment || '')}" title="Modifier"><i class="fas fa-pen"></i></button>
              <button class="btn-icon danger" type="button" data-comment-delete="${row.id}" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
          ` : ''}
        </div>
        <div style="color:var(--c-ink-60);margin-top:2px;white-space:pre-wrap;">${esc(row.comment || '')}</div>
        <div style="font-size:11px;color:var(--c-ink-40);margin-top:3px;">${formatDateTime(row.created_at)}</div>
      </div>
    `).join('');
  }

  async function addTaskComment() {
    if (!state.editingTask) {
      Toast.warning('Info', 'Enregistrez la tache avant de commenter.');
      return;
    }

    const input = document.getElementById('taskCommentBody');
    const value = (input?.value || '').trim();
    if (!value) {
      Toast.warning('Validation', 'Le commentaire est vide.');
      return;
    }

    const response = await Http.post(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${state.editingTask.id}/comments`, { comment: value });
    if (!response.ok) {
      Toast.error('Erreur', response.data?.message || 'Ajout commentaire impossible.');
      return;
    }

    if (input) input.value = '';
    Toast.success('Succes', response.data?.message || 'Commentaire ajoute.');
    loadTaskComments(state.editingTask.id);
    loadTasks();
  }

  async function editTaskComment(commentId, currentBody) {
    if (!state.editingTask) return;

    const value = window.prompt('Modifier le commentaire :', String(currentBody || ''));
    if (value === null) return;
    const body = value.trim();
    if (!body) {
      Toast.warning('Validation', 'Le commentaire est obligatoire.');
      return;
    }

    const response = await Http.put(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${state.editingTask.id}/comments/${commentId}`, { comment: body });
    if (!response.ok) {
      Toast.error('Erreur', response.data?.message || 'Modification commentaire impossible.');
      return;
    }

    Toast.success('Succes', response.data?.message || 'Commentaire mis a jour.');
    loadTaskComments(state.editingTask.id);
    loadTasks();
  }

  async function deleteTaskComment(commentId) {
    if (!state.editingTask) return;

    Modal.confirm({
      title: 'Supprimer ce commentaire ?',
      message: 'Le commentaire sera supprime definitivement.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const response = await Http.delete(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${state.editingTask.id}/comments/${commentId}`);
        if (!response.ok) {
          Toast.error('Erreur', response.data?.message || 'Suppression commentaire impossible.');
          return;
        }

        Toast.success('Succes', response.data?.message || 'Commentaire supprime.');
        loadTaskComments(state.editingTask.id);
        loadTasks();
      },
    });
  }

  async function loadTaskChecklist(taskId) {
    const list = document.getElementById('taskChecklistList');
    if (!list) return;
    list.innerHTML = '<div class="skeleton" style="height:44px;border-radius:8px;"></div>';

    const response = await Http.get(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${taskId}/checklist`);
    if (!response.ok || !response.data?.success) {
      list.innerHTML = '<div style="font-size:12px;color:var(--c-danger);">Impossible de charger la checklist.</div>';
      return;
    }

    const items = response.data.data || [];
    if (!items.length) {
      list.innerHTML = '<div style="font-size:12px;color:var(--c-ink-40);">Aucun item checklist.</div>';
      return;
    }

    list.innerHTML = items.map((item) => `
      <div class="task-check-item ${item.is_done ? 'done' : ''}">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
          <button class="btn-icon" data-check-toggle="${item.id}" title="Basculer">
            <i class="fas ${item.is_done ? 'fa-circle-check' : 'fa-circle'}"></i>
          </button>
          <div style="flex:1;color:var(--c-ink-70);">${esc(item.title || '')}</div>
          <button class="btn-icon" data-check-edit="${item.id}" data-check-title="${esc(item.title || '')}" title="Modifier"><i class="fas fa-pen"></i></button>
          <button class="btn-icon danger" data-check-delete="${item.id}" title="Supprimer"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    `).join('');
  }

  async function addChecklistItem() {
    if (!state.editingTask) {
      Toast.warning('Info', 'Enregistrez la tache avant la checklist.');
      return;
    }

    const input = document.getElementById('taskChecklistTitle');
    const value = (input?.value || '').trim();
    if (!value) {
      Toast.warning('Validation', 'Le titre checklist est vide.');
      return;
    }

    const response = await Http.post(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${state.editingTask.id}/checklist`, { title: value });
    if (!response.ok) {
      Toast.error('Erreur', response.data?.message || 'Ajout checklist impossible.');
      return;
    }

    if (input) input.value = '';
    Toast.success('Succes', response.data?.message || 'Checklist ajoutee.');
    loadTaskChecklist(state.editingTask.id);
    loadTasks();
  }

  async function editChecklistItem(itemId) {
    if (!state.editingTask) return;

    const currentTitle = Array.from(document.querySelectorAll('#taskChecklistList [data-check-edit]'))
      .find((btn) => Number(btn.dataset.checkEdit) === Number(itemId))
      ?.dataset?.checkTitle || '';
    const value = window.prompt('Modifier l\'item checklist :', currentTitle);
    if (value === null) return;
    const title = value.trim();
    if (!title) {
      Toast.warning('Validation', 'Le titre checklist est obligatoire.');
      return;
    }

    const response = await Http.put(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${state.editingTask.id}/checklist/${itemId}`, { title });
    if (!response.ok) {
      Toast.error('Erreur', response.data?.message || 'Modification checklist impossible.');
      return;
    }

    Toast.success('Succes', response.data?.message || 'Checklist mise a jour.');
    loadTaskChecklist(state.editingTask.id);
    loadTasks();
  }

  async function toggleChecklistItem(itemId) {
    if (!state.editingTask) return;

    const res = await fetch(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${state.editingTask.id}/checklist/${itemId}/toggle`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify({}),
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      Toast.error('Erreur', data.message || 'Mise a jour checklist impossible.');
      return;
    }

    loadTaskChecklist(state.editingTask.id);
    loadTasks();
  }

  async function deleteChecklistItem(itemId) {
    if (!state.editingTask) return;

    const response = await Http.delete(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${state.editingTask.id}/checklist/${itemId}`);
    if (!response.ok) {
      Toast.error('Erreur', response.data?.message || 'Suppression checklist impossible.');
      return;
    }

    Toast.success('Succes', response.data?.message || 'Checklist supprimee.');
    loadTaskChecklist(state.editingTask.id);
    loadTasks();
  }

  function bindMembersModal() {
    const addBtn = document.getElementById('projectMembersAddRowBtn');
    const saveBtn = document.getElementById('projectMembersSaveBtn');

    addBtn?.addEventListener('click', () => addMemberRow());
    saveBtn?.addEventListener('click', saveMembers);

    document.getElementById('projectMembersRows')?.addEventListener('click', (e) => {
      const remove = e.target.closest('[data-member-remove]');
      if (!remove) return;
      remove.closest('[data-member-row]')?.remove();
    });
  }

  function bindSelectFilter(searchId, selectId) {
    const input = document.getElementById(searchId);
    const select = document.getElementById(selectId);
    if (!input || !select) return;

    const allOptions = Array.from(select.querySelectorAll('option')).map((o) => ({
      value: o.value,
      text: o.textContent || '',
      selected: o.selected,
    }));

    const rebuild = (term) => {
      const t = String(term || '').trim().toLowerCase();
      const current = select.value;
      select.innerHTML = '';

      allOptions.forEach((opt, idx) => {
        if (idx === 0 || t === '' || opt.text.toLowerCase().includes(t)) {
          const o = document.createElement('option');
          o.value = opt.value;
          o.textContent = opt.text;
          select.appendChild(o);
        }
      });

      select.value = current;
    };

    input.addEventListener('input', () => rebuild(input.value));
    rebuild('');
  }

  function bindFilesPanel() {
    document.getElementById('projectFileUploadBtn')?.addEventListener('click', uploadProjectFile);
    document.getElementById('projectFilePickBtn')?.addEventListener('click', () => document.getElementById('projectFileInput')?.click());
    document.getElementById('projectFileInput')?.addEventListener('change', () => {
      const input = document.getElementById('projectFileInput');
      const nameEl = document.getElementById('projectFileName');
      const file = input?.files && input.files[0] ? input.files[0] : null;
      if (nameEl) nameEl.textContent = file ? file.name : 'Aucun fichier';
    });
    document.getElementById('projectFilesList')?.addEventListener('click', (e) => {
      const del = e.target.closest('[data-project-file-delete]');
      if (del) {
        const id = parseInt(del.dataset.projectFileDelete, 10);
        if (!Number.isNaN(id)) deleteProjectFile(id);
      }
    });
  }

  async function loadProjectFiles() {
    const list = document.getElementById('projectFilesList');
    if (!list || !state.projectId) return;

    list.innerHTML = '<div class="skeleton" style="height:44px;border-radius:10px;"></div>';
    const res = await Http.get(`${window.PROJECTS_ROUTES.base}/${state.projectId}/files`);
    if (!res.ok || !res.data?.success) {
      list.innerHTML = `<div style="font-size:12px;color:var(--c-danger);">Impossible de charger les fichiers. ${esc(res.data?.message || '')}</div>`;
      return;
    }

    const rows = Array.isArray(res.data.data) ? res.data.data : [];
    if (!rows.length) {
      list.innerHTML = '<div style="font-size:12px;color:var(--c-ink-40);padding:6px;">Aucun fichier pour ce projet.</div>';
      return;
    }

    list.innerHTML = rows.map(renderProjectFileRow).join('');
  }

  function renderProjectFileRow(row) {
    const icon = row?.meta?.icon ? String(row.meta.icon) : 'fa-file';
    const color = row?.meta?.color ? String(row.meta.color) : '#64748b';
    const size = formatSize(Number(row.size_bytes || 0));
    const created = row.created_at ? formatDateTime(row.created_at) : '';
    const view = row.web_view_link ? `<a class="btn btn-secondary btn-sm" href="${esc(row.web_view_link)}" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> Ouvrir</a>` : '';

    return `
      <div class="project-file-row">
        <div class="project-file-left">
          <div class="project-file-icon" style="color:${esc(color)}"><i class="fas ${esc(icon)}"></i></div>
          <div style="min-width:0;">
            <div class="project-file-name" title="${esc(row.name || '')}">${esc(row.name || '')}</div>
            <div class="project-file-meta">${esc(size)}${created ? ` • ${esc(created)}` : ''}</div>
          </div>
        </div>
        <div class="project-file-actions">
          ${view}
          <button class="btn btn-danger btn-sm" data-project-file-delete="${row.id}"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    `;
  }

  async function uploadProjectFile() {
    const input = document.getElementById('projectFileInput');
    const btn = document.getElementById('projectFileUploadBtn');
    if (!input || !btn || !state.projectId) return;

    const file = input.files && input.files[0] ? input.files[0] : null;
    if (!file) {
      Toast.warning('Validation', 'Selectionnez un fichier.');
      return;
    }

    const fd = new FormData();
    fd.append('file', file);

    CrmForm.setLoading(btn, true);
    const res = await fetch(`${window.PROJECTS_ROUTES.base}/${state.projectId}/files`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: fd,
    });
    const data = await res.json().catch(() => ({}));
    CrmForm.setLoading(btn, false);

    if (!res.ok || !data.success) {
      Toast.error('Erreur', data.message || "Ajout fichier impossible. Verifiez l'application Google Drive.");
      return;
    }

    Toast.success('Succes', data.message || 'Fichier ajoute.');
    input.value = '';
    const nameEl = document.getElementById('projectFileName');
    if (nameEl) nameEl.textContent = 'Aucun fichier';
    loadProjectFiles();
  }

  async function deleteProjectFile(fileId) {
    Modal.confirm({
      title: 'Supprimer ce fichier ?',
      message: 'Le fichier sera place dans la corbeille Google Drive.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const res = await Http.delete(`${window.PROJECTS_ROUTES.base}/${state.projectId}/files/${fileId}`);
        if (!res.ok || !res.data?.success) {
          Toast.error('Erreur', res.data?.message || 'Suppression impossible.');
          return;
        }
        Toast.success('Succes', res.data?.message || 'Fichier supprime.');
        loadProjectFiles();
      },
    });
  }

  function addMemberRow(userId = '', role = 'member') {
    const container = document.getElementById('projectMembersRows');
    if (!container) return;

    const users = Array.isArray(window.PROJECTS_USERS) ? window.PROJECTS_USERS : [];
    const roles = [
      { key: 'owner', label: 'Owner' },
      { key: 'manager', label: 'Manager' },
      { key: 'member', label: 'Member' },
      { key: 'viewer', label: 'Viewer' },
    ];

    const row = document.createElement('div');
    row.className = 'member-row';
    row.setAttribute('data-member-row', '1');
    row.innerHTML = `
      <select class="form-control" data-member-user>
        ${users.map((u) => `<option value="${u.id}" ${String(u.id) === String(userId) ? 'selected' : ''}>${esc(u.name)}</option>`).join('')}
      </select>
      <select class="form-control" data-member-role>
        ${roles.map((r) => `<option value="${r.key}" ${r.key === role ? 'selected' : ''}>${r.label}</option>`).join('')}
      </select>
      <button class="btn btn-danger" type="button" data-member-remove><i class="fas fa-trash"></i></button>
    `;
    container.appendChild(row);
  }

  async function saveMembers() {
    const rows = Array.from(document.querySelectorAll('#projectMembersRows [data-member-row]'));
    const members = rows.map((row) => ({
      user_id: Number(row.querySelector('[data-member-user]')?.value || 0),
      role: row.querySelector('[data-member-role]')?.value || 'member',
    })).filter((r) => r.user_id > 0);

    if (!members.length) {
      Toast.warning('Validation', 'Ajoutez au moins un membre.');
      return;
    }

    const response = await Http.put(`${window.PROJECTS_ROUTES.base}/${state.projectId}/members`, { members });
    if (!response.ok) {
      Toast.error('Erreur', response.data?.message || 'Sauvegarde des membres impossible.');
      return;
    }

    Toast.success('Succes', response.data?.message || 'Membres mis a jour.');
    setText('projectMembersCount', (response.data?.data || []).length || members.length);
    Modal.close(document.getElementById('projectMembersModal'));
  }

  function updateProjectProgress() {
    const total = state.tasks.length;
    const done = state.tasks.filter((task) => task.status === 'done').length;
    const progress = total > 0 ? Math.round((done / total) * 100) : 0;
    setText('projectProgressValue', `${progress}%`);
  }

  function resetTaskForm() {
    const drawer = document.getElementById('taskDrawerForm');
    if (drawer) drawer.reset();
    state.editingTask = null;
    setField('taskId', '');
    renderTaskStatusOptions();
    const defaultStatus = state.taskStatuses.todo ? 'todo' : (Object.keys(state.taskStatuses || {})[0] || 'todo');
    setField('taskStatus', defaultStatus);
    setField('taskPriority', 'medium');
    setField('taskAssignedTo', '');
    setField('taskClientId', '');
    setWysiwygHtml('taskDescriptionEditor', '');
    setField('taskDescription', '');
    setField('taskTagsHidden', '');
    setCheckbox('taskSyncGoogleCalendar', false);
    if (state.tagInput) state.tagInput.setTags([]);
    const comments = document.getElementById('taskCommentsList');
    const checklist = document.getElementById('taskChecklistList');
    const files = document.getElementById('taskFilesList');
    if (comments) comments.innerHTML = '';
    if (checklist) checklist.innerHTML = '';
    if (files) files.innerHTML = '';
    const fn = document.getElementById('taskFileName');
    if (fn) fn.textContent = 'Aucun fichier';
    updateTaskScheduleButton();
  }

  function initTaskDrafts() {
    state.taskDraftManager = window.CrmDrafts?.attach('taskDrawerForm', {
      type: 'task',
      label: 'tache',
      promptOnLoad: false,
      collect: (data) => {
        data.description = getWysiwygHtml('taskDescriptionEditor');
        data.tags = state.tagInput ? state.tagInput.getTags() : normalizeTags(getField('taskTagsHidden'));
        return data;
      },
      apply: (data) => {
        const tags = Array.isArray(data.tags) ? data.tags : normalizeTags(data.tags);
        setWysiwygHtml('taskDescriptionEditor', data.description || '');
        setField('taskDescription', data.description || '');
        setField('taskTagsHidden', tags.join(','));
        if (state.tagInput) state.tagInput.setTags(tags);
      },
      shouldSave: () => !state.editingTask,
      onStateChange: () => updateTaskDraftCta(),
    });

    updateTaskDraftCta();
  }

  function updateTaskDraftCta() {
    const btn = document.getElementById('taskCreateBtn');
    if (!btn) return;

    btn.innerHTML = state.taskDraftManager?.hasDraft()
      ? '<i class="fas fa-rotate-left"></i> Reprendre brouillon'
      : '<i class="fas fa-plus"></i> Nouvelle tache';
  }

  async function loadTaskFiles(taskId) {
    const list = document.getElementById('taskFilesList');
    if (!list || !taskId) return;
    list.innerHTML = '<div class="skeleton" style="height:44px;border-radius:10px;"></div>';

    const res = await Http.get(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${taskId}/files`);
    if (!res.ok || !res.data?.success) {
      list.innerHTML = `<div style="font-size:12px;color:var(--c-danger);">Impossible de charger les fichiers.</div>`;
      return;
    }

    const rows = Array.isArray(res.data.data) ? res.data.data : [];
    if (!rows.length) {
      list.innerHTML = '<div style="font-size:12px;color:var(--c-ink-40);padding:6px;">Aucun fichier.</div>';
      return;
    }

    list.innerHTML = rows.map((row) => {
      const icon = row?.meta?.icon ? String(row.meta.icon) : 'fa-file';
      const color = row?.meta?.color ? String(row.meta.color) : '#64748b';
      const size = formatSize(Number(row.size_bytes || 0));
      const view = row.web_view_link ? `<a class="btn btn-secondary btn-sm" href="${esc(row.web_view_link)}" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> Ouvrir</a>` : '';

      return `
        <div class="project-file-row">
          <div class="project-file-left">
            <div class="project-file-icon" style="color:${esc(color)}"><i class="fas ${esc(icon)}"></i></div>
            <div style="min-width:0;">
              <div class="project-file-name" title="${esc(row.name || '')}">${esc(row.name || '')}</div>
              <div class="project-file-meta">${esc(size)}</div>
            </div>
          </div>
          <div class="project-file-actions">
            ${view}
            <button class="btn btn-danger btn-sm" data-task-file-delete="${row.id}"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      `;
    }).join('');
  }

  async function uploadTaskFile() {
    if (!state.editingTask) {
      Toast.warning('Info', 'Enregistrez la tache avant d\'ajouter des fichiers.');
      return;
    }

    const input = document.getElementById('taskFileInput');
    const btn = document.getElementById('taskFileUploadBtn');
    if (!input || !btn) return;

    const file = input.files && input.files[0] ? input.files[0] : null;
    if (!file) {
      Toast.warning('Validation', 'Selectionnez un fichier.');
      return;
    }

    const fd = new FormData();
    fd.append('file', file);

    CrmForm.setLoading(btn, true);
    const res = await fetch(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${state.editingTask.id}/files`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: fd,
    });
    const data = await res.json().catch(() => ({}));
    CrmForm.setLoading(btn, false);

    if (!res.ok || !data.success) {
      Toast.error('Erreur', data.message || 'Ajout fichier impossible.');
      return;
    }

    Toast.success('Succes', data.message || 'Fichier ajoute.');
    input.value = '';
    const nameEl = document.getElementById('taskFileName');
    if (nameEl) nameEl.textContent = 'Aucun fichier';
    loadTaskFiles(state.editingTask.id);
  }

  async function deleteTaskFile(fileId) {
    if (!state.editingTask) return;

    Modal.confirm({
      title: 'Supprimer ce fichier ?',
      message: 'Le fichier sera place dans la corbeille Google Drive.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const res = await Http.delete(`${window.PROJECTS_ROUTES.base}/${state.projectId}/tasks/${state.editingTask.id}/files/${fileId}`);
        if (!res.ok || !res.data?.success) {
          Toast.error('Erreur', res.data?.message || 'Suppression impossible.');
          return;
        }
        Toast.success('Succes', res.data?.message || 'Fichier supprime.');
        loadTaskFiles(state.editingTask.id);
      },
    });
  }

  function projectStatusBadge(status) {
    const map = {
      planning: { cls: 'badge-draft', label: 'Planification' },
      active: { cls: 'badge-paid', label: 'Actif' },
      on_hold: { cls: 'badge-sent', label: 'En pause' },
      completed: { cls: 'badge-paid', label: 'Termine' },
      archived: { cls: 'badge-cancelled', label: 'Archive' },
    };
    const cfg = map[status] || { cls: 'badge-draft', label: status || '-' };
    return `<span class="badge ${cfg.cls}">${cfg.label}</span>`;
  }

  function priorityBadge(priority) {
    const map = { low: 'Faible', medium: 'Moyenne', high: 'Haute', critical: 'Critique' };
    const label = map[priority] || (priority || '-');
    return `<span class="badge badge-priority-${esc(priority || 'medium')}">${label}</span>`;
  }

  function normalizeNullable(value) {
    return value === '' || value === null || typeof value === 'undefined' ? null : value;
  }

  function normalizeTags(value) {
    if (!value) return [];
    return String(value).split(',').map((v) => v.trim()).filter((v) => !!v).slice(0, 20);
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
  }

  function setField(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value;
  }

  function setCheckbox(id, value) {
    const el = document.getElementById(id);
    if (el && el.type === 'checkbox') el.checked = !!value;
  }

  function getField(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
  }

  function getCheckbox(id) {
    const el = document.getElementById(id);
    return !!(el && el.type === 'checkbox' && el.checked);
  }

  function getMultiValues(id) {
    const el = document.getElementById(id);
    if (!el) return [];
    return Array.from(el.selectedOptions || []).map((opt) => opt.value);
  }

  function escapeSelectorValue(value) {
    const raw = String(value || '');
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(raw);
    }
    return raw.replace(/["\\]/g, '\\$&');
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  function stripHtml(value) {
    if (!value) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = String(value);
    return (tmp.textContent || tmp.innerText || '').trim();
  }

  function getWysiwygHtml(editorId) {
    const el = document.getElementById(editorId);
    if (!el) return '';
    const html = (el.innerHTML || '').trim();
    return html === '<br>' ? '' : html;
  }

  function setWysiwygHtml(editorId, html) {
    const el = document.getElementById(editorId);
    if (!el) return;
    el.innerHTML = html || '';
  }

  function createTagInput(root, options = {}) {
    if (!root) return null;
    const badges = root.querySelector('[data-tag-badges]');
    const input = root.querySelector('input.tag-field');
    const targetId = root.getAttribute('data-target');
    const hidden = targetId ? document.getElementById(targetId) : null;
    const preserveCase = !!options.preserveCase;
    const maxTags = Number.isInteger(options.maxTags) ? Math.max(1, options.maxTags) : 20;
    let tags = [];

    const normalize = (t) => {
      const value = String(t || '').trim();
      return preserveCase ? value : value.toLowerCase();
    };

    const syncHidden = () => {
      if (hidden) hidden.value = tags.join(',');
    };

    const render = () => {
      if (!badges) return;
      badges.innerHTML = tags.map((t) => `
        <span class="tag-badge">
          ${esc(t)}
          <button type="button" data-tag-remove="${encodeURIComponent(t)}" title="Retirer"><i class="fas fa-xmark"></i></button>
        </span>
      `).join('');
      syncHidden();
    };

    const addTag = (raw) => {
      const t = normalize(raw);
      if (!t) return;
      if (tags.some((tag) => tag.toLowerCase() === t.toLowerCase())) return;
      if (tags.length >= maxTags) return;
      tags.push(t);
      render();
    };

    const removeTag = (t) => {
      tags = tags.filter((x) => x !== t);
      render();
    };

    root.addEventListener('click', () => input?.focus());

    root.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-tag-remove]');
      if (!btn) return;
      const raw = btn.getAttribute('data-tag-remove');
      const t = raw ? decodeURIComponent(raw) : '';
      if (t) removeTag(t);
    });

    input?.addEventListener('keydown', (e) => {
      if (e.key === ',' || e.key === 'Enter' || e.key === 'Tab') {
        e.preventDefault();
        addTag(input.value);
        input.value = '';
        return;
      }
      if (e.key === 'Backspace' && !input.value && tags.length) {
        tags.pop();
        render();
      }
    });

    input?.addEventListener('blur', () => {
      if (!input.value) return;
      addTag(input.value);
      input.value = '';
    });

    render();

    return {
      getTags: () => tags.slice(),
      setTags: (arr) => {
        tags = (Array.isArray(arr) ? arr : [])
          .map(normalize)
          .filter((t) => !!t)
          .filter((tag, index, all) => all.findIndex((other) => other.toLowerCase() === tag.toLowerCase()) === index)
          .slice(0, maxTags);
        render();
      },
    };
  }

  function skeletonRows(rows, cols) {
    return Array.from({ length: rows }, () => `<tr>${Array.from({ length: cols }, () => '<td><div class="skeleton" style="height:14px;"></div></td>').join('')}</tr>`).join('');
  }

  function emptyRow(colspan, message) {
    return `<tr><td colspan="${colspan}"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-diagram-project"></i></div><h3>Aucune donnee</h3><p>${esc(message)}</p></div></td></tr>`;
  }

  function formatDate(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('fr-FR');
  }

  function formatDateTime(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value || '';
    return date.toLocaleString('fr-FR');
  }

  function formatSize(bytes) {
    const b = Number.isNaN(bytes) ? 0 : bytes;
    if (!b || b <= 0) return '-';
    if (b < 1024) return `${b} B`;
    if (b < 1024 * 1024) return `${Math.round((b / 1024) * 10) / 10} KB`;
    if (b < 1024 * 1024 * 1024) return `${Math.round((b / (1024 * 1024)) * 10) / 10} MB`;
    return `${Math.round((b / (1024 * 1024 * 1024)) * 100) / 100} GB`;
  }

  return { boot };
})();

window.ProjectsModule = ProjectsModule;

document.addEventListener('DOMContentLoaded', () => {
  if (window.ProjectsModule) window.ProjectsModule.boot();
});
