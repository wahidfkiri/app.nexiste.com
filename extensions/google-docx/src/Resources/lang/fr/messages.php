<?php

return [
    'common' => [
        'success' => 'Succès',
        'error' => 'Erreur',
        'validation' => 'Validation',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'État OAuth invalide pour la session en cours.',
        'extension_inactive' => 'Google Docs n est pas active pour ce tenant. Activez l application depuis le Marketplace.',
        'storage_missing' => 'Les tables Google Docs sont absentes. Executez: php artisan migrate',
        'client_id_missing' => 'GOOGLE_DOCX_CLIENT_ID est manquant.',
        'invalid_oauth_state' => 'État OAuth invalide.',
        'session_expired' => 'Session Google Docs expirée ou révoquée. Reconnectez votre compte Google.',
        'not_connected' => 'Google Docs n est pas connecté pour ce tenant.',
        'document_id_missing' => 'Identifiant Google Docs manquant.',
        'document_id_invalid' => 'Identifiant Google Docs invalide.',
        'document_url_invalid' => 'URL Google Docs invalide. Utilisez le lien du document ou son identifiant.',
        'document_not_found' => 'Document Google Docs introuvable',
        'permission_denied' => 'Accès refusé au document Google Docs. Partagez le document avec le compte connecté puis réessayez.',
        'unexpected' => 'Erreur Google Docs inattendue.',
    ],

    'success' => [
        'connected' => 'Google Docs connecté avec succès.',
        'disconnected' => 'Google Docs déconnecté.',
        'document_created' => 'Document créé avec succès.',
        'document_renamed' => 'Document renommé.',
        'document_duplicated' => 'Document dupliqué.',
        'document_deleted' => 'Document supprimé.',
        'text_appended' => 'Texte ajouté au document.',
        'replace_done' => 'Remplacement terminé.',
    ],

    'validation' => [
        'title_required' => 'Le titre est obligatoire.',
        'title_string' => 'Le titre doit être une chaîne de caractères.',
        'title_max' => 'Le titre ne doit pas dépasser 500 caractères.',
        'content_string' => 'Le contenu est invalide.',
        'content_max' => 'Le contenu est trop long.',
        'text_required' => 'Le texte à ajouter est obligatoire.',
        'text_string' => 'Le texte à ajouter est invalide.',
        'text_max' => 'Le texte à ajouter est trop long.',
        'search_required' => 'Le texte à rechercher est obligatoire.',
        'search_string' => 'Le texte à rechercher est invalide.',
        'search_max' => 'Le texte à rechercher est trop long.',
        'replace_string' => 'Le texte de remplacement est invalide.',
        'replace_max' => 'Le texte de remplacement est trop long.',
        'format_in' => 'Le format d export est invalide.',
    ],
];
