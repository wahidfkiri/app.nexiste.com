// ======================== TABLES PAGE - SCRIPT COMPLET ========================

// Données simulées
let usersData = [
    { id: 1, name: "Julien D.", email: "julien@nexusdash.com", role: "Administrateur", status: "Actif", date: "2024-01-15" },
    { id: 2, name: "Sophie Martin", email: "sophie@nexusdash.com", role: "Modérateur", status: "Actif", date: "2024-02-20" },
    { id: 3, name: "Alexandre Legrand", email: "alexandre@nexusdash.com", role: "Utilisateur", status: "Actif", date: "2024-03-10" },
    { id: 4, name: "Marie Lambert", email: "marie@nexusdash.com", role: "Utilisateur", status: "Inactif", date: "2024-01-05" },
    { id: 5, name: "Thomas Bernard", email: "thomas@nexusdash.com", role: "Modérateur", status: "Actif", date: "2024-02-28" },
    { id: 6, name: "Julie Petit", email: "julie@nexusdash.com", role: "Utilisateur", status: "En attente", date: "2024-03-15" },
    { id: 7, name: "Nicolas Robert", email: "nicolas@nexusdash.com", role: "Utilisateur", status: "Actif", date: "2024-01-22" },
    { id: 8, name: "Emma Dubois", email: "emma@nexusdash.com", role: "Administrateur", status: "Actif", date: "2024-02-10" },
    { id: 9, name: "Lucas Moreau", email: "lucas@nexusdash.com", role: "Utilisateur", status: "Inactif", date: "2024-03-01" },
    { id: 10, name: "Camille Leroy", email: "camille@nexusdash.com", role: "Modérateur", status: "Actif", date: "2024-01-30" }
];

let ordersData = [
    { id: "#OR-2394", client: "Sophie Martin", amount: "$289.00", status: "delivered", date: "2024-04-12" },
    { id: "#OR-2382", client: "Marc Lefebvre", amount: "$1,249.00", status: "shipped", date: "2024-04-10" },
    { id: "#OR-2375", client: "Emma Dubois", amount: "$89.90", status: "delivered", date: "2024-04-09" },
    { id: "#OR-2360", client: "Thomas R.", amount: "$550.50", status: "processing", date: "2024-04-07" },
    { id: "#OR-2351", client: "Julie Martin", amount: "$199.00", status: "pending", date: "2024-04-05" },
    { id: "#OR-2342", client: "Nicolas P.", amount: "$459.00", status: "delivered", date: "2024-04-03" },
    { id: "#OR-2338", client: "Marie L.", amount: "$129.90", status: "cancelled", date: "2024-04-01" },
    { id: "#OR-2329", client: "Alexandre D.", amount: "$899.00", status: "processing", date: "2024-03-30" }
];

let productsData = [
    { id: 1, name: "Nexus Pro", category: "Logiciel", price: "$49.00", stock: 234, sales: 1234 },
    { id: 2, name: "Dashboard Premium", category: "SaaS", price: "$89.00", stock: 45, sales: 892 },
    { id: 3, name: "Support 24/7", category: "Service", price: "$29.00", stock: 12, sales: 756 },
    { id: 4, name: "Cloud Storage", category: "Hébergement", price: "$19.00", stock: 567, sales: 543 },
    { id: 5, name: "API Access", category: "Développement", price: "$99.00", stock: 8, sales: 421 },
    { id: 6, name: "Analytics Tool", category: "Logiciel", price: "$149.00", stock: 78, sales: 345 },
    { id: 7, name: "Email Marketing", category: "Marketing", price: "$39.00", stock: 156, sales: 678 },
    { id: 8, name: "SEO Optimizer", category: "Marketing", price: "$59.00", stock: 23, sales: 234 }
];

let logsData = [
    { id: 1, date: "2024-04-12 14:23", user: "Julien D.", action: "Connexion", type: "auth", ip: "192.168.1.1", details: "Connexion réussie" },
    { id: 2, date: "2024-04-12 13:15", user: "Sophie M.", action: "Export rapport", type: "export", ip: "192.168.1.2", details: "Export PDF mensuel" },
    { id: 3, date: "2024-04-12 11:45", user: "Alexandre L.", action: "Modification utilisateur", type: "crud", ip: "192.168.1.3", details: "Modification rôle" },
    { id: 4, date: "2024-04-11 16:30", user: "Marie L.", action: "Erreur API", type: "error", ip: "192.168.1.4", details: "Timeout connexion" },
    { id: 5, date: "2024-04-11 10:20", user: "Thomas B.", action: "Déconnexion", type: "auth", ip: "192.168.1.5", details: "Déconnexion" },
    { id: 6, date: "2024-04-10 09:00", user: "Julie P.", action: "Création commande", type: "crud", ip: "192.168.1.6", details: "Nouvelle commande #OR-2450" }
];

// Loader
function showLoader() {
    const loader = document.getElementById('loaderOverlay');
    if(loader) loader.classList.add('active');
}

function hideLoader() {
    const loader = document.getElementById('loaderOverlay');
    if(loader) loader.classList.remove('active');
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Rendu des tables
function renderUsersTable(filter = '') {
    const tbody = document.getElementById('usersTableBody');
    let filteredUsers = usersData.filter(user => 
        user.name.toLowerCase().includes(filter.toLowerCase()) ||
        user.email.toLowerCase().includes(filter.toLowerCase())
    );
    
    tbody.innerHTML = filteredUsers.map(user => `
        <tr>
            <td><input type="checkbox" class="user-checkbox" data-id="${user.id}"></td>
            <td><strong>${user.name}</strong></td>
            <td>${user.email}</td>
            <td><span class="role-badge role-${user.role === 'Administrateur' ? 'admin' : user.role === 'Modérateur' ? 'moderator' : 'user'}">${user.role}</span></td>
            <td><span class="status-badge status-${user.status === 'Actif' ? 'active' : user.status === 'Inactif' ? 'inactive' : 'pending'}">${user.status}</span></td>
            <td>${user.date}</td>
            <td class="action-buttons">
                <button class="action-btn action-edit" onclick="editUser(${user.id})"><i class="fas fa-edit"></i></button>
                <button class="action-btn action-delete" onclick="deleteUser(${user.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function renderOrdersTable(filter = '', statusFilter = 'all') {
    const tbody = document.getElementById('ordersTableBody');
    let filteredOrders = ordersData.filter(order => 
        (order.id.toLowerCase().includes(filter.toLowerCase()) ||
         order.client.toLowerCase().includes(filter.toLowerCase()))
    );
    if(statusFilter !== 'all') {
        filteredOrders = filteredOrders.filter(order => order.status === statusFilter);
    }
    
    const statusMap = {
        pending: 'En attente',
        processing: 'En traitement',
        shipped: 'Expédiée',
        delivered: 'Livrée',
        cancelled: 'Annulée'
    };
    
    tbody.innerHTML = filteredOrders.map(order => `
        <tr>
            <td><strong>${order.id}</strong></td>
            <td>${order.client}</td>
            <td>${order.amount}</td>
            <td><span class="status-badge status-${order.status}">${statusMap[order.status]}</span></td>
            <td>${order.date}</td>
            <td class="action-buttons">
                <button class="action-btn action-view" onclick="viewOrder('${order.id}')"><i class="fas fa-eye"></i></button>
                <button class="action-btn action-edit" onclick="editOrder('${order.id}')"><i class="fas fa-edit"></i></button>
            </td>
        </tr>
    `).join('');
}

function renderProductsTable(filter = '', stockFilter = 'all') {
    const tbody = document.getElementById('productsTableBody');
    let filteredProducts = productsData.filter(product => 
        product.name.toLowerCase().includes(filter.toLowerCase())
    );
    
    if(stockFilter === 'in') filteredProducts = filteredProducts.filter(p => p.stock > 10);
    if(stockFilter === 'low') filteredProducts = filteredProducts.filter(p => p.stock > 0 && p.stock <= 10);
    if(stockFilter === 'out') filteredProducts = filteredProducts.filter(p => p.stock === 0);
    
    const stockClass = (stock) => {
        if(stock > 50) return 'high';
        if(stock > 10) return 'medium';
        return 'low';
    };
    
    tbody.innerHTML = filteredProducts.map(product => `
        <tr>
            <td><strong>${product.name}</strong></td>
            <td>${product.category}</td>
            <td>${product.price}</td>
            <td>
                <div class="stock-indicator">
                    <span>${product.stock}</span>
                    <div class="stock-bar">
                        <div class="stock-fill ${stockClass(product.stock)}" style="width: ${Math.min(100, product.stock / 10)}%"></div>
                    </div>
                </div>
            </td>
            <td>${product.sales}</td>
            <td class="action-buttons">
                <button class="action-btn action-edit" onclick="editProduct(${product.id})"><i class="fas fa-edit"></i></button>
                <button class="action-btn action-delete" onclick="deleteProduct(${product.id})"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function renderLogsTable(filter = '', typeFilter = 'all') {
    const tbody = document.getElementById('logsTableBody');
    let filteredLogs = logsData.filter(log => 
        log.action.toLowerCase().includes(filter.toLowerCase()) ||
        log.user.toLowerCase().includes(filter.toLowerCase())
    );
    if(typeFilter !== 'all') filteredLogs = filteredLogs.filter(log => log.type === typeFilter);
    
    const typeMap = { auth: 'Authentification', crud: 'CRUD', error: 'Erreur', export: 'Export' };
    
    tbody.innerHTML = filteredLogs.map(log => `
        <tr>
            <td>${log.date}</td>
            <td><strong>${log.user}</strong></td>
            <td>${log.action}</td>
            <td><span class="log-badge log-${log.type}">${typeMap[log.type]}</span></td>
            <td>${log.ip}</td>
            <td>${log.details}</td>
        </tr>
    `).join('');
}

// Actions CRUD
function editUser(id) {
    showToast(`Modification de l'utilisateur #${id}`, 'info');
}

function deleteUser(id) {
    if(confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        usersData = usersData.filter(u => u.id !== id);
        renderUsersTable(document.getElementById('usersSearch')?.value || '');
        showToast('Utilisateur supprimé avec succès', 'success');
    }
}

function viewOrder(id) {
    showToast(`Consultation de la commande ${id}`, 'info');
}

function editOrder(id) {
    showToast(`Modification de la commande ${id}`, 'info');
}

function editProduct(id) {
    showToast(`Modification du produit #${id}`, 'info');
}

function deleteProduct(id) {
    if(confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        productsData = productsData.filter(p => p.id !== id);
        renderProductsTable(document.getElementById('productsSearch')?.value || '');
        showToast('Produit supprimé avec succès', 'success');
    }
}

// Événements de recherche et filtres
document.getElementById('usersSearch')?.addEventListener('input', (e) => renderUsersTable(e.target.value));
document.getElementById('ordersSearch')?.addEventListener('input', (e) => renderOrdersTable(e.target.value, document.getElementById('orderStatusFilter')?.value || 'all'));
document.getElementById('orderStatusFilter')?.addEventListener('change', (e) => renderOrdersTable(document.getElementById('ordersSearch')?.value || '', e.target.value));
document.getElementById('productsSearch')?.addEventListener('input', (e) => renderProductsTable(e.target.value, document.getElementById('stockFilter')?.value || 'all'));
document.getElementById('stockFilter')?.addEventListener('change', (e) => renderProductsTable(document.getElementById('productsSearch')?.value || '', e.target.value));
document.getElementById('logsSearch')?.addEventListener('input', (e) => renderLogsTable(e.target.value, document.getElementById('logTypeFilter')?.value || 'all'));
document.getElementById('logTypeFilter')?.addEventListener('change', (e) => renderLogsTable(document.getElementById('logsSearch')?.value || '', e.target.value));

// Tabs
const tabBtns = document.querySelectorAll('.tab-btn');
const tableContainers = document.querySelectorAll('.table-container');

tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const tableId = btn.getAttribute('data-table');
        tabBtns.forEach(b => b.classList.remove('active'));
        tableContainers.forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(`${tableId}Table`).classList.add('active');
    });
});

// Modal
const addUserBtn = document.getElementById('addUserBtn');
const addUserModal = document.getElementById('addUserModal');
const closeUserModal = document.getElementById('closeUserModal');
const cancelUserBtn = document.getElementById('cancelUserBtn');
const saveUserBtn = document.getElementById('saveUserBtn');

function openModal() { addUserModal.classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeModal() { addUserModal.classList.remove('active'); document.body.style.overflow = ''; }

addUserBtn?.addEventListener('click', openModal);
closeUserModal?.addEventListener('click', closeModal);
cancelUserBtn?.addEventListener('click', closeModal);

saveUserBtn?.addEventListener('click', () => {
    const name = document.getElementById('userFullName')?.value;
    const email = document.getElementById('userEmail')?.value;
    const role = document.getElementById('userRole')?.value;
    const status = document.getElementById('userStatus')?.value;
    
    if(name && email) {
        const newUser = { id: usersData.length + 1, name, email, role, status, date: new Date().toISOString().split('T')[0] };
        usersData.push(newUser);
        renderUsersTable();
        closeModal();
        showToast('Utilisateur ajouté avec succès', 'success');
        document.getElementById('addUserForm')?.reset();
    } else {
        showToast('Veuillez remplir tous les champs', 'error');
    }
});

// Exports
document.getElementById('exportExcelBtn')?.addEventListener('click', () => {
    const activeTable = document.querySelector('.table-container.active .data-table');
    if(activeTable) {
        const ws = XLSX.utils.table_to_sheet(activeTable);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Data');
        XLSX.writeFile(wb, `export_${new Date().toISOString().split('T')[0]}.xlsx`);
        showToast('Export Excel réussi', 'success');
    }
});

document.getElementById('exportPdfBtn')?.addEventListener('click', async () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape');
    const activeTable = document.querySelector('.table-container.active .data-table');
    if(activeTable) {
        doc.autoTable({ html: activeTable, startY: 20 });
        doc.save(`export_${new Date().toISOString().split('T')[0]}.pdf`);
        showToast('Export PDF réussi', 'success');
    }
});

// Sélection multiple
document.querySelectorAll('.select-all').forEach(checkbox => {
    checkbox.addEventListener('change', (e) => {
        const table = e.target.closest('.table-container');
        const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
    });
});

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    showLoader();
    setTimeout(() => {
        renderUsersTable();
        renderOrdersTable();
        renderProductsTable();
        renderLogsTable();
        hideLoader();
    }, 500);
});

// Fermeture modals clic extérieur
window.addEventListener('click', (e) => {
    if(e.target === addUserModal) closeModal();
});