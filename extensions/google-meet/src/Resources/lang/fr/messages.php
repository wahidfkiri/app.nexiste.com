<?php

return [
    'breadcrumb' => [
        'applications' => 'Applications',
    ],

    'common' => [
        'success' => 'Succès',
        'error' => 'Erreur',
        'validation' => 'Validation',
        'unknown' => 'Inconnu',
        'never' => 'Jamais',
        'none' => 'Aucun',
        'no_title' => '(Sans titre)',
        'no_data_title' => 'Aucune donnée',
        'dash' => '-',
    ],

    'page' => [
        'title' => 'Google Meet',
        'subtitle' => 'Planifiez et gérez vos réunions Meet avec OAuth Google.',
    ],

    'actions' => [
        'migration_required' => 'Migration requise',
        'activate_marketplace' => 'Activer depuis Marketplace',
        'sync' => 'Synchroniser',
        'new_meeting' => 'Nouvelle réunion',
        'disconnect' => 'Déconnecter',
        'connect_google_meet' => 'Connecter Google Meet',
        'connect' => 'Se connecter',
        'open_marketplace' => 'Ouvrir Marketplace',
        'open_app' => 'Ouvrir l’application',
        'explore_apps' => 'Explorer les applications',
        'cancel' => 'Annuler',
        'save' => 'Enregistrer',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'reset' => 'Réinitialiser',
        'join_meet' => 'Rejoindre Meet',
    ],

    'storage' => [
        'title' => 'Migration base de données requise',
        'description' => 'Les tables Google Meet sont absentes. Exécutez la migration avant d’utiliser ce module.',
        'command' => 'php artisan migrate',
    ],

    'extension' => [
        'title' => 'Extension non activée',
        'description' => 'Google Meet est disponible sur la plateforme mais n’est pas encore activé pour ce tenant. Activez l’application depuis le Marketplace.',
    ],

    'connection' => [
        'title' => 'Connexion Google Meet',
        'description' => 'Ce tenant n’a pas encore connecté Google Meet. Lancez l’authentification OAuth pour synchroniser et gérer vos réunions.',
    ],

    'stats' => [
        'calendars' => 'Calendriers',
        'today' => 'Aujourd’hui',
        'next_7_days' => '7 prochains jours',
        'this_month' => 'Ce mois',
        'active_links' => 'Liens actifs',
    ],

    'account' => [
        'title' => 'Compte connecté',
        'name' => 'Nom',
        'email' => 'Email',
        'connected_at' => 'Connecté le',
        'last_sync' => 'Dernière sync',
    ],

    'calendars' => [
        'title' => 'Calendriers',
        'primary' => 'Principal',
        'no_calendars_title' => 'Aucun calendrier',
        'no_calendars_desc' => 'Lancez une synchronisation après connexion.',
    ],

    'table' => [
        'meetings' => 'Réunions Meet',
        'count_results' => ':count résultat(s)',
        'pagination_showing' => 'Affichage :from à :to sur :total réunion(s)',
        'empty_filtered' => 'Aucune réunion trouvée pour les filtres sélectionnés.',
    ],

    'columns' => [
        'meeting' => 'Réunion',
        'calendar' => 'Calendrier',
        'start' => 'Début',
        'end' => 'Fin',
        'status' => 'Statut',
        'actions' => 'Actions',
    ],

    'filters' => [
        'search' => 'Rechercher titre, description, organisateur...',
        'from' => 'Du',
        'to' => 'Au',
    ],

    'modal' => [
        'create_meeting' => 'Nouvelle réunion',
        'edit_meeting' => 'Modifier la réunion',
        'subtitle' => 'Les données sont enregistrées dans Google Calendar avec lien Meet.',
    ],

    'form' => [
        'title' => 'Titre',
        'start' => 'Début',
        'end' => 'Fin',
        'location' => 'Lieu',
        'location_placeholder' => 'Bureau, visio, etc.',
        'visibility' => 'Visibilité',
        'notifications' => 'Notifications',
        'attendees' => 'Participants (`,` ou touche Tab pour valider)',
        'attendees_placeholder' => 'Ajouter un email participant...',
        'auto_meet_link' => 'Générer un lien Google Meet automatiquement',
        'description' => 'Description',
    ],

    'visibility' => [
        'default' => 'Par défaut',
        'public' => 'Public',
        'private' => 'Privé',
        'confidential' => 'Confidentiel',
    ],

    'notifications' => [
        'all' => 'Tous',
        'external_only' => 'Externes',
        'none' => 'Aucune',
    ],

    'badges' => [
        'meet_link' => 'Lien Meet',
        'no_link' => 'Sans lien',
    ],

    'tooltips' => [
        'join_meet' => 'Rejoindre Meet',
        'open_calendar_module' => 'Ouvrir dans notre module Google Calendar',
        'install_calendar' => 'Installer Google Calendar depuis Marketplace',
    ],

    'status' => [
        'confirmed' => 'Confirmée',
        'tentative' => 'Tentative',
        'cancelled' => 'Annulée',
        'unknown' => 'Inconnu',
    ],

    'confirm' => [
        'disconnect_title' => 'Déconnecter Google Meet ?',
        'disconnect_message' => 'Les tokens OAuth seront supprimés pour ce tenant.',
        'disconnect_button' => 'Déconnecter',
        'delete_title' => 'Supprimer cette réunion ?',
        'delete_message' => 'La réunion ":title" sera supprimée de Google Calendar.',
        'delete_button' => 'Supprimer',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'L’état OAuth ne correspond pas à la session en cours.',
        'extension_inactive' => 'Google Meet n’est pas actif pour ce tenant. Activez-le depuis le Marketplace.',
        'storage_missing' => 'Les tables Google Meet sont absentes. Exécutez : php artisan migrate',
        'client_id_missing' => 'GOOGLE_MEET_CLIENT_ID est manquant.',
        'invalid_oauth_state' => 'État OAuth invalide.',
        'not_connected' => 'Google Meet n’est pas connecté pour ce tenant.',
        'session_expired' => 'Session Google Meet expirée ou révoquée. Reconnectez votre compte Google.',
        'calendar_missing' => 'Le calendrier sélectionné n’existe pas pour ce tenant.',
        'no_calendar_selected' => 'Aucun calendrier sélectionné.',
        'end_after_start' => 'La date de fin de réunion doit être après la date de début.',
        'event_id_missing' => 'L’identifiant d’événement Google Meet est manquant.',
        'google_session_invalid' => 'Session Google invalide ou expirée. Reconnectez Google Meet.',
        'google_event_not_found' => 'Réunion introuvable sur Google Calendar',
        'google_permission_denied' => 'Google a refusé la requête. Vérifiez les scopes OAuth et les droits du compte.',
        'google_access_blocked' => 'Accès Google bloqué. Vérifiez la configuration OAuth et les URI de redirection.',
        'unexpected' => 'Erreur Google Meet inattendue.',
        'load_calendars' => 'Impossible de charger les calendriers.',
        'select_calendar' => 'Impossible de sélectionner ce calendrier.',
        'load_meetings' => 'Impossible de charger les réunions.',
        'sync' => 'La synchronisation a échoué.',
        'disconnect' => 'Impossible de déconnecter Google Meet.',
        'delete' => 'Impossible de supprimer la réunion.',
        'save' => 'Impossible d’enregistrer la réunion.',
        'validation' => 'Merci de corriger les erreurs du formulaire.',
        'invalid_email_title' => 'Email invalide',
        'invalid_email_message' => '":email" n’est pas un email valide.',
    ],

    'success' => [
        'connected' => 'Google Meet connecté avec succès.',
        'disconnected' => 'Google Meet déconnecté.',
        'calendar_selected' => 'Calendrier sélectionné avec succès.',
        'calendar_selected_short' => 'Calendrier sélectionné.',
        'sync_count' => ':count réunion(s) synchronisée(s).',
        'sync' => 'Synchronisation terminée.',
        'meeting_created' => 'Réunion Google Meet créée avec succès.',
        'meeting_updated' => 'Réunion mise à jour avec succès.',
        'meeting_deleted' => 'Réunion supprimée.',
        'disconnected_title' => 'Déconnecté',
        'disconnected_message' => 'Google Meet déconnecté.',
        'deleted_title' => 'Supprimée',
        'deleted_message' => 'Réunion supprimée.',
        'saved' => 'Réunion enregistrée.',
    ],

    'validation' => [
        'calendar_required' => 'Veuillez sélectionner un calendrier.',
        'calendar' => 'Veuillez sélectionner un calendrier.',
        'summary_required' => 'Le titre de la réunion est obligatoire.',
        'summary_min' => 'Le titre doit contenir au moins 2 caractères.',
        'title_required' => 'Le titre est obligatoire.',
        'start_required' => 'La date de début est obligatoire.',
        'end_required' => 'La date de fin est obligatoire.',
        'end_after' => 'La date de fin doit être après la date de début.',
        'end_after_start' => 'La date de fin doit être après la date de début.',
        'send_updates_in' => 'La valeur de notification est invalide.',
        'attendees_invalid' => 'Un ou plusieurs e-mails participants sont invalides.',
    ],
];
