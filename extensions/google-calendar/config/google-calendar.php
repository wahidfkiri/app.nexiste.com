<?php

return [
    'slug' => 'google-calendar',
    'version' => '1.0.0',

    'oauth' => [
        'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_CALENDAR_REDIRECT_URI', '/extensions/google-calendar/oauth/callback'),
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
        'base_url' => 'https://www.googleapis.com/calendar/v3/',
        'oauth_base_url' => 'https://oauth2.googleapis.com/',
        'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'timeout' => 30,
        'page_size' => 100,
        'sync_days_past' => 30,
        'sync_days_future' => 90,
    ],

    'token' => [
        'refresh_buffer' => 300,
    ],

    'defaults' => [
        'timezone' => env('APP_TIMEZONE', 'UTC'),
    ],
];
