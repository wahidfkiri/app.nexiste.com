<?php

return [
    'success' => [
        'room_created' => 'Room created successfully.',
        'room_updated' => 'Room updated.',
        'room_archived' => 'Room archived.',
        'message_sent' => 'Message sent.',
        'message_deleted' => 'Message deleted.',
    ],

    'errors' => [
        'storage_missing' => 'The Chatbot tables are missing. Run: php artisan migrate',
        'room_name_required' => 'The room name is required.',
        'room_name_exists' => 'A room with this name already exists.',
        'default_room_delete_forbidden' => 'The default room cannot be deleted.',
        'room_select' => 'Select a room.',
        'room_required' => 'The room is required.',
        'message_empty' => 'The message is empty.',
        'message_delete_forbidden' => 'You are not allowed to delete this message.',
        'room_not_found' => 'Room not found.',
        'room_access_denied' => 'Access denied to this room.',
        'room_invalid' => 'Invalid room.',
        'room_manage_forbidden' => 'You do not have permission to modify this room.',
    ],

    'validation' => [
        'room_name_required' => 'The room name is required.',
        'room_name_max' => 'The room name is too long.',
        'icon_regex' => 'The icon format is invalid.',
        'color_regex' => 'The room color is invalid.',
        'member_exists' => 'A selected member is invalid.',
        'message_empty_with_file_hint' => 'The message is empty. Add text or a file.',
        'room_required' => 'The room is required.',
        'room_exists' => 'The selected room is invalid.',
        'text_max' => 'The message is too long.',
        'file_invalid' => 'The uploaded file is invalid.',
        'files_max' => 'You can send up to 6 files per message.',
        'file_size_max' => 'The file exceeds the maximum allowed size.',
        'file_mime' => 'The file type is not allowed.',
        'file_extension' => 'The file extension is not allowed.',
    ],

    'defaults' => [
        'general_description' => 'General room for your company.',
        'user' => 'User',
        'file' => 'file',
    ],
];
