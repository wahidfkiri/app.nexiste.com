<?php

return [
    'titles' => [
        'roles_permissions' => 'Rôles et permissions',
        'permissions' => 'Permissions',
        'new_role' => 'Nouveau rôle',
        'edit_role' => 'Modifier le rôle',
    ],

    'breadcrumbs' => [
        'admin' => 'Administration',
        'roles_permissions' => 'Rôles et permissions',
        'permissions' => 'Permissions',
        'new_role' => 'Nouveau rôle',
        'edit_role' => 'Modifier',
    ],

    'headings' => [
        'roles_permissions' => 'Rôles et permissions',
        'permissions_available' => 'Permissions disponibles',
        'new_role' => 'Nouveau rôle',
        'edit_role' => 'Modifier le rôle',
        'quick_permissions_selection' => 'Sélection rapide des permissions',
        'role_identity' => 'Identité du rôle',
        'permissions_summary' => 'Résumé des permissions',
        'information' => 'Informations',
        'quick_actions' => 'Actions',
    ],

    'subtitles' => [
        'roles_index' => "Définissez les rôles de votre équipe et leurs droits d'accès.",
        'permissions_index' => 'Référentiel de toutes les permissions du système, organisées par module.',
        'new_role' => "Définissez un rôle et ses droits d'accès.",
        'instant_sync' => 'Modifiez les permissions directement ici, elles sont sauvegardées instantanément.',
        'role_active_help' => 'Les membres avec ce rôle peuvent se connecter.',
        'system_role_warning' => 'Ce rôle est un rôle système. Seules les permissions peuvent être modifiées.',
    ],

    'stats' => [
        'total_roles' => 'Total rôles',
        'custom_roles' => 'Rôles personnalisés',
        'total_permissions' => 'Permissions disponibles',
        'members_without_role' => 'Membres sans rôle',
    ],

    'table' => [
        'roles' => 'Rôles',
        'role' => 'Rôle',
        'description' => 'Description',
        'permissions' => 'Permissions',
        'members' => 'Membres',
        'type' => 'Type',
        'actions' => 'Actions',
        'display' => 'Affichage de :from à :to sur :total rôles',
    ],

    'filters' => [
        'search_role' => 'Rechercher un rôle...',
        'search_permission' => 'Rechercher une permission...',
    ],

    'buttons' => [
        'view_permissions' => 'Voir les permissions',
        'view_roles' => 'Voir les rôles',
        'new_role' => 'Nouveau rôle',
        'back' => 'Retour',
        'select_all' => 'Tout sélectionner',
        'deselect_all' => 'Tout désélectionner',
        'enable_all' => 'Tout activer',
        'disable_all' => 'Tout désactiver',
        'save_changes' => 'Enregistrer les modifications',
        'save_permissions' => 'Sauvegarder les changements',
        'create_role' => 'Créer le rôle',
        'cancel' => 'Annuler',
        'view' => 'Voir',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'see_all' => 'Voir tous',
    ],

    'labels' => [
        'selected_permissions' => ':count permission(s) sélectionnée(s)',
        'enabled_permissions' => ':count / :total permission(s) activée(s)',
        'slug_auto' => 'Le slug sera généré automatiquement.',
        'internal_slug' => 'Slug interne',
        'role_name' => 'Nom du rôle',
        'role_name_placeholder' => 'Ex: Comptable, Commercial...',
        'description_placeholder' => 'Décrivez les responsabilités de ce rôle...',
        'identification_color' => "Couleur d'identification",
        'custom_color' => 'Couleur personnalisée',
        'preview' => 'Aperçu',
        'active_role' => 'Rôle actif',
        'none_selected' => 'Aucune permission sélectionnée',
        'total' => 'Total :count permission(s)',
        'system' => 'Système',
        'custom' => 'Personnalisé',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'allowed' => 'Autorisé',
        'denied' => 'Refusé',
        'created_on' => 'Créé le',
        'color' => 'Couleur',
        'status' => 'Statut',
        'type' => 'Type',
        'members' => 'Membres (:count)',
        'other_members' => '+ :count autre(s) membre(s)',
        'no_member' => 'Aucun membre avec ce rôle',
        'no_role' => 'Aucun rôle',
        'create_first_role' => 'Créez votre premier rôle personnalisé.',
        'no_role_for_permission' => 'Aucun rôle',
        'system_role_readonly' => 'Rôle système non modifiable',
    ],

    'badges' => [
        'system' => 'Système',
        'default' => 'Par défaut',
        'custom' => 'Personnalisé',
    ],

    'messages' => [
        'role_created' => 'Rôle « :label » créé avec succès.',
        'role_updated' => 'Rôle mis à jour.',
        'role_deleted' => 'Rôle supprimé.',
        'permissions_synced' => 'Permissions synchronisées.',
        'role_assigned' => 'Rôle « :label » assigné à :user.',
        'load_roles_failed' => 'Impossible de charger les rôles.',
        'saved_permissions' => ':count permission(s) active(s) sur ce rôle.',
        'save_failed' => 'Impossible de sauvegarder.',
        'validation_errors' => 'Erreurs de validation.',
    ],

    'confirmations' => [
        'delete_role_title' => 'Supprimer le rôle « :label » ?',
        'delete_role_message' => 'Ce rôle sera retiré de tous les membres qui le possèdent.',
    ],

    'toasts' => [
        'error' => 'Erreur',
        'deleted' => 'Supprimé',
        'role_created' => 'Rôle créé !',
        'role_updated' => 'Rôle mis à jour !',
        'permissions_saved' => 'Permissions sauvegardées !',
    ],

    'errors' => [
        'assign_owner_forbidden' => 'Seul le propriétaire du tenant peut attribuer le rôle propriétaire.',
        'unauthorized_role_access' => 'Accès non autorisé à ce rôle.',
        'system_role_locked' => 'Les rôles système ne peuvent pas être modifiés.',
        'system_role_delete_forbidden' => 'Impossible de supprimer un rôle système.',
        'default_role_delete_forbidden' => 'Ce rôle par défaut est recréé automatiquement et ne peut pas être supprimé. Vous pouvez le modifier ou le désactiver.',
        'role_assigned_users' => 'Ce rôle est assigné à des utilisateurs. Réassignez-les avant suppression.',
        'role_not_active_tenant' => 'Le rôle ne correspond pas au tenant actif.',
        'role_not_found_tenant' => 'Le rôle sélectionné est introuvable pour ce tenant.',
    ],

    'validation' => [
        'label_required' => 'Le nom du rôle est requis.',
        'label_max' => 'Le nom ne peut pas dépasser 100 caractères.',
        'color_regex' => 'La couleur doit être un code hexadécimal valide (ex: #2563eb).',
        'permission_exists' => 'Une permission sélectionnée est invalide.',
    ],
];
