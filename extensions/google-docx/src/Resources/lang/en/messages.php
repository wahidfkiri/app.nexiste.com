<?php

return [
    'common' => [
        'success' => 'Success',
        'error' => 'Error',
        'validation' => 'Validation',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'Invalid OAuth state for the current session.',
        'extension_inactive' => 'Google Docs is not active for this tenant. Activate the application from the Marketplace.',
        'storage_missing' => 'The Google Docs tables are missing. Run: php artisan migrate',
        'client_id_missing' => 'GOOGLE_DOCX_CLIENT_ID is missing.',
        'invalid_oauth_state' => 'Invalid OAuth state.',
        'session_expired' => 'Google Docs session expired or revoked. Reconnect your Google account.',
        'not_connected' => 'Google Docs is not connected for this tenant.',
        'document_id_missing' => 'Google Docs identifier missing.',
        'document_id_invalid' => 'Invalid Google Docs identifier.',
        'document_url_invalid' => 'Invalid Google Docs URL. Use the document link or its identifier.',
        'document_not_found' => 'Google Docs document not found',
        'permission_denied' => 'Access denied to the Google Docs document. Share the document with the connected account then try again.',
        'unexpected' => 'Unexpected Google Docs error.',
    ],

    'success' => [
        'connected' => 'Google Docs connected successfully.',
        'disconnected' => 'Google Docs disconnected.',
        'document_created' => 'Document created successfully.',
        'document_renamed' => 'Document renamed.',
        'document_duplicated' => 'Document duplicated.',
        'document_deleted' => 'Document deleted.',
        'text_appended' => 'Text appended to the document.',
        'replace_done' => 'Replacement completed.',
    ],

    'validation' => [
        'title_required' => 'The title is required.',
        'title_string' => 'The title must be a string.',
        'title_max' => 'The title must not exceed 500 characters.',
        'content_string' => 'The content is invalid.',
        'content_max' => 'The content is too long.',
        'text_required' => 'The text to append is required.',
        'text_string' => 'The text to append is invalid.',
        'text_max' => 'The text to append is too long.',
        'search_required' => 'The text to search is required.',
        'search_string' => 'The text to search is invalid.',
        'search_max' => 'The text to search is too long.',
        'replace_string' => 'The replacement text is invalid.',
        'replace_max' => 'The replacement text is too long.',
        'format_in' => 'The export format is invalid.',
    ],
];
