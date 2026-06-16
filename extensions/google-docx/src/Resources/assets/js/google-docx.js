'use strict';

const GoogleDocxModule = (() => {
  const state = {
    connected: false,
    documents: [],
    currentDocument: null,
    search: '',
    debounceTimer: null,
  };

  function boot(bootstrap = {}) {
    state.connected = !!bootstrap.connected;
    bindActions();

    if (!state.connected) return;

    loadStats();
    loadDocuments();
  }

  function bindActions() {
    document.getElementById('gdxRefreshBtn')?.addEventListener('click', () => {
      loadStats();
      loadDocuments();
    });

    document.getElementById('gdxDisconnectBtn')?.addEventListener('click', disconnect);
    document.getElementById('gdxSaveDocumentBtn')?.addEventListener('click', createDocument);

    document.getElementById('gdxSearchInput')?.addEventListener('input', (e) => {
      clearTimeout(state.debounceTimer);
      state.debounceTimer = setTimeout(() => {
        state.search = (e.target.value || '').trim();
        if (state.search.length >= 2 || state.search.length === 0) {
          loadDocuments();
        }
      }, 300);
    });

    document.getElementById('gdxDocumentsTableBody')?.addEventListener('click', handleActions);
    document.getElementById('gdxAppendBtn')?.addEventListener('click', appendText);
    document.getElementById('gdxReplaceBtn')?.addEventListener('click', replaceText);
  }
  function normalizeReconnectText(message) {
    return String(message || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase();
  }
  function isReconnectRequiredMessage(message) {
    const text = normalizeReconnectText(message);
    return text.includes('session google docs expiree')
      || text.includes('reconnectez votre compte google')
      || text.includes('reconnectez google docs')
      || text.includes('invalid_grant');
  }
  function promptReconnect(message) {
    const text = String(message || 'Session Google Docs expir\u00e9e ou r\u00e9voqu\u00e9e. Reconnectez votre compte Google.').trim();
    const reconnectUrl = window.GDOCX_ROUTES?.connect || '';
    if (!window.Modal || typeof window.Modal.confirm !== 'function' || !reconnectUrl) {
      Toast.error('Erreur', text);
      return;
    }
    Modal.confirm({
      title: 'Reconnecter Google Docs ?',
      message: text,
      confirmText: 'Reconnecter',
      type: 'warning',
      onConfirm: () => {
        window.location.href = reconnectUrl;
      },
      onCancel: () => {
        window.location.reload();
      },
    });
  }
  function handleFailure(title, message, fallback) {
    const resolved = String(message || fallback || 'Une erreur est survenue.').trim();
    if (isReconnectRequiredMessage(resolved)) {
      promptReconnect(resolved);
      return true;
    }
    Toast.error(title || 'Erreur', resolved);
    return false;
  }
  async function loadStats() {
    const { ok, data } = await Http.get(window.GDOCX_ROUTES.stats);
    if (!ok || !data.success) return;

    const stats = data.data || {};
    setText('gdxStatDocuments', stats.total_documents || 0);
    if (stats.last_sync_at) {
      setText('gdxLastSyncLabel', new Date(stats.last_sync_at).toLocaleString());
    }
  }

  async function loadDocuments() {
    const tbody = document.getElementById('gdxDocumentsTableBody');
    if (tbody) tbody.innerHTML = skeletonRows(6, 5);

    const { ok, data } = await Http.get(window.GDOCX_ROUTES.documentsData, { search: state.search });

    if (!ok || !data.success) {
      if (tbody) tbody.innerHTML = emptyRow('Impossible de charger les documents.');
      handleFailure('Erreur', data?.message, 'Impossible de charger les documents.');
      return;
    }

    state.documents = data.data?.documents || [];
    renderDocuments();
    setText('gdxCount', `${state.documents.length} rÃƒÂ©sultat(s)`);
  }

  function renderDocuments() {
    const tbody = document.getElementById('gdxDocumentsTableBody');
    if (!tbody) return;

    if (!state.documents.length) {
      tbody.innerHTML = emptyRow('Aucun document trouvÃƒÂ©.');
      return;
    }

    tbody.innerHTML = state.documents.map((doc, idx) => {
      const modified = doc.modified_at ? new Date(doc.modified_at).toLocaleString() : '-';
      const created = doc.created_at ? new Date(doc.created_at).toLocaleString() : '-';
      const shared = doc.is_shared
        ? '<span style="background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:99px;font-size:10.5px;font-weight:600;">PartagÃƒÂ©</span>'
        : '';

      return `
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:34px;height:34px;background:#1a73e818;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-file-word" style="color:#1a73e8;font-size:16px;"></i>
              </div>
              <div>
                <div style="font-weight:var(--fw-medium);color:var(--c-ink);">${esc(doc.title || 'Sans titre')}</div>
                <div style="font-size:11.5px;color:var(--c-ink-40);font-family:monospace;">${esc(doc.document_id)}</div>
              </div>
            </div>
          </td>
          <td>${esc(created)}</td>
          <td>${esc(modified)}</td>
          <td>${shared}</td>
          <td>
            <div class="row-actions" style="justify-content:flex-end;padding-right:4px;opacity:1;">
              ${doc.document_url ? `<a href="${esc(doc.document_url)}" target="_blank" rel="noopener" class="btn-icon" title="Ouvrir dans Google Docs"><i class="fas fa-arrow-up-right-from-square"></i></a>` : ''}
              <button class="btn-icon" data-action="open" data-index="${idx}" title="Ouvrir"><i class="fas fa-eye"></i></button>
              <button class="btn-icon" data-action="rename" data-index="${idx}" title="Renommer"><i class="fas fa-pen"></i></button>
              <button class="btn-icon" data-action="duplicate" data-index="${idx}" title="Dupliquer"><i class="fas fa-copy"></i></button>
              <button class="btn-icon" data-action="export" data-index="${idx}" title="Exporter"><i class="fas fa-download"></i></button>
              <button class="btn-icon danger" data-action="delete" data-index="${idx}" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  function handleActions(e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    const idx = parseInt(btn.dataset.index, 10);
    const doc = state.documents[idx];
    if (!doc) return;

    if (action === 'open') openEditor(doc);
    if (action === 'rename') renameDocument(doc);
    if (action === 'duplicate') duplicateDocument(doc);
    if (action === 'delete') deleteDocument(doc);
    if (action === 'export') exportDocument(doc);
  }

  async function createDocument() {
    const titleInput = document.getElementById('gdxDocumentTitle');
    const contentInput = document.getElementById('gdxDocumentContent');
    const title = (titleInput?.value || '').trim();
    const content = (contentInput?.value || '').trim();

    if (!title) {
      Toast.error('Validation', 'Le titre est obligatoire.');
      return;
    }

    const { ok, data } = await Http.post(window.GDOCX_ROUTES.createDocument, { title, content });

    if (!ok || !data.success) {
      handleFailure('Erreur', data?.message, 'Impossible de cr?er le document.');
      return;
    }

    if (titleInput) titleInput.value = '';
    if (contentInput) contentInput.value = '';
    Modal.close(document.getElementById('gdxCreateModal'));

    Toast.success('SuccÃƒÂ¨s', data.message || 'Document crÃƒÂ©ÃƒÂ©.');
    loadDocuments();
    loadStats();
  }

  async function openEditor(doc) {
    const { ok, data } = await Http.get(`${window.GDOCX_ROUTES.documentBase}/${encodeURIComponent(doc.document_id)}`);
    if (!ok || !data.success) {
      handleFailure('Erreur', data?.message, 'Impossible de charger le document.');
      return;
    }

    state.currentDocument = data.data;
    setText('gdxEditorTitle', state.currentDocument.title || 'Document');

    const textArea = document.getElementById('gdxCurrentContent');
    if (textArea) {
      textArea.value = state.currentDocument.body_text || '';
    }

    const append = document.getElementById('gdxAppendText');
    const search = document.getElementById('gdxSearchText');
    const replace = document.getElementById('gdxReplaceText');
    if (append) append.value = '';
    if (search) search.value = '';
    if (replace) replace.value = '';

    Modal.open(document.getElementById('gdxEditorModal'));
  }

  async function renameDocument(doc) {
    const title = window.prompt('Nouveau titre', doc.title || '');
    if (!title || !title.trim()) return;

    const res = await fetchWithMethod(
      `${window.GDOCX_ROUTES.documentBase}/${encodeURIComponent(doc.document_id)}/rename`,
      'PATCH',
      { title: title.trim() }
    );

    if (!res.ok || !res.data.success) {
      handleFailure('Erreur', res.data?.message, 'Impossible de renommer le document.');
      return;
    }

    Toast.success('SuccÃƒÂ¨s', res.data.message || 'Document renommÃƒÂ©.');
    loadDocuments();
  }

  async function duplicateDocument(doc) {
    const title = window.prompt('Titre de la copie', `Copie de ${doc.title || ''}`) || '';
    const { ok, data } = await Http.post(
      `${window.GDOCX_ROUTES.documentBase}/${encodeURIComponent(doc.document_id)}/duplicate`,
      { title: title.trim() }
    );

    if (!ok || !data.success) {
      handleFailure('Erreur', data?.message, 'Impossible de dupliquer le document.');
      return;
    }

    Toast.success('SuccÃƒÂ¨s', data.message || 'Document dupliquÃƒÂ©.');
    loadDocuments();
    loadStats();
  }

  async function deleteDocument(doc) {
    Modal.confirm({
      title: `Supprimer "${doc.title}" ?`,
      message: 'Ce document sera supprimÃƒÂ© dÃƒÂ©finitivement de Google Drive.',
      confirmText: 'Supprimer',
      type: 'danger',
      onConfirm: async () => {
        const res = await fetchWithMethod(
          `${window.GDOCX_ROUTES.documentBase}/${encodeURIComponent(doc.document_id)}`,
          'DELETE',
          {}
        );

        if (!res.ok || !res.data.success) {
          handleFailure('Erreur', res.data?.message, 'Impossible de supprimer le document.');
          return;
        }

        Toast.success('SupprimÃƒÂ©', res.data.message || 'Document supprimÃƒÂ©.');
        loadDocuments();
        loadStats();
      },
    });
  }

  function exportDocument(doc) {
    const format = window.prompt('Format d\'export: txt, html, pdf, docx', 'txt') || 'txt';
    const allowed = ['txt', 'html', 'pdf', 'docx'];
    const finalFormat = allowed.includes(format.trim().toLowerCase()) ? format.trim().toLowerCase() : 'txt';

    const url = `${window.GDOCX_ROUTES.documentBase}/${encodeURIComponent(doc.document_id)}/export?format=${encodeURIComponent(finalFormat)}`;
    window.open(url, '_blank');
  }

  async function appendText() {
    if (!state.currentDocument?.document_id) return;

    const textEl = document.getElementById('gdxAppendText');
    const text = (textEl?.value || '').trim();

    if (!text) {
      Toast.error('Validation', 'Le texte ÃƒÂ  ajouter est obligatoire.');
      return;
    }

    const { ok, data } = await Http.post(
      `${window.GDOCX_ROUTES.documentBase}/${encodeURIComponent(state.currentDocument.document_id)}/append`,
      { text }
    );

    if (!ok || !data.success) {
      handleFailure('Erreur', data?.message, 'Impossible d\'ajouter le texte.');
      return;
    }

    Toast.success('SuccÃƒÂ¨s', data.message || 'Texte ajoutÃƒÂ©.');

    if (textEl) textEl.value = '';
    state.currentDocument = data.data;
    const current = document.getElementById('gdxCurrentContent');
    if (current) current.value = state.currentDocument.body_text || '';

    loadDocuments();
    loadStats();
  }

  async function replaceText() {
    if (!state.currentDocument?.document_id) return;

    const searchEl = document.getElementById('gdxSearchText');
    const replaceEl = document.getElementById('gdxReplaceText');
    const search = (searchEl?.value || '').trim();
    const replace = (replaceEl?.value || '').trim();

    if (!search) {
      Toast.error('Validation', 'Le texte ÃƒÂ  rechercher est obligatoire.');
      return;
    }

    const { ok, data } = await Http.post(
      `${window.GDOCX_ROUTES.documentBase}/${encodeURIComponent(state.currentDocument.document_id)}/replace`,
      { search, replace, match_case: false }
    );

    if (!ok || !data.success) {
      handleFailure('Erreur', data?.message, 'Impossible de remplacer le texte.');
      return;
    }

    const changed = data.data?.replace_occurrences ?? 0;
    Toast.success('SuccÃƒÂ¨s', `${changed} occurrence(s) remplacÃƒÂ©e(s).`);

    state.currentDocument = data.data;
    const current = document.getElementById('gdxCurrentContent');
    if (current) current.value = state.currentDocument.body_text || '';

    loadDocuments();
    loadStats();
  }

  async function disconnect() {
    Modal.confirm({
      title: 'DÃƒÂ©connecter Google Docs ?',
      message: 'Les jetons OAuth seront supprimÃƒÂ©s pour ce tenant.',
      confirmText: 'DÃƒÂ©connecter',
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.post(window.GDOCX_ROUTES.disconnect, {});

        if (!ok || !data.success) {
          handleFailure('Erreur', data?.message, 'Impossible de d?connecter Google Docs.');
          return;
        }

        Toast.success('DÃƒÂ©connectÃƒÂ©', data.message || 'Google Docs dÃƒÂ©connectÃƒÂ©.');
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
        Accept: 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: ['GET', 'HEAD'].includes(method) ? undefined : JSON.stringify(payload || {}),
    });

    const data = await response.json().catch(() => ({}));
    const reconnectTarget = window.CrmAuth?.resolveReconnectRedirect?.(data?.message, data);
    if (reconnectTarget) {
      window.CrmAuth.redirectToReconnect(
        data?.message || 'La session Google Docs a expire. Redirection vers la reconnexion.',
        reconnectTarget
      );
    }
    return { ok: response.ok, status: response.status, data };
  }

  function skeletonRows(count, cols) {
    return Array.from({ length: count }, () =>
      `<tr>${Array.from({ length: cols }, () => '<td><div class="skeleton" style="height:13px;"></div></td>').join('')}</tr>`
    ).join('');
  }

  function emptyRow(message) {
    return `<tr><td colspan="5"><div class="table-empty"><div class="table-empty-icon"><i class="fas fa-file-word"></i></div><h3>Aucune donnÃƒÂ©e</h3><p>${esc(message)}</p></div></td></tr>`;
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

  return {
    boot,
    handleFailure,
  };
})();

window.GoogleDocxModule = GoogleDocxModule;
