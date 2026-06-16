<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuration RBAC - Roles & Permissions
    |--------------------------------------------------------------------------
    | Chaque tenant peut personnaliser ses roles et permissions via l'UI.
    | Les permissions sont groupees par module pour faciliter l'affichage.
    |--------------------------------------------------------------------------
    */

    'system_roles' => ['owner', 'super_admin', 'super-admin'],

    'platform_only_permissions' => [],

    'default_roles' => [
        'owner'   => ['label' => 'Proprietaire',   'color' => '#7c3aed', 'description' => 'Acces total, non modifiable'],
        'admin'   => ['label' => 'Administrateur', 'color' => '#2563eb', 'description' => 'Acces complet a la gestion'],
        'manager' => ['label' => 'Gestionnaire',   'color' => '#0891b2', 'description' => 'Gestion operationnelle'],
        'user'    => ['label' => 'Utilisateur',    'color' => '#059669', 'description' => 'Operations courantes'],
        'viewer'  => ['label' => 'Visiteur',       'color' => '#64748b', 'description' => 'Lecture seule'],
    ],

    'permission_groups' => [
        'dashboard' => [
            'label' => 'Tableau de bord',
            'icon'  => 'fa-gauge-high',
            'permissions' => [
                'dashboard.read' => 'Voir le tableau de bord',
                'home.read'      => 'Voir la page accueil',
            ],
        ],

        'users' => [
            'label' => 'Utilisateurs & Equipe',
            'icon'  => 'fa-users',
            'permissions' => [
                'users.read'        => 'Voir les membres',
                'users.invite'      => 'Inviter des membres',
                'users.update'      => 'Modifier les membres',
                'users.delete'      => 'Supprimer des membres',
                'users.export'      => 'Exporter les membres',
                'roles.read'        => 'Voir les roles',
                'roles.manage'      => 'Gerer les roles et permissions',
                'permissions.read'  => 'Voir le catalogue des permissions',
            ],
        ],

        'clients' => [
            'label' => 'Clients & CRM',
            'icon'  => 'fa-handshake',
            'permissions' => [
                'clients.read'   => 'Voir les clients',
                'clients.create' => 'Creer des clients',
                'clients.update' => 'Modifier les clients',
                'clients.delete' => 'Supprimer des clients',
                'clients.export' => 'Exporter les clients',
                'clients.import' => 'Importer des clients',
            ],
        ],

        'invoices' => [
            'label' => 'Facturation',
            'icon'  => 'fa-file-invoice',
            'permissions' => [
                'invoices.read'     => 'Voir les factures',
                'invoices.create'   => 'Creer des factures',
                'invoices.update'   => 'Modifier les factures',
                'invoices.delete'   => 'Supprimer des factures',
                'invoices.send'     => 'Envoyer des factures',
                'invoices.export'   => 'Exporter les factures',
                'invoices.import'   => 'Importer des factures',
                'quotes.read'       => 'Voir les devis',
                'quotes.create'     => 'Creer des devis',
                'quotes.update'     => 'Modifier les devis',
                'quotes.delete'     => 'Supprimer les devis',
                'quotes.convert'    => 'Convertir un devis en facture',
                'quotes.export'     => 'Exporter les devis',
                'payments.read'     => 'Voir les paiements',
                'payments.create'   => 'Enregistrer des paiements',
                'payments.delete'   => 'Supprimer des paiements',
                'payments.export'   => 'Exporter les paiements',
            ],
        ],

        'stock' => [
            'label' => 'Stock & Inventaire',
            'icon'  => 'fa-warehouse',
            'permissions' => [
                'stock.read'            => 'Voir les articles',
                'stock.create'          => 'Creer des articles',
                'stock.update'          => 'Modifier les articles',
                'stock.delete'          => 'Supprimer des articles',
                'stock.export'          => 'Exporter les articles',
                'stock.import'          => 'Importer des articles',
                'suppliers.read'        => 'Voir les fournisseurs',
                'suppliers.create'      => 'Creer des fournisseurs',
                'suppliers.update'      => 'Modifier les fournisseurs',
                'suppliers.delete'      => 'Supprimer des fournisseurs',
                'suppliers.export'      => 'Exporter les fournisseurs',
                'orders.read'           => 'Voir les commandes',
                'orders.create'         => 'Creer des commandes',
                'orders.update'         => 'Modifier les commandes',
                'orders.delete'         => 'Supprimer des commandes',
                'orders.receive'        => 'Receptionner des commandes',
                'orders.export'         => 'Exporter les commandes',
                'delivery-notes.read'   => 'Voir les bons de livraison',
                'delivery-notes.create' => 'Creer des bons de livraison',
                'delivery-notes.update' => 'Modifier des bons de livraison',
                'delivery-notes.delete' => 'Supprimer des bons de livraison',
                'delivery-notes.manage' => 'Valider ou annuler des bons de livraison',
                'delivery-notes.export' => 'Exporter les bons de livraison',
                'stock-movements.read'  => 'Voir les mouvements de stock',
                'stock-movements.export'=> 'Exporter les mouvements de stock',
            ],
        ],

        'projects' => [
            'label' => 'Projets & Taches',
            'icon'  => 'fa-diagram-project',
            'permissions' => [
                'projects.view'           => 'Voir les projets',
                'projects.create'         => 'Creer des projets',
                'projects.update'         => 'Modifier des projets',
                'projects.delete'         => 'Supprimer des projets',
                'projects.manage_members' => 'Gerer les membres projet',
                'projects.manage_tasks'   => 'Gerer les taches projet',
                'projects.comment'        => 'Commenter les taches',
                'projects.admin'          => 'Administrer les projets',
            ],
        ],

        'reports' => [
            'label' => 'Rapports & Analytiques',
            'icon'  => 'fa-chart-line',
            'permissions' => [
                'reports.read'   => 'Voir les rapports',
                'reports.export' => 'Exporter les rapports',
            ],
        ],

        'marketplace' => [
            'label' => 'Marketplace & Extensions',
            'icon'  => 'fa-puzzle-piece',
            'permissions' => [
                'marketplace.read'    => 'Voir le marketplace',
                'extensions.read'     => 'Voir les applications installees',
                'extensions.manage'   => 'Activer ou desactiver des extensions',
                'extensions.settings' => 'Modifier les parametres des extensions',
            ],
        ],

        'integrations' => [
            'label' => 'Integrations',
            'icon'  => 'fa-plug',
            'permissions' => [
                'google-drive.view'    => 'Voir Google Drive',
                'google-drive.manage'  => 'Gerer Google Drive',
                'dropbox.view'         => 'Voir Dropbox',
                'dropbox.manage'       => 'Gerer Dropbox',
                'google-calendar.view' => 'Voir Google Calendar',
                'google-calendar.manage'=> 'Gerer Google Calendar',
                'google-meet.view'     => 'Voir Google Meet',
                'google-meet.manage'   => 'Gerer Google Meet',
                'google-gmail.view'    => 'Voir Google Gmail',
                'google-gmail.send'    => 'Envoyer des emails Gmail',
                'google-gmail.manage'  => 'Gerer Gmail',
                'google-sheets.view'   => 'Voir Google Sheets',
                'google-sheets.manage' => 'Gerer Google Sheets',
                'google-docx.view'     => 'Voir Google Docs',
                'google-docx.manage'   => 'Gerer Google Docs',
                'slack.view'           => 'Voir Slack',
                'slack.send'           => 'Envoyer des messages Slack',
                'slack.manage'         => 'Gerer Slack',
                'trello.view'          => 'Voir Trello',
                'trello.manage'        => 'Gerer Trello',
                'chatbot.view'         => 'Voir le chatbot',
                'chatbot.manage'       => 'Gerer le chatbot',
                'notion.view'          => 'Voir Notion',
                'notion.create'        => 'Creer des pages Notion',
                'notion.update'        => 'Modifier des pages Notion',
                'notion.delete'        => 'Supprimer des pages Notion',
                'notion.share'         => 'Partager des pages Notion',
                'notion.comment'       => 'Commenter dans Notion',
                'notion.admin'         => 'Administrer Notion',
            ],
        ],

        'automation' => [
            'label' => 'Automatisation',
            'icon'  => 'fa-wand-magic-sparkles',
            'permissions' => [
                'automation.read'   => 'Voir les suggestions automatiques',
                'automation.manage' => 'Accepter ou rejeter les suggestions',
            ],
        ],

        'settings' => [
            'label' => 'Parametres',
            'icon'  => 'fa-gear',
            'permissions' => [
                'settings.read'       => 'Voir les parametres',
                'settings.update'     => 'Modifier les parametres',
                'settings.billing'    => 'Gerer l\'abonnement',
                'data-exports.read'   => 'Voir les exports de donnees',
                'data-exports.create' => 'Demarrer un export de donnees',
                'data-exports.process'=> 'Executer un export de donnees',
            ],
        ],
    ],

    'default_role_permissions' => [
        'owner' => ['*'],
        'admin' => [
            'dashboard.read','home.read',
            'users.read','users.invite','users.update','users.delete','users.export','roles.read','roles.manage','permissions.read',
            'clients.read','clients.create','clients.update','clients.delete','clients.export','clients.import',
            'invoices.read','invoices.create','invoices.update','invoices.delete','invoices.send','invoices.export','invoices.import',
            'quotes.read','quotes.create','quotes.update','quotes.delete','quotes.convert','quotes.export',
            'payments.read','payments.create','payments.delete','payments.export',
            'stock.read','stock.create','stock.update','stock.delete','stock.export','stock.import',
            'suppliers.read','suppliers.create','suppliers.update','suppliers.delete','suppliers.export',
            'orders.read','orders.create','orders.update','orders.delete','orders.receive','orders.export',
            'delivery-notes.read','delivery-notes.create','delivery-notes.update','delivery-notes.delete','delivery-notes.manage','delivery-notes.export',
            'stock-movements.read','stock-movements.export',
            'projects.view','projects.create','projects.update','projects.delete','projects.manage_members','projects.manage_tasks','projects.comment','projects.admin',
            'reports.read','reports.export',
            'marketplace.read','extensions.read','extensions.manage','extensions.settings',
            'google-drive.view','google-drive.manage','dropbox.view','dropbox.manage','google-calendar.view','google-calendar.manage','google-meet.view','google-meet.manage',
            'google-gmail.view','google-gmail.send','google-gmail.manage','google-sheets.view','google-sheets.manage','google-docx.view','google-docx.manage',
            'slack.view','slack.send','slack.manage','trello.view','trello.manage','chatbot.view','chatbot.manage',
            'notion.view','notion.create','notion.update','notion.delete','notion.share','notion.comment','notion.admin',
            'automation.read','automation.manage',
            'settings.read','settings.update','settings.billing','data-exports.read','data-exports.create','data-exports.process',
        ],
        'manager' => [
            'dashboard.read','home.read',
            'users.read','users.invite','users.update','users.export','roles.read','permissions.read',
            'clients.read','clients.create','clients.update','clients.export','clients.import',
            'invoices.read','invoices.create','invoices.update','invoices.send','invoices.export','invoices.import',
            'quotes.read','quotes.create','quotes.update','quotes.convert','quotes.export',
            'payments.read','payments.create','payments.export',
            'stock.read','stock.create','stock.update','stock.export','stock.import',
            'suppliers.read','suppliers.create','suppliers.update','suppliers.export',
            'orders.read','orders.create','orders.update','orders.receive','orders.export',
            'delivery-notes.read','delivery-notes.create','delivery-notes.update','delivery-notes.manage','delivery-notes.export',
            'stock-movements.read','stock-movements.export',
            'projects.view','projects.create','projects.update','projects.manage_members','projects.manage_tasks','projects.comment',
            'reports.read','reports.export',
            'google-drive.view','dropbox.view','google-calendar.view','google-calendar.manage','google-meet.view','google-meet.manage',
            'google-gmail.view','google-gmail.send','google-sheets.view','google-docx.view','slack.view','slack.send','trello.view','trello.manage','chatbot.view',
            'notion.view','notion.create','notion.update','notion.share','notion.comment',
            'automation.read','automation.manage',
            'settings.read','data-exports.read','data-exports.create',
        ],
        'user' => [
            'dashboard.read','home.read',
            'clients.read','clients.create','clients.update',
            'invoices.read','invoices.create',
            'quotes.read','quotes.create',
            'payments.read','payments.create',
            'stock.read','orders.read','delivery-notes.read','stock-movements.read',
            'projects.view','projects.manage_tasks','projects.comment',
            'reports.read',
            'google-drive.view','dropbox.view','google-calendar.view','google-meet.view','google-gmail.view','google-gmail.send','google-sheets.view','google-docx.view','slack.view','slack.send','trello.view','chatbot.view','notion.view','notion.comment',
            'automation.read',
            'settings.read','data-exports.read',
        ],
        'viewer' => [
            'dashboard.read','home.read','clients.read',
        ],
    ],

    'pagination' => [
        'per_page' => 15,
    ],

    'cache' => [
        'enabled' => true,
        'ttl'     => 3600,
        'prefix'  => 'rbac_',
    ],
];
