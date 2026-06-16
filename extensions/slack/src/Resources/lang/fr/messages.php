<?php

return [
    'success' => [
        'connected' => 'Slack connecté avec succès.',
        'disconnected' => 'Slack déconnecté.',
        'channel_selected' => 'Canal sélectionné.',
        'message_sent' => 'Message envoyé.',
        'sync_done' => ':channels canaux synchronisés, :messages messages importés.',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'L\'état OAuth ne correspond pas à la session en cours.',
        'extension_inactive' => 'Slack n\'est pas active pour ce tenant. Activez-la depuis le Marketplace.',
        'storage_missing' => 'Les tables Slack sont absentes. Exécutez : php artisan migrate',
        'client_id_missing' => 'SLACK_CLIENT_ID est manquant.',
        'oauth_state_invalid' => 'État OAuth invalide.',
        'oauth_state_expired' => 'L\'état OAuth a expiré. Relancez la connexion Slack.',
        'oauth_credentials_missing' => 'Les identifiants OAuth Slack sont manquants.',
        'oauth_request_failed' => 'La requête OAuth Slack a échoué : HTTP :status',
        'oauth_exchange_failed' => 'Échec de l\'échange OAuth Slack.',
        'bot_token_missing' => 'Le jeton bot Slack n\'a pas été retourné par OAuth.',
        'not_connected' => 'Slack n\'est pas connecté pour ce tenant.',
        'bot_token_missing_reconnect' => 'Le jeton bot Slack est manquant. Reconnectez votre espace Slack.',
        'channel_not_found' => 'Le canal Slack sélectionné n\'existe pas.',
        'channel_not_selected' => 'Aucun canal Slack sélectionné.',
        'channel_required' => 'Le canal Slack est obligatoire.',
        'message_required' => 'Le texte du message est obligatoire.',
        'api_failed' => 'L\'API Slack :endpoint a échoué : HTTP :status',
        'api_failed_generic' => 'L\'API Slack :endpoint a échoué.',
        'redirect_uri_invalid_format' => 'SLACK_REDIRECT_URI doit être une URL web http/https. Les URI non web nécessitent PKCE et ne sont pas supportées ici.',
        'redirect_uri_invalid' => 'Redirect URI OAuth invalide. Utilisez une URL web http/https (ex: http://127.0.0.1:8000/extensions/slack/oauth/callback).',
        'redirect_uri_localhost_bot_scopes' => 'Slack refuse cette connexion car l\'application utilise localhost comme redirect URI avec des scopes bot. Depuis les changements PKCE de Slack, localhost est traité comme une redirection desktop dans ce cas. Utilisez une URL web non localhost pour SLACK_REDIRECT_URI (ex: https://crm.test/extensions/slack/oauth/callback) et ajoutez-la aussi dans les Redirect URLs de votre app Slack, ou désactivez PKCE si vous devez rester sur localhost.',
        'client_id_google_detected' => 'SLACK_CLIENT_ID semble être un identifiant Google. Utilisez le Client ID de votre application Slack (api.slack.com/apps).',
    ],

    'common' => [
        'me' => 'Moi',
        'bot' => 'Bot',
        'user' => 'Utilisateur',
        'api_error_prefix' => 'Erreur API Slack :',
    ],
];
