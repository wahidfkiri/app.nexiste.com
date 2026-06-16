// ======================== GESTION SIDEBAR MOBILE CORRIGÉE ========================
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const mobileMenuBtn = document.getElementById('mobileMenuBtn');

// Fermer la sidebar
function closeSidebar() {
    if(window.innerWidth <= 992) {
        sidebar.classList.remove('open-mobile');
        if(overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
        document.body.style.position = '';
    }
}

// Ouvrir la sidebar
function openSidebar() {
    if(window.innerWidth <= 992) {
        sidebar.classList.add('open-mobile');
        if(overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
    }
}

// Toggle sidebar
function toggleSidebar() {
    if(sidebar.classList.contains('open-mobile')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

// Événements sidebar mobile
if(mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleSidebar();
    });
}

// Clic sur l'overlay pour fermer
if(overlay) {
    overlay.addEventListener('click', () => {
        closeSidebar();
    });
}

// Fermer la sidebar si redimension au-dessus de 992px
window.addEventListener('resize', () => {
    if(window.innerWidth > 992) {
        sidebar.classList.remove('open-mobile');
        if(overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';
    }
});

// Fermer la sidebar lors d'un clic sur un lien de navigation
document.querySelectorAll('.nav-link-custom').forEach(link => {
    link.addEventListener('click', () => {
        if(window.innerWidth <= 992) {
            closeSidebar();
        }
    });
});

// ======================== GESTION DU LOADER MODERNE ========================
// Créer le loader dynamiquement s'il n'existe pas
let loaderOverlay = document.getElementById('loaderOverlay');
if (!loaderOverlay) {
    loaderOverlay = document.createElement('div');
    loaderOverlay.id = 'loaderOverlay';
    loaderOverlay.className = 'loader-overlay';
    loaderOverlay.innerHTML = `
        <div class="loader">
            <div class="loader-ring"></div>
            <div class="loader-ring"></div>
            <div class="loader-ring"></div>
            <div class="loader-logo">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="loader-text">
                Chargement
                <div class="loader-dots">
                    <span>.</span><span>.</span><span>.</span>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(loaderOverlay);
}

function showLoader() {
    if(loaderOverlay) {
        loaderOverlay.classList.add('active');
    }
}

function hideLoader() {
    if(loaderOverlay) {
        loaderOverlay.classList.remove('active');
    }
}

// ======================== RECHERCHE AVEC SUGGESTIONS ========================
const searchInput = document.getElementById('searchInput');
const suggestionsBox = document.getElementById('suggestionsDropdown');
const searchSuggestions = [
    { name: "Commande #OR-2394", type: "order", icon: "fa-truck", link: "orders" },
    { name: "Commande #OR-2382", type: "order", icon: "fa-truck", link: "orders" },
    { name: "Sophie Martin", type: "client", icon: "fa-user", link: "users" },
    { name: "Marc Lefebvre", type: "client", icon: "fa-user", link: "users" },
    { name: "Rapports financiers", type: "report", icon: "fa-chart-line", link: "reports" },
    { name: "Analytiques Q1 2026", type: "analytics", icon: "fa-chart-pie", link: "analytics" },
    { name: "Paramètres facturation", type: "settings", icon: "fa-cog", link: "settings" },
    { name: "Tableau de bord", type: "dashboard", icon: "fa-tachometer-alt", link: "dashboard" }
];

function showSuggestions(filter = "") {
    if(!suggestionsBox) return;
    if(filter.length === 0) { 
        suggestionsBox.style.display = 'none'; 
        return; 
    }
    const filtered = searchSuggestions.filter(s => 
        s.name.toLowerCase().includes(filter.toLowerCase())
    );
    if(filtered.length === 0) { 
        suggestionsBox.style.display = 'none'; 
        return; 
    }
    suggestionsBox.innerHTML = filtered.map(s => `
        <div class="suggestion-item" data-value="${s.name}" data-link="${s.link}">
            <i class="fas ${s.icon}"></i>
            <div>
                <strong>${s.name}</strong>
                <br><small class="text-muted">${s.type}</small>
            </div>
        </div>
    `).join('');
    suggestionsBox.style.display = 'block';
    
    document.querySelectorAll('.suggestion-item').forEach(el => {
        el.addEventListener('click', () => {
            const value = el.getAttribute('data-value');
            const link = el.getAttribute('data-link');
            if(searchInput) searchInput.value = value;
            suggestionsBox.style.display = 'none';
            if(link) {
                // Naviguer vers la page correspondante
                const targetLink = document.querySelector(`.nav-link-custom[data-link="${link}"]`);
                if(targetLink) {
                    targetLink.click();
                }
            }
        });
    });
}

if(searchInput) {
    searchInput.addEventListener('input', (e) => showSuggestions(e.target.value));
    searchInput.addEventListener('focus', () => {
        if(searchInput.value.length > 0) showSuggestions(searchInput.value);
    });
}

document.addEventListener('click', (e) => {
    if(suggestionsBox && searchInput && 
       !searchInput.contains(e.target) && 
       !suggestionsBox.contains(e.target)) {
        suggestionsBox.style.display = 'none';
    }
});

// ======================== DONNÉES STATIQUES ========================
const statsData = {
    revenue: { value: "$52,480", change: "+14.2%", icon: "fa-dollar-sign", color: "#3b82f6", label: "CA total" },
    users: { value: "4,215", change: "+9.7%", icon: "fa-user-plus", color: "#10b981", label: "Utilisateurs" },
    orders: { value: "1,629", change: "+18%", icon: "fa-truck", color: "#f59e0b", label: "Commandes" },
    conversion: { value: "27.3%", change: "+4.1%", icon: "fa-chart-line", color: "#8b5cf6", label: "Conversion" }
};

// ======================== GRAPHIQUES ========================
let revenueChart = null;
let trafficChart = null;

function initCharts() {
    const ctxR = document.getElementById('revenueChart')?.getContext('2d');
    const ctxT = document.getElementById('trafficChart')?.getContext('2d');
    
    if(ctxR) {
        if(revenueChart) revenueChart.destroy();
        revenueChart = new Chart(ctxR, { 
            type: 'line', 
            data: { 
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], 
                datasets: [{ 
                    label: 'Revenus (k$)', 
                    data: [32, 37, 41, 45, 49, 52], 
                    borderColor: '#3b82f6', 
                    backgroundColor: 'rgba(59,130,246,0.05)', 
                    tension: 0.3, 
                    fill: true, 
                    pointBackgroundColor: '#2563eb',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    pointHoverRadius: 7
                }] 
            }, 
            options: { 
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index' }
                }
            } 
        });
    }
    
    if(ctxT) {
        if(trafficChart) trafficChart.destroy();
        trafficChart = new Chart(ctxT, { 
            type: 'doughnut', 
            data: { 
                labels: ['Direct', 'Réseaux', 'Email'], 
                datasets: [{ 
                    data: [48, 32, 20], 
                    backgroundColor: ['#3b82f6', '#f59e0b', '#10b981'], 
                    borderWidth: 0,
                    hoverOffset: 8
                }] 
            }, 
            options: { 
                cutout: '65%', 
                responsive: true,
                plugins: { 
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.raw}%` } }
                } 
            } 
        });
    }
}

// ======================== RENDU DES PAGES ========================
const contentDiv = document.getElementById('dynamicContent');

function renderDashboard() {
    if(!contentDiv) return;
    contentDiv.innerHTML = `
        <!-- Row statistiques -->
        <div class="row g-4 mb-5">
            ${Object.keys(statsData).map(key => `
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-secondary text-uppercase small fw-semibold">${statsData[key].label}</span>
                            <h2 class="mt-2 fw-bold">${statsData[key].value}</h2>
                            <span class="badge bg-success bg-opacity-10 text-success small">${statsData[key].change}</span>
                        </div>
                        <div class="card-icon" style="background: ${statsData[key].color}10; color:${statsData[key].color}">
                            <i class="fas ${statsData[key].icon}"></i>
                        </div>
                    </div>
                </div>
            `).join('')}
        </div>
        
        <!-- Graphiques -->
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 rounded-4 shadow-sm">
                    <div class="card-header bg-transparent border-0 pt-4">
                        <h5 class="fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>Performance mensuelle</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="280" style="max-height: 280px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 rounded-4 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-4">
                        <h5 class="fw-bold"><i class="fas fa-chart-pie me-2 text-info"></i>Source trafic</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trafficChart" height="220"></canvas>
                        <div class="mt-3 small text-center">
                            <span class="me-3"><i class="fas fa-circle" style="color:#3b82f6"></i> Direct 48%</span>
                            <span class="me-3"><i class="fas fa-circle" style="color:#f59e0b"></i> Réseaux 32%</span>
                            <span><i class="fas fa-circle" style="color:#10b981"></i> Email 20%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dernières commandes -->
        <div class="card border-0 rounded-4 shadow-sm mt-4">
            <div class="card-header bg-transparent border-0 pt-4 d-flex justify-content-between">
                <h5 class="fw-bold"><i class="fas fa-history me-2"></i>Dernières commandes</h5>
                <button class="btn btn-sm btn-soft" onclick="loadPage('orders')">Voir tout</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr><th>ID</th><th>Client</th><th>Montant</th><th>Statut</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>#OR-2394</td><td>Sophie Martin</td><td>$289,00</td><td><span class="badge-status">Livrée</span></td><td>12 Avr 2026</td></tr>
                            <tr><td>#OR-2382</td><td>Marc Lefebvre</td><td>$1,249,00</td><td><span class="badge-status" style="background:#fff3e0; color:#c2410c;">Expédiée</span></td><td>10 Avr 2026</td></tr>
                            <tr><td>#OR-2375</td><td>Emma Dubois</td><td>$89,90</td><td><span class="badge-status" style="background:#e6f7e6; color:#2b9348;">Payée</span></td><td>09 Avr 2026</td></tr>
                            <tr><td>#OR-2360</td><td>Thomas R.</td><td>$550,50</td><td><span class="badge-status">En traitement</span></td><td>07 Avr 2026</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    initCharts();
}

function renderAnalytics() { 
    contentDiv.innerHTML = `
        <div class="card border-0 rounded-4 p-5 text-center shadow-sm">
            <i class="fas fa-chart-simple fa-3x text-primary mb-3"></i>
            <h4 class="fw-bold">Analytiques avancées</h4>
            <p class="text-muted">Taux d'engagement +32% / Sessions: 18.4k</p>
            <div class="alert alert-light border-0 bg-light">Module complet avec filtres personnalisés</div>
            <div class="row mt-4">
                <div class="col-md-4"><div class="p-3 bg-white rounded-3"><h3>68%</h3><small>Taux retention</small></div></div>
                <div class="col-md-4"><div class="p-3 bg-white rounded-3"><h3>124k</h3><small>Pages vues</small></div></div>
                <div class="col-md-4"><div class="p-3 bg-white rounded-3"><h3>00:04:32</h3><small>Durée session</small></div></div>
            </div>
        </div>
    `; 
}

function renderUsers() { 
    contentDiv.innerHTML = `
        <div class="card border-0 rounded-4 p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold"><i class="fas fa-users me-2 text-primary"></i>Gestion utilisateurs</h4>
                <button class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Ajouter</button>
            </div>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <tr><td>Julien D.</td><td>julien@nexus.com</td><td>Super Admin</td><td><span class="badge-status">Actif</span></td><td><i class="fas fa-edit text-muted me-2"></i><i class="fas fa-trash text-muted"></i></td></tr>
                        <tr><td>Sophie M.</td><td>sophie@nexus.com</td><td>Modérateur</td><td><span class="badge-status">Actif</span></td><td><i class="fas fa-edit text-muted me-2"></i><i class="fas fa-trash text-muted"></i></td></tr>
                        <tr><td>Lucas B.</td><td>lucas@nexus.com</td><td>Utilisateur</td><td><span class="badge-status" style="background:#fef3c7; color:#d97706;">En attente</span></td><td><i class="fas fa-edit text-muted me-2"></i><i class="fas fa-trash text-muted"></i></td></tr>
                        <tr><td>Emma L.</td><td>emma@nexus.com</td><td>Utilisateur</td><td><span class="badge-status">Actif</span></td><td><i class="fas fa-edit text-muted me-2"></i><i class="fas fa-trash text-muted"></i></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    `; 
}

function renderOrders() { 
    contentDiv.innerHTML = `
        <div class="card border-0 rounded-4 p-4 shadow-sm">
            <h4 class="fw-bold"><i class="fas fa-shopping-cart me-2 text-primary"></i>Commandes récentes</h4>
            <p class="text-muted">1,629 commandes ce trimestre, +18% de croissance</p>
            <div class="progress mb-4" style="height: 10px;">
                <div class="progress-bar bg-primary" style="width:78%">78%</div>
            </div>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead><tr><th>ID</th><th>Client</th><th>Montant</th><th>Date</th><th>Statut</th></tr></thead>
                    <tbody>
                        <tr><td>#OR-2450</td><td>Nicolas P.</td><td>$459,00</td><td>15 Avr 2026</td><td><span class="badge-status">Livrée</span></td></tr>
                        <tr><td>#OR-2448</td><td>Julie M.</td><td>$129,90</td><td>14 Avr 2026</td><td><span class="badge-status">Expédiée</span></td></tr>
                        <tr><td>#OR-2442</td><td>Thomas R.</td><td>$899,00</td><td>13 Avr 2026</td><td><span class="badge-status">Payée</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    `; 
}

function renderSettings() { 
    contentDiv.innerHTML = `
        <div class="card border-0 rounded-4 p-4 shadow-sm">
            <h4 class="fw-bold"><i class="fas fa-cog me-2 text-primary"></i>Paramètres Dashboard</h4>
            <div class="mt-4">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="notifSwitch" checked>
                    <label class="form-check-label" for="notifSwitch">Notifications push</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="darkModeSwitch">
                    <label class="form-check-label" for="darkModeSwitch">Mode sombre (beta)</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="emailNotif" checked>
                    <label class="form-check-label" for="emailNotif">Emails de rapport hebdomadaire</label>
                </div>
                <hr>
                <h6>Préférences d'affichage</h6>
                <select class="form-select w-50 mt-2">
                    <option>Français</option>
                    <option>English</option>
                    <option>Español</option>
                </select>
            </div>
        </div>
    `; 
}

function renderReports() { 
    contentDiv.innerHTML = `
        <div class="card border-0 rounded-4 p-4 shadow-sm">
            <h4 class="fw-bold"><i class="fas fa-file-alt me-2 text-primary"></i>Génération de rapports</h4>
            <p class="text-muted">Exportez vos données analytiques au format PDF, CSV ou Excel</p>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="p-3 border rounded-3 mb-3">
                        <i class="fas fa-chart-line fa-2x text-primary mb-2"></i>
                        <h6>Rapport financier</h6>
                        <small class="text-muted">CA, bénéfices, projections</small>
                        <button class="btn btn-sm btn-outline-primary mt-2 w-100"><i class="fas fa-download"></i> Télécharger PDF</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 border rounded-3 mb-3">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h6>Rapport utilisateurs</h6>
                        <small class="text-muted">Croissance, engagement</small>
                        <button class="btn btn-sm btn-outline-primary mt-2 w-100"><i class="fas fa-download"></i> Télécharger CSV</button>
                    </div>
                </div>
            </div>
        </div>
    `; 
}

function renderSupport() { 
    contentDiv.innerHTML = `
        <div class="card border-0 rounded-4 p-4 shadow-sm">
            <h4 class="fw-bold"><i class="fas fa-headset me-2 text-primary"></i>Support technique</h4>
            <p class="text-muted">Notre équipe vous répond dans les 24h</p>
            <div class="mb-3">
                <label class="form-label">Sujet</label>
                <input type="text" class="form-control" placeholder="Titre de votre demande">
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea class="form-control" rows="4" placeholder="Décrivez votre problème..."></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Pièce jointe (optionnel)</label>
                <input type="file" class="form-control">
            </div>
            <button class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Envoyer ticket</button>
            <hr class="my-4">
            <div class="d-flex gap-3 justify-content-center">
                <a href="#" class="text-decoration-none"><i class="fab fa-whatsapp fa-2x text-success"></i></a>
                <a href="#" class="text-decoration-none"><i class="fab fa-telegram fa-2x text-primary"></i></a>
                <a href="#" class="text-decoration-none"><i class="far fa-envelope fa-2x text-secondary"></i></a>
            </div>
        </div>
    `; 
}

// ======================== NAVIGATION ========================
const navLinks = document.querySelectorAll('.nav-link-custom');

function setActiveLink(selected) { 
    navLinks.forEach(l => l.classList.remove('active')); 
    selected.classList.add('active'); 
}

function loadPage(pageId) {
    showLoader();
    setTimeout(() => {
        if(pageId === 'dashboard') renderDashboard();
        else if(pageId === 'analytics') renderAnalytics();
        else if(pageId === 'users') renderUsers();
        else if(pageId === 'orders') renderOrders();
        else if(pageId === 'settings') renderSettings();
        else if(pageId === 'reports') renderReports();
        else if(pageId === 'support') renderSupport();
        else renderDashboard();
        
        hideLoader();
        
        // Fermer la sidebar en mobile après navigation
        if(window.innerWidth <= 992) {
            closeSidebar();
        }
    }, 300);
}

navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const page = link.getAttribute('data-link');
        setActiveLink(link);
        loadPage(page);
    });
});

// ======================== INITIALISATION ========================
// Afficher le loader au chargement
showLoader();

// Charger le dashboard par défaut
setTimeout(() => {
    renderDashboard();
    hideLoader();
}, 500);

// Rendre la fonction loadPage accessible globalement
window.loadPage = loadPage;