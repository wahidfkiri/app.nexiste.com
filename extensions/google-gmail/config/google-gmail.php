<?php

return [
    'slug' => 'google-gmail',
    'version' => '1.0.0',

    'oauth' => [
        'client_id' => env('GOOGLE_GMAIL_CLIENT_ID'),
        'client_secret' => env('GOOGLE_GMAIL_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_GMAIL_REDIRECT_URI', '/extensions/google-gmail/oauth/callback'),
        'scopes' => [
            'https://www.googleapis.com/auth/gmail.modify',
            'https://www.googleapis.com/auth/gmail.send',
            'https://www.googleapis.com/auth/gmail.compose',
            'https://www.googleapis.com/auth/gmail.labels',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ],
    ],

    'api' => [
        'max_results' => 25,
        'timeout' => 60,
        'retry_attempts' => 3,
    ],

    'token' => [
        'refresh_buffer' => 300,
    ],

    'socket' => [
        'enabled' => filter_var(env('GOOGLE_GMAIL_SOCKET_IO_ENABLED', true), FILTER_VALIDATE_BOOL),
        'client_url' => env('GOOGLE_GMAIL_SOCKET_IO_URL', 'http://127.0.0.1:6004'),
        'path' => env('GOOGLE_GMAIL_SOCKET_IO_PATH', '/socket.io'),
        'namespace' => env('GOOGLE_GMAIL_SOCKET_IO_NAMESPACE', '/'),
        'emit_url' => env('GOOGLE_GMAIL_SOCKET_IO_EMIT_URL', 'http://127.0.0.1:6004/emit'),
        'server_token' => env('GOOGLE_GMAIL_SOCKET_IO_SERVER_TOKEN'),
        'transports' => ['websocket', 'polling'],
        'scheduler_enabled' => filter_var(env('GOOGLE_GMAIL_SOCKET_IO_SCHEDULER_ENABLED', true), FILTER_VALIDATE_BOOL),
        'scheduler_preview_limit' => (int) env('GOOGLE_GMAIL_SOCKET_IO_PREVIEW_LIMIT', 25),
    ],

    'mailbox_labels' => [
        'INBOX',
        'SENT',
        'DRAFT',
        'STARRED',
        'TRASH',
        'SPAM',
        'IMPORTANT',
        'CATEGORY_PERSONAL',
        'CATEGORY_UPDATES',
        'CATEGORY_PROMOTIONS',
    ],
];
