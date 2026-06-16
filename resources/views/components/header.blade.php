<div class="top-header">
    <div class="d-flex align-items-center gap-3">
        <button class="menu-toggle" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-bar" id="searchInput" placeholder="Rechercher commandes, clients...">
            <div class="suggestions-dropdown" id="suggestionsDropdown"></div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 profile-area-buttons">
        <!-- Messages Dropdown -->
        <div class="dropdown">
            <button class="dropdown-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="far fa-envelope fa-lg"></i>
                <span class="badge-notif" style="background:#10b981;">2</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
                <li class="dropdown-header-custom"><i class="far fa-comment-dots me-2"></i> Messages récents</li>
                <li><a class="dropdown-item message-item" href="#"><div class="d-flex gap-2"><div class="avatar-img" style="width:36px;height:36px;font-size:0.8rem;">SM</div><div><strong>Sophie Martin</strong><br><small>Bonjour, concernant la facture...</small></div></div></a></li>
                <li><a class="dropdown-item message-item" href="#"><div class="d-flex gap-2"><div class="avatar-img" style="width:36px;height:36px;background:#8b5cf6;">AL</div><div><strong>Alexandre Legrand</strong><br><small>Réunion à 14h aujourd'hui ✅</small></div></div></a></li>
                <li class="dropdown-footer"><a href="#" class="text-decoration-none small">Voir tous les messages →</a></li>
            </ul>
        </div>
        <!-- Notifications Dropdown -->
        <div class="dropdown">
            <button class="dropdown-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="far fa-bell fa-lg"></i>
                <span class="badge-notif">3</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
                <li class="dropdown-header-custom"><i class="fas fa-bell me-2"></i> Notifications</li>
                <li><a class="dropdown-item notification-item" href="#"><i class="fas fa-chart-line me-2 text-primary"></i> Ventes en hausse de 23% cette semaine</a></li>
                <li><a class="dropdown-item notification-item" href="#"><i class="fas fa-user-check me-2 text-success"></i> Nouvel utilisateur inscrit</a></li>
                <li><a class="dropdown-item notification-item" href="#"><i class="fas fa-clock me-2 text-warning"></i> Maintenance planifiée le 20/04</a></li>
                <li class="dropdown-footer"><a href="#" class="text-decoration-none small">Marquer tout comme lu</a></li>
            </ul>
        </div>
        <!-- Profil Dropdown -->
        <div class="dropdown">
            <div class="avatar-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar-img">{{ Auth::user()->getInitialsAttribute() }}</div>
                <div class="d-none d-md-block text-start">
                    <div class="fw-semibold small">{{ Auth::user()->name }}</div>
                    <small class="text-muted" style="font-size: 0.7rem;">{{ Auth::user()->getRoleNames()->first() ?? 'Utilisateur' }}</small>
                </div>
                <i class="fas fa-chevron-down fa-xs text-muted"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom" style="min-width: 220px;">
                <li><a class="dropdown-item" href="{{ route('profile-settings') }}"><i class="far fa-user-circle me-2"></i> Mon profil</a></li>
                <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="fas fa-chart-simple me-2"></i> Tableau de bord</a></li>
                <li><a class="dropdown-item" href="{{ route('profile-settings') }}"><i class="fas fa-cog me-2"></i> Paramètres</a></li>
                <li><hr class="dropdown-divider"></li>
                <!-- Formulaire de déconnexion -->
                <li>
                    <form method="POST" action="{{ route('logout') }}" id="logout-form">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger" style="width: 100%; text-align: left; background: none; border: none;">
                            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</div>