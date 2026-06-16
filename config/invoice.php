<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du module Facturation (Devis & Factures)
    | CRM SaaS — Multi-Tenant International
    |--------------------------------------------------------------------------
    */

    // Modèles
    'models' => [
        'invoice' => \Vendor\Invoice\Models\Invoice::class,
        'quote'   => \Vendor\Invoice\Models\Quote::class,
        'item'    => \Vendor\Invoice\Models\InvoiceItem::class,
        'payment' => \Vendor\Invoice\Models\Payment::class,
        'tax'     => \Vendor\Invoice\Models\TaxRate::class,
    ],

    // Tables
    'table_names' => [
        'invoices'      => 'invoices',
        'quotes'        => 'quotes',
        'invoice_items' => 'invoice_items',
        'quote_items'   => 'quote_items',
        'payments'      => 'payments',
        'tax_rates'     => 'tax_rates',
        'currencies'    => 'currencies',
    ],

    // Pagination
    'pagination' => [
        'per_page'     => 15,
        'max_per_page' => 100,
    ],

    // ─── NUMÉROTATION ──────────────────────────────────────────────────────
    'numbering' => [
        'invoice_prefix' => 'FAC',   // FAC-2024-0001
        'quote_prefix'   => 'DEV',   // DEV-2024-0001
        'separator'      => '-',
        'digits'         => 4,        // zéros de remplissage
        'reset_yearly'   => true,     // repart à 0001 chaque année
    ],

    // ─── STATUTS FACTURES ──────────────────────────────────────────────────
    'invoice_statuses' => [
        'draft'     => 'Brouillon',
        'sent'      => 'Envoyée',
        'viewed'    => 'Vue',
        'partial'   => 'Partiel.',
        'paid'      => 'Payée',
        'overdue'   => 'En retard',
        'cancelled' => 'Annulée',
        'refunded'  => 'Remboursée',
    ],

    // ─── STATUTS DEVIS ─────────────────────────────────────────────────────
    'quote_statuses' => [
        'draft'    => 'Brouillon',
        'sent'     => 'Envoyé',
        'viewed'   => 'Vu',
        'accepted' => 'Accepté',
        'declined' => 'Refusé',
        'expired'  => 'Expiré',
    ],

    // ─── MODES DE PAIEMENT ─────────────────────────────────────────────────
    'payment_methods' => [
        'bank_transfer' => 'Virement bancaire',
        'cheque'        => 'Chèque',
        'cash'          => 'Espèces',
        'card'          => 'Carte bancaire',
        'paypal'        => 'PayPal',
        'stripe'        => 'Stripe',
        'crypto'        => 'Crypto-monnaie',
        'other'         => 'Autre',
    ],

    // ─── CONDITIONS DE PAIEMENT ────────────────────────────────────────────
    'payment_terms' => [
        0   => 'Paiement immédiat',
        15  => 'Net 15 jours',
        30  => 'Net 30 jours',
        45  => 'Net 45 jours',
        60  => 'Net 60 jours',
        90  => 'Net 90 jours',
    ],

    // ─── DEVISES (multi-currencies) ────────────────────────────────────────
    'currencies' => [
        'EUR' => ['name' => 'Euro',             'symbol' => '€',  'position' => 'after',  'decimals' => 2, 'thousands' => ' ',  'decimal_sep' => ','],
        'USD' => ['name' => 'Dollar US',        'symbol' => '$',  'position' => 'before', 'decimals' => 2, 'thousands' => ',',  'decimal_sep' => '.'],
        'GBP' => ['name' => 'Livre Sterling',   'symbol' => '£',  'position' => 'before', 'decimals' => 2, 'thousands' => ',',  'decimal_sep' => '.'],
        'CHF' => ['name' => 'Franc Suisse',     'symbol' => 'Fr', 'position' => 'after',  'decimals' => 2, 'thousands' => "'",  'decimal_sep' => '.'],
        'MAD' => ['name' => 'Dirham Marocain',  'symbol' => 'DH', 'position' => 'after',  'decimals' => 2, 'thousands' => ' ',  'decimal_sep' => ','],
        'TND' => ['name' => 'Dinar Tunisien',   'symbol' => 'DT', 'position' => 'after',  'decimals' => 3, 'thousands' => ' ',  'decimal_sep' => ','],
        'DZD' => ['name' => 'Dinar Algérien',   'symbol' => 'DA', 'position' => 'after',  'decimals' => 2, 'thousands' => ' ',  'decimal_sep' => ','],
        'CAD' => ['name' => 'Dollar Canadien',  'symbol' => '$',  'position' => 'before', 'decimals' => 2, 'thousands' => ' ',  'decimal_sep' => ','],
        'AED' => ['name' => 'Dirham EAU',       'symbol' => 'AED','position' => 'before', 'decimals' => 2, 'thousands' => ',',  'decimal_sep' => '.'],
        'SAR' => ['name' => 'Riyal Saoudien',   'symbol' => 'SR', 'position' => 'before', 'decimals' => 2, 'thousands' => ',',  'decimal_sep' => '.'],
        'XOF' => ['name' => 'Franc CFA',        'symbol' => 'F',  'position' => 'after',  'decimals' => 0, 'thousands' => ' ',  'decimal_sep' => ','],
    ],

    // ─── RETENUE À LA SOURCE ───────────────────────────────────────────────
    'withholding_tax' => [
        'enabled'     => true,
        'label'       => 'Retenue à la source',
        'rates'       => [
            ['label' => '0%',  'value' => 0],
            ['label' => '1.5%','value' => 1.5],
            ['label' => '5%',  'value' => 5],
            ['label' => '10%', 'value' => 10],
            ['label' => '15%', 'value' => 15],
            ['label' => '20%', 'value' => 20],
            ['label' => '25%', 'value' => 25],
        ],
        'default_rate' => 0,
        // Pays utilisant la retenue à la source
        'countries'    => ['TN', 'MA', 'DZ', 'SN', 'CI', 'CM', 'NG'],
    ],

    // ─── TVA ───────────────────────────────────────────────────────────────
    'tax' => [
        'enabled'      => true,
        'default_rate' => 20,
        'rates'        => [0, 5, 7, 10, 13, 19, 20, 21],
        'label'        => 'TVA',
    ],

    // ─── REMISES ───────────────────────────────────────────────────────────
    'discount' => [
        'enabled'       => true,
        'types'         => ['percent' => 'Pourcentage (%)', 'fixed' => 'Montant fixe'],
        'max_percent'   => 100,
        'global'        => true,   // remise globale sur la facture
        'line_level'    => true,   // remise par ligne
    ],

    // ─── EXPORT ────────────────────────────────────────────────────────────
    'export' => [
        'formats'      => ['csv', 'excel', 'pdf'],
        'default'      => 'excel',
        'chunk_size'   => 500,
        'pdf_engine'   => 'dompdf',   // dompdf | snappy
        'pdf_paper'    => 'A4',
        'pdf_orientation' => 'portrait',
    ],

    // ─── IMPORT ────────────────────────────────────────────────────────────
    'import' => [
        'max_file_size'       => 10240, // Ko
        'allowed_extensions'  => ['csv', 'xlsx', 'xls'],
        'chunk_size'          => 250,
    ],

    // ─── VALIDITÉ DEVIS ────────────────────────────────────────────────────
    'quote_validity_days' => 30,

    // ─── RAPPELS AUTOMATIQUES ──────────────────────────────────────────────
    'reminders' => [
        'enabled'       => true,
        'days_before'   => [7, 3, 1],
        'days_after'    => [1, 7, 14, 30],
        'max_reminders' => 3,
    ],

    // ─── CACHE ─────────────────────────────────────────────────────────────
    'cache' => [
        'enabled' => true,
        'ttl'     => 3600,
        'prefix'  => 'invoice_',
    ],

    // ─── ACTIVITÉS ─────────────────────────────────────────────────────────
    'log_activities' => true,

    // ─── NOTIFICATIONS ─────────────────────────────────────────────────────
    'notifications' => [
        'on_create'    => true,
        'on_send'      => true,
        'on_paid'      => true,
        'on_overdue'   => true,
        'channels'     => ['database', 'mail'],
    ],

    // ─── PDF INVOICE TEMPLATE ──────────────────────────────────────────────
    'pdf' => [
        'template'     => 'invoice::pdf.invoice',
        'logo_max_h'   => 80,
        'show_bank'    => true,
        'show_notes'   => true,
        'watermark'    => [
            'draft'     => 'BROUILLON',
            'cancelled' => 'ANNULÉE',
        ],
    ],

    // ─── FORMATS ───────────────────────────────────────────────────────────
    'formats' => [
        'date'     => 'd/m/Y',
        'datetime' => 'd/m/Y H:i',
    ],
];
