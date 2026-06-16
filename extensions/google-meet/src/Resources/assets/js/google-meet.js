'use strict';

const GoogleMeetModule = (() => {
  const state = {
    connected: false,
    selectedCalendarId: null,
    timezone: 'Europe/Paris',
    page: 1,
    perPage: 20,
    search: '',
    from: '',
    to: '',
    meetings: [],
    calendars: [],
    attendeeEmails: [],
    editingMeeting: null,
    loadingMeetings: false,
    debounceTimer: null,
    googleCalendarInstalled: false,
    googleCalendarTargetUrl: '',
  };

  function t(path, fallback = '', replacements = {}) {
    const source = window.GMEET_I18N || {};
    const value = String(path || '').split('.').reduce((carry, segment) => (
      carry && Object.prototype.hasOwnProperty.call(carry, segment) ? carry[segment] : undefined
    ), source);

    let text = typeof value === 'string' ? value : fallback;
    Object.entries(replacements).forEach(([key, replacement]) => {
      text = text.split(`:${key}`).join(String(replacement));
    });

    return text;
  }

  function boot(bootstrap = {}) {
    state.connected = !!bootstrap.connected;
    state.selectedCalendarId = bootstrap.selectedCalendarId || null;
    state.timezone = bootstrap.timezone || 'Europe/Paris';
    state.googleCalendarInstalled = !!bootstrap.googleCalendarInstalled;
    state.googleCalendarTargetUrl = bootstrap.googleCalendarTargetUrl || '';

    bindActions();

    if (!state.connected) {
      return;
    }

    loadStats();
    loadCalendars(true);
    loadMeetings(true);
  }

  function bindActions() {
    bindAttendeesInput();

    const searchInput = document.getElementById('gmSearchInput');
    searchInput?.addEventListener('input', () => {
      clearTimeout(state.debounceTimer);
      state.debounceTimer = setTimeout(() => {
        state.search = searchInput.value.trim();
        state.page = 1;
        loadMeetings(false);
      }, 300);
    });

    document.getElementById('gmFromDate')?.addEventListener('change', (e) => {
      state.from = e.target.value || '';
      state.page = 1;
      loadMeetings(false);
    });

    document.getElementById('gmToDate')?.addEventListener('change', (e) => {
      state.to = e.target.value || '';
      state.page = 1;
      loadMeetings(false);
    });

    document.getElementById('gmResetFilters')?.addEventListener('click', () => {
      state.search = '';
      state.from = '';
      state.to = '';
      state.page = 1;

      const search = document.getElementById('gmSearchInput');
      const from = document.getElementById('gmFromDate');
      const to = document.getElementById('gmToDate');
      if (search) search.value = '';
      if (from) from.value = '';
      if (to) to.value = '';

      loadMeetings(false);
    });

    document.getElementById('gmSyncBtn')?.addEventListener('click', syncNow);
    document.getElementById('gmDisconnectBtn')?.addEventListener('click', disconnect);

    document.getElementById('gmCreateMeetingBtn')?.addEventListener('click', () => {
      resetMeetingForm();
      state.editingMeeting = null;
      setModalTitle(t('modal.create_meeting', 'Nouvelle réunion'));
    });

    document.getElementById('gmSaveMeetingBtn')?.addEventListener('click', saveMeeting);

    document.getElementById('gmMeetingsTableBody')?.addEventListener('click', (e) => {
      const editBtn = e.target.closest('[data-gm-edit]');
      if (editBtn) {
        const idx = parseInt(editBtn.dataset.gmEdit, 10);
        if (!Number.isNaN(idx)) {
          editMeeting(idx);
        }
        return;
      }

      const delBtn = e.target.closest('[data-gm-delete]');
      if (delBtn) {
        const idx = parseInt(delBtn.dataset.gmDelete, 10);
        if (!Number.isNaN(idx)) {
          deleteMeeting(idx);
        }
      }
    });
  }

  async function loadCalendars(refresh = false) {
    const { ok, data } = await Http.get(window.GMEET_ROUTES.calendarsData, { refresh: refresh ? 1 : 0 });

    if (!ok || !data.success) {
      Toast.error(t('common.error', 'Erreur'), data.message || t('errors.load_calendars', 'Impossible de charger les calendriers.'));
      return;
    }

    state.calendars = data.data || [];

    if (!state.selectedCalendarId) {
      const selected = state.calendars.find((c) => c.is_selected) || state.calendars.find((c) => c.is_primary) || state.calendars[0];
      state.selectedCalendarId = selected ? selected.calendar_id : null;
    }

    renderCalendars();
  }

  function renderCalendars() {
    const wrap = document.getElementById('gmCalendarsList');
    if (!wrap) return;

    if (!state.calendars.length) {
      wrap.innerHTML = `
        <div class="table-empty" style="padding:24px 12px;">
          <div class="table-empty-icon"><i class="fas fa-calendar-xmark"></i></div>
          <h3>${esc(t('calendars.no_calendars_title', 'Aucun calendrier'))}</h3>
          <p>${esc(t('calendars.no_calendars_desc', 'Lancez une synchronisation après connexion.'))}</p>
        </div>`;
      return;
    }

    wrap.innerHTML = state.calendars.map((calendar) => {
      const active = state.selectedCalendarId === calendar.calendar_id;
      const badge = calendar.is_primary ? `<span class="nav-badge" style="margin-left:8px;">${esc(t('calendars.primary', 'Principal'))}</span>` : '';

      return `
        <button class="gm-calendar-item ${active ? 'active' : ''}" data-calendar-id="${esc(calendar.calendar_id)}" type="button">
          <span class="gm-calendar-color" style="background:${esc(calendar.background_color || '#2563eb')};"></span>
          <span class="gm-calendar-name">${esc(calendar.summary || calendar.calendar_id)}</span>
          ${badge}
        </button>`;
    }).join('');

    wrap.querySelectorAll('[data-calendar-id]').forEach((btn) => {
      btn.addEventListener('click', () => selectCalendar(btn.dataset.calendarId));
    });
  }

  async function selectCalendar(calendarId) {
    if (!calendarId || calendarId === state.selectedCalendarId) return;

    const { ok, data } = await Http.post(window.GMEET_ROUTES.selectCalendar, { calendar_id: calendarId });

    if (!ok || !data.success) {
      Toast.error(t('common.error', 'Erreur'), data.message || t('errors.select_calendar', 'Impossible de sélectionner ce calendrier.'));
      return;
    }

    state.selectedCalendarId = calendarId;
    renderCalendars();
    state.page = 1;

    Toast.success(t('common.success', 'Succès'), t('success.calendar_selected_short', 'Calendrier sélectionné.'));
    loadMeetings(true);
  }

  async function loadMeetings(refresh = false) {
    if (state.loadingMeetings) return;
    state.loadingMeetings = true;

    const tbody = document.getElementById('gmMeetingsTableBody');
    if (tbody) {
      tbody.innerHTML = skeletonRows(5, 6);
    }

    const { ok, data } = await Http.get(window.GMEET_ROUTES.meetingsData, {
      calendar_id: state.selectedCalendarId || '',
      search: state.search,
      from: state.from,
      to: state.to,
      per_page: state.perPage,
      page: state.page,
      refresh: refresh ? 1 : 0,
    });

    state.loadingMeetings = false;

    if (!ok || !data.success) {
      Toast.error(t('common.error', 'Erreur'), data.message || t('errors.load_meetings', 'Impossible de charger les réunions.'));
      if (tbody) tbody.innerHTML = emptyRow(t('errors.load_meetings', 'Impossible de charger les réunions.'));
      return;
    }

    state.meetings = data.data || [];

    renderMeetings();
    renderPagination(data);

    const count = document.getElementById('gmCount');
    if (count) count.textContent = t('table.count_results', ':count résultat(s)', { count: data.total || 0 });

    const lastSync = document.getElementById('gmLastSyncLabel');
    if (refresh && lastSync) {
      lastSync.textContent = new Date().toLocaleString();
    }
  }

  function renderMeetings() {
    const tbody = document.getElementById('gmMeetingsTableBody');
    if (!tbody) return;

    if (!state.meetings.length) {
      tbody.innerHTML = emptyRow(t('table.empty_filtered', 'Aucune réunion trouvée pour les filtres sélectionnés.'));
      return;
    }

    tbody.innerHTML = state.meetings.map((meeting, idx) => {
      const statusBadge = statusToBadge(meeting.status || 'confirmed');
      const calendarName = calendarLabel(meeting.calendar_id);
      const calendarAppUrl = buildCalendarAppUrl(meeting);
      const meetBtn = meeting.meet_link
        ? `<a href="${esc(meeting.meet_link)}" target="_blank" rel="noopener" class="btn-icon" title="${esc(t('tooltips.join_meet', 'Rejoindre Meet'))}"><i class="fas fa-video"></i></a>`
        : '';

      return `
        <tr>
          <td>
            <div style="font-weight:var(--fw-medium);display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
              ${esc(meeting.summary || t('common.no_title', '(Sans titre)'))}
            </div>
            <div style="font-size:12px;color:var(--c-ink-40);display:flex;flex-wrap:wrap;gap:8px;">
              ${meeting.organizer_email ? `<span><i class="fas fa-user"></i> ${esc(meeting.organizer_email)}</span>` : ''}
              ${meeting.meet_link ? `<span class="badge badge-sent">${esc(t('badges.meet_link', 'Lien Meet'))}</span>` : `<span class="badge badge-draft">${esc(t('badges.no_link', 'Sans lien'))}</span>`}
            </div>
          </td>
          <td>${esc(calendarName)}</td>
          <td>${esc(meeting.start_display || '-')}</td>
          <td>${esc(meeting.end_display || '-')}</td>
          <td>${statusBadge}</td>
          <td>
            <div class="row-actions" style="justify-content:flex-end;padding-right:4px;opacity:1;">
              ${meetBtn}
              <a href="${esc(calendarAppUrl)}" class="btn-icon" title="${esc(state.googleCalendarInstalled ? t('tooltips.open_calendar_module', 'Ouvrir dans notre module Google Calendar') : t('tooltips.install_calendar', 'Installer Google Calendar depuis Marketplace'))}">
                <i class="fas fa-calendar-days"></i>
              </a>
              <button class="btn-icon" data-gm-edit="${idx}" title="${esc(t('actions.edit', 'Modifier'))}"><i class="fas fa-pen"></i></button>
              <button class="btn-icon danger" data-gm-delete="${idx}" title="${esc(t('actions.delete', 'Supprimer'))}"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  function renderPagination(payload) {
    const wrap = document.getElementById('gmPaginationControls');
    const info = document.getElementById('gmPaginationInfo');
    if (!wrap) return;

    const currentPage = payload.current_page || 1;
    const lastPage = payload.last_page || 1;

    if (info) {
      info.textContent = t('table.pagination_showing', 'Affichage :from à :to sur :total réunion(s)', {
        from: payload.from || 0,
        to: payload.to || 0,
        total: payload.total || 0,
      });
    }

    const pages = [];
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(lastPage, currentPage + 2);

    for (let i = start; i <= end; i += 1) {
      pages.push(i);
    }

    wrap.innerHTML = `
      <button class="page-btn" ${currentPage <= 1 ? 'disabled' : ''} data-gm-page="${currentPage - 1}">
        <i class="fas fa-chevron-left"></i>
      </button>
      ${pages.map((p) => `<button class="page-btn ${p === currentPage ? 'active' : ''}" data-gm-page="${p}">${p}</button>`).join('')}
      <button class="page-btn" ${currentPage >= lastPage ? 'disabled' : ''} data-gm-page="${currentPage + 1}">
        <i class="fas fa-chevron-right"></i>
      </button>`;

    wrap.querySelectorAll('[data-gm-page]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const page = parseInt(btn.dataset.gmPage, 10);
        if (!Number.isNaN(page) && page > 0 && page !== state.page) {
          state.page = page;
          loadMeetings(false);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
    });
  }

  async function loadStats() {
    const { ok, data } = await Http.get(window.GMEET_ROUTES.stats);

    if (!ok || !data.success) {
      return;
    }

    const stats = data.data || {};

    setText('gmStatCalendars', stats.calendars_count || 0);
    setText('gmStatToday', stats.meetings_today || 0);
    setText('gmStatWeek', stats.meetings_next_7_days || 0);
    setText('gmStatMonth', stats.meetings_this_month || 0);
    setText('gmStatLinks', stats.active_meet_links || 0);

    if (stats.last_sync_at) {
      setText('gmLastSyncLabel', new Date(stats.last_sync_at).toLocaleString());
    }
  }

  async function syncNow() {
    const btn = document.getElementById('gmSyncBtn');
    if (btn) CrmForm.setLoading(btn, true);

    const { ok, data } = await Http.post(window.GMEET_ROUTES.sync, {
      calendar_id: state.selectedCalendarId || null,
      from: state.from || null,
      to: state.to || null,
    });

    if (btn) CrmForm.setLoading(btn, false);

    if (!ok || !data.success) {
      Toast.error(t('common.error', 'Erreur'), data.message || t('errors.sync', 'La synchronisation a échoué.'));
      return;
    }

    Toast.success(t('common.success', 'Succès'), data.message || t('success.sync', 'Synchronisation terminée.'));

    await loadCalendars(false);
    await loadStats();
    await loadMeetings(false);
  }

  async function disconnect() {
    Modal.confirm({
      title: t('confirm.disconnect_title', 'Déconnecter Google Meet ?'),
      message: t('confirm.disconnect_message', 'Les tokens OAuth seront supprimés pour ce tenant.'),
      confirmText: t('confirm.disconnect_button', 'Déconnecter'),
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.post(window.GMEET_ROUTES.disconnect, {});
        if (!ok || !data.success) {
          Toast.error(t('common.error', 'Erreur'), data.message || t('errors.disconnect', 'Impossible de déconnecter Google Meet.'));
          return;
        }

        Toast.success(t('success.disconnected_title', 'Déconnecté'), data.message || t('success.disconnected_message', 'Google Meet déconnecté.'));
        setTimeout(() => window.location.reload(), 700);
      },
    });
  }

  function bindAttendeesInput() {
    const input = document.getElementById('gmAttendeesInput');
    const wrap = document.getElementById('gmParticipantsField');
    if (!input || !wrap) return;

    const commitInput = () => {
      const raw = input.value || '';
      const parts = raw.split(/[,;\n]+/).map((v) => v.trim()).filter(Boolean);
      parts.forEach((token) => addAttendeeEmail(token));
      input.value = '';
    };

    input.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === 'Tab' || event.key === ',' || event.key === ';') {
        event.preventDefault();
        commitInput();
      }
    });

    input.addEventListener('blur', () => {
      commitInput();
    });

    input.addEventListener('paste', (event) => {
      const text = event.clipboardData?.getData('text') || '';
      if (!text) return;

      const parts = text.split(/[,;\n\t ]+/).map((v) => v.trim()).filter(Boolean);
      if (parts.length > 1) {
        event.preventDefault();
        parts.forEach((token) => addAttendeeEmail(token));
        input.value = '';
      }
    });

    document.getElementById('gmAttendeesBadges')?.addEventListener('click', (event) => {
      const btn = event.target.closest('[data-gm-tag-remove]');
      if (!btn) return;
      const idx = parseInt(btn.dataset.gmTagRemove, 10);
      if (!Number.isNaN(idx)) {
        removeAttendeeEmail(idx);
      }
    });

    renderAttendeeBadges();
  }

  function normalizeEmailToken(token) {
    let value = String(token || '').trim();
    if (!value) return '';

    if (/<([^>]+)>/.test(value)) {
      value = value.replace(/^.*<([^>]+)>.*$/, '$1').trim();
    }

    value = value.replace(/^['"]|['"]$/g, '').trim();

    return value.toLowerCase();
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function addAttendeeEmail(token) {
    const email = normalizeEmailToken(token);
    if (!email) return false;
    if (!isValidEmail(email)) {
      Toast.error(t('errors.invalid_email_title', 'Email invalide'), t('errors.invalid_email_message', '":email" n’est pas un email valide.', { email }));
      return false;
    }
    if (state.attendeeEmails.includes(email)) {
      return false;
    }

    state.attendeeEmails.push(email);
    renderAttendeeBadges();
    syncAttendeesHiddenInput();
    return true;
  }

  function removeAttendeeEmail(index) {
    if (index < 0 || index >= state.attendeeEmails.length) return;
    state.attendeeEmails.splice(index, 1);
    renderAttendeeBadges();
    syncAttendeesHiddenInput();
  }

  function setAttendeesFromArray(values = []) {
    const cleaned = (Array.isArray(values) ? values : [])
      .map((value) => normalizeEmailToken(value))
      .filter((value, idx, arr) => value && isValidEmail(value) && arr.indexOf(value) === idx);

    state.attendeeEmails = cleaned;
    renderAttendeeBadges();
    syncAttendeesHiddenInput();

    const input = document.getElementById('gmAttendeesInput');
    if (input) {
      input.value = '';
    }
  }

  function syncAttendeesHiddenInput() {
    const hidden = document.getElementById('gmAttendees');
    if (hidden) {
      hidden.value = state.attendeeEmails.join(', ');
    }
  }

  function getAttendeesValue() {
    const input = document.getElementById('gmAttendeesInput');
    if (input && input.value.trim() !== '') {
      input.value.split(/[,;\n]+/).map((v) => v.trim()).filter(Boolean).forEach((token) => addAttendeeEmail(token));
      input.value = '';
    }

    return state.attendeeEmails.join(', ');
  }

  function renderAttendeeBadges() {
    const list = document.getElementById('gmAttendeesBadges');
    if (!list) return;

    if (!state.attendeeEmails.length) {
      list.innerHTML = '';
      return;
    }

    list.innerHTML = state.attendeeEmails
      .map((email, idx) => `
        <span class="gm-tag">
          <span>${esc(email)}</span>
          <button type="button" class="gm-tag-remove" data-gm-tag-remove="${idx}" aria-label="${esc(t('actions.delete', 'Supprimer'))}">&times;</button>
        </span>
      `)
      .join('');
  }

  function editMeeting(index) {
    const meeting = state.meetings[index];
    if (!meeting) return;

    state.editingMeeting = meeting;
    setModalTitle(t('modal.edit_meeting', 'Modifier la réunion'));

    resetMeetingForm();

    setFieldValue('gmMeetingCalendarId', meeting.calendar_id || state.selectedCalendarId || '');
    setFieldValue('gmMeetingEventId', meeting.event_id || '');
    setFieldValue('gmSummary', meeting.summary || '');
    setFieldValue('gmLocation', meeting.location || '');
    setFieldValue('gmVisibility', meeting.visibility || 'default');
    setFieldValue('gmDescription', meeting.description || '');

    if (meeting.start_at) {
      setFieldValue('gmStartAt', toDateTimeLocal(meeting.start_at));
    }

    if (meeting.end_at) {
      setFieldValue('gmEndAt', toDateTimeLocal(meeting.end_at));
    }

    if (Array.isArray(meeting.attendees) && meeting.attendees.length) {
      const emails = meeting.attendees.map((att) => att.email).filter(Boolean);
      setAttendeesFromArray(emails);
    } else {
      setAttendeesFromArray([]);
    }

    setFieldValue('gmCreateMeetLink', '1');
    const createMeetCheckbox = document.getElementById('gmCreateMeetLink');
    if (createMeetCheckbox) {
      createMeetCheckbox.checked = true;
    }

    Modal.open(document.getElementById('gmMeetingModal'));
  }

  async function deleteMeeting(index) {
    const meeting = state.meetings[index];
    if (!meeting) return;

    Modal.confirm({
      title: t('confirm.delete_title', 'Supprimer cette réunion ?'),
      message: t('confirm.delete_message', 'La réunion ":title" sera supprimée de Google Calendar.', {
        title: meeting.summary || t('common.no_title', '(Sans titre)'),
      }),
      confirmText: t('confirm.delete_button', 'Supprimer'),
      type: 'danger',
      onConfirm: async () => {
        const url = `${window.GMEET_ROUTES.meetingsBase}/${encodeURIComponent(meeting.calendar_id)}/${encodeURIComponent(meeting.event_id)}`;
        const { ok, data } = await Http.delete(url);

        if (!ok || !data.success) {
          Toast.error(t('common.error', 'Erreur'), data.message || t('errors.delete', 'Impossible de supprimer la réunion.'));
          return;
        }

        Toast.success(t('success.deleted_title', 'Supprimée'), data.message || t('success.deleted_message', 'Réunion supprimée.'));
        loadMeetings(false);
        loadStats();
      },
    });
  }

  async function saveMeeting() {
    const form = document.getElementById('gmMeetingForm');
    const btn = document.getElementById('gmSaveMeetingBtn');
    if (!form || !btn) return;

    clearFormErrors(form);

    const payload = {
      calendar_id: getFieldValue('gmMeetingCalendarId') || state.selectedCalendarId || '',
      summary: getFieldValue('gmSummary').trim(),
      start_at: getFieldValue('gmStartAt'),
      end_at: getFieldValue('gmEndAt'),
      location: getFieldValue('gmLocation').trim(),
      visibility: getFieldValue('gmVisibility'),
      send_updates: getFieldValue('gmSendUpdates') || 'all',
      attendees: getAttendeesValue(),
      description: getFieldValue('gmDescription').trim(),
      timezone: state.timezone,
      create_meet_link: document.getElementById('gmCreateMeetLink')?.checked ? 1 : 0,
    };

    const validationErrors = validatePayload(payload);
    if (Object.keys(validationErrors).length) {
      showFormErrors(form, validationErrors);
      Toast.error(t('common.validation', 'Validation'), t('errors.validation', 'Merci de corriger les erreurs du formulaire.'));
      return;
    }

    CrmForm.setLoading(btn, true);

    let response;
    if (state.editingMeeting) {
      const url = `${window.GMEET_ROUTES.meetingsBase}/${encodeURIComponent(state.editingMeeting.calendar_id)}/${encodeURIComponent(state.editingMeeting.event_id)}`;
      response = await Http.put(url, payload);
    } else {
      response = await Http.post(window.GMEET_ROUTES.meetingsStore, payload);
    }

    CrmForm.setLoading(btn, false);

    if (!response.ok) {
      if (response.status === 422 && response.data?.errors) {
        showFormErrors(form, response.data.errors);
      }

      Toast.error(t('common.error', 'Erreur'), response.data?.message || t('errors.save', 'Impossible d’enregistrer la réunion.'));
      return;
    }

    Toast.success(t('common.success', 'Succès'), response.data?.message || t('success.saved', 'Réunion enregistrée.'));
    Modal.close(document.getElementById('gmMeetingModal'));

    state.editingMeeting = null;
    loadMeetings(false);
    loadStats();
  }

  function validatePayload(payload) {
    const errors = {};

    if (!payload.calendar_id) {
      errors.calendar_id = [t('validation.calendar', 'Veuillez sélectionner un calendrier.')];
    }

    if (!payload.summary) {
      errors.summary = [t('validation.title_required', 'Le titre est obligatoire.')];
    }

    if (!payload.start_at) {
      errors.start_at = [t('validation.start_required', 'La date de début est obligatoire.')];
    }

    if (!payload.end_at) {
      errors.end_at = [t('validation.end_required', 'La date de fin est obligatoire.')];
    }

    if (payload.start_at && payload.end_at) {
      const start = new Date(payload.start_at);
      const end = new Date(payload.end_at);
      if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) {
        errors.end_at = [t('validation.end_after_start', 'La date de fin doit être après la date de début.')];
      }
    }

    if (payload.attendees) {
      const emails = payload.attendees.split(',').map((v) => v.trim()).filter(Boolean);
      const invalid = emails.find((email) => !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email));
      if (invalid) {
        errors.attendees = [t('validation.attendees_invalid', 'Un ou plusieurs e-mails participants sont invalides.')];
      }
    }

    return errors;
  }

  function resetMeetingForm() {
    const form = document.getElementById('gmMeetingForm');
    if (!form) return;

    clearFormErrors(form);
    form.reset();

    setFieldValue('gmMeetingCalendarId', state.selectedCalendarId || '');
    setFieldValue('gmMeetingEventId', '');
    setFieldValue('gmVisibility', 'default');
    setFieldValue('gmSendUpdates', 'all');

    const createMeetCheckbox = document.getElementById('gmCreateMeetLink');
    if (createMeetCheckbox) {
      createMeetCheckbox.checked = true;
    }

    const now = new Date();
    const plusHour = new Date(now.getTime() + 60 * 60 * 1000);

    setAttendeesFromArray([]);
    setFieldValue('gmStartAt', toDateTimeLocal(now));
    setFieldValue('gmEndAt', toDateTimeLocal(plusHour));
  }

  function setModalTitle(text) {
    setText('gmMeetingModalTitle', text);
  }

  function statusToBadge(status) {
    const map = {
      confirmed: { cls: 'badge-paid', label: t('status.confirmed', 'Confirmée') },
      tentative: { cls: 'badge-sent', label: t('status.tentative', 'Tentative') },
      cancelled: { cls: 'badge-cancelled', label: t('status.cancelled', 'Annulée') },
    };
    const cfg = map[status] || { cls: 'badge-draft', label: status || t('status.unknown', 'Inconnu') };
    return `<span class="badge ${cfg.cls}">${esc(cfg.label)}</span>`;
  }

  function calendarLabel(calendarId) {
    const found = state.calendars.find((c) => c.calendar_id === calendarId);
    return found ? (found.summary || calendarId) : calendarId;
  }

  function buildCalendarAppUrl(meeting) {
    const base = String(state.googleCalendarTargetUrl || '').trim();
    if (!base) {
      return '#';
    }

    const eventId = meeting?.event_id ? String(meeting.event_id) : '';
    if (!eventId) {
      return base;
    }

    const separator = base.includes('?') ? '&' : '?';
    return `${base}${separator}event_id=${encodeURIComponent(eventId)}`;
  }

  function skeletonRows(count, cols) {
    return Array.from({ length: count }, () => `<tr>${Array.from({ length: cols }, () => '<td><div class="skeleton" style="height:13px;"></div></td>').join('')}</tr>`).join('');
  }

  function emptyRow(message) {
    return `<tr><td colspan="6"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-video"></i></div><h3>${esc(t('common.no_data_title', 'Aucune donnée'))}</h3><p>${esc(message)}</p></div></td></tr>`;
  }

  function toDateTimeLocal(value) {
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    const offsetMs = date.getTimezoneOffset() * 60 * 1000;
    const local = new Date(date.getTime() - offsetMs);
    return local.toISOString().slice(0, 16);
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
  }

  function setFieldValue(id, value) {
    const el = document.getElementById(id);
    if (!el) return;

    if (el.type === 'checkbox') {
      el.checked = Boolean(value);
      return;
    }

    el.value = value;
  }

  function getFieldValue(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  function clearFormErrors(form) {
    form.querySelectorAll('.form-error').forEach((el) => el.remove());
    form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    document.getElementById('gmParticipantsField')?.classList.remove('is-invalid');
  }

  function showFormErrors(form, errors) {
    Object.entries(errors).forEach(([field, messages]) => {
      let input = form.querySelector(`[name="${field}"]`);
      if (!input || input.type === 'hidden') {
        input = mapFieldAlias(form, field) || input;
      }
      if (!input) return;

      input.classList.add('is-invalid');
      if (field === 'attendees') {
        document.getElementById('gmParticipantsField')?.classList.add('is-invalid');
      }

      const error = document.createElement('div');
      error.className = 'form-error';
      error.textContent = Array.isArray(messages) ? messages[0] : messages;
      const container = field === 'attendees'
        ? (document.getElementById('gmParticipantsField')?.parentNode || input.parentNode)
        : input.parentNode;
      container.appendChild(error);
    });
  }

  function mapFieldAlias(form, field) {
    if (field === 'calendar_id') {
      return form.querySelector('#gmSummary');
    }
    if (field === 'attendees') {
      return form.querySelector('#gmAttendeesInput');
    }
    return null;
  }

  return {
    boot,
  };
})();

window.GoogleMeetModule = GoogleMeetModule;
