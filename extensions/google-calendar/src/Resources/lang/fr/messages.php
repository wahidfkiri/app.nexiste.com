<?php

return [
    'breadcrumb' => [
        'applications' => 'Applications',
    ],

    'common' => [
        'success' => 'Succès',
        'error' => 'Erreur',
        'validation' => 'Validation',
        'none' => 'Aucun',
        'no_title' => '(Sans titre)',
        'no_data_title' => 'Aucune donnée',
        'no_data_message' => 'Aucune donnée disponible.',
        'all_day' => 'Toute la journée',
        'no_events' => 'Aucun événement',
        'more' => 'de plus',
    ],

    'page' => [
        'title' => 'Google Calendar',
        'subtitle' => 'Synchronisez vos calendriers et gérez vos événements du tenant avec OAuth Google.',
    ],

    'actions' => [
        'migration_required' => 'Migration requise',
        'activate_marketplace' => 'Activer depuis Marketplace',
        'sync' => 'Synchroniser',
        'new_event' => 'Nouvel événement',
        'disconnect' => 'Déconnecter',
        'connect_google' => 'Connecter Google Calendar',
        'cancel' => 'Annuler',
        'close' => 'Fermer',
        'save_event' => 'Enregistrer',
        'open_google' => 'Ouvrir dans Google',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
    ],

    'storage' => [
        'title' => 'Migration base de données requise',
        'description' => 'Les tables Google Calendar sont absentes. Exécutez la migration avant d’utiliser ce module.',
        'command' => 'php artisan migrate',
    ],

    'extension' => [
        'title' => 'Extension non activée',
        'description' => 'Google Calendar est installé sur la plateforme mais pas encore activé pour ce tenant. Activez-le depuis Marketplace pour utiliser OAuth et la synchronisation des événements.',
        'open_app_page' => 'Ouvrir la page application',
        'browse_apps' => 'Parcourir les applications',
    ],

    'connection' => [
        'title' => 'Connexion Google Calendar',
        'description' => 'Ce tenant n’a pas encore connecté Google Calendar. Lancez l’authentification OAuth pour activer la synchronisation, la sélection de calendrier et la gestion complète des événements.',
        'connect_now' => 'Se connecter maintenant',
        'open_marketplace' => 'Ouvrir Marketplace',
        'oauth_cancelled' => 'Authentification Google Calendar annulée ou refusée.',
    ],

    'stats' => [
        'calendars' => 'Calendriers',
        'events_today' => 'Événements du jour',
        'this_month' => 'Ce mois',
        'next_30_days' => '30 prochains jours',
        'holidays_year' => 'Jours fériés (année)',
    ],

    'account' => [
        'title' => 'Compte connecté',
        'name' => 'Nom',
        'email' => 'Email',
        'connected' => 'Connecté',
        'last_sync' => 'Dernière synchronisation',
        'unknown' => 'Inconnu',
        'never' => 'Jamais',
    ],

    'calendars' => [
        'title' => 'Calendriers',
        'primary' => 'Principal',
        'no_calendars_title' => 'Aucun calendrier',
        'no_calendars_desc' => 'Lancez une synchronisation après connexion Google Calendar.',
    ],

    'table' => [
        'events' => 'Événements',
        'count_results' => ':count résultat(s)',
        'pagination_showing' => 'Affichage :from à :to sur :total événement(s)',
        'empty_filtered' => 'Aucun événement trouvé pour les filtres sélectionnés.',
    ],

    'columns' => [
        'title' => 'Titre',
        'calendar' => 'Calendrier',
        'start' => 'Début',
        'end' => 'Fin',
        'status' => 'Statut',
        'actions' => 'Actions',
    ],

    'filters' => [
        'search' => 'Rechercher titre, description, lieu...',
        'from' => 'Du',
        'to' => 'Au',
        'include_holidays' => 'Inclure jours fériés',
        'reset' => 'Réinitialiser',
    ],

    'views' => [
        'aria' => 'Mode d’affichage calendrier',
        'month' => 'Mois',
        'week' => 'Semaine',
        'day' => 'Jour',
        'year' => 'Année',
        'list' => 'Liste',
    ],

    'period' => [
        'previous' => 'Période précédente',
        'today' => 'Aujourd’hui',
        'next' => 'Période suivante',
    ],

    'modal' => [
        'create_event' => 'Créer un événement',
        'edit_event' => 'Modifier un événement',
        'subtitle' => 'Les données sont enregistrées sur Google Calendar et synchronisées localement.',
        'detail_title' => 'Détails de l’événement',
        'detail_subtitle' => 'Consultez les informations avant modification ou suppression.',
    ],

    'detail' => [
        'when' => 'Quand',
        'location' => 'Lieu',
        'client' => 'Client',
        'source' => 'Source',
        'visibility' => 'Visibilité',
        'updated_at' => 'Mise à jour',
        'attendees' => 'Participants',
        'description' => 'Description',
        'empty' => 'Non renseigné',
        'no_attendees' => 'Aucun participant',
        'no_description' => 'Aucune description.',
        'client_optional' => 'Client (optionnel)',
        'client_module_missing' => 'Le module Clients n’est pas installé.',
        'install_client_module' => 'Installer le module Clients',
    ],

    'form' => [
        'title' => 'Titre',
        'start' => 'Début',
        'end' => 'Fin',
        'location' => 'Lieu',
        'visibility' => 'Visibilité',
        'reminder' => 'Rappel (min)',
        'reminder_placeholder' => '10',
        'attendees' => 'Participants (emails séparés par virgule)',
        'attendees_placeholder' => 'john@entreprise.com, jane@entreprise.com',
        'description' => 'Description',
    ],

    'visibility' => [
        'default' => 'Par défaut',
        'public' => 'Public',
        'private' => 'Privé',
        'confidential' => 'Confidentiel',
    ],

    'status' => [
        'confirmed' => 'Confirmé',
        'tentative' => 'Provisoire',
        'cancelled' => 'Annulé',
        'unknown' => 'Inconnu',
    ],

    'badges' => [
        'holiday' => 'Férié',
    ],

    'validation' => [
        'calendar' => 'Veuillez sélectionner un calendrier.',
        'title_required' => 'Le titre est obligatoire.',
        'start_required' => 'La date de début est obligatoire.',
        'end_required' => 'La date de fin est obligatoire.',
        'end_after_start' => 'La date de fin doit être après la date de début.',
        'attendees' => 'Un ou plusieurs emails participants sont invalides.',
        'source_type' => 'Le type de source est invalide.',
    ],

    'errors' => [
        'load_calendars' => 'Impossible de charger les calendriers.',
        'select_calendar' => 'Impossible de sélectionner ce calendrier.',
        'load_events' => 'Impossible de charger les événements.',
        'sync' => 'Échec de la synchronisation.',
        'disconnect' => 'Impossible de déconnecter Google Calendar.',
        'delete' => 'Impossible de supprimer cet événement.',
        'save' => 'Impossible d’enregistrer cet événement.',
        'validation' => 'Veuillez corriger les erreurs du formulaire.',
        'client_id_missing' => 'GOOGLE_CALENDAR_CLIENT_ID est manquant.',
        'invalid_oauth_state' => 'État OAuth invalide.',
        'oauth_credentials_missing' => 'Les identifiants OAuth Google Calendar sont manquants.',
        'oauth_code_exchange' => 'Impossible d’échanger le code d’autorisation : :message',
        'not_connected' => 'Google Calendar n’est pas connecté pour ce tenant.',
        'calendar_missing' => 'Le calendrier sélectionné n’existe pas pour ce tenant.',
        'no_calendar_selected' => 'Aucun calendrier sélectionné.',
        'no_google_calendar_available' => 'Aucun agenda Google disponible. Ouvrez Google Calendar dans le CRM et synchronisez vos agendas.',
        'refresh_token_missing' => 'Le jeton de rafraîchissement est manquant. Reconnectez votre compte Google.',
        'session_expired' => 'La session Google Calendar a expiré ou a été révoquée. Reconnectez votre compte Google.',
        'refresh_access_token' => 'Impossible de rafraîchir le jeton d’accès : :details',
        'api' => 'Erreur de l’API Google Calendar : :message',
        'client_not_found' => 'Client introuvable pour ce tenant.',
        'google_event_id_missing' => 'L’identifiant de l’événement Google est manquant.',
        'storage_missing' => 'Les tables Google Calendar sont absentes. Exécutez : php artisan migrate',
        'extension_inactive' => 'Google Calendar n’est pas activé pour ce tenant. Activez-le depuis le Marketplace.',
        'oauth_state_mismatch' => 'L’état OAuth ne correspond pas à la session courante.',
    ],

    'success' => [
        'calendar_selected' => 'Calendrier sélectionné.',
        'sync' => 'Synchronisation terminée.',
        'connected' => 'Google Calendar connecté avec succès.',
        'disconnected' => 'Google Calendar déconnecté.',
        'selected_calendar' => 'Calendrier sélectionné avec succès.',
        'synced_count' => ':count événement(s) synchronisé(s).',
        'event_created' => 'Événement créé avec succès.',
        'event_updated' => 'Événement mis à jour avec succès.',
        'event_deleted' => 'Événement supprimé.',
        'disconnected_title' => 'Déconnecté',
        'disconnected_message' => 'Google Calendar a été déconnecté.',
        'deleted_title' => 'Supprimé',
        'deleted_message' => 'Événement supprimé.',
        'saved' => 'Événement enregistré.',
    ],

    'confirm' => [
        'disconnect_title' => 'Déconnecter Google Calendar ?',
        'disconnect_message' => 'Les tokens OAuth seront supprimés pour ce tenant.',
        'disconnect_button' => 'Déconnecter',
        'delete_title' => 'Supprimer cet événement ?',
        'delete_message' => 'L’événement ":title" sera supprimé de Google Calendar.',
        'delete_button' => 'Supprimer',
    ],

    'mode' => [
        'no_events_title' => 'Aucun événement',
        'no_events_message' => 'Aucun événement trouvé sur cette période.',
        'load_error_title' => 'Erreur de chargement',
    ],
];
