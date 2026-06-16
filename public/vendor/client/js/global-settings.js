if (!window.__CRM_GLOBAL_SETTINGS_JS__) {
  window.__CRM_GLOBAL_SETTINGS_JS__ = true;

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('globalSettingsForm');
    const exportConfig = window.GlobalDataExportConfig || {};
    const startExportBtn = document.getElementById('startGlobalDataExportBtn');
    const statusCard = document.getElementById('dataExportStatusCard');
    const statusTitle = document.getElementById('dataExportStatusTitle');
    const activeStepTitle = document.getElementById('dataExportActiveStepTitle');
    const statusSubtitle = document.getElementById('dataExportStatusSubtitle');
    const statusBadge = document.getElementById('dataExportStatusBadge');
    const statusProvider = document.getElementById('dataExportStatusProvider');
    const progressBar = document.getElementById('dataExportProgressBar');
    const progressPercent = document.getElementById('dataExportProgressPercent');
    const currentStep = document.getElementById('dataExportCurrentStep');
    const timeline = document.getElementById('dataExportTimeline');
    const logList = document.getElementById('dataExportLogList');
    const warningList = document.getElementById('dataExportWarningList');
    const resultBox = document.getElementById('dataExportResultBox');
    const resultCopy = document.getElementById('dataExportResultCopy');
    const openRemoteBtn = document.getElementById('dataExportOpenRemoteBtn');
    const providerActionBtn = document.getElementById('dataExportProviderActionBtn');
    const restartBtn = document.getElementById('dataExportRestartBtn');
    const historyList = document.getElementById('dataExportHistoryList');

    const exportState = {
      current: exportConfig.currentExport || null,
      history: Array.isArray(exportConfig.history) ? exportConfig.history : [],
      timer: null,
      hideTimer: null,
      processing: false,
    };

    const AUTO_HIDE_DELAY_MS = 4200;

    function clearExportTimer() {
      if (exportState.timer) {
        window.clearTimeout(exportState.timer);
        exportState.timer = null;
      }
    }

    function clearHideTimer() {
      if (exportState.hideTimer) {
        window.clearTimeout(exportState.hideTimer);
        exportState.hideTimer = null;
      }
    }

    function hideStatusCard() {
      if (!statusCard) {
        return;
      }

      clearHideTimer();
      statusCard.classList.add('is-hiding');

      window.setTimeout(() => {
        statusCard.classList.remove('is-visible');
        statusCard.classList.remove('is-hiding');
      }, 280);
    }

    function scheduleStatusAutoHide(exportData) {
      clearHideTimer();

      if (exportData?.status !== 'completed') {
        return;
      }

      exportState.hideTimer = window.setTimeout(() => {
        hideStatusCard();
      }, AUTO_HIDE_DELAY_MS);
    }

    function requestJson(url, options = {}) {
      const method = options.method || 'GET';
      const payload = options.body || null;

      if (window.Http) {
        if (method === 'POST') {
          return window.Http.post(url, payload || {});
        }
        return window.Http.get(url, payload || {});
      }

      const headers = {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      };

      let body = null;
      if (payload && method !== 'GET') {
        headers['Content-Type'] = 'application/json';
        body = JSON.stringify(payload);
      }

      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (csrf) {
        headers['X-CSRF-TOKEN'] = csrf;
      }

      return fetch(url, {
        method,
        headers,
        body,
        credentials: 'same-origin',
      }).then(async (response) => ({
        ok: response.ok,
        status: response.status,
        data: await response.json().catch(() => ({})),
      }));
    }

    function selectedProvider() {
      return document.querySelector('input[name="data_export_provider"]:checked')?.value || '';
    }

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function statusTheme(status) {
      switch (status) {
        case 'completed':
          return { badge: 'rgba(34,197,94,.18)', text: '#bbf7d0' };
        case 'failed':
          return { badge: 'rgba(248,113,113,.18)', text: '#fecaca' };
        case 'running':
          return { badge: 'rgba(56,189,248,.18)', text: '#bae6fd' };
        default:
          return { badge: 'rgba(59,130,246,.18)', text: '#bfdbfe' };
      }
    }

    function resolveVisibleStep(exportData) {
      const items = Array.isArray(exportData?.timeline) ? exportData.timeline : [];

      if (!items.length) {
        return null;
      }

      if (exportData?.status === 'failed') {
        return items.find((item) => item.status === 'failed')
          || items.find((item) => item.status === 'running')
          || null;
      }

      if (exportData?.status === 'completed') {
        return null;
      }

      return items.find((item) => item.status === 'running')
        || items.find((item) => item.status === 'pending')
        || null;
    }

    function renderTimeline(exportData) {
      if (!timeline) return;

      const visibleStep = resolveVisibleStep(exportData);
      if (!visibleStep) {
        timeline.innerHTML = '';
        return;
      }

      timeline.innerHTML = `
        <div class="data-export-step is-${escapeHtml(visibleStep.status || 'pending')}">
          <div class="data-export-step-label">${escapeHtml(visibleStep.label || '')}</div>
          <div class="data-export-step-copy">${escapeHtml(visibleStep.description || '')}</div>
        </div>
      `;
    }

    function renderLogs(items) {
      if (!logList) return;
      const rows = Array.isArray(items) ? items : [];
      if (!rows.length) {
        logList.innerHTML = '<div class="data-export-log-item"><strong>En attente</strong><span>Le journal apparaitra au fur et à mesure des étapes.</span></div>';
        return;
      }

      logList.innerHTML = rows.slice().reverse().map((item) => `
        <div class="data-export-log-item">
          <strong>${escapeHtml(item.label || 'Étape')}</strong>
          <span>${escapeHtml(item.message || '')}</span>
        </div>
      `).join('');
    }

    function renderWarnings(exportData) {
      if (!warningList) return;

      const warnings = Array.isArray(exportData?.warnings) ? exportData.warnings : [];
      const hasError = !!exportData?.error_message;
      const blocks = [];

      if (hasError) {
        blocks.push(`
          <div class="data-export-warning-item is-error">
            <strong>Échec de la sauvegarde</strong>
            <span>${escapeHtml(exportData.error_message)}</span>
          </div>
        `);
      }

      warnings.forEach((warning) => {
        blocks.push(`
          <div class="data-export-warning-item is-warning">
            <strong>Avertissement</strong>
            <span>${escapeHtml(warning)}</span>
          </div>
        `);
      });

      if (!blocks.length) {
        blocks.push('<div class="data-export-warning-item"><strong>Rien à signaler</strong><span>Les éventuels avertissements de génération ou d upload apparaîtront ici.</span></div>');
      }

      warningList.innerHTML = blocks.join('');
    }

    function renderHistory(items) {
      if (!historyList) {
        return;
      }

      const rows = Array.isArray(items) ? items : [];
      if (!rows.length) {
        historyList.innerHTML = '<div class="data-export-history-empty" id="dataExportHistoryEmpty">Aucune sauvegarde precedente a afficher pour le moment.</div>';
        return;
      }

      historyList.innerHTML = rows.map((item) => {
        const hasLink = !!item.remote_url;
        const isCompleted = item.status === 'completed';

        return `
          <div class="data-export-history-item" data-export-history-id="${escapeHtml(item.id)}">
            <div class="data-export-history-main">
              <div class="data-export-history-row">
                <strong>${escapeHtml(item.reference_date_label || 'Date indisponible')}</strong>
                <span class="data-export-history-provider">
                  <i class="${escapeHtml(item.provider?.icon || '')}"></i>
                  ${escapeHtml(item.provider?.label || 'Application inconnue')}
                </span>
              </div>
              <div class="data-export-history-copy">
                ${escapeHtml(item.file_name || 'Archive ZIP')}
                ${item.error_message ? `<span> - ${escapeHtml(item.error_message)}</span>` : ''}
              </div>
            </div>
            <div class="data-export-history-actions">
              <span class="data-export-provider-state ${isCompleted ? 'is-ready' : 'is-warning'}">
                ${escapeHtml(item.status_label || item.status || '')}
              </span>
              ${hasLink ? `<a href="${escapeHtml(item.remote_url)}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm"><i class="fas fa-up-right-from-square"></i> Ouvrir</a>` : ''}
            </div>
          </div>
        `;
      }).join('');
    }

    function syncHistoryWithExport(exportData) {
      if (!exportData || !['completed', 'failed'].includes(exportData.status)) {
        return;
      }

      const referenceDate = exportData.completed_at || exportData.started_at || new Date().toISOString();
      const existingIndex = exportState.history.findIndex((item) => Number(item.id) === Number(exportData.id));
      const nextItem = {
        id: exportData.id,
        provider: {
          slug: exportData.provider?.slug || '',
          label: exportData.provider?.label || 'Application inconnue',
          icon: exportData.provider?.icon || '',
        },
        status: exportData.status,
        status_label: exportData.status_label || exportData.status,
        file_name: exportData.file_name || '',
        remote_url: exportData.remote_url || '',
        reference_date: referenceDate,
        reference_date_label: exportData.completed_at || exportData.started_at
          ? new Date(referenceDate).toLocaleString('fr-FR', {
              year: 'numeric',
              month: '2-digit',
              day: '2-digit',
              hour: '2-digit',
              minute: '2-digit',
            }).replace(',', '')
          : '',
        error_message: exportData.error_message || '',
      };

      if (existingIndex >= 0) {
        exportState.history.splice(existingIndex, 1);
      }

      exportState.history.unshift(nextItem);
      exportState.history = exportState.history.slice(0, 8);
      renderHistory(exportState.history);
    }

    function renderResult(exportData) {
      if (!resultBox || !resultCopy || !openRemoteBtn || !providerActionBtn || !restartBtn) {
        return;
      }

      const provider = exportData?.provider || {};
      const isCompleted = exportData?.status === 'completed';
      const isFailed = exportData?.status === 'failed';
      const hasRemoteUrl = !!exportData?.remote_url;
      const needsAction = !provider.ready && !!provider.action_url;

      if (!isCompleted && !isFailed) {
        resultBox.style.display = 'none';
        openRemoteBtn.style.display = 'none';
        providerActionBtn.style.display = 'none';
        restartBtn.style.display = 'none';
        return;
      }

      resultBox.style.display = 'block';
      if (isCompleted) {
        resultCopy.textContent = hasRemoteUrl
          ? `L archive ${exportData.file_name || 'ZIP'} a bien été envoyée vers ${provider.label || 'la destination choisie'}.`
          : `La génération est terminée pour ${exportData.file_name || 'l archive ZIP'}.`;
      } else {
        resultCopy.textContent = exportData?.error_message || 'La sauvegarde a rencontré une erreur.';
      }

      if (hasRemoteUrl) {
        openRemoteBtn.href = exportData.remote_url;
        openRemoteBtn.style.display = 'inline-flex';
      } else {
        openRemoteBtn.style.display = 'none';
      }

      if (needsAction) {
        providerActionBtn.href = provider.action_url;
        providerActionBtn.innerHTML = provider.installed
          ? `<i class="fas fa-up-right-from-square"></i> Ouvrir l'app ${escapeHtml(provider.label || '')}`
          : `<i class="fas fa-puzzle-piece"></i> ${escapeHtml(provider.action_label || 'Installer le connecteur')}`;
        providerActionBtn.style.display = 'inline-flex';
      } else {
        providerActionBtn.style.display = 'none';
      }

      restartBtn.style.display = 'inline-flex';
    }

    function renderExport(exportData) {
      if (!statusCard || !exportData) {
        return;
      }

      statusCard.classList.add('is-visible');
      statusCard.classList.remove('is-hiding');

      const theme = statusTheme(exportData.status);
      if (statusBadge) {
        statusBadge.textContent = exportData.status_label || exportData.status || 'En attente';
        statusBadge.style.background = theme.badge;
        statusBadge.style.color = theme.text;
      }

      if (statusProvider) {
        statusProvider.textContent = exportData.provider?.label
          ? `${exportData.provider.label}${exportData.provider.ready ? ' prête' : ''}`
          : 'Destination inconnue';
      }

      if (statusTitle) {
        statusTitle.textContent = exportData.status === 'completed'
          ? 'Sauvegarde terminée'
          : exportData.status === 'failed'
            ? 'Sauvegarde interrompue'
            : 'Sauvegarde en cours';
      }

      const visibleStep = resolveVisibleStep(exportData);
      if (activeStepTitle) {
        if (visibleStep?.label) {
          activeStepTitle.textContent = visibleStep.label;
          activeStepTitle.classList.add('is-visible');
          activeStepTitle.style.display = 'inline-flex';
        } else {
          activeStepTitle.textContent = '';
          activeStepTitle.classList.remove('is-visible');
          activeStepTitle.style.display = 'none';
        }
      }

      if (statusSubtitle) {
        statusSubtitle.textContent = exportData.status === 'completed'
          ? 'Toutes les étapes sont terminées. Vous pouvez ouvrir l archive distante ou relancer une nouvelle sauvegarde.'
          : exportData.status === 'failed'
            ? (!exportData.provider?.ready && exportData.provider?.installed
              ? `La session ${exportData.provider.label || 'du connecteur'} doit etre reconnectee. Ouvrez la page de l app via le bouton ci-dessous, reconnectez le service, puis relancez la sauvegarde.`
              : 'Corrigez le point bloquant puis relancez une nouvelle sauvegarde.')
            : 'Le CRM prépare les exports, les PDF et l archive ZIP par étapes sécurisées.';
      }

      if (progressBar) {
        progressBar.style.width = `${Math.max(0, Math.min(100, Number(exportData.progress_percent || 0)))}%`;
      }

      if (progressPercent) {
        progressPercent.textContent = `${Number(exportData.progress_percent || 0)}%`;
      }

      if (currentStep) {
        currentStep.textContent = visibleStep?.description
          || exportData.current_step_label
          || 'Aucune étape active.';
      }

      renderTimeline(exportData);
      renderLogs(exportData.logs);
      renderWarnings(exportData);
      renderResult(exportData);
      syncHistoryWithExport(exportData);
      scheduleStatusAutoHide(exportData);
    }

    function processUrlFor(exportId) {
      return String(exportConfig.processUrlTemplate || '').replace('__ID__', exportId);
    }

    function showUrlFor(exportId) {
      return String(exportConfig.showUrlTemplate || '').replace('__ID__', exportId);
    }

    async function syncExport(exportId) {
      const response = await requestJson(showUrlFor(exportId));
      if (!response.ok) {
        return null;
      }
      return response.data?.data || null;
    }

    async function continueExportLoop() {
      if (!exportState.current?.can_continue || exportState.processing) {
        return;
      }

      exportState.processing = true;
      const response = await requestJson(processUrlFor(exportState.current.id), { method: 'POST', body: {} });
      exportState.processing = false;

      if (!response.ok) {
        if (window.Toast) {
          window.Toast.error('Sauvegarde', response.data?.message || 'Impossible de poursuivre la sauvegarde.');
        }
        const synced = await syncExport(exportState.current.id);
        if (synced) {
          exportState.current = synced;
          renderExport(exportState.current);
        }
        return;
      }

      exportState.current = response.data?.data || null;
      renderExport(exportState.current);

      if (exportState.current?.can_continue) {
        clearExportTimer();
        exportState.timer = window.setTimeout(() => {
          continueExportLoop();
        }, 650);
      } else if (window.Toast && exportState.current?.status === 'completed') {
        window.Toast.success('Succès', response.data?.message || 'Sauvegarde terminée avec succès.');
      } else if (window.Toast && exportState.current?.status === 'failed') {
        window.Toast.error('Sauvegarde', exportState.current?.error_message || 'La sauvegarde a échoué.');
      }
    }

    async function startExport() {
      const provider = selectedProvider();
      if (!provider) {
        if (window.Toast) {
          window.Toast.warning('Sauvegarde', 'Choisissez une destination prête avant de lancer la sauvegarde.');
        }
        return;
      }

      if (startExportBtn) {
        startExportBtn.disabled = true;
      }

      clearHideTimer();
      statusCard?.classList.add('is-visible');
      statusCard?.classList.remove('is-hiding');

      const response = await requestJson(exportConfig.startUrl, {
        method: 'POST',
        body: { provider },
      });

      if (startExportBtn) {
        startExportBtn.disabled = false;
      }

      if (!response.ok) {
        if (window.Toast) {
          window.Toast.error('Sauvegarde', response.data?.message || 'Impossible de démarrer la sauvegarde.');
        }
        return;
      }

      exportState.current = response.data?.data || null;
      renderExport(exportState.current);
      clearExportTimer();
      if (exportState.current?.can_continue) {
        continueExportLoop();
      }
    }

    if (form && form.dataset.secureAjax === '1') {
      form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!window.SecureForm) {
          form.submit();
          return;
        }

        const submitButton = document.getElementById('globalSettingsSaveBtn') || form.querySelector('button[type="submit"]');
        const result = await window.SecureForm.submit(form, {
          method: 'POST',
          submitButton,
        });

        if (result.ok) {
          if (window.Toast) {
            window.Toast.success('Succès', result.data?.message || 'Paramètres enregistrés.');
          }
          return;
        }

        if (result.status === 422) {
          if (window.Toast) {
            window.Toast.error('Validation', result.data?.message || 'Veuillez corriger les champs.');
          }
          return;
        }

        if (window.Toast) {
          window.Toast.error('Erreur', result.data?.message || 'Impossible de sauvegarder les paramètres.');
        }
      });
    }

    startExportBtn?.addEventListener('click', () => {
      startExport();
    });

    restartBtn?.addEventListener('click', () => {
      startExport();
    });

    if (exportState.current) {
      renderExport(exportState.current);
      if (exportState.current.can_continue) {
        continueExportLoop();
      }
    }

    renderHistory(exportState.history);
  });
}
