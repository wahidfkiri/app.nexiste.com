<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Package Extensions / Marketplace
    |--------------------------------------------------------------------------
    | Géré par le super-admin. Chaque tenant peut activer/désactiver des
    | extensions (gratuites ou payantes) depuis son interface.
    |--------------------------------------------------------------------------
    */

    // Catégories d'extensions
    'categories' => [
        'storage'       => ['label' => 'Stockage',       'icon' => 'fa-database',       'color' => '#3b82f6'],
        'communication' => ['label' => 'Communication',  'icon' => 'fa-comment-dots',   'color' => '#10b981'],
        'productivity'  => ['label' => 'Productivité',   'icon' => 'fa-rocket',         'color' => '#8b5cf6'],
        'ai'            => ['label' => 'IA & Chatbot',   'icon' => 'fa-robot',          'color' => '#f59e0b'],
        'marketing'     => ['label' => 'Marketing',      'icon' => 'fa-bullhorn',       'color' => '#ef4444'],
        'finance'       => ['label' => 'Finance',        'icon' => 'fa-chart-pie',      'color' => '#06b6d4'],
        'security'      => ['label' => 'Sécurité',       'icon' => 'fa-shield-halved',  'color' => '#6366f1'],
        'analytics'     => ['label' => 'Analytics',      'icon' => 'fa-chart-line',     'color' => '#84cc16'],
        'integration'   => ['label' => 'Intégrations',   'icon' => 'fa-plug',           'color' => '#f97316'],
        'other'         => ['label' => 'Autre',          'icon' => 'fa-puzzle-piece',   'color' => '#64748b'],
    ],

    // Statuts d'une extension (dans le catalogue)
    'extension_statuses' => [
        'active'     => 'Active',
        'inactive'   => 'Inactive',
        'deprecated' => 'Dépréciée',
        'beta'       => 'Bêta',
        'coming_soon'=> 'Bientôt disponible',
    ],

    // Statuts d'une activation tenant
    'activation_statuses' => [
        'active'    => 'Active',
        'inactive'  => 'Inactive',
        'suspended' => 'Suspendue',
        'pending'   => 'En attente',
        'trial'     => 'Essai',
        'expired'   => 'Expirée',
    ],

    // Plans tarifaires
    'pricing_types' => [
        'free'       => 'Gratuit',
        'paid'       => 'Payant',
        'freemium'   => 'Freemium',
        'trial'      => 'Essai gratuit',
        'per_seat'   => 'Par utilisateur',
        'usage'      => 'À l\'usage',
    ],

    // Cycles de facturation
    'billing_cycles' => [
        'monthly'  => 'Mensuel',
        'yearly'   => 'Annuel',
        'lifetime' => 'À vie',
        'once'     => 'Paiement unique',
    ],

    // Période d'essai gratuit par défaut (jours)
    'default_trial_days' => 14,

    // Pagination
    'pagination' => [
        'per_page'     => 20,
        'max_per_page' => 100,
    ],

    // Cache
    'cache' => [
        'enabled' => true,
        'ttl'     => 3600,
        'prefix'  => 'extensions_',
    ],

    // Upload
    'upload' => [
        'disk'        => 'public',
        'icon_path'   => 'extensions/icons',
        'banner_path' => 'extensions/banners',
        'max_size_kb' => 2048,
    ],

    // Webhook events envoyés aux extensions
    'webhook_events' => [
        'extension.activated',
        'extension.deactivated',
        'extension.suspended',
        'extension.configured',
    ],

    // Logs
    'log_activities' => true,
];