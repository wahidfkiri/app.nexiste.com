<?php

return [
    'slug' => 'dropbox',
    'version' => '1.0.0',

    'oauth' => [
        'client_id' => env('DROPBOX_CLIENT_ID'),
        'client_secret' => env('DROPBOX_CLIENT_SECRET'),
        'redirect_uri' => env('DROPBOX_REDIRECT_URI', '/extensions/dropbox/oauth/callback'),
        'scopes' => [
            'account_info.read',
            'files.metadata.read',
            'files.metadata.write',
            'files.content.read',
            'files.content.write',
            'sharing.read',
            'sharing.write',
        ],
        'token_access_type' => 'offline',
    ],

    'api' => [
        'auth_url' => 'https://www.dropbox.com/oauth2/authorize',
        'token_url' => 'https://www.dropbox.com/oauth2/token',
        'base_url' => 'https://api.dropboxapi.com/2/',
        'content_url' => 'https://content.dropboxapi.com/2/',
        'page_size' => 100,
        'max_file_size_mb' => 100,
        'timeout' => 45,
        'content_timeout' => 180,
        'connect_timeout' => 30,
        'retry_attempts' => 3,
    ],

    'allowed_mime_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/x-rar-compressed',
        'video/mp4',
        'audio/mpeg',
    ],

    'mime_icons' => [
        'application/pdf' => ['icon' => 'fa-file-pdf', 'color' => '#dc2626'],
        'application/msword' => ['icon' => 'fa-file-word', 'color' => '#2563eb'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['icon' => 'fa-file-word', 'color' => '#2563eb'],
        'application/vnd.ms-excel' => ['icon' => 'fa-file-excel', 'color' => '#059669'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['icon' => 'fa-file-excel', 'color' => '#059669'],
        'application/vnd.ms-powerpoint' => ['icon' => 'fa-file-powerpoint', 'color' => '#d97706'],
        'image/jpeg' => ['icon' => 'fa-file-image', 'color' => '#8b5cf6'],
        'image/png' => ['icon' => 'fa-file-image', 'color' => '#8b5cf6'],
        'image/gif' => ['icon' => 'fa-file-image', 'color' => '#8b5cf6'],
        'image/webp' => ['icon' => 'fa-file-image', 'color' => '#8b5cf6'],
        'text/plain' => ['icon' => 'fa-file-lines', 'color' => '#64748b'],
        'text/csv' => ['icon' => 'fa-file-csv', 'color' => '#059669'],
        'application/zip' => ['icon' => 'fa-file-zipper', 'color' => '#f59e0b'],
        'video/mp4' => ['icon' => 'fa-file-video', 'color' => '#06b6d4'],
        'audio/mpeg' => ['icon' => 'fa-file-audio', 'color' => '#ec4899'],
        'folder' => ['icon' => 'fa-folder', 'color' => '#0061ff'],
        'default' => ['icon' => 'fa-file', 'color' => '#64748b'],
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => 300,
        'prefix' => 'dropbox_',
    ],

    'token' => [
        'encryption' => true,
        'refresh_buffer' => 300,
    ],
];
