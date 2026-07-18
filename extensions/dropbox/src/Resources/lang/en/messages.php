<?php

return [
    'common' => [
        'success' => 'Success',
        'error' => 'Error',
        'validation' => 'Validation',
    ],

    'errors' => [
        'oauth_session_mismatch' => 'The Dropbox OAuth session does not match your current session.',
        'extension_inactive' => 'The Dropbox extension is not active for this tenant. Activate it from the Marketplace.',
        'storage_missing' => 'The Dropbox tables are missing. Run: php artisan migrate',
        'client_id_missing' => 'DROPBOX_CLIENT_ID is missing.',
        'invalid_oauth_state' => 'Invalid Dropbox OAuth state.',
        'missing_access_token' => 'Dropbox did not return any access token.',
        'file_type_not_allowed' => 'File type not allowed: :mime',
        'file_too_large' => 'File too large.',
        'trash_file_not_found' => 'Dropbox file not found in the trash.',
        'trash_revision_missing' => 'Dropbox revision missing to restore this file.',
        'download_failed' => 'Unable to download this Dropbox file.',
        'not_connected' => 'Dropbox is not connected for this tenant.',
        'refresh_token_missing' => 'Dropbox requires reconnection: refresh token missing.',
        'session_expired' => 'Dropbox session expired or revoked. Reconnect Dropbox.',
        'refresh_failed' => 'Unable to refresh the Dropbox token.',
        'auth_finalize_failed' => 'Unable to finalize Dropbox authentication.',
        'resolve_path_failed' => 'Unable to resolve the Dropbox path of the file.',
        'invalid_name' => 'Invalid Dropbox name.',
    ],

    'success' => [
        'connected' => 'Dropbox is now connected to your workspace.',
        'disconnected' => 'Dropbox has been disconnected.',
        'folder_created' => 'Dropbox folder created successfully.',
        'files_uploaded' => 'Files uploaded to Dropbox successfully.',
        'file_uploaded' => 'File uploaded to Dropbox successfully.',
        'item_renamed' => 'Item renamed.',
        'item_moved' => 'Item moved.',
        'item_copied' => 'Item copied.',
        'item_deleted' => 'Item deleted.',
        'item_restored' => 'Item restored.',
        'trash_emptied' => 'Dropbox trash emptied.',
        'share_link_created' => 'Share link created.',
    ],

    'validation' => [
        'folder_name_required' => 'The folder name is required.',
        'folder_name_string' => 'The folder name must be a string.',
        'folder_name_max' => 'The folder name must not exceed 255 characters.',
        'parent_id_string' => 'The parent folder reference is invalid.',
        'parent_id_max' => 'The parent folder reference is too long.',
        'files_required' => 'Please select at least one file.',
        'files_array' => 'The format of the files to import is invalid.',
        'file_required' => 'A selected file is invalid.',
        'file_invalid' => 'One of the selected items is not a valid file.',
        'file_max' => 'A file exceeds the allowed limit of 100 MB.',
    ],
];
