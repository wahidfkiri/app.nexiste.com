'use strict';

const GoogleSheetsModule = (() => {
  const state = {
    connected: false,
    spreadsheets: [],
    currentSpreadsheet: null,
    currentSheets: [],
    currentSheetTitle: '',
    currentRange: 'A1:Z100',
    search: '',
    debounceTimer: null,
    dataLoaded: false,
  };

  function t(path, fallback = '', replacements = {}) {
    const source = window.GS_I18N || {};
    const value = String(path || '').split('.').reduce((carry, segment) => (
      carry && Object.prototype.hasOwnProperty.call(carry, segment) ? carry[segment] : undefined
    ), source);

    let text = typeof value === 'string' ? value : fallback;
    Object.entries(replacements).forEach(([key, replacement]) => {
      text = text.split(`:${key}`).join(String(replacement));
    });

    return text;
  }

  // ── Boot ───────────────────────────────────────────────────────────────

  function boot(bootstrap = {}) {
    state.connected = !!bootstrap.connected;
    bindActions();
    if (!state.connected) return;
    loadStats();
    loadSpreadsheets();
  }

  // ── Bind ───────────────────────────────────────────────────────────────

  function bindActions() {
    document.getElementById('gsRefreshBtn')?.addEventListener('click', () => {
      loadStats();
      loadSpreadsheets();
    });

    document.getElementById('gsDisconnectBtn')?.addEventListener('click', disconnect);
    document.getElementById('gsSaveSpreadsheetBtn')?.addEventListener('click', createSpreadsheet);

    document.getElementById('gsSearchInput')?.addEventListener('input', (e) => {
      clearTimeout(state.debounceTimer);
      state.debounceTimer = setTimeout(() => {
        state.search = (e.target.value || '').trim();
        if (state.search.length >= 2 || state.search.length === 0) {
          loadSpreadsheets();
        }
      }, 300);
    });

    document.getElementById('gsSpreadsheetsTableBody')?.addEventListener('click', handleSpreadsheetActions);
    document.getElementById('gsReadRangeBtn')?.addEventListener('click', readRange);
    document.getElementById('gsWriteRangeBtn')?.addEventListener('click', openWriteModal);
    document.getElementById('gsAppendRowsBtn')?.addEventListener('click', openAppendModal);
    document.getElementById('gsClearRangeBtn')?.addEventListener('click', clearRange);
    document.getElementById('gsSaveWriteBtn')?.addEventListener('click', writeRange);
    document.getElementById('gsSaveAppendBtn')?.addEventListener('click', appendRows);

    document.getElementById('gsAddSheetBtn')?.addEventListener('click', addSheet);
  }

  function normalizeReconnectText(message) {
    return String(message || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase();
  }

  function isReconnectRequiredMessage(message) {
    const text = normalizeReconnectText(message);
    return text.includes('session google sheets expiree')
      || text.includes('session google sheets expi?ee')
      || text.includes('reconnectez votre compte google')
      || text.includes('reconnectez google sheets')
      || text.includes('invalid_grant');
  }

  function promptReconnect(message) {
    const text = String(message || t('errors.session_expired', 'Session Google Sheets expirée ou révoquée. Reconnectez votre compte Google.')).trim();
    const reconnectUrl = window.GS_ROUTES?.connect || '';

    if (!window.Modal || typeof window.Modal.confirm !== 'function' || !reconnectUrl) {
      Toast.error(t('common.error', 'Erreur'), text);
      return;
    }

    Modal.confirm({
      title: t('confirm.reconnect_title', 'Reconnecter Google Sheets ?'),
      message: text,
      confirmText: t('confirm.reconnect_button', 'Reconnecter'),
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
    const resolved = String(message || fallback || t('errors.generic', 'Une erreur est survenue.')).trim();

    if (isReconnectRequiredMessage(resolved)) {
      promptReconnect(resolved);
      return true;
    }

    Toast.error(title || t('common.error', 'Erreur'), resolved);
    return false;
  }

  // ── Stats ──────────────────────────────────────────────────────────────

  async function loadStats() {
    const { ok, data } = await Http.get(window.GS_ROUTES.stats);
    if (!ok || !data.success) {
      handleFailure(t('common.error', 'Erreur'), data?.message, t('errors.load_stats', 'Impossible de charger les statistiques Google Sheets.'));
      return;
    }
    const s = data.data || {};
    setText('gsStatSpreadsheets', s.total_spreadsheets || 0);
    setText('gsStatSheets', s.total_sheets || 0);
    if (s.last_sync_at) setText('gsLastSyncLabel', new Date(s.last_sync_at).toLocaleString());
  }

  // ── Spreadsheets ───────────────────────────────────────────────────────

  async function loadSpreadsheets() {
    const tbody = document.getElementById('gsSpreadsheetsTableBody');
    if (tbody) tbody.innerHTML = skeletonRows(6, 5);

    const params = { search: state.search };
    const { ok, data } = await Http.get(window.GS_ROUTES.spreadsheetsData, params);

    if (!ok || !data.success) {
      if (tbody) tbody.innerHTML = emptyRow(t('errors.load_spreadsheets', 'Impossible de charger les feuilles de calcul.'));
      handleFailure(t('common.error', 'Erreur'), data?.message, t('errors.load_spreadsheets', 'Impossible de charger les feuilles de calcul.'));
      return;
    }

    state.spreadsheets = data.data?.spreadsheets || [];
    renderSpreadsheets();
    setText('gsCount', t('table.count_results', ':count résultat(s)', { count: state.spreadsheets.length }));
  }

  function renderSpreadsheets() {
    const tbody = document.getElementById('gsSpreadsheetsTableBody');
    if (!tbody) return;

    if (!state.spreadsheets.length) {
      tbody.innerHTML = emptyRow(t('table.empty_spreadsheets', 'Aucune feuille de calcul trouvée.'));
      return;
    }

    tbody.innerHTML = state.spreadsheets.map((ss, idx) => {
      const modified = ss.modified_at ? new Date(ss.modified_at).toLocaleString() : '-';
      const created  = ss.created_at  ? new Date(ss.created_at).toLocaleString()  : '-';
      const shared   = ss.is_shared
        ? `<span style="background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:99px;font-size:10.5px;font-weight:600;">${esc(t('badges.shared', 'Partagé'))}</span>`
        : '';

      return `
        <tr data-index="${idx}" class="gs-spreadsheet-row">
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:34px;height:34px;background:#0f9d5818;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-file-excel" style="color:#0f9d58;font-size:16px;"></i>
              </div>
              <div>
                <div style="font-weight:var(--fw-medium);color:var(--c-ink);">${esc(ss.title || t('common.no_title', 'Sans titre'))}</div>
                <div style="font-size:11.5px;color:var(--c-ink-40);font-family:monospace;">${esc(ss.spreadsheet_id)}</div>
              </div>
            </div>
          </td>
          <td>${esc(created)}</td>
          <td>${esc(modified)}</td>
          <td>${shared}</td>
          <td>
            <div class="row-actions" style="justify-content:flex-end;padding-right:4px;opacity:1;">
              ${ss.spreadsheet_url ? `<a href="${esc(ss.spreadsheet_url)}" target="_blank" rel="noopener" class="btn-icon" title="${esc(t('actions.open_in_google', 'Ouvrir dans Google Sheets'))}"><i class="fas fa-arrow-up-right-from-square"></i></a>` : ''}
              <button class="btn-icon" data-action="open" data-index="${idx}" title="${esc(t('actions.read_edit_data', 'Lire/éditer les données'))}"><i class="fas fa-table-cells"></i></button>
              <button class="btn-icon" data-action="rename" data-index="${idx}" title="${esc(t('actions.rename', 'Renommer'))}"><i class="fas fa-pen"></i></button>
              <button class="btn-icon" data-action="duplicate" data-index="${idx}" title="${esc(t('actions.duplicate', 'Dupliquer'))}"><i class="fas fa-copy"></i></button>
              <button class="btn-icon danger" data-action="delete" data-index="${idx}" title="${esc(t('actions.delete', 'Supprimer'))}"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  function handleSpreadsheetActions(e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    const idx    = parseInt(btn.dataset.index, 10);
    const ss     = state.spreadsheets[idx];
    if (!ss) return;

    if (action === 'open')      openDataModal(ss);
    if (action === 'rename')    renameSpreadsheet(ss);
    if (action === 'duplicate') duplicateSpreadsheet(ss);
    if (action === 'delete')    deleteSpreadsheet(ss);
  }

  // ── Create spreadsheet ─────────────────────────────────────────────────

  async function createSpreadsheet() {
    const titleInput   = document.getElementById('gsSpreadsheetTitle');
    const sheetsInput  = document.getElementById('gsSheetTitles');
    const title        = (titleInput?.value || '').trim();

    if (!title) { Toast.error(t('common.validation', 'Validation'), t('validation.title_required', 'Le titre est obligatoire.')); return; }

    const sheetTitles = (sheetsInput?.value || '')
      .split(',')
      .map(s => s.trim())
      .filter(Boolean);

    const { ok, data } = await Http.post(window.GS_ROUTES.createSpreadsheet, {
      title,
      sheet_titles: sheetTitles.length ? sheetTitles : [t('common.default_sheet', 'Feuil1')],
    });

    if (!ok || !data.success) {
      handleFailure(t('common.error', 'Erreur'), data?.message, t('errors.create_spreadsheet', 'Impossible de créer la feuille.'));
      return;
    }

    if (titleInput)  titleInput.value  = '';
    if (sheetsInput) sheetsInput.value = '';
    Modal.close(document.getElementById('gsCreateModal'));
    Toast.success(t('common.success', 'Succès'), data.message || t('success.created_short', 'Feuille créée.'));
    loadSpreadsheets();
    loadStats();
  }

  // ── Rename ─────────────────────────────────────────────────────────────

  async function renameSpreadsheet(ss) {
    const name = window.prompt(t('prompts.new_title', 'Nouveau titre'), ss.title || '');
    if (!name || !name.trim()) return;

    const resp = await fetchWithMethod(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(ss.spreadsheet_id)}/rename`,
      'PATCH',
      { title: name.trim() }
    );

    if (!resp.ok || !resp.data.success) {
      handleFailure(t('common.error', 'Erreur'), resp.data?.message, t('errors.rename_spreadsheet', 'Impossible de renommer.'));
      return;
    }
    Toast.success(t('common.success', 'Succès'), resp.data.message || t('success.renamed_short', 'Feuille renommée.'));
    loadSpreadsheets();
  }

  // ── Duplicate ──────────────────────────────────────────────────────────

  async function duplicateSpreadsheet(ss) {
    const name = window.prompt(
      t('prompts.copy_title', 'Titre de la copie'),
      t('prompts.copy_of', 'Copie de :title', { title: ss.title || '' })
    ) || '';
    const resp = await Http.post(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(ss.spreadsheet_id)}/duplicate`,
      { title: name.trim() }
    );

    if (!resp.ok || !resp.data.success) {
      handleFailure(t('common.error', 'Erreur'), resp.data?.message, t('errors.duplicate_spreadsheet', 'Impossible de dupliquer.'));
      return;
    }
    Toast.success(t('common.success', 'Succès'), resp.data.message || t('success.duplicated_short', 'Feuille dupliquée.'));
    loadSpreadsheets();
    loadStats();
  }

  // ── Delete ─────────────────────────────────────────────────────────────

  async function deleteSpreadsheet(ss) {
    Modal.confirm({
      title:       t('confirm.delete_spreadsheet_title', 'Supprimer ":title" ?', { title: ss.title || t('common.no_title', 'Sans titre') }),
      message:     t('confirm.delete_spreadsheet_message', 'Cette feuille sera supprimée définitivement de Google Drive.'),
      confirmText: t('confirm.delete_button', 'Supprimer'),
      type:        'danger',
      onConfirm:   async () => {
        const resp = await fetchWithMethod(
          `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(ss.spreadsheet_id)}`,
          'DELETE',
          {}
        );
        if (!resp.ok || !resp.data.success) {
          handleFailure(t('common.error', 'Erreur'), resp.data?.message, t('errors.delete_spreadsheet', 'Impossible de supprimer.'));
          return;
        }
        Toast.success(t('success.deleted_title', 'Supprimée'), resp.data.message || t('success.deleted_short', 'Feuille supprimée.'));
        loadSpreadsheets();
        loadStats();
      },
    });
  }

  // ── Data modal (read / write) ──────────────────────────────────────────

  async function openDataModal(ss) {
    state.currentSpreadsheet = ss;
    state.dataLoaded         = false;

    setText('gsDataModalTitle', ss.title || t('modal.data_title', 'Feuille de calcul'));

    // Charger les onglets
    const loaderWrap = document.getElementById('gsSheetTabsLoader');
    if (loaderWrap) loaderWrap.innerHTML = `<span style="font-size:12px;color:var(--c-ink-40);">${esc(t('data.loading_sheets', 'Chargement des onglets…'))}</span>`;

    const { ok, data } = await Http.get(`${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(ss.spreadsheet_id)}`);

    if (!ok || !data.success) {
      handleFailure(t('common.error', 'Erreur'), data?.message, t('errors.load_spreadsheet', 'Impossible de charger la feuille.'));
      return;
    }

    state.currentSheets = data.data?.sheets || [];

    // Render tabs
    if (loaderWrap) {
      loaderWrap.innerHTML = state.currentSheets.map((sh, idx) => `
        <button class="gs-sheet-tab ${idx === 0 ? 'active' : ''}"
                data-sheet-title="${esc(sh.title)}"
                onclick="GoogleSheetsModule.selectSheet(this, '${esc(sh.title)}')"
                title="${esc(sh.title)}">
          <i class="fas fa-table" style="font-size:10px;"></i>
          ${esc(sh.title)}
          <span class="gs-sheet-tab-actions" style="display:flex;gap:3px;margin-left:4px;">
            <span style="font-size:9px;color:rgba(255,255,255,.7);cursor:pointer;" data-sheet-rename="${sh.sheet_id}" onclick="event.stopPropagation();GoogleSheetsModule.renameSheetPrompt(${sh.sheet_id},'${esc(sh.title)}')" title="${esc(t('actions.rename', 'Renommer'))}"><i class="fas fa-pen"></i></span>
            <span style="font-size:9px;color:rgba(255,255,255,.7);cursor:pointer;" data-sheet-delete="${sh.sheet_id}" onclick="event.stopPropagation();GoogleSheetsModule.deleteSheetConfirm(${sh.sheet_id},'${esc(sh.title)}')" title="${esc(t('actions.delete', 'Supprimer'))}"><i class="fas fa-times"></i></span>
          </span>
        </button>`).join('');
    }

    if (state.currentSheets.length > 0) {
      state.currentSheetTitle = state.currentSheets[0].title;
      const rangeInput = document.getElementById('gsRangeInput');
      if (rangeInput) rangeInput.value = `${state.currentSheetTitle}!A1:Z50`;
    }

    Modal.open(document.getElementById('gsDataModal'));
  }

  function selectSheet(btn, sheetTitle) {
    document.querySelectorAll('.gs-sheet-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    state.currentSheetTitle = sheetTitle;

    const rangeInput = document.getElementById('gsRangeInput');
    if (rangeInput) rangeInput.value = `${sheetTitle}!A1:Z50`;

    // Clear table
    const wrap = document.getElementById('gsDataTableWrap');
    if (wrap) wrap.innerHTML = `<div style="text-align:center;padding:40px;color:var(--c-ink-40);"><i class="fas fa-table-cells" style="font-size:28px;margin-bottom:8px;display:block;opacity:.3;"></i><p>${esc(t('data.click_read_to_load', 'Cliquez sur "Lire" pour charger les données de cet onglet.'))}</p></div>`;
  }

  // ── Read range ─────────────────────────────────────────────────────────

  async function readRange() {
    const rangeInput = document.getElementById('gsRangeInput');
    const range      = (rangeInput?.value || '').trim();
    if (!range || !state.currentSpreadsheet) return;

    const wrap = document.getElementById('gsDataTableWrap');
    if (wrap) wrap.innerHTML = skeletonTable();

    const { ok, data } = await Http.get(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/values`,
      { range }
    );

    if (!ok || !data.success) {
      if (wrap) wrap.innerHTML = `<div style="text-align:center;padding:40px;color:var(--c-danger);">${esc(data.message || t('common.error', 'Erreur'))}</div>`;
      handleFailure(t('common.error', 'Erreur'), data?.message, t('errors.read_range', 'Impossible de lire la plage.'));
      return;
    }

    const payload = data.data || {};
    renderDataTable(payload.values || [], payload.range || range);
  }

  function renderDataTable(values, range) {
    const wrap = document.getElementById('gsDataTableWrap');
    if (!wrap) return;

    if (!values.length) {
      wrap.innerHTML = `<div style="text-align:center;padding:40px;color:var(--c-ink-40);"><i class="fas fa-inbox" style="font-size:28px;opacity:.3;display:block;margin-bottom:8px;"></i><p>${esc(t('data.range_empty', 'La plage :range est vide.', { range }))}</p></div>`;
      return;
    }

    const maxCols = Math.max(...values.map(r => r.length));
    const colLetters = Array.from({ length: maxCols }, (_, i) => colLetter(i));

    const thead = `<tr>
      <th class="gs-col-header" style="width:36px;">#</th>
      ${colLetters.map(l => `<th class="gs-col-header">${esc(l)}</th>`).join('')}
    </tr>`;

    const tbody = values.map((row, rowIdx) => {
      const cells = Array.from({ length: maxCols }, (_, colIdx) => {
        const val = row[colIdx] ?? '';
        return `<td title="${esc(val)}">${val !== '' ? esc(val) : '<span class="gs-empty-cell">·</span>'}</td>`;
      }).join('');
      return `<tr><td class="gs-row-num">${rowIdx + 1}</td>${cells}</tr>`;
    }).join('');

    wrap.innerHTML = `
      <div class="gs-data-wrap">
        <table class="gs-data-table">
          <thead>${thead}</thead>
          <tbody>${tbody}</tbody>
        </table>
      </div>
      <div style="margin-top:10px;font-size:12px;color:var(--c-ink-40);">
        <i class="fas fa-info-circle"></i>
        ${esc(t('data.range_summary', 'Plage : :range · :rows ligne(s) · :cols colonne(s)', { range, rows: values.length, cols: maxCols }))}
      </div>`;
  }

  // ── Write ──────────────────────────────────────────────────────────────

  function openWriteModal() {
    const rangeInput = document.getElementById('gsRangeInput');
    const wr = document.getElementById('gsWriteRange');
    if (wr && rangeInput) wr.value = rangeInput.value;
    Modal.open(document.getElementById('gsWriteModal'));
  }

  async function writeRange() {
    const rangeEl  = document.getElementById('gsWriteRange');
    const dataEl   = document.getElementById('gsWriteData');
    const range    = (rangeEl?.value || '').trim();
    const rawData  = (dataEl?.value || '').trim();

    if (!range || !rawData || !state.currentSpreadsheet) {
      Toast.error(t('common.validation', 'Validation'), t('validation.range_and_data_required', 'La plage et les données sont obligatoires.'));
      return;
    }

    const values = parseCsvData(rawData);
    if (!values.length) { Toast.error(t('common.validation', 'Validation'), t('validation.invalid_data_format', 'Format de données invalide.')); return; }

    const resp = await fetchWithMethod(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/values`,
      'PUT',
      { range, values }
    );

    if (!resp.ok || !resp.data.success) {
      handleFailure(t('common.error', 'Erreur'), resp.data?.message, t('errors.write_range', 'Impossible d’écrire les données.'));
      return;
    }

    Modal.close(document.getElementById('gsWriteModal'));
    Toast.success(t('success.write_title', 'Écriture'), t('data.updated_cells', ':count cellule(s) mise(s) à jour.', { count: resp.data.data?.updated_cells || 0 }));
    readRange();
  }

  // ── Append ─────────────────────────────────────────────────────────────

  function openAppendModal() {
    const rangeInput = document.getElementById('gsRangeInput');
    const ar = document.getElementById('gsAppendRange');
    if (ar && rangeInput) ar.value = rangeInput.value;
    Modal.open(document.getElementById('gsAppendModal'));
  }

  async function appendRows() {
    const rangeEl = document.getElementById('gsAppendRange');
    const dataEl  = document.getElementById('gsAppendData');
    const range   = (rangeEl?.value || '').trim();
    const rawData = (dataEl?.value || '').trim();

    if (!range || !rawData || !state.currentSpreadsheet) {
      Toast.error(t('common.validation', 'Validation'), t('validation.range_and_data_required', 'La plage et les données sont obligatoires.'));
      return;
    }

    const values = parseCsvData(rawData);
    if (!values.length) { Toast.error(t('common.validation', 'Validation'), t('validation.invalid_data_format', 'Format de données invalide.')); return; }

    const { ok, data } = await Http.post(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/values/append`,
      { range, values }
    );

    if (!ok || !data.success) {
      handleFailure(t('common.error', 'Erreur'), data?.message, t('errors.append_rows', 'Impossible d’ajouter les lignes.'));
      return;
    }

    Modal.close(document.getElementById('gsAppendModal'));
    Toast.success(t('success.append_title', 'Ajout'), t('data.appended_rows', ':count ligne(s) ajoutée(s).', { count: data.data?.updated_rows || 0 }));
    readRange();
  }

  // ── Clear range ────────────────────────────────────────────────────────

  async function clearRange() {
    const rangeInput = document.getElementById('gsRangeInput');
    const range      = (rangeInput?.value || '').trim();
    if (!range || !state.currentSpreadsheet) return;

    Modal.confirm({
      title:       t('confirm.clear_range_title', 'Vider la plage ":range" ?', { range }),
      message:     t('confirm.clear_range_message', 'Toutes les valeurs de cette plage seront supprimées.'),
      confirmText: t('confirm.clear_button', 'Vider'),
      type:        'danger',
      onConfirm:   async () => {
        const resp = await fetchWithMethod(
          `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/values`,
          'DELETE',
          { range }
        );
        if (!resp.ok || !resp.data.success) {
          handleFailure(t('common.error', 'Erreur'), resp.data?.message, t('errors.clear_range', 'Impossible de vider la plage.'));
          return;
        }
        Toast.success(t('success.clear_title', 'Plage vidée'), resp.data.message || t('success.range_cleared', 'Plage vidée.'));
        readRange();
      },
    });
  }

  // ── Sheet tab actions ──────────────────────────────────────────────────

  async function addSheet() {
    const nameInput = document.getElementById('gsNewSheetTitle');
    const name      = (nameInput?.value || '').trim();
    if (!name || !state.currentSpreadsheet) { Toast.error(t('common.validation', 'Validation'), t('validation.sheet_title_required', 'Le titre de l’onglet est obligatoire.')); return; }

    const { ok, data } = await Http.post(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/sheets`,
      { title: name }
    );

    if (!ok || !data.success) {
      handleFailure(t('common.error', 'Erreur'), data?.message, t('errors.add_sheet', 'Impossible d’ajouter un onglet.'));
      return;
    }

    if (nameInput) nameInput.value = '';
    Toast.success(t('success.append_title', 'Ajout'), data.message || t('success.sheet_added_short', 'Onglet ajouté.'));
    openDataModal(state.currentSpreadsheet);
  }

  function renameSheetPrompt(sheetId, currentTitle) {
    const name = window.prompt(t('prompts.new_sheet_name', 'Nouveau nom de l’onglet'), currentTitle || '');
    if (!name || !name.trim() || !state.currentSpreadsheet) return;

    fetchWithMethod(
      `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/sheets/${sheetId}/rename`,
      'PATCH',
      { title: name.trim() }
    ).then(resp => {
      if (!resp.ok || !resp.data.success) { handleFailure(t('common.error', 'Erreur'), resp.data?.message, t('errors.rename_sheet', 'Impossible de renommer.')); return; }
      Toast.success(t('success.sheet_renamed_title', 'Renommé'), resp.data.message || t('success.sheet_renamed_short', 'Onglet renommé.'));
      openDataModal(state.currentSpreadsheet);
    });
  }

  function deleteSheetConfirm(sheetId, title) {
    Modal.confirm({
      title:       t('confirm.delete_sheet_title', 'Supprimer l’onglet ":title" ?', { title }),
      message:     t('confirm.delete_sheet_message', 'Toutes les données de cet onglet seront perdues.'),
      confirmText: t('confirm.delete_button', 'Supprimer'),
      type:        'danger',
      onConfirm:   async () => {
        const resp = await fetchWithMethod(
          `${window.GS_ROUTES.spreadsheetBase}/${encodeURIComponent(state.currentSpreadsheet.spreadsheet_id)}/sheets/${sheetId}`,
          'DELETE',
          {}
        );
        if (!resp.ok || !resp.data.success) { handleFailure(t('common.error', 'Erreur'), resp.data?.message, t('errors.delete_sheet', 'Impossible de supprimer.')); return; }
        Toast.success(t('success.sheet_deleted_title', 'Supprimé'), resp.data.message || t('success.sheet_deleted_short', 'Onglet supprimé.'));
        openDataModal(state.currentSpreadsheet);
      },
    });
  }

  // ── Disconnect ─────────────────────────────────────────────────────────

  async function disconnect() {
    Modal.confirm({
      title:       t('confirm.disconnect_title', 'Déconnecter Google Sheets ?'),
      message:     t('confirm.disconnect_message', 'Les jetons OAuth seront supprimés pour ce tenant.'),
      confirmText: t('confirm.disconnect_button', 'Déconnecter'),
      type:        'danger',
      onConfirm:   async () => {
        const { ok, data } = await Http.post(window.GS_ROUTES.disconnect, {});
        if (!ok || !data.success) { handleFailure(t('common.error', 'Erreur'), data?.message, t('errors.disconnect', 'Impossible de déconnecter Google Sheets.')); return; }
        Toast.success(t('success.disconnected_title', 'Déconnecté'), data.message || t('success.disconnected_short', 'Google Sheets déconnecté.'));
        setTimeout(() => window.location.reload(), 700);
      },
    });
  }

  // ── Helpers ────────────────────────────────────────────────────────────

  async function fetchWithMethod(url, method, payload) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type':   'application/json',
        'Accept':         'application/json',
        'X-CSRF-TOKEN':   token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: ['GET', 'HEAD'].includes(method) ? undefined : JSON.stringify(payload || {}),
    });
    const data = await response.json().catch(() => ({}));
    const reconnectTarget = window.CrmAuth?.resolveReconnectRedirect?.(data?.message, data);
    if (reconnectTarget) {
      window.CrmAuth.redirectToReconnect(
        data?.message || t('errors.session_redirect', 'La session Google Sheets a expiré. Redirection vers la reconnexion.'),
        reconnectTarget
      );
    }
    return { ok: response.ok, status: response.status, data };
  }

  function parseCsvData(raw) {
    return raw.split('\n').map(line =>
      line.split('\t').map(cell => cell.replace(/\\n/g, '\n'))
    ).filter(row => row.some(c => c.trim() !== ''));
  }

  function colLetter(idx) {
    let result = '';
    let n = idx;
    while (n >= 0) {
      result = String.fromCharCode(65 + (n % 26)) + result;
      n = Math.floor(n / 26) - 1;
    }
    return result;
  }

  function skeletonRows(count, cols) {
    return Array.from({ length: count }, () =>
      `<tr>${Array.from({ length: cols }, () =>
        '<td><div class="skeleton" style="height:13px;"></div></td>'
      ).join('')}</tr>`
    ).join('');
  }

  function skeletonTable() {
    return `<div style="padding:20px;">${skeletonRows(8, 5)}</div>`;
  }

  function emptyRow(message) {
    return `<tr><td colspan="5"><div class="table-empty">
      <div class="table-empty-icon"><i class="fas fa-file-excel"></i></div>
      <h3>${esc(t('common.no_data_title', 'Aucune donnée'))}</h3>
      <p>${esc(message)}</p>
    </div></td></tr>`;
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
    selectSheet,
    renameSheetPrompt,
    deleteSheetConfirm,
  };
})();

window.GoogleSheetsModule = GoogleSheetsModule;
