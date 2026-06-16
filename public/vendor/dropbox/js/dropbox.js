'use strict';

const DropboxModule = (() => {
  const state = {
    connected: false,
    currentFolderId: null,
    rootFolderId: null,
    folderStack: [],
    files: [],
    search: '',
    debounceTimer: null,
    shareTarget: null,
    uploadFiles: [],
    uploadErrors: [],
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
    document.getElementById('dbxRefreshBtn')?.addEventListener('click', () => {
      loadStats();
      loadFiles();
    });

    document.getElementById('dbxDisconnectBtn')?.addEventListener('click', disconnect);
    document.getElementById('dbxSaveFolderBtn')?.addEventListener('click', createFolder);
    document.getElementById('dbxSaveUploadBtn')?.addEventListener('click', uploadFileFromModal);
    document.getElementById('dbxUploadModalInput')?.addEventListener('change', syncUploadSelection);
    document.getElementById('dbxUploadModal')?.addEventListener('crm:modal-open', syncUploadTarget);
    document.getElementById('dbxUploadModal')?.addEventListener('crm:modal-close', resetUploadModal);
    document.getElementById('dbxUploadFilesList')?.addEventListener('click', handleUploadFileActions);
    document.getElementById('dbxShareType')?.addEventListener('change', toggleShareEmailField);
    document.getElementById('dbxSaveShareBtn')?.addEventListener('click', submitShare);
    document.getElementById('dbxShareModal')?.addEventListener('crm:modal-close', resetShareModal);
    document.getElementById('dbxBackBtn')?.addEventListener('click', navigateBack);
    document.getElementById('dbxTrashBtn')?.addEventListener('click', openTrash);
    document.getElementById('dbxEmptyTrashBtn')?.addEventListener('click', emptyTrash);

    document.getElementById('dbxSearchInput')?.addEventListener('input', (e) => {
      clearTimeout(state.debounceTimer);
      state.debounceTimer = setTimeout(() => {
        state.search = (e.target.value || '').trim();
        if (state.search.length >= 2 || state.search.length === 0) {
          loadFiles();
        }
      }, 300);
    });

    document.getElementById('dbxFilesTableBody')?.addEventListener('click', handleFileActions);
    document.getElementById('dbxTrashTableBody')?.addEventListener('click', handleTrashActions);
  }

  async function loadStats() {
    const { ok, data } = await Http.get(window.DROPBOX_ROUTES.stats);
    if (!ok || !data.success) {
      return;
    }

    const stats = data.data || {};
    setText('dbxUsedStorage', `${stats.used_gb || 0} GB`);
    setText('dbxTotalStorage', `${stats.total_gb || 0} GB`);
  }

  async function loadFiles() {
    const tbody = document.getElementById('dbxFilesTableBody');
    if (tbody) {
      tbody.innerHTML = skeletonRows(6, 5);
    }

    const params = {
      folder_id: state.currentFolderId || '',
      search: state.search || '',
    };

    const { ok, data } = await Http.get(window.DROPBOX_ROUTES.filesData, params);
    if (!ok || !data.success) {
      if (tbody) tbody.innerHTML = emptyRow('Impossible de charger les fichiers.');
      Toast.error('Erreur', data.message || 'Impossible de charger les fichiers.');
      return;
    }

    const payload = data.data || {};
    state.files = payload.files || [];
    state.rootFolderId = payload.root_folder || state.rootFolderId;

    if (!state.currentFolderId) {
      state.currentFolderId = payload.folder_id || state.rootFolderId || null;
    }

    renderFiles();
    setText('dbxCount', `${state.files.length} element(s)`);
    updateFolderName();
  }

  function renderFiles() {
    const tbody = document.getElementById('dbxFilesTableBody');
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
              <span class="${file.is_folder ? 'dbx-folder-link' : ''}" data-open-folder="${index}" style="${file.is_folder ? 'cursor:pointer;color:var(--c-accent);font-weight:600;' : ''}">
                ${esc(file.name || '')}
              </span>
            </div>
          </td>
          <td>${esc(typeLabel)}</td>
          <td>${esc(size)}</td>
          <td>${esc(modified)}</td>
          <td>
            <div class="row-actions" style="justify-content:flex-end;padding-right:4px;opacity:1;">
              <button class="btn-icon" data-action="open" data-index="${index}" title="Ouvrir"><i class="fas fa-arrow-up-right-from-square"></i></button>
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
      setText('dbxCurrentFolderName', current.name);
      return;
    }

    if (state.currentFolderId === state.rootFolderId || !state.currentFolderId) {
      setText('dbxCurrentFolderName', 'Racine');
      return;
    }

    setText('dbxCurrentFolderName', 'Dossier');
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

    if (action === 'open') {
      window.open(`${window.DROPBOX_ROUTES.fileBase}/${encodeURIComponent(file.id)}/open`, '_blank');
      return;
    }

    if (action === 'download') {
      window.open(`${window.DROPBOX_ROUTES.fileBase}/${encodeURIComponent(file.id)}/download`, '_blank');
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
    const search = document.getElementById('dbxSearchInput');
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
    const nameInput = document.getElementById('dbxFolderName');
    const name = (nameInput?.value || '').trim();
    if (!name) {
      Toast.error('Validation', 'Le nom du dossier est obligatoire.');
      return;
    }

    const { ok, data } = await Http.post(window.DROPBOX_ROUTES.createFolder, {
      name,
      parent_id: state.currentFolderId || null,
    });

    if (!ok || !data.success) {
      Toast.error('Erreur', data.message || 'Impossible de creer le dossier.');
      return;
    }

    if (nameInput) nameInput.value = '';
    Modal.close(document.getElementById('dbxFolderModal'));
    Toast.success('Succes', data.message || 'Dossier cree.');
    loadFiles();
  }

  function syncUploadTarget() {
    setText('dbxUploadTargetName', currentFolderLabel());
    renderUploadSelection();
  }

  function syncUploadSelection() {
    const input = document.getElementById('dbxUploadModalInput');
    const selectedFiles = Array.from(input?.files || []);
    if (!selectedFiles.length) {
      return;
    }

    const nextErrors = [];
    selectedFiles.forEach((file) => {
      if (!file) {
        return;
      }

      if (file.size > 100 * 1024 * 1024) {
        nextErrors.push(`${file.name} depasse la limite de 100 MB.`);
        return;
      }

      const key = uploadFileKey(file);
      if (!state.uploadFiles.some((entry) => uploadFileKey(entry) === key)) {
        state.uploadFiles.push(file);
      }
    });

    state.uploadErrors = nextErrors;
    if (nextErrors.length) {
      Toast.error('Validation', nextErrors[0]);
    }

    if (input) {
      input.value = '';
    }
    renderUploadSelection();
  }

  function resetUploadModal() {
    const input = document.getElementById('dbxUploadModalInput');
    if (input) {
      input.value = '';
    }
    state.uploadFiles = [];
    state.uploadErrors = [];
    renderUploadSelection();
    setText('dbxUploadTargetName', currentFolderLabel());
  }

  async function uploadFileFromModal() {
    const button = document.getElementById('dbxSaveUploadBtn');
    if (!state.uploadFiles.length) {
      Toast.error('Validation', 'Choisissez au moins un fichier a importer avant de continuer.');
      return;
    }

    if (state.uploadErrors.length) {
      Toast.error('Validation', state.uploadErrors[0] || 'Corrigez les fichiers invalides avant de continuer.');
      return;
    }

    const body = new FormData();
    state.uploadFiles.forEach((file) => {
      body.append('files[]', file);
    });
    if (state.currentFolderId) {
      body.append('parent_id', state.currentFolderId);
    }

    if (window.CrmForm && button) {
      window.CrmForm.setLoading(button, true);
    }

    const { ok, data } = await Http.post(window.DROPBOX_ROUTES.upload, body);
    if (window.CrmForm && button) {
      window.CrmForm.setLoading(button, false);
    }

    if (!ok || !data.success) {
      const firstError = extractUploadError(data);
      Toast.error('Erreur', firstError || data.message || 'Impossible d\'envoyer les fichiers.');
      return;
    }

    state.uploadFiles = [];
    state.uploadErrors = [];
    renderUploadSelection();
    Modal.close(document.getElementById('dbxUploadModal'));
    Toast.success('Succes', data.message || 'Fichiers envoyes.');
    loadFiles();
    loadStats();
  }

  function handleUploadFileActions(e) {
    const button = e.target.closest('[data-upload-remove]');
    if (!button) return;

    const index = parseInt(button.dataset.uploadRemove, 10);
    if (Number.isNaN(index) || !state.uploadFiles[index]) {
      return;
    }

    state.uploadFiles.splice(index, 1);
    renderUploadSelection();
  }

  function renderUploadSelection() {
    const summary = document.getElementById('dbxUploadSelectedName');
    const list = document.getElementById('dbxUploadFilesList');
    const errors = document.getElementById('dbxUploadErrors');

    if (summary) {
      if (!state.uploadFiles.length) {
        summary.textContent = 'Aucun fichier selectionne';
      } else if (state.uploadFiles.length === 1) {
        const file = state.uploadFiles[0];
        summary.textContent = `1 fichier selectionne - ${file.name} (${formatBytes(file.size || 0)})`;
      } else {
        summary.textContent = `${state.uploadFiles.length} fichiers selectionnes.`;
      }
    }

    if (errors) {
      if (state.uploadErrors.length) {
        errors.style.display = '';
        errors.innerHTML = state.uploadErrors.map((message) => `<div>${esc(message)}</div>`).join('');
      } else {
        errors.style.display = 'none';
        errors.innerHTML = '';
      }
    }

    if (!list) {
      return;
    }

    if (!state.uploadFiles.length) {
      list.innerHTML = '';
      return;
    }

    list.innerHTML = state.uploadFiles.map((file, index) => `
      <div class="dbx-upload-file-item">
        <div class="dbx-upload-file-main">
          <span class="dbx-upload-file-icon"><i class="fas fa-file-arrow-up"></i></span>
          <div class="dbx-upload-file-text">
            <div class="dbx-upload-file-name">${esc(file.name || 'Fichier')}</div>
            <div class="dbx-upload-file-meta">${esc(file.type || 'application/octet-stream')} - ${esc(formatBytes(file.size || 0))}</div>
          </div>
        </div>
        <button type="button" class="dbx-upload-file-remove" data-upload-remove="${index}" title="Retirer ce fichier" aria-label="Retirer ce fichier">
          <i class="fas fa-xmark"></i>
        </button>
      </div>
    `).join('');
  }

  async function renameFile(file) {
    const name = window.prompt('Nouveau nom', file.name || '');
    if (!name || !name.trim()) return;

    const response = await fetchWithMethod(`${window.DROPBOX_ROUTES.fileBase}/${encodeURIComponent(file.id)}/rename`, 'PATCH', { name: name.trim() });

    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de renommer cet element.');
      return;
    }

    Toast.success('Succes', response.data.message || 'Element renomme.');
    loadFiles();
  }

  async function copyFile(file) {
    const name = window.prompt('Nom de la copie', `Copie - ${file.name || ''}`) || '';
    const response = await fetchWithMethod(`${window.DROPBOX_ROUTES.fileBase}/${encodeURIComponent(file.id)}/copy`, 'POST', {
      name: name.trim(),
      target_folder_id: state.currentFolderId || null,
    });

    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de copier cet element.');
      return;
    }

    Toast.success('Succes', response.data.message || 'Element copie.');
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

    const response = await fetchWithMethod(`${window.DROPBOX_ROUTES.fileBase}/${encodeURIComponent(file.id)}/move`, 'PATCH', {
      target_folder_id: target.trim(),
      current_folder_id: currentParent,
    });

    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de deplacer cet element.');
      return;
    }

    Toast.success('Succes', response.data.message || 'Element deplace.');
    loadFiles();
  }

  async function shareFile(file) {
    state.shareTarget = file;
    setText('dbxShareTargetName', file?.name || 'Element Dropbox');

    const typeSelect = document.getElementById('dbxShareType');
    const roleSelect = document.getElementById('dbxShareRole');
    const emailInput = document.getElementById('dbxShareEmail');

    if (typeSelect) typeSelect.value = 'anyone';
    if (roleSelect) roleSelect.value = 'reader';
    if (emailInput) emailInput.value = '';

    toggleShareEmailField();
    Modal.open(document.getElementById('dbxShareModal'));
  }

  function toggleShareEmailField() {
    const type = document.getElementById('dbxShareType')?.value || 'anyone';
    const group = document.getElementById('dbxShareEmailGroup');
    const input = document.getElementById('dbxShareEmail');
    const requiresEmail = type === 'user';

    if (group) {
      group.style.display = requiresEmail ? '' : 'none';
    }

    if (input) {
      input.required = requiresEmail;
      if (!requiresEmail) {
        input.value = '';
      }
    }
  }

  function resetShareModal() {
    state.shareTarget = null;
    setText('dbxShareTargetName', 'Aucun element selectionne');

    const typeSelect = document.getElementById('dbxShareType');
    const roleSelect = document.getElementById('dbxShareRole');
    const emailInput = document.getElementById('dbxShareEmail');

    if (typeSelect) typeSelect.value = 'anyone';
    if (roleSelect) roleSelect.value = 'reader';
    if (emailInput) {
      emailInput.value = '';
      emailInput.required = false;
    }

    toggleShareEmailField();
  }

  async function submitShare() {
    const file = state.shareTarget;
    if (!file?.id) {
      Toast.error('Erreur', 'Aucun element Dropbox a partager.');
      return;
    }

    const type = document.getElementById('dbxShareType')?.value || 'anyone';
    const role = document.getElementById('dbxShareRole')?.value || 'reader';
    const email = (document.getElementById('dbxShareEmail')?.value || '').trim();
    const button = document.getElementById('dbxSaveShareBtn');

    if (type === 'user' && !email) {
      Toast.error('Validation', 'L\'email du destinataire est obligatoire pour ce type de partage.');
      return;
    }

    if (type === 'user' && !isValidEmail(email)) {
      Toast.error('Validation', 'Veuillez saisir une adresse email valide.');
      return;
    }

    if (window.CrmForm && button) {
      window.CrmForm.setLoading(button, true);
    }

    const response = await fetchWithMethod(`${window.DROPBOX_ROUTES.fileBase}/${encodeURIComponent(file.id)}/share`, 'POST', {
      type,
      role,
      email: type === 'user' ? email : null,
    });

    if (window.CrmForm && button) {
      window.CrmForm.setLoading(button, false);
    }

    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de creer le lien de partage.');
      return;
    }

    const sharedUrl = response.data.data?.web_view_link || response.data.data?.download_link || '';
    const copied = sharedUrl ? await copyToClipboard(sharedUrl) : false;
    Modal.close(document.getElementById('dbxShareModal'));
    Toast.success(
      'Succes',
      copied
        ? 'Lien de partage cree et copie dans le presse-papiers.'
        : (response.data.message || 'Lien de partage cree.')
    );

    if (sharedUrl) {
      window.open(sharedUrl, '_blank');
    }
    loadFiles();
  }

  async function deleteFile(file) {
    Modal.confirm({
      title: `Supprimer "${file.name}" ?`,
      message: 'L\'element sera deplace dans la corbeille Dropbox.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const response = await fetchWithMethod(`${window.DROPBOX_ROUTES.fileBase}/${encodeURIComponent(file.id)}`, 'DELETE', { permanent: false });
        if (!response.ok || !response.data.success) {
          Toast.error('Erreur', response.data.message || 'Impossible de supprimer cet element.');
          return;
        }
        Toast.success('Succes', response.data.message || 'Element supprime.');
        loadFiles();
      },
    });
  }

  async function openTrash() {
    const tbody = document.getElementById('dbxTrashTableBody');
    if (tbody) tbody.innerHTML = skeletonRows(5, 4);

    const { ok, data } = await Http.get(window.DROPBOX_ROUTES.trashData);
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

    Modal.open(document.getElementById('dbxTrashModal'));
  }

  async function restoreFile(fileId) {
    const response = await fetchWithMethod(`${window.DROPBOX_ROUTES.fileBase}/${encodeURIComponent(fileId)}/restore`, 'POST', {});
    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de restaurer cet element.');
      return;
    }
    Toast.success('Succes', response.data.message || 'Element restaure.');
    openTrash();
    loadFiles();
  }

  async function deleteFilePermanently(fileId) {
    const response = await fetchWithMethod(`${window.DROPBOX_ROUTES.fileBase}/${encodeURIComponent(fileId)}`, 'DELETE', { permanent: true });
    if (!response.ok || !response.data.success) {
      Toast.error('Erreur', response.data.message || 'Impossible de supprimer definitivement cet element.');
      return;
    }
    Toast.success('Succes', response.data.message || 'Element supprime definitivement.');
    openTrash();
    loadFiles();
  }

  async function emptyTrash() {
    Modal.confirm({
      title: 'Vider la corbeille Dropbox ?',
      message: 'Tous les elements supprimes seront effaces definitivement.',
      confirmText: 'Vider',
      type: 'danger',
      onConfirm: async () => {
        const response = await fetchWithMethod(window.DROPBOX_ROUTES.emptyTrash, 'DELETE', {});
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
      title: 'Deconnecter Dropbox ?',
      message: 'Les tokens OAuth de ce tenant seront retires.',
      confirmText: 'Deconnecter',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.post(window.DROPBOX_ROUTES.disconnect, {});
        if (!ok || !data.success) {
          Toast.error('Erreur', data.message || 'Impossible de deconnecter Dropbox.');
          return;
        }
        Toast.success('Succes', data.message || 'Dropbox deconnecte.');
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
        data?.message || 'La session Dropbox a expire. Redirection vers la reconnexion.',
        reconnectTarget
      );
    }
    return { ok: response.ok, status: response.status, data };
  }

  function skeletonRows(count, cols) {
    return Array.from({ length: count }, () => `<tr>${Array.from({ length: cols }, () => '<td><div class="skeleton" style="height:13px;"></div></td>').join('')}</tr>`).join('');
  }

  function emptyRow(message, colSpan = 5) {
    return `<tr><td colspan="${colSpan}"><div class="table-empty"><div class="table-empty-icon"><i class="fab fa-dropbox"></i></div><h3>Aucune donnee</h3><p>${esc(message)}</p></div></td></tr>`;
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = String(value);
  }

  async function copyToClipboard(value) {
    if (!value || !navigator.clipboard?.writeText) {
      return false;
    }

    try {
      await navigator.clipboard.writeText(value);
      return true;
    } catch (e) {
      return false;
    }
  }

  function currentFolderLabel() {
    const current = state.files.find((f) => f.id === state.currentFolderId);
    if (current?.name) {
      return current.name;
    }

    if (state.currentFolderId === state.rootFolderId || !state.currentFolderId) {
      return 'Racine';
    }

    return 'Dossier courant';
  }

  function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '0 B';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    if (bytes < 1073741824) return `${(bytes / 1048576).toFixed(1)} MB`;

    return `${(bytes / 1073741824).toFixed(2)} GB`;
  }

  function uploadFileKey(file) {
    return [file?.name || '', file?.size || 0, file?.lastModified || 0].join('::');
  }

  function extractUploadError(data) {
    const errors = data?.errors || {};
    const firstKey = Object.keys(errors)[0];
    if (firstKey && Array.isArray(errors[firstKey]) && errors[firstKey][0]) {
      return errors[firstKey][0];
    }

    return data?.message || '';
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function esc(value) {
    const div = document.createElement('div');
    div.textContent = value || '';
    return div.innerHTML;
  }

  return { boot };
})();

window.DropboxModule = DropboxModule;
