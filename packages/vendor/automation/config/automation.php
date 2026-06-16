<?php

use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationLog;
use Vendor\Automation\Models\AutomationSuggestion;

return [
    'event_prefix' => 'automation.execute',

    'queue' => [
        'connection' => env('AUTOMATION_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'name' => env('AUTOMATION_QUEUE_NAME', 'automation'),
    ],

    'suggestions' => [
        'default_expiration_hours' => 72,
        'enabled_by_default' => true,
    ],

    'models' => [
        'suggestion' => AutomationSuggestion::class,
        'event' => AutomationEvent::class,
        'log' => AutomationLog::class,
    ],

    'providers' => [
        'article_created' => [
            \Vendor\Automation\SuggestionProviders\ArticleCreatedSuggestionProvider::class,
        ],
        'client_created' => [
            \Vendor\Automation\SuggestionProviders\ClientCreatedSuggestionProvider::class,
        ],
        'supplier_created' => [
            \Vendor\Automation\SuggestionProviders\SupplierCreatedSuggestionProvider::class,
        ],
        'invoice_created' => [
            \Vendor\Automation\SuggestionProviders\InvoiceCreatedSuggestionProvider::class,
        ],
        'quote_created' => [
            \Vendor\Automation\SuggestionProviders\QuoteCreatedSuggestionProvider::class,
        ],
        'stock_order_created' => [
            \Vendor\Automation\SuggestionProviders\StockOrderCreatedSuggestionProvider::class,
        ],
        'delivery_note_created' => [
            \Vendor\Automation\SuggestionProviders\DeliveryNoteCreatedSuggestionProvider::class,
        ],
        'delivery_note_validated' => [
            \Vendor\Automation\SuggestionProviders\DeliveryNoteValidatedSuggestionProvider::class,
        ],
        'stock_low_threshold_reached' => [
            \Vendor\Automation\SuggestionProviders\LowStockThresholdReachedSuggestionProvider::class,
        ],
        'project_created' => [
            \Vendor\Automation\SuggestionProviders\ProjectCreatedSuggestionProvider::class,
        ],
        'project_task_created' => [
            \Vendor\Automation\SuggestionProviders\ProjectTaskCreatedSuggestionProvider::class,
        ],
        'user_invited' => [
            \Vendor\Automation\SuggestionProviders\UserInvitedSuggestionProvider::class,
        ],
        'extension_activated' => [
            \Vendor\Automation\SuggestionProviders\ExtensionActivatedSuggestionProvider::class,
        ],
    ],

    'actions' => [
        'send_welcome_email' => \Vendor\Automation\Actions\SendEmailAutomationAction::class,
        'send_followup_meeting_email' => \Vendor\Automation\Actions\SendEmailAutomationAction::class,
        'create_followup_meeting' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'create_quote' => \Vendor\Automation\Actions\CreateQuoteAutomationAction::class,
        'append_article_sheet_row' => \Vendor\Automation\Actions\AppendGoogleSheetRowAutomationAction::class,
        'create_article_google_doc' => \Vendor\Automation\Actions\CreateGoogleDocAutomationAction::class,
        'append_client_sheet_row' => \Vendor\Automation\Actions\AppendGoogleSheetRowAutomationAction::class,
        'create_client_google_doc' => \Vendor\Automation\Actions\CreateGoogleDocAutomationAction::class,
        'append_supplier_sheet_row' => \Vendor\Automation\Actions\AppendGoogleSheetRowAutomationAction::class,
        'create_supplier_google_doc' => \Vendor\Automation\Actions\CreateGoogleDocAutomationAction::class,
        'send_invoice_email' => \Vendor\Automation\Actions\SendEmailAutomationAction::class,
        'schedule_invoice_reminder' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'create_payment_followup_task' => \Vendor\Automation\Actions\CreateProjectTaskAutomationAction::class,
        'append_invoice_sheet_row' => \Vendor\Automation\Actions\AppendGoogleSheetRowAutomationAction::class,
        'create_invoice_google_doc' => \Vendor\Automation\Actions\CreateGoogleDocAutomationAction::class,
        'send_quote_email' => \Vendor\Automation\Actions\SendEmailAutomationAction::class,
        'schedule_quote_followup' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'create_quote_followup_task' => \Vendor\Automation\Actions\CreateProjectTaskAutomationAction::class,
        'append_stock_order_sheet_row' => \Vendor\Automation\Actions\AppendGoogleSheetRowAutomationAction::class,
        'create_stock_order_google_doc' => \Vendor\Automation\Actions\CreateGoogleDocAutomationAction::class,
        'append_delivery_note_sheet_row' => \Vendor\Automation\Actions\AppendGoogleSheetRowAutomationAction::class,
        'create_delivery_note_google_doc' => \Vendor\Automation\Actions\CreateGoogleDocAutomationAction::class,
        'append_low_stock_sheet_row' => \Vendor\Automation\Actions\AppendGoogleSheetRowAutomationAction::class,
        'create_low_stock_google_doc' => \Vendor\Automation\Actions\CreateGoogleDocAutomationAction::class,
        'schedule_project_kickoff' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'schedule_project_task_calendar' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'send_team_invitation_followup_email' => \Vendor\Automation\Actions\SendEmailAutomationAction::class,
        'schedule_user_onboarding_meeting' => \Vendor\Automation\Actions\ScheduleCalendarAutomationAction::class,
        'create_user_onboarding_task' => \Vendor\Automation\Actions\CreateProjectTaskAutomationAction::class,
        'create_project_drive_folder' => \Vendor\Automation\Actions\CreateProjectDriveFolderAction::class,
        'create_project_dropbox_folder' => \Vendor\Automation\Actions\CreateProjectDropboxFolderAction::class,
        'create_project_channel' => \Vendor\Automation\Actions\CreateProjectChannelAction::class,
        'create_notion_page' => \Vendor\Automation\Actions\CreateNotionPageAutomationAction::class,
        'open_extension_workspace' => \Vendor\Automation\Actions\OpenExtensionWorkspaceAction::class,
        'install_extension' => \Vendor\Automation\Actions\DeferredAutomationAction::class,
    ],
];
