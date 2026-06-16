<?php

return [

    // Pagination
    'pagination' => [
        'per_page'     => 15,
        'max_per_page' => 100,
    ],

    // Rôles disponibles dans un tenant (synchronisé avec Spatie)
    'tenant_roles' => [
        'owner'   => 'Propriétaire',
        'admin'   => 'Administrateur',
        'manager' => 'Gestionnaire',
        'user'    => 'Utilisateur',
        'viewer'  => 'Visiteur',
    ],

    // Statuts utilisateur
    'user_statuses' => [
        'active'   => 'Actif',
        'inactive' => 'Inactif',
        'invited'  => 'Invitation envoyée',
        'suspended'=> 'Suspendu',
    ],

    // Invitation
    'invitation' => [
        'expire_days'    => 7,
        'resend_cooldown'=> 24, // heures avant de pouvoir renvoyer
        'token_length'   => 64,
    ],

    // Email
    'mail' => [
        'from_name'     => env('MAIL_FROM_NAME', config('app.name')),
        'from_address'  => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
    ],

    // Permissions par rôle (base — peut être étendu par module)
    'role_permissions' => [
        'owner'   => ['*'],
        'admin'   => ['users.*', 'clients.*', 'invoices.*', 'stock.*', 'reports.*'],
        'manager' => ['clients.*', 'invoices.*', 'stock.read'],
        'user'    => ['clients.read', 'invoices.read'],
        'viewer'  => ['clients.read'],
    ],

    // Cache
    'cache' => [
        'enabled' => true,
        'ttl'     => 3600,
        'prefix'  => 'user_',
    ],

    // Avatar
    'avatar' => [
        'default_colors' => ['#2563eb','#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777'],
        'upload_disk'    => 'public',
        'upload_path'    => 'avatars',
        'max_size_kb'    => 2048,
    ],

    // Notifications
    'notifications' => [
        'on_invite'    => true,
        'on_activate'  => true,
        'on_suspend'   => true,
        'on_role_change' => true,
        'channels'     => ['database', 'mail'],
    ],

    // Export
    'export' => [
        'formats'    => ['csv', 'excel'],
        'chunk_size' => 500,
    ],

    // Audit
    'log_activities' => true,
];