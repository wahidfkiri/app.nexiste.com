<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class ExtensionActivatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $activation = (array) ($context['extension_activation'] ?? []);
        $activationId = (int) ($activation['id'] ?? 0);
        $slug = trim((string) ($activation['extension_slug'] ?? ''));
        $name = trim((string) ($activation['extension_name'] ?? ucfirst(str_replace('-', ' ', $slug))));

        if ($tenantId <= 0 || $activationId <= 0 || $slug === '') {
            return [];
        }

        $suggestions = [
            SuggestionDefinition::make(
                'open_extension_workspace',
                $this->primaryWorkspaceLabel($slug, $name),
                0.98,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => $slug,
                    'target_url' => $this->extensions->targetUrl($slug),
                    'target_blank' => $slug === 'notion-workspace',
                    'message' => 'Raccourci vers ' . $name . ' enregistré.',
                ],
                [
                    'integration' => $slug,
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl($slug),
                    'target_blank' => $slug === 'notion-workspace',
                    'primary_label' => 'Marquer traité',
                ]
            ),
        ];

        if ($slug === 'projects') {
            $calendarInstalled = $this->extensions->isActive($tenantId, 'google-calendar');
            $storageInstalled = $this->extensions->preferredInstalled($tenantId, ['google-drive', 'dropbox']) !== null;
            $chatInstalled = $this->extensions->preferredInstalled($tenantId, ['chatbot', 'slack']) !== null;

            if (!$calendarInstalled) {
                $suggestions[] = SuggestionDefinition::make(
                    'install_extension',
                    'Installer Google Calendar pour planifier projets et tâches',
                    0.89,
                    ['extension_slug' => 'google-calendar', 'activation_id' => $activationId, 'target_action' => 'schedule_project_kickoff'],
                    [
                        'integration' => 'google-calendar',
                        'installed' => false,
                        'target_url' => $this->extensions->targetUrl('google-calendar'),
                    ]
                );
            }

            if (!$storageInstalled) {
                $suggestions[] = SuggestionDefinition::make(
                    'install_extension',
                    'Installer Dropbox ou Google Drive pour centraliser les fichiers projet',
                    0.84,
                    ['extension_slug' => 'dropbox', 'activation_id' => $activationId, 'target_action' => 'create_project_dropbox_folder'],
                    [
                        'integration' => 'dropbox',
                        'installed' => false,
                        'target_url' => $this->extensions->targetUrl('dropbox'),
                    ]
                );
            }

            if (!$chatInstalled) {
                $suggestions[] = SuggestionDefinition::make(
                    'install_extension',
                    'Installer Chatbot ou Slack pour ouvrir des canaux projet',
                    0.72,
                    ['extension_slug' => 'chatbot', 'activation_id' => $activationId, 'target_action' => 'create_project_channel'],
                    [
                        'integration' => 'chatbot',
                        'installed' => false,
                        'target_url' => $this->extensions->targetUrl('chatbot'),
                    ]
                );
            }
        }

        if ($slug === 'invoice') {
            $gmailInstalled = $this->extensions->isActive($tenantId, 'google-gmail');
            if (!$gmailInstalled) {
                $suggestions[] = SuggestionDefinition::make(
                    'install_extension',
                    'Installer Google Gmail pour envoyer vos devis et factures',
                    0.9,
                    ['extension_slug' => 'google-gmail', 'activation_id' => $activationId, 'target_action' => 'send_invoice_email'],
                    [
                        'integration' => 'google-gmail',
                        'installed' => false,
                        'target_url' => $this->extensions->targetUrl('google-gmail'),
                    ]
                );
            }
        }

        if ($slug === 'google-calendar' && $this->extensions->isActive($tenantId, 'projects')) {
            $suggestions[] = SuggestionDefinition::make(
                'open_extension_workspace',
                'Ouvrir Projets pour utiliser Google Calendar sur vos projets et tâches',
                0.86,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => 'projects',
                    'target_url' => $this->extensions->targetUrl('projects'),
                    'message' => 'Raccourci Projets enregistré après activation de Google Calendar.',
                ],
                [
                    'integration' => 'projects',
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl('projects'),
                    'primary_label' => 'Marquer traité',
                ]
            );
        }

        if ($slug === 'google-drive' && $this->extensions->isActive($tenantId, 'projects')) {
            $suggestions[] = SuggestionDefinition::make(
                'open_extension_workspace',
                'Ouvrir Projets pour stocker vos fichiers dans Google Drive',
                0.84,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => 'projects',
                    'target_url' => $this->extensions->targetUrl('projects'),
                    'message' => 'Raccourci Projets enregistré après activation de Google Drive.',
                ],
                [
                    'integration' => 'projects',
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl('projects'),
                    'primary_label' => 'Marquer traité',
                ]
            );
        }

        if ($slug === 'dropbox' && $this->extensions->isActive($tenantId, 'projects')) {
            $suggestions[] = SuggestionDefinition::make(
                'open_extension_workspace',
                'Ouvrir Projets pour stocker vos fichiers dans Dropbox',
                0.84,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => 'projects',
                    'target_url' => $this->extensions->targetUrl('projects'),
                    'message' => 'Raccourci Projets enregistré après activation de Dropbox.',
                ],
                [
                    'integration' => 'projects',
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl('projects'),
                    'primary_label' => 'Marquer traité',
                ]
            );
        }

        if ($slug === 'google-drive' && $this->extensions->isActive($tenantId, 'dropbox')) {
            $suggestions[] = SuggestionDefinition::make(
                'open_extension_workspace',
                'Ouvrir Dropbox si vous souhaitez utiliser un second espace de stockage',
                0.78,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => 'dropbox',
                    'target_url' => $this->extensions->targetUrl('dropbox'),
                    'message' => 'Raccourci Dropbox enregistré après activation de Google Drive.',
                ],
                [
                    'integration' => 'dropbox',
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl('dropbox'),
                    'primary_label' => 'Marquer traité',
                ]
            );
        }

        if ($slug === 'dropbox' && $this->extensions->isActive($tenantId, 'google-drive')) {
            $suggestions[] = SuggestionDefinition::make(
                'open_extension_workspace',
                'Ouvrir Google Drive si vous souhaitez utiliser un second espace de stockage',
                0.78,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => 'google-drive',
                    'target_url' => $this->extensions->targetUrl('google-drive'),
                    'message' => 'Raccourci Google Drive enregistré après activation de Dropbox.',
                ],
                [
                    'integration' => 'google-drive',
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl('google-drive'),
                    'primary_label' => 'Marquer traité',
                ]
            );
        }

        if ($slug === 'google-gmail' && $this->extensions->isActive($tenantId, 'invoice')) {
            $suggestions[] = SuggestionDefinition::make(
                'open_extension_workspace',
                'Ouvrir Facturation pour utiliser Gmail sur les devis et factures',
                0.87,
                [
                    'activation_id' => $activationId,
                    'extension_slug' => 'invoice',
                    'target_url' => $this->extensions->targetUrl('invoice'),
                    'message' => 'Raccourci Facturation enregistré après activation de Gmail.',
                ],
                [
                    'integration' => 'invoice',
                    'installed' => true,
                    'target_url' => $this->extensions->targetUrl('invoice'),
                    'primary_label' => 'Marquer traité',
                ]
            );
        }

        return $suggestions;
    }

    protected function primaryWorkspaceLabel(string $slug, string $name): string
    {
        return match ($slug) {
            'google-calendar', 'google-drive', 'dropbox', 'google-gmail', 'google-meet', 'google-sheets', 'google-docx', 'notion-workspace', 'slack' => 'Ouvrir ' . $name . ' pour finaliser la connexion',
            'projects' => 'Ouvrir Projets pour créer votre premier espace de travail',
            'invoice' => 'Ouvrir Facturation pour configurer vos documents',
            default => 'Ouvrir ' . $name . ' pour terminer sa configuration',
        };
    }
}
