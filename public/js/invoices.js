// ========== INVOICES PAGE - SCRIPT COMPLET ==========

// Données factures
let invoicesData = [
    { id: 1, number: "INV-2024-001", clientId: 1, clientName: "Sophie Martin", date: "2024-04-01", dueDate: "2024-05-01", amount: 1250, status: "paid", items: [{ name: "Consulting", qty: 5, price: 250 }], notes: "" },
    { id: 2, number: "INV-2024-002", clientId: 2, clientName: "Thomas Bernard", date: "2024-04-05", dueDate: "2024-05-05", amount: 890, status: "pending", items: [{ name: "Setup", qty: 1, price: 890 }], notes: "" },
    { id: 3, number: "INV-2024-003", clientId: 3, clientName: "NexTech Solutions", date: "2024-03-10", dueDate: "2024-04-10", amount: 3450, status: "overdue", items: [{ name: "License Pro", qty: 10, price: 345 }], notes: "" },
    { id: 4, number: "INV-2024-004", clientId: 4, clientName: "Marie Lambert", date: "2024-04-12", dueDate: "2024-05-12", amount: 450, status: "paid", items: [{ name: "Support", qty: 3, price: 150 }], notes: "" },
    { id: 5, number: "INV-2024-005", clientId: 5, clientName: "StartupHub", date: "2024-03-20", dueDate: "2024-04-20", amount: 2300, status: "cancelled", items: [{ name: "Enterprise Plan", qty: 1, price: 2300 }], notes: "" },
    { id: 6, number: "INV-2024-006", clientId: 6, clientName: "Julie Petit", date: "2024-04-15", dueDate: "2024-05-15", amount: 680, status: "pending", items: [{ name: "Maintenance", qty: 4, price: 170 }], notes: "" },
    { id: 7, number: "INV-2024-007", clientId: 7, clientName: "Global Corp", date: "2024-04-08", dueDate: "2024-05-08", amount: 5670, status: "paid", items: [{ name: "Global License", qty: 15, price: 378 }], notes: "" },
    { id: 8, number: "INV-2024-008", clientId: 8, clientName: "Lucas Moreau", date: "2024-03-25", dueDate: "2024-04-25", amount: 320, status: "overdue", items: [{ name: "Basic Plan", qty: 1, price: 320 }], notes: "" }
];

// Données clients
let clientsList = [
    { id: 1, name: "Sophie Martin" },
    { id: 2, name: "Thomas Bernard" },
    { id: 3, name: "NexTech Solutions" },
    { id: 4, name: "Marie Lambert" },
    { id: 5, name: "StartupHub" },
    { id: 6, name: "Julie Petit" },
    { id: 7, name: "Global Corp" },
    { id: 8, name: "Lucas Moreau" }
];

let currentPage = 1;
let itemsPerPage = 10;
let deleteId = null;
let currentItems = [];
let itemCounter = 0;

// Loader
function showLoader() { document.getElementById('loaderOverlay')?.classList.add('active'); }
function hideLoader() { document.getElementById('loaderOverlay')?.classList.remove('active'); }

// Toast
function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.toast-notification');
    if(existingToast) existingToast.remove();
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 3000);
}

// Render table
function renderTable() {
    const searchTerm = document.getElementById('searchInvoice')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('statusFilter')?.value || 'all';
    const dateFilter = document.getElementById('dateFilter')?.value || 'all';
    
    let filtered = invoicesData.filter(inv => {
        const matchSearch = inv.number.toLowerCase().includes(searchTerm) || inv.clientName.toLowerCase().includes(searchTerm);
        const matchStatus = statusFilter === 'all' || inv.status === statusFilter;
        let matchDate = true;
        const now = new Date();
        const invDate = new Date(inv.date);
        if(dateFilter === 'month') matchDate = invDate.getMonth() === now.getMonth() && invDate.getFullYear() === now.getFullYear();
        else if(dateFilter === 'quarter') matchDate = Math.floor(invDate.getMonth() / 3) === Math.floor(now.getMonth() / 3) && invDate.getFullYear() === now.getFullYear();
        else if(dateFilter === 'year') matchDate = invDate.getFullYear() === now.getFullYear();
        return matchSearch && matchStatus && matchDate;
    });
    
    const totalItems = filtered.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const start = (currentPage - 1) * itemsPerPage;
    const paginated = filtered.slice(start, start + itemsPerPage);
    
    const tbody = document.getElementById('invoicesTableBody');
    if(!tbody) return;
    
    const statusMap = { paid: 'Payée', pending: 'En attente', overdue: 'En retard', cancelled: 'Annulée' };
    const statusClass = { paid: 'status-paid', pending: 'status-pending', overdue: 'status-overdue', cancelled: 'status-cancelled' };
    
    tbody.innerHTML = paginated.map(inv => `
        <tr>
            <td><input type="checkbox" class="invoice-checkbox" data-id="${inv.id}"></td>
            <td><strong>${inv.number}</strong></td>
            <td>${inv.clientName}</td>
            <td>${inv.date}</td>
            <td>${inv.dueDate}</td>
            <td>€${inv.amount.toLocaleString()}</td>
            <td><span class="status-badge ${statusClass[inv.status]}">${statusMap[inv.status]}</span></td>
            <td class="action-buttons">
                <button class="action-btn action-view" onclick="viewInvoice(${inv.id})"><i class="fas fa-eye"></i></button>
                <button class="action-btn action-edit" onclick="editInvoice(${inv.id})"><i class="fas fa-edit"></i></button>
                <button class="action-btn action-delete" onclick="confirmDelete(${inv.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
    
    // Stats
    document.getElementById('totalInvoices').textContent = invoicesData.length;
    document.getElementById('paidInvoices').textContent = invoicesData.filter(i => i.status === 'paid').length;
    document.getElementById('pendingInvoices').textContent = invoicesData.filter(i => i.status === 'pending').length;
    document.getElementById('totalAmount').textContent = `€${invoicesData.reduce((sum, i) => sum + i.amount, 0).toLocaleString()}`;
    
    document.getElementById('paginationInfo').textContent = `Affichage ${start + 1}-${Math.min(start + itemsPerPage, totalItems)} sur ${totalItems}`;
    const pageNumbers = document.getElementById('pageNumbers');
    if(pageNumbers) {
        let pagesHtml = '';
        for(let i = 1; i <= Math.min(totalPages, 5); i++) {
            pagesHtml += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
        }
        pageNumbers.innerHTML = pagesHtml;
    }
}

function goToPage(page) { currentPage = page; renderTable(); }
document.getElementById('prevPage')?.addEventListener('click', () => { if(currentPage > 1) { currentPage--; renderTable(); } });
document.getElementById('nextPage')?.addEventListener('click', () => { const totalPages = Math.ceil(invoicesData.length / itemsPerPage); if(currentPage < totalPages) { currentPage++; renderTable(); } });
document.getElementById('searchInvoice')?.addEventListener('input', () => { currentPage = 1; renderTable(); });
document.getElementById('statusFilter')?.addEventListener('change', () => { currentPage = 1; renderTable(); });
document.getElementById('dateFilter')?.addEventListener('change', () => { currentPage = 1; renderTable(); });

// Modal gestion
const invoiceModal = document.getElementById('invoiceModal');
const viewModal = document.getElementById('viewInvoiceModal');
const deleteModal = document.getElementById('deleteModal');
let isEditing = false;
let editingId = null;

function openModal(modal) { modal?.classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeModal(modal) { modal?.classList.remove('active'); document.body.style.overflow = ''; }

document.getElementById('addInvoiceBtn')?.addEventListener('click', () => {
    isEditing = false; editingId = null;
    document.getElementById('invoiceForm')?.reset();
    document.querySelector('#invoiceModal .modal-header h3').innerHTML = '<i class="fas fa-plus-circle"></i> Nouvelle facture';
    document.getElementById('saveInvoiceBtn').textContent = 'Créer la facture';
    document.getElementById('invoiceNumber').value = `INV-${new Date().getFullYear()}-${String(invoicesData.length + 1).padStart(3, '0')}`;
    document.getElementById('invoiceDate').value = new Date().toISOString().split('T')[0];
    const dueDate = new Date(); dueDate.setDate(dueDate.getDate() + 30);
    document.getElementById('invoiceDueDate').value = dueDate.toISOString().split('T')[0];
    
    // Reset items
    document.getElementById('invoiceItems').innerHTML = '';
    itemCounter = 0;
    addItemRow();
    
    // Load clients
    const clientSelect = document.getElementById('invoiceClient');
    clientSelect.innerHTML = '<option value="">Sélectionner un client</option>';
    clientsList.forEach(c => { clientSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`; });
    
    openModal(invoiceModal);
});

document.getElementById('closeModalBtn')?.addEventListener('click', () => closeModal(invoiceModal));
document.getElementById('cancelModalBtn')?.addEventListener('click', () => closeModal(invoiceModal));
document.getElementById('closeViewModal')?.addEventListener('click', () => closeModal(viewModal));
document.getElementById('closeViewFooterBtn')?.addEventListener('click', () => closeModal(viewModal));
document.getElementById('cancelDeleteBtn')?.addEventListener('click', () => closeModal(deleteModal));

// Items management
function addItemRow() {
    const container = document.getElementById('invoiceItems');
    const index = itemCounter;
    const row = document.createElement('div');
    row.className = 'item-row';
    row.setAttribute('data-index', index);
    row.innerHTML = `
        <div class="item-name"><input type="text" class="form-control-modern" placeholder="Description" id="itemName${index}"></div>
        <div class="item-qty"><input type="number" class="form-control-modern" placeholder="Qté" id="itemQty${index}" value="1" onchange="updateTotals()"></div>
        <div class="item-price"><input type="number" class="form-control-modern" placeholder="Prix unit." id="itemPrice${index}" onchange="updateTotals()"></div>
        <div class="item-total" id="itemTotal${index}">€0.00</div>
        <div class="item-remove"><button type="button" class="remove-item" onclick="removeItemRow(${index})"><i class="fas fa-trash"></i></button></div>
    `;
    container.appendChild(row);
    itemCounter++;
}

function removeItemRow(index) { document.querySelector(`.item-row[data-index="${index}"]`)?.remove(); updateTotals(); }
document.getElementById('addItemBtn')?.addEventListener('click', addItemRow);

function updateTotals() {
    let subtotal = 0;
    for(let i = 0; i < itemCounter; i++) {
        const qty = parseFloat(document.getElementById(`itemQty${i}`)?.value) || 0;
        const price = parseFloat(document.getElementById(`itemPrice${i}`)?.value) || 0;
        const total = qty * price;
        const totalEl = document.getElementById(`itemTotal${i}`);
        if(totalEl) totalEl.textContent = `€${total.toFixed(2)}`;
        subtotal += total;
    }
    const tax = subtotal * 0.2;
    const grandTotal = subtotal + tax;
    document.getElementById('subtotal').textContent = `€${subtotal.toFixed(2)}`;
    document.getElementById('taxAmount').textContent = `€${tax.toFixed(2)}`;
    document.getElementById('grandTotal').textContent = `€${grandTotal.toFixed(2)}`;
}

// Save invoice
document.getElementById('saveInvoiceBtn')?.addEventListener('click', () => {
    const number = document.getElementById('invoiceNumber')?.value;
    const clientId = parseInt(document.getElementById('invoiceClient')?.value);
    const client = clientsList.find(c => c.id === clientId);
    const date = document.getElementById('invoiceDate')?.value;
    const dueDate = document.getElementById('invoiceDueDate')?.value;
    const notes = document.getElementById('invoiceNotes')?.value;
    
    // Calculate total
    let subtotal = 0;
    const items = [];
    for(let i = 0; i < itemCounter; i++) {
        const name = document.getElementById(`itemName${i}`)?.value;
        const qty = parseFloat(document.getElementById(`itemQty${i}`)?.value) || 0;
        const price = parseFloat(document.getElementById(`itemPrice${i}`)?.value) || 0;
        if(name && qty > 0 && price > 0) {
            items.push({ name, qty, price });
            subtotal += qty * price;
        }
    }
    const tax = subtotal * 0.2;
    const amount = subtotal + tax;
    
    if(!number || !clientId || !date || !dueDate || items.length === 0) {
        showToast('Veuillez remplir tous les champs obligatoires', 'error');
        return;
    }
    
    if(isEditing && editingId) {
        const index = invoicesData.findIndex(i => i.id === editingId);
        if(index !== -1) {
            invoicesData[index] = { ...invoicesData[index], number, clientId, clientName: client.name, date, dueDate, amount, items, notes, status: invoicesData[index].status };
            showToast('Facture modifiée avec succès', 'success');
        }
    } else {
        const newId = Math.max(...invoicesData.map(i => i.id), 0) + 1;
        invoicesData.push({ id: newId, number, clientId, clientName: client.name, date, dueDate, amount, items, notes, status: 'pending' });
        showToast('Facture créée avec succès', 'success');
    }
    
    closeModal(invoiceModal);
    renderTable();
});

// View invoice
window.viewInvoice = (id) => {
    const invoice = invoicesData.find(i => i.id === id);
    if(invoice) {
        const statusMap = { paid: 'Payée', pending: 'En attente', overdue: 'En retard', cancelled: 'Annulée' };
        const content = document.getElementById('viewInvoiceContent');
        content.innerHTML = `
            <div class="invoice-detail">
                <div class="invoice-header">
                    <h3>FACTURE</h3>
                    <p>${invoice.number}</p>
                </div>
                <div class="invoice-info">
                    <div class="info-group">
                        <strong>Client :</strong>
                        <p>${invoice.clientName}</p>
                    </div>
                    <div class="info-group">
                        <strong>Dates :</strong>
                        <p>Émission : ${invoice.date}</p>
                        <p>Échéance : ${invoice.dueDate}</p>
                    </div>
                </div>
                <table class="invoice-table">
                    <thead><tr><th>Description</th><th>Qté</th><th>Prix unit.</th><th>Total</th></tr></thead>
                    <tbody>
                        ${invoice.items.map(item => `<tr><td>${item.name}</td><td>${item.qty}</td><td>€${item.price}</td><td>€${(item.qty * item.price).toFixed(2)}</td></tr>`).join('')}
                    </tbody>
                </table>
                <div class="totals-section">
                    <div class="total-line">Sous-total : <span>€${(invoice.amount / 1.2).toFixed(2)}</span></div>
                    <div class="total-line">TVA (20%) : <span>€${(invoice.amount - invoice.amount / 1.2).toFixed(2)}</span></div>
                    <div class="total-line grand-total">Total : <span>€${invoice.amount.toFixed(2)}</span></div>
                </div>
                ${invoice.notes ? `<div class="notes-section"><strong>Notes :</strong><p>${invoice.notes}</p></div>` : ''}
                <div class="status-section"><strong>Statut :</strong> <span class="status-badge ${invoice.status === 'paid' ? 'status-paid' : invoice.status === 'pending' ? 'status-pending' : 'status-overdue'}">${statusMap[invoice.status]}</span></div>
            </div>
        `;
        openModal(viewModal);
    }
};

// Edit invoice
window.editInvoice = (id) => {
    const invoice = invoicesData.find(i => i.id === id);
    if(invoice) {
        isEditing = true;
        editingId = id;
        document.getElementById('invoiceNumber').value = invoice.number;
        document.getElementById('invoiceClient').value = invoice.clientId;
        document.getElementById('invoiceDate').value = invoice.date;
        document.getElementById('invoiceDueDate').value = invoice.dueDate;
        document.getElementById('invoiceNotes').value = invoice.notes || '';
        
        document.getElementById('invoiceItems').innerHTML = '';
        itemCounter = 0;
        invoice.items.forEach((item, idx) => {
            addItemRow();
            document.getElementById(`itemName${idx}`).value = item.name;
            document.getElementById(`itemQty${idx}`).value = item.qty;
            document.getElementById(`itemPrice${idx}`).value = item.price;
        });
        updateTotals();
        
        document.querySelector('#invoiceModal .modal-header h3').innerHTML = '<i class="fas fa-edit"></i> Modifier la facture';
        document.getElementById('saveInvoiceBtn').textContent = 'Modifier';
        openModal(invoiceModal);
    }
};

// Delete invoice
window.confirmDelete = (id) => { deleteId = id; openModal(deleteModal); };
document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => {
    if(deleteId) {
        invoicesData = invoicesData.filter(i => i.id !== deleteId);
        renderTable();
        showToast('Facture supprimée avec succès', 'success');
        closeModal(deleteModal);
        deleteId = null;
    }
});

// Export
document.getElementById('exportBtn')?.addEventListener('click', () => {
    let csv = "N° Facture,Client,Date,Date échéance,Montant,Statut\n";
    invoicesData.forEach(i => {
        const statusMap = { paid: 'Payée', pending: 'En attente', overdue: 'En retard', cancelled: 'Annulée' };
        csv += `"${i.number}","${i.clientName}","${i.date}","${i.dueDate}",${i.amount},"${statusMap[i.status]}"\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `factures_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);
    showToast('Export CSV réussi', 'success');
});

// Select all
document.getElementById('selectAll')?.addEventListener('change', (e) => {
    document.querySelectorAll('.invoice-checkbox').forEach(cb => cb.checked = e.target.checked);
});

// Close modals on outside click
window.addEventListener('click', (e) => {
    if(e.target === invoiceModal) closeModal(invoiceModal);
    if(e.target === viewModal) closeModal(viewModal);
    if(e.target === deleteModal) closeModal(deleteModal);
});

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    showLoader();
    setTimeout(() => { renderTable(); hideLoader(); }, 500);
});