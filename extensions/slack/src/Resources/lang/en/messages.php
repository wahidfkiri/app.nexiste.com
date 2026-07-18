<?php

return [
    'success' => [
        'connected' => 'Slack connected successfully.',
        'disconnected' => 'Slack disconnected.',
        'channel_selected' => 'Channel selected.',
        'message_sent' => 'Message sent.',
        'sync_done' => ':channels channels synced, :messages messages imported.',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'The OAuth state does not match the current session.',
        'extension_inactive' => 'Slack is not active for this tenant. Activate it from the Marketplace.',
        'storage_missing' => 'The Slack tables are missing. Run: php artisan migrate',
        'client_id_missing' => 'SLACK_CLIENT_ID is missing.',
        'oauth_state_invalid' => 'Invalid OAuth state.',
        'oauth_state_expired' => 'The OAuth state has expired. Restart the Slack connection.',
        'oauth_credentials_missing' => 'The Slack OAuth credentials are missing.',
        'oauth_request_failed' => 'The Slack OAuth request failed: HTTP :status',
        'oauth_exchange_failed' => 'Slack OAuth exchange failed.',
        'bot_token_missing' => 'The Slack bot token was not returned by OAuth.',
        'not_connected' => 'Slack is not connected for this tenant.',
        'bot_token_missing_reconnect' => 'The Slack bot token is missing. Reconnect your Slack workspace.',
        'channel_not_found' => 'The selected Slack channel does not exist.',
        'channel_not_selected' => 'No Slack channel selected.',
        'channel_required' => 'The Slack channel is required.',
        'message_required' => 'The message text is required.',
        'api_failed' => 'The Slack API :endpoint failed: HTTP :status',
        'api_failed_generic' => 'The Slack API :endpoint failed.',
        'redirect_uri_invalid_format' => 'SLACK_REDIRECT_URI must be an http/https web URL. Non-web URIs require PKCE and are not supported here.',
        'redirect_uri_invalid' => 'Invalid OAuth redirect URI. Use an http/https web URL (e.g. http://127.0.0.1:8000/extensions/slack/oauth/callback).',
        'redirect_uri_localhost_bot_scopes' => 'Slack refuses this connection because the application uses localhost as the redirect URI with bot scopes. Since Slack\'s PKCE changes, localhost is treated as a desktop redirect in this case. Use a non-localhost web URL for SLACK_REDIRECT_URI (e.g. https://crm.test/extensions/slack/oauth/callback) and also add it to your Slack app\'s Redirect URLs, or disable PKCE if you must stay on localhost.',
        'client_id_google_detected' => 'SLACK_CLIENT_ID appears to be a Google identifier. Use the Client ID of your Slack application (api.slack.com/apps).',
    ],

    'common' => [
        'me' => 'Me',
        'bot' => 'Bot',
        'user' => 'User',
        'api_error_prefix' => 'Slack API error:',
    ],
];
