<?php

return [
    // Commun
    'common' => [
        'month' => 'mois',
        'months' => 'mois',
        'free' => 'Gratuit',
        'trial' => 'Essai',
        'per_month' => '/ mois',
        'save' => 'Enregistrer',
        'cancel' => 'Annuler',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'activate' => 'Activer',
        'deactivate' => 'Désactiver',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'default' => 'Par défaut',
        'discount' => 'Remise',
        'price' => 'Prix',
        'period' => 'Période',
        'currency' => 'Devise',
    ],

    // Administration — forfaits
    'plans' => [
        'title' => 'Forfaits d’abonnement',
        'subtitle' => 'Créez et gérez les forfaits, leurs périodes, prix et remises.',
        'add' => 'Nouveau forfait',
        'name' => 'Nom du forfait',
        'description' => 'Description',
        'monthly_price' => 'Prix mensuel de référence',
        'monthly_price_hint' => 'Sert au calcul automatique des périodes.',
        'is_free' => 'Forfait gratuit (démo)',
        'trial_days' => 'Durée de la période d’essai (jours)',
        'trial_days_hint' => 'La période d’essai n’est utilisable qu’une seule fois par espace.',
        'features' => 'Atouts (un par ligne)',
        'periods' => 'Périodes et prix',
        'periods_hint' => 'Le prix est calculé automatiquement (mensuel × mois − remise) et reste modifiable.',
        'period_months' => 'Nombre de mois',
        'computed_price' => 'Prix calculé',
        'final_price' => 'Prix final',
        'add_period' => 'Ajouter une période',
        'empty' => 'Aucun forfait pour le moment. Créez votre premier forfait.',
        'created' => 'Forfait créé avec succès.',
        'updated' => 'Forfait mis à jour avec succès.',
        'deleted' => 'Forfait supprimé.',
        'status_updated' => 'Statut du forfait mis à jour.',
    ],

    // Administration — moyens de paiement
    'payments' => [
        'title' => 'Moyens de paiement',
        'subtitle' => 'Configurez les moyens de paiement proposés aux clients.',
        'add' => 'Nouveau moyen de paiement',
        'name' => 'Libellé',
        'provider' => 'Fournisseur',
        'provider_paypal' => 'PayPal',
        'provider_manual' => 'Paiement manuel / virement',
        'provider_stripe' => 'Stripe',
        'set_default' => 'Définir par défaut',
        'test' => 'Tester la connexion',
        'test_ok' => 'Test réussi : le moyen de paiement est correctement configuré.',
        'test_failed' => 'Test échoué. Vérifiez la configuration et les clés API.',
        'created' => 'Moyen de paiement ajouté.',
        'updated' => 'Moyen de paiement mis à jour.',
        'default_set' => 'Moyen de paiement défini par défaut.',
        'empty' => 'Aucun moyen de paiement configuré.',
        'keys_notice' => 'Les clés secrètes (API) ne sont jamais stockées en base : elles se configurent dans le fichier .env du serveur.',
    ],

    // Onboarding — choix du forfait
    'onboarding' => [
        'title' => 'Choisissez votre forfait',
        'subtitle' => 'Sélectionnez la formule et la période qui vous conviennent pour activer votre espace.',
        'choose_period' => 'Choisissez la période',
        'start_trial' => 'Démarrer l’essai gratuit',
        'subscribe' => 'S’abonner',
        'trial_badge' => 'Essai de :days jours',
        'trial_once_used' => 'La période d’essai a déjà été utilisée pour cet espace.',
        'payment_method' => 'Moyen de paiement',
        'pay_with' => 'Payer avec :method',
        'total_due' => 'Montant à régler',
        'success' => 'Abonnement activé. Une facture vous a été envoyée par e-mail.',
        'trial_success' => 'Votre essai gratuit est activé. Bienvenue !',
    ],

    // Facture d’abonnement
    'invoice' => [
        'subject' => 'Votre facture d’abonnement — :app',
        'greeting' => 'Bonjour :name,',
        'intro' => 'Merci pour votre abonnement. Vous trouverez votre facture en pièce jointe (PDF).',
        'trial_intro' => 'Votre période d’essai est activée. Voici le récapitulatif de votre commande en pièce jointe.',
        'title' => 'Facture d’abonnement',
        'plan' => 'Forfait',
        'period' => 'Période',
        'amount' => 'Montant',
        'date' => 'Date',
        'valid_until' => 'Valable jusqu’au',
        'thanks' => 'Merci de votre confiance.',
        'footer' => 'Ceci est une facture générée automatiquement.',
    ],

    // Middleware / accès
    'access' => [
        'required' => 'Un abonnement actif est nécessaire pour accéder à cette page.',
        'expired' => 'Votre abonnement a expiré. Merci de le renouveler pour continuer.',
    ],

    // Rappel d’expiration
    'reminder' => [
        'subject' => 'Votre abonnement expire bientôt — :app',
        'greeting' => 'Bonjour :name,',
        'body' => 'Votre abonnement arrive à expiration le :date (dans :days jours). Pensez à le renouveler pour éviter toute interruption de service.',
        'cta' => 'Renouveler mon abonnement',
        'dashboard_alert' => 'Votre abonnement expire le :date (dans :days jours). Renouvelez-le pour éviter toute interruption.',
        'dashboard_expired' => 'Votre abonnement a expiré. Renouvelez-le pour retrouver l’accès complet.',
        'renew' => 'Renouveler',
    ],
];
