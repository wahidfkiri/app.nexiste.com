<?php

return [
    'titles' => [
        'roles_permissions' => 'Roles and permissions',
        'permissions' => 'Permissions',
        'new_role' => 'New role',
        'edit_role' => 'Edit role',
    ],

    'breadcrumbs' => [
        'admin' => 'Administration',
        'roles_permissions' => 'Roles and permissions',
        'permissions' => 'Permissions',
        'new_role' => 'New role',
        'edit_role' => 'Edit',
    ],

    'headings' => [
        'roles_permissions' => 'Roles and permissions',
        'permissions_available' => 'Available permissions',
        'new_role' => 'New role',
        'edit_role' => 'Edit role',
        'quick_permissions_selection' => 'Quick permissions selection',
        'role_identity' => 'Role identity',
        'permissions_summary' => 'Permissions summary',
        'information' => 'Information',
        'quick_actions' => 'Actions',
    ],

    'subtitles' => [
        'roles_index' => 'Define your team roles and their access rights.',
        'permissions_index' => 'Reference of all system permissions, organized by module.',
        'new_role' => 'Define a role and its access rights.',
        'instant_sync' => 'Edit permissions directly here, they are saved instantly.',
        'role_active_help' => 'Members with this role can sign in.',
        'system_role_warning' => 'This is a system role. Only permissions can be modified.',
    ],

    'stats' => [
        'total_roles' => 'Total roles',
        'custom_roles' => 'Custom roles',
        'total_permissions' => 'Available permissions',
        'members_without_role' => 'Members without role',
    ],

    'table' => [
        'roles' => 'Roles',
        'role' => 'Role',
        'description' => 'Description',
        'permissions' => 'Permissions',
        'members' => 'Members',
        'type' => 'Type',
        'actions' => 'Actions',
        'display' => 'Showing :from to :to of :total roles',
    ],

    'filters' => [
        'search_role' => 'Search a role...',
        'search_permission' => 'Search a permission...',
    ],

    'buttons' => [
        'view_permissions' => 'View permissions',
        'view_roles' => 'View roles',
        'new_role' => 'New role',
        'back' => 'Back',
        'select_all' => 'Select all',
        'deselect_all' => 'Deselect all',
        'enable_all' => 'Enable all',
        'disable_all' => 'Disable all',
        'save_changes' => 'Save changes',
        'save_permissions' => 'Save changes',
        'create_role' => 'Create role',
        'cancel' => 'Cancel',
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'see_all' => 'See all',
    ],

    'labels' => [
        'selected_permissions' => ':count permission(s) selected',
        'enabled_permissions' => ':count / :total permission(s) enabled',
        'slug_auto' => 'The slug will be generated automatically.',
        'internal_slug' => 'Internal slug',
        'role_name' => 'Role name',
        'role_name_placeholder' => 'E.g. Accountant, Sales...',
        'description_placeholder' => 'Describe the responsibilities of this role...',
        'identification_color' => 'Identification color',
        'custom_color' => 'Custom color',
        'preview' => 'Preview',
        'active_role' => 'Active role',
        'none_selected' => 'No permission selected',
        'total' => 'Total :count permission(s)',
        'system' => 'System',
        'custom' => 'Custom',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'allowed' => 'Allowed',
        'denied' => 'Denied',
        'created_on' => 'Created on',
        'color' => 'Color',
        'status' => 'Status',
        'type' => 'Type',
        'members' => 'Members (:count)',
        'other_members' => '+ :count other member(s)',
        'no_member' => 'No member with this role',
        'no_role' => 'No role',
        'create_first_role' => 'Create your first custom role.',
        'no_role_for_permission' => 'No role',
        'system_role_readonly' => 'System role, not editable',
    ],

    'badges' => [
        'system' => 'System',
        'default' => 'Default',
        'custom' => 'Custom',
    ],

    'messages' => [
        'role_created' => 'Role ":label" created successfully.',
        'role_updated' => 'Role updated.',
        'role_deleted' => 'Role deleted.',
        'permissions_synced' => 'Permissions synced.',
        'role_assigned' => 'Role ":label" assigned to :user.',
        'load_roles_failed' => 'Unable to load roles.',
        'saved_permissions' => ':count active permission(s) on this role.',
        'save_failed' => 'Unable to save.',
        'validation_errors' => 'Validation errors.',
    ],

    'confirmations' => [
        'delete_role_title' => 'Delete the role ":label"?',
        'delete_role_message' => 'This role will be removed from all members who have it.',
    ],

    'toasts' => [
        'error' => 'Error',
        'deleted' => 'Deleted',
        'role_created' => 'Role created!',
        'role_updated' => 'Role updated!',
        'permissions_saved' => 'Permissions saved!',
    ],

    'errors' => [
        'assign_owner_forbidden' => 'Only the tenant owner can assign the owner role.',
        'unauthorized_role_access' => 'Unauthorized access to this role.',
        'system_role_locked' => 'System roles cannot be modified.',
        'system_role_delete_forbidden' => 'A system role cannot be deleted.',
        'default_role_delete_forbidden' => 'This default role is recreated automatically and cannot be deleted. You can edit or deactivate it.',
        'role_assigned_users' => 'This role is assigned to users. Reassign them before deletion.',
        'role_not_active_tenant' => 'The role does not match the active tenant.',
        'role_not_found_tenant' => 'The selected role could not be found for this tenant.',
    ],

    'validation' => [
        'label_required' => 'The role name is required.',
        'label_max' => 'The name cannot exceed 100 characters.',
        'color_regex' => 'The color must be a valid hexadecimal code (e.g. #2563eb).',
        'permission_exists' => 'A selected permission is invalid.',
    ],
];
