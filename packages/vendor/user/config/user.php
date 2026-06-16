<?php

return [
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],

    'tenant_roles' => [
        'owner' => 'Propriétaire',
        'admin' => 'Administrateur',
        'manager' => 'Gestionnaire',
        'user' => 'Utilisateur',
        'viewer' => 'Visiteur',
    ],

    'user_statuses' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'invited' => 'Invitation envoyée',
        'suspended' => 'Suspendu',
    ],

    'invitation' => [
        'expire_days' => 7,
        'resend_cooldown' => 24,
        'token_length' => 64,
    ],

    'mail' => [
        'from_name' => env('MAIL_FROM_NAME', config('app.name')),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
    ],

    'role_permissions' => [
        'owner' => ['*'],
        'admin' => ['users.*', 'clients.*', 'invoices.*', 'stock.*', 'reports.*'],
        'manager' => ['clients.*', 'invoices.*', 'stock.read'],
        'user' => ['clients.read', 'invoices.read'],
        'viewer' => ['clients.read'],
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'user_',
    ],

    'avatar' => [
        'default_colors' => ['#2563eb', '#7c3aed', '#0891b2', '#059669', '#d97706', '#dc2626', '#db2777'],
        'upload_disk' => 'public',
        'upload_path' => 'avatars',
        'max_size_kb' => 2048,
    ],

    'notifications' => [
        'on_invite' => true,
        'on_activate' => true,
        'on_suspend' => true,
        'on_role_change' => true,
        'channels' => ['database', 'mail'],
    ],

    'export' => [
        'formats' => ['csv', 'excel'],
        'chunk_size' => 500,
    ],

    'log_activities' => true,
];