<?php

return [
    'slug' => 'google-docx',
    'version' => '1.0.0',

    'oauth' => [
        'client_id' => env('GOOGLE_DOCX_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DOCX_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_DOCX_REDIRECT_URI', '/extensions/google-docx/oauth/callback'),
        'scopes' => [
            'https://www.googleapis.com/auth/documents',
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive.metadata.readonly',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ],
    ],

    'api' => [
        'page_size' => 50,
        'timeout' => 60,
        'retry_attempts' => 3,
    ],

    'token' => [
        'refresh_buffer' => 300,
    ],
];
