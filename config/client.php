<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du module Client
    |--------------------------------------------------------------------------
    */

    // Modèle Client
    'model' => \Vendor\Client\Models\Client::class,

    // Tables
    'table_names' => [
        'clients' => 'clients',
        'client_contacts' => 'client_contacts',
        'client_activities' => 'client_activities',
    ],

    // Pagination
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],

    // Types de clients
    'client_types' => [
        'entreprise' => 'Entreprise',
        'particulier' => 'Particulier',
        'startup' => 'Startup',
        'association' => 'Association',
        'public' => 'Public',
    ],

    // Statuts des clients
    'client_statuses' => [
        'actif' => 'Actif',
        'inactif' => 'Inactif',
        'en_attente' => 'En attente',
        'suspendu' => 'Suspendu',
    ],

    // Sources d'acquisition
    'client_sources' => [
        'direct' => 'Direct',
        'site_web' => 'Site Web',
        'reference' => 'Recommandation',
        'reseau_social' => 'Réseau social',
        'autre' => 'Autre',
    ],

    // Export
    'export' => [
        'formats' => ['csv', 'excel', 'pdf'],
        'default_format' => 'excel',
        'chunk_size' => 1000,
    ],

    // Import
    'import' => [
        'max_file_size' => 10240,
        'allowed_extensions' => ['csv', 'xlsx', 'xls'],
        'chunk_size' => 500,
    ],

    // Validation
    'validation' => [
        'company_name_max' => 255,
        'contact_name_max' => 255,
        'email_max' => 255,
        'phone_max' => 20,
        'notes_max' => 5000,
    ],

    // Filtres par défaut
    'default_filters' => [
        'status' => 'actif',
        'sort_by' => 'created_at',
        'sort_order' => 'desc',
    ],

    // Cache
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'client_',
    ],

    // Activités
    'log_activities' => true,

    // Notifications
    'notifications' => [
        'on_create' => true,
        'on_update' => true,
        'on_delete' => false,
        'channels' => ['database', 'mail'],
    ],
];