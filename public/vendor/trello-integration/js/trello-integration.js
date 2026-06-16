(function () {
  const boot = window.__TRELLO_WORKSPACE_BOOT__ || {};
  const root = document.getElementById('trelloWorkspaceApp');
  if (!root || !boot.routes) return;

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const state = {
    boards: Array.isArray(boot.boards) ? boot.boards : [],
    board: boot.selectedBoard || null,
    currentBoardId: boot.selectedBoardId || null,
    projects: Array.isArray(boot.projects) ? boot.projects : [],
    drag: {
      cardId: null,
      placeholder: null,
    },
  };

  const els = {
    status: document.getElementById('trelloStatus'),
    gallery: document.getElementById('trelloBoardGallery'),
    nav: document.getElementById('trelloBoardNav'),
    boardSearch: document.getElementById('trelloBoardSearchInput'),
    boardStage: document.getElementById('trelloBoardStage'),
    boardEmpty: document.getElementById('trelloBoardEmpty'),
    boardHeader: document.getElementById('trelloBoardHeader'),
    listsScroller: document.getElementById('trelloListsScroller'),
    syncBtn: document.getElementById('trelloSyncBtn'),
    disconnectBtn: document.getElementById('trelloDisconnectBtn'),
    clearBoardBtn: document.getElementById('trelloClearBoardBtn'),
    openBoardLink: document.getElementById('trelloOpenBoardLink'),
    boardsCount: document.getElementById('trelloBoardsCount'),
    currentBoardLabel: document.getElementById('trelloCurrentBoardLabel'),
    lastSyncLabel: document.getElementById('trelloLastSyncLabel'),
    modal: document.getElementById('trelloCardModal'),
    modalTitle: document.getElementById('trelloCardModalTitle'),
    modalMeta: document.getElementById('trelloCardModalMeta'),
    modalOpenLink: document.getElementById('trelloCardModalOpenLink'),
    cardId: document.getElementById('trelloCardId'),
    cardName: document.getElementById('trelloCardName'),
    cardDue: document.getElementById('trelloCardDue'),
    cardDescription: document.getElementById('trelloCardDescription'),
    cardProject: document.getElementById('trelloCardProject'),
    cardLinkNotes: document.getElementById('trelloCardLinkNotes'),
    saveCardBtn: document.getElementById('trelloSaveCardBtn'),
    archiveCardBtn: document.getElementById('trelloArchiveCardBtn'),
  };

  function setStatus(message, kind = 'info') {
    if (!els.status) return;
    els.status.textContent = message;
    els.status.dataset.kind = kind;
  }

  function formatDate(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date);
  }

  function truncate(text, limit = 118) {
    const value = String(text || '').trim();
    if (value.length <= limit) return value;
    return `${value.slice(0, limit).trim()}...`;
  }

  function boardGradient(board) {
    const primary = board.background_color || '#0f7be7';
    const secondary = board.background_image_url ? 'rgba(15,23,42,.42)' : '#14b8a6';
    return { primary, secondary };
  }

  function boardQueryUrl(boardId) {
    const url = new URL(boot.routes.index, window.location.origin);
    if (boardId) url.searchParams.set('board', String(boardId));
    return url.toString();
  }

  function apiUrl(template, replacement) {
    return template.replace(/__(BOARD|CARD|LIST)__/g, String(replacement));
  }

  async function jsonRequest(url, options = {}) {
    const response = await fetch(url, {
      headers: {
        Accept: 'application/json',
        ...(options.method && options.method !== 'GET'
          ? {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
            }
          : {}),
        ...(options.headers || {}),
      },
      credentials: 'same-origin',
      ...options,
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.success === false) {
      const error = new Error(data.message || 'Action Trello impossible.');
      error.payload = data;
      throw error;
    }

    return data;
  }

  function maybePromptReconnect(error) {
    const message = String(error?.message || '');
    if (!/Session Trello expiree ou revoquee/i.test(message)) return false;
    if (!window.Modal || typeof window.Modal.confirm !== 'function') return false;

    window.Modal.confirm({
      title: 'Reconnecter Trello',
      message: 'La session Trello a expire ou a ete revoquee. Nous pouvons vous rediriger vers la reconnexion pour restaurer votre workspace.',
      confirmText: 'Reconnecter',
      type: 'warning',
      onConfirm: async () => {
        window.location.href = boot.routes.connect;
      },
    });

    return true;
  }

  function renderBoardGallery() {
    if (!els.gallery) return;

    const term = String(els.boardSearch?.value || '').trim().toLowerCase();
    const filtered = state.boards.filter((board) => {
      if (!term) return true;
      return String(board.name || '').toLowerCase().includes(term)
        || String(board.description || '').toLowerCase().includes(term);
    });

    els.gallery.innerHTML = filtered.map((board) => {
      const gradient = boardGradient(board);
      const meta = [
        `<span><i class="fas fa-table-list"></i> ${board.lists_count || 0} listes</span>`,
        `<span><i class="fas fa-clone"></i> ${board.cards_count || 0} cartes</span>`,
      ].join('');

      return `
        <button type="button" class="ti-board-card ${state.currentBoardId === board.id ? 'is-active' : ''}" data-board-open="${board.id}" style="--board-a:${gradient.primary};--board-b:${gradient.secondary}">
          <div class="ti-board-card-top">
            <div class="ti-board-card-title">${escapeHtml(board.name)}</div>
            ${board.starred ? '<i class="fas fa-star"></i>' : '<i class="fas fa-table-columns"></i>'}
          </div>
          <div class="ti-board-card-desc">${escapeHtml(truncate(board.description || 'Board Trello synchronise dans le CRM.'))}</div>
          <div class="ti-board-card-meta">${meta}</div>
        </button>
      `;
    }).join('');
  }

  function renderBoardNav() {
    if (!els.nav) return;

    const term = String(els.boardSearch?.value || '').trim().toLowerCase();
    const filtered = state.boards.filter((board) => {
      if (!term) return true;
      return String(board.name || '').toLowerCase().includes(term)
        || String(board.description || '').toLowerCase().includes(term);
    });

    els.nav.innerHTML = filtered.map((board) => {
      const gradient = boardGradient(board);
      return `
        <button type="button" class="ti-board-nav-item ${state.currentBoardId === board.id ? 'is-active' : ''}" data-board-open="${board.id}">
          <span class="ti-board-nav-swatch" style="--swatch-a:${gradient.primary};--swatch-b:${gradient.secondary}"></span>
          <span class="ti-board-nav-copy">
            <strong>${escapeHtml(board.name)}</strong>
            <span>${board.cards_count || 0} cartes - ${board.lists_count || 0} listes</span>
          </span>
        </button>
      `;
    }).join('');
  }

  function renderBoardHeader(board) {
    if (!els.boardHeader) return;

    els.boardHeader.innerHTML = `
      <div class="ti-board-header-main">
        <h3>${escapeHtml(board.name)}</h3>
        <p>${escapeHtml(board.description || 'Board Trello synchronise en local pour une lecture rapide et des interactions fluides dans le CRM.')}</p>
        <div class="ti-board-header-meta">
          <span><i class="fas fa-table-list"></i> ${board.lists.length} listes</span>
          <span><i class="fas fa-clone"></i> ${board.lists.reduce((sum, list) => sum + list.cards.length, 0)} cartes</span>
          <span><i class="fas fa-clock"></i> Sync ${formatDate(board.last_synced_at) || 'maintenant'}</span>
        </div>
      </div>
    `;

    if (els.currentBoardLabel) els.currentBoardLabel.textContent = board.name;
    if (els.openBoardLink) {
      els.openBoardLink.href = board.url || '#';
      els.openBoardLink.style.display = board.url ? '' : 'none';
    }
  }

  function renderCard(card) {
    const members = Array.isArray(card.members) ? card.members.slice(0, 4) : [];
    const badges = [];

    if (card.due_at) {
      badges.push(`<span class="ti-card-badge"><i class="fas fa-clock"></i> ${escapeHtml(formatDate(card.due_at))}</span>`);
    }

    if ((card.badges?.comments || 0) > 0) {
      badges.push(`<span class="ti-card-badge"><i class="fas fa-comment"></i> ${card.badges.comments}</span>`);
    }

    if (card.link?.project_name) {
      badges.push(`<span class="ti-card-badge"><i class="fas fa-link"></i> ${escapeHtml(card.link.project_name)}</span>`);
    }

    const cover = card.cover_color
      ? `<div class="ti-card-cover" style="--cover-a:${card.cover_color};--cover-b:${card.cover_color}"></div>`
      : '';

    const memberHtml = members.map((member) => `
      <span class="ti-member-bubble" title="${escapeHtml(member.fullName || member.username || member.id || '')}">
        ${member.avatarUrl ? `<img src="${escapeAttribute(member.avatarUrl)}" alt="${escapeAttribute(member.fullName || member.username || 'Membre')}">` : escapeHtml((member.initials || member.fullName || 'M').slice(0, 2))}
      </span>
    `).join('');

    const linkHtml = card.link?.project_name
      ? `<div class="ti-card-link"><i class="fas fa-diagram-project"></i> Liee au projet ${escapeHtml(card.link.project_name)}</div>`
      : '';

    return `
      <article class="ti-card" draggable="true" data-card-id="${card.id}" data-position="${card.position || ''}" data-list-id="${card.list_id}">
        ${cover}
        <div class="ti-card-title">${escapeHtml(card.name)}</div>
        ${card.description ? `<div class="ti-card-desc">${escapeHtml(truncate(card.description, 180))}</div>` : ''}
        <div class="ti-card-meta">
          <div class="ti-card-badges">${badges.join('')}</div>
          <div class="ti-card-members">${memberHtml}</div>
        </div>
        ${linkHtml}
      </article>
    `;
  }

  function renderLists() {
    if (!els.listsScroller || !state.board) return;

    els.listsScroller.innerHTML = state.board.lists.map((list) => `
      <section class="ti-list-column" data-list-id="${list.id}">
        <header class="ti-list-head">
          <strong>${escapeHtml(list.name)}</strong>
          <span class="ti-list-count">${list.cards.length}</span>
        </header>
        <div class="ti-list-body" data-list-body="${list.id}">
          ${list.cards.map(renderCard).join('')}
        </div>
        <div class="ti-new-card">
          <button type="button" class="ti-new-card-trigger" data-card-trigger="${list.id}">
            <i class="fas fa-plus"></i> Nouvelle carte
          </button>
          <form class="ti-new-card-form" data-card-form="${list.id}">
            <input type="text" class="form-control" name="name" placeholder="Titre de la carte" required>
            <textarea class="form-control" name="description" rows="3" placeholder="Description rapide (optionnelle)"></textarea>
            <div class="ti-new-card-actions">
              <button class="btn btn-primary btn-sm" type="submit">Creer</button>
              <button class="btn btn-secondary btn-sm" type="button" data-card-cancel="${list.id}">Annuler</button>
            </div>
          </form>
        </div>
      </section>
    `).join('');

    bindListInteractions();
  }

  function showBoardWorkspace(visible) {
    if (els.boardStage) els.boardStage.style.display = visible ? '' : 'none';
    if (els.boardEmpty) els.boardEmpty.style.display = visible ? 'none' : '';
  }

  async function loadBoard(boardId, pushState = true) {
    setStatus('Chargement du board Trello...', 'loading');

    try {
      const response = await jsonRequest(apiUrl(boot.routes.board, boardId));
      state.board = response.data;
      state.currentBoardId = response.data.id;
      renderBoardGallery();
      renderBoardNav();
      renderBoardHeader(response.data);
      renderLists();
      showBoardWorkspace(true);

      if (els.lastSyncLabel && response.data.last_synced_at) {
        els.lastSyncLabel.textContent = formatDate(response.data.last_synced_at);
      }

      if (pushState) {
        window.history.pushState({ boardId }, '', boardQueryUrl(boardId));
      }

      setStatus('Board Trello synchronise et pret.', 'success');
    } catch (error) {
      maybePromptReconnect(error);
      setStatus(error.message || 'Impossible de charger le board.', 'error');
      window.Toast?.error('Trello', error.message || 'Impossible de charger le board.');
    }
  }

  async function syncAllBoards() {
    setStatus('Synchronisation de vos boards Trello...', 'loading');

    try {
      const response = await jsonRequest(boot.routes.sync, { method: 'POST' });
      state.boards = Array.isArray(response.boards) ? response.boards : state.boards;
      renderBoardGallery();
      renderBoardNav();

      if (els.boardsCount) els.boardsCount.textContent = String(state.boards.length);
      if (els.lastSyncLabel && response.data?.last_synced_at) {
        els.lastSyncLabel.textContent = formatDate(response.data.last_synced_at);
      }

      setStatus(response.message || 'Synchronisation terminee.', 'success');
      window.Toast?.success('Trello', response.message || 'Synchronisation terminee.');

      if (state.currentBoardId) {
        await loadBoard(state.currentBoardId, false);
      }
    } catch (error) {
      maybePromptReconnect(error);
      setStatus(error.message || 'Synchronisation Trello impossible.', 'error');
      window.Toast?.error('Trello', error.message || 'Synchronisation Trello impossible.');
    }
  }

  function clearSelectedBoard() {
    state.board = null;
    state.currentBoardId = null;
    renderBoardGallery();
    renderBoardNav();
    showBoardWorkspace(false);
    if (els.currentBoardLabel) els.currentBoardLabel.textContent = 'Aucun';
    if (els.openBoardLink) els.openBoardLink.style.display = 'none';
    window.history.pushState({}, '', boardQueryUrl(null));
    setStatus('Galerie des boards affichee.', 'info');
  }

  function openCardModal(cardId) {
    if (!state.board) return;

    const card = findCard(cardId);
    if (!card || !els.modal) return;

    els.modalTitle.textContent = card.name;
    els.modalMeta.textContent = card.link?.project_name
      ? `Carte liee au projet CRM ${card.link.project_name}`
      : 'Carte Trello non liee a un projet CRM';
    els.cardId.value = String(card.id);
    els.cardName.value = card.name || '';
    els.cardDescription.value = card.description || '';
    els.cardDue.value = card.due_at ? toLocalDateTimeInput(card.due_at) : '';
    els.cardLinkNotes.value = card.link?.notes || '';
    els.modalOpenLink.href = card.url || '#';
    populateProjectSelect(card.link?.project_id || '');

    if (window.Modal) {
      window.Modal.open(els.modal);
    } else {
      els.modal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function populateProjectSelect(selected) {
    if (!els.cardProject) return;

    els.cardProject.innerHTML = '<option value="">Aucun projet lie</option>' + state.projects.map((project) => `
      <option value="${project.id}" ${String(selected) === String(project.id) ? 'selected' : ''}>${escapeHtml(project.name)}</option>
    `).join('');
  }

  async function saveCard() {
    const cardId = els.cardId.value;
    if (!cardId) return;

    try {
      const payload = {
        name: els.cardName.value.trim(),
        description: els.cardDescription.value.trim(),
        due: els.cardDue.value ? new Date(els.cardDue.value).toISOString() : null,
        project_id: els.cardProject.value ? Number(els.cardProject.value) : null,
        link_notes: els.cardLinkNotes.value.trim(),
      };

      const response = await jsonRequest(apiUrl(boot.routes.cardUpdate, cardId), {
        method: 'PUT',
        body: JSON.stringify(payload),
      });

      window.Toast?.success('Trello', response.message || 'Carte mise a jour.');
      if (window.Modal) window.Modal.close(els.modal, true);
      await loadBoard(state.currentBoardId, false);
    } catch (error) {
      maybePromptReconnect(error);
      window.Toast?.error('Trello', error.message || 'Impossible d enregistrer la carte.');
    }
  }

  async function archiveCard() {
    const cardId = els.cardId.value;
    if (!cardId) return;

    try {
      const response = await jsonRequest(apiUrl(boot.routes.cardArchive, cardId), { method: 'DELETE' });
      window.Toast?.success('Trello', response.message || 'Carte archivee.');
      if (window.Modal) window.Modal.close(els.modal, true);
      await loadBoard(state.currentBoardId, false);
    } catch (error) {
      maybePromptReconnect(error);
      window.Toast?.error('Trello', error.message || 'Impossible d archiver la carte.');
    }
  }

  function bindListInteractions() {
    els.listsScroller.querySelectorAll('[data-card-trigger]').forEach((button) => {
      button.addEventListener('click', () => {
        const listId = button.getAttribute('data-card-trigger');
        const form = els.listsScroller.querySelector(`[data-card-form="${listId}"]`);
        form?.classList.add('open');
        button.style.display = 'none';
      });
    });

    els.listsScroller.querySelectorAll('[data-card-cancel]').forEach((button) => {
      button.addEventListener('click', () => {
        const listId = button.getAttribute('data-card-cancel');
        const form = els.listsScroller.querySelector(`[data-card-form="${listId}"]`);
        const trigger = els.listsScroller.querySelector(`[data-card-trigger="${listId}"]`);
        if (form) {
          form.classList.remove('open');
          form.reset();
        }
        if (trigger) trigger.style.display = '';
      });
    });

    els.listsScroller.querySelectorAll('[data-card-form]').forEach((form) => {
      form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const listId = form.getAttribute('data-card-form');
        const formData = new FormData(form);

        try {
          const response = await jsonRequest(apiUrl(boot.routes.listCreateCard, listId), {
            method: 'POST',
            body: JSON.stringify({
              name: String(formData.get('name') || '').trim(),
              description: String(formData.get('description') || '').trim(),
            }),
          });

          window.Toast?.success('Trello', response.message || 'Carte creee.');
          await loadBoard(state.currentBoardId, false);
        } catch (error) {
          maybePromptReconnect(error);
          window.Toast?.error('Trello', error.message || 'Impossible de creer la carte.');
        }
      });
    });

    els.listsScroller.querySelectorAll('[data-card-id]').forEach((cardEl) => {
      cardEl.addEventListener('click', () => openCardModal(cardEl.getAttribute('data-card-id')));
      cardEl.addEventListener('dragstart', (event) => {
        state.drag.cardId = cardEl.getAttribute('data-card-id');
        cardEl.classList.add('is-dragging');
        state.drag.placeholder = document.createElement('div');
        state.drag.placeholder.className = 'ti-drop-placeholder';
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', state.drag.cardId);
      });

      cardEl.addEventListener('dragend', () => {
        cardEl.classList.remove('is-dragging');
        state.drag.placeholder?.remove();
        state.drag.placeholder = null;
        state.drag.cardId = null;
        els.listsScroller.querySelectorAll('.ti-dropzone-hover').forEach((zone) => zone.classList.remove('ti-dropzone-hover'));
      });
    });

    els.listsScroller.querySelectorAll('[data-list-body]').forEach((zone) => {
      zone.addEventListener('dragover', (event) => {
        event.preventDefault();
        zone.classList.add('ti-dropzone-hover');
        const afterElement = getDragAfterElement(zone, event.clientY);
        if (!state.drag.placeholder) return;
        if (afterElement == null) {
          zone.appendChild(state.drag.placeholder);
        } else {
          zone.insertBefore(state.drag.placeholder, afterElement);
        }
      });

      zone.addEventListener('dragleave', () => {
        zone.classList.remove('ti-dropzone-hover');
      });

      zone.addEventListener('drop', async (event) => {
        event.preventDefault();
        zone.classList.remove('ti-dropzone-hover');
        if (!state.drag.cardId || !state.drag.placeholder) return;

        const cardId = state.drag.cardId;
        const targetListId = zone.getAttribute('data-list-body');
        const siblings = Array.from(zone.querySelectorAll('[data-card-id], .ti-drop-placeholder'));
        const index = siblings.indexOf(state.drag.placeholder);
        const prevCard = findCardElementCardId(siblings[index - 1]);
        const nextCard = findCardElementCardId(siblings[index + 1]);
        const position = computePosition(targetListId, prevCard, nextCard);

        state.drag.placeholder.remove();
        state.drag.placeholder = null;

        try {
          const response = await jsonRequest(apiUrl(boot.routes.cardMove, cardId), {
            method: 'PUT',
            body: JSON.stringify({
              target_list_id: Number(targetListId),
              position,
            }),
          });

          window.Toast?.success('Trello', response.message || 'Carte deplacee.');
          await loadBoard(state.currentBoardId, false);
        } catch (error) {
          maybePromptReconnect(error);
          window.Toast?.error('Trello', error.message || 'Deplacement impossible.');
          await loadBoard(state.currentBoardId, false);
        }
      });
    });
  }

  function computePosition(targetListId, prevCardId, nextCardId) {
    const list = state.board?.lists?.find((item) => String(item.id) === String(targetListId));
    if (!list) return 'bottom';

    const prevCard = prevCardId ? list.cards.find((item) => String(item.id) === String(prevCardId)) : null;
    const nextCard = nextCardId ? list.cards.find((item) => String(item.id) === String(nextCardId)) : null;

    if (!prevCard && !nextCard) return 'top';
    if (!prevCard) return 'top';
    if (!nextCard) return 'bottom';

    const prevPos = Number(prevCard.position || 0);
    const nextPos = Number(nextCard.position || 0);
    if (!Number.isFinite(prevPos) || !Number.isFinite(nextPos) || prevPos >= nextPos) {
      return 'bottom';
    }

    return String((prevPos + nextPos) / 2);
  }

  function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('[data-card-id]:not(.is-dragging)')];
    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) {
        return { offset, element: child };
      }
      return closest;
    }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
  }

  function findCardElementCardId(element) {
    if (!element || !element.matches('[data-card-id]')) return null;
    return element.getAttribute('data-card-id');
  }

  function findCard(cardId) {
    if (!state.board) return null;
    for (const list of state.board.lists) {
      const found = list.cards.find((card) => String(card.id) === String(cardId));
      if (found) return found;
    }
    return null;
  }

  function toLocalDateTimeInput(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (number) => String(number).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escapeAttribute(value) {
    return escapeHtml(value);
  }

  function bindGlobalEvents() {
    document.addEventListener('click', (event) => {
      const boardTrigger = event.target.closest('[data-board-open]');
      if (boardTrigger) {
        loadBoard(boardTrigger.getAttribute('data-board-open'));
      }
    });

    els.boardSearch?.addEventListener('input', () => {
      renderBoardGallery();
      renderBoardNav();
    });

    els.syncBtn?.addEventListener('click', syncAllBoards);
    els.clearBoardBtn?.addEventListener('click', clearSelectedBoard);
    els.disconnectBtn?.addEventListener('click', async () => {
      if (!window.Modal || typeof window.Modal.confirm !== 'function') return;

      window.Modal.confirm({
        title: 'Deconnecter Trello',
        message: 'La connexion actuelle sera supprimee du CRM. Vous pourrez reconnecter Trello plus tard.',
        confirmText: 'Deconnecter',
        type: 'danger',
        onConfirm: async () => {
          try {
            const response = await jsonRequest(boot.routes.disconnect, { method: 'POST' });
            window.Toast?.success('Trello', response.message || 'Trello deconnecte.');
            window.location.reload();
          } catch (error) {
            maybePromptReconnect(error);
            window.Toast?.error('Trello', error.message || 'Deconnexion impossible.');
          }
        },
      });
    });

    els.saveCardBtn?.addEventListener('click', saveCard);
    els.archiveCardBtn?.addEventListener('click', archiveCard);

    window.addEventListener('popstate', (event) => {
      const boardId = event.state?.boardId || new URL(window.location.href).searchParams.get('board');
      if (boardId) {
        loadBoard(boardId, false);
      } else {
        clearSelectedBoard();
      }
    });
  }

  renderBoardGallery();
  renderBoardNav();
  bindGlobalEvents();

  if (state.board) {
    renderBoardHeader(state.board);
    renderLists();
    showBoardWorkspace(true);
  } else {
    showBoardWorkspace(false);
  }
})();


