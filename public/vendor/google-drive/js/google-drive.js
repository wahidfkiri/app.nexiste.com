'use strict';

const GoogleDriveModule = (() => {
  const state = {
    connected: false,
    currentFolderId: null,
    rootFolderId: null,
    folderStack: [],
    files: [],
    search: '',
    debounceTimer: null,
  };

  function boot(bootstrap = {}) {
    state.connected = !!bootstrap.connected;
    bindActions();

    if (!state.connected) {
      return;
    }

    loadStats();
    loadFiles();
  }

  function bindActions() {
    document.getElementById('gdRefreshBtn')?.addEventListener('click', () => {
      loadStats();
      loadFiles();
    });

    document.getElementById('gdDisconnectBtn')?.addEventListener('click', disconnect);
    document.getElementById('gdSaveFolderBtn')?.addEventListener('click', createFolder);
    document.getElementById('gdUploadInput')?.addEventListener('change', uploadFile);
    document.getElementById('gdBackBtn')?.addEventListener('click', navigateBack);
    document.getElementById('gdTrashBtn')?.addEventListener('click', openTrash);
    document.getElementById('gdEmptyTrashBtn')?.addEventListener('click', emptyTrash);

    document.getElementById('gdSearchInput')?.addEventListener('input', (e) => {
      clearTimeout(state.debounceTimer);
      state.debounceTimer = setTimeout(() => {
        state.search = (e.target.value || '').trim();
        if (state.search.length >= 2 || state.search.length === 0) {
          loadFiles();
        }
      }, 300);
    });

    document.getElementById('gdFilesTableBody')?.addEventListener('click', handleFileActions);
    document.getElementById('gdTrashTableBody')?.addEventListener('click', handleTrashActions);
  }

  async function loadStats() {
    const { ok, data } = await Http.get(window.GDRIVE_ROUTES.stats);
    if (!ok || !data.success) {
      return;
    }

    const stats = data.data || {};
    setText('gdUsedStorage', `${stats.used_gb || 0} GB`);
    setText('gdTotalStorage', `${stats.total_gb || 0} GB`);
  }

  async function loadFiles() {
    const tbody = document.getElementById('gdFilesTableBody');
    if (tbody) {
      tbody.innerHTML = skeletonRows(6, 5);
    }

    const params = {
      folder_id: state.currentFolderId || '',
      search: state.search || '',
    };

    const { ok, data } = await Http.get(window.GDRIVE_ROUTES.filesData, params);
    if (!ok || !data.success) {
      if (tbody) tbody.innerHTML = emptyRow('Impossible de charger les fichiers.');
      Toast.error('Erreur', data.message || 'Impossible de charger les fichiers.');
      return;
    }

    const payload = data.data || {};
    state.files = payload.files || [];
    state.rootFolderId = payload.root_folder || state.rootFolderId;

    if (!state.currentFolderId) {
      state.currentFolderId = payload.folder_id || null;
    }

    renderFiles();
    setText('gdCount', `${state.files.length} element(s)`);
    updateFolderName();
  }

  function renderFiles() {
    const tbody = document.getElementById('gdFilesTableBody');
    if (!tbody) return;

    if (!state.files.length) {
      tbody.innerHTML = emptyRow('Aucun element dans ce dossier.');
      return;
    }

    tbody.innerHTML = state.files.map((file, index) => {
      const icon = file.is_folder ? 'fa-folder' : (file.icon || 'fa-file');
      const modified = file.modified_at ? new Date(file.modified_at).toLocaleString() : '-';
      const typeLabel = file.is_folder ? 'Dossier' : (file.mime_type || '-');
      const size = file.is_folder ? '-' : (file.size_formatted || '-');

      return `
        <tr data-index="${index}">
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <i class="fas ${esc(icon)}" style="color:${esc(file.color || '#64748b')};"></i>
              <span class="${file.is_folder ? 'gd-folder-link' : ''}" data-open-folder="${index}" style="${file.is_folder ? 'cursor:pointer;color:var(--c-accent);font-weight:600;' : ''}">
                ${esc(file.name || '')}
              </span>
            </div>
          </td>
          <td>${esc(typeLabel)}</td>
          <td>${esc(size)}</td>
          <td>${esc(modified)}</td>
          <td>
            <div class="row-actions" style="justify-content:flex-end;padding-right:4px;opacity:1;">
              ${file.web_view_link ? `<a href="${esc(file.web_view_link)}" target="_blank" rel="noopener" class="btn-icon" title="Ouvrir"><i class="fas fa-arrow-up-right-from-square"></i></a>` : ''}
              ${file.is_folder ? '' : `<button class="btn-icon" data-action="download" data-index="${index}" title="Telecharger"><i class="fas fa-download"></i></button>`}
              <button class="btn-icon" data-action="rename" data-index="${index}" title="Renommer"><i class="fas fa-pen"></i></button>
              <button class="btn-icon" data-action="copy" data-index="${index}" title="Copier"><i class="fas fa-copy"></i></button>
              <button class="btn-icon" data-action="move" data-index="${index}" title="Deplacer"><i class="fas fa-right-left"></i></button>
              <button class="btn-icon" data-action="share" data-index="${index}" title="Partager"><i class="fas fa-share-nodes"></i></button>
              <button class="btn-icon danger" data-action="delete" data-index="${index}" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  function updateFolderName() {
    const current = state.files.find((f) => f.id === state.currentFolderId);
    if (current) {
      setText('gdCurrentFolderName', current.name);
      return;
    }

    if (state.currentFolderId === state.rootFolderId || !state.currentFolderId) {
      setText('gdCurrentFolderName', 'Racine');
      return;
    }

    setText('gdCurrentFolderName', 'Dossier');
  }

  function handleFileActions(e) {
    const folderTarget = e.target.closest('[data-open-folder]');
    if (folderTarget) {
      const idx = parseInt(folderTarget.dataset.openFolder, 10);
      const file = state.files[idx];
      if (file?.is_folder) {
        openFolder(file);
      }
      return;
    }

    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    const idx = parseInt(btn.dataset.index, 10);
    const file = state.files[idx];
    if (!file) return;

    if (action === 'download') {
      window.open(`${window.GDRIVE_ROUTES.fileBase}/${encodeURIComponent(file.id)}/download`, '_blank');
      return;
    }

    if (action === 'rename') {
      renameFile(file);
      return;
    }

    if (action === 'copy') {
      copyFile(file);
      return;
    }

    if (action === 'move') {
      moveFile(file);
      return;
    }

    if (action === 'share') {
      shareFile(file);
      return;
    }

    if (action === 'delete') {
      deleteFile(file);
    }
  }

  function handleTrashActions(e) {
    const btn = e.target.closest('[data-trash-action]');
    if (!btn) return;
    const id = btn.dataset.id;
    if (!id) return;

    if (btn.dataset.trashAction === 'restore') {
      restoreFile(id);
      return;
    }

    if (btn.dataset.trashAction === 'delete') {
      deleteFilePermanently(id);
    }
  }

  function openFolder(file) {
    state.folderStack.push(state.currentFolderId);
    state.currentFolderId = file.id;
    state.search = '';
    const search = document.getElementById('gdSearchInput');
    if (search) search.value = '';
    loadFiles();
  }

  function navigateBack() {
    if (!state.folderStack.length) {
      Toast.info('Information', 'Vous etes deja au niveau racine.');
      return;
    }
    state.currentFolderId = state.folderStack.pop() || state.rootFolderId;
    loadFiles();
  }

  async function createFolder() {
    const nameInput = document.getElementById('gdFolderName');
    const name = (nameInput?.value || '').trim();
    if (!name) {
      Toast.error('Validation', 'Le nom du dossier est obligatoire.');
      return;
    }

    const { ok, data } = await Http.post(window.GDRIVE_ROUTES.createFolder, {
      name,
      parent_id: state.currentFolderId || null,
    });

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de creer le dossier.');
      return;
    }

    if (nameInput) nameInput.value = '';
    Modal.close(document.getElementById('gdFolderModal'));
    Toast.success('Succes', data.message || 'Dossier cree.');
    loadFiles();
  }

  async function uploadFile(e) {
    const input = e.target;
    const file = input?.files?.[0];
    if (!file) return;

    if (file.size > 100 * 1024 * 1024) {
      Toast.error('Validation', 'Fichier trop volumineux (max 100 MB).');
      input.value = '';
      return;
    }

    const body = new FormData();
    body.append('file', file);
    if (state.currentFolderId) {
      body.append('parent_id', state.currentFolderId);
    }

    const { ok, data } = await Http.post(window.GDRIVE_ROUTES.upload, body);
    input.value = '';

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible d importer le fichier.');
      return;
    }

    Toast.success('Succes', data.message || 'Fichier importe.');
    loadFiles();
    loadStats();
  }

  async function renameFile(file) {
    const name = window.prompt('Nouveau nom', file.name || '');
    if (!name || !name.trim()) return;

    const response = await fetchWithMethod(`${window.GDRIVE_ROUTES.fileBase}/${encodeURIComponent(file.id)}/rename`, 'PATCH', { name: name.trim() });

    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de renommer ce fichier.');
      return;
    }

    Toast.success('Succes', response.data.message || 'Fichier renomme.');
    loadFiles();
  }

  async function copyFile(file) {
    const name = window.prompt('Nom de la copie', `Copie de ${file.name || ''}`) || '';
    const response = await fetchWithMethod(`${window.GDRIVE_ROUTES.fileBase}/${encodeURIComponent(file.id)}/copy`, 'POST', {
      name: name.trim(),
      target_folder_id: state.currentFolderId || null,
    });

    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de copier ce fichier.');
      return;
    }

    Toast.success('Succes', response.data.message || 'Fichier copie.');
    loadFiles();
  }

  async function moveFile(file) {
    const target = window.prompt('Identifiant du dossier cible', state.currentFolderId || '');
    if (!target || !target.trim()) return;

    const currentParent = (file.parents && file.parents.length ? file.parents[0] : state.currentFolderId) || '';
    if (!currentParent) {
      Toast.error('Validation', 'Le dossier source est introuvable.');
      return;
    }

    const response = await fetchWithMethod(`${window.GDRIVE_ROUTES.fileBase}/${encodeURIComponent(file.id)}/move`, 'PATCH', {
      target_folder_id: target.trim(),
      current_folder_id: currentParent,
    });

    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de deplacer ce fichier.');
      return;
    }

    Toast.success('Succes', response.data.message || 'Fichier deplace.');
    loadFiles();
  }

  async function shareFile(file) {
    const email = window.prompt('Email du destinataire (laisser vide pour lien public)', '');
    const payload = email && email.trim()
      ? { type: 'user', role: 'reader', email: email.trim() }
      : { type: 'anyone', role: 'reader' };

    const response = await fetchWithMethod(`${window.GDRIVE_ROUTES.fileBase}/${encodeURIComponent(file.id)}/share`, 'POST', payload);
    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de partager ce fichier.');
      return;
    }

    Toast.success('Succes', response.data.message || 'Fichier partage.');
    if (response.data.data?.web_view_link) {
      window.open(response.data.data.web_view_link, '_blank');
    }
  }

  async function deleteFile(file) {
    Modal.confirm({
      title: `Supprimer "${file.name}" ?`,
      message: 'Le fichier sera deplace dans la corbeille.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const response = await fetchWithMethod(`${window.GDRIVE_ROUTES.fileBase}/${encodeURIComponent(file.id)}`, 'DELETE', { permanent: false });
        if (!response.ok || !response.data.success) {
          Toast.error('Erreur', response.data.message || 'Impossible de supprimer ce fichier.');
          return;
        }
        Toast.success('Succes', response.data.message || 'Fichier supprime.');
        loadFiles();
      },
    });
  }

  async function openTrash() {
    const tbody = document.getElementById('gdTrashTableBody');
    if (tbody) tbody.innerHTML = skeletonRows(5, 4);

    const { ok, data } = await Http.get(window.GDRIVE_ROUTES.trashData);
    if (!ok || !data.success) {
      if (tbody) tbody.innerHTML = emptyRow('Impossible de charger la corbeille.', 4);
      Toast.error('Erreur', data.message || 'Impossible de charger la corbeille.');
      return;
    }

    const rows = data.data || [];
    if (!rows.length) {
      if (tbody) tbody.innerHTML = emptyRow('La corbeille est vide.', 4);
    } else if (tbody) {
      tbody.innerHTML = rows.map((file) => {
        const modified = file.modified_at ? new Date(file.modified_at).toLocaleString() : '-';
        return `
          <tr>
            <td>${esc(file.name || '')}</td>
            <td>${esc(file.mime_type || '-')}</td>
            <td>${esc(modified)}</td>
            <td>
              <div class="row-actions" style="justify-content:flex-end;padding-right:4px;opacity:1;">
                <button class="btn-icon" data-trash-action="restore" data-id="${esc(file.id)}" title="Restaurer"><i class="fas fa-rotate-left"></i></button>
                <button class="btn-icon danger" data-trash-action="delete" data-id="${esc(file.id)}" title="Supprimer definitivement"><i class="fas fa-trash-can"></i></button>
              </div>
            </td>
          </tr>`;
      }).join('');
    }

    Modal.open(document.getElementById('gdTrashModal'));
  }

  async function restoreFile(fileId) {
    const response = await fetchWithMethod(`${window.GDRIVE_ROUTES.fileBase}/${encodeURIComponent(fileId)}/restore`, 'POST', {});
    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de restaurer ce fichier.');
      return;
    }
    Toast.success('Succes', response.data.message || 'Fichier restaure.');
    openTrash();
    loadFiles();
  }

  async function deleteFilePermanently(fileId) {
    const response = await fetchWithMethod(`${window.GDRIVE_ROUTES.fileBase}/${encodeURIComponent(fileId)}`, 'DELETE', { permanent: true });
    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de supprimer definitivement ce fichier.');
      return;
    }
    Toast.success('Succes', response.data.message || 'Fichier supprime definitivement.');
    openTrash();
    loadFiles();
  }

  async function emptyTrash() {
    Modal.confirm({
      title: 'Vider la corbeille ?',
      message: 'Cette action supprime definitivement tous les fichiers de la corbeille.',
      confirmText: 'Vider',
      type: 'danger',
      onConfirm: async () => {
        const response = await fetchWithMethod(window.GDRIVE_ROUTES.emptyTrash, 'DELETE', {});
        if (!response.ok || !response.data.success) {
          Toast.error('Erreur', response.data.message || 'Impossible de vider la corbeille.');
          return;
        }
        Toast.success('Succes', response.data.message || 'Corbeille videe.');
        openTrash();
      },
    });
  }

  async function disconnect() {
    Modal.confirm({
      title: 'Deconnecter Google Drive ?',
      message: 'Les tokens OAuth de ce tenant seront retires.',
      confirmText: 'Deconnecter',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.post(window.GDRIVE_ROUTES.disconnect, {});
        if (!ok || !data.success) {
          Toast.error('Erreur', data.message || 'Impossible de deconnecter Google Drive.');
          return;
        }
        Toast.success('Succes', data.message || 'Google Drive deconnecte.');
        setTimeout(() => window.location.reload(), 700);
      },
    });
  }

  async function fetchWithMethod(url, method, payload) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: ['GET', 'HEAD'].includes(method) ? undefined : JSON.stringify(payload || {}),
    });

    const data = await response.json().catch(() => ({}));
    const reconnectTarget = window.CrmAuth?.resolveReconnectRedirect?.(data?.message, data);
    if (reconnectTarget) {
      window.CrmAuth.redirectToReconnect(
        data?.message || 'La session Google Drive a expire. Redirection vers la reconnexion.',
        reconnectTarget
      );
    }
    return { ok: response.ok, status: response.status, data };
  }

  function skeletonRows(count, cols) {
    return Array.from({ length: count }, () => `<tr>${Array.from({ length: cols }, () => '<td><div class="skeleton" style="height:13px;"></div></td>').join('')}</tr>`).join('');
  }

  function emptyRow(message, colSpan = 5) {
    return `<tr><td colspan="${colSpan}"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-folder-open"></i></div><h3>Aucune donnee</h3><p>${esc(message)}</p></div></td></tr>`;
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  return { boot };
})();

window.GoogleDriveModule = GoogleDriveModule;
