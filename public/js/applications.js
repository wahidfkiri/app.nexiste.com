// ========== APPLICATIONS PAGE - SCRIPT COMPLET ==========

// Applications data
const applications = [
    // Storage
    { id: 1, name: "Dropbox", category: "storage", icon: "fab fa-dropbox", color: "#0061FF", description: "Synchronisez et partagez vos fichiers en toute sécurité.", features: ["Stockage cloud", "Synchronisation automatique", "Partage de fichiers", "Sauvegarde sécurisée"], website: "https://www.dropbox.com" },
    { id: 2, name: "Google Drive", category: "storage", icon: "fab fa-google-drive", color: "#4285F4", description: "Stockage cloud intégré avec Google Workspace.", features: ["15 Go gratuits", "Intégration Google Docs", "Partage avancé", "Recherche intelligente"], website: "https://drive.google.com" },
    { id: 3, name: "OneDrive", category: "storage", icon: "fab fa-microsoft", color: "#0078D4", description: "Stockage cloud Microsoft intégré à Office 365.", features: ["Intégration Office", "5 Go gratuits", "Synchronisation PC", "Partage sécurisé"], website: "https://onedrive.live.com" },
    { id: 4, name: "iCloud", category: "storage", icon: "fab fa-apple", color: "#A2AAAD", description: "Stockage cloud Apple pour vos appareils.", features: ["5 Go gratuits", "Synchronisation Apple", "Sauvegarde iPhone", "Partage familial"], website: "https://www.icloud.com" },
    
    // Communication
    { id: 5, name: "Roundcube", category: "communication", icon: "fas fa-envelope", color: "#3b82f6", description: "Webmail professionnel pour gérer vos emails.", features: ["Interface webmail", "Gestion des contacts", "Filtres anti-spam", "Signature personnalisée"], website: "https://roundcube.net" },
    { id: 6, name: "Slack", category: "communication", icon: "fab fa-slack", color: "#4A154B", description: "Messagerie d'équipe pour une collaboration efficace.", features: ["Canaux de discussion", "Appels vidéo", "Intégrations multiples", "Recherche avancée"], website: "https://slack.com" },
    { id: 7, name: "Microsoft Teams", category: "communication", icon: "fab fa-microsoft", color: "#6264A7", description: "Plateforme de collaboration Microsoft.", features: ["Visioconférence", "Chat d'équipe", "Partage de fichiers", "Intégration Office"], website: "https://teams.microsoft.com" },
    { id: 8, name: "Zoom", category: "communication", icon: "fab fa-zoom", color: "#2D8CFF", description: "Visioconférence professionnelle HD.", features: ["Réunions HD", "Enregistrement", "Salles de sous-groupes", "Partage d'écran"], website: "https://zoom.us" },
    
    // Productivity
    { id: 9, name: "Trello", category: "productivity", icon: "fab fa-trello", color: "#0079BF", description: "Gestion de projets visuelle avec Kanban.", features: ["Tableaux Kanban", "Listes de tâches", "Dates d'échéance", "Étiquettes personnalisées"], website: "https://trello.com" },
    { id: 10, name: "Asana", category: "productivity", icon: "fas fa-tasks", color: "#F06A6A", description: "Gestion de projets et d'équipes.", features: ["Suivi des tâches", "Calendrier", "Chronologies", "Rapports avancés"], website: "https://asana.com" },
    { id: 11, name: "Notion", category: "productivity", icon: "fas fa-book", color: "#000000", description: "Espace de travail tout-en-un.", features: ["Notes", "Bases de données", "Wikis", "Calendriers"], website: "https://notion.so" },
    { id: 12, name: "Monday.com", category: "productivity", icon: "fas fa-chart-line", color: "#FF6600", description: "Plateforme de gestion de projets.", features: ["Tableaux personnalisés", "Automatisations", "Vues multiples", "Tableaux de bord"], website: "https://monday.com" },
    
    // AI & Chatbot
    { id: 13, name: "ChatGPT", category: "ai", icon: "fas fa-comment-dots", color: "#10a37f", description: "Assistant IA pour le support client.", features: ["Chatbot intelligent", "Support 24/7", "Analyse des demandes", "Réponses automatiques"], website: "https://chat.openai.com" },
    { id: 14, name: "Google Gemini", category: "ai", icon: "fab fa-google", color: "#4285F4", description: "IA avancée de Google.", features: ["Génération de texte", "Analyse de données", "Traduction", "Synthèse vocale"], website: "https://gemini.google.com" },
    { id: 15, name: "Intercom", category: "ai", icon: "fas fa-headset", color: "#6C52D9", description: "Plateforme de support client.", features: ["Chat en direct", "Base de connaissances", "Messages ciblés", "Tickets support"], website: "https://www.intercom.com" },
    { id: 16, name: "Drift", category: "ai", icon: "fas fa-bolt", color: "#00B4E0", description: "Chatbot de conversion commerciale.", features: ["Lead generation", "Rendez-vous automatiques", "Analytics", "Intégration CRM"], website: "https://www.drift.com" },
    
    // Marketing
    { id: 17, name: "Mailchimp", category: "marketing", icon: "fas fa-envelope-open-text", color: "#FFE01B", description: "Email marketing automatisé.", features: ["Campagnes email", "Segmentation", "Analytics", "Automatisation"], website: "https://mailchimp.com" },
    { id: 18, name: "HubSpot", category: "marketing", icon: "fab fa-hubspot", color: "#FF7A59", description: "CRM et marketing inbound.", features: ["CRM gratuit", "Marketing automation", "Analytics", "Lead scoring"], website: "https://hubspot.com" }
];

let connectedApps = JSON.parse(localStorage.getItem('connectedApps')) || [];

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

// Render apps grid
function renderApps(category = 'all') {
    const grid = document.getElementById('appsGrid');
    if(!grid) return;
    
    let filtered = applications;
    if(category !== 'all') filtered = applications.filter(app => app.category === category);
    
    grid.innerHTML = filtered.map(app => {
        const isConnected = connectedApps.includes(app.id);
        const categoryClass = app.category;
        return `
            <div class="app-card" data-id="${app.id}">
                <div class="app-icon ${categoryClass}" style="background: ${app.color}20; color: ${app.color};">
                    <i class="${app.icon}"></i>
                </div>
                <div class="app-badge">${app.category === 'storage' ? 'Stockage' : app.category === 'communication' ? 'Communication' : app.category === 'productivity' ? 'Productivité' : app.category === 'ai' ? 'IA & Chatbot' : 'Marketing'}</div>
                <h3 class="app-title">${app.name}</h3>
                <p class="app-description">${app.description}</p>
                <div class="app-status ${isConnected ? 'status-connected' : 'status-disconnected'}">
                    <i class="fas ${isConnected ? 'fa-check-circle' : 'fa-plug'}"></i>
                    <span>${isConnected ? 'Connectée' : 'Déconnectée'}</span>
                </div>
            </div>
        `;
    }).join('');
    
    // Add click events
    document.querySelectorAll('.app-card').forEach(card => {
        card.addEventListener('click', () => {
            const id = parseInt(card.getAttribute('data-id'));
            showAppDetail(id);
        });
    });
}

// Show app detail
let currentAppId = null;

function showAppDetail(appId) {
    const app = applications.find(a => a.id === appId);
    if(!app) return;
    currentAppId = appId;
    
    const isConnected = connectedApps.includes(appId);
    const categoryLabel = app.category === 'storage' ? 'Stockage' : app.category === 'communication' ? 'Communication' : app.category === 'productivity' ? 'Productivité' : app.category === 'ai' ? 'IA & Chatbot' : 'Marketing';
    
    const content = document.getElementById('appDetailContent');
    content.innerHTML = `
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div class="app-icon ${app.category}" style="width: 80px; height: 80px; font-size: 2.5rem; margin: 0 auto 1rem; background: ${app.color}20; color: ${app.color};">
                <i class="${app.icon}"></i>
            </div>
            <h3 style="margin-bottom: 0.5rem;">${app.name}</h3>
            <span class="app-badge" style="position: relative; top: 0;">${categoryLabel}</span>
        </div>
        <p style="margin-bottom: 1rem; color: #64748b;">${app.description}</p>
        <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem;">Fonctionnalités :</h4>
        <ul style="margin-bottom: 1rem; padding-left: 1.5rem;">
            ${app.features.map(f => `<li style="margin-bottom: 0.25rem; color: #475569;">✓ ${f}</li>`).join('')}
        </ul>
        <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 12px;">
            <small style="color: #64748b;">Site officiel : <a href="${app.website}" target="_blank" style="color: #3b82f6;">${app.website}</a></small>
        </div>
    `;
    
    document.getElementById('modalAppTitle').innerHTML = `<i class="${app.icon}"></i> ${app.name}`;
    const connectBtn = document.getElementById('connectAppBtn');
    connectBtn.textContent = isConnected ? 'Déconnecter' : 'Connecter';
    connectBtn.style.background = isConnected ? '#ef4444' : 'linear-gradient(95deg, #3b82f6, #2563eb)';
    
    openModal(document.getElementById('appDetailModal'));
}

// Connect/disconnect app
document.getElementById('connectAppBtn')?.addEventListener('click', () => {
    const app = applications.find(a => a.id === currentAppId);
    if(!app) return;
    
    const isConnected = connectedApps.includes(currentAppId);
    if(isConnected) {
        connectedApps = connectedApps.filter(id => id !== currentAppId);
        showToast(`${app.name} a été déconnectée`, 'info');
    } else {
        connectedApps.push(currentAppId);
        showToast(`${app.name} a été connectée avec succès !`, 'success');
    }
    localStorage.setItem('connectedApps', JSON.stringify(connectedApps));
    closeModal(document.getElementById('appDetailModal'));
    renderApps(document.querySelector('.category-btn.active')?.getAttribute('data-category') || 'all');
});

// Category filtering
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const category = btn.getAttribute('data-category');
        renderApps(category);
    });
});

// Modal management
function openModal(modal) { modal?.classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeModal(modal) { modal?.classList.remove('active'); document.body.style.overflow = ''; }

document.getElementById('closeAppModal')?.addEventListener('click', () => closeModal(document.getElementById('appDetailModal')));
document.getElementById('cancelAppModal')?.addEventListener('click', () => closeModal(document.getElementById('appDetailModal')));
document.getElementById('closeConnectModal')?.addEventListener('click', () => closeModal(document.getElementById('connectModal')));

// Manage apps
document.getElementById('manageAppsBtn')?.addEventListener('click', () => {
    showToast('Page de gestion des intégrations ouverte', 'info');
});

// Chatbot
const chatbotFloat = document.getElementById('chatbotFloat');
const chatbotModal = document.getElementById('chatbotModal');
const chatbotClose = document.getElementById('chatbotClose');
const chatbotInput = document.getElementById('chatbotInput');
const chatbotSend = document.getElementById('chatbotSend');
const chatbotMessages = document.getElementById('chatbotMessages');

let chatbotResponses = {
    "bonjour": "Bonjour ! Comment puis-je vous aider ?",
    "aide": "Je peux vous aider avec : les applications, les connexions, les problèmes techniques, ou pour trouver une information.",
    "application": "Nous avons plusieurs applications disponibles : stockage (Dropbox, Google Drive), communication (Slack, Teams), productivité (Trello, Asana), IA (ChatGPT), marketing (Mailchimp).",
    "connecter": "Pour connecter une application, cliquez sur la carte de l'application puis sur le bouton 'Connecter'.",
    "probleme": "Je suis désolé d'apprendre que vous rencontrez un problème. Pouvez-vous me donner plus de détails ?",
    "merci": "Avec plaisir ! N'hésitez pas si vous avez d'autres questions.",
    "au revoir": "Au revoir ! À bientôt sur NexusCRM."
};

function addMessage(text, isUser = true) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;
    messageDiv.innerHTML = `
        <div class="message-avatar"><i class="fas ${isUser ? 'fa-user' : 'fa-robot'}"></i></div>
        <div class="message-content">${text}</div>
    `;
    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function getBotResponse(userMessage) {
    const lowerMsg = userMessage.toLowerCase();
    for(let [key, response] of Object.entries(chatbotResponses)) {
        if(lowerMsg.includes(key)) return response;
    }
    return "Je n'ai pas compris votre demande. Pouvez-vous reformuler ? Ou contactez notre support pour plus d'aide.";
}

function sendMessage() {
    const message = chatbotInput.value.trim();
    if(!message) return;
    addMessage(message, true);
    chatbotInput.value = '';
    setTimeout(() => {
        const response = getBotResponse(message);
        addMessage(response, false);
    }, 500);
}

chatbotFloat?.addEventListener('click', () => {
    chatbotModal.classList.toggle('active');
});

chatbotClose?.addEventListener('click', () => {
    chatbotModal.classList.remove('active');
});

chatbotSend?.addEventListener('click', sendMessage);
chatbotInput?.addEventListener('keypress', (e) => {
    if(e.key === 'Enter') sendMessage();
});

// Search
document.getElementById('searchInput')?.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    const apps = document.querySelectorAll('.app-card');
    apps.forEach(app => {
        const title = app.querySelector('.app-title')?.textContent.toLowerCase() || '';
        const desc = app.querySelector('.app-description')?.textContent.toLowerCase() || '';
        if(title.includes(searchTerm) || desc.includes(searchTerm)) {
            app.style.display = '';
        } else {
            app.style.display = 'none';
        }
    });
});

// Close modals on outside click
window.addEventListener('click', (e) => {
    const detailModal = document.getElementById('appDetailModal');
    const connectModal = document.getElementById('connectModal');
    if(e.target === detailModal) closeModal(detailModal);
    if(e.target === connectModal) closeModal(connectModal);
});

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    showLoader();
    setTimeout(() => {
        renderApps('all');
        hideLoader();
    }, 500);
});