<?php

return [
    'slug' => 'google-meet',
    'version' => '1.0.0',

    'oauth' => [
        'client_id' => env('GOOGLE_MEET_CLIENT_ID'),
        'client_secret' => env('GOOGLE_MEET_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_MEET_REDIRECT_URI', '/extensions/google-meet/oauth/callback'),
        'scopes' => [
            'openid',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/calendar.events',
            'https://www.googleapis.com/auth/calendar.calendarlist',
        ],
    ],

    'api' => [
        'page_size' => 100,
        'timeout' => 30,
        'sync_days_past' => 30,
        'sync_days_future' => 90,
    ],

    'token' => [
        'refresh_buffer' => 300,
    ],

    'defaults' => [
        'timezone' => env('APP_TIMEZONE', 'Europe/Paris'),
        'send_updates' => 'all',
    ],
];
