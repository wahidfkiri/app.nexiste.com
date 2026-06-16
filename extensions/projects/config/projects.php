<?php

return [
    'slug' => 'projects',
    'task_statuses' => [
        'backlog' => 'Backlog',
        'todo' => 'A faire',
        'in_progress' => 'En cours',
        'review' => 'En revue',
        'done' => 'Terminee',
        'blocked' => 'Bloquee',
    ],
    'project_statuses' => [
        'planning' => 'Planification',
        'active' => 'Actif',
        'on_hold' => 'En pause',
        'completed' => 'Termine',
        'archived' => 'Archive',
    ],
    'priorities' => [
        'low' => 'Faible',
        'medium' => 'Moyenne',
        'high' => 'Haute',
        'critical' => 'Critique',
    ],
    'permissions' => [
        'projects.view',
        'projects.create',
        'projects.update',
        'projects.delete',
        'projects.manage_members',
        'projects.manage_tasks',
        'projects.comment',
        'projects.admin',
    ],
];
