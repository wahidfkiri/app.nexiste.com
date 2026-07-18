<?php

return [
    'success' => [
        'connected' => 'Notion workspace connected successfully.',
        'disconnected' => 'Notion workspace disconnected.',
        'page_created' => 'Notion page created successfully.',
        'link_created' => 'Notion page linked to the CRM.',
        'link_updated' => 'CRM link updated.',
        'link_deleted' => 'CRM link deleted.',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'Invalid OAuth state for this session.',
        'permission_insufficient' => 'Insufficient permission: :permission',
        'storage_missing' => 'The Notion workspace tables are missing. Run: php artisan migrate',
        'extension_inactive' => 'The Notion workspace is not active for this tenant. Activate it from the Marketplace.',
        'clients_module_missing' => 'The clients module is not available.',
        'client_invalid' => 'Invalid client for this tenant.',
        'projects_module_missing' => 'The projects module is not available.',
        'project_invalid' => 'Invalid project for this tenant.',
        'client_id_missing' => 'NOTION_WORKSPACE_CLIENT_ID is missing.',
        'oauth_state_invalid' => 'Invalid Notion OAuth state.',
        'not_connected' => 'The Notion workspace is not connected for this tenant.',
        'page_title_required' => 'The Notion page title is required.',
        'oauth_finalize_failed' => 'Unable to finalize the Notion connection: :message',
        'session_expired' => 'Notion session expired or revoked. Reconnect your Notion workspace.',
        'session_refresh_failed' => 'Unable to refresh the Notion session: :message',
        'api_error' => 'Notion API: :message',
        'oauth_credentials_missing' => 'The Notion OAuth credentials are missing.',
    ],

    'defaults' => [
        'workspace_name' => 'Notion workspace',
        'untitled' => 'Untitled',
        'child_page' => 'Child page',
    ],
];
