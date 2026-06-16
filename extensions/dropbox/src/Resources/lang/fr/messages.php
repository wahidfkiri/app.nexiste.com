<?php

return [
    'common' => [
        'success' => 'Succès',
        'error' => 'Erreur',
        'validation' => 'Validation',
    ],

    'errors' => [
        'oauth_session_mismatch' => 'La session OAuth Dropbox ne correspond pas à votre session courante.',
        'extension_inactive' => "L'extension Dropbox n'est pas active pour ce tenant. Activez-la depuis le Marketplace.",
        'storage_missing' => 'Les tables Dropbox sont manquantes. Lancez : php artisan migrate',
        'client_id_missing' => 'DROPBOX_CLIENT_ID est manquant.',
        'invalid_oauth_state' => 'État OAuth Dropbox invalide.',
        'missing_access_token' => "Dropbox n'a retourné aucun access token.",
        'file_type_not_allowed' => 'Type de fichier non autorisé : :mime',
        'file_too_large' => 'Fichier trop volumineux.',
        'trash_file_not_found' => 'Fichier Dropbox introuvable dans la corbeille.',
        'trash_revision_missing' => 'Révision Dropbox manquante pour restaurer ce fichier.',
        'download_failed' => 'Impossible de télécharger ce fichier Dropbox.',
        'not_connected' => "Dropbox n'est pas connecté pour ce tenant.",
        'refresh_token_missing' => 'Dropbox demande une reconnexion: refresh token manquant.',
        'session_expired' => 'Session Dropbox expirée ou révoquée. Reconnectez Dropbox.',
        'refresh_failed' => 'Impossible de rafraîchir le token Dropbox.',
        'auth_finalize_failed' => 'Impossible de finaliser l authentification Dropbox.',
        'resolve_path_failed' => 'Impossible de résoudre le chemin Dropbox du fichier.',
        'invalid_name' => 'Nom Dropbox invalide.',
    ],

    'success' => [
        'connected' => 'Dropbox est maintenant connecté à votre espace.',
        'disconnected' => 'Dropbox a été déconnecté.',
        'folder_created' => 'Dossier Dropbox créé avec succès.',
        'files_uploaded' => 'Fichiers envoyés vers Dropbox avec succès.',
        'file_uploaded' => 'Fichier envoyé vers Dropbox avec succès.',
        'item_renamed' => 'Élément renommé.',
        'item_moved' => 'Élément déplacé.',
        'item_copied' => 'Élément copié.',
        'item_deleted' => 'Élément supprimé.',
        'item_restored' => 'Élément restauré.',
        'trash_emptied' => 'Corbeille Dropbox vidée.',
        'share_link_created' => 'Lien de partage créé.',
    ],

    'validation' => [
        'folder_name_required' => 'Le nom du dossier est obligatoire.',
        'folder_name_string' => 'Le nom du dossier doit être une chaîne de caractères.',
        'folder_name_max' => 'Le nom du dossier ne doit pas dépasser 255 caractères.',
        'parent_id_string' => 'La référence du dossier parent est invalide.',
        'parent_id_max' => 'La référence du dossier parent est trop longue.',
        'files_required' => 'Veuillez sélectionner au moins un fichier.',
        'files_array' => 'Le format des fichiers a importer est invalide.',
        'file_required' => 'Un fichier sélectionné est invalide.',
        'file_invalid' => "Un des éléments sélectionnés n'est pas un fichier valide.",
        'file_max' => 'Un fichier dépasse la limite autorisée de 100 MB.',
    ],
];
