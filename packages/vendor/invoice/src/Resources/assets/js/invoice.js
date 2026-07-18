/**
 * CRM SaaS — Module Facturation
 * InvTable, InvLineItems, InvClientSearch — cohérent avec crm.js
 */
'use strict';

const _defaultInvoiceLang = {
  successTitle: 'Success',
  errorTitle: 'Error',
  warningTitle: 'Warning',
  validationTitle: 'Form',
  loadError: 'Unable to load data.',
  emptyDefaultLabel: 'item',
  emptyInvoiceLabel: 'invoice',
  emptyQuoteLabel: 'quote',
  emptyPaymentLabel: 'payment',
  emptyTitleTemplate: 'No :label found',
  emptyHelp: '',
  paidLockTitle: '',
  referencePrefix: 'Ref.:',
  settledLabel: 'Settled',
  convertedBadge: 'Converted',
  viewInvoiceTitle: 'View invoice',
  paginationInfo: 'Showing :from to :to of :total result(s)',
  countLabel: ':total result(s)',
  irreversibleAction: '',
  paymentRecalculation: '',
  lineDescriptionPlaceholder: '',
  optionalReferencePlaceholder: '',
  unitPlaceholder: '',
  minOneLine: '',
  deleteConfirmText: 'Delete',
  invoiceDeleteTitle: 'Delete invoice :number?',
  paymentDeleteTitle: 'Delete this payment?',
};

const InvoiceLang = new Proxy({}, {
  get(_, key) {
    const val = (window.InvoiceLang && window.InvoiceLang[key] !== undefined)
      ? window.InvoiceLang[key]
      : _defaultInvoiceLang[key];
    return val !== undefined ? val : '';
  }
});

function invoiceLang(key, replacements = {}) {
  let value = InvoiceLang[key] ?? '';
  Object.entries(replacements).forEach(([placeholder, replacement]) => {
    value = value.replaceAll(`:${placeholder}`, String(replacement));
  });
  return value;
}

function invoiceRoute(name, replacements = {}) {
  let template = window.INVOICE_ROUTES?.[name] || '#';
  Object.entries(replacements).forEach(([key, value]) => {
    template = template.replace(`__${key.toUpperCase()}__`, encodeURIComponent(String(value)));
  });
  return template;
}

/* ============================================================
   InvTable — Table manager (identique CrmTable mais pour factures/devis)
   ============================================================ */
class InvTable {
  constructor(options) {
    this.options = Object.assign({
      tbodyId:  'invoicesTableBody',
      dataUrl:  null,
      statsUrl: null,
      perPage:  15,
      mode:     'invoice',    // 'invoice' | 'quote' | 'payment'
      countEl:  'invCount',
      statsMap: { total:'statTotal', paid:'statPaid', overdue:'statOverdue', revenue:'statRevenue', due:'statDue' },
    }, options);

    this.state = { page: 1, search: '', filters: {}, sort: '', dir: 'desc', loading: false };
    this.selectedIds = new Set();
    this._debounce   = null;

    this._bindEvents();
    this.load();
    if (this.options.statsUrl) this.loadStats();
  }

  _bindEvents() {
    // Search
    document.getElementById('searchInput')?.addEventListener('input', () => {
      clearTimeout(this._debounce);
      this._debounce = setTimeout(() => {
        this.state.search = document.getElementById('searchInput').value.trim();
        this.state.page = 1;
        this.load();
      }, 350);
    });

    // Filters
    document.querySelectorAll('[data-filter]').forEach(el => {
      el.addEventListener('change', () => {
        this.state.filters[el.dataset.filter] = el.value;
        this.state.page = 1;
        this.load();
      });
      el.addEventListener('input', () => {
        this.state.filters[el.dataset.filter] = el.value;
        this.state.page = 1;
        this.load();
      });
    });

    // Reset
    document.getElementById('resetFilters')?.addEventListener('click', () => {
      this.state.search = ''; this.state.filters = {};
      document.getElementById('searchInput') && (document.getElementById('searchInput').value = '');
      document.querySelectorAll('[data-filter]').forEach(s => s.value = '');
      this.state.page = 1; this.load();
    });

    // Select all
    document.getElementById('selectAll')?.addEventListener('change', (e) => {
      document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = e.target.checked;
        e.target.checked ? this.selectedIds.add(+cb.dataset.id) : this.selectedIds.delete(+cb.dataset.id);
      });
      this._updateBulkBar();
    });

    // Tbody delegate
    document.getElementById(this.options.tbodyId)?.addEventListener('change', (e) => {
      if (e.target.classList.contains('row-check')) {
        const id = +e.target.dataset.id;
        e.target.checked ? this.selectedIds.add(id) : this.selectedIds.delete(id);
        this._updateBulkBar();
      }
    });

    // Sort
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

    if (!ok) { Toast.error(InvoiceLang.errorTitle, InvoiceLang.loadError); return; }

    this._renderRows(data.data || []);
    this._renderPagination(data);
    this._updateCount(data.total);
  }

  async loadStats() {
    const { ok, data } = await Http.get(this.options.statsUrl);
    if (!ok || !data.data) return;
    const s = data.data;
    const map = this.options.statsMap;

    if (this.options.mode === 'invoice') {
      this._setStat(map.total,   s.invoices?.total || 0);
      this._setStat(map.paid,    s.invoices?.paid  || 0);
      this._setStat(map.overdue, s.invoices?.overdue || 0);
      this._setStat(map.revenue, this._fmtCur(s.invoices?.paid_total));
      this._setStat(map.due,     this._fmtCur(s.invoices?.due_total));
    } else if (this.options.mode === 'quote') {
      this._setStat(map.total,    s.quotes?.total    || 0);
      this._setStat(map.sent,     s.quotes?.sent     || 0);
      this._setStat(map.accepted, s.quotes?.accepted || 0);
      this._setStat(map.expired,  s.quotes?.expired  || 0);
      const tot = s.quotes?.total || 0;
      const acc = s.quotes?.accepted || 0;
      this._setStat(map.conversion, tot > 0 ? Math.round(acc/tot*100)+'%' : '0%');
    } else if (this.options.mode === 'payment') {
      this._setStat(map.total, this._fmtCur(s.total));
      this._setStat(map.month, this._fmtCur(s.month));
      this._setStat(map.count, s.count || 0);
      this._setStat(map.transfer, this._fmtCur(s.transfer));
    }
  }

  _setStat(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val ?? '\u2014';
  }

  _fmtCur(n) {
    const cur = window.DEFAULT_CURRENCY || 'EUR';
    return new Intl.NumberFormat('fr-FR', { style:'currency', currency: cur, maximumFractionDigits:0 }).format(n || 0);
  }

  _showSkeletons(count = 5) {
    const tbody = document.getElementById(this.options.tbodyId);
    if (!tbody) return;
    const cols = this.options.mode === 'payment' ? 8 : 10;
    tbody.innerHTML = Array.from({ length: count }, () =>
      `<tr>${Array.from({ length: cols }, () => `<td><div class="skeleton" style="height:13px;border-radius:4px;"></div></td>`).join('')}</tr>`
    ).join('');
  }

  _renderRows(rows) {
    const tbody = document.getElementById(this.options.tbodyId);
    if (!tbody) return;

    if (!rows.length) {
      const labels = {
        invoice: InvoiceLang.emptyInvoiceLabel,
        quote: InvoiceLang.emptyQuoteLabel,
        payment: InvoiceLang.emptyPaymentLabel,
      };
      const emptyTitle = invoiceLang('emptyTitleTemplate', {
        label: labels[this.options.mode] || InvoiceLang.emptyDefaultLabel,
      });
      tbody.innerHTML = `
        <tr><td colspan="${this.options.mode === 'payment' ? 8 : 10}">
          <div class="table-empty">
            <div class="table-empty-icon"><i class="fas fa-file-invoice"></i></div>
            <h3>${this._esc(emptyTitle)}</h3>
            <p>${InvoiceLang.emptyHelp}</p>
          </div>
        </td></tr>`;
      return;
    }

    if (this.options.mode === 'invoice')  tbody.innerHTML = rows.map(r => this._rowInvoice(r)).join('');
    if (this.options.mode === 'quote')    tbody.innerHTML = rows.map(r => this._rowQuote(r)).join('');
    if (this.options.mode === 'payment')  tbody.innerHTML = rows.map(r => this._rowPayment(r)).join('');

    // Re-check selected
    tbody.querySelectorAll('.row-check').forEach(cb => {
      cb.checked = this.selectedIds.has(+cb.dataset.id);
    });
  }

  _rowInvoice(inv) {
    const cur = window.DEFAULT_CURRENCY || inv.currency || 'EUR';
    const fmtCur = (n) => InvCurrencyFmt.format(n, cur);
    const statusColors = { draft:'var(--c-ink-20)', sent:'var(--c-info)', viewed:'var(--c-purple)', partial:'var(--c-warning)', paid:'var(--c-success)', overdue:'var(--c-danger)', cancelled:'var(--c-ink-20)', refunded:'var(--c-warning)' };
    const dot = `<span style="width:6px;height:6px;border-radius:50%;background:${statusColors[inv.status]||'var(--c-ink-20)'};display:inline-block;margin-right:5px;"></span>`;
    const isOverdue = inv.is_overdue;
    const isPaid = String(inv.status || '').toLowerCase() === 'paid';
    const paidLockTitle = InvoiceLang.paidLockTitle;
    const showUrl = invoiceRoute('show', { invoice: inv.uuid ?? inv.id });
    const editUrl = invoiceRoute('edit', { invoice: inv.uuid ?? inv.id });
    const pdfUrl = invoiceRoute('pdf', { invoice: inv.uuid ?? inv.id });
    const editAction = isPaid
      ? `<button type="button" class="btn-icon is-disabled" aria-disabled="true" title="${paidLockTitle}"><i class="fas fa-pen"></i></button>`
      : `<a href="${editUrl}" class="btn-icon" title="Modifier"><i class="fas fa-pen"></i></a>`;
    const deleteAction = isPaid
      ? `<button type="button" class="btn-icon danger is-disabled" aria-disabled="true" title="${paidLockTitle}"><i class="fas fa-trash"></i></button>`
      : `<button class="btn-icon danger" onclick="InvTable._deleteInvoice(${inv.id},'${this._esc(inv.number)}')" title="Supprimer"><i class="fas fa-trash"></i></button>`;
    return `
      <tr data-id="${inv.id}" class="${this.selectedIds.has(inv.id) ? 'selected' : ''}">
        <td style="width:40px"><input type="checkbox" class="row-check" data-id="${inv.id}" ${this.selectedIds.has(inv.id)?'checked':''}></td>
        <td>
          <div><a href="${showUrl}" style="color:var(--c-accent);font-weight:var(--fw-semi);text-decoration:none;font-family:var(--ff-display);">${this._esc(inv.number)}</a></div>
          ${inv.reference ? `<div style="font-size:11.5px;color:var(--c-ink-40);">${InvoiceLang.referencePrefix} ${this._esc(inv.reference)}</div>` : ''}
        </td>
        <td>
          <div style="font-weight:var(--fw-medium);">${this._esc(inv.client?.company_name||'—')}</div>
          <div style="font-size:11.5px;color:var(--c-ink-40);">${this._esc(inv.client?.email||'')}</div>
        </td>
        <td style="color:var(--c-ink-60);">${inv.issue_date ? this._fmtDate(inv.issue_date) : '—'}</td>
        <td style="${isOverdue?'color:var(--c-danger);font-weight:var(--fw-medium);':''}">${inv.due_date ? this._fmtDate(inv.due_date) : '—'}</td>
        <td><span style="background:var(--c-ink-02);border:1px solid var(--c-ink-05);border-radius:4px;padding:2px 8px;font-size:11px;font-weight:var(--fw-semi);">${cur}</span></td>
        <td style="text-align:right;font-weight:var(--fw-semi);font-family:var(--ff-display);">${fmtCur(inv.total)}</td>
        <td style="text-align:right;font-weight:var(--fw-semi);font-family:var(--ff-display);${+inv.amount_due>0?'color:var(--c-danger);':'color:var(--c-success);'}">${+inv.amount_due>0?fmtCur(inv.amount_due):InvoiceLang.settledLabel}</td>
        <td><span class="badge badge-${inv.status}">${dot}${this._esc((window.INVOICE_STATUS_LABELS && window.INVOICE_STATUS_LABELS[inv.status]) || inv.status_label || inv.status)}</span></td>
        <td>
          <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
            <a href="${showUrl}" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
            <a href="${pdfUrl}" data-pdf-export class="btn-icon" title="PDF"><i class="fas fa-file-pdf"></i></a>
            ${editAction}
            ${deleteAction}
          </div>
        </td>
      </tr>`;
  }

  _rowQuote(q) {
    const cur = window.DEFAULT_CURRENCY || q.currency || 'EUR';
    const expired = q.is_expired;
    const dot = `<span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;margin-right:5px;opacity:.7;"></span>`;
    const showUrl = invoiceRoute('quoteShow', { quote: q.uuid ?? q.id });
    const editUrl = invoiceRoute('quoteEdit', { quote: q.uuid ?? q.id });
    const pdfUrl = invoiceRoute('quotePdf', { quote: q.uuid ?? q.id });
    return `
      <tr data-id="${q.id}" class="${this.selectedIds.has(q.id)?'selected':''}">
        <td style="width:40px"><input type="checkbox" class="row-check" data-id="${q.id}" ${this.selectedIds.has(q.id)?'checked':''}></td>
        <td>
          <div><a href="${showUrl}" style="color:var(--c-purple);font-weight:var(--fw-semi);text-decoration:none;font-family:var(--ff-display);">${this._esc(q.number)}</a></div>
          ${q.reference ? `<div style="font-size:11.5px;color:var(--c-ink-40);">${InvoiceLang.referencePrefix} ${this._esc(q.reference)}</div>` : ''}
        </td>
        <td>
          <div style="font-weight:var(--fw-medium);">${this._esc(q.client?.company_name||'—')}</div>
          <div style="font-size:11.5px;color:var(--c-ink-40);">${this._esc(q.client?.email||'')}</div>
        </td>
        <td style="color:var(--c-ink-60);">${q.issue_date ? this._fmtDate(q.issue_date) : '—'}</td>
        <td style="${expired?'color:var(--c-danger);font-weight:var(--fw-medium);':''}">${q.valid_until ? this._fmtDate(q.valid_until) : '—'}</td>
        <td><span style="background:var(--c-ink-02);border:1px solid var(--c-ink-05);border-radius:4px;padding:2px 8px;font-size:11px;font-weight:var(--fw-semi);">${cur}</span></td>
        <td style="text-align:right;font-weight:var(--fw-semi);font-family:var(--ff-display);">${InvCurrencyFmt.format(q.total, cur)}</td>
        <td><span class="badge badge-${q.status}">${dot}${this._esc((window.QUOTE_STATUS_LABELS && window.QUOTE_STATUS_LABELS[q.status]) || q.status_label || q.status)}</span>${q.is_converted?`<span class="badge badge-paid" style="margin-left:6px;">${InvoiceLang.convertedBadge}</span>`:''}</td>
        <td>
          <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
            <a href="${showUrl}" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
            ${q.status==='accepted'?`<a href="${pdfUrl}" data-pdf-export class="btn-icon" title="PDF"><i class="fas fa-file-pdf"></i></a>`:''}
            ${!['accepted','declined'].includes(q.status)?`<a href="${editUrl}" class="btn-icon" title="Modifier"><i class="fas fa-pen"></i></a>`:''}
            ${!q.is_converted&&q.status==='sent'?`<button class="btn btn-sm btn-success" onclick="convertQuote(${q.id},'${this._esc(q.number)}')" title="Convertir"><i class="fas fa-arrow-right"></i> FAC</button>`:''}
            <button class="btn-icon danger" onclick="deleteQuote(${q.id})" title="Supprimer"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>`;
  }

  _rowPayment(p) {
    const cur = window.DEFAULT_CURRENCY || p.currency || 'EUR';
    const invoiceUrl = invoiceRoute('show', { invoice: p.invoice_id });
    return `
      <tr data-id="${p.id}">
        <td style="font-weight:var(--fw-medium);color:var(--c-ink-60);">${p.payment_date ? this._fmtDate(p.payment_date) : '—'}</td>
        <td><a href="${invoiceUrl}" style="color:var(--c-accent);font-weight:var(--fw-semi);text-decoration:none;">${this._esc(p.invoice?.number||'—')}</a></td>
        <td>
          <div style="font-weight:var(--fw-medium);">${this._esc(p.invoice?.client?.company_name||'—')}</div>
        </td>
        <td>
          <span style="background:var(--c-ink-02);border:1px solid var(--c-ink-05);border-radius:var(--r-full);padding:3px 10px;font-size:11.5px;font-weight:var(--fw-medium);">
            <i class="fas fa-credit-card" style="color:var(--c-accent);font-size:10px;margin-right:4px;"></i>
            ${this._esc(p.method_label||p.payment_method||'—')}
          </span>
        </td>
        <td style="color:var(--c-ink-60);font-size:13px;">${this._esc(p.reference||'—')}</td>
        <td style="color:var(--c-ink-60);font-size:13px;">${this._esc(p.bank_name||'—')}</td>
        <td style="text-align:right;font-weight:var(--fw-bold);font-family:var(--ff-display);color:var(--c-success);">${InvCurrencyFmt.format(p.amount, cur)}</td>
        <td>
          <div class="row-actions" style="justify-content:flex-end;padding-right:4px;">
            <a href="${invoiceUrl}" class="btn-icon" title="${InvoiceLang.viewInvoiceTitle}"><i class="fas fa-file-invoice"></i></a>
            <button class="btn-icon danger" onclick="InvTable._deletePayment(${p.id})" title="Supprimer"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>`;
  }

  _renderPagination(data) {
    const wrap = document.getElementById('paginationControls');
    const info = document.getElementById('paginationInfo');
    if (!wrap) return;

    const { current_page, last_page, from, to, total } = data;
    if (info) info.textContent = invoiceLang('paginationInfo', { from: from || 0, to: to || 0, total: total || 0 });

    const pages = [];
    for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page || 1, current_page + 2); i++) pages.push(i);

    wrap.innerHTML = `
      <button class="page-btn" ${current_page<=1?'disabled':''} onclick="window._${this.options.mode==='payment'?'pay':'inv'}Table?.goTo(${current_page-1})">
        <i class="fas fa-chevron-left"></i>
      </button>
      ${pages.map(p => `<button class="page-btn ${p===current_page?'active':''}" onclick="window._${this.options.mode==='payment'?'pay':'inv'}Table?.goTo(${p})">${p}</button>`).join('')}
      <button class="page-btn" ${current_page>=last_page?'disabled':''} onclick="window._${this.options.mode==='payment'?'pay':'inv'}Table?.goTo(${current_page+1})">
        <i class="fas fa-chevron-right"></i>
      </button>`;
  }

  goTo(page) { this.state.page = page; this.load(); window.scrollTo({ top: 0, behavior: 'smooth' }); }

  _updateCount(total) {
    const el = document.getElementById(this.options.countEl);
    if (el) el.textContent = invoiceLang('countLabel', { total: total || 0 });
  }

  _updateBulkBar() {
    const bar = document.getElementById('bulkBar');
    if (!bar) return;
    const n = this.selectedIds.size;
    bar.classList.toggle('visible', n > 0);
    const cnt = document.getElementById('selectedCount');
    if (cnt) cnt.textContent = n;
  }

  getSelectedIds() { return [...this.selectedIds]; }

  _fmtDate(str) {
    if (!str) return '—';
    const d = new Date(str);
    return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' });
  }

  _esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

  static _deleteInvoice(id, number) {
    Modal.confirm({
      title: invoiceLang('invoiceDeleteTitle', { number }),
      message: InvoiceLang.irreversibleAction,
      confirmText: InvoiceLang.deleteConfirmText,
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.delete(invoiceRoute('destroy', { invoice: id }));
        if (ok) { Toast.success(InvoiceLang.successTitle, data.message); window._invTable?.load(); }
        else Toast.error(InvoiceLang.errorTitle, data.message);
      }
    });
  }

  static _deletePayment(id) {
    Modal.confirm({
      title: InvoiceLang.paymentDeleteTitle,
      message: InvoiceLang.paymentRecalculation,
      confirmText: InvoiceLang.deleteConfirmText,
      type: 'danger',
      onConfirm: async () => {
        const { ok, data } = await Http.delete(invoiceRoute('paymentsDestroy', { payment: id }));
        if (ok) { Toast.success(InvoiceLang.successTitle, data.message); window._payTable?.load(); }
        else Toast.error(InvoiceLang.errorTitle, data.message);
      }
    });
  }
}

window.InvTable = InvTable;

/* ============================================================
   InvCurrencyFmt
   ============================================================ */
const InvCurrencyFmt = (() => {
  function format(amount, code) {
    const currencies = window.INVOICE_CURRENCIES || {};
    const cfg = currencies[code] || { symbol: code || '€', position: 'after', decimals: 2, thousands: ' ', decimal_sep: ',' };
    const num = parseFloat(amount || 0);
    const fixed = num.toFixed(cfg.decimals);
    const [int, dec] = fixed.split('.');
    const intFmt = int.replace(/\B(?=(\d{3})+(?!\d))/g, cfg.thousands || ' ');
    const formatted = dec !== undefined ? `${intFmt}${cfg.decimal_sep}${dec}` : intFmt;
    return cfg.position === 'before' ? `${cfg.symbol}${formatted}` : `${formatted} ${cfg.symbol}`;
  }
  return { format };
})();
window.InvCurrencyFmt = InvCurrencyFmt;

/* ============================================================
   InvLineItems — Dynamic line items builder
   ============================================================ */
const InvLineItems = (() => {
  let items   = [];
  let counter = 0;
  let currency        = window.DEFAULT_CURRENCY || 'EUR';
  let globalDiscType  = 'none';
  let globalDiscVal   = 0;
  let taxRate         = 20;
  let withholdingRate = 0;

  function init(opts = {}) {
    currency        = opts.currency        || window.DEFAULT_CURRENCY || 'EUR';
    taxRate         = parseFloat(opts.defaultTaxRate || 0);
    withholdingRate = parseFloat(opts.withholdingRate || 0);

    // Load existing items (edit mode)
    if (opts.items?.length) {
      load(opts.items);
    } else {
      clear();
      addLine();
    }
  }

  function addLine() {
    _addFromData({ description:'', quantity:1, unit:'', unit_price:0, discount_type:'none', discount_value:0, tax_rate: taxRate });
  }

  function _addFromData(data) {
    const id = ++counter;
    items.push({ id });
    _renderRow(id, data);
  }

  function _renderRow(id, data = {}) {
    const tbody = document.getElementById('lineItemsBody');
    if (!tbody) return;

    const tr = document.createElement('tr');
    tr.id = `li-${id}`;
    tr.draggable = true;
    tr.innerHTML = `
      <td><span class="drag-handle"><i class="fas fa-grip-vertical"></i></span></td>
      <td>
        <input type="text" name="items[${id}][description]" value="${_esc(data.description||'')}"
          class="form-control" style="font-size:13px;" placeholder="${InvoiceLang.lineDescriptionPlaceholder}" required>
        <input type="text" name="items[${id}][reference]" value="${_esc(data.reference||'')}"
          class="form-control" style="font-size:11.5px;margin-top:4px;padding:6px 10px;" placeholder="${InvoiceLang.optionalReferencePlaceholder}">
      </td>
      <td>
        <input type="number" name="items[${id}][quantity]" value="${data.quantity||1}"
          class="form-control li-qty" style="font-size:13px;" min="0.0001" step="any" required>
      </td>
      <td>
        <input type="text" name="items[${id}][unit]" value="${_esc(data.unit||'')}"
          class="form-control" style="font-size:13px;" placeholder="${InvoiceLang.unitPlaceholder}">
      </td>
      <td>
        <input type="number" name="items[${id}][unit_price]" value="${data.unit_price||0}"
          class="form-control li-price" style="font-size:13px;" min="0" step="any" required>
      </td>
      <td>
        <select name="items[${id}][discount_type]" class="form-control li-disc-type" style="font-size:12.5px;margin-bottom:4px;">
          <option value="none"    ${(data.discount_type||'none')==='none'   ?'selected':''}>Aucune</option>
          <option value="percent" ${data.discount_type==='percent'?'selected':''}>% Pourcent</option>
          <option value="fixed"   ${data.discount_type==='fixed'  ?'selected':''}>Fixe</option>
        </select>
        <input type="number" name="items[${id}][discount_value]" value="${data.discount_value||0}"
          class="form-control li-disc-val" style="font-size:12.5px;" min="0" step="any">
      </td>
      <td>
        <input type="number" name="items[${id}][tax_rate]" value="${data.tax_rate ?? taxRate}"
          class="form-control li-tax" style="font-size:13px;" min="0" max="100" step="any">
      </td>
      <td class="cell-total">
        <span id="li-total-${id}" style="font-family:var(--ff-display);font-weight:var(--fw-semi);">—</span>
      </td>
      <td>
        <button type="button" class="btn-icon danger btn-sm" onclick="InvLineItems.removeLine(${id})">
          <i class="fas fa-times"></i>
        </button>
      </td>`;
    tbody.appendChild(tr);

    // Bind events
    ['li-qty','li-price','li-disc-type','li-disc-val','li-tax'].forEach(cls => {
      tr.querySelector(`.${cls}`)?.addEventListener('input',  () => _calcLine(id));
      tr.querySelector(`.${cls}`)?.addEventListener('change', () => _calcLine(id));
    });

    _calcLine(id);
  }

  function removeLine(id) {
    if (items.length <= 1) { Toast.warning(InvoiceLang.warningTitle, InvoiceLang.minOneLine); return; }
    items = items.filter(i => i.id !== id);
    document.getElementById(`li-${id}`)?.remove();
    recalc();
  }

  function clear() {
    items = [];
    counter = 0;
    const tbody = document.getElementById('lineItemsBody');
    if (tbody) tbody.innerHTML = '';
  }

  function load(rows = []) {
    clear();

    if (Array.isArray(rows) && rows.length) {
      rows.forEach((row) => _addFromData(row));
    } else {
      addLine();
    }

    recalc();
  }

  function getData() {
    return items.map((item) => {
      const tr = document.getElementById(`li-${item.id}`);
      if (!tr) return null;

      return {
        description: tr.querySelector(`[name="items[${item.id}][description]"]`)?.value || '',
        reference: tr.querySelector(`[name="items[${item.id}][reference]"]`)?.value || '',
        quantity: parseFloat(tr.querySelector(`[name="items[${item.id}][quantity]"]`)?.value || 0) || 0,
        unit: tr.querySelector(`[name="items[${item.id}][unit]"]`)?.value || '',
        unit_price: parseFloat(tr.querySelector(`[name="items[${item.id}][unit_price]"]`)?.value || 0) || 0,
        discount_type: tr.querySelector(`[name="items[${item.id}][discount_type]"]`)?.value || 'none',
        discount_value: parseFloat(tr.querySelector(`[name="items[${item.id}][discount_value]"]`)?.value || 0) || 0,
        tax_rate: parseFloat(tr.querySelector(`[name="items[${item.id}][tax_rate]"]`)?.value || taxRate) || taxRate,
      };
    }).filter(Boolean);
  }

  function _calcLine(id) {
    const tr = document.getElementById(`li-${id}`);
    if (!tr) return;
    const qty       = parseFloat(tr.querySelector('.li-qty')?.value   || 0);
    const price     = parseFloat(tr.querySelector('.li-price')?.value  || 0);
    const discType  = tr.querySelector('.li-disc-type')?.value || 'none';
    const discVal   = parseFloat(tr.querySelector('.li-disc-val')?.value || 0);
    const taxR      = parseFloat(tr.querySelector('.li-tax')?.value    || 0);
    const lineTotal = qty * price;
    const discAmt   = discType === 'percent' ? lineTotal*(discVal/100) : discType==='fixed' ? discVal : 0;
    const afterDisc = lineTotal - discAmt;
    const taxAmt    = afterDisc*(taxR/100);
    const total     = afterDisc + taxAmt;
    const el = document.getElementById(`li-total-${id}`);
    if (el) el.textContent = InvCurrencyFmt.format(total, currency);
    recalc();
  }

  function recalc() {
    let subtotal = 0;
    items.forEach(item => {
      const tr = document.getElementById(`li-${item.id}`);
      if (!tr) return;
      const qty      = parseFloat(tr.querySelector('.li-qty')?.value   || 0);
      const price    = parseFloat(tr.querySelector('.li-price')?.value  || 0);
      const discType = tr.querySelector('.li-disc-type')?.value || 'none';
      const discVal  = parseFloat(tr.querySelector('.li-disc-val')?.value || 0);
      const taxR     = parseFloat(tr.querySelector('.li-tax')?.value    || 0);
      const line = qty * price;
      const disc = discType==='percent' ? line*(discVal/100) : discType==='fixed' ? discVal : 0;
      subtotal += line - disc;
    });

    globalDiscType = document.getElementById('discount_type')?.value || 'none';
    globalDiscVal  = parseFloat(document.getElementById('discount_value')?.value || 0);
    taxRate        = parseFloat(document.getElementById('tax_rate')?.value || 0);
    withholdingRate= parseFloat(document.getElementById('withholding_tax_rate')?.value || 0);

    const discAmt   = globalDiscType==='percent' ? subtotal*(globalDiscVal/100) : globalDiscType==='fixed' ? globalDiscVal : 0;
    const afterDisc = subtotal - discAmt;
    const taxAmt    = afterDisc*(taxRate/100);
    const withAmt   = afterDisc*(withholdingRate/100);
    const total     = afterDisc + taxAmt;
    const netAPayer = total - withAmt;

    _setText('tot-subtotal', InvCurrencyFmt.format(subtotal, currency));
    _setText('tot-discount', InvCurrencyFmt.format(discAmt, currency));
    _setText('tot-tax',      InvCurrencyFmt.format(taxAmt, currency));
    _setText('tot-withholding', InvCurrencyFmt.format(withAmt, currency));
    _setText('tot-grand',    InvCurrencyFmt.format(total, currency));
    _setText('tot-net',      InvCurrencyFmt.format(netAPayer, currency));

    const discRow = document.getElementById('tot-discount-row');
    if (discRow) discRow.style.display = discAmt > 0 ? 'flex' : 'none';
    const withRow = document.getElementById('tot-withholding-row');
    if (withRow) withRow.style.display = withAmt > 0 ? 'flex' : 'none';
    const withInfo = document.getElementById('withholding-info');
    if (withInfo) withInfo.style.display = withholdingRate > 0 ? 'flex' : 'none';
  }

  function _setText(id, v) { const el = document.getElementById(id); if (el) el.textContent = v; }
  function _esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

  function setCurrency(code) {
    if (!code) return;
    currency = String(code).toUpperCase();
    recalc();
  }

  return { init, addLine, removeLine, recalc, clear, load, getData, setCurrency };
})();
window.InvLineItems = InvLineItems;

/* ============================================================
   InvClientSearch — Client autocomplete
   ============================================================ */
const InvClientSearch = (() => {
  function init(inputId, hiddenId, opts = {}) {
    const input  = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const sugsEl = document.getElementById(opts.suggestionsEl || 'clientSuggestions');
    if (!input) return;

    let debounceTimer;
    input.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(async () => {
        const q = input.value.trim();
        if (q.length < 2) { if (sugsEl) sugsEl.style.display = 'none'; return; }
        try {
          const { ok, data } = await Http.get(window.CLIENT_ROUTES?.search || '#', { q });
          if (ok && data.data) renderSuggestions(data.data, sugsEl, input, hidden, opts);
        } catch(e) {}
      }, 300);
    });

    document.addEventListener('click', (e) => {
      if (!input.contains(e.target) && sugsEl && !sugsEl.contains(e.target)) {
        if (sugsEl) sugsEl.style.display = 'none';
      }
    });
  }

  function renderSuggestions(clients, sugsEl, input, hidden, opts) {
    if (!sugsEl) return;
    sugsEl.innerHTML = '';
    if (!clients.length) { sugsEl.style.display = 'none'; return; }
    clients.forEach(c => {
      const item = document.createElement('div');
      item.className = 'client-suggestion-item';
      item.innerHTML = `
        <div class="client-avatar-sm">${(c.company_name||'?').substring(0,2).toUpperCase()}</div>
        <div>
          <div style="font-weight:var(--fw-medium);font-size:13px;">${c.company_name}</div>
          <div style="font-size:12px;color:var(--c-ink-40);">${c.email||''}</div>
        </div>`;
      item.addEventListener('click', () => {
        input.value = c.company_name;
        if (hidden) hidden.value = c.id;
        sugsEl.style.display = 'none';
        if (typeof opts.onSelect === 'function') opts.onSelect(c);
      });
      sugsEl.appendChild(item);
    });
    sugsEl.style.display = 'block';
  }

  return { init };
})();
window.InvClientSearch = InvClientSearch;
