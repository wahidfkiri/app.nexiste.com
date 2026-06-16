'use strict';

const ChatbotModule = (() => {
  const state = {
    enabled: false,
    tenantId: 0,
    userId: 0,
    userName: 'Utilisateur',
    socketConfig: {},
    socket: null,
    rooms: [],
    selectedRoomId: null,
    messages: [],
    perPage: 300,
    roomSearch: '',
    messageSearch: '',
    roomFormMode: 'create',
    pendingFiles: [],
    loadingMessages: false,
    searchTimer: null,
    users: [],
    selectedMemberIds: [],
    openRoomMenuId: null,
    localEchoCreatedIds: new Set(),
    localEchoDeletedIds: new Set(),
    fileChipUrls: [],
    fileRules: {
      maxSizeKb: 10240,
      allowedMimeTypes: [],
      allowedExtensions: [],
      maxFilesPerMessage: 6,
    },
  };

  const EMOJIS = ['😀', '😁', '😂', '🤣', '😊', '😍', '😎', '😇', '😉', '🤝', '👍', '👏', '🙌', '🎉', '🔥', '💡', '✅', '⚡', '🚀', '📌', '📎', '🛠️'];

  function boot(bootstrap = {}) {
    state.enabled = !!bootstrap.enabled;
    state.tenantId = Number(bootstrap.tenantId || 0);
    state.userId = Number(bootstrap.userId || 0);
    state.userName = String(bootstrap.userName || 'Utilisateur');
    state.socketConfig = bootstrap.socket || {};

    const files = bootstrap.files || {};
    const messages = bootstrap.messages || {};
    state.perPage = Math.max(20, Math.min(Number(messages.maxFetch || state.perPage || 300), 1000));
    state.fileRules.maxSizeKb = Number(files.maxSizeKb || 10240);
    state.fileRules.allowedMimeTypes = Array.isArray(files.allowedMimeTypes) ? files.allowedMimeTypes.map((v) => String(v || '').toLowerCase()) : [];
    state.fileRules.allowedExtensions = Array.isArray(files.allowedExtensions) ? files.allowedExtensions.map((v) => String(v || '').toLowerCase().replace(/^\./, '')) : [];

    bindActions();
    initEmojiPanel();
    initRoomFormPickers();

    if (!state.enabled) return;

    loadUsers();
    loadStats();
    loadRooms(true).then(() => loadMessages(true));
    initSocket();
  }

  function bindActions() {
    document.getElementById('cbRefreshBtn')?.addEventListener('click', async () => {
      await Promise.all([loadUsers(), loadStats()]);
      await loadRooms(true);
      await loadMessages(true);
      Toast.success('Succes', 'Chat actualise.');
    });

    document.getElementById('cbNewRoomBtn')?.addEventListener('click', () => openRoomModal('create'));
    document.getElementById('cbEditRoomBtn')?.addEventListener('click', () => openRoomModal('edit'));
    document.getElementById('cbDeleteRoomBtn')?.addEventListener('click', deleteSelectedRoom);

    const roomSearch = document.getElementById('cbRoomSearchInput');
    roomSearch?.addEventListener('input', () => {
      state.roomSearch = String(roomSearch.value || '').trim().toLowerCase();
      renderRooms();
    });

    const messageSearch = document.getElementById('cbMessageSearchInput');
    messageSearch?.addEventListener('input', () => {
      clearTimeout(state.searchTimer);
      state.searchTimer = setTimeout(() => {
        state.messageSearch = String(messageSearch.value || '').trim();
        loadMessages(false);
      }, 260);
    });

    document.getElementById('cbComposeForm')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      await sendMessage();
    });

    document.getElementById('cbFileInput')?.addEventListener('change', (event) => {
      const files = Array.from(event.target?.files || []);
      if (!files.length) return;
      addPendingFiles(files);
      event.target.value = '';
    });

    document.getElementById('cbFileChips')?.addEventListener('click', (event) => {
      const removeBtn = event.target.closest('[data-cb-remove-file]');
      if (!removeBtn) return;
      const idx = Number(removeBtn.dataset.cbRemoveFile || -1);
      if (idx < 0) return;
      state.pendingFiles.splice(idx, 1);
      renderFileChips();
    });

    document.getElementById('cbMessagesList')?.addEventListener('click', (event) => {
      const deleteBtn = event.target.closest('[data-cb-delete-message]');
      if (!deleteBtn) return;
      const id = Number(deleteBtn.dataset.cbDeleteMessage || 0);
      if (id > 0) deleteMessage(id);
    });

    document.getElementById('cbRoomsList')?.addEventListener('click', (event) => {
      const roomBtn = event.target.closest('[data-cb-room-select]');
      if (roomBtn) {
        const roomId = Number(roomBtn.dataset.cbRoomSelect || 0);
        if (roomId && roomId !== Number(state.selectedRoomId)) {
          state.selectedRoomId = roomId;
          state.openRoomMenuId = null;
          renderRooms();
          syncRoomHeader();
          loadMessages(false);
        }
        return;
      }

      const menuToggle = event.target.closest('[data-cb-room-menu-toggle]');
      if (menuToggle) {
        event.preventDefault();
        event.stopPropagation();
        const roomId = Number(menuToggle.dataset.cbRoomMenuToggle || 0);
        if (!roomId) return;
        state.openRoomMenuId = (state.openRoomMenuId === roomId) ? null : roomId;
        renderRooms();
        return;
      }

      const actionBtn = event.target.closest('[data-cb-room-action]');
      if (!actionBtn) return;
      event.preventDefault();
      event.stopPropagation();

      const roomId = Number(actionBtn.dataset.cbRoomId || 0);
      const action = String(actionBtn.dataset.cbRoomAction || '');
      state.openRoomMenuId = null;

      if (!roomId) return;
      if (action === 'edit') openRoomModal('edit', roomId);
      if (action === 'delete') deleteRoomById(roomId);
    });

    document.getElementById('cbRoomForm')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      await saveRoom();
    });

    const memberSearch = document.getElementById('cbMemberSearch');
    memberSearch?.addEventListener('input', () => renderMemberSuggestions(memberSearch.value || ''));
    memberSearch?.addEventListener('focus', () => renderMemberSuggestions(memberSearch.value || ''));

    document.getElementById('cbMemberSuggest')?.addEventListener('click', (event) => {
      const addBtn = event.target.closest('[data-cb-member-add]');
      if (!addBtn) return;
      addMember(Number(addBtn.dataset.cbMemberAdd || 0));
    });

    document.getElementById('cbMemberBadges')?.addEventListener('click', (event) => {
      const removeBtn = event.target.closest('[data-cb-member-remove]');
      if (!removeBtn) return;
      removeMember(Number(removeBtn.dataset.cbMemberRemove || 0));
    });

    document.addEventListener('click', (event) => {
      const inEmoji = event.target.closest('#cbEmojiPanel') || event.target.closest('#cbEmojiBtn');
      if (!inEmoji) document.getElementById('cbEmojiPanel')?.classList.add('hidden');

      if (!event.target.closest('.cb-room-actions') && state.openRoomMenuId !== null) {
        state.openRoomMenuId = null;
        renderRooms();
      }

      if (!event.target.closest('.cb-member-picker')) hideMemberSuggestions();
    });
  }

  async function loadUsers() {
    const { ok, data } = await Http.get(window.CHATBOT_ROUTES.usersData);
    if (!ok || !data.success) return;
    state.users = Array.isArray(data.data) ? data.data : [];
  }

  function initEmojiPanel() {
    const panel = document.getElementById('cbEmojiPanel');
    if (!panel) return;

    panel.innerHTML = EMOJIS.map((emoji) => `<button type="button" class="cb-emoji-item" data-cb-emoji="${emoji}">${emoji}</button>`).join('');
    panel.classList.add('hidden');

    document.getElementById('cbEmojiBtn')?.addEventListener('click', () => panel.classList.toggle('hidden'));

    panel.addEventListener('click', (event) => {
      const target = event.target.closest('[data-cb-emoji]');
      if (!target) return;
      const emoji = String(target.dataset.cbEmoji || '');
      const textarea = document.getElementById('cbComposeText');
      if (!textarea) return;
      const start = textarea.selectionStart || textarea.value.length;
      const end = textarea.selectionEnd || textarea.value.length;
      textarea.value = `${textarea.value.slice(0, start)}${emoji}${textarea.value.slice(end)}`;
      textarea.focus();
      textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
    });
  }

  function initRoomFormPickers() {
    document.querySelectorAll('[data-cb-icon-choice]').forEach((button) => {
      button.addEventListener('click', () => selectRoomIcon(String(button.dataset.cbIconChoice || 'fa-comments')));
    });

    document.querySelectorAll('[data-cb-color-choice]').forEach((button) => {
      button.addEventListener('click', () => selectRoomColor(String(button.dataset.cbColorChoice || '#0ea5e9'), true));
    });

    document.getElementById('cbRoomColorCustom')?.addEventListener('input', (event) => {
      selectRoomColor(String(event.target?.value || '#0ea5e9'), false);
    });
  }

  function selectRoomIcon(icon) {
    const hidden = document.getElementById('cbRoomIcon');
    if (hidden) hidden.value = icon;

    document.querySelectorAll('[data-cb-icon-choice]').forEach((button) => {
      button.classList.toggle('active', String(button.dataset.cbIconChoice || '') === icon);
    });
  }

  function selectRoomColor(color, fromPalette) {
    document.getElementById('cbRoomColor').value = color;
    document.getElementById('cbRoomColorCustom').value = color;
    const label = document.getElementById('cbRoomColorLabel');
    if (label) label.textContent = color;

    document.querySelectorAll('[data-cb-color-choice]').forEach((button) => {
      const isCurrent = String(button.dataset.cbColorChoice || '').toLowerCase() === color.toLowerCase();
      button.classList.toggle('active', fromPalette && isCurrent);
      if (!fromPalette) button.classList.remove('active');
    });
  }

  async function loadRooms(refresh = false) {
    const { ok, data } = await Http.get(window.CHATBOT_ROUTES.roomsData, { refresh: refresh ? 1 : 0 });
    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de charger les salons.');
      return;
    }

    state.rooms = Array.isArray(data.data) ? data.data : [];

    if (!state.selectedRoomId || !state.rooms.find((room) => Number(room.id) === Number(state.selectedRoomId))) {
      state.selectedRoomId = state.rooms.length ? Number(state.rooms[0].id) : null;
    }

    renderRooms();
    syncRoomHeader();
  }

  function renderRooms() {
    const wrap = document.getElementById('cbRoomsList');
    if (!wrap) return;

    const rows = state.rooms.filter((room) => {
      if (!state.roomSearch) return true;
      return String(room.name || '').toLowerCase().includes(state.roomSearch)
        || String(room.description || '').toLowerCase().includes(state.roomSearch);
    });

    if (!rows.length) {
      wrap.innerHTML = `<div class="cb-empty"><i class="fas fa-comments"></i><div>Aucun salon trouve.</div></div>`;
      return;
    }

    wrap.innerHTML = rows.map((room) => {
      const active = Number(room.id) === Number(state.selectedRoomId);
      const iconClass = normalizeIcon(room.icon || 'fa-comments');
      const privateBadge = room.is_private ? '<span class="cb-room-badge">Prive</span>' : '';
      const menuOpen = Number(state.openRoomMenuId) === Number(room.id);
      const membersCount = Number(room.members_count || 0);

      return `
        <div class="cb-room-item ${active ? 'active' : ''}">
          <button type="button" class="cb-room-select" data-cb-room-select="${Number(room.id)}">
            <span class="cb-room-icon" style="background:${esc(room.color || '#0ea5e9')}"><i class="${iconClass}"></i></span>
            <span class="cb-room-main">
              <span class="cb-room-name">${esc(room.name || 'Salon')}</span>
              <span class="cb-room-meta">${Number(room.messages_count || 0)} message(s) • ${membersCount} membre(s)</span>
            </span>
          </button>
          ${privateBadge}
          <div class="cb-room-actions">
            <button type="button" class="cb-room-more" data-cb-room-menu-toggle="${Number(room.id)}" title="Parametres salon">
              <i class="fas fa-ellipsis"></i>
            </button>
            <div class="cb-room-menu ${menuOpen ? 'open' : ''}">
              <button type="button" data-cb-room-action="edit" data-cb-room-id="${Number(room.id)}"><i class="fas fa-sliders"></i> Personnaliser le salon</button>
              ${room.is_default ? '' : `<button type="button" class="is-danger" data-cb-room-action="delete" data-cb-room-id="${Number(room.id)}"><i class="fas fa-trash"></i> Supprimer</button>`}
            </div>
          </div>
        </div>`;
    }).join('');
  }

  function syncRoomHeader() {
    const room = selectedRoom();
    const title = document.getElementById('cbRoomTitle');
    const hiddenRoom = document.getElementById('cbComposeRoomId');
    const editBtn = document.getElementById('cbEditRoomBtn');
    const deleteBtn = document.getElementById('cbDeleteRoomBtn');
    const canManage = !!room?.can_manage;

    if (title) title.textContent = room ? room.name : 'Selectionnez un salon';
    if (hiddenRoom) hiddenRoom.value = room ? String(room.id) : '';
    if (editBtn) editBtn.disabled = !room || !canManage;
    if (deleteBtn) deleteBtn.disabled = !room || !canManage || !!room?.is_default;
  }

  async function loadMessages(refresh = false) {
    const room = selectedRoom();
    const list = document.getElementById('cbMessagesList');

    if (!room) {
      state.messages = [];
      if (list) list.innerHTML = '<div class="cb-empty"><i class="fas fa-message"></i><div>Aucun salon selectionne.</div></div>';
      setText('cbCount', '0 message(s)');
      return;
    }

    if (state.loadingMessages) return;
    state.loadingMessages = true;
    if (list) list.innerHTML = skeletonMessages(6);

    const { ok, data } = await Http.get(window.CHATBOT_ROUTES.messagesData, {
      room_id: room.id,
      search: state.messageSearch,
      per_page: state.perPage,
      refresh: refresh ? 1 : 0,
    });

    state.loadingMessages = false;

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de charger les messages.');
      if (list) list.innerHTML = '<div class="cb-empty"><i class="fas fa-triangle-exclamation"></i><div>Chargement impossible.</div></div>';
      return;
    }

    const rows = Array.isArray(data.data) ? data.data : [];
    state.messages = rows.sort((a, b) => byDateAsc(a.sent_at, b.sent_at));
    renderMessages();
    setText('cbCount', `${Number(data.total || state.messages.length)} message(s)`);
  }

  function renderMessages() {
    const list = document.getElementById('cbMessagesList');
    if (!list) return;

    if (!state.messages.length) {
      list.innerHTML = '<div class="cb-empty"><i class="fas fa-comments"></i><div>Aucun message pour ce salon.</div></div>';
      return;
    }

    list.innerHTML = state.messages.map((message) => {
      const mineClass = message.is_mine ? 'is-mine' : '';
      const safeText = renderMessageText(String(message.text || ''));
      const attachments = renderAttachments(Array.isArray(message.attachments) ? message.attachments : []);
      const deleteButton = message.is_mine && !message.is_deleted
        ? `<button type="button" class="cb-message-action" data-cb-delete-message="${Number(message.id)}" title="Supprimer"><i class="fas fa-trash"></i></button>`
        : '';

      return `
        <article class="cb-message-card ${mineClass}">
          <div class="cb-message-head">
            <div class="cb-message-user">${esc(message.sender_name || 'Utilisateur')}</div>
            <div class="cb-message-date">${esc(message.sent_display || '-')}</div>
            ${deleteButton}
          </div>
          <div class="cb-message-text">${safeText || '<span class="cb-muted">(sans texte)</span>'}</div>
          ${attachments}
        </article>`;
    }).join('');

    list.scrollTop = list.scrollHeight;
  }

  function renderAttachments(attachments) {
    if (!attachments.length) return '';

    return `<div class="cb-attachments">${attachments.map((file) => {
      const fileName = esc(file.name || 'fichier');
      const fileUrl = esc(file.url || '#');
      const fileSize = Number(file.size_kb || 0);
      const fileType = esc(file.mime_type || '');
      const iconClass = iconForMime(fileType);
      const isImage = String(file.mime_type || '').toLowerCase().startsWith('image/');
      const thumb = isImage
        ? `<a class="cb-attachment-thumb" href="${fileUrl}" target="_blank" rel="noopener"><img src="${fileUrl}" alt="${fileName}"></a>`
        : `<span class="cb-attachment-icon"><i class="fas ${iconClass}"></i></span>`;
      const preview = file.previewable ? `<a class="cb-file-btn" href="${fileUrl}" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Apercu</a>` : '';

      return `
        <div class="cb-attachment-item">
          ${thumb}
          <span class="cb-attachment-main">
            <span class="cb-attachment-name">${fileName}</span>
            <span class="cb-attachment-meta">${fileType || 'fichier'}${fileSize ? ` - ${fileSize} KB` : ''}</span>
          </span>
          ${preview}
          <a class="cb-file-btn" href="${fileUrl}" target="_blank" rel="noopener" download><i class="fas fa-download"></i> Telecharger</a>
        </div>`;
    }).join('')}</div>`;
  }

  async function sendMessage() {
    const room = selectedRoom();
    const textInput = document.getElementById('cbComposeText');
    const sendBtn = document.getElementById('cbSendBtn');
    if (!room || !textInput || !sendBtn) return;

    const text = String(textInput.value || '').trim();
    if (!text && !state.pendingFiles.length) {
      Toast.warning('Message vide', 'Ajoutez du texte ou un fichier.');
      return;
    }

    if (state.pendingFiles.length > state.fileRules.maxFilesPerMessage) {
      Toast.error('Fichiers', `Maximum ${state.fileRules.maxFilesPerMessage} fichiers par message.`);
      return;
    }

    const invalid = findInvalidFiles(state.pendingFiles);
    if (invalid.length) {
      Toast.error('Fichiers refuses', invalid.slice(0, 2).join(' | '));
      return;
    }

    const formData = new FormData();
    formData.append('room_id', String(room.id));
    formData.append('text', text);
    const replyId = document.getElementById('cbReplyToMessageId')?.value || '';
    if (replyId) formData.append('reply_to_message_id', String(replyId));
    state.pendingFiles.forEach((file) => formData.append('files[]', file));

    CrmForm.setLoading(sendBtn, true);
    const { ok, data, status } = await Http.post(window.CHATBOT_ROUTES.messageSend, formData);
    CrmForm.setLoading(sendBtn, false);

    if (!ok || !data.success) {
      if (status === 422 && data?.errors) {
        const firstError = Object.values(data.errors).flat()[0];
        Toast.error('Validation', String(firstError || 'Envoi impossible.'));
      } else {
        Toast.error('Erreur', data.message || 'Envoi impossible.');
      }
      return;
    }

    textInput.value = '';
    state.pendingFiles = [];
    renderFileChips();

    const created = data.data || null;
    if (created && Number(created.room_id || 0) === Number(state.selectedRoomId)) {
      appendOrUpdateMessage(created);
      setText('cbCount', `${state.messages.length} message(s)`);
    }
    if (created?.id && state.socket?.connected) {
      state.localEchoCreatedIds.add(Number(created.id));
    }
    if (created?.id) applyRoomMessageDelta(Number(created.room_id || 0), +1, created.sent_at || new Date().toISOString());

    loadStats();
    Toast.success('Succes', data.message || 'Message envoye.');
  }

  function addPendingFiles(files) {
    const accepted = [];
    const rejected = [];

    files.forEach((file) => {
      const issue = validateSingleFile(file);
      if (issue) rejected.push(`${file.name}: ${issue}`);
      else accepted.push(file);
    });

    if (accepted.length) {
      state.pendingFiles = [...state.pendingFiles, ...accepted].slice(0, state.fileRules.maxFilesPerMessage);
      renderFileChips();
    }

    if (rejected.length) Toast.error('Fichiers refuses', rejected.slice(0, 2).join(' | '));
  }

  function validateSingleFile(file) {
    const maxBytes = state.fileRules.maxSizeKb * 1024;
    if (Number(file.size || 0) > maxBytes) return `taille > ${(state.fileRules.maxSizeKb / 1024).toFixed(1)} MB`;

    const ext = String(file.name || '').split('.').pop()?.toLowerCase() || '';
    const mime = String(file.type || '').toLowerCase();
    if (state.fileRules.allowedExtensions.length && !state.fileRules.allowedExtensions.includes(ext)) return 'extension non autorisee';
    if (state.fileRules.allowedMimeTypes.length && mime && !state.fileRules.allowedMimeTypes.includes(mime)) return 'type MIME non autorise';
    return null;
  }

  function findInvalidFiles(files) {
    return (files || []).map((file) => {
      const issue = validateSingleFile(file);
      return issue ? `${file.name}: ${issue}` : null;
    }).filter(Boolean);
  }

  function renderFileChips() {
    const wrap = document.getElementById('cbFileChips');
    if (!wrap) return;

    if (state.fileChipUrls.length) {
      state.fileChipUrls.forEach((url) => URL.revokeObjectURL(url));
      state.fileChipUrls = [];
    }

    if (!state.pendingFiles.length) {
      wrap.innerHTML = '';
      return;
    }

    wrap.innerHTML = state.pendingFiles.map((file, idx) => {
      const name = esc(file.name || `fichier-${idx + 1}`);
      const sizeKb = Math.max(1, Math.round(Number(file.size || 0) / 1024));
      const isImage = String(file.type || '').startsWith('image/');
      const previewUrl = isImage ? URL.createObjectURL(file) : '';
      if (previewUrl) {
        state.fileChipUrls.push(previewUrl);
      }
      const preview = isImage
        ? `<span class="cb-file-chip-preview" style="background-image:url('${previewUrl}')"></span>`
        : `<i class="fas ${iconForMime(file.type || '')}"></i>`;
      return `
        <span class="cb-file-chip">
          ${preview}
          ${name}
          <small>${sizeKb} KB</small>
          <button type="button" data-cb-remove-file="${idx}" title="Retirer"><i class="fas fa-xmark"></i></button>
        </span>`;
    }).join('');
  }

  function openRoomModal(mode, roomId = null) {
    const room = roomId ? roomById(roomId) : selectedRoom();
    state.roomFormMode = mode;

    const title = document.getElementById('cbRoomModalTitle');
    const idField = document.getElementById('cbRoomId');
    const nameField = document.getElementById('cbRoomName');
    const descField = document.getElementById('cbRoomDescription');
    const privateField = document.getElementById('cbRoomPrivate');
    const memberSearch = document.getElementById('cbMemberSearch');

    if (!idField || !nameField || !descField || !privateField) return;

    if (mode === 'edit' && room) {
      if (title) title.textContent = 'Personnaliser le salon';
      idField.value = String(room.id);
      nameField.value = String(room.name || '');
      descField.value = String(room.description || '');
      privateField.checked = !!room.is_private;
      state.selectedMemberIds = Array.isArray(room.member_ids) ? room.member_ids.map((id) => Number(id)) : [];
      selectRoomIcon(extractGlyph(room.icon || 'fa-comments'));
      selectRoomColor(String(room.color || '#0ea5e9'), true);
    } else {
      if (title) title.textContent = 'Nouveau salon';
      idField.value = '';
      nameField.value = '';
      descField.value = '';
      privateField.checked = false;
      state.selectedMemberIds = [];
      selectRoomIcon('fa-comments');
      selectRoomColor('#0ea5e9', true);
    }

    if (memberSearch) memberSearch.value = '';
    renderMemberBadges();
    renderMemberSuggestions('');
    CrmForm.clearErrors(document.getElementById('cbRoomForm'));
    Modal.open(document.getElementById('cbRoomModal'));
  }

  async function saveRoom() {
    const form = document.getElementById('cbRoomForm');
    const saveBtn = document.getElementById('cbRoomSaveBtn');
    if (!form || !saveBtn) return;

    CrmForm.clearErrors(form);

    const id = Number(document.getElementById('cbRoomId')?.value || 0);
    const payload = {
      name: String(document.getElementById('cbRoomName')?.value || '').trim(),
      description: String(document.getElementById('cbRoomDescription')?.value || '').trim(),
      icon: String(document.getElementById('cbRoomIcon')?.value || 'fa-comments').trim(),
      color: String(document.getElementById('cbRoomColor')?.value || '#0ea5e9').trim(),
      is_private: document.getElementById('cbRoomPrivate')?.checked ? 1 : 0,
      member_ids: state.selectedMemberIds.slice(),
    };

    if (!payload.name) {
      CrmForm.showErrors(form, { name: ['Le nom du salon est obligatoire.'] });
      return;
    }

    CrmForm.setLoading(saveBtn, true);
    const response = (state.roomFormMode === 'edit' && id > 0)
      ? await Http.put(`${window.CHATBOT_ROUTES.roomUpdateBase}/${id}`, payload)
      : await Http.post(window.CHATBOT_ROUTES.roomStore, payload);
    CrmForm.setLoading(saveBtn, false);

    if (!response.ok || !response.data?.success) {
      if (response.status === 422 && response.data?.errors) CrmForm.showErrors(form, response.data.errors);
      else Toast.error('Erreur', response.data?.message || 'Enregistrement impossible.');
      return;
    }

    const room = response.data?.data || null;
    if (room?.id) {
      upsertRoom(room);
      state.selectedRoomId = Number(room.id);
      renderRooms();
      syncRoomHeader();
    }

    Modal.close(document.getElementById('cbRoomModal'));
    hideMemberSuggestions();
    loadStats();
    Toast.success('Succes', response.data?.message || 'Salon enregistre.');
  }

  function renderMemberSuggestions(rawQuery) {
    const suggest = document.getElementById('cbMemberSuggest');
    if (!suggest) return;
    const query = String(rawQuery || '').trim().toLowerCase();
    const rows = state.users.filter((user) => !state.selectedMemberIds.includes(Number(user.id))).filter((user) => {
      if (!query) return true;
      return String(user.name || '').toLowerCase().includes(query) || String(user.email || '').toLowerCase().includes(query);
    }).slice(0, 8);

    if (!rows.length) {
      suggest.innerHTML = '';
      suggest.classList.remove('open');
      return;
    }

    suggest.innerHTML = rows.map((user) => `
      <button type="button" class="cb-member-option" data-cb-member-add="${Number(user.id)}">
        <span class="cb-member-option-avatar">${esc(initials(user.name || 'U'))}</span>
        <span class="cb-member-option-main">
          <strong>${esc(user.name || 'Utilisateur')}</strong>
          <small>${esc(user.email || '')}</small>
        </span>
      </button>`).join('');
    suggest.classList.add('open');
  }

  function hideMemberSuggestions() {
    const suggest = document.getElementById('cbMemberSuggest');
    if (!suggest) return;
    suggest.classList.remove('open');
  }

  function renderMemberBadges() {
    const wrap = document.getElementById('cbMemberBadges');
    if (!wrap) return;

    if (!state.selectedMemberIds.length) {
      wrap.innerHTML = '<span class="cb-member-empty">Aucun membre selectionne.</span>';
      return;
    }

    wrap.innerHTML = state.selectedMemberIds.map((id) => {
      const user = state.users.find((row) => Number(row.id) === Number(id));
      if (!user) return '';
      return `
        <span class="cb-member-badge">
          <span class="cb-member-avatar">${esc(initials(user.name || 'U'))}</span>
          <span>${esc(user.name || 'Utilisateur')}</span>
          <button type="button" data-cb-member-remove="${Number(user.id)}"><i class="fas fa-xmark"></i></button>
        </span>`;
    }).join('');
  }

  function addMember(userId) {
    if (!userId || state.selectedMemberIds.includes(userId)) return;
    state.selectedMemberIds.push(userId);
    renderMemberBadges();
    renderMemberSuggestions(document.getElementById('cbMemberSearch')?.value || '');
  }

  function removeMember(userId) {
    state.selectedMemberIds = state.selectedMemberIds.filter((id) => Number(id) !== Number(userId));
    renderMemberBadges();
    renderMemberSuggestions(document.getElementById('cbMemberSearch')?.value || '');
  }

  function deleteSelectedRoom() {
    const room = selectedRoom();
    if (room) deleteRoomById(Number(room.id));
  }

  function deleteRoomById(roomId) {
    const room = roomById(roomId);
    if (!room) return;
    if (room.is_default) {
      Toast.warning('Action bloquee', 'Le salon par defaut ne peut pas etre supprime.');
      return;
    }

    Modal.confirm({
      title: 'Supprimer ce salon',
      message: `Le salon ${room.name} sera archive. Continuer ?`,
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.delete(`${window.CHATBOT_ROUTES.roomDeleteBase}/${room.id}`);
        if (!ok || !data.success) throw new Error(data.message || 'Suppression impossible.');

        state.rooms = state.rooms.filter((row) => Number(row.id) !== Number(room.id));
        if (Number(state.selectedRoomId) === Number(room.id)) {
          state.selectedRoomId = state.rooms.length ? Number(state.rooms[0].id) : null;
          await loadMessages(false);
        }
        renderRooms();
        syncRoomHeader();
        Modal.close(document.getElementById('confirmModal'));
        loadStats();
        Toast.success('Succes', data.message || 'Salon supprime.');
      },
    });
  }

  function deleteMessage(messageId) {
    Modal.confirm({
      title: 'Supprimer ce message',
      message: 'Cette action est definitive.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.delete(`${window.CHATBOT_ROUTES.messageDeleteBase}/${messageId}`);
        if (!ok || !data.success) throw new Error(data.message || 'Suppression impossible.');

        const roomId = Number(selectedRoom()?.id || 0);
        state.messages = state.messages.filter((message) => Number(message.id) !== Number(messageId));
        renderMessages();
        setText('cbCount', `${state.messages.length} message(s)`);
        if (roomId) {
          if (state.socket?.connected) {
            state.localEchoDeletedIds.add(Number(messageId));
          }
          applyRoomMessageDelta(roomId, -1, null);
        }

        Modal.close(document.getElementById('confirmModal'));
        loadStats();
        Toast.success('Succes', data.message || 'Message supprime.');
      },
    });
  }

  async function loadStats() {
    const { ok, data } = await Http.get(window.CHATBOT_ROUTES.stats);
    if (!ok || !data.success) return;
    const stats = data.data || {};
    setText('cbStatRooms', Number(stats.rooms_count || 0));
    setText('cbStatPrivateRooms', Number(stats.private_rooms_count || 0));
    setText('cbStatToday', Number(stats.messages_today || 0));
    setText('cbStatWeek', Number(stats.messages_last_7_days || 0));
    setText('cbSocketStatus', stats.socket_enabled ? 'Actif' : 'Off');
  }

  function initSocket() {
    const cfg = state.socketConfig || {};
    if (!cfg.enabled || typeof window.io !== 'function') {
      setText('cbSocketStatus', 'Off');
      return;
    }

    const clientUrl = String(cfg.clientUrl || '').trim();
    if (!clientUrl) {
      setText('cbSocketStatus', 'Off');
      return;
    }

    const namespace = String(cfg.namespace || '/').trim();
    const target = namespace && namespace !== '/' ? `${clientUrl}${namespace}` : clientUrl;

    try {
      state.socket = window.io(target, {
        path: cfg.path || '/socket.io',
        transports: Array.isArray(cfg.transports) && cfg.transports.length ? cfg.transports : ['websocket', 'polling'],
      });
    } catch (error) {
      console.warn('[Chatbot] socket init failed:', error);
      setText('cbSocketStatus', 'Erreur');
      return;
    }

    state.socket.on('connect', () => {
      setText('cbSocketStatus', 'Actif');
      state.socket.emit('subscribe', { tenant_id: state.tenantId, module: 'chatbot' });
    });
    state.socket.on('disconnect', () => setText('cbSocketStatus', 'Hors ligne'));
    state.socket.on('connect_error', () => setText('cbSocketStatus', 'Erreur'));
    state.socket.on('chatbot.event', onSocketEvent);
    state.socket.on(`tenant:${state.tenantId}:chatbot`, onSocketEvent);
  }

  function onSocketEvent(packet) {
    if (!packet || Number(packet.tenant_id || 0) !== Number(state.tenantId)) return;
    const type = String(packet.event || '').toLowerCase();
    const payload = packet.payload || {};

    if (type === 'message.created') {
      const roomId = Number(payload.room_id || 0);
      const message = payload.message || null;
      const messageId = Number(message?.id || 0);
      const isEcho = messageId > 0 && state.localEchoCreatedIds.has(messageId);

      if (isEcho) {
        state.localEchoCreatedIds.delete(messageId);
      } else if (roomId) {
        applyRoomMessageDelta(roomId, +1, message?.sent_at || new Date().toISOString());
      }

      if (roomId === Number(state.selectedRoomId) && message) {
        appendOrUpdateMessage(message);
        setText('cbCount', `${state.messages.length} message(s)`);
      }
      loadStats();
      return;
    }

    if (type === 'message.deleted') {
      const roomId = Number(payload.room_id || 0);
      const messageId = Number(payload.message_id || 0);
      const isEcho = messageId > 0 && state.localEchoDeletedIds.has(messageId);

      if (isEcho) {
        state.localEchoDeletedIds.delete(messageId);
      }

      if (roomId === Number(state.selectedRoomId) && messageId > 0) {
        state.messages = state.messages.filter((message) => Number(message.id) !== messageId);
        renderMessages();
        setText('cbCount', `${state.messages.length} message(s)`);
      }
      if (roomId > 0 && !isEcho) applyRoomMessageDelta(roomId, -1, null);
      loadStats();
      return;
    }

    if (type === 'room.created' || type === 'room.updated') {
      if (payload.room) {
        upsertRoom(payload.room);
        renderRooms();
        syncRoomHeader();
      }
      loadStats();
      return;
    }

    if (type === 'room.deleted') {
      const roomId = Number(payload.room_id || 0);
      if (roomId > 0) {
        state.rooms = state.rooms.filter((room) => Number(room.id) !== roomId);
        if (Number(state.selectedRoomId) === roomId) {
          state.selectedRoomId = state.rooms.length ? Number(state.rooms[0].id) : null;
          loadMessages(false);
        }
        renderRooms();
        syncRoomHeader();
      }
      loadStats();
    }
  }

  function applyRoomMessageDelta(roomId, delta, sentAt) {
    const idx = state.rooms.findIndex((room) => Number(room.id) === Number(roomId));
    if (idx < 0) return;
    const next = { ...state.rooms[idx] };
    next.messages_count = Math.max(0, Number(next.messages_count || 0) + delta);
    if (delta > 0 && sentAt) next.last_message_at = sentAt;
    state.rooms[idx] = next;
    sortRooms();
    renderRooms();
  }

  function appendOrUpdateMessage(message) {
    if (!message?.id) return;
    const id = Number(message.id);
    const idx = state.messages.findIndex((row) => Number(row.id) === id);
    if (idx >= 0) state.messages[idx] = message;
    else state.messages.push(message);
    state.messages.sort((a, b) => byDateAsc(a.sent_at, b.sent_at));
    renderMessages();
  }

  function upsertRoom(room) {
    if (!room?.id) return;
    const idx = state.rooms.findIndex((row) => Number(row.id) === Number(room.id));
    if (idx >= 0) state.rooms[idx] = room;
    else state.rooms.push(room);
    sortRooms();
  }

  function sortRooms() {
    state.rooms.sort((a, b) => {
      if (Number(a.is_default || 0) !== Number(b.is_default || 0)) return Number(b.is_default || 0) - Number(a.is_default || 0);
      return byDateAsc(b.last_message_at, a.last_message_at);
    });
  }

  function selectedRoom() {
    return state.rooms.find((room) => Number(room.id) === Number(state.selectedRoomId)) || null;
  }

  function roomById(roomId) {
    return state.rooms.find((room) => Number(room.id) === Number(roomId)) || null;
  }

  function normalizeIcon(value) {
    const raw = String(value || '').trim();
    if (!raw) return 'fas fa-comments';
    if (/^fa-[a-z0-9-]+$/i.test(raw)) return `fas ${raw}`;
    return raw;
  }

  function extractGlyph(value) {
    const raw = String(value || '').trim();
    if (/^fa-[a-z0-9-]+$/i.test(raw)) return raw;
    const token = raw.split(/\s+/).find((item) => /^fa-[a-z0-9-]+$/i.test(item));
    return token || 'fa-comments';
  }

  function initials(name) {
    return String(name || 'U').split(' ').filter(Boolean).slice(0, 2).map((part) => part.charAt(0).toUpperCase()).join('');
  }

  function byDateAsc(a, b) {
    const left = Date.parse(String(a || ''));
    const right = Date.parse(String(b || ''));
    return (Number.isFinite(left) ? left : 0) - (Number.isFinite(right) ? right : 0);
  }

  function iconForMime(mimeType) {
    const mime = String(mimeType || '').toLowerCase();
    if (mime.startsWith('image/')) return 'fa-file-image';
    if (mime.includes('pdf')) return 'fa-file-pdf';
    if (mime.includes('word')) return 'fa-file-word';
    if (mime.includes('excel') || mime.includes('sheet')) return 'fa-file-excel';
    if (mime.includes('zip')) return 'fa-file-zipper';
    if (mime.startsWith('text/')) return 'fa-file-lines';
    return 'fa-file';
  }

  function renderMessageText(raw) {
    const escaped = esc(raw || '');
    const withLinks = escaped.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
    return withLinks.replace(/\n/g, '<br>');
  }

  function skeletonMessages(rows = 6) {
    return Array.from({ length: rows }).map(() => `
      <article class="cb-message-card">
        <div style="height:12px;width:160px;background:var(--surface-2);border-radius:6px;margin-bottom:8px"></div>
        <div style="height:10px;width:100%;background:var(--surface-2);border-radius:6px;margin-bottom:5px"></div>
        <div style="height:10px;width:80%;background:var(--surface-2);border-radius:6px"></div>
      </article>`).join('');
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
  }

  function setText(id, value) {
    const node = document.getElementById(id);
    if (node) node.textContent = String(value ?? '');
  }

  return { boot };
})();

window.ChatbotModule = ChatbotModule;
