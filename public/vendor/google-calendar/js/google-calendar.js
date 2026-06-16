'use strict';

const GoogleCalendarModule = (() => {
  const VIEW_MODES = ['month', 'week', 'day', 'year', 'list'];

  const state = {
    connected: false,
    selectedCalendarId: null,
    timezone: 'UTC',
    page: 1,
    perPage: 20,
    search: '',
    from: '',
    to: '',
    includeHolidays: true,
    events: [],
    calendarEvents: [],
    calendars: [],
    editingEvent: null,
    detailEvent: null,
    loadingEvents: false,
    debounceTimer: null,
    viewMode: 'month',
    anchorDate: new Date(),
    loadToken: 0,
    locale: 'fr-FR',
    i18n: {},
    clientsInstalled: false,
    prefill: {},
  };

  function boot(bootstrap = {}) {
    state.connected = !!bootstrap.connected;
    state.selectedCalendarId = bootstrap.selectedCalendarId || null;
    state.timezone = bootstrap.timezone || 'UTC';
    state.includeHolidays = bootstrap.includeHolidays !== false;
    state.viewMode = VIEW_MODES.includes(bootstrap.viewMode) ? bootstrap.viewMode : 'month';
    state.locale = bootstrap.locale || 'fr-FR';
    state.i18n = (bootstrap.i18n && typeof bootstrap.i18n === 'object') ? bootstrap.i18n : {};
    state.clientsInstalled = !!bootstrap.clientsInstalled;
    state.prefill = (bootstrap.prefill && typeof bootstrap.prefill === 'object') ? bootstrap.prefill : {};

    bindActions();
    updateViewUi();

    const includeHolidays = document.getElementById('gcIncludeHolidays');
    if (includeHolidays) {
      includeHolidays.checked = state.includeHolidays;
    }

    if (!state.connected) {
      return;
    }

    loadStats();
    loadCalendars(true);
    loadEvents(true);
  }

  function bindActions() {
    const searchInput = document.getElementById('gcSearchInput');
    searchInput?.addEventListener('input', () => {
      clearTimeout(state.debounceTimer);
      state.debounceTimer = setTimeout(() => {
        state.search = searchInput.value.trim();
        state.page = 1;
        loadEvents(false);
      }, 300);
    });

    document.getElementById('gcFromDate')?.addEventListener('change', (e) => {
      state.from = e.target.value || '';
      state.page = 1;
      loadEvents(false);
    });

    document.getElementById('gcToDate')?.addEventListener('change', (e) => {
      state.to = e.target.value || '';
      state.page = 1;
      loadEvents(false);
    });

    document.getElementById('gcIncludeHolidays')?.addEventListener('change', (e) => {
      state.includeHolidays = !!e.target.checked;
      state.page = 1;
      loadEvents(false);
    });

    document.getElementById('gcResetFilters')?.addEventListener('click', () => {
      state.search = '';
      state.from = '';
      state.to = '';
      state.page = 1;
      state.anchorDate = new Date();
      state.includeHolidays = true;

      const search = document.getElementById('gcSearchInput');
      const from = document.getElementById('gcFromDate');
      const to = document.getElementById('gcToDate');
      const includeHolidays = document.getElementById('gcIncludeHolidays');

      if (search) search.value = '';
      if (from) from.value = '';
      if (to) to.value = '';
      if (includeHolidays) includeHolidays.checked = true;

      updatePeriodLabel();
      loadEvents(false);
    });

    document.getElementById('gcViewModeSwitcher')?.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-gc-view-mode]');
      if (!btn) {
        return;
      }

      const mode = btn.dataset.gcViewMode;
      if (!mode || !VIEW_MODES.includes(mode)) {
        return;
      }

      setViewMode(mode, true);
    });

    document.getElementById('gcPrevPeriod')?.addEventListener('click', () => {
      shiftAnchorDate(-1);
      loadEvents(false);
    });

    document.getElementById('gcNextPeriod')?.addEventListener('click', () => {
      shiftAnchorDate(1);
      loadEvents(false);
    });

    document.getElementById('gcTodayPeriod')?.addEventListener('click', () => {
      state.anchorDate = new Date();
      updatePeriodLabel();
      loadEvents(false);
    });

    document.getElementById('gcSyncBtn')?.addEventListener('click', syncNow);
    document.getElementById('gcDisconnectBtn')?.addEventListener('click', disconnect);

    document.getElementById('gcCreateEventBtn')?.addEventListener('click', () => {
      resetEventForm();
      state.editingEvent = null;
      setModalTitle(t('modal_create', 'Créer un événement'));
    });

    document.getElementById('gcSaveEventBtn')?.addEventListener('click', saveEvent);

    document.getElementById('gcDetailEditBtn')?.addEventListener('click', openDetailEditMode);
    document.getElementById('gcDetailDeleteBtn')?.addEventListener('click', deleteDetailEvent);
    document.getElementById('gcDetailOpenGoogleBtn')?.addEventListener('click', openDetailGoogleLink);
    document.getElementById('gcEventsTableBody')?.addEventListener('click', (e) => {
      const detailBtn = e.target.closest('[data-gc-detail]');
      if (detailBtn) {
        const idx = parseInt(detailBtn.dataset.gcDetail, 10);
        if (!Number.isNaN(idx)) {
          openEventDetails(state.events[idx]);
        }
        return;
      }

      const editBtn = e.target.closest('[data-gc-edit]');
      if (editBtn) {
        const idx = parseInt(editBtn.dataset.gcEdit, 10);
        if (!Number.isNaN(idx)) {
          editEvent(idx);
        }
        return;
      }

      const delBtn = e.target.closest('[data-gc-delete]');
      if (delBtn) {
        const idx = parseInt(delBtn.dataset.gcDelete, 10);
        if (!Number.isNaN(idx)) {
          deleteEvent(idx);
        }
      }
    });

    document.getElementById('gcCalendarModeWrap')?.addEventListener('click', (e) => {
      const eventCard = e.target.closest('[data-gc-event-id]');
      if (eventCard) {
        openEventDetailsById(eventCard.dataset.gcEventId);
        return;
      }

      const jumpMonth = e.target.closest('[data-gc-jump-month]');
      if (jumpMonth) {
        const month = parseInt(jumpMonth.dataset.gcJumpMonth || '', 10);
        const year = parseInt(jumpMonth.dataset.gcJumpYear || '', 10);
        if (!Number.isNaN(month) && !Number.isNaN(year)) {
          state.anchorDate = new Date(year, month, 1, 12, 0, 0, 0);
          setViewMode('month', true);
        }
      }
    });
  }

  function setViewMode(mode, reload = true) {
    if (!VIEW_MODES.includes(mode)) {
      return;
    }

    if (state.viewMode === mode) {
      return;
    }

    state.viewMode = mode;
    state.page = 1;
    updateViewUi();

    if (reload) {
      loadEvents(false);
    }
  }

  function updateViewUi() {
    document.querySelectorAll('[data-gc-view-mode]').forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.gcViewMode === state.viewMode);
    });

    const listWrap = document.getElementById('gcListWrap');
    const modeWrap = document.getElementById('gcCalendarModeWrap');
    const periodNav = document.getElementById('gcPeriodNav');
    const listOnly = document.querySelectorAll('.gc-list-only');

    const isList = state.viewMode === 'list';

    if (listWrap) {
      listWrap.hidden = !isList;
    }

    if (modeWrap) {
      modeWrap.hidden = isList;
    }

    if (periodNav) {
      periodNav.classList.toggle('is-hidden', isList);
    }

    listOnly.forEach((el) => {
      el.classList.toggle('is-hidden', !isList);
    });

    updatePeriodLabel();
  }

  function updatePeriodLabel() {
    const label = document.getElementById('gcPeriodLabel');
    if (!label) {
      return;
    }

    if (state.viewMode === 'list') {
      label.textContent = '';
      return;
    }

    const range = getCurrentRange();
    const start = range.start;
    const end = range.end;

    switch (state.viewMode) {
      case 'month':
        label.textContent = new Intl.DateTimeFormat(state.locale, { month: 'long', year: 'numeric' }).format(start);
        break;
      case 'week':
        label.textContent = `${new Intl.DateTimeFormat(state.locale, { day: '2-digit', month: 'short', year: 'numeric' }).format(start)} - ${new Intl.DateTimeFormat(state.locale, { day: '2-digit', month: 'short', year: 'numeric' }).format(end)}`;
        break;
      case 'day':
        label.textContent = new Intl.DateTimeFormat(state.locale, { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }).format(start);
        break;
      case 'year':
        label.textContent = String(start.getFullYear());
        break;
      default:
        label.textContent = '';
    }
  }

  function shiftAnchorDate(direction) {
    const next = new Date(state.anchorDate);

    switch (state.viewMode) {
      case 'month':
        next.setMonth(next.getMonth() + direction);
        break;
      case 'week':
        next.setDate(next.getDate() + (7 * direction));
        break;
      case 'day':
        next.setDate(next.getDate() + direction);
        break;
      case 'year':
        next.setFullYear(next.getFullYear() + direction);
        break;
      default:
        break;
    }

    state.anchorDate = next;
    updatePeriodLabel();
  }

  async function loadCalendars(refresh = false) {
    const { ok, data } = await Http.get(window.GCAL_ROUTES.calendarsData, { refresh: refresh ? 1 : 0 });

    if (!ok || !data.success) {
      Toast.error(t('error', 'Erreur'), data.message || t('load_calendars_error', 'Impossible de charger les calendriers.'));
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
    const wrap = document.getElementById('gcCalendarsList');
    if (!wrap) return;

    if (!state.calendars.length) {
      wrap.innerHTML = `
        <div class="table-empty" style="padding:24px 12px;">
          <div class="table-empty-icon"><i class="fas fa-calendar-xmark"></i></div>
          <h3>${esc(t('no_calendars_title', 'Aucun calendrier'))}</h3>
          <p>${esc(t('no_calendars_desc', 'Lancez une synchronisation après connexion Google Calendar.'))}</p>
        </div>`;
      return;
    }

    wrap.innerHTML = state.calendars.map((calendar) => {
      const active = state.selectedCalendarId === calendar.calendar_id;
      const badge = calendar.is_primary ? `<span class="nav-badge" style="margin-left:8px;">${esc(t('primary', 'Principal'))}</span>` : '';

      return `
        <button class="gc-calendar-item ${active ? 'active' : ''}" data-calendar-id="${esc(calendar.calendar_id)}" type="button">
          <span class="gc-calendar-color" style="background:${esc(calendar.background_color || '#2563eb')};"></span>
          <span class="gc-calendar-name">${esc(calendar.summary || calendar.calendar_id)}</span>
          ${badge}
        </button>`;
    }).join('');

    wrap.querySelectorAll('[data-calendar-id]').forEach((btn) => {
      btn.addEventListener('click', () => selectCalendar(btn.dataset.calendarId));
    });
  }

  async function selectCalendar(calendarId) {
    if (!calendarId || calendarId === state.selectedCalendarId) return;

    const { ok, data } = await Http.post(window.GCAL_ROUTES.selectCalendar, { calendar_id: calendarId });

    if (!ok || !data.success) {
      Toast.error(t('error', 'Erreur'), data.message || t('select_calendar_error', 'Impossible de sélectionner ce calendrier.'));
      return;
    }

    state.selectedCalendarId = calendarId;
    renderCalendars();
    state.page = 1;

    Toast.success(t('success', 'Succès'), t('calendar_selected', 'Calendrier sélectionné.'));
    loadEvents(true);
  }

  async function loadEvents(refresh = false) {
    const token = ++state.loadToken;
    state.loadingEvents = true;

    const tbody = document.getElementById('gcEventsTableBody');
    const modeWrap = document.getElementById('gcCalendarModeWrap');

    if (state.viewMode === 'list') {
      if (tbody) {
        tbody.innerHTML = skeletonRows(5, 6);
      }

      const { ok, data } = await Http.get(window.GCAL_ROUTES.eventsData, {
        calendar_id: state.selectedCalendarId || '',
        search: state.search,
        from: state.from,
        to: state.to,
        per_page: state.perPage,
        page: state.page,
        refresh: refresh ? 1 : 0,
        include_holidays: state.includeHolidays ? 1 : 0,
      });

      if (token !== state.loadToken) {
        return;
      }

      state.loadingEvents = false;

      if (!ok || !data.success) {
        Toast.error(t('error', 'Erreur'), data.message || t('load_events_error', 'Impossible de charger les événements.'));
        if (tbody) tbody.innerHTML = emptyRow(t('load_events_error', 'Impossible de charger les événements.'));
        return;
      }

      state.events = data.data || [];
      renderEvents();
      renderPagination(data);
      setText('gcCount', t('count_results', ':count résultat(s)', { count: data.total || 0 }));

      const lastSync = document.getElementById('gcLastSyncLabel');
      if (refresh && lastSync) {
        lastSync.textContent = new Date().toLocaleString();
      }

      return;
    }

    if (modeWrap) {
      modeWrap.innerHTML = modeSkeleton();
    }

    clearPagination();
    updatePeriodLabel();

    const range = getCurrentRange();
    const rangeResult = await fetchEventsRange(range, refresh, token);

    if (token !== state.loadToken || rangeResult.cancelled) {
      return;
    }

    state.loadingEvents = false;

    if (!rangeResult.success) {
      Toast.error(t('error', 'Erreur'), rangeResult.message || t('load_events_error', 'Impossible de charger les événements.'));
      if (modeWrap) {
        modeWrap.innerHTML = modeError(rangeResult.message || t('load_events_error', 'Impossible de charger les événements.'));
      }
      return;
    }

    state.calendarEvents = rangeResult.events;
    renderCalendarMode(range);
    setText('gcCount', t('count_results', ':count résultat(s)', { count: state.calendarEvents.length }));

    const lastSync = document.getElementById('gcLastSyncLabel');
    if (refresh && lastSync) {
      lastSync.textContent = new Date().toLocaleString();
    }
  }

  async function fetchEventsRange(range, refresh, token) {
    const allEvents = [];
    let page = 1;
    let lastPage = 1;
    let firstError = null;

    do {
      if (token !== state.loadToken) {
        return { cancelled: true };
      }

      const response = await Http.get(window.GCAL_ROUTES.eventsData, {
        calendar_id: state.selectedCalendarId || '',
        search: state.search,
        from: range.startDate,
        to: range.endDate,
        per_page: 100,
        page,
        refresh: refresh && page === 1 ? 1 : 0,
        include_holidays: state.includeHolidays ? 1 : 0,
      });

      if (!response.ok || !response.data?.success) {
        firstError = response.data?.message || t('load_events_error', 'Impossible de charger les événements.');
        break;
      }

      const payload = response.data;
      const pageItems = Array.isArray(payload.data) ? payload.data : [];
      allEvents.push(...pageItems);

      lastPage = Math.max(1, parseInt(payload.last_page || 1, 10));
      page += 1;
    } while (page <= lastPage);

    if (firstError) {
      return { success: false, message: firstError, events: [] };
    }

    allEvents.sort((a, b) => {
      const at = safeDate(a.start_at).getTime();
      const bt = safeDate(b.start_at).getTime();
      return at - bt;
    });

    return { success: true, events: allEvents };
  }

  function renderCalendarMode(range) {
    const wrap = document.getElementById('gcCalendarModeWrap');
    if (!wrap) {
      return;
    }

    if (!state.calendarEvents.length) {
      wrap.innerHTML = modeEmpty();
      return;
    }

    switch (state.viewMode) {
      case 'month':
        wrap.innerHTML = renderMonthMode(range);
        break;
      case 'week':
        wrap.innerHTML = renderWeekMode(range);
        break;
      case 'day':
        wrap.innerHTML = renderDayMode(range);
        break;
      case 'year':
        wrap.innerHTML = renderYearMode(range);
        break;
      default:
        wrap.innerHTML = modeEmpty();
    }
  }

  function renderMonthMode(range) {
    const monthStart = startOfMonth(range.start);
    const monthEnd = endOfMonth(range.start);
    const gridStart = startOfWeek(monthStart);
    const gridEnd = endOfWeek(monthEnd);
    const eventsByDay = mapEventsByDay(state.calendarEvents, gridStart, gridEnd);

    let pointer = new Date(gridStart);
    const cells = [];

    while (pointer <= gridEnd) {
      const dayKey = toDateKey(pointer);
      const events = (eventsByDay.get(dayKey) || []).slice().sort(sortByStart);
      const isCurrentMonth = pointer.getMonth() === monthStart.getMonth();
      const isToday = isSameDate(pointer, new Date());
      const visibleEvents = events.slice(0, 3);
      const extraCount = Math.max(0, events.length - visibleEvents.length);

      cells.push(`
        <div class="gc-month-cell ${isCurrentMonth ? '' : 'is-muted'} ${isToday ? 'is-today' : ''}">
          <div class="gc-month-day-head">
            <span>${esc(String(pointer.getDate()))}</span>
          </div>
          <div class="gc-month-events">
            ${visibleEvents.map((event) => renderEventCard(event, true)).join('')}
            ${extraCount > 0 ? `<div class="gc-more-events">+${extraCount} ${esc(t('more', 'de plus'))}</div>` : ''}
          </div>
        </div>
      `);

      pointer = addDays(pointer, 1);
    }

    return `
      <div class="gc-month-grid">
        <div class="gc-month-weekdays">${getWeekdayLabels().map((day) => `<div>${esc(day)}</div>`).join('')}</div>
        <div class="gc-month-cells">${cells.join('')}</div>
      </div>
    `;
  }

  function renderWeekMode(range) {
    const weekStart = startOfWeek(range.start);
    const weekEnd = endOfWeek(range.start);
    const eventsByDay = mapEventsByDay(state.calendarEvents, weekStart, weekEnd);

    const days = [];
    for (let i = 0; i < 7; i += 1) {
      const date = addDays(weekStart, i);
      const dayKey = toDateKey(date);
      const events = (eventsByDay.get(dayKey) || []).slice().sort(sortByStart);
      const isToday = isSameDate(date, new Date());

      days.push(`
        <div class="gc-week-day ${isToday ? 'is-today' : ''}">
          <div class="gc-week-day-head">
            <span class="gc-week-day-name">${esc(new Intl.DateTimeFormat(state.locale, { weekday: 'short' }).format(date))}</span>
            <span class="gc-week-day-date">${esc(new Intl.DateTimeFormat(state.locale, { day: '2-digit', month: 'short' }).format(date))}</span>
          </div>
          <div class="gc-week-events">
            ${events.length ? events.map((event) => renderEventCard(event)).join('') : `<div class="gc-no-events">${esc(t('no_events', 'Aucun événement'))}</div>`}
          </div>
        </div>
      `);
    }

    return `<div class="gc-week-grid">${days.join('')}</div>`;
  }

  function renderDayMode(range) {
    const day = startOfDay(range.start);
    const dayKey = toDateKey(day);
    const eventsByDay = mapEventsByDay(state.calendarEvents, day, day);
    const dayEvents = (eventsByDay.get(dayKey) || []).slice().sort(sortByStart);

    const allDay = dayEvents.filter((event) => !!event.all_day);
    const timed = dayEvents.filter((event) => !event.all_day);

    const hourlyRows = [];
    for (let hour = 0; hour < 24; hour += 1) {
      const rowEvents = timed.filter((event) => safeDate(event.start_at).getHours() === hour);
      hourlyRows.push(`
        <div class="gc-day-hour-row">
          <div class="gc-day-hour-label">${String(hour).padStart(2, '0')}:00</div>
          <div class="gc-day-hour-events">
            ${rowEvents.length ? rowEvents.map((event) => renderEventCard(event)).join('') : '<div class="gc-day-hour-empty"></div>'}
          </div>
        </div>
      `);
    }

    return `
      <div class="gc-day-view">
        <div class="gc-day-head">${esc(new Intl.DateTimeFormat(state.locale, { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }).format(day))}</div>
        ${allDay.length ? `<div class="gc-day-all-day"><div class="gc-day-all-day-title">${esc(t('all_day', 'Toute la journée'))}</div>${allDay.map((event) => renderEventCard(event)).join('')}</div>` : ''}
        <div class="gc-day-hours">${hourlyRows.join('')}</div>
      </div>
    `;
  }

  function renderYearMode(range) {
    const year = range.start.getFullYear();
    const monthBuckets = Array.from({ length: 12 }, () => []);

    state.calendarEvents.forEach((event) => {
      const start = safeDate(event.start_at);
      if (start.getFullYear() !== year) {
        return;
      }
      monthBuckets[start.getMonth()].push(event);
    });

    const cards = monthBuckets.map((events, monthIndex) => {
      events.sort(sortByStart);
      const label = new Intl.DateTimeFormat(state.locale, { month: 'long' }).format(new Date(year, monthIndex, 1));
      const preview = events.slice(0, 3);

      return `
        <div class="gc-year-month-card" data-gc-jump-month="${monthIndex}" data-gc-jump-year="${year}">
          <div class="gc-year-month-head">
            <span class="gc-year-month-label">${esc(label)}</span>
            <span class="gc-year-month-count">${events.length}</span>
          </div>
          <div class="gc-year-month-events">
            ${preview.length ? preview.map((event) => renderEventCard(event, true)).join('') : `<div class="gc-no-events">${esc(t('no_events', 'Aucun événement'))}</div>`}
          </div>
        </div>
      `;
    });

    return `<div class="gc-year-grid">${cards.join('')}</div>`;
  }

  function renderEventCard(event, compact = false) {
    const localId = String(event.id || '');
    const classes = ['gc-event-card'];
    if (compact) classes.push('is-compact');
    if (event.is_holiday) classes.push('is-holiday');
    if (event.client_name) classes.push('is-client-linked');

    const palette = eventPalette(event);
    const timeLabel = eventTimeLabel(event);
    const clientBadge = event.client_name
      ? `<span class="gc-event-client"><i class="fas fa-building"></i> ${esc(event.client_name)}</span>`
      : '';

    return `
      <button type="button" class="${classes.join(' ')}" data-gc-event-id="${esc(localId)}" style="${eventPaletteStyle(palette)}" aria-label="${esc(event.summary || t('no_title', '(Sans titre)'))}">
        <span class="gc-event-indicator" aria-hidden="true"></span>
        <div class="gc-event-main">
          <div class="gc-event-title-row">
            <span class="gc-event-time">${esc(timeLabel)}</span>
            <span class="gc-event-title">${esc(event.summary || t('no_title', '(Sans titre)'))}</span>
          </div>
          ${clientBadge}
          ${event.location && !compact ? `<div class="gc-event-location"><i class="fas fa-location-dot"></i> ${esc(event.location)}</div>` : ''}
        </div>
      </button>
    `;
  }

  function eventTimeLabel(event) {
    if (event.all_day) {
      return t('all_day', 'Toute la journée');
    }

    const start = safeDate(event.start_at);
    const end = safeDate(event.end_at);
    const formatter = new Intl.DateTimeFormat(state.locale, { hour: '2-digit', minute: '2-digit', hour12: false });

    const startText = formatter.format(start);
    const endText = formatter.format(end);

    if (startText === endText) {
      return startText;
    }

    return `${startText}-${endText}`;
  }

  function mapEventsByDay(events, rangeStart, rangeEnd) {
    const output = new Map();

    events.forEach((event) => {
      const span = getEventDaySpan(event);
      if (!span) {
        return;
      }

      let start = span.start;
      let end = span.end;

      if (end < rangeStart || start > rangeEnd) {
        return;
      }

      if (start < rangeStart) start = rangeStart;
      if (end > rangeEnd) end = rangeEnd;

      let cursor = startOfDay(start);
      const limit = startOfDay(end);

      while (cursor <= limit) {
        const key = toDateKey(cursor);
        if (!output.has(key)) {
          output.set(key, []);
        }
        output.get(key).push(event);
        cursor = addDays(cursor, 1);
      }
    });

    return output;
  }

  function getEventDaySpan(event) {
    const start = safeDate(event.start_at);
    const endRaw = safeDate(event.end_at);

    if (Number.isNaN(start.getTime()) || Number.isNaN(endRaw.getTime())) {
      return null;
    }

    const dayStart = startOfDay(start);
    let dayEnd = startOfDay(endRaw);

    if (event.all_day) {
      dayEnd = addDays(dayEnd, -1);
    } else if (endRaw <= start) {
      dayEnd = dayStart;
    }

    if (dayEnd < dayStart) {
      dayEnd = dayStart;
    }

    return { start: dayStart, end: dayEnd };
  }

  function renderEvents() {
    const tbody = document.getElementById('gcEventsTableBody');
    if (!tbody) return;

    if (!state.events.length) {
      tbody.innerHTML = emptyRow(t('empty_filtered', 'Aucun événement trouvé pour les filtres sélectionnés.'));
      return;
    }

    tbody.innerHTML = state.events.map((event, idx) => {
      const statusBadge = statusToBadge(event.status || 'confirmed');
      const calendarName = calendarLabel(event.calendar_id);
      const holidayBadge = event.is_holiday
        ? `<span class="badge badge-sent" style="margin-left:8px;">${esc(t('holiday_badge', 'Férié'))}</span>`
        : '';

      return `
        <tr>
          <td>
            <button type="button" data-gc-detail="${idx}" style="all:unset;cursor:pointer;font-weight:var(--fw-medium);display:flex;align-items:center;gap:6px;flex-wrap:wrap;color:var(--c-ink-90);">${esc(event.summary || t('no_title', '(Sans titre)'))} ${holidayBadge}</button>
            ${event.client_name ? `<div style="font-size:12px;color:var(--c-ink-40);"><i class="fas fa-building"></i> ${esc(event.client_name)}</div>` : ''}
            ${event.location ? `<div style="font-size:12px;color:var(--c-ink-40);"><i class="fas fa-location-dot"></i> ${esc(event.location)}</div>` : ''}
          </td>
          <td>${esc(calendarName)}</td>
          <td>${esc(event.start_display || '-')}</td>
          <td>${esc(event.end_display || '-')}</td>
          <td>${statusBadge}</td>
          <td>
            <div class="row-actions" style="justify-content:flex-end;padding-right:4px;opacity:1;">
              ${event.html_link ? `<a href="${esc(event.html_link)}" target="_blank" rel="noopener" class="btn-icon" title="${esc(t('open_google', 'Ouvrir dans Google'))}"><i class="fas fa-arrow-up-right-from-square"></i></a>` : ''}
              <button class="btn-icon" data-gc-edit="${idx}" title="${esc(t('edit', 'Modifier'))}"><i class="fas fa-pen"></i></button>
              <button class="btn-icon danger" data-gc-delete="${idx}" title="${esc(t('delete', 'Supprimer'))}"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  function renderPagination(payload) {
    const wrap = document.getElementById('gcPaginationControls');
    const info = document.getElementById('gcPaginationInfo');
    if (!wrap) return;

    const currentPage = payload.current_page || 1;
    const lastPage = payload.last_page || 1;

    if (info) {
      info.textContent = t('pagination_showing', 'Affichage :from à :to sur :total événement(s)', {
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
      <button class="page-btn" ${currentPage <= 1 ? 'disabled' : ''} data-gc-page="${currentPage - 1}">
        <i class="fas fa-chevron-left"></i>
      </button>
      ${pages.map((p) => `<button class="page-btn ${p === currentPage ? 'active' : ''}" data-gc-page="${p}">${p}</button>`).join('')}
      <button class="page-btn" ${currentPage >= lastPage ? 'disabled' : ''} data-gc-page="${currentPage + 1}">
        <i class="fas fa-chevron-right"></i>
      </button>`;

    wrap.querySelectorAll('[data-gc-page]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const page = parseInt(btn.dataset.gcPage, 10);
        if (!Number.isNaN(page) && page > 0 && page !== state.page) {
          state.page = page;
          loadEvents(false);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
    });
  }

  function clearPagination() {
    const info = document.getElementById('gcPaginationInfo');
    const wrap = document.getElementById('gcPaginationControls');

    if (info) {
      info.textContent = '';
    }

    if (wrap) {
      wrap.innerHTML = '';
    }
  }

  async function loadStats() {
    const { ok, data } = await Http.get(window.GCAL_ROUTES.stats);

    if (!ok || !data.success) {
      return;
    }

    const stats = data.data || {};

    setText('gcStatCalendars', stats.calendars_count || 0);
    setText('gcStatToday', stats.events_today || 0);
    setText('gcStatMonth', stats.events_this_month || 0);
    setText('gcStatNext', stats.events_next_30_days || 0);
    setText('gcStatHolidays', stats.holiday_events_this_year || 0);

    if (stats.last_sync_at) {
      setText('gcLastSyncLabel', new Date(stats.last_sync_at).toLocaleString());
    }
  }

  async function syncNow() {
    const btn = document.getElementById('gcSyncBtn');
    if (btn) CrmForm.setLoading(btn, true);

    const syncRange = state.viewMode === 'list' ? { from: state.from || null, to: state.to || null } : getCurrentRange();
    const payload = {
      calendar_id: state.selectedCalendarId || null,
      include_holidays: state.includeHolidays ? 1 : 0,
      from: syncRange.from || syncRange.startDate || null,
      to: syncRange.to || syncRange.endDate || null,
    };

    const { ok, data } = await Http.post(window.GCAL_ROUTES.sync, payload);

    if (btn) CrmForm.setLoading(btn, false);

    if (!ok || !data.success) {
      Toast.error(t('error', 'Erreur'), data.message || t('sync_error', 'Échec de la synchronisation.'));
      return;
    }

    Toast.success(t('success', 'Succès'), data.message || t('sync_success', 'Synchronisation terminée.'));

    await loadCalendars(false);
    await loadStats();
    await loadEvents(false);
  }

  async function disconnect() {
    Modal.confirm({
      title: t('disconnect_confirm_title', 'Déconnecter Google Calendar ?'),
      message: t('disconnect_confirm_message', 'Les tokens OAuth seront supprimés pour ce tenant.'),
      confirmText: t('disconnect_confirm_button', 'Déconnecter'),
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.post(window.GCAL_ROUTES.disconnect, {});
        if (!ok || !data.success) {
          Toast.error(t('error', 'Erreur'), data.message || t('disconnect_error', 'Impossible de déconnecter Google Calendar.'));
          return;
        }

        Toast.success(t('disconnect_success_title', 'Déconnecté'), data.message || t('disconnect_success_message', 'Google Calendar a été déconnecté.'));
        setTimeout(() => window.location.reload(), 700);
      },
    });
  }

  function editEvent(index) {
    const event = state.events[index];
    if (!event) return;
    editEventObject(event);
  }

  function editEventById(localId) {
    const event = findEventById(localId);
    if (!event) return;
    editEventObject(event);
  }

  function editEventObject(event) {
    Modal.close(document.getElementById('gcEventDetailModal'));
    state.detailEvent = null;
    state.editingEvent = event;
    setModalTitle(t('modal_edit', 'Modifier un événement'));

    resetEventForm();

    setFieldValue('gcEventCalendarId', event.calendar_id || state.selectedCalendarId || '');
    setFieldValue('gcEventId', event.event_id || '');
    setFieldValue('gcSummary', event.summary || '');
    setFieldValue('gcLocation', event.location || '');
    setFieldValue('gcVisibility', event.visibility || 'default');
    setFieldValue('gcDescription', event.description || '');
    setFieldValue('gcClientId', event.client_id || '');
    setFieldValue('gcSourceType', event.source_type || '');
    setFieldValue('gcSourceId', event.source_id || '');
    setFieldValue('gcSourceLabel', event.source_label || '');

    if (event.start_at) {
      setFieldValue('gcStartAt', toDateTimeLocal(event.start_at));
    }

    if (event.end_at) {
      setFieldValue('gcEndAt', toDateTimeLocal(event.end_at));
    }

    if (Array.isArray(event.attendees) && event.attendees.length) {
      const emails = event.attendees.map((att) => att.email).filter(Boolean);
      setFieldValue('gcAttendees', emails.join(', '));
    }

    Modal.open(document.getElementById('gcEventModal'));
  }

  async function deleteEvent(index) {
    const event = state.events[index];
    if (!event) return;
    await deleteEventObject(event);
  }

  async function deleteEventById(localId) {
    const event = findEventById(localId);
    if (!event) return;
    await deleteEventObject(event);
  }

  async function deleteEventObject(event) {
    Modal.close(document.getElementById('gcEventDetailModal'));
    state.detailEvent = null;
    Modal.confirm({
      title: t('delete_confirm_title', 'Supprimer cet événement ?'),
      message: t('delete_confirm_message', 'L’événement ":title" sera supprimé de Google Calendar.', {
        title: event.summary || t('no_title', '(Sans titre)'),
      }),
      confirmText: t('delete_confirm_button', 'Supprimer'),
      type: 'danger',
      onConfirm: async () => {
        const url = `${window.GCAL_ROUTES.eventsBase}/${encodeURIComponent(event.calendar_id)}/${encodeURIComponent(event.event_id)}`;
        const { ok, data } = await Http.delete(url);

        if (!ok || !data.success) {
          Toast.error(t('error', 'Erreur'), data.message || t('delete_error', 'Impossible de supprimer cet événement.'));
          return;
        }

        Toast.success(t('delete_success_title', 'Supprimé'), data.message || t('delete_success_message', 'Événement supprimé.'));
        loadEvents(false);
        loadStats();
      },
    });
  }

  async function saveEvent() {
    const form = document.getElementById('gcEventForm');
    const btn = document.getElementById('gcSaveEventBtn');
    if (!form || !btn) return;

    clearFormErrors(form);

    const payload = {
      calendar_id: getFieldValue('gcEventCalendarId') || state.selectedCalendarId || '',
      summary: getFieldValue('gcSummary').trim(),
      start_at: getFieldValue('gcStartAt'),
      end_at: getFieldValue('gcEndAt'),
      location: getFieldValue('gcLocation').trim(),
      client_id: normalizeNullable(getFieldValue('gcClientId')),
      source_type: normalizeNullable(getFieldValue('gcSourceType')),
      source_id: normalizeNullable(getFieldValue('gcSourceId')),
      source_label: normalizeNullable(getFieldValue('gcSourceLabel')),
      visibility: getFieldValue('gcVisibility'),
      reminder_minutes: getFieldValue('gcReminder') || null,
      attendees: getFieldValue('gcAttendees').trim(),
      description: getFieldValue('gcDescription').trim(),
      timezone: state.timezone,
    };

    const validationErrors = validatePayload(payload);
    if (Object.keys(validationErrors).length) {
      showFormErrors(form, validationErrors);
      Toast.error(t('validation_title', 'Validation'), t('validation_error', 'Veuillez corriger les erreurs du formulaire.'));
      return;
    }

    CrmForm.setLoading(btn, true);

    let response;
    if (state.editingEvent) {
      const url = `${window.GCAL_ROUTES.eventsBase}/${encodeURIComponent(state.editingEvent.calendar_id)}/${encodeURIComponent(state.editingEvent.event_id)}`;
      response = await Http.put(url, payload);
    } else {
      response = await Http.post(window.GCAL_ROUTES.eventsStore, payload);
    }

    CrmForm.setLoading(btn, false);

    if (!response.ok) {
      if (response.status === 422 && response.data?.errors) {
        showFormErrors(form, response.data.errors);
      }

      Toast.error(t('error', 'Erreur'), response.data?.message || t('save_error', 'Impossible d’enregistrer cet événement.'));
      return;
    }

    Toast.success(t('success', 'Succès'), response.data?.message || t('save_success_message', 'Événement enregistré.'));
    Modal.close(document.getElementById('gcEventModal'));

    state.editingEvent = null;
    loadEvents(false);
    loadStats();
  }

  function validatePayload(payload) {
    const errors = {};

    if (!payload.calendar_id) {
      errors.calendar_id = [t('validation_calendar', 'Veuillez sélectionner un calendrier.')];
    }

    if (!payload.summary) {
      errors.summary = [t('validation_title_required', 'Le titre est obligatoire.')];
    }

    if (!payload.start_at) {
      errors.start_at = [t('validation_start', 'La date de début est obligatoire.')];
    }

    if (!payload.end_at) {
      errors.end_at = [t('validation_end', 'La date de fin est obligatoire.')];
    }

    if (payload.start_at && payload.end_at) {
      const start = new Date(payload.start_at);
      const end = new Date(payload.end_at);
      if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) {
        errors.end_at = [t('validation_end_after_start', 'La date de fin doit être après la date de début.')];
      }
    }

    if (payload.attendees) {
      const emails = payload.attendees.split(',').map((v) => v.trim()).filter(Boolean);
      const invalid = emails.find((email) => !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email));
      if (invalid) {
        errors.attendees = [t('validation_attendees', 'Un ou plusieurs emails participants sont invalides.')];
      }
    }

    if (payload.source_type && !payload.source_id) {
      errors.source_id = [t('validation_source', 'Le type de source est invalide.')];
    }

    return errors;
  }

  function resetEventForm() {
    const form = document.getElementById('gcEventForm');
    if (!form) return;

    clearFormErrors(form);
    form.reset();

    setFieldValue('gcEventCalendarId', state.selectedCalendarId || '');
    setFieldValue('gcEventId', '');
    setFieldValue('gcVisibility', 'default');
    setFieldValue('gcClientId', '');
    setFieldValue('gcSourceType', '');
    setFieldValue('gcSourceId', '');
    setFieldValue('gcSourceLabel', '');

    const now = new Date();
    const plusHour = new Date(now.getTime() + 60 * 60 * 1000);

    setFieldValue('gcStartAt', toDateTimeLocal(now));
    setFieldValue('gcEndAt', toDateTimeLocal(plusHour));

    if (state.prefill && !state.editingEvent) {
      setFieldValue('gcClientId', state.prefill.client_id || '');
      setFieldValue('gcSourceType', state.prefill.source_type || '');
      setFieldValue('gcSourceId', state.prefill.source_id || '');
      setFieldValue('gcSourceLabel', state.prefill.source_label || '');

      if (state.prefill.summary) {
        setFieldValue('gcSummary', state.prefill.summary);
      }

      if (state.prefill.description) {
        setFieldValue('gcDescription', state.prefill.description);
      }
    }
  }

  function setModalTitle(text) {
    setText('gcEventModalTitle', text);
  }

  function statusToBadge(status) {
    const map = {
      confirmed: { cls: 'badge-paid', label: t('status_confirmed', 'Confirmé') },
      tentative: { cls: 'badge-sent', label: t('status_tentative', 'Provisoire') },
      cancelled: { cls: 'badge-cancelled', label: t('status_cancelled', 'Annulé') },
    };
    const cfg = map[status] || { cls: 'badge-draft', label: status || t('status_unknown', 'Inconnu') };
    return `<span class="badge ${cfg.cls}">${esc(cfg.label)}</span>`;
  }

  function calendarLabel(calendarId) {
    const found = state.calendars.find((c) => c.calendar_id === calendarId);
    return found ? (found.summary || calendarId) : calendarId;
  }

  function findEventById(localId) {
    const key = String(localId || '');
    if (!key) {
      return null;
    }

    return state.calendarEvents.find((event) => String(event.id) === key)
      || state.events.find((event) => String(event.id) === key)
      || null;
  }

  function openEventDetailsById(localId) {
    const event = findEventById(localId);
    if (!event) return;
    openEventDetails(event);
  }

  function openEventDetails(event) {
    if (!event) return;

    state.detailEvent = event;

    const palette = eventPalette(event);
    const icon = document.getElementById('gcDetailModalIcon');
    const googleBtn = document.getElementById('gcDetailOpenGoogleBtn');
    const attendees = Array.isArray(event.attendees) ? event.attendees.filter((att) => att && att.email) : [];
    const description = String(event.description || '').trim();
    const clientName = String(event.client_name || '').trim();
    const sourceLabel = String(event.source_label || '').trim();
    const location = String(event.location || '').trim();

    if (icon) {
      icon.style.background = palette.soft;
      icon.style.color = palette.text;
    }

    const dot = document.getElementById('gcDetailCalendarDot');
    if (dot) {
      dot.style.background = palette.borderStrong;
    }

    setText('gcDetailCalendarName', calendarLabel(event.calendar_id));
    setText('gcDetailEventTitle', event.summary || t('no_title', '(Sans titre)'));
    setText('gcDetailWhen', eventDetailWhenLabel(event));
    setText('gcDetailLocation', location || t('detail_empty', 'Non renseigné'));
    setText('gcDetailClient', clientName || t('detail_empty', 'Non renseigné'));
    setText('gcDetailSource', sourceLabel || t('detail_empty', 'Non renseigné'));
    setText('gcDetailVisibility', visibilityLabel(event.visibility || 'default'));
    setText('gcDetailUpdatedAt', formatDetailDateTime(event.google_updated_at || event.updated_at));

    const statusWrap = document.getElementById('gcDetailStatus');
    if (statusWrap) {
      statusWrap.innerHTML = statusToBadge(event.status || 'confirmed');
    }

    const attendeesWrap = document.getElementById('gcDetailAttendees');
    if (attendeesWrap) {
      attendeesWrap.innerHTML = attendees.length
        ? attendees.map((att) => `<span class="gc-detail-attendee"><i class="fas fa-user"></i> ${esc(att.email)}</span>`).join('')
        : `<span class="gc-detail-empty">${esc(t('detail_no_attendees', 'Aucun participant'))}</span>`;
    }

    const descriptionWrap = document.getElementById('gcDetailDescription');
    if (descriptionWrap) {
      descriptionWrap.textContent = description || t('detail_no_description', 'Aucune description.');
      descriptionWrap.classList.toggle('gc-detail-empty', !description);
    }

    if (googleBtn) {
      googleBtn.hidden = !event.html_link;
    }

    Modal.open(document.getElementById('gcEventDetailModal'));
  }

  function openDetailEditMode() {
    if (!state.detailEvent) return;
    editEventObject(state.detailEvent);
  }

  async function deleteDetailEvent() {
    if (!state.detailEvent) return;
    await deleteEventObject(state.detailEvent);
  }

  function openDetailGoogleLink() {
    if (!state.detailEvent?.html_link) return;
    window.open(state.detailEvent.html_link, '_blank', 'noopener,noreferrer');
  }

  function eventPalette(event) {
    if (event.is_holiday) {
      return {
        background: '#b45309',
        foreground: '#fff8eb',
        soft: '#b45309',
        hover: '#92400e',
        border: '#92400e',
        borderStrong: '#78350f',
        timeBg: 'rgba(255, 248, 235, .18)',
        chipBg: 'rgba(255, 248, 235, .16)',
        chipText: '#fff8eb',
        subtle: 'rgba(255, 248, 235, .88)',
        text: '#fff8eb',
      };
    }

    const calendar = state.calendars.find((item) => item.calendar_id === event.calendar_id) || null;
    const background = normalizeHex(calendar?.background_color) || '#2563eb';
    const foreground = normalizeHex(calendar?.foreground_color) || readableTextOn(background);
    const prefersLightText = readableTextOn(background) === '#ffffff';

    return {
      background,
      foreground,
      soft: background,
      hover: shiftColor(background, prefersLightText ? -18 : -10),
      border: shiftColor(background, prefersLightText ? -10 : -14),
      borderStrong: shiftColor(background, prefersLightText ? -22 : -22),
      timeBg: prefersLightText ? 'rgba(255, 255, 255, .16)' : 'rgba(255, 255, 255, .42)',
      chipBg: prefersLightText ? 'rgba(255, 255, 255, .14)' : 'rgba(255, 255, 255, .36)',
      chipText: foreground,
      subtle: prefersLightText ? 'rgba(255, 255, 255, .88)' : alpha(foreground, 0.82),
      text: foreground,
    };
  }

  function eventPaletteStyle(palette) {
    return [
      `--gc-event-bg:${palette.soft}`,
      `--gc-event-bg-hover:${palette.hover}`,
      `--gc-event-border:${palette.border}`,
      `--gc-event-border-strong:${palette.borderStrong}`,
      `--gc-event-text:${palette.text}`,
      `--gc-event-subtle:${palette.subtle}`,
      `--gc-event-time-bg:${palette.timeBg}`,
      `--gc-event-time-text:${palette.text}`,
      `--gc-event-chip-bg:${palette.chipBg}`,
      `--gc-event-chip-text:${palette.chipText}`,
      `--gc-event-dot:${palette.background}`,
    ].join(';');
  }

  function normalizeHex(value) {
    const color = String(value || '').trim();
    if (!/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(color)) {
      return null;
    }

    if (color.length === 4) {
      return `#${color[1]}${color[1]}${color[2]}${color[2]}${color[3]}${color[3]}`.toLowerCase();
    }

    return color.toLowerCase();
  }

  function alpha(hex, opacity) {
    const normalized = normalizeHex(hex);
    if (!normalized) return hex;

    const rgb = hexToRgb(normalized);
    if (!rgb) return hex;

    return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${opacity})`;
  }

  function hexToRgb(hex) {
    const normalized = normalizeHex(hex);
    if (!normalized) return null;

    const bigint = parseInt(normalized.slice(1), 16);
    return {
      r: (bigint >> 16) & 255,
      g: (bigint >> 8) & 255,
      b: bigint & 255,
    };
  }

  function readableTextOn(hex) {
    const rgb = hexToRgb(hex);
    if (!rgb) return '#1f2937';

    const luminance = ((0.299 * rgb.r) + (0.587 * rgb.g) + (0.114 * rgb.b)) / 255;
    return luminance > 0.68 ? '#1f2937' : '#ffffff';
  }

  function shiftColor(hex, delta) {
    const rgb = hexToRgb(hex);
    if (!rgb) return hex;

    const clamp = (value) => Math.max(0, Math.min(255, value));
    const channels = [rgb.r, rgb.g, rgb.b]
      .map((channel) => clamp(channel + delta).toString(16).padStart(2, '0'))
      .join('');

    return `#${channels}`;
  }

  function eventDetailWhenLabel(event) {
    if (event.all_day) {
      if (event.start_display && event.end_display && event.start_display !== event.end_display) {
        return `${event.start_display} - ${event.end_display}`;
      }

      return event.start_display || t('all_day', 'Toute la journée');
    }

    if (event.start_display && event.end_display) {
      return `${event.start_display} - ${event.end_display}`;
    }

    return event.start_display || event.end_display || '-';
  }

  function visibilityLabel(value) {
    const map = {
      default: t('visibility_default', 'Par défaut'),
      public: t('visibility_public', 'Public'),
      private: t('visibility_private', 'Privé'),
      confidential: t('visibility_confidential', 'Confidentiel'),
    };

    return map[value] || value || '-';
  }

  function formatDetailDateTime(value) {
    if (!value) return t('detail_empty', 'Non renseigné');

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return t('detail_empty', 'Non renseigné');

    return new Intl.DateTimeFormat(state.locale, {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).format(date);
  }

  function getCurrentRange() {
    const anchor = new Date(state.anchorDate);

    if (state.viewMode === 'week') {
      const start = startOfWeek(anchor);
      const end = endOfWeek(anchor);
      return {
        start,
        end,
        startDate: toDateKey(start),
        endDate: toDateKey(end),
      };
    }

    if (state.viewMode === 'day') {
      const start = startOfDay(anchor);
      const end = endOfDay(anchor);
      return {
        start,
        end,
        startDate: toDateKey(start),
        endDate: toDateKey(end),
      };
    }

    if (state.viewMode === 'year') {
      const start = new Date(anchor.getFullYear(), 0, 1, 0, 0, 0, 0);
      const end = new Date(anchor.getFullYear(), 11, 31, 23, 59, 59, 999);
      return {
        start,
        end,
        startDate: toDateKey(start),
        endDate: toDateKey(end),
      };
    }

    const start = startOfMonth(anchor);
    const end = endOfMonth(anchor);

    return {
      start,
      end,
      startDate: toDateKey(start),
      endDate: toDateKey(end),
    };
  }

  function startOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth(), 1, 0, 0, 0, 0);
  }

  function endOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0, 23, 59, 59, 999);
  }

  function startOfWeek(date) {
    const d = startOfDay(date);
    const day = (d.getDay() + 6) % 7;
    d.setDate(d.getDate() - day);
    return startOfDay(d);
  }

  function endOfWeek(date) {
    const start = startOfWeek(date);
    const end = addDays(start, 6);
    end.setHours(23, 59, 59, 999);
    return end;
  }

  function startOfDay(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    return d;
  }

  function endOfDay(date) {
    const d = new Date(date);
    d.setHours(23, 59, 59, 999);
    return d;
  }

  function addDays(date, days) {
    const d = new Date(date);
    d.setDate(d.getDate() + days);
    return d;
  }

  function toDateKey(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  function isSameDate(a, b) {
    return a.getFullYear() === b.getFullYear()
      && a.getMonth() === b.getMonth()
      && a.getDate() === b.getDate();
  }

  function sortByStart(a, b) {
    return safeDate(a.start_at).getTime() - safeDate(b.start_at).getTime();
  }

  function safeDate(value) {
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) {
      return new Date(0);
    }
    return d;
  }

  function skeletonRows(count, cols) {
    return Array.from({ length: count }, () => `<tr>${Array.from({ length: cols }, () => '<td><div class="skeleton" style="height:13px;"></div></td>').join('')}</tr>`).join('');
  }

  function modeSkeleton() {
    return `
      <div class="gc-mode-skeleton">
        <div class="skeleton" style="height:16px;width:180px;margin-bottom:10px;"></div>
        <div class="skeleton" style="height:110px;margin-bottom:10px;"></div>
        <div class="skeleton" style="height:110px;margin-bottom:10px;"></div>
        <div class="skeleton" style="height:110px;"></div>
      </div>
    `;
  }

  function modeEmpty() {
    return `
      <div class="table-empty">
        <div class="table-empty-icon"><i class="fas fa-calendar"></i></div>
        <h3>${esc(t('mode_no_events_title', 'Aucun événement'))}</h3>
        <p>${esc(t('mode_no_events_message', 'Aucun événement trouvé sur cette période.'))}</p>
      </div>
    `;
  }

  function modeError(message) {
    return `
      <div class="table-empty">
        <div class="table-empty-icon"><i class="fas fa-circle-exclamation"></i></div>
        <h3>${esc(t('mode_load_error_title', 'Erreur de chargement'))}</h3>
        <p>${esc(message || t('load_events_error', 'Impossible de charger les événements.'))}</p>
      </div>
    `;
  }

  function emptyRow(message) {
    return `<tr><td colspan="6"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-calendar"></i></div><h3>${esc(t('no_data_title', 'Aucune donnée'))}</h3><p>${esc(message || t('no_data_message', 'Aucune donnée disponible.'))}</p></div></td></tr>`;
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
    if (el) el.value = value;
  }

  function getFieldValue(id) {
    const el = document.getElementById(id);
    return el ? el.value : '';
  }

  function normalizeNullable(value) {
    if (value === null || typeof value === 'undefined') {
      return null;
    }

    const casted = String(value).trim();
    return casted === '' ? null : casted;
  }

  function t(key, fallback = '', params = {}) {
    const bag = state.i18n && typeof state.i18n === 'object' ? state.i18n : {};
    let output = Object.prototype.hasOwnProperty.call(bag, key) ? bag[key] : fallback;

    if (typeof output !== 'string') {
      output = fallback || '';
    }

    Object.entries(params || {}).forEach(([param, value]) => {
      output = output.replace(new RegExp(`:${param}`, 'g'), String(value));
    });

    return output;
  }

  function getWeekdayLabels() {
    const base = new Date(2024, 0, 1);
    return Array.from({ length: 7 }, (_, index) => new Intl.DateTimeFormat(state.locale, { weekday: 'short' }).format(addDays(base, index)));
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  function clearFormErrors(form) {
    form.querySelectorAll('.form-error').forEach((el) => el.remove());
    form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
  }

  function showFormErrors(form, errors) {
    Object.entries(errors).forEach(([field, messages]) => {
      const input = form.querySelector(`[name="${field}"]`) || mapFieldAlias(form, field);
      if (!input) return;

      input.classList.add('is-invalid');

      const error = document.createElement('div');
      error.className = 'form-error';
      error.textContent = Array.isArray(messages) ? messages[0] : messages;
      input.parentNode.appendChild(error);
    });
  }

  function mapFieldAlias(form, field) {
    if (field === 'calendar_id') {
      return form.querySelector('#gcSummary');
    }
    if (field === 'client_id') {
      return form.querySelector('#gcClientId');
    }
    if (field === 'source_id') {
      return form.querySelector('#gcSummary');
    }
    return null;
  }

  return {
    boot,
  };
})();

window.GoogleCalendarModule = GoogleCalendarModule;
