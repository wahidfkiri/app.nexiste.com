// ======================== PAGE PROFIL & SETTINGS - SCRIPT COMPLET ========================

// Loader
const loaderOverlay = document.getElementById('loaderOverlay');

function showLoader() {
    if(loaderOverlay) loaderOverlay.classList.add('active');
}

function hideLoader() {
    if(loaderOverlay) loaderOverlay.classList.remove('active');
}

// ======================== GESTION DES TABS ========================
const tabBtns = document.querySelectorAll('.tab-btn');
const tabPanes = document.querySelectorAll('.tab-pane');

tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const tabId = btn.getAttribute('data-tab');
        
        tabBtns.forEach(b => b.classList.remove('active'));
        tabPanes.forEach(p => p.classList.remove('active'));
        
        btn.classList.add('active');
        document.getElementById(`${tabId}Tab`).classList.add('active');
    });
});

// ======================== CHARGEMENT DES DONNÉES UTILISATEUR ========================
function loadUserData() {
    const firstName = localStorage.getItem('userFirstName') || 'Julien';
    const lastName = localStorage.getItem('userLastName') || 'D.';
    const email = localStorage.getItem('userEmail') || 'julien@nexusdash.com';
    const company = localStorage.getItem('userCompany') || 'NexusDash';
    const position = localStorage.getItem('userPosition') || 'Administrateur principal';
    const phone = localStorage.getItem('userPhone') || '+33 6 12 34 56 78';
    const bio = localStorage.getItem('userBio') || 'Passionné par les technologies et l\'innovation.';
    
    // Mettre à jour l'affichage
    document.getElementById('profileName').textContent = `${firstName} ${lastName}`;
    document.getElementById('profileEmail').textContent = email;
    document.getElementById('avatarInitials').textContent = `${firstName.charAt(0)}${lastName.charAt(0)}`;
    
    // Mettre à jour le header
    const headerAvatar = document.getElementById('headerAvatar');
    const headerUserName = document.getElementById('headerUserName');
    if(headerAvatar) headerAvatar.textContent = `${firstName.charAt(0)}${lastName.charAt(0)}`;
    if(headerUserName) headerUserName.textContent = `${firstName} ${lastName}`;
    
    // Remplir le formulaire
    const editFirstName = document.getElementById('editFirstName');
    const editLastName = document.getElementById('editLastName');
    const editEmail = document.getElementById('editEmail');
    const editCompany = document.getElementById('editCompany');
    const editPosition = document.getElementById('editPosition');
    const editPhone = document.getElementById('editPhone');
    const editBio = document.getElementById('editBio');
    
    if(editFirstName) editFirstName.value = firstName;
    if(editLastName) editLastName.value = lastName;
    if(editEmail) editEmail.value = email;
    if(editCompany) editCompany.value = company;
    if(editPosition) editPosition.value = position;
    if(editPhone) editPhone.value = phone;
    if(editBio) editBio.value = bio;
}

// ======================== SAUVEGARDE DU PROFIL ========================
const profileForm = document.getElementById('profileForm');

if(profileForm) {
    profileForm.addEventListener('submit', (e) => {
        e.preventDefault();
        showLoader();
        
        setTimeout(() => {
            const firstName = document.getElementById('editFirstName').value;
            const lastName = document.getElementById('editLastName').value;
            const email = document.getElementById('editEmail').value;
            const company = document.getElementById('editCompany').value;
            const position = document.getElementById('editPosition').value;
            const phone = document.getElementById('editPhone').value;
            const bio = document.getElementById('editBio').value;
            
            localStorage.setItem('userFirstName', firstName);
            localStorage.setItem('userLastName', lastName);
            localStorage.setItem('userEmail', email);
            localStorage.setItem('userCompany', company);
            localStorage.setItem('userPosition', position);
            localStorage.setItem('userPhone', phone);
            localStorage.setItem('userBio', bio);
            localStorage.setItem('userName', `${firstName} ${lastName}`);
            
            loadUserData();
            
            hideLoader();
            showToast('Profil mis à jour avec succès !', 'success');
        }, 1000);
    });
}

// ======================== ANNULER LES MODIFICATIONS PROFIL ========================
const cancelProfileBtn = document.getElementById('cancelProfileBtn');

if(cancelProfileBtn) {
    cancelProfileBtn.addEventListener('click', () => {
        loadUserData();
        showToast('Modifications annulées', 'info');
    });
}

// ======================== CHANGEMENT DE MOT DE PASSE ========================
const passwordForm = document.getElementById('passwordForm');

if(passwordForm) {
    passwordForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmNewPassword').value;
        
        if(!currentPassword) {
            showToast('Veuillez entrer votre mot de passe actuel', 'error');
            return;
        }
        
        if(newPassword.length < 8) {
            showToast('Le mot de passe doit contenir au moins 8 caractères', 'error');
            return;
        }
        
        if(!/[A-Z]/.test(newPassword)) {
            showToast('Le mot de passe doit contenir au moins une majuscule', 'error');
            return;
        }
        
        if(!/[0-9]/.test(newPassword)) {
            showToast('Le mot de passe doit contenir au moins un chiffre', 'error');
            return;
        }
        
        if(newPassword !== confirmPassword) {
            showToast('Les mots de passe ne correspondent pas', 'error');
            return;
        }
        
        showLoader();
        
        setTimeout(() => {
            hideLoader();
            showToast('Mot de passe modifié avec succès !', 'success');
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmNewPassword').value = '';
        }, 1000);
    });
}

// ======================== ANNULER CHANGEMENT MOT DE PASSE ========================
const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');

if(cancelPasswordBtn) {
    cancelPasswordBtn.addEventListener('click', () => {
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmNewPassword').value = '';
        document.getElementById('strengthProgress').style.width = '0%';
        document.getElementById('strengthText').textContent = '';
        showToast('Champs réinitialisés', 'info');
    });
}

// ======================== FORCE DU MOT DE PASSE ========================
const newPasswordInput = document.getElementById('newPassword');
const strengthProgress = document.getElementById('strengthProgress');
const strengthText = document.getElementById('strengthText');

function checkPasswordStrength(password) {
    let strength = 0;
    let message = '';
    let color = '';
    
    if(password.length === 0) {
        if(strengthProgress) strengthProgress.style.width = '0%';
        if(strengthText) strengthText.textContent = '';
        return;
    }
    
    // Length check
    if(password.length >= 8) strength++;
    if(password.length >= 12) strength++;
    
    // Character variety
    if(/[a-z]/.test(password)) strength++;
    if(/[A-Z]/.test(password)) strength++;
    if(/[0-9]/.test(password)) strength++;
    if(/[^a-zA-Z0-9]/.test(password)) strength++;
    
    // Determine strength
    if(strength <= 2) {
        message = 'Très faible';
        color = '#ef4444';
        if(strengthProgress) strengthProgress.style.width = '20%';
    } else if(strength <= 4) {
        message = 'Faible';
        color = '#f59e0b';
        if(strengthProgress) strengthProgress.style.width = '40%';
    } else if(strength <= 6) {
        message = 'Moyen';
        color = '#eab308';
        if(strengthProgress) strengthProgress.style.width = '60%';
    } else if(strength <= 8) {
        message = 'Fort';
        color = '#10b981';
        if(strengthProgress) strengthProgress.style.width = '80%';
    } else {
        message = 'Très fort';
        color = '#059669';
        if(strengthProgress) strengthProgress.style.width = '100%';
    }
    
    if(strengthProgress) strengthProgress.style.backgroundColor = color;
    if(strengthText) {
        strengthText.textContent = `Force du mot de passe : ${message}`;
        strengthText.style.color = color;
    }
}

if(newPasswordInput) {
    newPasswordInput.addEventListener('input', (e) => checkPasswordStrength(e.target.value));
}

// ======================== TOGGLE PASSWORD ========================
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if(input) {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            button.querySelector('i').classList.toggle('fa-eye');
            button.querySelector('i').classList.toggle('fa-eye-slash');
        }
    });
});

// ======================== THÈMES ========================
const themeCards = document.querySelectorAll('.theme-card');
let currentTheme = localStorage.getItem('theme') || 'light';

function applyTheme(theme) {
    if(theme === 'dark') {
        document.body.classList.add('dark-theme');
    } else {
        document.body.classList.remove('dark-theme');
    }
    
    themeCards.forEach(card => {
        if(card.getAttribute('data-theme') === theme) {
            card.classList.add('active');
        } else {
            card.classList.remove('active');
        }
    });
}

themeCards.forEach(card => {
    card.addEventListener('click', () => {
        const theme = card.getAttribute('data-theme');
        currentTheme = theme;
        localStorage.setItem('theme', theme);
        applyTheme(theme);
        showToast(`Thème ${theme === 'light' ? 'clair' : theme === 'dark' ? 'sombre' : 'automatique'} activé`, 'success');
    });
});

applyTheme(currentTheme);

// ======================== NOTIFICATIONS ========================
function loadNotificationSettings() {
    const settings = JSON.parse(localStorage.getItem('notificationSettings') || '{}');
    const emailToggle = document.getElementById('emailNotificationsToggle');
    const pushToggle = document.getElementById('pushNotificationsToggle');
    const newsletterToggle = document.getElementById('newsletterToggle');
    const newOrdersToggle = document.getElementById('newOrdersToggle');
    const newUsersToggle = document.getElementById('newUsersToggle');
    const monthlyReportToggle = document.getElementById('monthlyReportToggle');
    
    if(emailToggle) emailToggle.checked = settings.email !== false;
    if(pushToggle) pushToggle.checked = settings.push || false;
    if(newsletterToggle) newsletterToggle.checked = settings.newsletter !== false;
    if(newOrdersToggle) newOrdersToggle.checked = settings.newOrders !== false;
    if(newUsersToggle) newUsersToggle.checked = settings.newUsers !== false;
    if(monthlyReportToggle) monthlyReportToggle.checked = settings.monthlyReport !== false;
}

function saveNotificationSettings() {
    const settings = {
        email: document.getElementById('emailNotificationsToggle')?.checked,
        push: document.getElementById('pushNotificationsToggle')?.checked,
        newsletter: document.getElementById('newsletterToggle')?.checked,
        newOrders: document.getElementById('newOrdersToggle')?.checked,
        newUsers: document.getElementById('newUsersToggle')?.checked,
        monthlyReport: document.getElementById('monthlyReportToggle')?.checked
    };
    localStorage.setItem('notificationSettings', JSON.stringify(settings));
}

document.querySelectorAll('#notificationsTab .switch input').forEach(toggle => {
    toggle.addEventListener('change', () => {
        saveNotificationSettings();
        showToast('Préférences de notification sauvegardées', 'success');
    });
});

// ======================== PRÉFÉRENCES ========================
function loadPreferences() {
    const animations = localStorage.getItem('animations') !== 'false';
    const compactMode = localStorage.getItem('compactMode') === 'true';
    const language = localStorage.getItem('language') || 'fr';
    const timezone = localStorage.getItem('timezone') || 'Europe/Paris';
    
    const animationsToggle = document.getElementById('animationsToggle');
    const compactModeToggle = document.getElementById('compactModeToggle');
    const languageSelect = document.getElementById('languageSelect');
    const timezoneSelect = document.getElementById('timezoneSelect');
    
    if(animationsToggle) animationsToggle.checked = animations;
    if(compactModeToggle) compactModeToggle.checked = compactMode;
    if(languageSelect) languageSelect.value = language;
    if(timezoneSelect) timezoneSelect.value = timezone;
    
    if(compactMode) {
        document.body.classList.add('compact-mode');
    }
}

function savePreferences() {
    const animations = document.getElementById('animationsToggle')?.checked;
    const compactMode = document.getElementById('compactModeToggle')?.checked;
    const language = document.getElementById('languageSelect')?.value;
    const timezone = document.getElementById('timezoneSelect')?.value;
    
    if(animations !== undefined) localStorage.setItem('animations', animations);
    if(compactMode !== undefined) localStorage.setItem('compactMode', compactMode);
    if(language) localStorage.setItem('language', language);
    if(timezone) localStorage.setItem('timezone', timezone);
    
    if(compactMode) {
        document.body.classList.add('compact-mode');
    } else {
        document.body.classList.remove('compact-mode');
    }
    
    showToast('Préférences sauvegardées', 'success');
}

document.querySelectorAll('#preferencesTab select, #preferencesTab .switch input').forEach(element => {
    element.addEventListener('change', savePreferences);
});

// ======================== AVATAR MODAL ========================
const editAvatarBtn = document.getElementById('editAvatarBtn');
const avatarModal = document.getElementById('avatarModal');
const closeAvatarModal = document.getElementById('closeAvatarModal');
const cancelAvatarBtn = document.getElementById('cancelAvatarBtn');
const saveAvatarBtn = document.getElementById('saveAvatarBtn');
const avatarUpload = document.getElementById('avatarUpload');
const previewInitials = document.getElementById('previewInitials');
let selectedAvatarType = 'initials';
let uploadedImageData = null;

function openAvatarModal() {
    if(avatarModal) {
        avatarModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        // Reset preview
        const preview = document.getElementById('avatarPreview');
        if(preview) {
            preview.style.backgroundImage = '';
            preview.style.backgroundColor = '';
            if(previewInitials) {
                previewInitials.style.display = 'flex';
                const initials = document.getElementById('avatarInitials')?.textContent || 'JD';
                previewInitials.textContent = initials;
            }
        }
    }
}

function closeAvatarModalFunc() {
    if(avatarModal) {
        avatarModal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

if(editAvatarBtn) editAvatarBtn.addEventListener('click', openAvatarModal);
if(closeAvatarModal) closeAvatarModal.addEventListener('click', closeAvatarModalFunc);
if(cancelAvatarBtn) cancelAvatarBtn.addEventListener('click', closeAvatarModalFunc);

document.querySelectorAll('.avatar-option').forEach(option => {
    option.addEventListener('click', () => {
        selectedAvatarType = option.getAttribute('data-avatar-type');
        if(selectedAvatarType === 'upload' && avatarUpload) {
            avatarUpload.click();
        } else if(selectedAvatarType === 'initials') {
            const preview = document.getElementById('avatarPreview');
            if(preview) {
                preview.style.backgroundImage = '';
                preview.style.backgroundColor = '';
                if(previewInitials) {
                    previewInitials.style.display = 'flex';
                    const initials = document.getElementById('avatarInitials')?.textContent || 'JD';
                    previewInitials.textContent = initials;
                }
            }
            uploadedImageData = null;
        }
    });
});

if(avatarUpload) {
    avatarUpload.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if(file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                const preview = document.getElementById('avatarPreview');
                if(preview) {
                    preview.style.backgroundImage = `url(${event.target.result})`;
                    preview.style.backgroundSize = 'cover';
                    preview.style.backgroundPosition = 'center';
                    if(previewInitials) previewInitials.style.display = 'none';
                    uploadedImageData = event.target.result;
                }
            };
            reader.readAsDataURL(file);
        }
    });
}

if(saveAvatarBtn) {
    saveAvatarBtn.addEventListener('click', () => {
        if(uploadedImageData) {
            localStorage.setItem('userAvatar', uploadedImageData);
            const profileAvatar = document.getElementById('profileAvatar');
            const headerAvatar = document.getElementById('headerAvatar');
            if(profileAvatar) {
                profileAvatar.style.backgroundImage = `url(${uploadedImageData})`;
                profileAvatar.style.backgroundSize = 'cover';
                profileAvatar.style.backgroundPosition = 'center';
                const avatarInitials = document.getElementById('avatarInitials');
                if(avatarInitials) avatarInitials.style.display = 'none';
            }
            if(headerAvatar) {
                headerAvatar.style.backgroundImage = `url(${uploadedImageData})`;
                headerAvatar.style.backgroundSize = 'cover';
                headerAvatar.style.backgroundPosition = 'center';
                headerAvatar.textContent = '';
            }
        } else {
            // Reset to initials
            const profileAvatar = document.getElementById('profileAvatar');
            const headerAvatar = document.getElementById('headerAvatar');
            const initials = document.getElementById('avatarInitials')?.textContent || 'JD';
            if(profileAvatar) {
                profileAvatar.style.backgroundImage = '';
                if(avatarInitials) avatarInitials.style.display = 'flex';
            }
            if(headerAvatar) {
                headerAvatar.style.backgroundImage = '';
                headerAvatar.textContent = initials;
            }
            localStorage.removeItem('userAvatar');
        }
        showToast('Photo de profil mise à jour', 'success');
        closeAvatarModalFunc();
    });
}

// ======================== SUPPRESSION DE COMPTE ========================
const deleteAccountBtn = document.getElementById('deleteAccountBtn');
const deleteModal = document.getElementById('deleteModal');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const deleteConfirmInput = document.getElementById('deleteConfirmInput');

function openDeleteModal() {
    if(deleteModal) {
        deleteModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeDeleteModal() {
    if(deleteModal) {
        deleteModal.classList.remove('active');
        document.body.style.overflow = '';
        if(deleteConfirmInput) deleteConfirmInput.value = '';
        if(confirmDeleteBtn) confirmDeleteBtn.disabled = true;
    }
}

if(deleteAccountBtn) deleteAccountBtn.addEventListener('click', openDeleteModal);
if(cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteModal);

if(deleteConfirmInput) {
    deleteConfirmInput.addEventListener('input', (e) => {
        if(confirmDeleteBtn) {
            confirmDeleteBtn.disabled = e.target.value !== 'SUPPRIMER';
        }
    });
}

if(confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', () => {
        showLoader();
        setTimeout(() => {
            localStorage.clear();
            sessionStorage.clear();
            hideLoader();
            window.location.href = 'login.html';
        }, 1500);
    });
}

// ======================== 2FA ========================
const enableTwofaBtn = document.getElementById('enableTwofaBtn');

if(enableTwofaBtn) {
    enableTwofaBtn.addEventListener('click', () => {
        showToast('Fonctionnalité 2FA bientôt disponible', 'info');
    });
}

// ======================== TOAST NOTIFICATION ========================
function showToast(message, type = 'success') {
    // Remove existing toast
    const existingToast = document.querySelector('.toast-notification');
    if(existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// ======================== FERMETURE DES MODALS PAR CLIC EXTÉRIEUR ========================
window.addEventListener('click', (e) => {
    if(e.target === avatarModal) closeAvatarModalFunc();
    if(e.target === deleteModal) closeDeleteModal();
});

// ======================== INITIALISATION ========================
document.addEventListener('DOMContentLoaded', () => {
    loadUserData();
    loadNotificationSettings();
    loadPreferences();
    
    // Charger l'avatar sauvegardé
    const savedAvatar = localStorage.getItem('userAvatar');
    if(savedAvatar) {
        const profileAvatar = document.getElementById('profileAvatar');
        const headerAvatar = document.getElementById('headerAvatar');
        const avatarInitials = document.getElementById('avatarInitials');
        if(profileAvatar) {
            profileAvatar.style.backgroundImage = `url(${savedAvatar})`;
            profileAvatar.style.backgroundSize = 'cover';
            profileAvatar.style.backgroundPosition = 'center';
            if(avatarInitials) avatarInitials.style.display = 'none';
        }
        if(headerAvatar) {
            headerAvatar.style.backgroundImage = `url(${savedAvatar})`;
            headerAvatar.style.backgroundSize = 'cover';
            headerAvatar.style.backgroundPosition = 'center';
            headerAvatar.textContent = '';
        }
    }
    
    // Animation d'entrée
    const cards = document.querySelectorAll('.settings-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    showLoader();
    setTimeout(() => {
        hideLoader();
    }, 500);
});

// ======================== RECHERCHE (intégration avec main.js) ========================
const searchInput = document.getElementById('searchInput');
const suggestionsBox = document.getElementById('suggestionsDropdown');

if(searchInput && suggestionsBox) {
    const searchSuggestions = [
        { name: "Mon profil", type: "page", icon: "fa-user", link: "profile-settings.html" },
        { name: "Paramètres", type: "page", icon: "fa-cog", link: "profile-settings.html" },
        { name: "Sécurité", type: "page", icon: "fa-shield-alt", link: "profile-settings.html" },
        { name: "Notifications", type: "page", icon: "fa-bell", link: "profile-settings.html" }
    ];
    
    function showSuggestions(filter = "") {
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
            <div class="suggestion-item" data-link="${s.link}">
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
                const link = el.getAttribute('data-link');
                if(link) {
                    window.location.href = link;
                }
                suggestionsBox.style.display = 'none';
                searchInput.value = '';
            });
        });
    }
    
    searchInput.addEventListener('input', (e) => showSuggestions(e.target.value));
    document.addEventListener('click', (e) => {
        if(!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
}

// ======================== SIDEBAR MOBILE (si pas dans main.js) ========================
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const mobileMenuBtn = document.getElementById('mobileMenuBtn');

function closeSidebar() {
    if(window.innerWidth <= 992) {
        if(sidebar) sidebar.classList.remove('open-mobile');
        if(overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function openSidebar() {
    if(window.innerWidth <= 992) {
        if(sidebar) sidebar.classList.add('open-mobile');
        if(overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function toggleSidebar() {
    if(sidebar && sidebar.classList.contains('open-mobile')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

if(mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', toggleSidebar);
}

if(overlay) {
    overlay.addEventListener('click', closeSidebar);
}

window.addEventListener('resize', () => {
    if(window.innerWidth > 992) {
        if(sidebar) sidebar.classList.remove('open-mobile');
        if(overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});