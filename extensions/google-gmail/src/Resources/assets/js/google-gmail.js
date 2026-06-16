'use strict';

const GoogleGmailModule = (() => {
  const MAX_ATTACHMENTS = 8;
  const MAX_ATTACHMENT_SIZE_BYTES = 10 * 1024 * 1024;
  const MAX_TOTAL_ATTACHMENTS_BYTES = 24 * 1024 * 1024;

  const DEFAULT_MAIN_LABELS = [
    'INBOX',
    'STARRED',
    'SENT',
    'DRAFT',
    'IMPORTANT',
    'UNREAD',
    'CATEGORY_PERSONAL',
    'CATEGORY_UPDATES',
    'CATEGORY_PROMOTIONS',
    'TRASH',
  ];

  const LABEL_NAME_MAP = {
    INBOX: 'Boite de reception',
    SENT: 'Envoyes',
    DRAFT: 'Brouillons',
    TRASH: 'Corbeille',
    SPAM: 'Spam',
    STARRED: 'Favoris',
    IMPORTANT: 'Importants',
    UNREAD: 'Non lus',
    CATEGORY_PERSONAL: 'Personnel',
    CATEGORY_UPDATES: 'Mises a jour',
    CATEGORY_PROMOTIONS: 'Promotions',
    CATEGORY_SOCIAL: 'Reseaux sociaux',
    CATEGORY_FORUMS: 'Forums',
    ALL: 'Tous les messages',
    ANY: 'Tous les messages',
  };

  const state = {
    connected: false,
    tenantId: 0,
    userId: 0,
    labels: [],
    messages: [],
    currentMessage: null,
    selectedLabel: 'INBOX',
    search: '',
    currentToken: null,
    nextToken: null,
    prevTokens: [],
    debounceTimer: null,
    socketConfig: {},
    socket: null,
    useSocket: false,
    socketRefreshTimer: null,
    visibilityListenerBound: false,
    unloadListenerBound: false,
    pollTimer: null,
    pollBusy: false,
    pollTick: 0,
    lastSnapshotAt: 0,
    lastUnread: null,
    lastInboxTotal: null,
    showMoreLabels: false,
    settings: {
      signature_enabled: false,
      signature_html: '',
      signature_on_replies: true,
      signature_on_forwards: true,
      default_cc: [],
      default_bcc: [],
      polling_interval_seconds: 45,
      main_labels: DEFAULT_MAIN_LABELS.slice(),
    },
    i18n: {},
  };

  const tagsStores = {};
  const attachmentStores = {};

  function boot(bootstrap = {}) {
    state.connected = !!bootstrap.connected;
    state.tenantId = Number(bootstrap.tenantId || 0);
    state.userId = Number(bootstrap.userId || 0);
    state.settings = normalizeSettings(bootstrap.settings || {});
    state.socketConfig = bootstrap.socket || {};
    state.i18n = (bootstrap.i18n && typeof bootstrap.i18n === 'object') ? bootstrap.i18n : {};

    bindActions();
    initEditors();
    initTagsInputs();
    initAttachmentInputs();
    hydrateSettingsForm();

    if (!state.connected) return;

    refreshAll(true, { silent: true }).then(() => {
      initRealtime();
    });
  }

  function bindActions() {
    document.getElementById('ggmRefreshBtn')?.addEventListener('click', () => refreshAll(true));
    document.getElementById('ggmDisconnectBtn')?.addEventListener('click', disconnect);
    document.getElementById('ggmSettingsSaveBtn')?.addEventListener('click', saveSettings);
    document.getElementById('ggmComposeBtn')?.addEventListener('click', () => prepareComposeModal());

    document.getElementById('ggmSearchInput')?.addEventListener('input', (e) => {
      clearTimeout(state.debounceTimer);
      state.debounceTimer = setTimeout(() => {
        state.search = (e.target.value || '').trim();
        resetPagination();
        loadMessages();
      }, 320);
    });

    document.getElementById('ggmPrevPageBtn')?.addEventListener('click', goPrevPage);
    document.getElementById('ggmNextPageBtn')?.addEventListener('click', goNextPage);

    document.getElementById('ggmComposeSendBtn')?.addEventListener('click', sendCompose);
    document.getElementById('ggmReplySendBtn')?.addEventListener('click', sendReply);
    document.getElementById('ggmForwardSendBtn')?.addEventListener('click', sendForward);
    document.getElementById('ggmComposeSignatureMode')?.addEventListener('change', (event) => {
      applyComposeSignatureMode(event?.target?.value || 'auto', { silent: false });
    });

    document.getElementById('ggmToggleReadBtn')?.addEventListener('click', toggleRead);
    document.getElementById('ggmToggleStarBtn')?.addEventListener('click', toggleStar);
    document.getElementById('ggmArchiveBtn')?.addEventListener('click', archiveMessage);
    document.getElementById('ggmTrashBtn')?.addEventListener('click', trashOrDeleteMessage);

    document.querySelectorAll('#ggmAttachmentPreviewModal [data-modal-close]').forEach((btn) => {
      btn.addEventListener('click', () => {
        clearAttachmentPreviewBody();
      });
    });

    document.getElementById('ggmAttachmentPreviewModal')?.addEventListener('click', (event) => {
      if (event.target?.id === 'ggmAttachmentPreviewModal') {
        clearAttachmentPreviewBody();
      }
    });
  }

  function normalizeSettings(raw) {
    const mainLabels = Array.isArray(raw.main_labels) && raw.main_labels.length
      ? raw.main_labels.map((label) => String(label || '').trim().toUpperCase()).filter(Boolean)
      : DEFAULT_MAIN_LABELS.slice();

    return {
      signature_enabled: !!raw.signature_enabled,
      signature_html: String(raw.signature_html || ''),
      signature_on_replies: raw.signature_on_replies !== false,
      signature_on_forwards: raw.signature_on_forwards !== false,
      default_cc: normalizeEmailArray(raw.default_cc || []),
      default_bcc: normalizeEmailArray(raw.default_bcc || []),
      polling_interval_seconds: normalizePollingInterval(raw.polling_interval_seconds),
      main_labels: mainLabels.slice(0, 10),
    };
  }

  function normalizePollingInterval(value) {
    const intValue = Number(value || 45);
    if (!Number.isFinite(intValue)) return 45;
    return Math.max(15, Math.min(300, Math.floor(intValue)));
  }

  function normalizeEmailArray(values) {
    const source = Array.isArray(values) ? values : String(values || '').split(/[,;\n]+/);
    const result = [];

    source.forEach((value) => {
      const email = sanitizeEmail(value);
      if (!email || !isValidEmail(email)) return;
      if (!result.includes(email)) result.push(email);
    });

    return result;
  }

  async function refreshAll(force = false, options = {}) {
    const btn = document.getElementById('ggmRefreshBtn');
    const backgroundRefresh = !!(options.silent || options.background);
    if (btn && !backgroundRefresh) CrmForm.setLoading(btn, true);

    try {
      if (window.GGMAIL_ROUTES.snapshotData) {
        await loadSnapshot(!!force, options);
        return;
      }

      await Promise.all([
        loadSettings(false),
        loadLabels(!!force),
      ]);
      await Promise.all([
        loadStats(!!force, options),
        loadMessages(options),
      ]);
      state.lastSnapshotAt = Date.now();
    } finally {
      if (btn && !backgroundRefresh) CrmForm.setLoading(btn, false);
    }
  }

  async function loadSnapshot(refresh = false, options = {}) {
    if (!window.GGMAIL_ROUTES.snapshotData) {
      await Promise.all([
        loadSettings(false),
        loadLabels(!!refresh),
      ]);
      await Promise.all([
        loadStats(!!refresh, options),
        loadMessages(options),
      ]);
      state.lastSnapshotAt = Date.now();
      return true;
    }

    const list = document.getElementById('ggmMessagesList');
    if (list && !options.silent) {
      list.innerHTML = skeletonItems(8);
    }

    const selectedUpper = (state.selectedLabel || '').toUpperCase();
    const { ok, data } = await Http.get(window.GGMAIL_ROUTES.snapshotData, {
      refresh: refresh ? 1 : 0,
      label_id: state.selectedLabel,
      q: state.search,
      page_token: state.currentToken || '',
      max_results: 25,
      include_spam_trash: ['TRASH', 'SPAM'].includes(selectedUpper) ? 1 : 0,
    });

    if (!ok || !data.success) {
      if (list && !options.silent) {
        list.innerHTML = '<div class="table-empty" style="padding:18px;"><h3>Erreur</h3><p>Impossible de charger les emails.</p></div>';
      }

      if (!options.silent) {
        Toast.error('Erreur', data.message || 'Impossible de charger la boite Gmail.');
      }

      return false;
    }

    const payload = data.data || {};
    state.settings = normalizeSettings(payload.settings || state.settings || {});
    hydrateSettingsForm();

    applyLabels(payload.labels || []);
    applyStats(payload.stats || {}, options);

    const messagesPayload = payload.messages || {};
    state.messages = Array.isArray(messagesPayload.messages) ? messagesPayload.messages : [];
    state.nextToken = messagesPayload.next_page_token || null;

    renderMessages();
    updatePagerButtons();
    updateListTitle();
    updateVisibleMessageCount();

    if (state.currentMessage?.message_id) {
      const stillThere = state.messages.find((row) => row.message_id === state.currentMessage.message_id);
      if (!stillThere) {
        showEmptyView();
      }
    }

    state.lastSnapshotAt = Date.now();

    return true;
  }

  async function loadSettings(showErrors = false) {
    if (!window.GGMAIL_ROUTES.settingsData) return;

    const { ok, data } = await Http.get(window.GGMAIL_ROUTES.settingsData);
    if (!ok || !data.success) {
      if (showErrors) Toast.error('Erreur', data.message || 'Impossible de charger les parametres Gmail.');
      return;
    }

    state.settings = normalizeSettings(data.data || {});
    hydrateSettingsForm();
  }

  function hydrateSettingsForm() {
    const form = document.getElementById('ggmSettingsForm');
    if (!form) return;

    const settings = state.settings;

    const signatureEnabled = document.getElementById('ggmSettingsSignatureEnabled');
    const signatureReplies = document.getElementById('ggmSettingsSignatureReplies');
    const signatureForwards = document.getElementById('ggmSettingsSignatureForwards');
    const polling = document.getElementById('ggmSettingsPolling');

    if (signatureEnabled) signatureEnabled.checked = !!settings.signature_enabled;
    if (signatureReplies) signatureReplies.checked = !!settings.signature_on_replies;
    if (signatureForwards) signatureForwards.checked = !!settings.signature_on_forwards;
    if (polling) polling.value = String(settings.polling_interval_seconds || 45);

    const signatureEditor = document.getElementById('ggmSettingsSignatureEditor');
    const signatureInput = document.getElementById('ggmSettingsSignatureHtml');
    if (signatureEditor && signatureInput) {
      signatureEditor.innerHTML = settings.signature_html || '';
      signatureInput.value = settings.signature_html || '';
    }

    setTags('ggmSettingsDefaultCc', settings.default_cc || []);
    setTags('ggmSettingsDefaultBcc', settings.default_bcc || []);
    setTags('ggmSettingsMainLabels', settings.main_labels || DEFAULT_MAIN_LABELS);

    if (!getTags('ggmComposeCc').length) {
      setTags('ggmComposeCc', settings.default_cc || []);
    }
    if (!getTags('ggmComposeBcc').length) {
      setTags('ggmComposeBcc', settings.default_bcc || []);
    }
  }

  async function saveSettings() {
    const form = document.getElementById('ggmSettingsForm');
    const btn = document.getElementById('ggmSettingsSaveBtn');
    if (!form || !btn) return;

    const signatureEditor = document.getElementById('ggmSettingsSignatureEditor');
    const signatureInput = document.getElementById('ggmSettingsSignatureHtml');
    if (signatureEditor && signatureInput) {
      signatureInput.value = (signatureEditor.innerHTML || '').trim();
    }

    const payload = {
      signature_enabled: document.getElementById('ggmSettingsSignatureEnabled')?.checked ? 1 : 0,
      signature_html: signatureInput?.value || '',
      signature_on_replies: document.getElementById('ggmSettingsSignatureReplies')?.checked ? 1 : 0,
      signature_on_forwards: document.getElementById('ggmSettingsSignatureForwards')?.checked ? 1 : 0,
      default_cc: getTagsCsv('ggmSettingsDefaultCc'),
      default_bcc: getTagsCsv('ggmSettingsDefaultBcc'),
      polling_interval_seconds: Number(document.getElementById('ggmSettingsPolling')?.value || 45),
      main_labels: getTags('ggmSettingsMainLabels').map((label) => label.toUpperCase()).slice(0, 10),
    };

    if (!payload.main_labels.length) {
      payload.main_labels = DEFAULT_MAIN_LABELS.slice();
    }

    CrmForm.clearErrors(form);
    CrmForm.setLoading(btn, true);
    const { ok, status, data } = await Http.post(window.GGMAIL_ROUTES.settingsSave, payload);
    CrmForm.setLoading(btn, false);

    if (!ok || !data.success) {
      if (status === 422 && data.errors) {
        CrmForm.showErrors(form, data.errors);
      }
      Toast.error('Erreur', data.message || 'Impossible de sauvegarder les parametres Gmail.');
      return;
    }

    state.settings = normalizeSettings(data.data || payload);
    hydrateSettingsForm();
    renderLabels();
    updateListTitle();
    initRealtime();
    Toast.success('Succes', data.message || 'Parametres Gmail enregistres.');
    Modal.close(document.getElementById('ggmSettingsModal'));
  }

  async function loadStats(refresh = false, options = {}) {
    const { ok, data } = await Http.get(window.GGMAIL_ROUTES.stats, { refresh: refresh ? 1 : 0 });
    if (!ok || !data.success) {
      if (!options.silent) {
        Toast.error('Erreur', data.message || 'Impossible de charger les statistiques Gmail.');
      }
      return;
    }

    applyStats(data.data || {}, options);
  }

  async function loadLabels(refresh = false) {
    const { ok, data } = await Http.get(window.GGMAIL_ROUTES.labelsData, { refresh: refresh ? 1 : 0 });

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de charger les dossiers Gmail.');
      return;
    }

    applyLabels(data.data || []);
  }

  function applyStats(stats = {}, options = {}) {
    const inbox = Number(stats.inbox_total || 0);
    const unread = Number(stats.unread_total || 0);

    const hadPreviousUnread = state.lastUnread !== null;
    const previousUnread = Number(state.lastUnread || 0);

    state.lastInboxTotal = inbox;
    state.lastUnread = unread;

    setText('ggmStatInbox', inbox);
    setText('ggmStatUnread', unread);
    setText('ggmStatSent', Number(stats.sent_total || 0));
    setText('ggmStatDraft', Number(stats.draft_total || 0));
    setText('ggmStatTrash', Number(stats.trash_total || 0));
    setText('ggmStatStarred', Number(stats.starred_total || 0));

    if (stats.last_sync_at) {
      setText('ggmLastSyncLabel', new Date(stats.last_sync_at).toLocaleString('fr-FR'));
    }

    syncHeaderNotifications(unread);

    if (hadPreviousUnread && unread > previousUnread && !options.silentNewMail) {
      const delta = unread - previousUnread;
      const label = delta > 1 ? `${delta} nouveaux emails recus.` : '1 nouvel email recu.';
      Toast.info('Nouveaux emails', label);
    }
  }

  function applyLabels(rawLabels = []) {
    state.labels = (Array.isArray(rawLabels) ? rawLabels : []).filter((label) => !!label.label_id);
    if (state.labels.length && !state.labels.find((label) => label.label_id === state.selectedLabel)) {
      state.selectedLabel = state.labels[0].label_id;
      resetPagination();
    }
    renderLabels();
    updateListTitle();
  }

  function renderLabels() {
    const wrap = document.getElementById('ggmLabelsList');
    if (!wrap) return;

    if (!state.labels.length) {
      wrap.innerHTML = '<div class="table-empty" style="padding:12px;"><h3>Aucun dossier</h3><p>Synchronisez Gmail pour charger les labels.</p></div>';
      return;
    }

    const labels = state.labels.filter((l) => !!l.label_id);
    const configuredMain = (state.settings.main_labels || DEFAULT_MAIN_LABELS)
      .map((label) => String(label || '').trim().toUpperCase())
      .filter(Boolean)
      .slice(0, 10);

    const mapById = new Map(labels.map((label) => [String(label.label_id).toUpperCase(), label]));
    const mainLabels = [];
    configuredMain.forEach((labelId) => {
      const row = mapById.get(labelId);
      if (row) mainLabels.push(row);
    });
    if (!mainLabels.length) {
      mainLabels.push(...labels.slice(0, 10));
    }

    const mainIds = new Set(mainLabels.map((label) => String(label.label_id).toUpperCase()));
    const otherLabels = labels.filter((label) => !mainIds.has(String(label.label_id).toUpperCase()));

    const mainHtml = mainLabels.map((label) => renderLabelButton(label)).join('');
    const otherHtml = otherLabels.map((label) => renderLabelButton(label)).join('');

    wrap.innerHTML = `
      <div class="ggm-labels-section-title">Principaux</div>
      ${mainHtml}
      ${otherLabels.length ? `<button class="ggm-more-toggle" id="ggmMoreLabelsBtn">${state.showMoreLabels ? 'Masquer' : 'Plus'} (${otherLabels.length})</button>` : ''}
      ${otherLabels.length ? `<div id="ggmMoreLabelsWrap" style="display:${state.showMoreLabels ? 'block' : 'none'}"><div class="ggm-labels-section-title">Autres dossiers</div>${otherHtml}</div>` : ''}
    `;

    wrap.querySelectorAll('[data-label-id]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const labelId = btn.dataset.labelId;
        if (!labelId || labelId === state.selectedLabel) return;

        state.selectedLabel = labelId;
        resetPagination();
        renderLabels();
        updateListTitle();
        loadMessages();
      });
    });

    document.getElementById('ggmMoreLabelsBtn')?.addEventListener('click', () => {
      state.showMoreLabels = !state.showMoreLabels;
      renderLabels();
    });
  }

  function renderLabelButton(label) {
    const active = state.selectedLabel === label.label_id;
    const unread = Number(label.messages_unread || 0);
    const total = Number(label.messages_total || 0);
    const tone = label.type === 'system' ? 'sys' : 'usr';
    const name = formatLabelName(label);

    return `
      <button class="ggm-label-item ${active ? 'active' : ''} ${tone}" data-label-id="${esc(label.label_id)}">
        <span class="ggm-label-name">${esc(name)}</span>
        <span class="ggm-label-count">${total}</span>
        ${unread > 0 ? `<span class="ggm-label-unread-dot" title="${unread} non lus">${unread}</span>` : ''}
      </button>`;
  }

  async function loadMessages(options = {}) {
    const list = document.getElementById('ggmMessagesList');
    const backgroundRefresh = !!(options.silent || options.background);

    if (list && !backgroundRefresh) {
      list.innerHTML = skeletonItems(8);
    }

    const selectedUpper = (state.selectedLabel || '').toUpperCase();
    const { ok, data } = await Http.get(window.GGMAIL_ROUTES.messagesData, {
      label_id: state.selectedLabel,
      q: state.search,
      page_token: state.currentToken || '',
      max_results: 25,
      include_spam_trash: ['TRASH', 'SPAM'].includes(selectedUpper) ? 1 : 0,
    });

    if (!ok || !data.success) {
      if (list && !backgroundRefresh) {
        list.innerHTML = '<div class="table-empty" style="padding:18px;"><h3>Erreur</h3><p>Impossible de charger les emails.</p></div>';
      }
      if (!backgroundRefresh) {
        Toast.error('Erreur', data.message || 'Impossible de charger les messages Gmail.');
      }
      return;
    }

    const payload = data.data || {};
    state.messages = payload.messages || [];
    state.nextToken = payload.next_page_token || null;

    renderMessages();
    updatePagerButtons();
    updateListTitle();
    setText('ggmListCount', state.messages.length);

    if (state.currentMessage?.message_id) {
      const stillThere = state.messages.find((m) => m.message_id === state.currentMessage.message_id);
      if (!stillThere) {
        showEmptyView();
      }
    }
  }

  function renderMessages() {
    const list = document.getElementById('ggmMessagesList');
    if (!list) return;

    if (!state.messages.length) {
      list.innerHTML = '<div class="table-empty" style="padding:18px;"><div class="table-empty-icon"><i class="fas fa-inbox"></i></div><h3>Aucun message</h3><p>Aucun email pour ce dossier.</p></div>';
      return;
    }

    list.innerHTML = state.messages.map((msg, idx) => {
      const unread = msg.is_read ? '' : 'unread';
      const active = state.currentMessage?.message_id === msg.message_id ? 'active' : '';
      const star = msg.is_starred ? '<i class="fas fa-star" style="color:#ca8a04;"></i>' : '';
      const date = humanDate(msg.sent_at || msg.internal_date);
      const from = msg.from || '-';

      return `
        <button class="ggm-mail-item ${unread} ${active}" data-msg-index="${idx}">
          <div class="ggm-mail-top">
            <span class="ggm-mail-from">${esc(from)}</span>
            <span class="ggm-mail-date">${esc(date)}</span>
          </div>
          <div class="ggm-mail-subject">${esc(msg.subject || '(Sans objet)')}</div>
          <div class="ggm-mail-snippet">${esc(msg.snippet || '')}</div>
          <div class="ggm-mail-flags">
            ${star}
            ${msg.has_attachments ? '<i class="fas fa-paperclip"></i>' : ''}
          </div>
        </button>`;
    }).join('');

    list.querySelectorAll('[data-msg-index]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const idx = parseInt(btn.dataset.msgIndex, 10);
        const row = state.messages[idx];
        if (!row) return;

        openMessage(row.message_id);
      });
    });
  }

  function syncCurrentMessageIntoList() {
    if (!state.currentMessage?.message_id) return;

    const idx = state.messages.findIndex((row) => row.message_id === state.currentMessage.message_id);
    if (idx < 0) return;

    state.messages[idx] = {
      ...state.messages[idx],
      ...state.currentMessage,
    };
  }

  function removeMessageFromList(messageId) {
    if (!messageId) return;
    state.messages = state.messages.filter((row) => row.message_id !== messageId);
  }

  function updateVisibleMessageCount() {
    setText('ggmListCount', state.messages.length);
  }

  async function openMessage(messageId) {
    if (!messageId) return;

    const { ok, data } = await Http.get(`${window.GGMAIL_ROUTES.messageBase}/${encodeURIComponent(messageId)}`);

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible d ouvrir ce message.');
      return;
    }

    state.currentMessage = data.data || null;
    renderCurrentMessage();
    renderMessages();
  }

  async function toggleRead() {
    const msg = state.currentMessage;
    if (!msg?.message_id) return;

    const route = msg.is_read ? 'mark-unread' : 'mark-read';
    const { ok, data } = await Http.post(`${window.GGMAIL_ROUTES.messageBase}/${encodeURIComponent(msg.message_id)}/${route}`, {});

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de changer le statut lu/non lu.');
      return;
    }

    state.currentMessage = data.data;
    Toast.success('Succes', data.message || 'Statut mis a jour.');
    syncCurrentMessageIntoList();
    renderCurrentMessage();
    renderMessages();
    if (!isSocketReady()) {
      loadSnapshot(true, { silent: true, silentNewMail: true });
    }
  }

  async function toggleStar() {
    const msg = state.currentMessage;
    if (!msg?.message_id) return;

    const route = msg.is_starred ? 'unstar' : 'star';
    const { ok, data } = await Http.post(`${window.GGMAIL_ROUTES.messageBase}/${encodeURIComponent(msg.message_id)}/${route}`, {});

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de mettre a jour le favori.');
      return;
    }

    state.currentMessage = data.data;
    Toast.success('Succes', data.message || 'Favori mis a jour.');
    syncCurrentMessageIntoList();
    renderCurrentMessage();
    renderMessages();
    if (!isSocketReady()) {
      loadSnapshot(true, { silent: true, silentNewMail: true });
    }
  }

  async function archiveMessage() {
    const msg = state.currentMessage;
    if (!msg?.message_id) return;

    const { ok, data } = await Http.post(`${window.GGMAIL_ROUTES.messageBase}/${encodeURIComponent(msg.message_id)}/archive`, {});

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible d archiver ce message.');
      return;
    }

    Toast.success('Succes', data.message || 'Message archive.');
    state.currentMessage = data.data;
    removeMessageFromList(msg.message_id);
    showEmptyView();
    renderMessages();
    updateVisibleMessageCount();
    if (!isSocketReady()) {
      loadSnapshot(true, { silent: true, silentNewMail: true });
    }
  }

  async function trashOrDeleteMessage() {
    const msg = state.currentMessage;
    if (!msg?.message_id) return;

    const isTrash = (msg.label_ids || []).includes('TRASH');

    if (isTrash) {
      Modal.confirm({
        title: 'Supprimer definitivement ?',
        message: 'Ce message sera supprime de Gmail de facon irreversible.',
        confirmText: 'Supprimer',
        type: 'danger',
        onConfirm: async () => {
          const res = await Http.delete(`${window.GGMAIL_ROUTES.messageBase}/${encodeURIComponent(msg.message_id)}`);
          if (!res.ok || !res.data.success) {
            Toast.error('Erreur', res.data.message || 'Suppression impossible.');
            return;
          }
          Toast.success('Supprime', res.data.message || 'Message supprime.');
          removeMessageFromList(msg.message_id);
          showEmptyView();
          renderMessages();
          updateVisibleMessageCount();
          if (!isSocketReady()) {
            loadSnapshot(true, { silent: true, silentNewMail: true });
          }
        },
      });
      return;
    }

    const { ok, data } = await Http.post(`${window.GGMAIL_ROUTES.messageBase}/${encodeURIComponent(msg.message_id)}/trash`, {});

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de deplacer le message en corbeille.');
      return;
    }

    Toast.success('Succes', data.message || 'Message deplace en corbeille.');
    removeMessageFromList(msg.message_id);
    showEmptyView();
    renderMessages();
    updateVisibleMessageCount();
    if (!isSocketReady()) {
      loadSnapshot(true, { silent: true, silentNewMail: true });
    }
  }

  async function sendCompose() {
    const btn = document.getElementById('ggmComposeSendBtn');
    const form = document.getElementById('ggmComposeForm');
    if (!form) return;

    const signatureMode = document.getElementById('ggmComposeSignatureMode')?.value || 'auto';
    applyComposeSignatureMode(signatureMode, { silent: true });

    if (!validateAttachmentStore('ggmComposeAttachments')) return;
    const fd = buildMessageFormData(form, 'ggmComposeBodyEditor', 'ggmComposeBody', 'ggmComposeAttachments');
    const to = String(fd.get('to') || '').trim();
    const subject = String(fd.get('subject') || '').trim();
    const bodyHtml = String(fd.get('body_html') || '').trim();
    const bodyText = String(fd.get('body_text') || '').trim();

    if (!to || !subject) {
      Toast.error('Validation', 'Les champs A et Sujet sont obligatoires.');
      return;
    }

    if (!bodyHtml && !bodyText) {
      Toast.error('Validation', 'Le message est obligatoire.');
      return;
    }

    CrmForm.clearErrors(form);
    CrmForm.setLoading(btn, true);
    const { ok, status, data } = await Http.post(window.GGMAIL_ROUTES.send, fd);
    CrmForm.setLoading(btn, false);

    if (!ok || !data.success) {
      if (status === 422 && data.errors) {
        CrmForm.showErrors(form, data.errors);
      }
      Toast.error('Erreur', data.message || 'Impossible d envoyer le message.');
      return;
    }

    Toast.success('Succes', data.message || 'Email envoye.');
    resetFormUi({
      formId: 'ggmComposeForm',
      editorId: 'ggmComposeBodyEditor',
      inputId: 'ggmComposeBody',
      tagIds: ['ggmComposeTo', 'ggmComposeCc', 'ggmComposeBcc'],
      attachmentInputId: 'ggmComposeAttachments',
      attachmentListId: 'ggmComposeAttachmentsList',
    });
    Modal.close(document.getElementById('ggmComposeModal'));
    if (!isSocketReady()) {
      refreshAll(true, { silent: true });
    }
  }

  async function sendReply() {
    const msg = state.currentMessage;
    if (!msg?.message_id) return;

    const btn = document.getElementById('ggmReplySendBtn');
    const form = document.getElementById('ggmReplyForm');
    if (!form) return;

    if (!validateAttachmentStore('ggmReplyAttachments')) return;
    const fd = buildMessageFormData(form, 'ggmReplyBodyEditor', 'ggmReplyBody', 'ggmReplyAttachments');
    const bodyHtml = String(fd.get('body_html') || '').trim();
    const bodyText = String(fd.get('body_text') || '').trim();

    if (!bodyHtml && !bodyText) {
      Toast.error('Validation', 'Le corps de la reponse est obligatoire.');
      return;
    }

    CrmForm.clearErrors(form);
    CrmForm.setLoading(btn, true);
    const { ok, status, data } = await Http.post(`${window.GGMAIL_ROUTES.messageBase}/${encodeURIComponent(msg.message_id)}/reply`, fd);
    CrmForm.setLoading(btn, false);

    if (!ok || !data.success) {
      if (status === 422 && data.errors) {
        CrmForm.showErrors(form, data.errors);
      }
      Toast.error('Erreur', data.message || 'Impossible d envoyer la reponse.');
      return;
    }

    Toast.success('Succes', data.message || 'Reponse envoyee.');
    resetFormUi({
      formId: 'ggmReplyForm',
      editorId: 'ggmReplyBodyEditor',
      inputId: 'ggmReplyBody',
      tagIds: ['ggmReplyCc', 'ggmReplyBcc'],
      attachmentInputId: 'ggmReplyAttachments',
      attachmentListId: 'ggmReplyAttachmentsList',
    });
    Modal.close(document.getElementById('ggmReplyModal'));
    if (!isSocketReady()) {
      refreshAll(true, { silent: true });
    }
  }

  async function sendForward() {
    const msg = state.currentMessage;
    if (!msg?.message_id) return;

    const btn = document.getElementById('ggmForwardSendBtn');
    const form = document.getElementById('ggmForwardForm');
    if (!form) return;

    if (!validateAttachmentStore('ggmForwardAttachments')) return;
    const fd = buildMessageFormData(form, 'ggmForwardBodyEditor', 'ggmForwardBody', 'ggmForwardAttachments');
    const to = String(fd.get('to') || '').trim();

    if (!to) {
      Toast.error('Validation', 'Le destinataire est obligatoire.');
      return;
    }

    CrmForm.clearErrors(form);
    CrmForm.setLoading(btn, true);
    const { ok, status, data } = await Http.post(`${window.GGMAIL_ROUTES.messageBase}/${encodeURIComponent(msg.message_id)}/forward`, fd);
    CrmForm.setLoading(btn, false);

    if (!ok || !data.success) {
      if (status === 422 && data.errors) {
        CrmForm.showErrors(form, data.errors);
      }
      Toast.error('Erreur', data.message || 'Impossible de transferer ce message.');
      return;
    }

    Toast.success('Succes', data.message || 'Email transfere.');
    resetFormUi({
      formId: 'ggmForwardForm',
      editorId: 'ggmForwardBodyEditor',
      inputId: 'ggmForwardBody',
      tagIds: ['ggmForwardTo', 'ggmForwardCc', 'ggmForwardBcc'],
      attachmentInputId: 'ggmForwardAttachments',
      attachmentListId: 'ggmForwardAttachmentsList',
    });
    Modal.close(document.getElementById('ggmForwardModal'));
    if (!isSocketReady()) {
      refreshAll(true, { silent: true });
    }
  }

  async function disconnect() {
    Modal.confirm({
      title: 'Deconnecter Google Gmail ?',
      message: 'Les tokens OAuth Gmail seront supprimes pour ce tenant.',
      confirmText: 'Deconnecter',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.post(window.GGMAIL_ROUTES.disconnect, {});
        if (!ok || !data.success) {
          Toast.error('Erreur', data.message || 'Impossible de deconnecter Google Gmail.');
          return;
        }

        Toast.success('Deconnecte', data.message || 'Google Gmail deconnecte.');
        setTimeout(() => window.location.reload(), 700);
      },
    });
  }

  function renderCurrentMessage() {
    const msg = state.currentMessage;
    if (!msg) {
      showEmptyView();
      return;
    }

    document.getElementById('ggmEmptyState').style.display = 'none';
    document.getElementById('ggmMessageView').style.display = '';

    setText('ggmMessageSubject', msg.subject || '(Sans objet)');
    setText('ggmMessageFrom', msg.from || '-');
    setText('ggmMessageDate', humanDate(msg.sent_at || msg.internal_date));
    setText('ggmMessageTo', (msg.to || []).join(', ') || '-');
    setText('ggmMessageCc', (msg.cc || []).join(', ') || '-');

    const toggleReadBtn = document.getElementById('ggmToggleReadBtn');
    if (toggleReadBtn) {
      toggleReadBtn.innerHTML = msg.is_read
        ? '<i class="fas fa-envelope"></i> Marquer non lu'
        : '<i class="fas fa-envelope-open"></i> Marquer lu';
    }

    const toggleStarBtn = document.getElementById('ggmToggleStarBtn');
    if (toggleStarBtn) {
      toggleStarBtn.innerHTML = msg.is_starred
        ? '<i class="fas fa-star"></i> Retirer favori'
        : '<i class="far fa-star"></i> Ajouter favori';
    }

    const bodyWrap = document.getElementById('ggmMessageBody');
    if (bodyWrap) {
      if ((msg.body_html || '').trim() !== '') {
        bodyWrap.innerHTML = '<iframe id="ggmBodyFrame" class="ggm-body-frame" sandbox="allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation"></iframe>';
        const frame = document.getElementById('ggmBodyFrame');
        if (frame) {
          frame.srcdoc = buildSafeEmailHtml(msg.body_html || '');
        }
      } else {
        bodyWrap.innerHTML = `<pre class="ggm-body-plain">${esc(msg.body_text || msg.snippet || '')}</pre>`;
      }
    }

    renderAttachments(msg);
  }

  function renderAttachments(msg) {
    const wrap = document.getElementById('ggmAttachmentsWrap');
    const list = document.getElementById('ggmAttachmentsList');
    if (!wrap || !list) return;

    const attachments = msg.attachments || [];

    if (!attachments.length) {
      wrap.style.display = 'none';
      list.innerHTML = '';
      return;
    }

    wrap.style.display = '';
    list.innerHTML = attachments.map((att) => {
      const url = `${window.GGMAIL_ROUTES.messageBase}/${encodeURIComponent(msg.message_id)}/attachments/${encodeURIComponent(att.attachment_id)}/download`;
      const fileName = att.filename || t('attachment', 'piece-jointe');
      const mime = att.mime_type || '';
      return `
        <div class="ggm-attachment-item">
          <i class="fas fa-paperclip"></i>
          <span>${esc(fileName)}</span>
          <small>${formatBytes(att.size || 0)}</small>
          <button type="button" class="ggm-attachment-action" data-attachment-preview="1" data-message-id="${esc(msg.message_id)}" data-attachment-id="${esc(att.attachment_id)}" data-file-name="${esc(fileName)}" data-mime-type="${esc(mime)}">${esc(t('preview', 'Previsualiser'))}</button>
          <button type="button" class="ggm-attachment-action" data-attachment-download="1" data-message-id="${esc(msg.message_id)}" data-attachment-id="${esc(att.attachment_id)}" data-file-name="${esc(fileName)}" data-mime-type="${esc(mime)}">${esc(t('download', 'Telecharger'))}</button>
          <a class="ggm-attachment-link-fallback" href="${esc(url)}" download="${esc(fileName)}" target="_blank" rel="noopener noreferrer">fallback</a>
        </div>`;
    }).join('');

    list.querySelectorAll('[data-attachment-download]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        await runWithButtonLoading(btn, t('download_loading', 'Telechargement...'), async () => {
          await downloadAttachment({
            messageId: btn.getAttribute('data-message-id') || '',
            attachmentId: btn.getAttribute('data-attachment-id') || '',
            fileName: btn.getAttribute('data-file-name') || '',
            mimeType: btn.getAttribute('data-mime-type') || '',
          });
        });
      });
    });

    list.querySelectorAll('[data-attachment-preview]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        await runWithButtonLoading(btn, t('preview_loading', 'Previsualisation...'), async () => {
          await previewAttachment({
            messageId: btn.getAttribute('data-message-id') || '',
            attachmentId: btn.getAttribute('data-attachment-id') || '',
            fileName: btn.getAttribute('data-file-name') || '',
            mimeType: btn.getAttribute('data-mime-type') || '',
          });
        });
      });
    });
  }

  async function runWithButtonLoading(button, loadingText, callback) {
    if (!button || typeof callback !== 'function') {
      return;
    }

    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.classList.add('is-loading');
    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${esc(loadingText || '...')}`;

    try {
      await callback();
    } finally {
      button.disabled = false;
      button.classList.remove('is-loading');
      button.innerHTML = originalHtml;
    }
  }

  async function downloadAttachment(meta) {
    try {
      const file = await fetchAttachmentBlob(meta);
      const objectUrl = URL.createObjectURL(file.blob);
      const a = document.createElement('a');
      a.href = objectUrl;
      a.download = file.fileName || meta.fileName || 'attachment';
      a.rel = 'noopener';
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
    } catch (error) {
      Toast.error(t('error', 'Erreur'), error?.message || t('download_error', 'Impossible de telecharger la piece jointe.'));
    }
  }

  async function previewAttachment(meta) {
    try {
      const file = await fetchAttachmentBlob(meta);
      const title = file.fileName || meta.fileName || t('attachment', 'piece-jointe');
      const body = document.getElementById('ggmAttachmentPreviewBody');
      const titleEl = document.getElementById('ggmAttachmentPreviewTitle');
      if (!body || !titleEl) return;

      clearAttachmentPreviewBody();
      titleEl.textContent = title;

      const mime = String(file.mime || meta.mimeType || '').toLowerCase();
      const objectUrl = URL.createObjectURL(file.blob);
      body.dataset.previewUrl = objectUrl;

      if (mime.startsWith('image/')) {
        body.innerHTML = `<img src="${esc(objectUrl)}" alt="${esc(title)}" class="ggm-preview-image">`;
      } else if (mime === 'application/pdf') {
        body.innerHTML = `<iframe src="${esc(objectUrl)}" class="ggm-preview-frame" title="${esc(title)}"></iframe>`;
      } else if (mime.startsWith('video/')) {
        body.innerHTML = `<video src="${esc(objectUrl)}" class="ggm-preview-media" controls></video>`;
      } else if (mime.startsWith('audio/')) {
        body.innerHTML = `<audio src="${esc(objectUrl)}" class="ggm-preview-media" controls></audio>`;
      } else if (isTextLikeMime(mime)) {
        const text = await file.blob.text();
        body.innerHTML = `<pre class="ggm-preview-text">${esc(text)}</pre>`;
      } else {
        URL.revokeObjectURL(objectUrl);
        delete body.dataset.previewUrl;
        body.innerHTML = `
          <div class="ggm-preview-unsupported">
            <i class="fas fa-file-circle-question"></i>
            <p>${esc(t('preview_not_supported', 'Previsualisation non supportee pour ce fichier par votre navigateur.'))}</p>
            <button type="button" class="btn btn-primary btn-sm" id="ggmPreviewDownloadBtn"><i class="fas fa-download"></i> ${esc(t('download', 'Telecharger'))}</button>
          </div>`;
        document.getElementById('ggmPreviewDownloadBtn')?.addEventListener('click', async (event) => {
          await runWithButtonLoading(event.currentTarget, t('download_loading', 'Telechargement...'), async () => {
            await downloadAttachment(meta);
          });
        });
      }

      Modal.open(document.getElementById('ggmAttachmentPreviewModal'));
    } catch (error) {
      Toast.error(t('error', 'Erreur'), error?.message || t('preview_error', 'Impossible de previsualiser la piece jointe.'));
    }
  }

  async function fetchAttachmentBlob(meta) {
    const messageId = String(meta?.messageId || '').trim();
    const attachmentId = String(meta?.attachmentId || '').trim();

    if (!messageId || !attachmentId) {
      throw new Error(t('download_error', 'Impossible de telecharger la piece jointe.'));
    }

    const url = `${window.GGMAIL_ROUTES.messageBase}/${encodeURIComponent(messageId)}/attachments/${encodeURIComponent(attachmentId)}/download`;
    const response = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok) {
      let message = t('download_error', 'Impossible de telecharger la piece jointe.');
      try {
        const data = await response.json();
        if (data?.message) {
          message = data.message;
        }
        const reconnectTarget = window.CrmAuth?.resolveReconnectRedirect?.(data?.message, data);
        if (reconnectTarget) {
          window.CrmAuth.redirectToReconnect(
            data?.message || t('download_error', 'Impossible de telecharger la piece jointe.'),
            reconnectTarget
          );
        }
      } catch (_err) {
        // no-op
      }
      throw new Error(message);
    }

    const blob = await response.blob();
    const contentType = response.headers.get('Content-Type') || '';
    const disposition = response.headers.get('Content-Disposition') || '';
    const fileName = extractFilenameFromDisposition(disposition) || String(meta?.fileName || t('attachment', 'piece-jointe'));
    const mime = await resolveAttachmentMime(blob, contentType, fileName, String(meta?.mimeType || ''));

    return { blob, mime, fileName };
  }

  async function resolveAttachmentMime(blob, headerMime, fileName, hintedMime) {
    let mime = normalizeMime(headerMime) || normalizeMime(blob.type) || normalizeMime(hintedMime) || '';

    if (isGenericMime(mime)) {
      const byName = inferMimeFromFilename(fileName);
      if (byName) {
        mime = byName;
      }
    }

    if (isGenericMime(mime)) {
      const buf = new Uint8Array(await blob.slice(0, 32).arrayBuffer());
      const bySig = inferMimeFromSignature(buf);
      if (bySig) {
        mime = bySig;
      }
    }

    return mime || 'application/octet-stream';
  }

  function normalizeMime(value) {
    const mime = String(value || '').trim().toLowerCase();
    if (!mime) return '';
    return mime.split(';')[0].trim();
  }

  function isGenericMime(mime) {
    const value = normalizeMime(mime);
    return !value || value === 'application/octet-stream' || value === 'binary/octet-stream';
  }

  function inferMimeFromFilename(fileName) {
    const name = String(fileName || '').toLowerCase();
    if (!name.includes('.')) return '';

    const ext = name.split('.').pop();
    const map = {
      pdf: 'application/pdf',
      png: 'image/png',
      jpg: 'image/jpeg',
      jpeg: 'image/jpeg',
      gif: 'image/gif',
      webp: 'image/webp',
      bmp: 'image/bmp',
      svg: 'image/svg+xml',
      txt: 'text/plain',
      csv: 'text/csv',
      json: 'application/json',
      xml: 'application/xml',
      html: 'text/html',
      mp4: 'video/mp4',
      mp3: 'audio/mpeg',
      wav: 'audio/wav',
      ogg: 'audio/ogg',
      webm: 'video/webm',
    };

    return map[ext] || '';
  }

  function inferMimeFromSignature(bytes) {
    if (!bytes || !bytes.length) return '';
    const has = (...arr) => arr.every((value, index) => bytes[index] === value);

    if (has(0x25, 0x50, 0x44, 0x46)) return 'application/pdf';
    if (has(0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a)) return 'image/png';
    if (has(0xff, 0xd8, 0xff)) return 'image/jpeg';
    if (has(0x47, 0x49, 0x46, 0x38)) return 'image/gif';
    if (has(0x52, 0x49, 0x46, 0x46) && bytes[8] === 0x57 && bytes[9] === 0x45 && bytes[10] === 0x42 && bytes[11] === 0x50) return 'image/webp';
    if (has(0x49, 0x44, 0x33)) return 'audio/mpeg';
    if (has(0x52, 0x49, 0x46, 0x46) && bytes[8] === 0x57 && bytes[9] === 0x41 && bytes[10] === 0x56 && bytes[11] === 0x45) return 'audio/wav';
    if (bytes[4] === 0x66 && bytes[5] === 0x74 && bytes[6] === 0x79 && bytes[7] === 0x70) return 'video/mp4';

    return '';
  }

  function extractFilenameFromDisposition(disposition) {
    if (!disposition) return '';

    const utf8Match = disposition.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
    if (utf8Match?.[1]) {
      try {
        return decodeURIComponent(utf8Match[1]);
      } catch (_err) {
        return utf8Match[1];
      }
    }

    const plainMatch = disposition.match(/filename\s*=\s*\"?([^\";]+)\"?/i);
    return plainMatch?.[1] ? plainMatch[1].trim() : '';
  }

  function isTextLikeMime(mime) {
    const value = normalizeMime(mime);
    if (!value) return false;
    return value.startsWith('text/')
      || value.includes('json')
      || value.includes('xml')
      || value.includes('javascript');
  }

  function clearAttachmentPreviewBody() {
    const body = document.getElementById('ggmAttachmentPreviewBody');
    if (!body) return;

    if (body.dataset.previewUrl) {
      URL.revokeObjectURL(body.dataset.previewUrl);
      delete body.dataset.previewUrl;
    }

    body.innerHTML = '';
  }

  function buildSafeEmailHtml(rawHtml) {
    const template = document.createElement('template');
    template.innerHTML = String(rawHtml || '');

    template.content.querySelectorAll('script,iframe,object,embed,base,meta[http-equiv],link[rel="import"]').forEach((node) => node.remove());

    template.content.querySelectorAll('*').forEach((node) => {
      Array.from(node.attributes || []).forEach((attr) => {
        const name = String(attr.name || '').toLowerCase();
        if (name.startsWith('on')) {
          node.removeAttribute(attr.name);
        }
      });

      if (node.tagName.toLowerCase() === 'a') {
        const href = String(node.getAttribute('href') || '').trim();
        if (!isSafeHref(href)) {
          node.removeAttribute('href');
        }
        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener noreferrer');
      }
    });

    return `<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <base target="_blank">
  <style>
    html,body{margin:0;padding:12px;font:14px/1.6 Arial,sans-serif;color:#0f172a;background:#fff}
    img{max-width:100%;height:auto}
    table{max-width:100% !important}
    a{color:#1d4ed8}
  </style>
</head>
<body>${template.innerHTML}</body>
</html>`;
  }

  function isSafeHref(href) {
    if (!href) return false;
    const value = href.trim().toLowerCase();
    if (value.startsWith('#')) return true;
    if (value.startsWith('http://') || value.startsWith('https://')) return true;
    if (value.startsWith('mailto:') || value.startsWith('tel:')) return true;
    return false;
  }

  function goNextPage() {
    if (!state.nextToken) return;

    state.prevTokens.push(state.currentToken);
    state.currentToken = state.nextToken;
    loadMessages();
  }

  function goPrevPage() {
    if (!state.prevTokens.length) return;

    state.currentToken = state.prevTokens.pop() || null;
    loadMessages();
  }

  function updatePagerButtons() {
    const prev = document.getElementById('ggmPrevPageBtn');
    const next = document.getElementById('ggmNextPageBtn');
    if (prev) prev.disabled = state.prevTokens.length === 0;
    if (next) next.disabled = !state.nextToken;
  }

  function resetPagination() {
    state.currentToken = null;
    state.nextToken = null;
    state.prevTokens = [];
    updatePagerButtons();
  }

  function showEmptyView() {
    state.currentMessage = null;
    document.getElementById('ggmEmptyState').style.display = '';
    document.getElementById('ggmMessageView').style.display = 'none';
    renderMessages();
  }

  function formatLabelName(label) {
    if (!label) return 'Messages';
    const key = String(label.label_id || '').toUpperCase();
    return LABEL_NAME_MAP[key] || label.name || label.label_id || 'Messages';
  }

  function updateListTitle() {
    const selected = state.labels.find((label) => label.label_id === state.selectedLabel);
    const base = selected ? formatLabelName(selected) : 'Messages';
    const suffix = state.search ? ' (filtre actif)' : '';
    setText('ggmListTitle', `${base}${suffix}`);
  }

  function syncHeaderNotifications(unreadCount) {
    const count = Number(unreadCount || 0);
    const badge = document.getElementById('globalNotifCount');

    if (badge) {
      badge.textContent = count > 99 ? '99+' : String(count);
      badge.style.display = count > 0 ? 'inline-flex' : 'none';
    }

    window.dispatchEvent(new CustomEvent('crm:gmail-unread', {
      detail: {
        unread: count,
      },
    }));
  }

  function isSocketReady() {
    return !!(state.useSocket && state.socket && state.socket.connected);
  }

  function initRealtime() {
    bindRealtimeLifecycle();
    stopFallbackPolling();

    if (!initSocket()) {
      startFallbackPolling();
    }
  }

  function bindRealtimeLifecycle() {
    if (!state.visibilityListenerBound) {
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState !== 'visible' || !state.connected) {
          return;
        }

        refreshAll(true, { silent: true, silentNewMail: true });
      });
      state.visibilityListenerBound = true;
    }

    if (!state.unloadListenerBound) {
      window.addEventListener('beforeunload', () => {
        stopFallbackPolling();

        if (state.socketRefreshTimer) {
          clearTimeout(state.socketRefreshTimer);
          state.socketRefreshTimer = null;
        }

        if (state.socket) {
          state.socket.disconnect();
          state.socket = null;
        }
      });
      state.unloadListenerBound = true;
    }
  }

  function disconnectSocket() {
    if (state.socket) {
      state.socket.disconnect();
      state.socket = null;
    }

    state.useSocket = false;
  }

  function initSocket() {
    const cfg = state.socketConfig || {};

    if (!state.connected || !cfg.enabled || typeof window.io !== 'function') {
      disconnectSocket();
      return false;
    }

    const clientUrl = String(cfg.clientUrl || '').trim();
    if (!clientUrl) {
      disconnectSocket();
      return false;
    }

    const namespace = String(cfg.namespace || '/').trim();
    const target = namespace && namespace !== '/' ? `${clientUrl}${namespace}` : clientUrl;

    disconnectSocket();

    try {
      state.socket = window.io(target, {
        path: cfg.path || '/socket.io',
        transports: Array.isArray(cfg.transports) && cfg.transports.length ? cfg.transports : ['websocket', 'polling'],
      });
    } catch (error) {
      console.warn('[GoogleGmail] Socket init failed:', error);
      disconnectSocket();
      return false;
    }

    state.socket.on('connect', () => {
      state.useSocket = true;
      stopFallbackPolling();
      state.socket.emit('subscribe', {
        tenant_id: state.tenantId,
        module: 'google-gmail',
      });

      if ((Date.now() - Number(state.lastSnapshotAt || 0)) > 5000) {
        refreshAll(true, { silent: true, silentNewMail: true });
      }
    });

    state.socket.on('disconnect', () => {
      state.useSocket = false;
      startFallbackPolling();
    });

    state.socket.on('connect_error', () => {
      state.useSocket = false;
      startFallbackPolling();
    });

    state.socket.on('google-gmail.event', onSocketEvent);
    state.socket.on(`tenant:${state.tenantId}:google-gmail`, onSocketEvent);

    return true;
  }

  function startFallbackPolling() {
    if (!state.connected || isSocketReady()) {
      return;
    }

    stopFallbackPolling();

    const intervalMs = normalizePollingInterval(state.settings.polling_interval_seconds) * 1000;

    state.pollTimer = setInterval(async () => {
      if (document.visibilityState !== 'visible' || state.pollBusy || isSocketReady()) return;

      state.pollBusy = true;
      try {
        if (window.GGMAIL_ROUTES.snapshotData) {
          await loadSnapshot(true, { silent: true, silentNewMail: false, fromPolling: true });
        } else {
          await loadStats(true, { silent: true, silentNewMail: false, fromPolling: true });

          if (state.pollTick % 2 === 0) {
            await loadLabels(true);
          }

          if (!state.search && ['INBOX', 'ALL', 'ANY'].includes((state.selectedLabel || '').toUpperCase())) {
            await loadMessages({ silent: true, background: true });
          }
        }

        state.pollTick += 1;
      } finally {
        state.pollBusy = false;
      }
    }, intervalMs);
  }

  function stopFallbackPolling() {
    if (state.pollTimer) {
      clearInterval(state.pollTimer);
      state.pollTimer = null;
    }

    state.pollBusy = false;
  }

  function scheduleSocketRefresh(options = {}) {
    if (state.socketRefreshTimer) {
      clearTimeout(state.socketRefreshTimer);
    }

    state.socketRefreshTimer = setTimeout(async () => {
      state.socketRefreshTimer = null;

      if (options.labels) {
        await loadLabels(true);
      }

      if (options.stats) {
        await loadStats(true, { silent: true, silentNewMail: true });
      }

      if (options.messages) {
        await loadMessages({ silent: true, background: true });
      }

      if (options.refreshCurrent && state.currentMessage?.message_id) {
        await openMessage(state.currentMessage.message_id);
      }
    }, Number(options.delay || 220));
  }

  function onSocketEvent(packet) {
    if (!packet || Number(packet.tenant_id || 0) !== Number(state.tenantId || 0)) {
      return;
    }

    const type = String(packet.event || '').toLowerCase();
    const payload = packet.payload || {};
    const sameActor = Number(payload.actor_user_id || 0) > 0
      && Number(payload.actor_user_id || 0) === Number(state.userId || 0);

    if (payload.settings && type === 'settings.updated' && !sameActor) {
      state.settings = normalizeSettings(payload.settings || {});
      hydrateSettingsForm();
      renderLabels();
      updateListTitle();
    }

    if (payload.labels) {
      applyLabels(payload.labels);
    }

    if (payload.stats) {
      const allowMailToast = type === 'mailbox.synced' && !sameActor;
      applyStats(payload.stats, { silent: true, silentNewMail: !allowMailToast });
    }

    if (type === 'disconnected') {
      state.connected = false;
      Toast.info('Connexion Gmail', 'La session Gmail a ete deconnectee. L interface va se recharger.');
      window.setTimeout(() => window.location.reload(), 450);
      return;
    }

    if (['mailbox.synced', 'connected'].includes(type)) {
      handleSocketMailboxSynced(payload);
      return;
    }

    if (['message.updated', 'message.sent', 'message.replied', 'message.forwarded'].includes(type)) {
      if (payload.message) {
        handleSocketMessage(payload.message);
      } else {
        scheduleSocketRefresh({ messages: true, refreshCurrent: true });
      }
      return;
    }

    if (type === 'message.deleted') {
      handleSocketDelete(payload.message_id || '');
    }
  }

  function handleSocketMailboxSynced(payload = {}) {
    const selectedUpper = String(state.selectedLabel || '').toUpperCase();
    const payloadLabelUpper = String(payload.selected_label || 'INBOX').toUpperCase();

    if (Array.isArray(payload.messages) && canApplySocketPreview(payloadLabelUpper)) {
      state.messages = payload.messages;
      state.nextToken = payload.next_page_token || null;
      renderMessages();
      updatePagerButtons();
      updateListTitle();
      updateVisibleMessageCount();

      if (state.currentMessage?.message_id && ['INBOX', 'ALL', 'ANY'].includes(selectedUpper)) {
        scheduleSocketRefresh({ refreshCurrent: true, delay: 260 });
      }
      return;
    }

    if (!state.search && ['INBOX', 'ALL', 'ANY'].includes(selectedUpper)) {
      scheduleSocketRefresh({ messages: true, refreshCurrent: !!state.currentMessage });
    }
  }

  function handleSocketMessage(message) {
    const messageId = String(message?.message_id || '').trim();
    if (!messageId) {
      return;
    }

    if (state.currentMessage?.message_id === messageId) {
      state.currentMessage = {
        ...state.currentMessage,
        ...message,
      };
      renderCurrentMessage();
    }

    const idx = state.messages.findIndex((row) => row.message_id === messageId);
    const visible = currentLabelMatchesMessage(message);

    if (idx >= 0) {
      if (visible) {
        state.messages[idx] = {
          ...state.messages[idx],
          ...message,
        };
      } else {
        state.messages.splice(idx, 1);
      }
    } else if (visible && canPrependSocketMessage()) {
      state.messages.unshift(message);
      if (state.messages.length > 25) {
        state.messages = state.messages.slice(0, 25);
      }
    } else if (!visible && state.currentMessage?.message_id === messageId) {
      showEmptyView();
    }

    renderMessages();
    updateVisibleMessageCount();

    if (state.currentMessage?.message_id === messageId) {
      scheduleSocketRefresh({ refreshCurrent: true, delay: 180 });
    }
  }

  function handleSocketDelete(messageId) {
    const normalized = String(messageId || '').trim();
    if (!normalized) {
      return;
    }

    removeMessageFromList(normalized);

    if (state.currentMessage?.message_id === normalized) {
      showEmptyView();
    }

    renderMessages();
    updateVisibleMessageCount();
  }

  function currentLabelMatchesMessage(message) {
    const currentUpper = String(state.selectedLabel || '').toUpperCase();
    if (!message || ['ALL', 'ANY'].includes(currentUpper)) {
      return true;
    }

    const labels = Array.isArray(message.label_ids)
      ? message.label_ids.map((value) => String(value || '').toUpperCase())
      : [];

    return labels.includes(currentUpper);
  }

  function canPrependSocketMessage() {
    return !state.search && !state.currentToken && state.prevTokens.length === 0;
  }

  function canApplySocketPreview(labelUpper) {
    if (!canPrependSocketMessage()) {
      return false;
    }

    const currentUpper = String(state.selectedLabel || '').toUpperCase();
    return currentUpper === String(labelUpper || '').toUpperCase()
      || (String(labelUpper || '').toUpperCase() === 'INBOX' && ['ALL', 'ANY'].includes(currentUpper));
  }

  function initEditors() {
    document.querySelectorAll('[data-editor-root]').forEach((root) => {
      const editorId = root.getAttribute('data-editor-id');
      const inputId = root.getAttribute('data-input-id');
      if (!editorId || !inputId) return;

      const editor = document.getElementById(editorId);
      const hiddenInput = document.getElementById(inputId);
      if (!editor || !hiddenInput) return;

      editor.addEventListener('input', () => syncEditorToInput(editor, hiddenInput));
      editor.addEventListener('blur', () => syncEditorToInput(editor, hiddenInput));

      root.querySelectorAll('[data-cmd]').forEach((control) => {
        const cmd = control.getAttribute('data-cmd');
        if (!cmd) return;

        if (control.tagName === 'INPUT' && control.type === 'color') {
          control.addEventListener('input', () => runEditorCommand(editor, cmd, control.value));
        } else {
          control.addEventListener('click', () => {
            if (cmd === 'createLink') {
              const link = window.prompt('Entrez l URL du lien', 'https://');
              if (!link) return;
              runEditorCommand(editor, cmd, link);
              return;
            }

            runEditorCommand(editor, cmd);
          });
        }
      });

      root.querySelectorAll('[data-editor-action]').forEach((control) => {
        const action = control.getAttribute('data-editor-action');
        if (!action) return;

        control.addEventListener('click', () => {
          if (action === 'attach-file') {
            const targetInputId = control.getAttribute('data-input-target') || root.getAttribute('data-attachment-input');
            const targetInput = targetInputId ? document.getElementById(targetInputId) : null;
            if (!targetInput) {
              Toast.error('Erreur', 'Champ de piece jointe introuvable.');
              return;
            }
            targetInput.click();
            return;
          }

          if (action === 'insert-signature') {
            if (insertSignatureIntoEditor(editor, { silentDuplicate: false, silentMissing: false })) {
              const composeSignatureMode = document.getElementById('ggmComposeSignatureMode');
              if (composeSignatureMode && editor.id === 'ggmComposeBodyEditor') {
                composeSignatureMode.value = 'with_signature';
              }
            }
          }
        });
      });

      syncEditorToInput(editor, hiddenInput);
    });
  }

  function runEditorCommand(editor, command, value = null) {
    editor.focus();
    document.execCommand(command, false, value);

    const root = editor.closest('[data-editor-root]');
    if (!root) return;

    const inputId = root.getAttribute('data-input-id');
    if (!inputId) return;

    const hiddenInput = document.getElementById(inputId);
    if (hiddenInput) {
      syncEditorToInput(editor, hiddenInput);
    }
  }

  function syncEditorToInput(editor, hiddenInput) {
    if (!editor || !hiddenInput) return;

    editor.querySelectorAll('script').forEach((node) => node.remove());
    hiddenInput.value = (editor.innerHTML || '').trim();
  }

  function insertSignatureIntoEditor(editor, options = {}) {
    if (!editor) return false;

    const silentDuplicate = options.silentDuplicate !== false;
    const silentMissing = options.silentMissing !== false;
    const signature = getSafeSignatureHtml();
    if (!signature) {
      if (!silentMissing) {
        Toast.info('Signature', 'Aucune signature active dans les parametres.');
      }
      return false;
    }

    if (editor.querySelector('.ggm-mail-signature')) {
      if (!silentDuplicate) {
        Toast.info('Signature', 'La signature est deja inseree.');
      }
      return true;
    }

    const normalizedSignature = normalizeComparableHtml(signature);
    const normalizedEditor = normalizeComparableHtml(editor.innerHTML || '');
    if (normalizedSignature && normalizedEditor.includes(normalizedSignature)) {
      if (!silentDuplicate) {
        Toast.info('Signature', 'La signature est deja inseree.');
      }
      return true;
    }

    editor.focus();
    const prefix = (editor.innerHTML || '').trim() ? '<br><br>' : '';
    document.execCommand('insertHTML', false, `${prefix}<div class="ggm-mail-signature">${signature}</div>`);

    const root = editor.closest('[data-editor-root]');
    const inputId = root?.getAttribute('data-input-id');
    if (inputId) {
      const hiddenInput = document.getElementById(inputId);
      if (hiddenInput) {
        syncEditorToInput(editor, hiddenInput);
      }
    }

    return true;
  }

  function removeSignatureFromEditor(editor) {
    if (!editor) return false;

    let removed = false;
    editor.querySelectorAll('.ggm-mail-signature').forEach((node) => {
      node.remove();
      removed = true;
    });

    if (!removed) return false;

    editor.innerHTML = String(editor.innerHTML || '')
      .replace(/^(<br\s*\/?>\s*)+/i, '')
      .replace(/(\s*<br\s*\/?>){3,}/gi, '<br><br>')
      .trim();

    const root = editor.closest('[data-editor-root]');
    const inputId = root?.getAttribute('data-input-id');
    if (inputId) {
      const hiddenInput = document.getElementById(inputId);
      if (hiddenInput) {
        syncEditorToInput(editor, hiddenInput);
      }
    }

    return true;
  }

  function prepareComposeModal() {
    const modeSelect = document.getElementById('ggmComposeSignatureMode');
    if (modeSelect) {
      modeSelect.value = 'auto';
    }
    applyComposeSignatureMode(modeSelect?.value || 'auto', { silent: true });
  }

  function applyComposeSignatureMode(mode = 'auto', options = {}) {
    const editor = document.getElementById('ggmComposeBodyEditor');
    if (!editor) return;

    const normalizedMode = String(mode || 'auto').trim();
    const silent = !!options.silent;

    if (normalizedMode === 'without_signature') {
      removeSignatureFromEditor(editor);
      return;
    }

    if (normalizedMode === 'with_signature') {
      insertSignatureIntoEditor(editor, { silentDuplicate: true, silentMissing: silent });
      return;
    }

    if (state.settings.signature_enabled) {
      insertSignatureIntoEditor(editor, { silentDuplicate: true, silentMissing: true });
    } else {
      removeSignatureFromEditor(editor);
    }
  }

  function getSafeSignatureHtml() {
    if (!state.settings.signature_enabled) return '';

    const raw = String(state.settings.signature_html || '').trim();
    if (!raw) return '';

    const template = document.createElement('template');
    template.innerHTML = raw;
    template.content.querySelectorAll('script').forEach((node) => node.remove());
    return template.innerHTML.trim();
  }

  function normalizeComparableHtml(value) {
    return String(value || '')
      .replace(/\s+/g, ' ')
      .replace(/>\s+</g, '><')
      .trim()
      .toLowerCase();
  }

  function initTagsInputs() {
    const configs = [
      { id: 'ggmComposeTo', validateEmail: true, maxItems: 50 },
      { id: 'ggmComposeCc', validateEmail: true, maxItems: 50 },
      { id: 'ggmComposeBcc', validateEmail: true, maxItems: 50 },
      { id: 'ggmReplyCc', validateEmail: true, maxItems: 50 },
      { id: 'ggmReplyBcc', validateEmail: true, maxItems: 50 },
      { id: 'ggmForwardTo', validateEmail: true, maxItems: 50 },
      { id: 'ggmForwardCc', validateEmail: true, maxItems: 50 },
      { id: 'ggmForwardBcc', validateEmail: true, maxItems: 50 },
      { id: 'ggmSettingsDefaultCc', validateEmail: true, maxItems: 20 },
      { id: 'ggmSettingsDefaultBcc', validateEmail: true, maxItems: 20 },
      { id: 'ggmSettingsMainLabels', validateEmail: false, maxItems: 10, normalize: (v) => String(v || '').trim().toUpperCase() },
    ];

    configs.forEach(initTagsInput);

    setTags('ggmComposeCc', state.settings.default_cc || []);
    setTags('ggmComposeBcc', state.settings.default_bcc || []);
  }

  function initTagsInput(config) {
    const id = config.id;
    const input = document.getElementById(`${id}Input`);
    const hidden = document.getElementById(id);
    const chipsWrap = document.getElementById(`${id}Chips`);
    const container = input?.closest('.ggm-tags');

    if (!input || !hidden || !chipsWrap || !container) return;

    tagsStores[id] = {
      tokens: [],
      validateEmail: !!config.validateEmail,
      normalize: typeof config.normalize === 'function' ? config.normalize : ((value) => sanitizeEmail(value)),
      maxItems: Number(config.maxItems || 999),
      input,
      hidden,
      chipsWrap,
      container,
    };

    const addFromInput = () => {
      addTag(id, input.value);
      input.value = '';
    };

    input.addEventListener('keydown', (event) => {
      if (['Enter', 'Tab', ',', ';'].includes(event.key)) {
        event.preventDefault();
        addFromInput();
      }

      if (event.key === 'Backspace' && !input.value.trim()) {
        const store = tagsStores[id];
        if (store?.tokens?.length) {
          store.tokens.pop();
          syncTagsDom(id);
        }
      }
    });

    input.addEventListener('blur', addFromInput);
    container.addEventListener('click', () => input.focus());
  }

  function addTag(id, rawValue) {
    const store = tagsStores[id];
    if (!store) return;

    const values = splitTagValues(rawValue);
    if (!values.length) return;

    values.forEach((entry) => {
      if (store.tokens.length >= store.maxItems) return;

      const normalized = store.normalize(entry);
      if (!normalized) return;
      if (store.validateEmail && !isValidEmail(normalized)) return;
      if (store.tokens.includes(normalized)) return;

      store.tokens.push(normalized);
    });

    syncTagsDom(id);
  }

  function removeTag(id, token) {
    const store = tagsStores[id];
    if (!store) return;

    store.tokens = store.tokens.filter((item) => item !== token);
    syncTagsDom(id);
  }

  function setTags(id, values) {
    const store = tagsStores[id];
    if (!store) return;

    store.tokens = [];
    const list = Array.isArray(values) ? values : splitTagValues(values);
    list.forEach((value) => addTag(id, value));
    syncTagsDom(id);
  }

  function getTags(id) {
    return [...((tagsStores[id]?.tokens) || [])];
  }

  function getTagsCsv(id) {
    return getTags(id).join(', ');
  }

  function splitTagValues(rawValue) {
    return String(rawValue || '')
      .split(/[;,\n]+/)
      .map((value) => String(value || '').trim())
      .filter(Boolean);
  }

  function syncTagsDom(id) {
    const store = tagsStores[id];
    if (!store) return;

    store.hidden.value = store.tokens.join(', ');
    store.chipsWrap.innerHTML = store.tokens.map((token) => `
      <span class="ggm-tag-chip">
        <span>${esc(token)}</span>
        <button type="button" data-remove-tag="${esc(token)}" aria-label="Retirer">×</button>
      </span>
    `).join('');

    store.chipsWrap.querySelectorAll('[data-remove-tag]').forEach((btn) => {
      btn.addEventListener('click', (event) => {
        event.stopPropagation();
        removeTag(id, btn.getAttribute('data-remove-tag') || '');
      });
    });
  }

  function initAttachmentInputs() {
    bindAttachmentInput('ggmComposeAttachments', 'ggmComposeAttachmentsList');
    bindAttachmentInput('ggmReplyAttachments', 'ggmReplyAttachmentsList');
    bindAttachmentInput('ggmForwardAttachments', 'ggmForwardAttachmentsList');
  }

  function bindAttachmentInput(inputId, listId) {
    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);
    if (!input || !list) return;

    attachmentStores[inputId] = [];

    input.addEventListener('change', () => {
      const selected = Array.from(input.files || []);
      if (!selected.length) return;

      selected.forEach((file) => {
        if (file.size > MAX_ATTACHMENT_SIZE_BYTES) {
          Toast.error('Validation', `${file.name}: taille max ${formatBytes(MAX_ATTACHMENT_SIZE_BYTES)} par fichier.`);
          return;
        }

        const key = `${file.name}-${file.size}-${file.lastModified}`;
        const exists = attachmentStores[inputId].some((item) => item.key === key);
        if (!exists) {
          if (attachmentStores[inputId].length >= MAX_ATTACHMENTS) {
            Toast.error('Validation', `Maximum ${MAX_ATTACHMENTS} pieces jointes autorisees.`);
            return;
          }
          attachmentStores[inputId].push({ key, file });
        }
      });

      input.value = '';
      renderAttachmentStore(inputId, listId);
    });
  }

  function renderAttachmentStore(inputId, listId) {
    const list = document.getElementById(listId);
    if (!list) return;

    const files = attachmentStores[inputId] || [];
    const summaryEl = document.querySelector(`[data-attachment-summary-for="${inputId}"]`);
    if (summaryEl) {
      summaryEl.textContent = files.length
        ? `${files.length} fichier(s) ajoute(s)`
        : 'Aucune piece jointe';
    }

    if (!files.length) {
      list.innerHTML = '';
      return;
    }

    list.innerHTML = files.map((entry) => `
      <div class="ggm-file-chip">
        <i class="fas fa-paperclip"></i>
        <span>${esc(entry.file.name)}</span>
        <small>${formatBytes(entry.file.size)}</small>
        <button type="button" data-remove-file="${esc(entry.key)}" aria-label="Retirer">×</button>
      </div>
    `).join('');

    list.querySelectorAll('[data-remove-file]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const key = btn.getAttribute('data-remove-file') || '';
        attachmentStores[inputId] = (attachmentStores[inputId] || []).filter((entry) => entry.key !== key);
        renderAttachmentStore(inputId, listId);
      });
    });
  }

  function buildMessageFormData(form, editorId, inputId, attachmentInputId) {
    const editor = document.getElementById(editorId);
    const input = document.getElementById(inputId);
    if (editor && input) {
      syncEditorToInput(editor, input);
    }

    const fd = new FormData(form);
    const html = String(fd.get('body_html') || '').trim();
    fd.set('body_html', html);
    fd.set('body_text', stripHtml(html));

    fd.delete('attachments[]');
    fd.delete('attachments');
    (attachmentStores[attachmentInputId] || []).forEach((entry) => {
      fd.append('attachments[]', entry.file);
    });

    return fd;
  }

  function validateAttachmentStore(inputId) {
    const files = attachmentStores[inputId] || [];
    if (files.length > MAX_ATTACHMENTS) {
      Toast.error('Validation', `Maximum ${MAX_ATTACHMENTS} pieces jointes autorisees.`);
      return false;
    }

    let total = 0;
    for (const entry of files) {
      const file = entry?.file;
      if (!file) continue;
      if (file.size > MAX_ATTACHMENT_SIZE_BYTES) {
        Toast.error('Validation', `${file.name}: taille max ${formatBytes(MAX_ATTACHMENT_SIZE_BYTES)} par fichier.`);
        return false;
      }
      total += Number(file.size || 0);
    }

    if (total > MAX_TOTAL_ATTACHMENTS_BYTES) {
      Toast.error('Validation', `Taille totale trop grande (${formatBytes(total)}). Maximum recommande: ${formatBytes(MAX_TOTAL_ATTACHMENTS_BYTES)}.`);
      return false;
    }

    return true;
  }

  function resetFormUi({ formId, editorId, inputId, tagIds = [], attachmentInputId, attachmentListId }) {
    const form = document.getElementById(formId);
    if (form) form.reset();

    const editor = document.getElementById(editorId);
    const input = document.getElementById(inputId);
    if (editor) editor.innerHTML = '';
    if (input) input.value = '';

    tagIds.forEach((id) => setTags(id, []));

    if (attachmentInputId) {
      attachmentStores[attachmentInputId] = [];
    }

    if (attachmentListId) {
      const list = document.getElementById(attachmentListId);
      if (list) list.innerHTML = '';
    }
    if (attachmentInputId && attachmentListId) {
      renderAttachmentStore(attachmentInputId, attachmentListId);
    }

    if (formId === 'ggmComposeForm') {
      setTags('ggmComposeCc', state.settings.default_cc || []);
      setTags('ggmComposeBcc', state.settings.default_bcc || []);
      const modeSelect = document.getElementById('ggmComposeSignatureMode');
      if (modeSelect) {
        modeSelect.value = 'auto';
      }
      applyComposeSignatureMode(modeSelect?.value || 'auto', { silent: true });
    }
  }

  function skeletonItems(count) {
    return Array.from({ length: count }, () => `
      <div class="ggm-mail-item skeleton-item">
        <div class="skeleton" style="height:10px;width:55%;"></div>
        <div class="skeleton" style="height:12px;width:80%;margin-top:8px;"></div>
        <div class="skeleton" style="height:10px;width:96%;margin-top:8px;"></div>
      </div>`).join('');
  }

  function stripHtml(value) {
    const div = document.createElement('div');
    div.innerHTML = value || '';
    return (div.textContent || div.innerText || '').trim();
  }

  function sanitizeEmail(value) {
    let email = String(value || '').trim().toLowerCase();
    const match = email.match(/<([^>]+)>/);
    if (match) email = String(match[1] || '').trim().toLowerCase();
    return email.replace(/^['"]+|['"]+$/g, '');
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
  }

  function humanDate(value) {
    if (!value) return '-';
    const dt = new Date(value);
    if (Number.isNaN(dt.getTime())) return '-';
    return dt.toLocaleString('fr-FR');
  }

  function formatBytes(bytes) {
    const size = Number(bytes || 0);
    if (size <= 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);
    const value = size / (1024 ** i);
    return `${value.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
  }

  function t(key, fallback = '') {
    const bag = state.i18n && typeof state.i18n === 'object' ? state.i18n : {};
    const value = bag[key];
    return typeof value === 'string' && value.trim() !== '' ? value : fallback;
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  return {
    boot,
  };
})();

window.GoogleGmailModule = GoogleGmailModule;
