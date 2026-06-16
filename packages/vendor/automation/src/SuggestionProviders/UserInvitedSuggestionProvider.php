<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class UserInvitedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $invitation = (array) ($context['invitation'] ?? []);
        $invitationId = (int) ($invitation['id'] ?? 0);

        if ($tenantId <= 0 || $invitationId <= 0) {
            return [];
        }

        $email = (string) ($invitation['email'] ?? 'ce membre');

        $gmailInstalled = $this->extensions->isActive($tenantId, 'google-gmail');
        $calendarInstalled = $this->extensions->isActive($tenantId, 'google-calendar');
        $projectsInstalled = $this->extensions->isActive($tenantId, 'projects');

        return [
            SuggestionDefinition::make(
                $gmailInstalled ? 'send_team_invitation_followup_email' : 'install_extension',
                $gmailInstalled
                    ? "Envoyer un email d'accueil à " . $email
                    : "Installer Google Gmail pour envoyer un email d'accueil à ce membre",
                0.9,
                $gmailInstalled
                    ? ['invitation_id' => $invitationId]
                    : ['extension_slug' => 'google-gmail', 'invitation_id' => $invitationId, 'target_action' => 'send_team_invitation_followup_email'],
                [
                    'integration' => 'google-gmail',
                    'installed' => $gmailInstalled,
                    'target_url' => $this->extensions->targetUrl('google-gmail'),
                ]
            ),
            SuggestionDefinition::make(
                $calendarInstalled ? 'schedule_user_onboarding_meeting' : 'install_extension',
                $calendarInstalled
                    ? "Planifier un rendez-vous d'onboarding pour " . $email
                    : "Installer Google Calendar pour préparer l'onboarding de ce membre",
                0.84,
                $calendarInstalled
                    ? ['invitation_id' => $invitationId]
                    : ['extension_slug' => 'google-calendar', 'invitation_id' => $invitationId, 'target_action' => 'schedule_user_onboarding_meeting'],
                [
                    'integration' => 'google-calendar',
                    'installed' => $calendarInstalled,
                    'target_url' => $this->extensions->targetUrl('google-calendar'),
                ]
            ),
            SuggestionDefinition::make(
                $projectsInstalled ? 'create_user_onboarding_task' : 'install_extension',
                $projectsInstalled
                    ? "Créer une tâche d'intégration pour " . $email
                    : "Installer Projets pour suivre l'intégration de ce membre",
                0.8,
                $projectsInstalled
                    ? ['invitation_id' => $invitationId]
                    : ['extension_slug' => 'projects', 'invitation_id' => $invitationId, 'target_action' => 'create_user_onboarding_task'],
                [
                    'integration' => 'projects',
                    'installed' => $projectsInstalled,
                    'target_url' => $this->extensions->targetUrl('projects'),
                ]
            ),
        ];
    }
}
