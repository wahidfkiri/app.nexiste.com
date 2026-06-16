<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Extension Google Sheets — Configuration
    |--------------------------------------------------------------------------
    */

    // Identifiant unique de l'extension
    'slug'    => 'google-sheets',
    'version' => '1.0.0',

    // Credentials OAuth2 Google (globaux — configurés par le super-admin)
    // Chaque tenant lie ensuite son propre compte Google
    'oauth' => [
        'client_id'     => env('GOOGLE_SHEETS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_SHEETS_CLIENT_SECRET'),
        'redirect_uri'  => env('GOOGLE_SHEETS_REDIRECT_URI', '/extensions/google-sheets/oauth/callback'),
        'scopes'        => [
            'https://www.googleapis.com/auth/spreadsheets',
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive.metadata.readonly',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ],
    ],

    // Paramètres de l'API Sheets
    'api' => [
        'page_size'        => 50,
        'max_rows'         => 10000,
        'timeout'          => 60,
        'retry_attempts'   => 3,
    ],

    // Types de valeurs d'entrée pour l'API
    'value_input_option' => 'USER_ENTERED',

    // Formats de date/heure
    'date_format' => 'Y-m-d',

    // Cache
    'cache' => [
        'enabled'  => true,
        'ttl'      => 300,   // 5 minutes
        'prefix'   => 'gsheets_',
    ],

    // Tokens
    'token' => [
        'encryption'     => true,
        'refresh_buffer' => 300,  // Rafraîchir si expire dans moins de 5min
    ],

    // Couleurs des onglets disponibles
    'tab_colors' => [
        'red'    => ['red' => 1.0, 'green' => 0.0, 'blue' => 0.0],
        'green'  => ['red' => 0.0, 'green' => 0.7, 'blue' => 0.0],
        'blue'   => ['red' => 0.0, 'green' => 0.0, 'blue' => 1.0],
        'yellow' => ['red' => 1.0, 'green' => 0.9, 'blue' => 0.0],
        'purple' => ['red' => 0.6, 'green' => 0.0, 'blue' => 1.0],
        'cyan'   => ['red' => 0.0, 'green' => 0.8, 'blue' => 0.8],
        'orange' => ['red' => 1.0, 'green' => 0.6, 'blue' => 0.0],
        'pink'   => ['red' => 1.0, 'green' => 0.4, 'blue' => 0.7],
    ],
];