if (!window.__CRM_CORE_LOADED__) {
  window.__CRM_CORE_LOADED__ = true;

function crmRoute(name, replacements = {}) {
  let template = window.CLIENT_ROUTES?.[name] || '#';
  Object.entries(replacements).forEach(([key, value]) => {
    template = template.replace(`__${key.toUpperCase()}__`, encodeURIComponent(String(value)));
  });
  return template;
}

/**
 * CRM SaaS - Core JavaScript
 * Toast notifications, Modals, Table manager, Form helpers, AJAX utils
 */

/* ============================================================
   TOAST SYSTEM
   ============================================================ */
const Toast = (() => {
  let container = null;

  function closeConfirmModalOnSuccess() {
    const confirm = document.getElementById('confirmModal');
    if (!confirm || !confirm.classList.contains('open')) return;
    if (confirm.dataset.busy === '1') {
      confirm.dataset.closeOnSuccess = '1';
      return;
    }
    confirm.classList.remove('open');
    confirm.dataset.closeOnSuccess = '0';
    if (!document.querySelector('.modal-overlay.open')) {
      document.body.style.overflow = '';
    }
  }

  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }

  const icons = {
    success: '<i class="fas fa-check"></i>',
    error:   '<i class="fas fa-xmark"></i>',
    info:    'i',
    warning: '!',
  };

  function show(type, title, message = '', duration = 4500) {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <div class="toast-icon">${icons[type] || 'i'}</div>
      <div class="toast-body">
        <p class="toast-title">${title}</p>
        ${message ? `<p class="toast-message">${message}</p>` : ''}
      </div>
      <button class="toast-close" aria-label="Fermer">×</button>
    `;

    getContainer().appendChild(toast);

    const close = () => {
      toast.classList.add('removing');
      toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };

    toast.querySelector('.toast-close').addEventListener('click', close);
    if (duration > 0) setTimeout(close, duration);

    if (type === 'success') {
      closeConfirmModalOnSuccess();
    }

    return { close };
  }

  return {
    success: (title, msg, d) => show('success', title, msg, d),
    error:   (title, msg, d) => show('error',   title, msg, d),
    info:    (title, msg, d) => show('info',    title, msg, d),
    warning: (title, msg, d) => show('warning', title, msg, d),
  };
})();

window.Toast = Toast;

/* ============================================================
   MODAL SYSTEM
   ============================================================ */
const Modal = (() => {
  async function runConfirmDismiss(overlayEl) {
    if (!overlayEl || overlayEl.id !== 'confirmModal' || overlayEl.dataset.busy === '1') {
      return false;
    }

    const action = overlayEl.__confirmCancelAction;
    if (typeof action !== 'function') {
      return false;
    }

    await action();
    return true;
  }

  function setConfirmBusy(overlayEl, btn, busy, loadingText = window.CLIENT_LANG?.processing || 'Processing...') {
    if (!overlayEl || !btn) return;
    overlayEl.dataset.busy = busy ? '1' : '0';

    const closeButtons = overlayEl.querySelectorAll('[data-modal-close]');
    closeButtons.forEach((el) => {
      if (busy) {
        el.setAttribute('disabled', 'disabled');
      } else {
        el.removeAttribute('disabled');
      }
    });

    if (busy) {
      btn.dataset.originalText = btn.innerHTML;
      btn.disabled = true;
      btn.classList.add('loading');
      btn.setAttribute('aria-busy', 'true');
      btn.innerHTML = loadingText;
      return;
    }

    btn.disabled = false;
    btn.classList.remove('loading');
    btn.removeAttribute('aria-busy');
    btn.innerHTML = btn.dataset.originalText || btn.innerHTML;

    if (overlayEl.dataset.closeOnSuccess === '1') {
      overlayEl.classList.remove('open');
      overlayEl.dataset.closeOnSuccess = '0';
      if (!document.querySelector('.modal-overlay.open')) {
        document.body.style.overflow = '';
      }
    }
  }

  function open(overlayEl) {
    if (!overlayEl) return;
    overlayEl.classList.add('open');
    document.body.style.overflow = 'hidden';
    overlayEl.dispatchEvent(new CustomEvent('crm:modal-open'));
    // Close on backdrop click
    overlayEl.addEventListener('click', async (e) => {
      if (e.target !== overlayEl) return;
      const handled = await runConfirmDismiss(overlayEl);
      if (!handled) close(overlayEl);
    }, { once: true });
    // Close on Escape
    const escHandler = (e) => {
      if (e.key === 'Escape') {
        Promise.resolve(runConfirmDismiss(overlayEl)).then((handled) => {
          if (!handled) close(overlayEl);
          document.removeEventListener('keydown', escHandler);
        });
      }
    };
    document.addEventListener('keydown', escHandler);
  }

  function close(overlayEl, force = false) {
    if (!overlayEl) return;
    if (!force && overlayEl.id === 'confirmModal' && overlayEl.dataset.busy === '1') return;
    const wasOpen = overlayEl.classList.contains('open');
    overlayEl.classList.remove('open');
    overlayEl.dataset.busy = '0';
    overlayEl.dataset.closeOnSuccess = '0';
    document.body.style.overflow = '';
    if (wasOpen) {
      overlayEl.dispatchEvent(new CustomEvent('crm:modal-close'));
    }
  }

  function confirm({
    title,
    message,
    confirmText = window.CLIENT_LANG?.confirmText || 'Confirm',
    cancelText = window.CLIENT_LANG?.cancelText || 'Cancel',
    type = 'danger',
    iconHtml = '',
    iconVariant = '',
    iconColor = '',
    onConfirm,
    onCancel,
  }) {
    const overlay = document.getElementById('confirmModal');
    if (!overlay) { console.warn('confirmModal element not found'); return; }

    const iconEl = overlay.querySelector('[data-confirm-icon]');
    const cancelBtn = overlay.querySelector('[data-modal-close].btn');
    const fallbackIcons = {
      danger: 'fas fa-exclamation-triangle',
      success: 'fas fa-circle-check',
      warning: 'fas fa-triangle-exclamation',
      info: 'fas fa-circle-info',
    };
    const resolvedVariant = iconVariant || (iconHtml ? 'app' : type);

    overlay.querySelector('[data-confirm-title]').textContent  = title;
    overlay.querySelector('[data-confirm-text]').textContent   = message;
    const btn = overlay.querySelector('[data-confirm-ok]');
    if (iconEl) {
      iconEl.className = `modal-confirm-icon ${resolvedVariant}`;
      if (iconColor) iconEl.style.setProperty('--modal-app-color', iconColor);
      else iconEl.style.removeProperty('--modal-app-color');
      iconEl.innerHTML = iconHtml || `<i class="${fallbackIcons[type] || fallbackIcons.info}"></i>`;
    }
    btn.textContent = confirmText;
    btn.className = `btn btn-${type}`;
    overlay.dataset.busy = '0';
    overlay.dataset.closeOnSuccess = '0';
    overlay.__confirmCancelAction = null;

    // Remove previous listeners
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
    newBtn.textContent = confirmText;
    newBtn.className = `btn btn-${type}`;

    if (cancelBtn) {
      const newCancelBtn = cancelBtn.cloneNode(true);
      cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
      newCancelBtn.textContent = cancelText;

      overlay.__confirmCancelAction = async () => {
        if (overlay.dataset.busy === '1') return;
        const loadingText = /supprim/i.test(cancelText)
          ? (window.CLIENT_LANG?.deleting || 'Deleting...')
          : (window.CLIENT_LANG?.processing || 'Processing...');
        setConfirmBusy(overlay, newCancelBtn, true, loadingText);
        try {
          if (typeof onCancel === 'function') {
            await onCancel();
          } else {
            close(overlay, true);
          }
        } catch (err) {
          Toast.error(window.CLIENT_LANG?.errorTitle || 'Error', err?.message || window.CLIENT_LANG?.unexpectedError || 'Unexpected error.');
        } finally {
          setConfirmBusy(overlay, newCancelBtn, false);
        }
      };

      newCancelBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        await overlay.__confirmCancelAction();
      });
    }

    newBtn.addEventListener('click', async () => {
      if (overlay.dataset.busy === '1') return;
      const loadingText = /supprim/i.test(confirmText)
        ? (window.CLIENT_LANG?.deleting || 'Deleting...')
        : (window.CLIENT_LANG?.processing || 'Processing...');
      setConfirmBusy(overlay, newBtn, true, loadingText);
      try {
        if (typeof onConfirm === 'function') {
          await onConfirm();
        }
      } catch (err) {
        Toast.error(window.CLIENT_LANG?.errorTitle || 'Error', err?.message || window.CLIENT_LANG?.unexpectedError || 'Unexpected error.');
      } finally {
        setConfirmBusy(overlay, newBtn, false);
      }
    });

    open(overlay);
  }

  // Wire up all [data-modal-open] triggers
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-modal-open]');
    if (trigger) {
      const target = document.getElementById(trigger.dataset.modalOpen);
      open(target);
    }
    const closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) {
      const overlay = closeBtn.closest('.modal-overlay');
      if (overlay?.id === 'confirmModal') {
        Promise.resolve(runConfirmDismiss(overlay)).then((handled) => {
          if (!handled) close(overlay);
        });
        return;
      }
      close(overlay);
    }
  });

  return { open, close, confirm };
})();

window.Modal = Modal;

/* ============================================================
   FORM HELPERS
   ============================================================ */
const Form = (() => {
  function clearErrors(form) {
    form.querySelectorAll('.form-error').forEach(el => el.remove());
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  }

  function showErrors(form, errors) {
    clearErrors(form);
    Object.entries(errors).forEach(([field, messages]) => {
      const input = form.querySelector(`[name="${field}"]`);
      if (!input) return;
      input.classList.add('is-invalid');
      const err = document.createElement('span');
      err.className = 'form-error';
      err.textContent = Array.isArray(messages) ? messages[0] : messages;
      input.parentNode.appendChild(err);
    });
    // Scroll to first error
    const first = form.querySelector('.is-invalid');
    if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function setLoading(btn, loading) {
    if (loading) {
      btn.dataset.originalText = btn.innerHTML;
      btn.dataset.originalAriaLabel = btn.getAttribute('aria-label') || '';
      btn.disabled = true;
      btn.classList.add('loading');
      btn.setAttribute('aria-busy', 'true');

      const loadingText = btn.getAttribute('data-loading-text');
      if (loadingText && loadingText.trim() !== '') {
        btn.innerHTML = loadingText;
      }
    } else {
      btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
      if (btn.dataset.originalAriaLabel !== undefined) {
        const v = btn.dataset.originalAriaLabel;
        if (v) btn.setAttribute('aria-label', v);
        else btn.removeAttribute('aria-label');
      }
      btn.disabled = false;
      btn.classList.remove('loading');
      btn.removeAttribute('aria-busy');
    }
  }

  function serialize(form) {
    const data = new FormData(form);
    // handle checkboxes that aren't checked
    return data;
  }

  return { clearErrors, showErrors, setLoading, serialize };
})();

window.CrmForm = Form;

/* ============================================================
   GLOBAL REQUEST LOADER (TOP BAR)
   ============================================================ */
const RequestProgress = (() => {
  let root = null;
  let bar = null;
  let pending = 0;
  let progress = 0;
  let tickTimer = null;
  let hideTimer = null;

  function ensureDom() {
    if (root && bar) return true;

    const main = document.querySelector('.crm-main') || document.body;
    if (!main) return false;

    root = document.getElementById('crmTopLoader');
    if (!root) {
      root = document.createElement('div');
      root.id = 'crmTopLoader';
      root.className = 'crm-top-loader';
      root.innerHTML = '<div class="crm-top-loader-bar"></div>';
      main.insertBefore(root, main.firstChild || null);
    }

    bar = root.querySelector('.crm-top-loader-bar');
    return !!bar;
  }

  function setProgress(value) {
    if (!ensureDom()) return;
    progress = Math.max(progress, Math.min(100, value));
    bar.style.width = `${progress}%`;
  }

  function startTick() {
    clearInterval(tickTimer);
    tickTimer = setInterval(() => {
      if (progress >= 90) return;
      setProgress(progress + (Math.random() * 8) + 2);
    }, 220);
  }

  function stopTick() {
    clearInterval(tickTimer);
    tickTimer = null;
  }

  function start() {
    if (!ensureDom()) return;
    pending += 1;
    if (pending > 1) return;

    clearTimeout(hideTimer);
    progress = 0;
    bar.style.width = '0%';
    root.classList.add('active');
    requestAnimationFrame(() => setProgress(18));
    startTick();
  }

  function done() {
    if (pending > 0) pending -= 1;
    if (pending > 0) return;

    stopTick();
    setProgress(100);

    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
      if (!root || !bar) return;
      root.classList.remove('active');
      progress = 0;
      bar.style.width = '0%';
    }, 260);
  }

  function wrapFetch() {
    if (window.__CRM_FETCH_WRAPPED__ || typeof window.fetch !== 'function') return;
    const nativeFetch = window.fetch.bind(window);
    window.fetch = (...args) => {
      start();
      return nativeFetch(...args).finally(done);
    };
    window.__CRM_FETCH_WRAPPED__ = true;
  }

  return { init: ensureDom, start, done, wrapFetch };
})();

window.RequestProgress = RequestProgress;
RequestProgress.wrapFetch();

/* ============================================================
   HTTP / AJAX HELPER
   ============================================================ */
const Http = (() => {
  function loginUrl() {
    return window.CRM_AUTH_ROUTES?.login || '/login';
  }

  function redirectToLogin(message = 'Votre session a expire. Redirection vers la connexion.') {
    if (window.Toast) {
      window.Toast.warning('Session expiree', message, 1600);
    }

    window.setTimeout(() => {
      window.location.href = loginUrl();
    }, 180);
  }

  function isReconnectPayload(payload) {
    return !!payload && !!payload.reconnect_required && !!(payload.redirect || payload.redirect_url || payload.reconnect_url);
  }

  function reconnectUrl(payload = {}) {
    return payload.redirect || payload.redirect_url || payload.reconnect_url || '';
  }

  function normalizeReconnectText(message) {
    return String(message || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();
  }

  function resolveReconnectRedirect(message, payload = null) {
    if (isReconnectPayload(payload)) {
      return reconnectUrl(payload);
    }

    const text = normalizeReconnectText(message);
    if (!text) {
      return '';
    }

    const providerRoutes = [
      {
        url: window.CLIENT_EXTENSION_ROUTES?.googleGmail || '',
        patterns: ['google gmail', 'gmail n est pas connecte', 'google gmail is not connected', 'session google gmail expiree', 'reconnectez google gmail', 'reconnect your gmail'],
      },
      {
        url: window.CLIENT_EXTENSION_ROUTES?.googleCalendar || '',
        patterns: ['google calendar', 'calendar n est pas connecte', 'google calendar is not connected', 'session google calendar expiree', 'reconnectez google calendar', 'reconnect google calendar'],
      },
      {
        url: window.CLIENT_EXTENSION_ROUTES?.googleDrive || '',
        patterns: ['google drive', 'drive n est pas connecte', 'google drive is not connected', 'session google drive expiree', 'reconnectez google drive', 'reconnect google drive'],
      },
      {
        url: window.CLIENT_EXTENSION_ROUTES?.dropbox || '',
        patterns: ['dropbox n est pas connecte', 'dropbox demande une reconnexion', 'reconnectez dropbox', 'reconnect dropbox', 'refresh token manquant', 'invalid_access_token', 'expired_access_token', 'invalid_grant'],
      },
      {
        url: window.CLIENT_EXTENSION_ROUTES?.slack || '',
        patterns: ['slack n est pas connecte', 'slack is not connected', 'slack bot token is missing', 'reconnect your slack workspace', 'reconnectez slack', 'invalid_auth', 'token_revoked', 'account_inactive'],
      },
      {
        url: window.CLIENT_EXTENSION_ROUTES?.googleMeet || '',
        patterns: ['google meet', 'google meet is not connected', 'session google meet expiree', 'reconnectez google meet', 'reconnect google meet'],
      },
      {
        url: window.CLIENT_EXTENSION_ROUTES?.googleSheets || '',
        patterns: ['google sheets', 'google sheets is not connected', 'session google sheets expiree', 'reconnectez votre compte google', 'reconnectez google sheets', 'reconnect google sheets'],
      },
      {
        url: window.CLIENT_EXTENSION_ROUTES?.googleDocx || '',
        patterns: ['google docs', 'google docs is not connected', 'session google docs expiree', 'reconnectez google docs', 'reconnect google docs'],
      },
      {
        url: window.CLIENT_EXTENSION_ROUTES?.notionWorkspace || '',
        patterns: ['notion workspace', 'notion n est pas connecte', 'session notion expiree', 'session notion workspace expiree', 'reconnectez notion', 'reconnect notion', 'reconnectez votre workspace notion', 'reconnect your notion workspace'],
      },
    ];

    for (const provider of providerRoutes) {
      if (!provider.url) continue;
      if ((provider.patterns || []).some((pattern) => text.includes(pattern))) {
        return new URL(provider.url, window.location.origin).toString();
      }
    }

    return '';
  }

  function redirectToReconnect(message = 'La session de cette extension a expire. Redirection vers la reconnexion.', targetUrl = '') {
    const redirectUrl = String(targetUrl || '').trim();
    if (!redirectUrl) {
      return;
    }

    if (window.Toast) {
      window.Toast.warning('Reconnexion requise', message, 1800);
    }

    window.setTimeout(() => {
      window.location.href = redirectUrl;
    }, 220);
  }

  function isLoginRedirectResponse(response) {
    if (!response || !response.redirected || !response.url) {
      return false;
    }

    try {
      const redirectedUrl = new URL(response.url, window.location.origin);
      const loginPath = new URL(loginUrl(), window.location.origin).pathname.replace(/\/+$/, '');

      return redirectedUrl.pathname.replace(/\/+$/, '') === loginPath;
    } catch (e) {
      return false;
    }
  }

  function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }

  async function request(url, options = {}) {
    const defaults = {
      headers: {
        'X-CSRF-TOKEN': getCsrf(),
        'X-Requested-With': 'XMLHttpRequest',
      },
    };

    // Merge headers
    const headers = { ...defaults.headers, ...(options.headers || {}) };

    // If body is FormData, don't set Content-Type (browser handles boundary)
    if (!(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      headers['Accept'] = 'application/json';
    }

    const response = await fetch(url, { ...options, headers });
    if (isLoginRedirectResponse(response)) {
      redirectToLogin();

      return {
        ok: false,
        status: 401,
        data: {
          success: false,
          message: 'Votre session a expire. Redirection vers la connexion.',
          redirect: loginUrl(),
        },
      };
    }

    const data = await response.json().catch(() => ({}));
    if (response.status === 401 || response.status === 419) {
      redirectToLogin(data?.message || 'Votre session a expire. Redirection vers la connexion.');
    }

    const reconnectTarget = resolveReconnectRedirect(data?.message, data);
    if (reconnectTarget) {
      redirectToReconnect(
        data?.message || 'La session de cette extension a expire. Redirection vers la reconnexion.',
        reconnectTarget
      );
    }

    return { ok: response.ok, status: response.status, data };
  }

  const get  = (url, params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(qs ? `${url}?${qs}` : url, { method: 'GET' });
  };
  const post   = (url, body) => request(url, { method: 'POST',   body: body instanceof FormData ? body : JSON.stringify(body) });
  const put    = (url, body) => request(url, { method: 'PUT',    body: JSON.stringify(body) });
  const del    = (url)       => request(url, { method: 'DELETE' });

  window.CrmAuth = Object.assign(window.CrmAuth || {}, {
    loginUrl,
    redirectToLogin,
    isReconnectPayload,
    reconnectUrl,
    resolveReconnectRedirect,
    redirectToReconnect,
    isLoginRedirectResponse,
  });

  return { get, post, put, delete: del };
})();

window.Http = Http;

/* ============================================================
   TABLE MANAGER
   ============================================================ */
class CrmTable {
  constructor(options) {
    this.options = Object.assign({
      tableId:      'clientsTable',
      tbodyId:      'clientsTableBody',
      dataUrl:      null,
      statsUrl:     null,
      perPage:      15,
      renderRow:    null,
      renderEmpty:  null,
    }, options);

    this.state = { page: 1, search: '', filters: {}, sort: '', dir: 'asc', total: 0, loading: false };
    this.selectedIds = new Set();
    this.debounceTimer = null;

    this._bindEvents();
    this.load();
    if (this.options.statsUrl) this.loadStats();
  }

  _bindEvents() {
    // Search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
          this.state.search = searchInput.value.trim();
          this.state.page = 1;
          this.load();
        }, 350);
      });
    }

    // Filter selects
    document.querySelectorAll('[data-filter]').forEach(sel => {
      sel.addEventListener('change', () => {
        this.state.filters[sel.dataset.filter] = sel.value;
        this.state.page = 1;
        this.load();
      });
    });

    // Reset filters
    document.getElementById('resetFilters')?.addEventListener('click', () => {
      this.state.search = '';
      this.state.filters = {};
      document.getElementById('searchInput') && (document.getElementById('searchInput').value = '');
      document.querySelectorAll('[data-filter]').forEach(s => s.value = '');
      this.state.page = 1;
      this.load();
    });

    // Select all
    document.getElementById('selectAll')?.addEventListener('change', (e) => {
      document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = e.target.checked;
        const id = parseInt(cb.dataset.id);
        e.target.checked ? this.selectedIds.add(id) : this.selectedIds.delete(id);
      });
      this._updateBulkBar();
    });

    // Tbody: delegate row checkbox
    const tbody = document.getElementById(this.options.tbodyId);
    if (tbody) {
      tbody.addEventListener('change', (e) => {
        if (e.target.classList.contains('row-check')) {
          const id = parseInt(e.target.dataset.id);
          e.target.checked ? this.selectedIds.add(id) : this.selectedIds.delete(id);
          this._updateBulkBar();
        }
      });
    }

    // Sortable headers
    document.querySelectorAll('[data-sort]').forEach(th => {
      th.addEventListener('click', () => {
        const col = th.dataset.sort;
        if (this.state.sort === col) this.state.dir = this.state.dir === 'asc' ? 'desc' : 'asc';
        else { this.state.sort = col; this.state.dir = 'asc'; }
        this.load();
      });
    });
  }

  async load() {
    if (this.state.loading) return;
    this.state.loading = true;
    this._showSkeletons();

    const params = {
      page:     this.state.page,
      per_page: this.options.perPage,
      search:   this.state.search,
      sort_by:  this.state.sort,
      sort_dir: this.state.dir,
      ...this.state.filters,
    };

    const { ok, data } = await Http.get(this.options.dataUrl, params);
    this.state.loading = false;

    if (!ok) { Toast.error(window.CLIENT_LANG?.errorTitle || 'Error', window.CLIENT_LANG?.loadFailed || 'Unable to load data.'); return; }

    this.state.total = data.total || 0;
    this._renderRows(data.data || []);
    this._renderPagination(data);
    this._updateCount(data.total);
  }

  async loadStats() {
    const { ok, data } = await Http.get(this.options.statsUrl);
    if (!ok || !data.data) return;
    const stats = data.data;
    this._setStat('totalClients',  stats.total);
    this._setStat('activeClients', stats.active);
    this._setStat('pendingClients',stats.pending);
    this._setStat('totalRevenue',  this._formatCurrency(stats.revenue_total));
  }

  _setStat(id, val) {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = val;
      el.classList.add('stat-animate');
    }
  }

  _formatCurrency(n) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: (window.DEFAULT_CURRENCY || 'EUR'), maximumFractionDigits: 0 }).format(n || 0);
  }

  _showSkeletons(count = 5) {
    const tbody = document.getElementById(this.options.tbodyId);
    if (!tbody) return;
    tbody.innerHTML = Array.from({ length: count }, () => `
      <tr>
        ${Array.from({ length: 8 }, () => `<td><div class="skeleton" style="height:14px;border-radius:4px;"></div></td>`).join('')}
      </tr>
    `).join('');
  }

  _renderRows(rows) {
    const tbody = document.getElementById(this.options.tbodyId);
    if (!tbody) return;

    if (!rows.length) {
      tbody.innerHTML = `
        <tr><td colspan="8">
          <div class="table-empty">
            <div class="table-empty-icon"><i class="fas fa-users"></i></div>
            <h3>Aucun client trouvé</h3>
            <p>Modifiez vos filtres ou créez votre premier client.</p>
            <a href="${window.CRM_ROUTES?.create || '#'}" class="btn btn-primary">
              <i class="fas fa-plus"></i> Nouveau client
            </a>
          </div>
        </td></tr>
      `;
      return;
    }

    tbody.innerHTML = rows.map(client => this._renderRow(client)).join('');

    // Re-check selected
    tbody.querySelectorAll('.row-check').forEach(cb => {
      cb.checked = this.selectedIds.has(parseInt(cb.dataset.id));
    });
  }

  _renderRow(c) {
    if (typeof this.options.renderRow === 'function') return this.options.renderRow(c);

    const avatarColors = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706','#dc2626'];
    const color = avatarColors[(c.company_name?.charCodeAt(0) || 0) % avatarColors.length];
    const initials = (c.company_name || '??').substring(0, 2).toUpperCase();
    const statusBadge = `<span class="badge badge-${c.status}"><span class="badge-dot" style="background:currentColor"></span>${this._statusLabel(c.status)}</span>`;
    const typeBadge   = `<span class="badge badge-${c.type}">${this._typeLabel(c.type)}</span>`;
    const revenue = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: (window.DEFAULT_CURRENCY || 'EUR'), maximumFractionDigits: 0 }).format(c.revenue || 0);

    return `
      <tr data-id="${c.id}" class="${this.selectedIds.has(c.id) ? 'selected' : ''}">
        <td style="width:40px;">
          <input type="checkbox" class="row-check" data-id="${c.id}" ${this.selectedIds.has(c.id) ? 'checked' : ''}>
        </td>
        <td>
          <div class="client-cell">
            <div class="client-avatar" style="background:${color}">${initials}</div>
            <div>
              <div class="client-name">${this._esc(c.company_name)}</div>
              <div class="client-sub">${this._esc(c.contact_name || c.email || '')}</div>
            </div>
          </div>
        </td>
        <td>${typeBadge}</td>
        <td style="color:var(--c-ink-60)">${this._esc(c.email)}</td>
        <td style="color:var(--c-ink-40)">${c.phone || '—'}</td>
        <td>${statusBadge}</td>
        <td style="font-weight:500">${revenue}</td>
        <td>
          <div class="row-actions">
            <a href="${crmRoute('show', { client: c.uuid ?? c.id })}" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
            <a href="${crmRoute('edit', { client: c.uuid ?? c.id })}" class="btn-icon" title="Modifier"><i class="fas fa-pen"></i></a>
            <button class="btn-icon danger" onclick="CrmTable.deleteClient(${c.id},'${this._esc(c.company_name)}')" title="Supprimer">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }

  _statusLabel(s) { return { actif:'Actif', inactif:'Inactif', en_attente:'En attente', suspendu:'Suspendu' }[s] || s; }
  _typeLabel(t)   { return { entreprise:'Entreprise', particulier:'Particulier', startup:'Startup', association:'Association', public:'Public' }[t] || t; }
  _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

  _renderPagination(data) {
    const wrap = document.getElementById('paginationControls');
    const info = document.getElementById('paginationInfo');
    if (!wrap) return;

    const { current_page, last_page, from, to, total } = data;
    if (info) info.textContent = `Affichage de ${from || 0} à ${to || 0} sur ${total || 0} clients`;

    const pages = [];
    for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) pages.push(i);

    wrap.innerHTML = `
      <button class="page-btn" ${current_page <= 1 ? 'disabled' : ''} onclick="window._crmTable?.goTo(${current_page - 1})">
        <i class="fas fa-chevron-left"></i>
      </button>
      ${pages.map(p => `<button class="page-btn ${p === current_page ? 'active' : ''}" onclick="window._crmTable?.goTo(${p})">${p}</button>`).join('')}
      <button class="page-btn" ${current_page >= last_page ? 'disabled' : ''} onclick="window._crmTable?.goTo(${current_page + 1})">
        <i class="fas fa-chevron-right"></i>
      </button>
    `;
  }

  goTo(page) {
    this.state.page = page;
    this.load();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  _updateCount(total) {
    const el = document.querySelector('.table-count');
    if (el) el.textContent = `${total || 0} client${(total || 0) !== 1 ? 's' : ''}`;
  }

  _updateBulkBar() {
    const bar   = document.getElementById('bulkBar');
    const count = document.getElementById('selectedCount');
    if (!bar) return;
    const n = this.selectedIds.size;
    bar.classList.toggle('visible', n > 0);
    if (count) count.textContent = n;
    document.getElementById('selectAll') && (document.getElementById('selectAll').indeterminate = n > 0);
  }

  getSelectedIds() { return [...this.selectedIds]; }

  /* Static helper called from inline onclick */
  static deleteClient(id, name) {
    Modal.confirm({
      title:       window.CLIENT_LANG?.deleteTitle || '',
      message:     (window.CLIENT_LANG?.deleteMessage || ':name').replace(':name', name),
      confirmText: window.CLIENT_LANG?.deleteAction || '',
      type:        'danger',
      onConfirm:   async () => {
        const { ok, data } = await Http.delete(crmRoute('destroy', { client: id }));
        if (ok) {
          Toast.success(window.CLIENT_LANG?.deletedTitle || '', data.message || window.CLIENT_LANG?.deletedTitle || '');
          window._crmTable?.load();
          window._crmTable?.loadStats();
        } else {
          Toast.error(window.CLIENT_LANG?.errorTitle || 'Error', data.message || window.CLIENT_LANG?.deleteUnable || '');
        }
      },
    });
  }
}

window.CrmTable = CrmTable;

/* ============================================================
   BULK OPERATIONS (wired up in index page)
   ============================================================ */
async function bulkDelete() {
  const ids = window._crmTable?.getSelectedIds();
  if (!ids?.length) return;
  Modal.confirm({
    title:       (window.CLIENT_LANG?.bulkDeleteTitle || ':count').replace(':count', ids.length),
    message:     window.CLIENT_LANG?.bulkDeleteMessage || '',
    confirmText: window.CLIENT_LANG?.deleteAction || '',
    type:        'danger',
    onConfirm:   async () => {
      const { ok, data } = await Http.post(window.CRM_ROUTES?.bulkDelete, { ids });
      if (ok) {
        Toast.success(window.CLIENT_LANG?.successTitle || 'Success', data.message);
        window._crmTable?.load();
        window._crmTable?.loadStats();
        window._crmTable?.selectedIds.clear();
        window._crmTable?._updateBulkBar();
      } else {
        Toast.error(window.CLIENT_LANG?.errorTitle || 'Error', data.message);
      }
    },
  });
}

async function bulkStatus(status) {
  const ids = window._crmTable?.getSelectedIds();
  if (!ids?.length) return;
  const { ok, data } = await Http.post(window.CRM_ROUTES?.bulkStatus, { ids, status });
  if (ok) {
    Toast.success(window.CLIENT_LANG?.successTitle || 'Success', data.message);
    window._crmTable?.load();
    window._crmTable?.selectedIds.clear();
    window._crmTable?._updateBulkBar();
  } else {
    Toast.error(window.CLIENT_LANG?.errorTitle || 'Error', data.message);
  }
}

/* ============================================================
   AJAX FORM SUBMISSION
   ============================================================ */
function ajaxForm(formId, options = {}) {
  const form = document.getElementById(formId);
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('[type=submit]');

    CrmForm.clearErrors(form);
    if (btn) CrmForm.setLoading(btn, true);

    const formData = new FormData(form);
    const method = (formData.get('_method') || form.method || 'POST').toUpperCase();
    const url    = form.action;

    // Always submit multipart FormData (supports files on create/update).
    // Also skip empty file inputs to keep optional uploads truly optional.
    const payload = new FormData();
    formData.forEach((value, key) => {
      if (value instanceof File && value.size === 0 && !value.name) {
        return;
      }
      payload.append(key, value);
    });
    if (method !== 'POST' && !payload.get('_method')) {
      payload.set('_method', method);
    }

    const res = await Http.post(url, payload);

    if (btn) CrmForm.setLoading(btn, false);

    if (res.ok) {
      if (form.__crmDraftManager && typeof form.__crmDraftManager.complete === 'function') {
        form.__crmDraftManager.complete(res.data);
      }

      Toast.success(window.CLIENT_LANG?.successTitle || 'Success', res.data.message || window.CLIENT_LANG?.operationSuccess || '');
      const automationFlow = !options.skipAutomation
        && window.AutomationSuggestions
        && res.data?.automation?.should_prompt
        ? window.AutomationSuggestions.open(res.data.automation, {
            redirectUrl: res.data.redirect || null,
          })
        : null;

      let onSuccessResult;
      if (options.onSuccess) onSuccessResult = await options.onSuccess(res.data);

      if (res.data.redirect && !options.noRedirect && onSuccessResult !== false) {
        if (automationFlow && typeof automationFlow.finally === 'function') {
          automationFlow.finally(() => {
            window.location.href = res.data.redirect;
          });
        } else {
          setTimeout(() => window.location.href = res.data.redirect, 900);
        }
      }
    } else if (res.status === 422) {
      CrmForm.showErrors(form, res.data.errors || {});
      Toast.error(window.CLIENT_LANG?.validationTitle || 'Form', res.data.message || window.CLIENT_LANG?.fixErrors || '');
    } else {
      Toast.error(window.CLIENT_LANG?.errorTitle || 'Error', res.data.message || window.CLIENT_LANG?.unexpectedError || '');
    }
  });
}

window.ajaxForm = ajaxForm;

/* ============================================================
   DRAFTS / AUTOSAVE
   ============================================================ */
const CrmDrafts = (() => {
  const DEFAULTS = {
    saveUrl: '/api/drafts/save',
    loadUrl: '/api/drafts/load',
    deleteUrlBase: '/api/drafts/delete',
    debounceMs: Number(window.CRM_DRAFTS_CONFIG?.debounceMs || 1500),
    promptOnLoad: true,
    autoLoad: true,
    renderUi: false,
  };

  function attach(formRef, options = {}) {
    const form = typeof formRef === 'string' ? document.getElementById(formRef) : formRef;
    if (!form) return null;
    if (form.__crmDraftManager) return form.__crmDraftManager;

    const settings = { ...DEFAULTS, ...options };
    const state = {
      draft: null,
      timer: null,
      loading: false,
      restoring: false,
      resumed: false,
      prompted: false,
      skipExitPersist: false,
      initialData: {},
    };

    const draftInput = ensureDraftInput(form);
    const ui = settings.renderUi === false ? null : ensureDraftUi(form);

    const api = {
      load,
      saveNow,
      resume,
      discard,
      complete,
      resetLocal,
      hasDraft: () => !!state.draft,
      getDraft: () => state.draft,
      promptResumeIfAvailable,
      setStatus,
    };

    form.__crmDraftManager = api;
    bind();
    captureInitialData();

    if (settings.autoLoad !== false) {
      window.setTimeout(() => {
        load({ prompt: settings.promptOnLoad !== false });
      }, 0);
    }

    return api;

    function bind() {
      if (ui?.resumeBtn) {
        ui.resumeBtn.addEventListener('click', () => resume());
      }

      if (ui?.discardBtn) {
        ui.discardBtn.addEventListener('click', () => discard());
      }

      form.addEventListener('input', (event) => {
        if (shouldIgnoreEvent(event)) return;
        scheduleSave();
      }, true);

      form.addEventListener('change', (event) => {
        if (shouldIgnoreEvent(event)) return;
        scheduleSave();
      }, true);

      window.addEventListener('pagehide', flushPendingSave, true);
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
          flushPendingSave();
        }
      });
    }

    function captureInitialData() {
      state.initialData = normalizeDraftValue(collectRawData()) || {};
    }

    function collectRawData() {
      let data = serializeForm(form);
      if (typeof settings.collect === 'function') {
        data = settings.collect(data, form, api) || data;
      }

      return data;
    }

    function shouldIgnoreEvent(event) {
      if (state.restoring) return true;
      if (typeof settings.shouldSave === 'function' && settings.shouldSave() === false) return true;

      const target = event?.target;
      if (!target || !target.name) return false;

      const type = String(target.type || '').toLowerCase();

      return ['file', 'submit', 'button', 'reset'].includes(type);
    }

    function scheduleSave() {
      clearTimeout(state.timer);
      state.skipExitPersist = false;
      setStatus(window.CLIENT_LANG?.draftSaving || '', 'muted');
      state.timer = window.setTimeout(() => {
        saveNow();
      }, Math.max(300, settings.debounceMs || 1500));
    }

    async function load({ prompt = false } = {}) {
      const params = { type: resolveType() };
      const route = resolveRoute();
      if (route) params.route = route;

      const res = await Http.get(settings.loadUrl, params);
      if (!res.ok) {
        setStatus(res.data?.message || window.CLIENT_LANG?.draftUnavailable || '', 'danger');
        return null;
      }

      state.draft = res.data?.data || null;
      draftInput.value = state.draft?.id || '';
      refreshUi();
      emitStateChange();

      if (typeof settings.onDraftLoaded === 'function') {
        settings.onDraftLoaded(state.draft, api);
      }

      if (prompt && state.draft) {
        promptResumeIfAvailable();
      }

      return state.draft;
    }

    async function saveNow() {
      if (state.loading || state.restoring) return null;
      if (typeof settings.shouldSave === 'function' && settings.shouldSave() === false) return null;

      const payload = buildPayload();
      if (!payload) {
        return null;
      }

      state.loading = true;
      const res = await Http.post(settings.saveUrl, payload);
      state.loading = false;

      if (!res.ok || !res.data?.data) {
        setStatus(res.data?.message || window.CLIENT_LANG?.draftFailed || '', 'danger');
        return null;
      }

      state.draft = res.data.data;
      state.resumed = true;
      state.prompted = false;
      draftInput.value = state.draft.id || '';
      refreshUi();
      setStatus((window.CLIENT_LANG?.draftSavedAt || ':time').replace(':time', formatRelativeTime(state.draft.updated_at)), 'success');
      emitStateChange();

      if (typeof settings.onSaved === 'function') {
        settings.onSaved(state.draft, api);
      }

      return state.draft;
    }

    async function resume() {
      if (!state.draft?.data) return;

      state.restoring = true;
      applySerializedData(form, state.draft.data);

      if (typeof settings.apply === 'function') {
        settings.apply(state.draft.data, form, api);
      }

      window.setTimeout(() => {
        state.restoring = false;
      }, 0);

      state.resumed = true;
      state.prompted = false;
      refreshUi();
      setStatus(window.CLIENT_LANG?.draftRestoredStatus || '', 'success');
      emitStateChange();
      Toast.success(window.CLIENT_LANG?.draftRestoredTitle || '', window.CLIENT_LANG?.draftRestoredHelp || '');
    }

    async function discard() {
      const draftId = Number(state.draft?.id || draftInput.value || 0);
      if (draftId > 0) {
        await Http.delete(settings.deleteUrlBase + '/' + draftId);
      }

      state.skipExitPersist = true;
      resetLocal();
      setStatus(window.CLIENT_LANG?.draftDeletedStatus || '', 'muted');
      Toast.success(window.CLIENT_LANG?.draftRestoredTitle || '', window.CLIENT_LANG?.draftDeletedHelp || '');
    }

    function resetLocal() {
      clearTimeout(state.timer);
      state.draft = null;
      state.resumed = false;
      state.prompted = false;
      draftInput.value = '';
      refreshUi();
      emitStateChange();
    }

    function complete() {
      state.skipExitPersist = true;
      resetLocal();
    }

    function buildPayload() {
      const rawData = collectRawData();
      const normalizedData = normalizeDraftValue(rawData) || {};
      if (!hasMeaningfulValue(normalizedData)) {
        return null;
      }

      const changedData = extractChangedDraftData(normalizedData, state.initialData) || {};
      if (!hasMeaningfulValue(changedData)) {
        return null;
      }

      if (typeof settings.isMeaningfulData === 'function') {
        const isMeaningful = settings.isMeaningfulData(changedData, form, api, {
          rawData,
          normalizedData,
          initialData: state.initialData,
        });

        if (isMeaningful === false) {
          return null;
        }
      }

      return {
        type: resolveType(),
        route: resolveRoute(),
        data: changedData,
      };
    }

    function flushPendingSave() {
      clearTimeout(state.timer);
      state.timer = null;

      if (state.restoring || state.skipExitPersist) return;
      if (typeof settings.shouldSave === 'function' && settings.shouldSave() === false) return;

      const payload = buildPayload();
      if (!payload) return;

      try {
        fetch(settings.saveUrl, {
          method: 'POST',
          credentials: 'same-origin',
          keepalive: true,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(payload),
        });
      } catch (e) {
        // Ignore unload persistence failures.
      }
    }

    function promptResumeIfAvailable() {
      if (!state.draft || state.resumed || state.prompted) return;

      state.prompted = true;

      if (!window.Modal || typeof window.Modal.confirm !== 'function') {
        resume();
        return;
      }

      Modal.confirm({
        title: window.CLIENT_LANG?.draftAvailableTitle || '',
        message: (window.CLIENT_LANG?.draftAvailableMessage || ':label').replace(':label', resolveLabel()),
        confirmText: window.CLIENT_LANG?.draftResume || '',
        cancelText: window.CLIENT_LANG?.draftCancelDelete || '',
        type: 'info',
        onConfirm: async () => {
          await resume();
        },
        onCancel: async () => {
          await discard();
        },
      });
    }

    function resolveType() {
      return typeof settings.type === 'function' ? settings.type() : settings.type;
    }

    function resolveLabel() {
      return typeof settings.label === 'function'
        ? settings.label()
        : (settings.label || resolveType() || window.CLIENT_LANG?.draftCurrent || '');
    }

    function resolveRoute() {
      if (typeof settings.route === 'function') {
        return settings.route();
      }

      if (typeof settings.route === 'string' && settings.route.trim() !== '') {
        return settings.route.trim();
      }

      return (window.location.pathname || '') + (window.location.search || '');
    }

    function setStatus(message = '', tone = 'muted') {
      if (!ui?.statusEl) return;

      ui.statusEl.textContent = message;
      ui.statusEl.style.color = ({
        muted: 'var(--c-ink-40)',
        success: 'var(--c-success)',
        danger: 'var(--c-danger)',
      })[tone] || 'var(--c-ink-40)';
    }

    function refreshUi() {
      if (!ui) return;

      const hasDraft = !!state.draft;
      ui.resumeBtn.style.display = hasDraft && !state.resumed ? '' : 'none';
      ui.discardBtn.style.display = hasDraft ? '' : 'none';
      ui.labelEl.textContent = hasDraft
        ? (window.CLIENT_LANG?.draftAvailableLabel || ':label').replace(':label', resolveLabel())
        : (window.CLIENT_LANG?.draftAutoSave || '');
    }

    function emitStateChange() {
      if (typeof settings.onStateChange === 'function') {
        settings.onStateChange({
          draft: state.draft,
          hasDraft: !!state.draft,
          resumed: state.resumed,
        }, api);
      }
    }
  }

  function ensureDraftInput(form) {
    let input = form.querySelector('input[name="draft_id"]');
    if (input) return input;

    input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'draft_id';
    form.appendChild(input);

    return input;
  }

  function ensureDraftUi(form) {
    let root = form.querySelector('[data-draft-ui]');
    if (!root) {
      root = document.createElement('div');
      root.setAttribute('data-draft-ui', '1');
      root.style.display = 'flex';
      root.style.alignItems = 'center';
      root.style.justifyContent = 'space-between';
      root.style.gap = '12px';
      root.style.padding = '12px 14px';
      root.style.marginBottom = '16px';
      root.style.border = '1px solid var(--c-ink-10)';
      root.style.borderRadius = '12px';
      root.style.background = 'rgba(15, 23, 42, 0.03)';
      root.innerHTML = ''
        + '<div style="display:flex;flex-direction:column;gap:4px;">'
        + '  <strong data-draft-label style="font-size:13px;color:var(--c-ink);">' + (window.CLIENT_LANG?.draftAutoSave || '') + '</strong>'
        + '  <span data-draft-status style="font-size:12px;color:var(--c-ink-40);"></span>'
        + '</div>'
        + '<div style="display:flex;align-items:center;gap:8px;">'
        + '  <button type="button" class="btn btn-secondary btn-sm" data-draft-resume style="display:none;">' + (window.CLIENT_LANG?.draftResumeButton || '') + '</button>'
        + '  <button type="button" class="btn btn-ghost btn-sm" data-draft-discard style="display:none;">' + (window.CLIENT_LANG?.deleteAction || '') + '</button>'
        + '</div>';
      form.insertBefore(root, form.firstChild);
    }

    return {
      root,
      labelEl: root.querySelector('[data-draft-label]'),
      statusEl: root.querySelector('[data-draft-status]'),
      resumeBtn: root.querySelector('[data-draft-resume]'),
      discardBtn: root.querySelector('[data-draft-discard]'),
    };
  }

  function serializeForm(form) {
    const data = {};
    const radiosHandled = new Set();

    Array.from(form.elements || []).forEach((field) => {
      if (!field || !field.name || field.disabled) return;
      if (['_token', '_method', 'draft_id'].includes(field.name)) return;

      const tag = String(field.tagName || '').toLowerCase();
      const type = String(field.type || '').toLowerCase();
      if (type === 'file') return;

      if (type === 'radio') {
        if (radiosHandled.has(field.name)) return;
        radiosHandled.add(field.name);
        const checked = form.querySelector('input[type="radio"][name="' + escapeSelector(field.name) + '"]:checked');
        data[field.name] = checked ? checked.value : '';
        return;
      }

      if (type === 'checkbox') {
        if (field.name.endsWith('[]')) {
          if (!Array.isArray(data[field.name])) data[field.name] = [];
          if (field.checked) data[field.name].push(field.value);
          return;
        }

        data[field.name] = field.checked ? (field.value === 'on' ? 1 : field.value) : '';
        return;
      }

      if (tag === 'select' && field.multiple) {
        data[field.name] = Array.from(field.selectedOptions || []).map((option) => option.value);
        return;
      }

      assignSerializedValue(data, field.name, field.value);
    });

    return data;
  }

  function assignSerializedValue(target, name, value) {
    if (!Object.prototype.hasOwnProperty.call(target, name)) {
      target[name] = value;
      return;
    }

    if (!Array.isArray(target[name])) {
      target[name] = [target[name]];
    }

    target[name].push(value);
  }

  function applySerializedData(form, data) {
    if (!data || typeof data !== 'object') return;

    Object.entries(data).forEach(([name, value]) => {
      if (name.startsWith('__')) return;

      const fields = form.querySelectorAll('[name="' + escapeSelector(name) + '"]');
      if (!fields.length) return;

      fields.forEach((field) => {
        const tag = String(field.tagName || '').toLowerCase();
        const type = String(field.type || '').toLowerCase();

        if (type === 'file') return;

        if (type === 'checkbox') {
          if (field.name.endsWith('[]')) {
            const selected = Array.isArray(value) ? value.map(String) : [];
            field.checked = selected.includes(String(field.value));
          } else {
            field.checked = normalizeCheckboxValue(value);
          }
        } else if (type === 'radio') {
          field.checked = String(value ?? '') === String(field.value);
        } else if (tag === 'select' && field.multiple) {
          const selectedValues = Array.isArray(value) ? value.map(String) : [];
          Array.from(field.options || []).forEach((option) => {
            option.selected = selectedValues.includes(String(option.value));
          });
        } else if (typeof value !== 'object' || value === null) {
          field.value = value == null ? '' : String(value);
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });
  }

  function hasMeaningfulValue(value) {
    if (Array.isArray(value)) {
      return value.some((item) => hasMeaningfulValue(item));
    }

    if (value && typeof value === 'object') {
      return Object.values(value).some((item) => hasMeaningfulValue(item));
    }

    if (typeof value === 'number') {
      return !Number.isNaN(value);
    }

    if (typeof value === 'boolean') {
      return value;
    }

    return String(value ?? '').trim() !== '';
  }

  function normalizeDraftValue(value) {
    if (Array.isArray(value)) {
      const items = value
        .map((item) => normalizeDraftValue(item))
        .filter((item) => item !== undefined);

      return items.length ? items : undefined;
    }

    if (value && typeof value === 'object') {
      const entries = Object.entries(value).reduce((carry, [key, item]) => {
        const normalized = normalizeDraftValue(item);
        if (normalized !== undefined) {
          carry[key] = normalized;
        }
        return carry;
      }, {});

      return Object.keys(entries).length ? entries : undefined;
    }

    if (value == null) {
      return undefined;
    }

    if (typeof value === 'string') {
      const normalized = value.trim();
      return normalized === '' ? undefined : normalized;
    }

    if (typeof value === 'number') {
      return Number.isNaN(value) ? undefined : value;
    }

    if (typeof value === 'boolean') {
      return value ? true : undefined;
    }

    return value;
  }

  function extractChangedDraftData(currentValue, initialValue) {
    if (currentValue === undefined) {
      return undefined;
    }

    if (Array.isArray(currentValue)) {
      return isSameDraftValue(currentValue, initialValue) ? undefined : currentValue;
    }

    if (currentValue && typeof currentValue === 'object') {
      if (!initialValue || Array.isArray(initialValue) || typeof initialValue !== 'object') {
        return currentValue;
      }

      const changedEntries = Object.entries(currentValue).reduce((carry, [key, value]) => {
        const changed = extractChangedDraftData(value, initialValue[key]);
        if (changed !== undefined) {
          carry[key] = changed;
        }
        return carry;
      }, {});

      return Object.keys(changedEntries).length ? changedEntries : undefined;
    }

    return isSameDraftValue(currentValue, initialValue) ? undefined : currentValue;
  }

  function isSameDraftValue(left, right) {
    if (left === right) {
      return true;
    }

    if (Array.isArray(left) || Array.isArray(right)) {
      if (!Array.isArray(left) || !Array.isArray(right) || left.length !== right.length) {
        return false;
      }

      return left.every((item, index) => isSameDraftValue(item, right[index]));
    }

    if (left && right && typeof left === 'object' && typeof right === 'object') {
      const leftKeys = Object.keys(left);
      const rightKeys = Object.keys(right);

      if (leftKeys.length !== rightKeys.length) {
        return false;
      }

      return leftKeys.every((key) => isSameDraftValue(left[key], right[key]));
    }

    return false;
  }

  function normalizeCheckboxValue(value) {
    if (typeof value === 'boolean') return value;
    if (typeof value === 'number') return value === 1;

    const normalized = String(value ?? '').trim().toLowerCase();

    return ['1', 'true', 'yes', 'on'].includes(normalized);
  }

  function escapeSelector(value) {
    if (typeof window.CSS !== 'undefined' && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(value);
    }

    return String(value).replace(/([ #;?%&,.+*~\':"!^$[\]()=>|/@])/g, '\\$1');
  }

  function formatRelativeTime(value) {
    if (!value) return 'a l instant';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'recemment';

    return 'a ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  }

  return { attach };
})();

window.CrmDrafts = CrmDrafts;

/* ============================================================
   AUTOMATION SUGGESTIONS
   ============================================================ */
const AutomationSuggestions = (() => {
  let booted = false;
  let state = {
    items: [],
    redirectUrl: null,
    resolver: null,
    title: '',
    subtitle: '',
    mode: 'default',
    successTitle: '',
    successMessage: '',
  };

  function getModal() {
    return document.getElementById('automationSuggestionsModal');
  }

  function getEls() {
    const modal = getModal();
    if (!modal) return {};

    return {
      modal,
      title: modal.querySelector('[data-automation-title]'),
      subtitle: modal.querySelector('[data-automation-subtitle]'),
      summary: modal.querySelector('.automation-summary'),
      list: modal.querySelector('[data-automation-list]'),
      empty: modal.querySelector('[data-automation-empty]'),
      success: modal.querySelector('[data-automation-success]'),
      successTitle: modal.querySelector('[data-automation-success-title]'),
      successText: modal.querySelector('[data-automation-success-text]'),
      counter: modal.querySelector('[data-automation-count]'),
      acceptAll: modal.querySelector('[data-automation-bulk="accept"]'),
      rejectAll: modal.querySelector('[data-automation-bulk="reject"]'),
      close: modal.querySelector('[data-automation-close]'),
    };
  }

  function esc(value) {
    const node = document.createElement('div');
    node.textContent = value == null ? '' : String(value);
    return node.innerHTML;
  }

  function routes() {
    return window.CRM_AUTOMATION_ROUTES || {};
  }

  function parseResumeIds(value) {
    return String(value || '')
      .split(',')
      .map((item) => Number(String(item).trim()))
      .filter((item) => Number.isInteger(item) && item > 0);
  }

  function clearResumeParams() {
    const url = new URL(window.location.href);
    ['automation_resume', 'automation_suggestion_ids', 'automation_provider', 'automation_resume_state'].forEach((key) => {
      url.searchParams.delete(key);
    });
    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
  }

  function pendingItems() {
    return state.items.filter((item) => item.status === 'pending' && item.is_actionable);
  }

  function findItemById(id) {
    const numericId = Number(id);
    return state.items.find((item) => Number(item.id) === numericId) || null;
  }

  function reserveAutomationTargetWindow(item) {
    if (!item?.integration?.target_blank) {
      return null;
    }

    const reservedWindow = window.open('', '_blank');
    if (!reservedWindow) {
      return null;
    }

    try {
      reservedWindow.opener = null;
      reservedWindow.document.title = item.integration?.label
        ? `Ouverture de ${item.integration.label}...`
        : 'Ouverture...';
      reservedWindow.document.body.innerHTML = `
        <div style="font-family:Inter,Arial,sans-serif;padding:32px;color:#0f172a;background:#f8fafc;">
          <div style="max-width:420px;margin:48px auto;padding:24px;border-radius:18px;background:#ffffff;box-shadow:0 18px 45px rgba(15,23,42,.12);">
            <div style="width:44px;height:44px;border-radius:14px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:16px;">↗</div>
            <h1 style="font-size:18px;line-height:1.4;margin:0 0 8px;">Ouverture en cours...</h1>
            <p style="margin:0;color:#475569;font-size:14px;line-height:1.6;">Nous ouvrons votre suggestion dans une nouvelle fenêtre pour garder le modal disponible.</p>
          </div>
        </div>
      `;
    } catch (_) {
      // Ignore browser-specific limitations; the reserved window is enough.
    }

    return reservedWindow;
  }

  function closeReservedWindow(reservedWindow) {
    if (!reservedWindow || reservedWindow.closed) {
      return;
    }

    try {
      reservedWindow.close();
    } catch (_) {
      // noop
    }
  }

  function openAutomationTarget(targetUrl, targetBlank, reservedWindow = null) {
    if (!targetUrl) return;

    if (targetBlank && reservedWindow && !reservedWindow.closed) {
      try {
        reservedWindow.location.href = targetUrl;
        return;
      } catch (_) {
        closeReservedWindow(reservedWindow);
      }
    }

    if (targetBlank) {
      const opened = window.open(targetUrl, '_blank', 'noopener');
      if (opened) return;
      Toast.warning('Automation', "La nouvelle fenêtre a été bloquée par le navigateur. Autorisez les popups pour ouvrir ce lien sans quitter la page.");
      return;
    }

    window.location.href = targetUrl;
  }

  function updateItems(updatedItems = []) {
    const updates = new Map((updatedItems || []).map((item) => [Number(item.id), item]));
    state.items = state.items.map((item) => updates.get(Number(item.id)) || item);
  }

  function resetState() {
    state = {
      items: [],
      redirectUrl: null,
      resolver: null,
      title: '',
      subtitle: '',
      mode: 'default',
      successTitle: '',
      successMessage: '',
    };
  }

  function resolvedItemIds(updatedItems = []) {
    return (updatedItems || [])
      .filter((item) => ['accepted', 'rejected', 'expired'].includes(String(item.status)))
      .map((item) => Number(item.id))
      .filter((id) => Number.isInteger(id) && id > 0);
  }

  function showSuccessState(title, message) {
    state.mode = 'success';
    state.successTitle = title || window.CLIENT_LANG?.successTitle || 'Success';
    state.successMessage = message || window.CLIENT_LANG?.automationSuccessMessage || '';
    render();
  }

  async function animateCardRemoval(card, delay = 0) {
    if (!card) return;

    await new Promise((resolve) => {
      window.setTimeout(() => {
        const styles = window.getComputedStyle(card);
        card.style.height = `${card.offsetHeight}px`;
        card.style.marginTop = styles.marginTop;
        card.style.marginBottom = styles.marginBottom;
        card.style.paddingTop = styles.paddingTop;
        card.style.paddingBottom = styles.paddingBottom;

        // Force layout so the browser sees the fixed dimensions before animating out.
        void card.offsetHeight;

        card.classList.add('is-removing');

        window.setTimeout(() => {
          card.style.height = '0px';
          card.style.marginTop = '0px';
          card.style.marginBottom = '0px';
          card.style.paddingTop = '0px';
          card.style.paddingBottom = '0px';
          card.style.borderWidth = '0px';

          window.setTimeout(resolve, 240);
        }, 300);
      }, delay);
    });
  }

  async function dismissItems(ids = [], options = {}) {
    const uniqueIds = [...new Set((ids || []).map((id) => Number(id)).filter((id) => Number.isInteger(id) && id > 0))];
    if (!uniqueIds.length) {
      if (options.successState) {
        showSuccessState(options.successState.title, options.successState.message);
      } else {
        render();
      }
      return;
    }

    const { modal, list } = getEls();
    if (list) {
      for (let index = 0; index < uniqueIds.length; index += 1) {
        const card = list.querySelector(`[data-automation-id="${uniqueIds[index]}"]`);
        await animateCardRemoval(card, index * 90);
      }
    }

    state.items = state.items.filter((item) => !uniqueIds.includes(Number(item.id)));

    if (options.closeModal && modal) {
      Modal.close(modal);
      return;
    }

    if (options.successState) {
      showSuccessState(options.successState.title, options.successState.message);
      return;
    }

    render();
  }

  function render() {
    const {
      title,
      subtitle,
      summary,
      list,
      empty,
      success,
      successTitle,
      successText,
      counter,
      acceptAll,
      rejectAll,
      close,
    } = getEls();
    if (!list) return;

    if (title) title.textContent = state.title || 'Suggestions intelligentes';
    if (subtitle) subtitle.textContent = state.subtitle || 'Le CRM vous propose la suite la plus utile.';

    const pendingCount = pendingItems().length;
    const successMode = state.mode === 'success';

    if (counter) {
      counter.textContent = pendingCount > 0
        ? `${pendingCount} suggestion(s) en attente`
        : 'Toutes les suggestions ont été traitées';
    }

    if (summary) summary.style.display = successMode ? 'none' : 'flex';
    if (acceptAll) acceptAll.disabled = pendingCount === 0 || successMode;
    if (rejectAll) rejectAll.disabled = pendingCount === 0 || successMode;
    if (close) {
      close.innerHTML = successMode
        ? '<i class="fas fa-check"></i> Fermer'
        : pendingCount === 0
          ? '<i class="fas fa-check"></i> Continuer'
          : '<i class="fas fa-arrow-right"></i> Continuer';
    }

    if (success) {
      success.classList.toggle('is-visible', successMode);
      success.style.display = successMode ? 'flex' : 'none';
    }
    if (successTitle) {
      successTitle.textContent = state.successTitle || window.CLIENT_LANG?.successTitle || 'Success';
    }
    if (successText) {
      successText.textContent = state.successMessage || window.CLIENT_LANG?.automationSuccessMessage || '';
    }

    if (successMode) {
      list.innerHTML = '';
      list.style.display = 'none';
      if (empty) empty.style.display = 'none';
      return;
    }

    list.style.display = 'flex';

    if (!state.items.length) {
      list.innerHTML = '';
      if (empty) empty.style.display = 'block';
      return;
    }

    if (empty) empty.style.display = 'none';

    list.innerHTML = state.items.map((item) => {
      const actionable = item.status === 'pending' && item.is_actionable;
      const targetUrl = item.integration?.target_url ? esc(item.integration.target_url) : '';
      const targetBlank = item.integration?.target_blank ? ' target="_blank" rel="noopener"' : '';
      const openLink = targetUrl
        ? `<a class="btn btn-secondary btn-sm" href="${targetUrl}"${targetBlank}><i class="fas fa-up-right-from-square"></i> ${esc(window.CLIENT_LANG?.openAction || 'Open')}</a>`
        : '';
      const statusPill = item.status === 'pending'
        ? ''
        : `<span class="automation-status-pill is-${esc(item.status)}">${esc(item.status_label || item.status)}</span>`;
      const inlineActions = actionable
        ? `
            <span class="automation-card-inline-actions">
              <button type="button" class="btn btn-primary btn-sm" data-automation-action="accept" data-id="${item.id}">${esc(window.CLIENT_LANG?.acceptAction || 'Accept')}</button>
              <button type="button" class="btn btn-secondary btn-sm" data-automation-action="reject" data-id="${item.id}">${esc(window.CLIENT_LANG?.cancelText || 'Cancel')}</button>
            </span>
          `
        : '';

      return `
        <article class="automation-card is-${esc(item.status)}" data-automation-id="${item.id}">
          <div class="automation-card-head">
            <div class="automation-card-icon" style="background:${esc(item.theme?.background || 'rgba(37,99,235,.12)')};color:${esc(item.theme?.color || '#2563eb')}">
              <i class="${esc(item.theme?.icon || 'fas fa-wand-magic-sparkles')}"></i>
            </div>
            <div class="automation-card-copy">
              <div class="automation-card-title-row">
                <div class="automation-card-title-main">
                  <h4>${esc(item.label)}</h4>
                  <div class="automation-card-title-actions">
                    ${statusPill}
                    ${inlineActions}
                  </div>
                </div>
              </div>
              <div class="automation-card-meta">
                <span><i class="fas fa-bolt"></i> ${esc(item.integration?.label || 'Automation')}</span>
                <span><i class="fas fa-signal"></i> ${esc(item.confidence_label || 'Pertinent')} (${esc(item.confidence_percent || 0)}%)</span>
                ${item.expires_human ? `<span><i class="fas fa-clock"></i> Expire ${esc(item.expires_human)}</span>` : ''}
              </div>
            </div>
          </div>
          <div class="automation-card-actions">
            ${openLink}
          </div>
        </article>
      `;
    }).join('');
  }

  async function processSingle(id, action, button) {
    const endpointTemplate = action === 'accept' ? routes().accept : routes().reject;
    if (!endpointTemplate) {
      Toast.error('Automation', window.CLIENT_LANG?.automationRoutesUnavailable || 'Automation routes unavailable.');
      return;
    }

    const currentItem = findItemById(id);
    const reservedWindow = action === 'accept'
      ? reserveAutomationTargetWindow(currentItem)
      : null;

    if (button) CrmForm.setLoading(button, true);
    const response = await Http.post(endpointTemplate.replace('__ID__', id), {});
    if (button) CrmForm.setLoading(button, false);

    const payload = response.data?.data || {};
    const eventData = payload?.event || {};
    const updatedSuggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];

    if (updatedSuggestions.length) {
      updateItems(updatedSuggestions);
    }

    if (eventData?.status === 'failed') {
      closeReservedWindow(reservedWindow);
      render();
      handleAutomationFailure(eventData, response.data?.message || 'Cette automation a échoué.');
      return;
    }

    if (!response.ok) {
      closeReservedWindow(reservedWindow);
      render();
      Toast.error('Automation', response.data?.message || window.CLIENT_LANG?.automationActionFailed || 'Automation action failed.');
      return;
    }

    const dismissedIds = updatedSuggestions.length
      ? resolvedItemIds(updatedSuggestions)
      : [Number(id)];
    await dismissItems(dismissedIds.length ? dismissedIds : [Number(id)]);

    const targetUrl = eventData?.target_url || eventData?.response?.target_url || null;
    const targetBlank = Boolean(eventData?.target_blank || eventData?.response?.target_blank);
    if (action === 'accept' && eventData?.status === 'completed' && targetUrl) {
      window.setTimeout(() => {
        openAutomationTarget(targetUrl, targetBlank, reservedWindow);
      }, 160);
      return;
    }

    closeReservedWindow(reservedWindow);
  }

  async function processBulk(action, button) {
    const ids = pendingItems().map((item) => Number(item.id));
    if (!ids.length) return;

    const endpoint = action === 'accept' ? routes().bulkAccept : routes().bulkReject;
    if (!endpoint) {
      Toast.error('Automation', window.CLIENT_LANG?.automationRoutesUnavailable || 'Automation routes unavailable.');
      return;
    }

    if (button) CrmForm.setLoading(button, true);
    const response = await Http.post(endpoint, { ids });
    if (button) CrmForm.setLoading(button, false);

    if (!response.ok) {
      if (Array.isArray(response.data?.data?.suggestions)) {
        updateItems(response.data.data.suggestions);
        render();
      }
      const errors = Array.isArray(response.data?.data?.errors) ? response.data.data.errors : [];
      const reconnectError = errors.find((item) => item?.event?.requires_reconnect && item?.event?.reconnect_url);
      if (reconnectError) {
        handleAutomationFailure(reconnectError.event, reconnectError.message || response.data?.message || 'Certaines automations ont échoué.');
        return;
      }
      Toast.error('Automation', response.data?.message || window.CLIENT_LANG?.automationBulkFailed || 'Bulk processing failed.');
      return;
    }

    const updatedSuggestions = Array.isArray(response.data?.data?.suggestions)
      ? response.data.data.suggestions
      : [];
    updateItems(updatedSuggestions);

    const errorCount = Array.isArray(response.data?.data?.errors) ? response.data.data.errors.length : 0;
    const processedIds = resolvedItemIds(updatedSuggestions).filter((id) => ids.includes(id));

    if (action === 'reject') {
      const shouldClose = errorCount === 0 && processedIds.length === ids.length;
      await dismissItems(processedIds.length ? processedIds : ids, { closeModal: shouldClose });
    } else {
      const allAccepted = errorCount === 0 && processedIds.length === ids.length;
      await dismissItems(processedIds, allAccepted ? {
        successState: {
          title: window.CLIENT_LANG?.successTitle || 'Success',
          message: response.data?.message || window.CLIENT_LANG?.automationSuccessMessage || '',
        },
      } : {});
    }

    if (errorCount > 0) {
      Toast.warning('Automation', (window.CLIENT_LANG?.automationPartialFailed || ':count').replace(':count', errorCount));
      const reconnectError = response.data.data.errors.find((item) => item?.event?.requires_reconnect && item?.event?.reconnect_url);
      if (reconnectError) {
        handleAutomationFailure(reconnectError.event, reconnectError.message || 'Un service externe doit être reconnecté avant de rejouer ces automations.');
      }
    }
  }

  function handleAutomationFailure(eventData, fallbackMessage) {
    const message = eventData?.last_error || fallbackMessage || 'Cette automation a échoué.';
    const reconnectUrl = eventData?.reconnect_url || null;
    const reconnectLabel = eventData?.reconnect_label || 'Reconnecter';
    const reconnectTitle = reconnectLabel;

    if (eventData?.requires_reconnect && reconnectUrl && window.Modal && typeof window.Modal.confirm === 'function') {
      Modal.confirm({
        title: reconnectTitle,
        message,
        confirmText: reconnectLabel,
        cancelText: 'Fermer',
        type: 'warning',
        onConfirm: async () => {
          window.location.href = reconnectUrl;
        },
      });
      return;
    }

    Toast.error('Automation', message);
  }

  function init() {
    if (booted) return;
    const { modal } = getEls();
    if (!modal) return;

    modal.addEventListener('click', async (event) => {
      const singleAction = event.target.closest('[data-automation-action]');
      if (singleAction) {
        event.preventDefault();
        await processSingle(singleAction.dataset.id, singleAction.dataset.automationAction, singleAction);
        return;
      }

      const bulkAction = event.target.closest('[data-automation-bulk]');
      if (bulkAction) {
        event.preventDefault();
        await processBulk(bulkAction.dataset.automationBulk, bulkAction);
        return;
      }

      if (event.target.closest('[data-automation-close]')) {
        event.preventDefault();
        Modal.close(modal);
      }
    });

    modal.addEventListener('crm:modal-close', () => {
      const resolver = state.resolver;
      const redirectUrl = state.redirectUrl;
      resetState();
      if (typeof resolver === 'function') {
        resolver({ redirectUrl });
      }
    });

    booted = true;
  }

  async function resumeFromQuery() {
    init();

    const params = new URLSearchParams(window.location.search);
    if (params.get('automation_resume') !== '1') {
      return;
    }

    const ids = parseResumeIds(params.get('automation_suggestion_ids'));
    const providerSlug = params.get('automation_provider') || '';
    const resumeState = params.get('automation_resume_state') || 'reconnected';
    clearResumeParams();

    if (!ids.length || !routes().list) {
      return;
    }

    const response = await Http.get(routes().list, {
      ids,
      status: 'pending',
      limit: Math.max(ids.length, 1),
    });

    if (!response.ok) {
      Toast.error('Automation', response.data?.message || window.CLIENT_LANG?.automationReloadFailed || 'Unable to reload pending suggestion.');
      return;
    }

    const suggestions = Array.isArray(response.data?.data?.suggestions)
      ? response.data.data.suggestions.filter((item) => ids.includes(Number(item.id)))
      : [];

    if (!suggestions.length) {
      Toast.info('Automation', "Cette suggestion n'est plus en attente.");
      return;
    }

    const providerLabel = suggestions[0]?.integration?.label
      || providerSlug.replace(/-/g, ' ')
      || 'ce service';

    const subtitle = resumeState === 'pending_reconnect'
      ? `${providerLabel} doit etre reconnecte pour rejouer cette suggestion. Vous pouvez aussi relancer l'action apres correction.`
      : `${providerLabel} est reconnecte. Vous pouvez maintenant relancer cette suggestion.`;

    open({
      should_prompt: true,
      title: resumeState === 'pending_reconnect'
        ? 'Suggestion en attente de reconnexion'
        : 'Suggestion en attente a reprendre',
      subtitle,
      suggestions,
    });
  }

  function open(payload, options = {}) {
    init();
    const { modal } = getEls();
    if (!modal || !payload || !payload.should_prompt || !Array.isArray(payload.suggestions) || !payload.suggestions.length) {
      return null;
    }

    state = {
      items: payload.suggestions.slice(),
      redirectUrl: options.redirectUrl || null,
      resolver: null,
      title: payload.title || 'Suggestions intelligentes',
      subtitle: payload.subtitle || 'Le CRM vous propose les prochaines actions utiles.',
      mode: 'default',
      successTitle: '',
      successMessage: '',
    };

    render();
    Modal.open(modal);

    return new Promise((resolve) => {
      state.resolver = resolve;
    });
  }

  return { open, render, resumeFromQuery };
})();

window.AutomationSuggestions = AutomationSuggestions;
window.setTimeout(() => {
  if (window.AutomationSuggestions?.resumeFromQuery) {
    window.AutomationSuggestions.resumeFromQuery();
  }
}, 0);

/* ============================================================
   TAGS INPUT
   ============================================================ */

function initTagsInput(inputId, hiddenName) {
  const container = typeof inputId === 'string'
    ? (document.getElementById(inputId) || document.getElementById(inputId + '_wrap'))
    : inputId;
  if (!container) return null;
  const textInput = container.querySelector('.tags-input');
  const tags = new Set();

  function addTag(val) {
    val = String(val || '').trim().toLowerCase();
    if (!val || tags.has(val)) return;
    tags.add(val);
    const chip = document.createElement('span');
    chip.className = 'tag-chip';
    chip.innerHTML = val + '<button type="button" aria-label="Retirer">x</button>'; 
    chip.querySelector('button').addEventListener('click', () => {
      tags.delete(val);
      chip.remove();
      syncHidden();
    });
    container.insertBefore(chip, textInput);
    syncHidden();
  }

  function syncHidden() {
    container.querySelectorAll('input[name="' + hiddenName + '[]"]').forEach((input) => input.remove());
    tags.forEach((tag) => {
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = hiddenName + '[]';
      hidden.value = tag;
      container.appendChild(hidden);
    });
  }

  function clearTags() {
    tags.clear();
    container.querySelectorAll('.tag-chip').forEach((chip) => chip.remove());
    syncHidden();
  }

  function setTags(values) {
    clearTags();
    (Array.isArray(values) ? values : []).forEach((value) => addTag(value));
  }

  textInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      addTag(textInput.value);
      textInput.value = '';
    }
  });
  container.addEventListener('click', () => textInput?.focus());

  const key = container.id || hiddenName;
  window.CrmTagsInputs = window.CrmTagsInputs || {};
  window.CrmTagsInputs[key] = {
    addTag,
    getTags: () => Array.from(tags),
    setTags,
    clearTags,
  };

  return window.CrmTagsInputs[key];
}

/* ============================================================
   DROPDOWN TOGGLE
   ============================================================ */
document.addEventListener('click', (e) => {
  if (e.target.closest('[data-dropdown-toggle]')) {
    const target = e.target.closest('[data-dropdown-toggle]').closest('.dropdown');
    target?.classList.toggle('open');
    return;
  }
  document.querySelectorAll('.dropdown.open').forEach(d => {
    if (!d.contains(e.target)) d.classList.remove('open');
  });
});

/* ============================================================
   AUTO-INIT on DOMContentLoaded
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  RequestProgress.init();

  // Mark current nav link as active
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar-nav a').forEach(link => {
    if (link.getAttribute('href') === currentPath || currentPath.startsWith(link.getAttribute('href') + '/')) {
      link.classList.add('active');
    }
  });

  // Init tags inputs
  document.querySelectorAll('[data-tags-input]').forEach(el => {
    initTagsInput(el, el.dataset.tagsInput);
  });

  // Mobile sidebar toggle
  const sidebar = document.querySelector('.crm-sidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarCompactToggle = document.getElementById('sidebarCompactToggle');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const sidebarCompactStorageKey = 'crm.sidebar.compact';
  const closeSidebar = () => sidebar?.classList.remove('open');
  const readSidebarCompactPreference = () => {
    try {
      const value = window.localStorage.getItem(sidebarCompactStorageKey);
      // Par défaut (aucune préférence enregistrée) : menu réduit.
      return value === null ? true : value === '1';
    } catch (_) {
      return true;
    }
  };
  const writeSidebarCompactPreference = (value) => {
    try {
      window.localStorage.setItem(sidebarCompactStorageKey, value ? '1' : '0');
    } catch (_) {
      // noop
    }
  };
  let sidebarCompactPreferred = readSidebarCompactPreference();
  const applySidebarCompactState = () => {
    const enabled = !!sidebarCompactPreferred && window.innerWidth > 1024;
    document.body.classList.toggle('sidebar-collapsed', enabled);

    if (!sidebarCompactToggle) {
      return;
    }

    sidebarCompactToggle.classList.toggle('is-active', enabled);
    sidebarCompactToggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    const sidebarCompactIcon = sidebarCompactToggle.querySelector('i');
    if (sidebarCompactIcon) {
      sidebarCompactIcon.className = `fas ${enabled ? 'fa-arrow-right' : 'fa-arrow-left'}`;
    }

    const label = enabled
      ? 'Réafficher les libellés du menu'
      : 'Afficher le menu en mode icônes';

    sidebarCompactToggle.setAttribute('aria-label', label);
  };

  applySidebarCompactState();

  sidebarToggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
  });

  sidebarCompactToggle?.addEventListener('click', () => {
    sidebarCompactPreferred = !sidebarCompactPreferred;
    writeSidebarCompactPreference(sidebarCompactPreferred);
    applySidebarCompactState();
  });

  sidebarBackdrop?.addEventListener('click', closeSidebar);

  document.querySelectorAll('.sidebar-nav a').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 1024) {
        closeSidebar();
      }
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeSidebar();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
      closeSidebar();
    }
    applySidebarCompactState();
  });

  const sidebarNav = document.querySelector('.sidebar-nav');
  if (sidebarNav) {
    let scrollHideTimer = null;
    sidebarNav.addEventListener('scroll', () => {
      sidebarNav.classList.add('is-scrolling');
      clearTimeout(scrollHideTimer);
      scrollHideTimer = setTimeout(() => {
        sidebarNav.classList.remove('is-scrolling');
      }, 700);
    }, { passive: true });
  }
});

}
