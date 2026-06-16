const NotionWorkspaceModule = (() => {
  const routes = window.NOTION_WORKSPACE_ROUTES || {};
  const bootstrap = window.NOTION_WORKSPACE_BOOTSTRAP || {};
  const state = {
    connected: Boolean(bootstrap.connected),
    pages: [],
    links: Array.isArray(bootstrap.links) ? bootstrap.links.slice() : [],
    nextCursor: null,
    hasMore: false,
    query: '',
    selectedPage: null,
    selectedPageId: null,
    createParentId: null,
    searchTimer: null,
    loadingPages: false,
  };

  const els = {};

  function boot() {
    els.root = document.getElementById('notionWorkspaceApp');
    if (!els.root) return;

    cacheElements();
    bindEvents();
    renderLinks();
    renderPages();

    if (state.connected) {
      loadPages({ append: false });
    }
  }

  function cacheElements() {
    els.searchInput = document.getElementById('notionSearchInput');
    els.refreshPagesBtn = document.getElementById('notionRefreshPagesBtn');
    els.pageList = document.getElementById('notionPageList');
    els.searchStatus = document.getElementById('notionSearchStatus');
    els.pagesCountLabel = document.getElementById('notionPagesCountLabel');
    els.loadMoreBtn = document.getElementById('notionLoadMoreBtn');
    els.statLoadedPages = document.getElementById('notionStatLoadedPages');
    els.statLinkedPages = document.getElementById('notionStatLinkedPages');
    els.statLinkedClients = document.getElementById('notionStatLinkedClients');
    els.statLinkedProjects = document.getElementById('notionStatLinkedProjects');
    els.previewEmpty = document.getElementById('notionPreviewEmpty');
    els.preview = document.getElementById('notionPreview');
    els.previewCover = document.getElementById('notionPreviewCover');
    els.previewIcon = document.getElementById('notionPreviewIcon');
    els.previewTitle = document.getElementById('notionPreviewTitle');
    els.previewMeta = document.getElementById('notionPreviewMeta');
    els.openExternalBtn = document.getElementById('notionOpenExternalBtn');
    els.blocksPreview = document.getElementById('notionBlocksPreview');
    els.linkForm = document.getElementById('notionLinkForm');
    els.linkId = document.getElementById('notionLinkId');
    els.linkClientId = document.getElementById('notionLinkClientId');
    els.linkProjectId = document.getElementById('notionLinkProjectId');
    els.linkContextLabel = document.getElementById('notionLinkContextLabel');
    els.linkNotes = document.getElementById('notionLinkNotes');
    els.linkStatus = document.getElementById('notionLinkStatus');
    els.deleteLinkBtn = document.getElementById('notionDeleteLinkBtn');
    els.saveLinkBtn = document.getElementById('notionSaveLinkBtn');
    els.disconnectBtn = document.getElementById('notionDisconnectBtn');
    els.linkedList = document.getElementById('notionLinkedList');
    els.createParentLabel = document.getElementById('notionCreateParentLabel');
    els.createTitle = document.getElementById('notionCreateTitle');
    els.createIcon = document.getElementById('notionCreateIcon');
    els.createContent = document.getElementById('notionCreateContent');
    els.createPageSubmitBtn = document.getElementById('notionCreatePageSubmitBtn');
    els.createModal = document.getElementById('notionCreatePageModal');
    els.openCreateModalBtn = document.getElementById('notionOpenCreateModalBtn');
  }

  function bindEvents() {
    if (els.searchInput) {
      els.searchInput.addEventListener('input', () => {
        window.clearTimeout(state.searchTimer);
        state.searchTimer = window.setTimeout(() => {
          state.query = els.searchInput.value.trim();
          loadPages({ append: false });
        }, 250);
      });
    }

    if (els.refreshPagesBtn) {
      els.refreshPagesBtn.addEventListener('click', () => loadPages({ append: false }));
    }

    if (els.loadMoreBtn) {
      els.loadMoreBtn.addEventListener('click', () => {
        if (!state.hasMore || !state.nextCursor) return;
        loadPages({ append: true, cursor: state.nextCursor });
      });
    }

    if (els.linkForm) {
      els.linkForm.addEventListener('submit', saveLink);
    }

    if (els.deleteLinkBtn) {
      els.deleteLinkBtn.addEventListener('click', removeLink);
    }

    if (els.disconnectBtn) {
      els.disconnectBtn.addEventListener('click', disconnectWorkspace);
    }

    if (els.createPageSubmitBtn) {
      els.createPageSubmitBtn.addEventListener('click', createPage);
    }

    if (els.openCreateModalBtn) {
      els.openCreateModalBtn.addEventListener('click', syncCreateParentLabel);
    }
  }

  async function loadPages({ append = false, cursor = null } = {}) {
    if (!state.connected || state.loadingPages) return;

    state.loadingPages = true;
    setStatus(append ? 'Chargement de pages supplémentaires...' : 'Chargement des pages Notion...');
    toggleButtonLoading(els.refreshPagesBtn, true);
    if (append) toggleButtonLoading(els.loadMoreBtn, true);

    try {
      const params = new URLSearchParams();
      if (state.query) params.set('query', state.query);
      if (cursor) params.set('cursor', cursor);
      params.set('page_size', '20');

      const response = await apiFetch(`${routes.pagesSearch}?${params.toString()}`);
      const results = Array.isArray(response.data) ? response.data : [];

      state.pages = append ? state.pages.concat(results) : results;
      state.hasMore = Boolean(response.has_more);
      state.nextCursor = response.next_cursor || null;

      if (!append && !state.selectedPageId && state.pages.length > 0) {
        await selectPage(state.pages[0].id, false);
      }

      renderPages();
      updateLoadMore();
      setStatus(results.length === 0 && !append ? 'Aucune page retournée. Essayez un titre plus précis ou utilisez Rafraîchir.' : 'Pages Notion chargées.');
    } catch (error) {
      setStatus(error.message || 'Impossible de charger les pages Notion.');
      toastError('Notion', error.message || 'Impossible de charger les pages Notion.');
    } finally {
      state.loadingPages = false;
      toggleButtonLoading(els.refreshPagesBtn, false);
      toggleButtonLoading(els.loadMoreBtn, false);
    }
  }

  function renderPages() {
    if (!els.pageList) return;

    updatePagesCounters();

    if (!state.pages.length) {
      els.pageList.innerHTML = "<div class=\"nw-api-page-item\"><div class=\"nw-api-page-meta\">Aucune page partagée avec cette intégration pour l'instant.</div></div>";
      return;
    }

    els.pageList.innerHTML = state.pages.map((page) => {
      const icon = renderIcon(page.icon, 'nw-api-page-icon');
      const tags = [];
      if (page.link) tags.push('<span class="nw-api-tag success"><i class="fas fa-link"></i> Liée au CRM</span>');
      if (page.parent?.type === 'workspace') tags.push('<span class="nw-api-tag slate">Racine de l espace</span>');
      return `
        <button type="button" class="nw-api-page-item ${state.selectedPageId === page.id ? 'active' : ''}" data-page-id="${escapeHtml(page.id)}">
          <div class="nw-api-page-title">${icon}<span>${escapeHtml(page.title || 'Sans titre')}</span></div>
          <div class="nw-api-page-meta">Màj : ${formatDate(page.last_edited_time)}</div>
          ${tags.length ? `<div class="nw-api-page-tags">${tags.join('')}</div>` : ''}
        </button>
      `;
    }).join('');

    els.pageList.querySelectorAll('[data-page-id]').forEach((btn) => {
      btn.addEventListener('click', () => selectPage(btn.dataset.pageId));
    });
  }

  async function selectPage(pageId, showToastOnError = true) {
    if (!pageId) return;
    state.selectedPageId = pageId;
    renderPages();
    setStatus('Chargement de la page Notion sélectionnée...');

    try {
      const response = await apiFetch(pageShowUrl(pageId));
      state.selectedPage = response.data || null;
      syncCreateParentLabel();
      renderSelectedPage();
      setStatus('Page Notion chargée.');
    } catch (error) {
      if (showToastOnError) {
        toastError('Notion', error.message || 'Impossible de charger cette page Notion.');
      }
      setStatus(error.message || 'Impossible de charger cette page Notion.');
    }
  }

  function renderSelectedPage() {
    const payload = state.selectedPage;
    if (!payload || !payload.page) {
      if (els.preview) els.preview.style.display = 'none';
      if (els.previewEmpty) els.previewEmpty.style.display = 'block';
      return;
    }

    const page = payload.page;
    if (els.previewEmpty) els.previewEmpty.style.display = 'none';
    if (els.preview) els.preview.style.display = 'block';

    if (els.previewCover) {
      if (page.cover) {
        els.previewCover.style.display = 'block';
        els.previewCover.style.backgroundImage = `linear-gradient(135deg, rgba(224, 231, 255, 0.3), rgba(248, 250, 252, 0.15)), url('${page.cover.replace(/'/g, '%27')}')`;
      } else {
        els.previewCover.style.display = 'none';
        els.previewCover.style.backgroundImage = '';
      }
    }

    if (els.previewIcon) {
      els.previewIcon.innerHTML = renderIcon(page.icon, 'nw-api-preview-icon', true);
    }
    if (els.previewTitle) {
      els.previewTitle.textContent = page.title || 'Sans titre';
    }
    if (els.previewMeta) {
      const meta = [];
      if (page.parent?.type) meta.push(`Parent : ${page.parent.type}`);
      if (page.last_edited_time) meta.push(`Maj ${formatDate(page.last_edited_time)}`);
      meta.push(page.in_trash ? 'Dans la corbeille' : 'Actif');
      els.previewMeta.textContent = meta.join(' · ');
    }
    if (els.openExternalBtn) {
      els.openExternalBtn.href = page.url || '#';
      els.openExternalBtn.style.display = page.url ? 'inline-flex' : 'none';
    }

    renderBlocks(payload.blocks || []);
    fillLinkForm(payload.link || null);
  }

  function renderBlocks(blocks) {
    if (!els.blocksPreview) return;

    if (!Array.isArray(blocks) || !blocks.length) {
      els.blocksPreview.innerHTML = '<div class="nw-api-block"><p>Aucun bloc lisible retourné par Notion pour cette page.</p></div>';
      return;
    }

    els.blocksPreview.innerHTML = blocks.map((block) => renderBlock(block, 0)).join('');
  }

  function renderBlock(block, depth) {
    const indent = depth > 0 ? ` style="margin-left:${Math.min(depth * 18, 72)}px"` : '';
    const children = Array.isArray(block.children) && block.children.length
      ? block.children.map((child) => renderBlock(child, depth + 1)).join('')
      : '';

    let content = '';
    switch (block.type) {
      case 'heading_1':
        content = `<h1>${escapeHtml(block.plain_text || 'Titre')}</h1>`;
        break;
      case 'heading_2':
        content = `<h2>${escapeHtml(block.plain_text || 'Titre')}</h2>`;
        break;
      case 'heading_3':
        content = `<h3>${escapeHtml(block.plain_text || 'Titre')}</h3>`;
        break;
      case 'paragraph':
      case 'bulleted_list_item':
      case 'numbered_list_item':
      case 'toggle':
        content = `<p>${escapeHtml(block.plain_text || '')}</p>`;
        break;
      case 'to_do':
        content = `<label class="nw-api-check"><input type="checkbox" ${block.checked ? 'checked' : ''} disabled><span>${escapeHtml(block.plain_text || '')}</span></label>`;
        break;
      case 'quote':
      case 'callout':
        content = `<blockquote class="nw-api-blockquote">${escapeHtml(block.plain_text || '')}</blockquote>`;
        break;
      case 'code':
        content = `<pre>${escapeHtml(block.plain_text || '')}</pre>`;
        break;
      case 'divider':
        content = '<div class="nw-api-divider"></div>';
        break;
      case 'image':
        content = block.url ? `<div class="nw-api-media"><img src="${escapeHtml(block.url)}" alt="Image Notion"></div>` : '<p>Image sans URL exploitable.</p>';
        break;
      case 'table_row':
        content = `<div class="nw-api-table-wrap"><table class="nw-api-table"><tbody><tr>${(block.cells || []).map((cell) => `<td>${escapeHtml(cell)}</td>`).join('')}</tr></tbody></table></div>`;
        break;
      case 'child_page':
        content = `<p><strong>Sous-page:</strong> ${escapeHtml(block.plain_text || 'Sans titre')}</p>`;
        break;
      default:
        if (block.url) {
          content = `<p><strong>${escapeHtml(block.type)}</strong> <a href="${escapeHtml(block.url)}" target="_blank" rel="noopener">ouvrir</a></p>`;
        } else {
          content = `<p><strong>${escapeHtml(block.type)}</strong>${block.plain_text ? ` - ${escapeHtml(block.plain_text)}` : ''}</p>`;
        }
        break;
    }

    return `<div class="nw-api-block"${indent}>${content}${children}</div>`;
  }

  function fillLinkForm(link) {
    if (!els.linkForm) return;

    els.linkId.value = link?.id || '';
    els.linkClientId.value = link?.client_id || '';
    els.linkProjectId.value = link?.project_id || '';
    els.linkContextLabel.value = link?.context_label || '';
    els.linkNotes.value = link?.notes || '';
    els.deleteLinkBtn.style.display = link?.id ? 'inline-flex' : 'none';

    if (link?.id) {
      els.linkStatus.textContent = "Cette page est déjà liée au CRM. Vous pouvez mettre à jour ou supprimer cette association.";
    } else {
      els.linkStatus.textContent = "Aucun lien CRM sur cette page pour l'instant.";
    }
  }

  async function saveLink(event) {
    event.preventDefault();
    if (!state.selectedPage?.page) {
      toastError('Notion', "Sélectionnez d'abord une page Notion.");
      return;
    }

    toggleButtonLoading(els.saveLinkBtn, true);

    try {
      const page = state.selectedPage.page;
      const payload = {
        notion_page_id: page.id,
        notion_page_title: page.title,
        notion_page_url: page.url,
        notion_parent_id: page.parent?.page_id || null,
        client_id: normalizeNullableInt(els.linkClientId.value),
        project_id: normalizeNullableInt(els.linkProjectId.value),
        context_label: normalizeNullableString(els.linkContextLabel.value),
        notes: normalizeNullableString(els.linkNotes.value),
      };

      const isUpdate = Boolean(els.linkId.value);
      const response = await apiFetch(isUpdate ? linkUrl(els.linkId.value) : routes.linksStore, {
        method: isUpdate ? 'PUT' : 'POST',
        body: JSON.stringify(payload),
      });

      upsertLink(response.data);
      state.selectedPage.link = response.data;
      fillLinkForm(response.data);
      renderLinks();
      renderPages();
      toastSuccess('Notion', response.message || 'Lien CRM enregistré.');
    } catch (error) {
      toastError('Notion', error.message || "Impossible d'enregistrer le lien CRM.");
    } finally {
      toggleButtonLoading(els.saveLinkBtn, false);
    }
  }

  async function removeLink() {
    if (!els.linkId.value) return;

    const runDelete = async () => {
      try {
        await apiFetch(linkUrl(els.linkId.value), { method: 'DELETE' });
        const deletedId = String(els.linkId.value);
        state.links = state.links.filter((link) => String(link.id) !== deletedId);
        if (state.selectedPage) state.selectedPage.link = null;
        fillLinkForm(null);
        renderLinks();
        renderPages();
        toastSuccess('Notion', 'Lien CRM supprimé.');
      } catch (error) {
        toastError('Notion', error.message || 'Impossible de supprimer le lien CRM.');
      }
    };

    if (window.Modal && typeof window.Modal.confirm === 'function') {
      window.Modal.confirm({
        title: 'Supprimer le lien CRM ?',
        message: 'La page restera bien dans Notion, seule l association locale au CRM sera retiree.',
        confirmText: 'Supprimer',
        type: 'danger',
        onConfirm: runDelete,
      });
      return;
    }

    if (window.confirm('Supprimer ce lien CRM ?')) {
      await runDelete();
    }
  }

  function renderLinks() {
    if (!els.linkedList) return;

    updateLinkCounters();

    if (!state.links.length) {
      els.linkedList.innerHTML = '<div class="nw-api-linked-item"><div class="nw-api-linked-meta">Aucune page Notion n est encore liee au CRM.</div></div>';
      return;
    }

    els.linkedList.innerHTML = state.links.map((link) => {
      const tags = [];
      if (link.client_name) tags.push(`<span class="nw-api-tag"><i class="fas fa-building"></i> ${escapeHtml(link.client_name)}</span>`);
      if (link.project_name) tags.push(`<span class="nw-api-tag success"><i class="fas fa-diagram-project"></i> ${escapeHtml(link.project_name)}</span>`);
      if (link.context_label) tags.push(`<span class="nw-api-tag slate">${escapeHtml(link.context_label)}</span>`);
      return `
        <button type="button" class="nw-api-linked-item" data-linked-page-id="${escapeHtml(link.notion_page_id)}">
          <div class="nw-api-linked-title"><i class="fas fa-link"></i><span>${escapeHtml(link.notion_page_title || 'Sans titre')}</span></div>
          <div class="nw-api-linked-meta">Màj locale : ${formatDate(link.updated_at)}</div>
          ${tags.length ? `<div class="nw-api-linked-tags">${tags.join('')}</div>` : ''}
        </button>
      `;
    }).join('');

    els.linkedList.querySelectorAll('[data-linked-page-id]').forEach((btn) => {
      btn.addEventListener('click', () => selectPage(btn.dataset.linkedPageId));
    });
  }

  async function disconnectWorkspace() {
    const runDisconnect = async () => {
      toggleButtonLoading(els.disconnectBtn, true);
      try {
        const response = await apiFetch(routes.disconnect, { method: 'POST' });
        toastSuccess('Notion', response.message || 'Espace Notion deconnecte.');
        window.location.reload();
      } catch (error) {
        toggleButtonLoading(els.disconnectBtn, false);
        toastError('Notion', error.message || 'Impossible de déconnecter Notion.');
      }
    };

    if (window.Modal && typeof window.Modal.confirm === 'function') {
      window.Modal.confirm({
        title: 'Déconnecter Notion ?',
        message: 'Le token local sera révoqué. Les liens CRM existants seront conservés mais la lecture live des pages sera arrêtée jusqu à la prochaine connexion.',
        confirmText: 'Deconnecter',
        type: 'warning',
        onConfirm: runDisconnect,
      });
      return;
    }

    if (window.confirm('Déconnecter Notion ?')) {
      await runDisconnect();
    }
  }

  function syncCreateParentLabel() {
    if (!els.createParentLabel) return;
    state.createParentId = state.selectedPage?.page?.id || null;
    els.createParentLabel.value = state.selectedPage?.page
      ? `${state.selectedPage.page.title || 'Sans titre'} (${state.selectedPage.page.id})`
      : 'Aucun parent selectionne, la page sera creee a la racine de l espace';
  }

  async function createPage() {
    toggleButtonLoading(els.createPageSubmitBtn, true);

    try {
      const payload = {
        title: (els.createTitle?.value || '').trim(),
        content: (els.createContent?.value || '').trim(),
        parent_page_id: state.createParentId,
        icon: (els.createIcon?.value || '').trim(),
      };

      if (!payload.title) {
        throw new Error('Le titre de la page Notion est requis.');
      }

      const response = await apiFetch(routes.pagesStore, {
        method: 'POST',
        body: JSON.stringify(payload),
      });

      closeModal(els.createModal);
      clearCreateForm();
      toastSuccess('Notion', response.message || 'Page Notion créée avec succès.');
      await loadPages({ append: false });
      if (response.data?.id) {
        await selectPage(response.data.id, false);
      }
    } catch (error) {
      toastError('Notion', error.message || 'Impossible de créer la page Notion.');
    } finally {
      toggleButtonLoading(els.createPageSubmitBtn, false);
    }
  }

  function clearCreateForm() {
    if (els.createTitle) els.createTitle.value = '';
    if (els.createIcon) els.createIcon.value = '';
    if (els.createContent) els.createContent.value = '';
  }

  function updateLoadMore() {
    if (!els.loadMoreBtn) return;
    els.loadMoreBtn.style.display = state.hasMore ? 'inline-flex' : 'none';
  }

  function upsertLink(link) {
    const idx = state.links.findIndex((item) => String(item.notion_page_id) === String(link.notion_page_id));
    if (idx >= 0) state.links.splice(idx, 1, link);
    else state.links.unshift(link);
  }

  function updatePagesCounters() {
    if (els.pagesCountLabel) {
      const total = state.pages.length;
      els.pagesCountLabel.textContent = `${total} ${total > 1 ? 'résultats' : 'resultat'}`;
    }

    if (els.statLoadedPages) {
      els.statLoadedPages.textContent = String(state.pages.length);
    }
  }

  function updateLinkCounters() {
    if (els.statLinkedPages) {
      els.statLinkedPages.textContent = String(state.links.length);
    }

    if (els.statLinkedClients) {
      const total = state.links.filter((link) => Boolean(link.client_id)).length;
      els.statLinkedClients.textContent = String(total);
    }

    if (els.statLinkedProjects) {
      const total = state.links.filter((link) => Boolean(link.project_id)).length;
      els.statLinkedProjects.textContent = String(total);
    }
  }

  function pageShowUrl(pageId) {
    return String(routes.pageShowBase || '').replace('__PAGE_ID__', encodeURIComponent(pageId));
  }

  function linkUrl(linkId) {
    return String(routes.linksBase || '').replace('__LINK_ID__', encodeURIComponent(linkId));
  }

  async function apiFetch(url, options = {}) {
    const response = await fetch(url, {
      method: options.method || 'GET',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        ...(options.headers || {}),
      },
      credentials: 'same-origin',
      body: options.body,
    });

    const payload = await response.json().catch(() => ({}));
    const reconnectTarget = window.CrmAuth?.resolveReconnectRedirect?.(payload?.message, payload);
    if (reconnectTarget) {
      window.CrmAuth.redirectToReconnect(
        payload?.message || 'La session Notion a expire. Redirection vers la reconnexion.',
        reconnectTarget
      );
    }
    if (!response.ok || payload.success === false) {
      throw new Error(payload.message || `Erreur HTTP ${response.status}`);
    }

    return payload;
  }

  function setStatus(message) {
    if (els.searchStatus) {
      els.searchStatus.textContent = message || '';
    }
  }

  function renderIcon(icon, className, innerOnly = false) {
    if (!icon) {
      return innerOnly ? '<i class="fas fa-file-lines"></i>' : `<span class="${className}"><i class="fas fa-file-lines"></i></span>`;
    }

    const inner = icon.type === 'image'
      ? `<img src="${escapeHtml(icon.value || '')}" alt="Notion icon">`
      : escapeHtml(icon.value || '??');

    return innerOnly ? inner : `<span class="${className}">${inner}</span>`;
  }

  function closeModal(overlay) {
    if (window.Modal && typeof window.Modal.close === 'function') {
      window.Modal.close(overlay, true);
      return;
    }

    if (overlay) {
      overlay.classList.remove('open');
    }
  }

  function toggleButtonLoading(button, loading) {
    if (!button) return;
    if (loading) {
      button.dataset.originalHtml = button.innerHTML;
      button.disabled = true;
      button.classList.add('loading');
      return;
    }

    button.disabled = false;
    button.classList.remove('loading');
    if (button.dataset.originalHtml) {
      button.innerHTML = button.dataset.originalHtml;
    }
  }

  function normalizeNullableString(value) {
    const normalized = String(value || '').trim();
    return normalized === '' ? null : normalized;
  }

  function normalizeNullableInt(value) {
    const normalized = String(value || '').trim();
    if (normalized === '') return null;
    const parsed = Number.parseInt(normalized, 10);
    return Number.isNaN(parsed) ? null : parsed;
  }

  function formatDate(value) {
    if (!value) return 'date inconnue';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function toastSuccess(title, message) {
    if (window.Toast?.success) window.Toast.success(title, message);
  }

  function toastError(title, message) {
    if (window.Toast?.error) window.Toast.error(title, message);
    else window.alert(message || title);
  }

  return { boot };
})();

document.addEventListener('DOMContentLoaded', () => {
  NotionWorkspaceModule.boot();
});

