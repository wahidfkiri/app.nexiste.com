<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du module Client
    |--------------------------------------------------------------------------
    */

    'model' => \Vendor\Client\Models\Client::class,

    'table_names' => [
        'clients' => 'clients',
        'client_contacts' => 'client_contacts',
        'client_activities' => 'client_activities',
    ],

    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],

    'client_types' => [
        'entreprise' => 'Entreprise',
        'particulier' => 'Particulier',
        'startup' => 'Startup',
        'association' => 'Association',
        'public' => 'Public',
    ],

    'client_statuses' => [
        'actif' => 'Actif',
        'inactif' => 'Inactif',
        'en_attente' => 'En attente',
        'suspendu' => 'Suspendu',
    ],

    'client_sources' => [
        'direct' => 'Direct',
        'site_web' => 'Site web',
        'reference' => 'Recommandation',
        'reseau_social' => 'Réseau social',
        'autre' => 'Autre',
    ],

    'export' => [
        'formats' => ['csv', 'excel', 'pdf'],
        'default_format' => 'excel',
        'chunk_size' => 1000,
    ],

    'import' => [
        'max_file_size' => 10240,
        'allowed_extensions' => ['csv', 'xlsx', 'xls'],
        'chunk_size' => 500,
    ],

    'validation' => [
        'company_name_max' => 255,
        'contact_name_max' => 255,
        'email_max' => 255,
        'phone_max' => 20,
        'notes_max' => 5000,
    ],

    'default_filters' => [
        'status' => 'actif',
        'sort_by' => 'created_at',
        'sort_order' => 'desc',
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'client_',
    ],

    'log_activities' => true,

    'notifications' => [
        'on_create' => true,
        'on_update' => true,
        'on_delete' => false,
        'channels' => ['database', 'mail'],
    ],
];
