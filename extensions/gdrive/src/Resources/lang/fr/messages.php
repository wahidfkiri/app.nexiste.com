<?php

return [
    'common' => [
        'success' => 'Succès',
        'error' => 'Erreur',
        'validation' => 'Validation',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'L état OAuth ne correspond pas à la session en cours.',
        'extension_inactive' => 'L extension Google Drive n est pas active pour ce tenant. Activez-la d abord depuis Marketplace.',
        'storage_missing' => 'Les tables Google Drive sont absentes. Lancez les migrations : php artisan migrate',
        'client_id_missing' => 'GOOGLE_DRIVE_CLIENT_ID est manquant.',
        'invalid_oauth_state' => 'État OAuth invalide.',
        'session_expired' => 'Session Google Drive expirée ou révoquée. Reconnectez votre compte Google.',
        'list_files' => 'Impossible de lister les fichiers : :message',
        'file_type_not_allowed' => 'Type de fichier non autorisé : :mime',
        'file_too_large' => 'Fichier trop volumineux.',
        'not_connected' => 'Google Drive n est pas connecté pour ce tenant.',
    ],

    'success' => [
        'connected' => 'Google Drive connecté avec succès.',
        'disconnected' => 'Google Drive déconnecté.',
        'folder_created' => 'Dossier créé avec succès.',
        'file_uploaded' => 'Fichier importé avec succès.',
        'file_renamed' => 'Fichier renommé.',
        'file_moved' => 'Fichier déplacé.',
        'file_copied' => 'Fichier copié.',
        'file_deleted' => 'Fichier supprimé.',
        'file_restored' => 'Fichier restauré.',
        'trash_emptied' => 'Corbeille vidée.',
        'file_shared' => 'Fichier partagé.',
    ],

    'validation' => [
        'folder_name_required' => 'Le nom du dossier est obligatoire.',
        'folder_name_string' => 'Le nom du dossier doit être une chaîne de caractères.',
        'folder_name_max' => 'Le nom du dossier ne doit pas dépasser 500 caractères.',
        'parent_id_string' => 'La référence du dossier parent est invalide.',
        'parent_id_max' => 'La reference du dossier parent est trop longue.',
        'file_required' => 'Veuillez sélectionner un fichier.',
        'file_invalid' => 'Le fichier sélectionné est invalide.',
        'file_max' => 'Le fichier dépasse la limite autorisée de 100 MB.',
    ],
];
