<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration CRM Core
    |--------------------------------------------------------------------------
    */

    // Modèle Tenant (Entreprise)
    'tenant_model' => \Vendor\CrmCore\Models\Tenant::class,

    // Tables
    'table_names' => [
        'tenants' => 'tenants',
        'tenant_settings' => 'tenant_settings',
    ],

    // Configuration multi-tenant
    'multi_tenant' => [
        'enabled' => true,
        'auto_detect' => true, // Détecter automatiquement le tenant via l'utilisateur connecté
        'tenant_id_column' => 'tenant_id',
    ],

    // Types de tenants
    'tenant_types' => [
        'enterprise' => 'Entreprise',
        'freelance' => 'Freelance',
        'association' => 'Association',
        'public' => 'Public',
    ],

    // Statuts des tenants
    'tenant_statuses' => [
        'active' => 'Actif',
        'suspended' => 'Suspendu',
        'pending' => 'En attente',
        'expired' => 'Expiré',
    ],

    // Rôles dans le tenant
    'tenant_roles' => [
        'owner' => 'Propriétaire',
        'admin' => 'Administrateur',
        'manager' => 'Gestionnaire',
        'user' => 'Utilisateur',
        'viewer' => 'Visiteur',
    ],

    // Abonnement
    'subscription' => [
        'enabled' => false,
        'plans' => [
            'free' => [
                'name' => 'Gratuit',
                'price' => 0,
                'max_users' => 5,
                'max_clients' => 50,
                'max_invoices' => 100,
                'features' => ['clients', 'invoices', 'basic_reports'],
            ],
            'pro' => [
                'name' => 'Professionnel',
                'price' => 49,
                'max_users' => 20,
                'max_clients' => 500,
                'max_invoices' => 1000,
                'features' => ['clients', 'invoices', 'advanced_reports', 'api_access', 'email_marketing'],
            ],
            'enterprise' => [
                'name' => 'Entreprise',
                'price' => 99,
                'max_users' => -1, // illimité
                'max_clients' => -1,
                'max_invoices' => -1,
                'features' => ['all'],
            ],
        ],
        'trial_days' => 14,
        'currency' => 'EUR',
    ],

    // Configuration des sessions
    'session' => [
        'tenant_key' => 'current_tenant_id',
        'store_in_session' => true,
    ],

    // Cache
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // secondes
        'tenant_prefix' => 'tenant_',
        'settings_prefix' => 'tenant_settings_',
    ],

    // Middleware
    'middleware' => [
        'tenant' => \Vendor\CrmCore\Http\Middleware\TenantMiddleware::class,
        'tenant_owner' => \Vendor\CrmCore\Http\Middleware\TenantOwnerMiddleware::class,
    ],

    // Routes
    'routes' => [
        'enabled' => true,
        'prefix' => 'tenant',
        'middleware' => ['web', 'auth'],
    ],

    // URL de redirection après sélection du tenant
    'redirect_after_tenant_select' => '/dashboard',

    // URL de redirection si aucun tenant
    'redirect_if_no_tenant' => '/tenants/select',

    // Événements
    'events' => [
        'tenant_created' => \Vendor\CrmCore\Events\TenantCreated::class,
        'tenant_updated' => \Vendor\CrmCore\Events\TenantUpdated::class,
        'tenant_deleted' => \Vendor\CrmCore\Events\TenantDeleted::class,
        'tenant_activated' => \Vendor\CrmCore\Events\TenantActivated::class,
        'tenant_suspended' => \Vendor\CrmCore\Events\TenantSuspended::class,
    ],

    // Notifications
    'notifications' => [
        'on_tenant_created' => true,
        'on_tenant_suspended' => true,
        'on_subscription_expiring' => true,
        'subscription_expiring_days' => [7, 3, 1],
    ],

    // Logs
    'logging' => [
        'enabled' => true,
        'channel' => 'daily',
        'events' => ['created', 'updated', 'deleted', 'suspended', 'activated'],
    ],

    // Backup
    'backup' => [
        'enabled' => false,
        'frequency' => 'daily', // hourly, daily, weekly, monthly
        'keep_days' => 30,
    ],

    // API
    'api' => [
        'rate_limit' => 60, // requêtes par minute
        'version' => 'v1',
        'prefix' => 'api/v1',
    ],

    // Frontend
    'frontend' => [
        'dashboard_components' => [
            'stats_widget' => true,
            'recent_clients_widget' => true,
            'recent_invoices_widget' => true,
            'charts_widget' => true,
        ],
        'per_page_options' => [10, 25, 50, 100],
        'default_per_page' => 15,
    ],

    // Thème
    'theme' => [
        'primary_color' => '#3b82f6',
        'secondary_color' => '#8b5cf6',
        'logo' => '/images/logo.png',
        'favicon' => '/images/favicon.ico',
    ],

    // Modules activés
    'modules' => [
        'clients' => true,
        'invoices' => true,
        'products' => true,
        'projects' => false,
        'tickets' => false,
        'reports' => true,
        'api' => true,
    ],

    // Intégrations tierces
    'integrations' => [
        'google' => [
            'enabled' => false,
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        ],
        'facebook' => [
            'enabled' => false,
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
        ],
        'stripe' => [
            'enabled' => false,
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
        ],
    ],

    // Validation des données
    'validation' => [
        'tenant_name_max' => 255,
        'tenant_slug_max' => 255,
        'tenant_email_max' => 255,
        'tenant_phone_max' => 20,
        'tenant_address_max' => 500,
    ],

    // Formats
    'formats' => [
        'date' => 'd/m/Y',
        'datetime' => 'd/m/Y H:i:s',
        'time' => 'H:i:s',
        'currency' => 'EUR',
        'currency_symbol' => '€',
        'currency_position' => 'after', // before, after
        'thousands_separator' => ' ',
        'decimal_separator' => ',',
        'decimals' => 2,
    ],

    // Timezone par défaut
    'default_timezone' => 'Europe/Paris',

    // Langue par défaut
    'default_locale' => 'fr',

    // Langues disponibles
    'available_locales' => [
        'fr' => 'Français',
        'en' => 'English',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
    ],
];