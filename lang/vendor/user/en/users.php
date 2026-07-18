<?php

return [
    'title'    => 'Team management',
    'subtitle' => 'Manage members, roles and access',

    'fields' => [
        'name'           => 'Full name',
        'email'          => 'Email',
        'phone'          => 'Phone',
        'role'           => 'Role',
        'status'         => 'Status',
        'job_title'      => 'Title / Function',
        'department'     => 'Department',
        'last_login'     => 'Last login',
        'created_at'     => 'Member since',
        'invited_by'     => 'Invited by',
    ],

    'roles' => [
        'owner'   => 'Owner',
        'admin'   => 'Administrator',
        'manager' => 'Manager',
        'user'    => 'User',
        'viewer'  => 'Viewer',
    ],

    'statuses' => [
        'active'   => 'Active',
        'inactive' => 'Inactive',
        'invited'  => 'Invited',
        'suspended'=> 'Suspended',
    ],

    'actions' => [
        'invite'   => 'Create a member',
        'edit'     => 'Edit',
        'delete'   => 'Delete',
        'suspend'  => 'Suspend',
        'activate' => 'Activate',
        'resend'   => 'Resend invitation',
        'revoke'   => 'Revoke',
        'export'   => 'Export',
    ],

    'messages' => [
        'invited'          => 'Invitation sent successfully.',
        'updated'          => 'Member updated successfully.',
        'deleted'          => 'Member deleted successfully.',
        'suspended'        => 'Member suspended.',
        'activated'        => 'Member activated.',
        'invitation_resent'=> 'Invitation resent.',
        'invitation_revoked'=> 'Invitation revoked.',
        'cannot_delete_owner' => 'The owner cannot be deleted.',
        'cannot_delete_self'  => 'You cannot delete yourself.',
    ],
];
