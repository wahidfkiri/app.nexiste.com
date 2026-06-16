<?php

return [
    'slug' => 'slack',
    'version' => '1.0.0',

    'oauth' => [
        'client_id' => env('SLACK_CLIENT_ID'),
        'client_secret' => env('SLACK_CLIENT_SECRET'),
        'redirect_uri' => env('SLACK_REDIRECT_URI', '/extensions/slack/oauth/callback'),
        'scopes' => [
            'channels:read',
            'channels:history',
            'groups:read',
            'groups:history',
            'im:read',
            'im:history',
            'mpim:read',
            'mpim:history',
            'chat:write',
            'users:read',
            'team:read',
        ],
        'user_scopes' => [],
    ],

    'api' => [
        'base_url' => env('SLACK_API_BASE_URL', 'https://slack.com/api'),
        'channel_types' => env('SLACK_CHANNEL_TYPES', 'public_channel,private_channel,im,mpim'),
        'page_size' => (int) env('SLACK_PAGE_SIZE', 100),
        'message_page_size' => (int) env('SLACK_MESSAGE_PAGE_SIZE', 50),
        'sync_days_past' => (int) env('SLACK_SYNC_DAYS_PAST', 14),
        'timeout' => (int) env('SLACK_TIMEOUT', 20),
    ],

    'socket' => [
        'enabled' => filter_var(env('SLACK_SOCKET_IO_ENABLED', true), FILTER_VALIDATE_BOOL),
        'client_url' => env('SLACK_SOCKET_IO_URL', 'http://127.0.0.1:6002'),
        'path' => env('SLACK_SOCKET_IO_PATH', '/socket.io'),
        'namespace' => env('SLACK_SOCKET_IO_NAMESPACE', '/'),
        'emit_url' => env('SLACK_SOCKET_IO_EMIT_URL', 'http://127.0.0.1:6002/emit'),
        'server_token' => env('SLACK_SOCKET_IO_SERVER_TOKEN'),
        'transports' => ['websocket', 'polling'],
    ],
];

