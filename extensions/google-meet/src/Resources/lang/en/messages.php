<?php

return [
    'breadcrumb' => [
        'applications' => 'Applications',
    ],

    'common' => [
        'success' => 'Success',
        'error' => 'Error',
        'validation' => 'Validation',
        'unknown' => 'Unknown',
        'never' => 'Never',
        'none' => 'None',
        'no_title' => '(Untitled)',
        'no_data_title' => 'No data',
        'dash' => '-',
    ],

    'page' => [
        'title' => 'Google Meet',
        'subtitle' => 'Schedule and manage your Meet meetings with Google OAuth.',
    ],

    'actions' => [
        'migration_required' => 'Migration required',
        'activate_marketplace' => 'Activate from Marketplace',
        'sync' => 'Sync',
        'new_meeting' => 'New meeting',
        'disconnect' => 'Disconnect',
        'connect_google_meet' => 'Connect Google Meet',
        'connect' => 'Connect',
        'open_marketplace' => 'Open Marketplace',
        'open_app' => 'Open the application',
        'explore_apps' => 'Explore applications',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'reset' => 'Reset',
        'join_meet' => 'Join Meet',
    ],

    'storage' => [
        'title' => 'Database migration required',
        'description' => 'The Google Meet tables are missing. Run the migration before using this module.',
        'command' => 'php artisan migrate',
    ],

    'extension' => [
        'title' => 'Extension not activated',
        'description' => 'Google Meet is available on the platform but not yet activated for this tenant. Activate the application from the Marketplace.',
    ],

    'connection' => [
        'title' => 'Google Meet connection',
        'description' => 'This tenant has not connected Google Meet yet. Start OAuth authentication to sync and manage your meetings.',
    ],

    'stats' => [
        'calendars' => 'Calendars',
        'today' => 'Today',
        'next_7_days' => 'Next 7 days',
        'this_month' => 'This month',
        'active_links' => 'Active links',
    ],

    'account' => [
        'title' => 'Connected account',
        'name' => 'Name',
        'email' => 'Email',
        'connected_at' => 'Connected on',
        'last_sync' => 'Last sync',
    ],

    'calendars' => [
        'title' => 'Calendars',
        'primary' => 'Primary',
        'no_calendars_title' => 'No calendar',
        'no_calendars_desc' => 'Start a sync after connecting.',
    ],

    'table' => [
        'meetings' => 'Meet meetings',
        'count_results' => ':count result(s)',
        'pagination_showing' => 'Showing :from to :to of :total meeting(s)',
        'empty_filtered' => 'No meeting found for the selected filters.',
    ],

    'columns' => [
        'meeting' => 'Meeting',
        'calendar' => 'Calendar',
        'start' => 'Start',
        'end' => 'End',
        'status' => 'Status',
        'actions' => 'Actions',
    ],

    'filters' => [
        'search' => 'Search title, description, organizer...',
        'from' => 'From',
        'to' => 'To',
    ],

    'modal' => [
        'create_meeting' => 'New meeting',
        'edit_meeting' => 'Edit meeting',
        'subtitle' => 'Data is saved to Google Calendar with a Meet link.',
    ],

    'form' => [
        'title' => 'Title',
        'start' => 'Start',
        'end' => 'End',
        'location' => 'Location',
        'location_placeholder' => 'Office, video call, etc.',
        'visibility' => 'Visibility',
        'notifications' => 'Notifications',
        'attendees' => 'Attendees (`,` or Tab key to confirm)',
        'attendees_placeholder' => 'Add an attendee email...',
        'auto_meet_link' => 'Generate a Google Meet link automatically',
        'description' => 'Description',
    ],

    'visibility' => [
        'default' => 'Default',
        'public' => 'Public',
        'private' => 'Private',
        'confidential' => 'Confidential',
    ],

    'notifications' => [
        'all' => 'All',
        'external_only' => 'External',
        'none' => 'None',
    ],

    'badges' => [
        'meet_link' => 'Meet link',
        'no_link' => 'No link',
    ],

    'tooltips' => [
        'join_meet' => 'Join Meet',
        'open_calendar_module' => 'Open in our Google Calendar module',
        'install_calendar' => 'Install Google Calendar from the Marketplace',
    ],

    'status' => [
        'confirmed' => 'Confirmed',
        'tentative' => 'Tentative',
        'cancelled' => 'Cancelled',
        'unknown' => 'Unknown',
    ],

    'confirm' => [
        'disconnect_title' => 'Disconnect Google Meet?',
        'disconnect_message' => 'The OAuth tokens will be deleted for this tenant.',
        'disconnect_button' => 'Disconnect',
        'delete_title' => 'Delete this meeting?',
        'delete_message' => 'The meeting ":title" will be deleted from Google Calendar.',
        'delete_button' => 'Delete',
    ],

    'errors' => [
        'oauth_state_mismatch' => 'The OAuth state does not match the current session.',
        'extension_inactive' => 'Google Meet is not active for this tenant. Activate it from the Marketplace.',
        'storage_missing' => 'The Google Meet tables are missing. Run: php artisan migrate',
        'client_id_missing' => 'GOOGLE_MEET_CLIENT_ID is missing.',
        'invalid_oauth_state' => 'Invalid OAuth state.',
        'not_connected' => 'Google Meet is not connected for this tenant.',
        'session_expired' => 'Google Meet session expired or revoked. Reconnect your Google account.',
        'calendar_missing' => 'The selected calendar does not exist for this tenant.',
        'no_calendar_selected' => 'No calendar selected.',
        'end_after_start' => 'The meeting end date must be after the start date.',
        'event_id_missing' => 'The Google Meet event identifier is missing.',
        'google_session_invalid' => 'Invalid or expired Google session. Reconnect Google Meet.',
        'google_event_not_found' => 'Meeting not found on Google Calendar',
        'google_permission_denied' => 'Google refused the request. Check the OAuth scopes and the account permissions.',
        'google_access_blocked' => 'Google access blocked. Check the OAuth configuration and redirect URIs.',
        'unexpected' => 'Unexpected Google Meet error.',
        'load_calendars' => 'Unable to load the calendars.',
        'select_calendar' => 'Unable to select this calendar.',
        'load_meetings' => 'Unable to load the meetings.',
        'sync' => 'Sync failed.',
        'disconnect' => 'Unable to disconnect Google Meet.',
        'delete' => 'Unable to delete the meeting.',
        'save' => 'Unable to save the meeting.',
        'validation' => 'Please correct the form errors.',
        'invalid_email_title' => 'Invalid email',
        'invalid_email_message' => '":email" is not a valid email.',
    ],

    'success' => [
        'connected' => 'Google Meet connected successfully.',
        'disconnected' => 'Google Meet disconnected.',
        'calendar_selected' => 'Calendar selected successfully.',
        'calendar_selected_short' => 'Calendar selected.',
        'sync_count' => ':count meeting(s) synced.',
        'sync' => 'Sync completed.',
        'meeting_created' => 'Google Meet meeting created successfully.',
        'meeting_updated' => 'Meeting updated successfully.',
        'meeting_deleted' => 'Meeting deleted.',
        'disconnected_title' => 'Disconnected',
        'disconnected_message' => 'Google Meet disconnected.',
        'deleted_title' => 'Deleted',
        'deleted_message' => 'Meeting deleted.',
        'saved' => 'Meeting saved.',
    ],

    'validation' => [
        'calendar_required' => 'Please select a calendar.',
        'calendar' => 'Please select a calendar.',
        'summary_required' => 'The meeting title is required.',
        'summary_min' => 'The title must contain at least 2 characters.',
        'title_required' => 'The title is required.',
        'start_required' => 'The start date is required.',
        'end_required' => 'The end date is required.',
        'end_after' => 'The end date must be after the start date.',
        'end_after_start' => 'The end date must be after the start date.',
        'send_updates_in' => 'The notification value is invalid.',
        'attendees_invalid' => 'One or more attendee emails are invalid.',
    ],
];
