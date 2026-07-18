<?php

return [
    'page_title' => 'Dashboard',
    'welcome' => 'Welcome, :name',
    'session_expired' => 'Session expired',
    'access_denied' => 'You do not have access to this workspace.',

    'meta' => [
        'title' => 'Dashboard',
        'subtitle' => 'An executive, fast and actionable view of your activity, modules and integrations.',
        'tenant_fallback' => 'CRM workspace',
        'member' => 'Member',
    ],

    'actions' => [
        'fallback' => 'Action',
        'new_client' => 'New client',
        'new_invoice' => 'New invoice',
        'projects' => 'Projects',
        'applications' => 'Applications',
        'settings' => 'Settings',
    ],

    'command' => [
        'modules' => 'Modules',
        'modules_active' => ':count active',
        'currency' => 'Currency',
        'integrations' => 'Integrations',
        'date' => 'Date',
    ],

    'signals' => [
        'aria_label' => 'Key indicators',
        'fallback_label' => 'Indicator',
        'revenue_month' => 'Monthly revenue',
        'payments' => 'Payments',
        'clients' => 'Clients',
        'clients_hint' => '+:count this month',
        'priorities' => 'Priorities',
        'priorities_hint' => 'Tasks, stock and invoices to handle',
        'integrations' => 'Integrations',
        'integrations_hint' => ':count to reconnect',
    ],

    'finance' => [
        'kicker' => 'Performance',
        'title' => 'Monthly finance',
        'view_invoices' => 'View invoices',
        'issued_revenue' => 'Issued revenue',
        'collected' => 'Collected',
        'pending' => 'Outstanding',
    ],

    'modules' => [
        'kicker' => 'Workspace',
        'title' => 'Active modules',
        'fallback_label' => 'Module',
        'empty_title' => 'No visible module',
        'empty_description' => 'Enable applications or check the role permissions.',
        'clients' => [
            'name' => 'Clients',
            'label' => 'CRM',
            'caption' => ':count new this month',
        ],
        'invoice' => [
            'name' => 'Invoicing',
            'label' => 'Finance',
            'caption' => ':count open invoices',
        ],
        'projects' => [
            'name' => 'Projects',
            'label' => 'Delivery',
            'caption' => ':count urgent tasks',
        ],
        'stock' => [
            'name' => 'Stock',
            'label' => 'Operations',
            'caption' => ':count critical items',
        ],
    ],

    'focus' => [
        'kicker' => 'To handle',
        'title' => 'Operational priorities',
        'fallback_kind' => 'Priority',
        'fallback_title' => 'Action',
        'empty_title' => 'All is quiet',
        'empty_description' => 'No critical priority for now.',
        'open_invoice' => 'Open invoice',
        'invoice_fallback' => 'Invoice',
        'missing_client' => 'Client not provided',
        'upcoming_task' => 'Upcoming task',
        'missing_project' => 'Project not defined',
        'critical_stock' => 'Critical stock',
        'article_fallback' => 'Unnamed item',
        'missing_sku' => 'No SKU',
    ],

    'activity' => [
        'kicker' => 'Timeline',
        'title' => 'Recent activity',
        'fallback_event' => 'Event',
        'empty_title' => 'No activity',
        'empty_description' => 'Workspace events will be listed here.',
        'client_created' => 'Client added',
        'client_fallback' => 'Unnamed client',
        'invoice_created' => 'Invoice created',
        'invoice_fallback' => 'Invoice',
        'project_updated' => 'Project updated',
        'project_update_fallback' => 'Update',
        'draft_resume' => 'Draft to resume',
        'draft_description' => ':type not finalized',
        'app_active' => 'Active application',
        'app_fallback' => 'Application',
    ],

    'charts' => [
        'finance' => [
            'invoices' => 'Invoices',
            'payments' => 'Payments',
        ],
        'projects' => [
            'todo' => 'To do',
            'in_progress' => 'In progress',
            'review' => 'In review',
            'done' => 'Done',
        ],
        'stock' => [
            'critical' => 'Critical',
            'healthy' => 'Healthy',
        ],
        'integrations' => [
            'connected' => 'Connected',
            'attention' => 'To reconnect',
            'installed' => 'To configure',
        ],
    ],

    'integrations' => [
        'states' => [
            'installed' => 'To configure',
            'internal' => 'Internal',
            'connected' => 'Connected',
            'attention' => 'To reconnect',
        ],
        'resources' => [
            'pages' => 'pages',
            'boards' => 'boards',
            'files' => 'files',
            'events' => 'events',
            'spreadsheets' => 'spreadsheets',
            'documents' => 'documents',
            'messages' => 'messages',
            'meetings' => 'meetings',
        ],
        'names' => [
            'notion' => 'Notion',
            'trello' => 'Trello',
            'drive' => 'Drive',
            'dropbox' => 'Dropbox',
            'calendar' => 'Calendar',
            'sheets' => 'Sheets',
            'docs' => 'Docs',
            'gmail' => 'Gmail',
            'meet' => 'Meet',
            'slack' => 'Slack',
            'chatbot' => 'Chatbot',
        ],
    ],

    'trend' => [
        'stable' => 'Stable this month',
        'new_growth' => '+100% vs last month',
        'vs_previous' => ':percent% vs last month',
    ],
];
