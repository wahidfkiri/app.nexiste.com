<?php

return [
    'success' => [
        'connected' => 'Espace Notion connecté avec succès.',
        'disconnected' => 'Espace Notion déconnecté.',
        'page_created' => 'Page Notion créée avec succès.',
        'link_created' => 'Page Notion liée au CRM.',
        'link_updated' => 'Lien CRM mis à jour.',
        'link_deleted' => 'Lien CRM supprimé.',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'État OAuth invalide pour cette session.',
        'permission_insufficient' => 'Permission insuffisante : :permission',
        'storage_missing' => 'Les tables de l\'espace Notion sont absentes. Exécutez : php artisan migrate',
        'extension_inactive' => 'L\'espace Notion n\'est pas actif pour ce tenant. Activez-le depuis le Marketplace.',
        'clients_module_missing' => 'Le module clients n\'est pas disponible.',
        'client_invalid' => 'Client invalide pour ce tenant.',
        'projects_module_missing' => 'Le module projets n\'est pas disponible.',
        'project_invalid' => 'Projet invalide pour ce tenant.',
        'client_id_missing' => 'NOTION_WORKSPACE_CLIENT_ID est manquant.',
        'oauth_state_invalid' => 'État OAuth Notion invalide.',
        'not_connected' => 'L\'espace Notion n\'est pas connecté pour ce tenant.',
        'page_title_required' => 'Le titre de la page Notion est requis.',
        'oauth_finalize_failed' => 'Impossible de finaliser la connexion Notion : :message',
        'session_expired' => 'Session Notion expirée ou révoquée. Reconnectez votre espace Notion.',
        'session_refresh_failed' => 'Impossible de rafraîchir la session Notion : :message',
        'api_error' => 'API Notion : :message',
        'oauth_credentials_missing' => 'Les identifiants OAuth Notion sont manquants.',
    ],

    'defaults' => [
        'workspace_name' => 'Espace Notion',
        'untitled' => 'Sans titre',
        'child_page' => 'Sous-page',
    ],
];
