<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Extension Google Drive — Configuration
    |--------------------------------------------------------------------------
    */

    // Identifiant unique de l'extension (doit correspondre au slug dans le catalogue)
    'slug'    => 'google-drive',
    'version' => '1.0.0',

    // Credentials OAuth2 Google (globaux — configurés par le super-admin)
    // Chaque tenant lie ensuite son propre compte Google
    'oauth' => [
        'client_id'     => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'redirect_uri'  => env('GOOGLE_DRIVE_REDIRECT_URI', '/extensions/google-drive/oauth/callback'),
        'scopes'        => [
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive.metadata',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ],
    ],

    // Paramètres de l'API Drive
    'api' => [
        'page_size'        => 50,          // Fichiers par page
        'max_file_size_mb' => 100,         // Taille max upload
        'timeout'          => 60,          // Timeout requêtes (secondes)
        'retry_attempts'   => 3,
    ],

    // Types MIME autorisés pour l'upload
    'allowed_mime_types' => [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // Images
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        // Texte
        'text/plain', 'text/csv',
        // Archives
        'application/zip', 'application/x-rar-compressed',
        // Vidéo/Audio
        'video/mp4', 'audio/mpeg',
    ],

    // Icônes MIME pour l'affichage
    'mime_icons' => [
        'application/pdf'       => ['icon' => 'fa-file-pdf',   'color' => '#dc2626'],
        'application/msword'    => ['icon' => 'fa-file-word',  'color' => '#2563eb'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                => ['icon' => 'fa-file-word',  'color' => '#2563eb'],
        'application/vnd.ms-excel'
                                => ['icon' => 'fa-file-excel', 'color' => '#059669'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                                => ['icon' => 'fa-file-excel', 'color' => '#059669'],
        'application/vnd.ms-powerpoint'
                                => ['icon' => 'fa-file-powerpoint', 'color' => '#d97706'],
        'image/jpeg'            => ['icon' => 'fa-file-image', 'color' => '#8b5cf6'],
        'image/png'             => ['icon' => 'fa-file-image', 'color' => '#8b5cf6'],
        'text/plain'            => ['icon' => 'fa-file-lines', 'color' => '#64748b'],
        'text/csv'              => ['icon' => 'fa-file-csv',   'color' => '#059669'],
        'application/zip'       => ['icon' => 'fa-file-zipper','color' => '#f59e0b'],
        'video/mp4'             => ['icon' => 'fa-file-video', 'color' => '#06b6d4'],
        'audio/mpeg'            => ['icon' => 'fa-file-audio', 'color' => '#ec4899'],
        // Google Docs natifs
        'application/vnd.google-apps.document'     => ['icon' => 'fa-file-word',       'color' => '#4285f4'],
        'application/vnd.google-apps.spreadsheet'  => ['icon' => 'fa-file-excel',      'color' => '#0f9d58'],
        'application/vnd.google-apps.presentation' => ['icon' => 'fa-file-powerpoint', 'color' => '#f4b400'],
        'application/vnd.google-apps.folder'       => ['icon' => 'fa-folder',          'color' => '#f59e0b'],
        'default'                                   => ['icon' => 'fa-file',            'color' => '#64748b'],
    ],

    // Cache
    'cache' => [
        'enabled'  => true,
        'ttl'      => 300,   // 5 minutes
        'prefix'   => 'gdrive_',
    ],

    // Tokens
    'token' => [
        'encryption' => true,     // Chiffrer les tokens en base
        'refresh_buffer' => 300,  // Rafraîchir si expire dans moins de 5min
    ],
];