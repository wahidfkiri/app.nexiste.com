// ========== CREATE INVOICE PAGE - SCRIPT COMPLET ==========

// Données clients
let clientsList = [
    { id: 1, name: "Sophie Martin", email: "sophie@martin.com", address: "15 rue de Paris, 75001 Paris" },
    { id: 2, name: "Thomas Bernard", email: "thomas@bernard.fr", address: "8 avenue des Champs, 69002 Lyon" },
    { id: 3, name: "NexTech Solutions", email: "contact@nextech.com", address: "45 rue de la République, 13001 Marseille" },
    { id: 4, name: "Marie Lambert", email: "marie@lambert.fr", address: "12 boulevard Victor Hugo, 31000 Toulouse" },
    { id: 5, name: "StartupHub", email: "hello@startuphub.io", address: "7 rue de la Bourse, 33000 Bordeaux" }
];

let invoicesData = JSON.parse(localStorage.getItem('invoicesData')) || [];
let itemCounter = 1;
let currentInvoiceId = null;

// Loader
function showLoader() { document.getElementById('loaderOverlay')?.classList.add('active'); }
function hideLoader() { document.getElementById('loaderOverlay')?.classList.remove('active'); }

// Toast
function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.toast-notification');
    if(existingToast) existingToast.remove();
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 3000);
}

// Generate invoice number
function generateInvoiceNumber() {
    const year = new Date().getFullYear();
    const count = invoicesData.length + 1;
    return `INV-${year}-${String(count).padStart(3, '0')}`;
}

// Load clients into select
function loadClients() {
    const select = document.getElementById('clientSelect');
    if(select) {
        select.innerHTML = '<option value="">Sélectionner un client</option>';
        clientsList.forEach(client => {
            select.innerHTML += `<option value="${client.id}">${client.name}</option>`;
        });
    }
}

// Set default dates
function setDefaultDates() {
    const today = new Date();
    const dueDate = new Date();
    dueDate.setDate(today.getDate() + 30);
    
    document.getElementById('issueDate').valueAsDate = today;
    document.getElementById('dueDate').valueAsDate = dueDate;
    document.getElementById('invoiceNumber').value = generateInvoiceNumber();
}

// Calculate item total
function calculateItemTotal(row) {
    const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
    const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
    const total = qty * price;
    const totalSpan = row.querySelector('.item-total');
    if(totalSpan) totalSpan.textContent = `€${total.toFixed(2)}`;
    return total;
}

// Calculate all totals
function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        subtotal += calculateItemTotal(row);
    });
    
    const taxRate = parseFloat(document.getElementById('taxRate')?.value) || 0;
    const discount = parseFloat(document.getElementById('discount')?.value) || 0;
    const currency = document.getElementById('currency')?.value || '€';
    
    const discountAmount = subtotal * (discount / 100);
    const afterDiscount = subtotal - discountAmount;
    const taxAmount = afterDiscount * (taxRate / 100);
    const grandTotal = afterDiscount + taxAmount;
    
    return { subtotal, discountAmount, taxAmount, grandTotal, currency, taxRate, discount };
}

// Update preview
function updatePreview() {
    const invoiceNumber = document.getElementById('invoiceNumber')?.value || 'INV-001';
    const clientId = parseInt(document.getElementById('clientSelect')?.value);
    const client = clientsList.find(c => c.id === clientId);
    const issueDate = document.getElementById('issueDate')?.value || new Date().toISOString().split('T')[0];
    const dueDate = document.getElementById('dueDate')?.value || '';
    const notes = document.getElementById('notes')?.value || '';
    
    // Get items
    const items = [];
    document.querySelectorAll('.item-row').forEach(row => {
        const desc = row.querySelector('.item-desc')?.value || '';
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        if(desc && qty > 0 && price > 0) {
            items.push({ desc, qty, price, total: qty * price });
        }
    });
    
    const { subtotal, discountAmount, taxAmount, grandTotal, currency, taxRate, discount } = calculateTotals();
    
    const preview = document.getElementById('invoicePreview');
    if(!preview) return;
    
    preview.innerHTML = `
        <div class="preview-invoice">
            <div class="preview-header-invoice">
                <h2>FACTURE</h2>
                <p>${invoiceNumber}</p>
            </div>
            
            <div class="preview-company-info">
                <h4>NexusCRM</h4>
                <p>123 Avenue des Affaires<br>75001 Paris, France<br>contact@nexuscrm.com<br>+33 1 23 45 67 89</p>
            </div>
            
            <div class="preview-client-info">
                <h4>Facturé à :</h4>
                <p>${client ? client.name : 'Client non sélectionné'}<br>${client ? client.email : ''}<br>${client ? client.address : ''}</p>
            </div>
            
            <div class="preview-dates">
                <span><strong>Date d'émission :</strong> ${issueDate}</span>
                <span><strong>Date d'échéance :</strong> ${dueDate}</span>
            </div>
            
            <table class="preview-items-table">
                <thead>
                    <tr><th>Description</th><th>Qté</th><th>Prix unit.</th><th>Total</th></tr>
                </thead>
                <tbody>
                    ${items.length > 0 ? items.map(item => `
                        <tr>
                            <td>${item.desc}</td>
                            <td>${item.qty}</td>
                            <td>${currency}${item.price.toFixed(2)}</td>
                            <td>${currency}${item.total.toFixed(2)}</td>
                        </tr>
                    `).join('') : '<tr><td colspan="4" style="text-align:center;">Aucun article</td></tr>'}
                </tbody>
            </table>
            
            <div class="preview-totals">
                <div>Sous-total : ${currency}${subtotal.toFixed(2)}</div>
                ${discount > 0 ? `<div>Remise (${discount}%) : -${currency}${discountAmount.toFixed(2)}</div>` : ''}
                ${taxRate > 0 ? `<div>TVA (${taxRate}%) : ${currency}${taxAmount.toFixed(2)}</div>` : ''}
                <div class="grand-total">Total : ${currency}${grandTotal.toFixed(2)}</div>
            </div>
            
            ${notes ? `<div class="preview-notes"><strong>Notes :</strong> ${notes}</div>` : ''}
        </div>
    `;
}

// Add new item row
function addItemRow() {
    const tbody = document.getElementById('itemsContainer');
    const rowCount = document.querySelectorAll('.item-row').length;
    const newRow = document.createElement('tr');
    newRow.className = 'item-row';
    newRow.innerHTML = `
        <td><input type="text" class="form-control-modern item-desc" placeholder="Description du produit/service"></td>
        <td><input type="number" class="form-control-modern item-qty" value="1" min="1"></td>
        <td><input type="number" class="form-control-modern item-price" value="0" step="0.01"></td>
        <td><span class="item-total">€0.00</span></td>
        <td><button type="button" class="remove-item-btn"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(newRow);
    
    // Add event listeners to new row
    const qtyInput = newRow.querySelector('.item-qty');
    const priceInput = newRow.querySelector('.item-price');
    const removeBtn = newRow.querySelector('.remove-item-btn');
    
    qtyInput.addEventListener('input', () => { calculateItemTotal(newRow); updatePreview(); });
    priceInput.addEventListener('input', () => { calculateItemTotal(newRow); updatePreview(); });
    removeBtn.addEventListener('click', () => { newRow.remove(); updatePreview(); });
    
    updatePreview();
}

// Save invoice
function saveInvoice() {
    const invoiceNumber = document.getElementById('invoiceNumber')?.value;
    const clientId = parseInt(document.getElementById('clientSelect')?.value);
    const client = clientsList.find(c => c.id === clientId);
    const issueDate = document.getElementById('issueDate')?.value;
    const dueDate = document.getElementById('dueDate')?.value;
    const notes = document.getElementById('notes')?.value;
    const taxRate = parseFloat(document.getElementById('taxRate')?.value);
    const discount = parseFloat(document.getElementById('discount')?.value);
    const currency = document.getElementById('currency')?.value;
    
    // Get items
    const items = [];
    let itemValid = false;
    document.querySelectorAll('.item-row').forEach(row => {
        const desc = row.querySelector('.item-desc')?.value;
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        if(desc && qty > 0 && price > 0) {
            items.push({ desc, qty, price, total: qty * price });
            itemValid = true;
        }
    });
    
    if(!clientId) {
        showToast('Veuillez sélectionner un client', 'error');
        return;
    }
    if(!itemValid) {
        showToast('Veuillez ajouter au moins un article valide', 'error');
        return;
    }
    if(!issueDate || !dueDate) {
        showToast('Veuillez remplir les dates', 'error');
        return;
    }
    
    const { subtotal, taxAmount, grandTotal } = calculateTotals();
    
    const newInvoice = {
        id: Date.now(),
        number: invoiceNumber,
        clientId: clientId,
        clientName: client.name,
        date: issueDate,
        dueDate: dueDate,
        amount: grandTotal,
        subtotal: subtotal,
        tax: taxAmount,
        taxRate: taxRate,
        discount: discount,
        currency: currency,
        items: items,
        notes: notes,
        status: 'pending',
        createdAt: new Date().toISOString()
    };
    
    invoicesData.push(newInvoice);
    localStorage.setItem('invoicesData', JSON.stringify(invoicesData));
    
    currentInvoiceId = newInvoice.id;
    showSuccessModal();
}

// Show success modal
function showSuccessModal() {
    const modal = document.getElementById('successModal');
    if(modal) modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    if(modal) modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Reset form
function resetForm() {
    document.getElementById('invoiceForm')?.reset();
    document.getElementById('invoiceNumber').value = generateInvoiceNumber();
    setDefaultDates();
    
    // Reset items
    const container = document.getElementById('itemsContainer');
    container.innerHTML = `
        <tr class="item-row">
            <td><input type="text" class="form-control-modern item-desc" placeholder="Description du produit/service"></td>
            <td><input type="number" class="form-control-modern item-qty" value="1" min="1"></td>
            <td><input type="number" class="form-control-modern item-price" value="0" step="0.01"></td>
            <td><span class="item-total">€0.00</span></td>
            <td><button type="button" class="remove-item-btn" disabled><i class="fas fa-trash"></i></button></td>
        </tr>
    `;
    
    document.getElementById('taxRate').value = '20';
    document.getElementById('discount').value = '0';
    document.getElementById('currency').value = '€';
    document.getElementById('notes').value = '';
    
    updatePreview();
}

// Event listeners
document.getElementById('addItemBtn')?.addEventListener('click', addItemRow);
document.getElementById('refreshPreviewBtn')?.addEventListener('click', updatePreview);
document.getElementById('saveInvoiceBtn')?.addEventListener('click', saveInvoice);
document.getElementById('cancelBtn')?.addEventListener('click', () => {
    if(confirm('Êtes-vous sûr de vouloir annuler ? Les modifications seront perdues.')) {
        resetForm();
        showToast('Formulaire réinitialisé', 'info');
    }
});
document.getElementById('closeSuccessModal')?.addEventListener('click', () => {
    closeSuccessModal();
    resetForm();
    window.location.href = 'invoices.html';
});
document.getElementById('viewInvoiceModalBtn')?.addEventListener('click', () => {
    closeSuccessModal();
    window.location.href = `invoices.html?id=${currentInvoiceId}`;
});

// Real-time updates
document.getElementById('clientSelect')?.addEventListener('change', updatePreview);
document.getElementById('issueDate')?.addEventListener('change', updatePreview);
document.getElementById('dueDate')?.addEventListener('change', updatePreview);
document.getElementById('notes')?.addEventListener('input', updatePreview);
document.getElementById('taxRate')?.addEventListener('change', updatePreview);
document.getElementById('discount')?.addEventListener('input', updatePreview);
document.getElementById('currency')?.addEventListener('change', updatePreview);
document.getElementById('invoiceNumber')?.addEventListener('input', updatePreview);

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    showLoader();
    loadClients();
    setDefaultDates();
    updatePreview();
    
    // Add event listeners to initial row
    const initialRow = document.querySelector('.item-row');
    if(initialRow) {
        const qtyInput = initialRow.querySelector('.item-qty');
        const priceInput = initialRow.querySelector('.item-price');
        qtyInput?.addEventListener('input', () => { calculateItemTotal(initialRow); updatePreview(); });
        priceInput?.addEventListener('input', () => { calculateItemTotal(initialRow); updatePreview(); });
    }
    
    setTimeout(() => hideLoader(), 500);
});

// Close modal on outside click
window.addEventListener('click', (e) => {
    const modal = document.getElementById('successModal');
    if(e.target === modal) closeSuccessModal();
});