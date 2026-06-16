<?php

return [
    'slug' => 'notion-workspace',
    'version' => '1.0.0',

    'oauth' => [
        'client_id' => env('NOTION_WORKSPACE_CLIENT_ID'),
        'client_secret' => env('NOTION_WORKSPACE_CLIENT_SECRET'),
        'redirect_uri' => env('NOTION_WORKSPACE_REDIRECT_URI', '/extensions/notion-workspace/oauth/callback'),
    ],

    'api' => [
        'base_url' => 'https://api.notion.com/v1/',
        'auth_url' => 'https://api.notion.com/v1/oauth/authorize',
        'timeout' => 30,
        'version' => env('NOTION_WORKSPACE_API_VERSION', '2026-03-11'),
        'page_size' => 20,
        'block_page_size' => 100,
    ],

    'token' => [
        'refresh_buffer' => 300,
    ],

    'permissions' => [
        'notion.view',
        'notion.create',
        'notion.update',
        'notion.delete',
        'notion.share',
        'notion.comment',
        'notion.admin',
    ],
];