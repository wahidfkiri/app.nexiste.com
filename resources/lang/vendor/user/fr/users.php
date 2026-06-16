<?php

return [
    'title'    => 'Gestion de l\'équipe',
    'subtitle' => 'Gérez les membres, rôles et accès',

    'fields' => [
        'name'           => 'Nom complet',
        'email'          => 'Email',
        'phone'          => 'Téléphone',
        'role'           => 'Rôle',
        'status'         => 'Statut',
        'job_title'      => 'Titre / Fonction',
        'department'     => 'Département',
        'last_login'     => 'Dernière connexion',
        'created_at'     => 'Membre depuis',
        'invited_by'     => 'Invité par',
    ],

    'roles' => [
        'owner'   => 'Propriétaire',
        'admin'   => 'Administrateur',
        'manager' => 'Gestionnaire',
        'user'    => 'Utilisateur',
        'viewer'  => 'Visiteur',
    ],

    'statuses' => [
        'active'   => 'Actif',
        'inactive' => 'Inactif',
        'invited'  => 'Invité',
        'suspended'=> 'Suspendu',
    ],

    'actions' => [
        'invite'   => 'Créer un membre',
        'edit'     => 'Modifier',
        'delete'   => 'Supprimer',
        'suspend'  => 'Suspendre',
        'activate' => 'Activer',
        'resend'   => 'Renvoyer l\'invitation',
        'revoke'   => 'Révoquer',
        'export'   => 'Exporter',
    ],

    'messages' => [
        'invited'          => 'Invitation envoyée avec succès.',
        'updated'          => 'Membre mis à jour avec succès.',
        'deleted'          => 'Membre supprimé avec succès.',
        'suspended'        => 'Membre suspendu.',
        'activated'        => 'Membre activé.',
        'invitation_resent'=> 'Invitation renvoyée.',
        'invitation_revoked'=> 'Invitation révoquée.',
        'cannot_delete_owner' => 'Impossible de supprimer le propriétaire.',
        'cannot_delete_self'  => 'Vous ne pouvez pas vous supprimer vous-même.',
    ],
];
