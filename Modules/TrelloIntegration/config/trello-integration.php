<?php

return [
    'app' => [
        'name' => env('TRELLO_INTEGRATION_APP_NAME', config('app.name') . ' Trello'),
    ],

    'api' => [
        'base_url' => env('TRELLO_INTEGRATION_API_BASE_URL', 'https://api.trello.com/1'),
        'timeout' => (int) env('TRELLO_INTEGRATION_API_TIMEOUT', 25),
        'key' => env('TRELLO_INTEGRATION_API_KEY', ''),
        // Optional today, useful if we switch to a stricter Trello OAuth flow later.
        'secret' => env('TRELLO_INTEGRATION_API_SECRET', ''),
    ],

    'auth' => [
        'scopes' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRELLO_INTEGRATION_SCOPES', 'read,write'))))),
        'expiration' => env('TRELLO_INTEGRATION_TOKEN_EXPIRATION', '30days'),
        'redirect_uri' => env('TRELLO_INTEGRATION_REDIRECT_URI', '/extensions/trello-integration/oauth/callback'),
    ],

    'ui' => [
        'max_projects_in_picker' => (int) env('TRELLO_INTEGRATION_PROJECT_PICKER_LIMIT', 100),
    ],
];
