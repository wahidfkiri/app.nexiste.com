<?php

return [
    'common' => [
        'success' => 'Success',
        'error' => 'Error',
        'validation' => 'Validation',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'The OAuth state does not match the current session.',
        'extension_inactive' => 'The Google Drive extension is not active for this tenant. Activate it first from the Marketplace.',
        'storage_missing' => 'The Google Drive tables are missing. Run the migrations: php artisan migrate',
        'client_id_missing' => 'GOOGLE_DRIVE_CLIENT_ID is missing.',
        'invalid_oauth_state' => 'Invalid OAuth state.',
        'session_expired' => 'Google Drive session expired or revoked. Reconnect your Google account.',
        'list_files' => 'Unable to list the files: :message',
        'file_type_not_allowed' => 'File type not allowed: :mime',
        'file_too_large' => 'File too large.',
        'not_connected' => 'Google Drive is not connected for this tenant.',
    ],

    'success' => [
        'connected' => 'Google Drive connected successfully.',
        'disconnected' => 'Google Drive disconnected.',
        'folder_created' => 'Folder created successfully.',
        'file_uploaded' => 'File uploaded successfully.',
        'file_renamed' => 'File renamed.',
        'file_moved' => 'File moved.',
        'file_copied' => 'File copied.',
        'file_deleted' => 'File deleted.',
        'file_restored' => 'File restored.',
        'trash_emptied' => 'Trash emptied.',
        'file_shared' => 'File shared.',
    ],

    'validation' => [
        'folder_name_required' => 'The folder name is required.',
        'folder_name_string' => 'The folder name must be a string.',
        'folder_name_max' => 'The folder name must not exceed 500 characters.',
        'parent_id_string' => 'The parent folder reference is invalid.',
        'parent_id_max' => 'The parent folder reference is too long.',
        'file_required' => 'Please select a file.',
        'file_invalid' => 'The selected file is invalid.',
        'file_max' => 'The file exceeds the allowed limit of 100 MB.',
    ],
];
