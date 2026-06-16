<?php

namespace Vendor\Extensions\Console\Commands;

use Illuminate\Console\Command;
use Vendor\Extensions\Models\Extension;

class SeedExtensionsCommand extends Command
{
    protected $signature   = 'extensions:seed {--reset : Supprime et recrée le catalogue}';
    protected $description = 'Peuple le catalogue avec des extensions de démonstration';

    public function handle(): int
    {
        if ($this->option('reset')) {
            Extension::query()->forceDelete();
            $this->warn('  ↺ Catalogue réinitialisé.');
        }

        $extensions = $this->getDemoExtensions();
        $this->info("📦 Création de " . count($extensions) . " extensions...");

        foreach ($extensions as $idx => $data) {
            $ext = Extension::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['sort_order' => ($idx + 1) * 10])
            );
            $this->line("  ✓ <comment>{$ext->name}</comment> " . ($ext->wasRecentlyCreated ? '(créée)' : '(existante)'));
        }

        $this->info('✅ Catalogue prêt.');
        return self::SUCCESS;
    }

    private function getDemoExtensions(): array
    {
        return [
            // ── STOCKAGE ──────────────────────────────────────────────────
            [
                'slug'          => 'google-drive',
                'name'          => 'Google Drive',
                'tagline'       => 'Stockez, partagez et accédez à vos fichiers partout',
                'description'   => 'Connectez Google Drive pour joindre des fichiers directement à vos clients et factures.',
                'category'      => 'storage',
                'icon'          => 'fa-google-drive',
                'icon_bg_color' => '#4285F4',
                'developer_name'=> 'Google LLC',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'is_featured'   => true,
                'is_official'   => false,
                'is_verified'   => true,
                'installs_count'=> 1240,
                'rating'        => 4.8,
            ],
            [
                'slug'          => 'dropbox',
                'name'          => 'Dropbox',
                'tagline'       => 'Synchronisez vos documents d\'affaires',
                'description'   => 'Reliez votre espace Dropbox pour un accès rapide à vos fichiers métiers.',
                'category'      => 'storage',
                'icon'          => 'fa-dropbox',
                'icon_bg_color' => '#0061FF',
                'developer_name'=> 'Dropbox Inc.',
                'pricing_type'  => 'freemium',
                'price'         => 9.99,
                'billing_cycle' => 'monthly',
                'status'        => 'active',
                'is_new'        => true,
                'installs_count'=> 430,
                'rating'        => 4.5,
            ],

            // ── COMMUNICATION ─────────────────────────────────────────────
            [
                'slug'          => 'slack',
                'name'          => 'Slack',
                'tagline'       => 'Recevez vos alertes CRM dans Slack',
                'description'   => 'Notifications de nouveaux clients, factures et opportunités directement dans vos canaux Slack.',
                'category'      => 'communication',
                'icon'          => 'fa-slack',
                'icon_bg_color' => '#4A154B',
                'developer_name'=> 'Slack Technologies',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'is_featured'   => true,
                'is_verified'   => true,
                'installs_count'=> 2100,
                'rating'        => 4.9,
            ],
            [
                'slug'          => 'microsoft-teams',
                'name'          => 'Microsoft Teams',
                'tagline'       => 'Intégration native avec l\'écosystème Microsoft',
                'description'   => 'Synchronisez vos contacts, réunions et documents avec Microsoft Teams.',
                'category'      => 'communication',
                'icon'          => 'fa-microsoft',
                'icon_bg_color' => '#6264A7',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'installs_count'=> 890,
                'rating'        => 4.6,
            ],
            [
                'slug'          => 'twilio-sms',
                'name'          => 'Twilio SMS',
                'tagline'       => 'Envoyez des SMS à vos clients',
                'description'   => 'Campagnes SMS, rappels de rendez-vous et notifications personnalisées.',
                'category'      => 'communication',
                'icon'          => 'fa-sms',
                'icon_bg_color' => '#F22F46',
                'developer_name'=> 'Twilio Inc.',
                'pricing_type'  => 'usage',
                'price'         => 0.05,
                'status'        => 'active',
                'has_trial'     => true,
                'trial_days'    => 30,
                'installs_count'=> 320,
                'rating'        => 4.4,
            ],

            // ── IA ────────────────────────────────────────────────────────
            [
                'slug'          => 'nexus-ai',
                'name'          => 'Nexus AI Assistant',
                'tagline'       => 'IA générative pour votre CRM',
                'description'   => 'Générez des emails, résumés clients et analyses commerciales grâce à l\'IA.',
                'category'      => 'ai',
                'icon'          => 'fa-robot',
                'icon_bg_color' => '#f59e0b',
                'pricing_type'  => 'paid',
                'price'         => 29.00,
                'billing_cycle' => 'monthly',
                'has_trial'     => true,
                'trial_days'    => 14,
                'status'        => 'active',
                'is_featured'   => true,
                'is_official'   => true,
                'is_new'        => true,
                'installs_count'=> 540,
                'rating'        => 4.9,
            ],
            [
                'slug'          => 'chatgpt-integration',
                'name'          => 'ChatGPT',
                'tagline'       => 'Intégration OpenAI ChatGPT',
                'description'   => 'Utilisez GPT-4 pour automatiser vos réponses clients et créer du contenu.',
                'category'      => 'ai',
                'icon'          => 'fa-brain',
                'icon_bg_color' => '#10a37f',
                'developer_name'=> 'OpenAI',
                'pricing_type'  => 'paid',
                'price'         => 19.00,
                'billing_cycle' => 'monthly',
                'has_trial'     => true,
                'trial_days'    => 7,
                'status'        => 'active',
                'installs_count'=> 710,
                'rating'        => 4.7,
            ],

            // ── MARKETING ─────────────────────────────────────────────────
            [
                'slug'          => 'mailchimp',
                'name'          => 'Mailchimp',
                'tagline'       => 'Synchronisez vos listes et campagnes email',
                'description'   => 'Exportez vos contacts vers Mailchimp et déclenchez des campagnes depuis le CRM.',
                'category'      => 'marketing',
                'icon'          => 'fa-mailchimp',
                'icon_bg_color' => '#FFE01B',
                'developer_name'=> 'Intuit Mailchimp',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'installs_count'=> 980,
                'rating'        => 4.5,
            ],
            [
                'slug'          => 'hubspot',
                'name'          => 'HubSpot CRM',
                'tagline'       => 'Synchronisation bidirectionnelle HubSpot',
                'description'   => 'Importez/exportez contacts, deals et activités entre NexusCRM et HubSpot.',
                'category'      => 'marketing',
                'icon'          => 'fa-hubspot',
                'icon_bg_color' => '#FF7A59',
                'developer_name'=> 'HubSpot Inc.',
                'pricing_type'  => 'freemium',
                'price'         => 49.00,
                'billing_cycle' => 'monthly',
                'status'        => 'active',
                'is_verified'   => true,
                'installs_count'=> 620,
                'rating'        => 4.6,
            ],

            // ── PRODUCTIVITÉ ──────────────────────────────────────────────
            [
                'slug'          => 'google-calendar',
                'name'          => 'Google Calendar',
                'tagline'       => 'Synchronisez vos rendez-vous clients',
                'description'   => 'Créez des événements depuis vos fiches clients, synchronisation temps réel.',
                'category'      => 'productivity',
                'icon'          => 'fa-calendar-days',
                'icon_bg_color' => '#4285F4',
                'developer_name'=> 'Google LLC',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'is_featured'   => true,
                'installs_count'=> 1560,
                'rating'        => 4.8,
            ],
            [
                'slug'           => 'trello-integration',
                'name'           => 'Trello Integration',
                'tagline'        => 'Boards Trello modernes directement dans le CRM',
                'description'    => 'Connectez Trello pour retrouver vos boards, listes et cartes dans une interface SaaS dediee et fluide.',
                'category'       => 'productivity',
                'icon'           => 'fab fa-trello',
                'icon_bg_color'  => '#026aa7',
                'developer_name' => 'Nexus CRM',
                'pricing_type'   => 'free',
                'status'         => 'active',
                'is_featured'    => true,
                'is_official'    => true,
                'is_verified'    => true,
                'installs_count' => 180,
                'rating'         => 4.8,
            ],

            // ── PRODUCTIVITÉ ──────────────────────────────────────────────
            [
                'slug'           => 'google-sheets',
                'name'           => 'Google Sheets',
                'tagline'        => 'Créez et gérez vos feuilles de calcul Google',
                'description'    => 'Connectez Google Sheets pour créer, lire, modifier et supprimer des feuilles de calcul directement depuis le CRM.',
                'category'       => 'productivity',
                'icon'           => 'fa-file-excel',
                'icon_bg_color'  => '#0f9d58',
                'developer_name' => 'Google LLC',
                'pricing_type'   => 'free',
                'status'         => 'active',
                'is_featured'    => true,
                'is_official'    => false,
                'is_verified'    => true,
                'installs_count' => 980,
                'rating'         => 4.7,
            ],
            [
                'slug'           => 'google-docx',
                'name'           => 'Google Docs',
                'tagline'        => 'Create and manage your Google documents',
                'description'    => 'Connect Google Docs to create, read, edit, duplicate and export your documents directly from the CRM.',
                'category'       => 'productivity',
                'icon'           => 'fa-file-word',
                'icon_bg_color'  => '#1a73e8',
                'developer_name' => 'Google LLC',
                'pricing_type'   => 'free',
                'status'         => 'active',
                'is_featured'    => true,
                'is_official'    => false,
                'is_verified'    => true,
                'installs_count' => 640,
                'rating'         => 4.6,
            ],
            [
                'slug'           => 'google-gmail',
                'name'           => 'Google Gmail',
                'tagline'        => 'Read, send and manage your Gmail inbox',
                'description'    => 'Connect Gmail to read, send, reply, forward, archive and manage email directly inside the CRM.',
                'category'       => 'communication',
                'icon'           => 'fa-envelope-open-text',
                'icon_bg_color'  => '#ea4335',
                'developer_name' => 'Google LLC',
                'pricing_type'   => 'free',
                'status'         => 'active',
                'is_featured'    => true,
                'is_official'    => false,
                'is_verified'    => true,
                'installs_count' => 520,
                'rating'         => 4.7,
            ],
            [
                'slug'           => 'google-meet',
                'name'           => 'Google Meet',
                'tagline'        => 'Plan, host and manage your Meet meetings',
                'description'    => 'Connect Google Meet to create, schedule, update and manage meeting links directly in the CRM.',
                'category'       => 'communication',
                'icon'           => 'fa-video',
                'icon_bg_color'  => '#34a853',
                'developer_name' => 'Google LLC',
                'pricing_type'   => 'free',
                'status'         => 'active',
                'is_featured'    => true,
                'is_official'    => false,
                'is_verified'    => true,
                'installs_count' => 410,
                'rating'         => 4.6,
            ],
            [
                'slug'          => 'zapier',
                'name'          => 'Zapier',
                'tagline'       => 'Connectez 5000+ applications',
                'description'   => 'Automatisez vos workflows en connectant NexusCRM à toutes vos applications via Zapier.',
                'category'      => 'integration',
                'icon'          => 'fa-bolt',
                'icon_bg_color' => '#FF4A00',
                'developer_name'=> 'Zapier Inc.',
                'pricing_type'  => 'freemium',
                'price'         => 19.99,
                'billing_cycle' => 'monthly',
                'has_trial'     => true,
                'trial_days'    => 14,
                'status'        => 'active',
                'is_verified'   => true,
                'installs_count'=> 750,
                'rating'        => 4.7,
            ],

            // ── FINANCE ───────────────────────────────────────────────────
            [
                'slug'          => 'stripe-payments',
                'name'          => 'Stripe',
                'tagline'       => 'Encaissez vos factures en ligne',
                'description'   => 'Envoyez des liens de paiement Stripe depuis vos factures NexusCRM.',
                'category'      => 'finance',
                'icon'          => 'fa-stripe',
                'icon_bg_color' => '#635BFF',
                'developer_name'=> 'Stripe Inc.',
                'pricing_type'  => 'free',
                'status'        => 'active',
                'is_official'   => true,
                'is_featured'   => true,
                'installs_count'=> 1890,
                'rating'        => 4.9,
            ],
            [
                'slug'          => 'quickbooks',
                'name'          => 'QuickBooks',
                'tagline'       => 'Synchronisation comptable complète',
                'description'   => 'Exportez automatiquement vos factures vers QuickBooks pour la comptabilité.',
                'category'      => 'finance',
                'icon'          => 'fa-calculator',
                'icon_bg_color' => '#2CA01C',
                'developer_name'=> 'Intuit Inc.',
                'pricing_type'  => 'paid',
                'price'         => 15.00,
                'billing_cycle' => 'monthly',
                'status'        => 'active',
                'installs_count'=> 430,
                'rating'        => 4.4,
            ],
        ];
    }
}

