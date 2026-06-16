<?php

return [
    'sanitize' => [
        // Champs sensibles à ne jamais modifier.
        'except' => [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'new_password_confirmation',
            'token',
            '_token',
        ],

        // Champs autorisés à contenir du HTML (ex: éditeur riche).
        'allow_html' => [
            'content',
            'description_html',
        ],
    ],

    'idempotency' => [
        // Durée (secondes) de verrouillage d'une requête déjà soumise.
        'ttl_seconds' => 30,
    ],
];
