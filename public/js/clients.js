// ========== CLIENTS PAGE - SCRIPT COMPLET ==========

// Données clients
let clientsData = [
    { id: 1, name: "Sophie Martin", email: "sophie@martin.com", phone: "+33 6 12 34 56 78", type: "entreprise", status: "actif", revenue: 12500, address: "15 rue de Paris, 75001 Paris" },
    { id: 2, name: "Thomas Bernard", email: "thomas@bernard.fr", phone: "+33 6 23 45 67 89", type: "particulier", status: "actif", revenue: 3200, address: "8 avenue des Champs, 69002 Lyon" },
    { id: 3, name: "NexTech Solutions", email: "contact@nextech.com", phone: "+33 1 23 45 67 89", type: "entreprise", status: "actif", revenue: 45800, address: "45 rue de la République, 13001 Marseille" },
    { id: 4, name: "Marie Lambert", email: "marie@lambert.fr", phone: "+33 6 34 56 78 90", type: "particulier", status: "inactif", revenue: 890, address: "12 boulevard Victor Hugo, 31000 Toulouse" },
    { id: 5, name: "StartupHub", email: "hello@startuphub.io", phone: "+33 1 34 56 78 90", type: "startup", status: "actif", revenue: 23400, address: "7 rue de la Bourse, 33000 Bordeaux" },
    { id: 6, name: "Julie Petit", email: "julie@petit.com", phone: "+33 6 45 67 89 01", type: "particulier", status: "actif", revenue: 1500, address: "23 rue Nationale, 59000 Lille" },
    { id: 7, name: "Global Corp", email: "contact@globalcorp.com", phone: "+33 1 45 67 89 01", type: "entreprise", status: "actif", revenue: 78900, address: "100 avenue des Champs-Élysées, 75008 Paris" },
    { id: 8, name: "Lucas Moreau", email: "lucas@moreau.fr", phone: "+33 6 56 78 90 12", type: "particulier", status: "inactif", revenue: 450, address: "5 rue des Lilas, 44000 Nantes" },
    { id: 9, name: "Tech Innovators", email: "info@techinnov.com", phone: "+33 1 56 78 90 12", type: "startup", status: "actif", revenue: 16700, address: "34 rue du Commerce, 67000 Strasbourg" },
    { id: 10, name: "Emma Dubois", email: "emma@dubois.fr", phone: "+33 6 67 89 01 23", type: "particulier", status: "actif", revenue: 2100, address: "89 rue de la Gare, 35000 Rennes" }
];

let currentPage = 1;
let itemsPerPage = 10;
let deleteId = null;

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
    const existingToast = document.querySelector('.toast-notification');
    if(existingToast) existingToast.remove();
    
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

// Rendu du tableau
function renderTable() {
    const searchTerm = document.getElementById('searchClient')?.value.toLowerCase() || '';
    const typeFilter = document.getElementById('typeFilter')?.value || 'all';
    const statusFilter = document.getElementById('statusFilter')?.value || 'all';
    
    let filtered = clientsData.filter(client => {
        const matchSearch = client.name.toLowerCase().includes(searchTerm) || client.email.toLowerCase().includes(searchTerm);
        const matchType = typeFilter === 'all' || client.type === typeFilter;
        const matchStatus = statusFilter === 'all' || client.status === statusFilter;
        return matchSearch && matchType && matchStatus;
    });
    
    const totalItems = filtered.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const start = (currentPage - 1) * itemsPerPage;
    const paginated = filtered.slice(start, start + itemsPerPage);
    
    const tbody = document.getElementById('clientsTableBody');
    if(!tbody) return;
    
    tbody.innerHTML = paginated.map(client => {
        const typeClass = client.type === 'entreprise' ? 'badge-entreprise' : client.type === 'startup' ? 'badge-startup' : 'badge-particulier';
        const typeLabel = client.type === 'entreprise' ? 'Entreprise' : client.type === 'startup' ? 'Startup' : 'Particulier';
        const statusClass = client.status === 'actif' ? 'status-active' : 'status-inactif';
        const statusLabel = client.status === 'actif' ? 'Actif' : 'Inactif';
        const initials = client.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        
        return `
            <tr>
                <td><input type="checkbox" class="client-checkbox" data-id="${client.id}"></td>
                <td>
                    <div class="client-info">
                        <div class="client-avatar">${initials}</div>
                        <div>
                            <div class="client-name">${client.name}</div>
                            <div class="client-email">${client.email}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge-client ${typeClass}">${typeLabel}</span></td>
                <td>${client.email}</td>
                <td>${client.phone}</td>
                <td><span class="badge-client ${statusClass}">${statusLabel}</span></td>
                <td>€${client.revenue.toLocaleString()}</td>
                <td class="action-buttons">
                    <button class="action-btn action-edit" onclick="editClient(${client.id})"><i class="fas fa-edit"></i></button>
                    <button class="action-btn action-delete" onclick="confirmDelete(${client.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    }).join('');
    
    // Mise à jour des stats
    document.getElementById('totalClients').textContent = clientsData.length;
    document.getElementById('activeClients').textContent = clientsData.filter(c => c.status === 'actif').length;
    document.getElementById('totalRevenue').textContent = `€${clientsData.reduce((sum, c) => sum + c.revenue, 0).toLocaleString()}`;
    document.getElementById('newClients').textContent = clientsData.filter(c => c.id > clientsData.length - 5).length;
    
    // Pagination
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

// Navigation pages
function goToPage(page) {
    currentPage = page;
    renderTable();
}

document.getElementById('prevPage')?.addEventListener('click', () => {
    if(currentPage > 1) {
        currentPage--;
        renderTable();
    }
});

document.getElementById('nextPage')?.addEventListener('click', () => {
    const totalPages = Math.ceil(clientsData.length / itemsPerPage);
    if(currentPage < totalPages) {
        currentPage++;
        renderTable();
    }
});

// Recherche et filtres
document.getElementById('searchClient')?.addEventListener('input', () => {
    currentPage = 1;
    renderTable();
});

document.getElementById('typeFilter')?.addEventListener('change', () => {
    currentPage = 1;
    renderTable();
});

document.getElementById('statusFilter')?.addEventListener('change', () => {
    currentPage = 1;
    renderTable();
});

// Sélection multiple
document.getElementById('selectAll')?.addEventListener('change', (e) => {
    document.querySelectorAll('.client-checkbox').forEach(cb => cb.checked = e.target.checked);
});

// Modal gestion
const clientModal = document.getElementById('clientModal');
const deleteModal = document.getElementById('deleteModal');
let isEditing = false;
let editingId = null;

function openModal() { clientModal?.classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeModal() { clientModal?.classList.remove('active'); document.body.style.overflow = ''; }
function openDeleteModal() { deleteModal?.classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeDeleteModal() { deleteModal?.classList.remove('active'); document.body.style.overflow = ''; }

document.getElementById('addClientBtn')?.addEventListener('click', () => {
    isEditing = false;
    editingId = null;
    document.getElementById('clientForm')?.reset();
    document.querySelector('#clientModal .modal-header h3').innerHTML = '<i class="fas fa-user-plus"></i> Nouveau client';
    document.getElementById('saveClientBtn').textContent = 'Ajouter';
    openModal();
});

document.getElementById('closeModalBtn')?.addEventListener('click', closeModal);
document.getElementById('cancelModalBtn')?.addEventListener('click', closeModal);
document.getElementById('cancelDeleteBtn')?.addEventListener('click', closeDeleteModal);

// Sauvegarde client
document.getElementById('saveClientBtn')?.addEventListener('click', () => {
    const name = document.getElementById('clientName')?.value;
    const email = document.getElementById('clientEmail')?.value;
    const phone = document.getElementById('clientPhone')?.value;
    const type = document.getElementById('clientType')?.value;
    const status = document.getElementById('clientStatus')?.value;
    const address = document.getElementById('clientAddress')?.value;
    
    if(!name || !email) {
        showToast('Veuillez remplir le nom et l\'email', 'error');
        return;
    }
    
    if(isEditing && editingId) {
        const index = clientsData.findIndex(c => c.id === editingId);
        if(index !== -1) {
            clientsData[index] = { ...clientsData[index], name, email, phone, type, status, address };
            showToast('Client modifié avec succès', 'success');
        }
    } else {
        const newId = Math.max(...clientsData.map(c => c.id), 0) + 1;
        clientsData.push({ id: newId, name, email, phone, type, status, address, revenue: 0 });
        showToast('Client ajouté avec succès', 'success');
    }
    
    closeModal();
    renderTable();
});

// Édition client
window.editClient = (id) => {
    const client = clientsData.find(c => c.id === id);
    if(client) {
        isEditing = true;
        editingId = id;
        document.getElementById('clientName').value = client.name;
        document.getElementById('clientEmail').value = client.email;
        document.getElementById('clientPhone').value = client.phone;
        document.getElementById('clientType').value = client.type;
        document.getElementById('clientStatus').value = client.status;
        document.getElementById('clientAddress').value = client.address || '';
        document.querySelector('#clientModal .modal-header h3').innerHTML = '<i class="fas fa-edit"></i> Modifier le client';
        document.getElementById('saveClientBtn').textContent = 'Modifier';
        openModal();
    }
};

// Suppression client
window.confirmDelete = (id) => {
    deleteId = id;
    openDeleteModal();
};

document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => {
    if(deleteId) {
        clientsData = clientsData.filter(c => c.id !== deleteId);
        renderTable();
        showToast('Client supprimé avec succès', 'success');
        closeDeleteModal();
        deleteId = null;
    }
});

// Export
document.getElementById('exportBtn')?.addEventListener('click', () => {
    let csv = "Nom,Email,Téléphone,Type,Statut,Revenu,Adresse\n";
    clientsData.forEach(c => {
        csv += `"${c.name}","${c.email}","${c.phone}","${c.type}","${c.status}",${c.revenue},"${c.address || ''}"\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `clients_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);
    showToast('Export CSV réussi', 'success');
});

// Fermeture modals clic extérieur
window.addEventListener('click', (e) => {
    if(e.target === clientModal) closeModal();
    if(e.target === deleteModal) closeDeleteModal();
});

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    showLoader();
    setTimeout(() => {
        renderTable();
        hideLoader();
    }, 500);
});