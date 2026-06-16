<?php

return [
    'pagination' => [
        'per_page' => 15,
    ],

    'article_statuses' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ],

    'order_statuses' => [
        'draft' => 'Brouillon',
        'ordered' => 'Commandée',
        'received' => 'Reçue',
        'cancelled' => 'Annulée',
    ],

    'delivery_note_statuses' => [
        'draft' => 'Brouillon',
        'validated' => 'Validé',
        'cancelled' => 'Annulé',
    ],

    'movement_types' => [
        'opening_balance' => 'Stock initial',
        'delivery_note_in' => 'BL entrée',
        'delivery_note_out' => 'BL sortie',
        'delivery_note_reversal' => 'Contre-passation BL',
        'adjustment' => 'Ajustement',
        'return' => 'Retour',
    ],
];
