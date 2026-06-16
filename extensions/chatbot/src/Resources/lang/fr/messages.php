<?php

return [
    'success' => [
        'room_created' => 'Salon créé avec succès.',
        'room_updated' => 'Salon mis à jour.',
        'room_archived' => 'Salon archivé.',
        'message_sent' => 'Message envoyé.',
        'message_deleted' => 'Message supprimé.',
    ],

    'errors' => [
        'storage_missing' => 'Les tables Chatbot sont absentes. Exécutez : php artisan migrate',
        'room_name_required' => 'Le nom du salon est obligatoire.',
        'room_name_exists' => 'Un salon avec ce nom existe déjà.',
        'default_room_delete_forbidden' => 'Le salon par défaut ne peut pas être supprimé.',
        'room_select' => 'Sélectionnez un salon.',
        'room_required' => 'Le salon est obligatoire.',
        'message_empty' => 'Le message est vide.',
        'message_delete_forbidden' => 'Vous n\'avez pas le droit de supprimer ce message.',
        'room_not_found' => 'Salon introuvable.',
        'room_access_denied' => 'Accès refusé à ce salon.',
        'room_invalid' => 'Salon invalide.',
        'room_manage_forbidden' => 'Vous n\'avez pas la permission de modifier ce salon.',
    ],

    'validation' => [
        'room_name_required' => 'Le nom du salon est obligatoire.',
        'room_name_max' => 'Le nom du salon est trop long.',
        'icon_regex' => 'Le format de l\'icône est invalide.',
        'color_regex' => 'La couleur du salon est invalide.',
        'member_exists' => 'Un membre sélectionné est invalide.',
        'message_empty_with_file_hint' => 'Le message est vide. Ajoutez du texte ou un fichier.',
        'room_required' => 'Le salon est obligatoire.',
        'room_exists' => 'Le salon sélectionné est invalide.',
        'text_max' => 'Le message est trop long.',
        'file_invalid' => 'Le fichier envoyé est invalide.',
        'files_max' => 'Vous pouvez envoyer jusqu\'à 6 fichiers par message.',
        'file_size_max' => 'Le fichier dépasse la taille maximale autorisée.',
        'file_mime' => 'Le type de fichier n\'est pas autorisé.',
        'file_extension' => 'L\'extension du fichier n\'est pas autorisée.',
    ],

    'defaults' => [
        'general_description' => 'Salon général de votre entreprise.',
        'user' => 'Utilisateur',
        'file' => 'fichier',
    ],
];
