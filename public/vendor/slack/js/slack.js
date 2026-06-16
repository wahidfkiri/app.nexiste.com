'use strict';

const SlackModule = (() => {
  const state = {
    connected: false,
    tenantId: null,
    selectedChannelId: null,
    page: 1,
    perPage: 40,
    search: '',
    channels: [],
    messages: [],
    loadingMessages: false,
    debounceTimer: null,
    socketConfig: {},
    socket: null,
  };

  function boot(bootstrap = {}) {
    state.connected = !!bootstrap.connected;
    state.selectedChannelId = bootstrap.selectedChannelId || null;
    state.tenantId = Number(bootstrap.tenantId || 0);
    state.socketConfig = bootstrap.socket || {};

    bindActions();

    if (!state.connected) {
      return;
    }

    loadStats();
    loadChannels(true);
    loadMessages(true);
    initSocket();
  }

  function bindActions() {
    document.getElementById('slSyncBtn')?.addEventListener('click', syncNow);
    document.getElementById('slDisconnectBtn')?.addEventListener('click', disconnectSlack);

    const searchInput = document.getElementById('slSearchInput');
    searchInput?.addEventListener('input', () => {
      clearTimeout(state.debounceTimer);
      state.debounceTimer = setTimeout(() => {
        state.search = (searchInput.value || '').trim();
        state.page = 1;
        loadMessages(false);
      }, 300);
    });

    document.getElementById('slResetFilters')?.addEventListener('click', () => {
      state.search = '';
      state.page = 1;
      if (searchInput) searchInput.value = '';
      loadMessages(false);
    });

    const composeForm = document.getElementById('slComposeForm');
    composeForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      await sendMessage();
    });
  }

  function initSocket() {
    const cfg = state.socketConfig || {};
    if (!cfg.enabled || typeof window.io !== 'function') {
      setSocketStatus('Off');
      return;
    }

    const clientUrl = (cfg.clientUrl || '').trim();
    if (!clientUrl) {
      setSocketStatus('Off');
      return;
    }

    const namespace = (cfg.namespace || '/').trim();
    const target = namespace && namespace !== '/' ? `${clientUrl}${namespace}` : clientUrl;

    try {
      state.socket = window.io(target, {
        path: cfg.path || '/socket.io',
        transports: Array.isArray(cfg.transports) && cfg.transports.length ? cfg.transports : ['websocket', 'polling'],
      });
    } catch (err) {
      console.warn('[Slack] Socket init failed:', err);
      setSocketStatus('Erreur');
      return;
    }

    state.socket.on('connect', () => {
      setSocketStatus('Actif');
      state.socket.emit('subscribe', {
        tenant_id: state.tenantId,
        module: 'slack',
      });
    });

    state.socket.on('disconnect', () => setSocketStatus('Hors ligne'));
    state.socket.on('connect_error', () => setSocketStatus('Erreur'));

    state.socket.on('slack.event', onSocketEvent);
    state.socket.on(`tenant:${state.tenantId}:slack`, onSocketEvent);
  }

  function onSocketEvent(event) {
    if (!event || Number(event.tenant_id || 0) !== Number(state.tenantId || 0)) {
      return;
    }

    const type = String(event.event || '').toLowerCase();
    const payload = event.payload || {};

    if (type === 'message.created' && payload.channel_id && payload.message) {
      if (String(payload.channel_id) === String(state.selectedChannelId)) {
        state.messages.unshift(payload.message);
        renderMessages();
      }
      loadStats();
      loadChannels(false);
      return;
    }

    if (type === 'messages.synced') {
      if (!payload.channel_id || String(payload.channel_id) === String(state.selectedChannelId)) {
        loadMessages(false);
      }
      loadStats();
      return;
    }

    if (type === 'channels.synced') {
      loadChannels(false);
      loadStats();
      return;
    }

    if (type === 'disconnected') {
      setSocketStatus('Off');
      return;
    }
  }

  async function loadChannels(refresh = false) {
    const { ok, data } = await Http.get(window.SLACK_ROUTES.channelsData, { refresh: refresh ? 1 : 0 });

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de charger les canaux Slack.');
      return;
    }

    state.channels = data.data || [];

    if (!state.selectedChannelId) {
      const selected = state.channels.find((c) => c.is_selected) || state.channels[0];
      state.selectedChannelId = selected ? selected.channel_id : null;
    }

    renderChannels();
    setText('slStatChannels', state.channels.length);
  }

  function renderChannels() {
    const wrap = document.getElementById('slChannelsList');
    if (!wrap) return;

    if (!state.channels.length) {
      wrap.innerHTML = `
        <div class="sl-empty">
          <i class="fas fa-hashtag"></i>
          <div>Aucun canal disponible</div>
        </div>`;
      return;
    }

    wrap.innerHTML = state.channels.map((channel) => {
      const active = String(channel.channel_id) === String(state.selectedChannelId);
      const icon = channel.is_private ? 'fa-lock' : (channel.is_im ? 'fa-user' : 'fa-hashtag');
      const privateBadge = channel.is_private ? '<span class="sl-channel-badge">Prive</span>' : '';
      const archivedStyle = channel.is_archived ? 'opacity:.5;' : '';

      return `
        <button type="button" class="sl-channel-item ${active ? 'active' : ''}" style="${archivedStyle}" data-sl-channel="${esc(channel.channel_id)}">
          <span class="sl-channel-icon"><i class="fas ${icon}"></i></span>
          <span class="sl-channel-name">${esc(channel.name || channel.channel_id)}</span>
          ${privateBadge}
        </button>`;
    }).join('');

    wrap.querySelectorAll('[data-sl-channel]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const channelId = String(btn.dataset.slChannel || '');
        if (!channelId || channelId === String(state.selectedChannelId)) return;
        selectChannel(channelId);
      });
    });
  }

  async function selectChannel(channelId) {
    const { ok, data } = await Http.post(window.SLACK_ROUTES.selectChannel, { channel_id: channelId });
    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de selectionner le canal.');
      return;
    }

    state.selectedChannelId = channelId;
    state.page = 1;

    const hiddenChannel = document.getElementById('slComposeChannelId');
    if (hiddenChannel) hiddenChannel.value = channelId;

    renderChannels();
    loadMessages(true);
  }

  async function loadMessages(refresh = false) {
    if (state.loadingMessages) return;
    state.loadingMessages = true;

    const list = document.getElementById('slMessagesList');
    if (list) {
      list.innerHTML = skeletonMessages(5);
    }

    const { ok, data } = await Http.get(window.SLACK_ROUTES.messagesData, {
      channel_id: state.selectedChannelId || '',
      search: state.search,
      per_page: state.perPage,
      page: state.page,
      refresh: refresh ? 1 : 0,
    });

    state.loadingMessages = false;

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de charger les messages.');
      if (list) {
        list.innerHTML = `
          <div class="sl-empty"><i class="fas fa-triangle-exclamation"></i><div>Impossible de charger les messages.</div></div>`;
      }
      return;
    }

    state.messages = data.data || [];
    renderMessages();
    renderPagination(data);

    const selected = state.channels.find((c) => String(c.channel_id) === String(state.selectedChannelId));
    setText('slChannelTitle', selected ? `# ${selected.name}` : 'Messages');
    setText('slCount', `${data.total || 0} resultat(s)`);

    const hiddenChannel = document.getElementById('slComposeChannelId');
    if (hiddenChannel) hiddenChannel.value = state.selectedChannelId || '';
  }

  function renderMessages() {
    const list = document.getElementById('slMessagesList');
    if (!list) return;

    if (!state.messages.length) {
      list.innerHTML = `
        <div class="sl-empty">
          <i class="fas fa-comments"></i>
          <div>Aucun message trouve pour ce canal.</div>
        </div>`;
      return;
    }

    list.innerHTML = state.messages.map((message) => {
      const isBot = !!message.is_bot;
      const edited = message.edited_display ? `<span class="sl-message-date">modifie ${esc(message.edited_display)}</span>` : '';
      const botBadge = isBot ? '<span class="sl-message-bot">BOT</span>' : '';
      const safeText = esc(message.text || '');
      const rows = safeText.split('\n').join('<br>');

      return `
        <article class="sl-message-card ${isBot ? 'is-bot' : ''}">
          <div class="sl-message-head">
            <span class="sl-message-author">${esc(message.username || 'Utilisateur')}</span>
            ${botBadge}
            <span class="sl-message-date">${esc(message.sent_display || '-')}</span>
            ${edited}
          </div>
          <div class="sl-message-text">${rows || '-'}</div>
        </article>`;
    }).join('');
  }

  function renderPagination(payload) {
    const wrap = document.getElementById('slPaginationControls');
    const info = document.getElementById('slPaginationInfo');
    if (!wrap) return;

    const currentPage = payload.current_page || 1;
    const lastPage = payload.last_page || 1;

    if (info) {
      info.textContent = `Affichage ${payload.from || 0} a ${payload.to || 0} sur ${payload.total || 0} message(s)`;
    }

    const pages = [];
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(lastPage, currentPage + 2);
    for (let i = start; i <= end; i += 1) pages.push(i);

    wrap.innerHTML = `
      <button class="page-btn" ${currentPage <= 1 ? 'disabled' : ''} data-sl-page="${currentPage - 1}">
        <i class="fas fa-chevron-left"></i>
      </button>
      ${pages.map((p) => `<button class="page-btn ${p === currentPage ? 'active' : ''}" data-sl-page="${p}">${p}</button>`).join('')}
      <button class="page-btn" ${currentPage >= lastPage ? 'disabled' : ''} data-sl-page="${currentPage + 1}">
        <i class="fas fa-chevron-right"></i>
      </button>`;

    wrap.querySelectorAll('[data-sl-page]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const nextPage = parseInt(btn.dataset.slPage, 10);
        if (!Number.isNaN(nextPage) && nextPage > 0 && nextPage !== state.page) {
          state.page = nextPage;
          loadMessages(false);
        }
      });
    });
  }

  async function sendMessage() {
    const channelId = String(state.selectedChannelId || '');
    const textInput = document.getElementById('slComposeText');
    const sendBtn = document.getElementById('slSendBtn');
    if (!textInput || !sendBtn) return;

    const text = (textInput.value || '').trim();
    if (!channelId) {
      Toast.warning('Canal requis', 'Selectionnez un canal avant d envoyer un message.');
      return;
    }
    if (!text) {
      Toast.warning('Message vide', 'Saisissez un message.');
      return;
    }

    CrmForm.setLoading(sendBtn, true);
    const { ok, data } = await Http.post(window.SLACK_ROUTES.messageSend, {
      channel_id: channelId,
      text,
      thread_ts: document.getElementById('slComposeThreadTs')?.value || '',
    });
    CrmForm.setLoading(sendBtn, false);

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible d envoyer le message.');
      return;
    }

    textInput.value = '';
    Toast.success('Succes', data.message || 'Message envoye.');

    if (data.data) {
      state.messages.unshift(data.data);
      renderMessages();
    } else {
      loadMessages(false);
    }

    loadStats();
    loadChannels(false);
  }

  async function loadStats() {
    const { ok, data } = await Http.get(window.SLACK_ROUTES.stats);
    if (!ok || !data.success) return;

    const stats = data.data || {};
    setText('slStatChannels', stats.channels_count || 0);
    setText('slStatToday', stats.messages_today || 0);
    setText('slStatWeek', stats.messages_last_7_days || 0);
    setText('slSocketStatus', stats.socket_enabled ? 'Actif' : 'Off');

    const lastSync = document.getElementById('slLastSync');
    if (lastSync && stats.last_sync_at) {
      lastSync.textContent = formatDate(stats.last_sync_at);
    }
  }

  async function syncNow() {
    const { ok, data } = await Http.post(window.SLACK_ROUTES.sync, {
      channel_id: state.selectedChannelId || '',
    });

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'La synchronisation Slack a echoue.');
      return;
    }

    Toast.success('Synchronisation', data.message || 'Synchronisation terminee.');
    await loadChannels(false);
    await loadMessages(false);
    loadStats();
  }

  async function disconnectSlack() {
    Modal.confirm({
      title: 'Deconnecter Slack',
      message: 'La deconnexion supprimera la session OAuth Slack de ce tenant.',
      confirmText: 'Deconnecter',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.post(window.SLACK_ROUTES.disconnect, {});
        if (!ok || !data.success) {
          Toast.error('Erreur', data.message || 'Impossible de deconnecter Slack.');
          return;
        }
        Toast.success('Succes', data.message || 'Slack deconnecte.');
        window.location.reload();
      },
    });
  }

  function setSocketStatus(label) {
    const el = document.getElementById('slSocketStatus');
    if (!el) return;
    el.textContent = label;
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value ?? '');
  }

  function formatDate(iso) {
    try {
      const d = new Date(iso);
      if (Number.isNaN(d.getTime())) return '-';
      return d.toLocaleString();
    } catch (_) {
      return '-';
    }
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = String(value || '');
    return div.innerHTML;
  }

  function skeletonMessages(rows = 5) {
    return Array.from({ length: rows }).map(() => `
      <article class="sl-message-card">
        <div style="height:11px;width:170px;background:var(--surface-2);border-radius:6px;margin-bottom:8px;"></div>
        <div style="height:10px;width:100%;background:var(--surface-2);border-radius:6px;margin-bottom:5px;"></div>
        <div style="height:10px;width:78%;background:var(--surface-2);border-radius:6px;"></div>
      </article>`).join('');
  }

  return { boot };
})();

window.SlackModule = SlackModule;

