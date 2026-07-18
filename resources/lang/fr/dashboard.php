<?php

return [
    'page_title' => 'Tableau de bord',
    'welcome' => 'Bienvenue, :name',
    'session_expired' => 'Session expirée',
    'access_denied' => 'Vous n’avez pas accès à cet espace.',

    'meta' => [
        'title' => 'Tableau de bord',
        'subtitle' => 'Une vue exécutive, rapide et actionnable sur votre activité, vos modules et vos intégrations.',
        'tenant_fallback' => 'Espace CRM',
        'member' => 'Membre',
    ],

    'actions' => [
        'fallback' => 'Action',
        'new_client' => 'Nouveau client',
        'new_invoice' => 'Nouvelle facture',
        'projects' => 'Projets',
        'applications' => 'Applications',
        'settings' => 'Paramètres',
    ],

    'command' => [
        'modules' => 'Modules',
        'modules_active' => ':count actifs',
        'currency' => 'Devise',
        'integrations' => 'Intégrations',
        'date' => 'Date',
    ],

    'signals' => [
        'aria_label' => 'Indicateurs clés',
        'fallback_label' => 'Indicateur',
        'revenue_month' => 'CA du mois',
        'payments' => 'Encaissements',
        'clients' => 'Clients',
        'clients_hint' => '+:count ce mois',
        'priorities' => 'Priorités',
        'priorities_hint' => 'Tâches, stock et factures à traiter',
        'integrations' => 'Intégrations',
        'integrations_hint' => ':count à reconnecter',
    ],

    'finance' => [
        'kicker' => 'Performance',
        'title' => 'Finance du mois',
        'view_invoices' => 'Voir factures',
        'issued_revenue' => 'CA émis',
        'collected' => 'Encaissé',
        'pending' => 'Reste dû',
    ],

    'modules' => [
        'kicker' => 'Workspace',
        'title' => 'Modules actifs',
        'fallback_label' => 'Module',
        'empty_title' => 'Aucun module visible',
        'empty_description' => 'Activez des applications ou vérifiez les permissions du rôle.',
        'clients' => [
            'name' => 'Clients',
            'label' => 'CRM',
            'caption' => ':count nouveaux ce mois',
        ],
        'invoice' => [
            'name' => 'Facturation',
            'label' => 'Finance',
            'caption' => ':count factures ouvertes',
        ],
        'projects' => [
            'name' => 'Projets',
            'label' => 'Delivery',
            'caption' => ':count tâches urgentes',
        ],
        'stock' => [
            'name' => 'Stock',
            'label' => 'Opérations',
            'caption' => ':count articles critiques',
        ],
    ],

    'focus' => [
        'kicker' => 'À traiter',
        'title' => 'Priorités opérationnelles',
        'fallback_kind' => 'Priorité',
        'fallback_title' => 'Action',
        'empty_title' => 'Tout est calme',
        'empty_description' => 'Aucune priorité critique pour le moment.',
        'open_invoice' => 'Facture ouverte',
        'invoice_fallback' => 'Facture',
        'missing_client' => 'Client non renseigné',
        'upcoming_task' => 'Tâche proche',
        'missing_project' => 'Projet non défini',
        'critical_stock' => 'Stock critique',
        'article_fallback' => 'Article sans nom',
        'missing_sku' => 'Sans SKU',
    ],

    'activity' => [
        'kicker' => 'Timeline',
        'title' => 'Activité récente',
        'fallback_event' => 'Événement',
        'empty_title' => 'Aucune activité',
        'empty_description' => 'Les événements de l’espace seront listés ici.',
        'client_created' => 'Client ajouté',
        'client_fallback' => 'Client sans nom',
        'invoice_created' => 'Facture créée',
        'invoice_fallback' => 'Facture',
        'project_updated' => 'Projet mis à jour',
        'project_update_fallback' => 'Mise à jour',
        'draft_resume' => 'Brouillon à reprendre',
        'draft_description' => ':type non finalisé',
        'app_active' => 'Application active',
        'app_fallback' => 'Application',
    ],

    'charts' => [
        'finance' => [
            'invoices' => 'Factures',
            'payments' => 'Encaissements',
        ],
        'projects' => [
            'todo' => 'À faire',
            'in_progress' => 'En cours',
            'review' => 'En revue',
            'done' => 'Terminées',
        ],
        'stock' => [
            'critical' => 'Critique',
            'healthy' => 'Sain',
        ],
        'integrations' => [
            'connected' => 'Connectées',
            'attention' => 'À reconnecter',
            'installed' => 'À configurer',
        ],
    ],

    'integrations' => [
        'states' => [
            'installed' => 'À configurer',
            'internal' => 'Interne',
            'connected' => 'Connectée',
            'attention' => 'À reconnecter',
        ],
        'resources' => [
            'pages' => 'pages',
            'boards' => 'tableaux',
            'files' => 'fichiers',
            'events' => 'événements',
            'spreadsheets' => 'tableurs',
            'documents' => 'documents',
            'messages' => 'messages',
            'meetings' => 'réunions',
        ],
        'names' => [
            'notion' => 'Notion',
            'trello' => 'Trello',
            'drive' => 'Drive',
            'dropbox' => 'Dropbox',
            'calendar' => 'Calendar',
            'sheets' => 'Sheets',
            'docs' => 'Docs',
            'gmail' => 'Gmail',
            'meet' => 'Meet',
            'slack' => 'Slack',
            'chatbot' => 'Chatbot',
        ],
    ],

    'trend' => [
        'stable' => 'Stable ce mois',
        'new_growth' => '+100% vs mois dernier',
        'vs_previous' => ':percent% vs mois dernier',
    ],
];
