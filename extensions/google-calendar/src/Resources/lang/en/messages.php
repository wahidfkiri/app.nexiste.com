<?php

return [
    'breadcrumb' => [
        'applications' => 'Applications',
    ],

    'common' => [
        'success' => 'Success',
        'error' => 'Error',
        'validation' => 'Validation',
        'none' => 'None',
        'no_title' => '(Untitled)',
        'no_data_title' => 'No data',
        'no_data_message' => 'No data available.',
        'all_day' => 'All day',
        'no_events' => 'No event',
        'more' => 'more',
    ],

    'page' => [
        'title' => 'Google Calendar',
        'subtitle' => 'Sync your calendars and manage your tenant events with Google OAuth.',
    ],

    'actions' => [
        'migration_required' => 'Migration required',
        'activate_marketplace' => 'Activate from Marketplace',
        'sync' => 'Sync',
        'new_event' => 'New event',
        'disconnect' => 'Disconnect',
        'connect_google' => 'Connect Google Calendar',
        'cancel' => 'Cancel',
        'close' => 'Close',
        'save_event' => 'Save',
        'open_google' => 'Open in Google',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],

    'storage' => [
        'title' => 'Database migration required',
        'description' => 'The Google Calendar tables are missing. Run the migration before using this module.',
        'command' => 'php artisan migrate',
    ],

    'extension' => [
        'title' => 'Extension not activated',
        'description' => 'Google Calendar is installed on the platform but not yet activated for this tenant. Activate it from the Marketplace to use OAuth and event synchronization.',
        'open_app_page' => 'Open application page',
        'browse_apps' => 'Browse applications',
    ],

    'connection' => [
        'title' => 'Google Calendar connection',
        'description' => 'This tenant has not connected Google Calendar yet. Start OAuth authentication to enable syncing, calendar selection and full event management.',
        'connect_now' => 'Connect now',
        'open_marketplace' => 'Open Marketplace',
        'oauth_cancelled' => 'Google Calendar authentication cancelled or denied.',
    ],

    'stats' => [
        'calendars' => 'Calendars',
        'events_today' => 'Today\'s events',
        'this_month' => 'This month',
        'next_30_days' => 'Next 30 days',
        'holidays_year' => 'Holidays (year)',
    ],

    'account' => [
        'title' => 'Connected account',
        'name' => 'Name',
        'email' => 'Email',
        'connected' => 'Connected',
        'last_sync' => 'Last sync',
        'unknown' => 'Unknown',
        'never' => 'Never',
    ],

    'calendars' => [
        'title' => 'Calendars',
        'primary' => 'Primary',
        'no_calendars_title' => 'No calendar',
        'no_calendars_desc' => 'Start a sync after connecting Google Calendar.',
    ],

    'table' => [
        'events' => 'Events',
        'count_results' => ':count result(s)',
        'pagination_showing' => 'Showing :from to :to of :total event(s)',
        'empty_filtered' => 'No event found for the selected filters.',
    ],

    'columns' => [
        'title' => 'Title',
        'calendar' => 'Calendar',
        'start' => 'Start',
        'end' => 'End',
        'status' => 'Status',
        'actions' => 'Actions',
    ],

    'filters' => [
        'search' => 'Search title, description, location...',
        'from' => 'From',
        'to' => 'To',
        'include_holidays' => 'Include holidays',
        'reset' => 'Reset',
    ],

    'views' => [
        'aria' => 'Calendar display mode',
        'month' => 'Month',
        'week' => 'Week',
        'day' => 'Day',
        'year' => 'Year',
        'list' => 'List',
    ],

    'period' => [
        'previous' => 'Previous period',
        'today' => 'Today',
        'next' => 'Next period',
    ],

    'modal' => [
        'create_event' => 'Create an event',
        'edit_event' => 'Edit an event',
        'subtitle' => 'Data is saved to Google Calendar and synced locally.',
        'detail_title' => 'Event details',
        'detail_subtitle' => 'Review the information before editing or deleting.',
    ],

    'detail' => [
        'when' => 'When',
        'location' => 'Location',
        'client' => 'Client',
        'source' => 'Source',
        'visibility' => 'Visibility',
        'updated_at' => 'Updated',
        'attendees' => 'Attendees',
        'description' => 'Description',
        'empty' => 'Not provided',
        'no_attendees' => 'No attendee',
        'no_description' => 'No description.',
        'client_optional' => 'Client (optional)',
        'client_module_missing' => 'The Clients module is not installed.',
        'install_client_module' => 'Install the Clients module',
    ],

    'form' => [
        'title' => 'Title',
        'start' => 'Start',
        'end' => 'End',
        'location' => 'Location',
        'visibility' => 'Visibility',
        'reminder' => 'Reminder (min)',
        'reminder_placeholder' => '10',
        'attendees' => 'Attendees (comma-separated emails)',
        'attendees_placeholder' => 'john@company.com, jane@company.com',
        'description' => 'Description',
    ],

    'visibility' => [
        'default' => 'Default',
        'public' => 'Public',
        'private' => 'Private',
        'confidential' => 'Confidential',
    ],

    'status' => [
        'confirmed' => 'Confirmed',
        'tentative' => 'Tentative',
        'cancelled' => 'Cancelled',
        'unknown' => 'Unknown',
    ],

    'badges' => [
        'holiday' => 'Holiday',
    ],

    'validation' => [
        'calendar' => 'Please select a calendar.',
        'title_required' => 'The title is required.',
        'start_required' => 'The start date is required.',
        'end_required' => 'The end date is required.',
        'end_after_start' => 'The end date must be after the start date.',
        'attendees' => 'One or more attendee emails are invalid.',
        'source_type' => 'The source type is invalid.',
    ],

    'errors' => [
        'load_calendars' => 'Unable to load the calendars.',
        'select_calendar' => 'Unable to select this calendar.',
        'load_events' => 'Unable to load the events.',
        'sync' => 'Sync failed.',
        'disconnect' => 'Unable to disconnect Google Calendar.',
        'delete' => 'Unable to delete this event.',
        'save' => 'Unable to save this event.',
        'validation' => 'Please correct the form errors.',
        'client_id_missing' => 'GOOGLE_CALENDAR_CLIENT_ID is missing.',
        'invalid_oauth_state' => 'Invalid OAuth state.',
        'oauth_credentials_missing' => 'The Google Calendar OAuth credentials are missing.',
        'oauth_code_exchange' => 'Unable to exchange the authorization code: :message',
        'not_connected' => 'Google Calendar is not connected for this tenant.',
        'calendar_missing' => 'The selected calendar does not exist for this tenant.',
        'no_calendar_selected' => 'No calendar selected.',
        'no_google_calendar_available' => 'No Google calendar available. Open Google Calendar in the CRM and sync your calendars.',
        'refresh_token_missing' => 'The refresh token is missing. Reconnect your Google account.',
        'session_expired' => 'The Google Calendar session expired or was revoked. Reconnect your Google account.',
        'refresh_access_token' => 'Unable to refresh the access token: :details',
        'api' => 'Google Calendar API error: :message',
        'client_not_found' => 'Client not found for this tenant.',
        'google_event_id_missing' => 'The Google event identifier is missing.',
        'storage_missing' => 'The Google Calendar tables are missing. Run: php artisan migrate',
        'extension_inactive' => 'Google Calendar is not activated for this tenant. Activate it from the Marketplace.',
        'oauth_state_mismatch' => 'The OAuth state does not match the current session.',
    ],

    'success' => [
        'calendar_selected' => 'Calendar selected.',
        'sync' => 'Sync completed.',
        'connected' => 'Google Calendar connected successfully.',
        'disconnected' => 'Google Calendar disconnected.',
        'selected_calendar' => 'Calendar selected successfully.',
        'synced_count' => ':count event(s) synced.',
        'event_created' => 'Event created successfully.',
        'event_updated' => 'Event updated successfully.',
        'event_deleted' => 'Event deleted.',
        'disconnected_title' => 'Disconnected',
        'disconnected_message' => 'Google Calendar has been disconnected.',
        'deleted_title' => 'Deleted',
        'deleted_message' => 'Event deleted.',
        'saved' => 'Event saved.',
    ],

    'confirm' => [
        'disconnect_title' => 'Disconnect Google Calendar?',
        'disconnect_message' => 'The OAuth tokens will be deleted for this tenant.',
        'disconnect_button' => 'Disconnect',
        'delete_title' => 'Delete this event?',
        'delete_message' => 'The event ":title" will be deleted from Google Calendar.',
        'delete_button' => 'Delete',
    ],

    'mode' => [
        'no_events_title' => 'No event',
        'no_events_message' => 'No event found for this period.',
        'load_error_title' => 'Loading error',
    ],
];
