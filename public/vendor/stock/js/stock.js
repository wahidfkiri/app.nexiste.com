'use strict';

window.Stock = {
  formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('fr-FR');
  },

  formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString('fr-FR', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  },

  async loadStats(url) {
    const { ok, data } = await Http.get(url);
    if (!ok || !data.data) return;

    const s = data.data;
    const set = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    };

    set('kpiArticles', s.articles_total ?? 0);
    set('kpiLowStock', s.articles_low_stock ?? 0);
    set('kpiSuppliers', s.suppliers_total ?? 0);
    set('kpiOrders', s.orders_total ?? 0);
    set('kpiDeliveryNotes', s.delivery_notes_total ?? 0);
    set('kpiMovements', s.movements_total ?? 0);
  },

  initCrudTable(opts) {
    const table = new CrmTable({
      tbodyId: opts.tbodyId,
      dataUrl: opts.dataUrl,
      perPage: 15,
      renderRow: opts.renderRow,
    });
    window._stockTable = table;
  },

  bindAjaxForm(formId) {
    ajaxForm(formId);
  },

  addOrderLine(containerId) {
    const tbody = document.getElementById(containerId);
    if (!tbody) return;
    const idx = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><select name="items[${idx}][article_id]" class="form-control" onchange="Stock.fillOrderLineFromArticle(this)">${window.StockArticleOptionsHtml || '<option value="">-</option>'}</select></td>
      <td><input type="text" name="items[${idx}][name]" class="form-control" required></td>
      <td><input type="number" name="items[${idx}][quantity]" class="form-control" min="0.0001" step="any" value="1" required></td>
      <td><input type="text" name="items[${idx}][unit]" class="form-control" value="${window.StockLang?.unitPiece || ''}"></td>
      <td><input type="number" name="items[${idx}][unit_price]" class="form-control" min="0" step="any" value="0" required></td>
      <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
  },

  addDeliveryLine(containerId) {
    const tbody = document.getElementById(containerId);
    if (!tbody) return;
    const idx = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><select name="items[${idx}][article_id]" class="form-control" onchange="Stock.fillDeliveryLineFromArticle(this)">${window.StockArticleOptionsHtml || '<option value="">-</option>'}</select></td>
      <td><input type="text" name="items[${idx}][sku]" class="form-control" placeholder="SKU"></td>
      <td><input type="text" name="items[${idx}][name]" class="form-control" required></td>
      <td><input type="number" name="items[${idx}][quantity]" class="form-control" min="0.0001" step="any" value="1" required></td>
      <td><input type="text" name="items[${idx}][unit]" class="form-control" value="${window.StockLang?.unitPiece || ''}"></td>
      <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
  },

  fillOrderLineFromArticle(selectEl) {
    const option = selectEl.selectedOptions[0];
    if (!option) return;
    const tr = selectEl.closest('tr');
    const nameInput = tr.querySelector('[name$="[name]"]');
    const unitInput = tr.querySelector('[name$="[unit]"]');
    const priceInput = tr.querySelector('[name$="[unit_price]"]');
    if (nameInput && !nameInput.value) nameInput.value = option.dataset.name || option.textContent;
    if (unitInput && option.dataset.unit) unitInput.value = option.dataset.unit;
    if (priceInput && option.dataset.purchasePrice) priceInput.value = option.dataset.purchasePrice;
  },

  fillDeliveryLineFromArticle(selectEl) {
    const option = selectEl.selectedOptions[0];
    if (!option) return;
    const tr = selectEl.closest('tr');
    const skuInput = tr.querySelector('[name$="[sku]"]');
    const nameInput = tr.querySelector('[name$="[name]"]');
    const unitInput = tr.querySelector('[name$="[unit]"]');
    if (skuInput && !skuInput.value) skuInput.value = option.dataset.sku || '';
    if (nameInput && !nameInput.value) nameInput.value = option.dataset.name || option.textContent;
    if (unitInput && option.dataset.unit) unitInput.value = option.dataset.unit;
  },

  toggleDeliveryType(type) {
    const supplierWrap = document.getElementById('deliverySupplierWrap');
    const clientWrap = document.getElementById('deliveryClientWrap');
    const supplierInput = supplierWrap?.querySelector('select');
    const clientInput = clientWrap?.querySelector('select');

    if (type === 'in') {
      if (supplierWrap) supplierWrap.style.display = '';
      if (clientWrap) clientWrap.style.display = 'none';
      if (supplierInput) supplierInput.disabled = false;
      if (clientInput) clientInput.disabled = true;
    } else {
      if (supplierWrap) supplierWrap.style.display = 'none';
      if (clientWrap) clientWrap.style.display = '';
      if (supplierInput) supplierInput.disabled = true;
      if (clientInput) clientInput.disabled = false;
    }
  },
};
