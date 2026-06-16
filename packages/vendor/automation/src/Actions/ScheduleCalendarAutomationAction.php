<?php

namespace Vendor\Automation\Actions;

use Carbon\Carbon;
use RuntimeException;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Projects\Models\ProjectTask;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Client\Models\Client;
use Vendor\GoogleCalendar\Services\GoogleCalendarService;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;
use Vendor\User\Models\UserInvitation;

class ScheduleCalendarAutomationAction extends AbstractAutomationAction
{
    public function __construct(
        \Vendor\Automation\Services\ExtensionAvailabilityService $extensions,
        protected GoogleCalendarService $calendarService
    ) {
        parent::__construct($extensions);
    }

    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return $this->withReconnectHandling('google-calendar', function () use ($automationEvent, $suggestion) {
            return match ((string) $automationEvent->action_type) {
                'create_followup_meeting' => $this->createFollowupMeeting($automationEvent, $suggestion),
                'schedule_invoice_reminder' => $this->scheduleInvoiceReminder($automationEvent, $suggestion),
                'schedule_quote_followup' => $this->scheduleQuoteFollowup($automationEvent, $suggestion),
                'schedule_project_kickoff' => $this->scheduleProjectKickoff($automationEvent, $suggestion),
                'schedule_project_task_calendar' => $this->scheduleProjectTaskCalendar($automationEvent, $suggestion),
                'schedule_user_onboarding_meeting' => $this->scheduleUserOnboardingMeeting($automationEvent, $suggestion),
                default => throw new RuntimeException('Type de planification calendrier non pris en charge.'),
            };
        });
    }

    protected function createFollowupMeeting(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->ensureCalendarAvailability($tenantId);

        $payload = $this->payload($automationEvent);
        $clientId = $this->modelId($payload, $suggestion, 'client_id', Client::class);
        if (!$clientId) {
            throw new RuntimeException('Client introuvable pour le rendez-vous.');
        }

        $client = $this->loadClient($tenantId, $clientId);
        $startAt = $client->next_follow_up_at
            ? Carbon::parse($client->next_follow_up_at)->timezone($this->defaultTimezone())
            : now($this->defaultTimezone())->addDay()->setTime(10, 0);
        $endAt = $startAt->copy()->addHour();

        $event = $this->calendarService->createEvent($tenantId, [
            'summary' => 'Rendez-vous client - ' . $this->clientDisplayName($client),
            'description' => implode("\n", array_filter([
                'Suivi commercial automatise pour ' . $this->clientDisplayName($client),
                $client->contact_name ? 'Contact: ' . $client->contact_name : null,
                $client->phone ? 'Telephone: ' . $client->phone : null,
                $this->sourceUrlForModel($client) ? 'Fiche CRM: ' . $this->sourceUrlForModel($client) : null,
            ])),
            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt->toIso8601String(),
            'all_day' => false,
            'timezone' => $this->defaultTimezone(),
            'client_id' => (int) $client->id,
            'source_type' => 'manual',
            'source_id' => (int) $client->id,
            'source_label' => $this->clientDisplayName($client),
            'reminder_minutes' => 60,
        ]);

        $client->forceFill(['next_follow_up_at' => $startAt])->save();

        return [
            'result' => 'calendar_event_created',
            'message' => 'Rendez-vous interne planifié avec succes.',
            'calendar_id' => (string) ($event['calendar_id'] ?? ''),
            'event_id' => (string) ($event['event_id'] ?? ''),
            'client_id' => (int) $client->id,
            'target_url' => $event['html_link'] ?? $this->routeUrl('google-calendar.index'),
            'target_blank' => true,
        ];
    }

    protected function scheduleInvoiceReminder(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->ensureCalendarAvailability($tenantId);

        $payload = $this->payload($automationEvent);
        $invoiceId = $this->modelId($payload, $suggestion, 'invoice_id', Invoice::class);
        if (!$invoiceId) {
            throw new RuntimeException('Facture introuvable pour le rappel.');
        }

        $invoice = $this->loadInvoice($tenantId, $invoiceId);
        if (!$invoice->due_date) {
            throw new RuntimeException('Cette facture ne possede pas de date d echeance.');
        }

        $startAt = Carbon::parse($invoice->due_date->format('Y-m-d') . ' 10:00:00', $this->defaultTimezone())->subDay();
        if ($startAt->lessThanOrEqualTo(now($this->defaultTimezone()))) {
            $startAt = now($this->defaultTimezone())->addHour()->minute(0)->second(0);
        }
        $endAt = $startAt->copy()->addHour();

        $event = $this->calendarService->createEvent($tenantId, [
            'summary' => 'Relance facture - ' . $invoice->number,
            'description' => implode("\n", array_filter([
                'Rappel de paiement pour la facture ' . $invoice->number,
                $invoice->client ? 'Client: ' . $this->clientDisplayName($invoice->client) : null,
                'Montant: ' . $this->formatMoney((float) $invoice->amount_due, (string) $invoice->currency),
                $this->routeUrl('invoices.show', $invoice) ? 'Facture CRM: ' . $this->routeUrl('invoices.show', $invoice) : null,
            ])),
            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt->toIso8601String(),
            'all_day' => false,
            'timezone' => $this->defaultTimezone(),
            'client_id' => $invoice->client_id ? (int) $invoice->client_id : null,
            'source_type' => 'manual',
            'source_id' => (int) $invoice->id,
            'source_label' => (string) $invoice->number,
        ]);

        return [
            'result' => 'calendar_event_created',
            'message' => 'Rappel de paiement planifié.',
            'calendar_id' => (string) ($event['calendar_id'] ?? ''),
            'event_id' => (string) ($event['event_id'] ?? ''),
            'invoice_id' => (int) $invoice->id,
            'target_url' => $event['html_link'] ?? $this->routeUrl('google-calendar.index'),
            'target_blank' => true,
        ];
    }

    protected function scheduleQuoteFollowup(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->ensureCalendarAvailability($tenantId);

        $payload = $this->payload($automationEvent);
        $quoteId = $this->modelId($payload, $suggestion, 'quote_id', Quote::class);
        if (!$quoteId) {
            throw new RuntimeException('Devis introuvable pour la relance.');
        }

        $quote = $this->loadQuote($tenantId, $quoteId);
        if (!$quote->valid_until) {
            throw new RuntimeException('Ce devis ne possede pas de date de validite.');
        }

        $startAt = Carbon::parse($quote->valid_until->format('Y-m-d') . ' 10:00:00', $this->defaultTimezone())->subDays(2);
        if ($startAt->lessThanOrEqualTo(now($this->defaultTimezone()))) {
            $startAt = now($this->defaultTimezone())->addHour()->minute(0)->second(0);
        }
        $endAt = $startAt->copy()->addHour();

        $event = $this->calendarService->createEvent($tenantId, [
            'summary' => 'Relance devis - ' . $quote->number,
            'description' => implode("\n", array_filter([
                'Relance commerciale pour le devis ' . $quote->number,
                $quote->client ? 'Client: ' . $this->clientDisplayName($quote->client) : null,
                'Montant: ' . $this->formatMoney((float) $quote->total, (string) $quote->currency),
                $this->routeUrl('invoices.quotes.show', $quote) ? 'Devis CRM: ' . $this->routeUrl('invoices.quotes.show', $quote) : null,
            ])),
            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt->toIso8601String(),
            'all_day' => false,
            'timezone' => $this->defaultTimezone(),
            'client_id' => $quote->client_id ? (int) $quote->client_id : null,
            'source_type' => 'manual',
            'source_id' => (int) $quote->id,
            'source_label' => (string) $quote->number,
        ]);

        return [
            'result' => 'calendar_event_created',
            'message' => 'Relance devis planifiée.',
            'calendar_id' => (string) ($event['calendar_id'] ?? ''),
            'event_id' => (string) ($event['event_id'] ?? ''),
            'quote_id' => (int) $quote->id,
            'target_url' => $event['html_link'] ?? $this->routeUrl('google-calendar.index'),
            'target_blank' => true,
        ];
    }

    protected function scheduleProjectKickoff(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->ensureCalendarAvailability($tenantId);

        $payload = $this->payload($automationEvent);
        $projectId = $this->modelId($payload, $suggestion, 'project_id', Project::class);
        if (!$projectId) {
            throw new RuntimeException('Projet introuvable pour le kickoff.');
        }

        $project = $this->loadProject($tenantId, $projectId);
        [$startAt, $endAt] = $this->resolveProjectRange($project);
        $existingMeta = $this->projectMetadata($project, 'google_calendar', []);
        $existingEventId = trim((string) ($existingMeta['event_id'] ?? ''));
        $existingCalendarId = trim((string) ($existingMeta['calendar_id'] ?? ''));

        $eventPayload = [
            'calendar_id' => $existingCalendarId !== '' ? $existingCalendarId : null,
            'summary' => '[Projet] ' . $project->name,
            'description' => implode("\n", array_filter([
                'Projet CRM: ' . $project->name,
                $project->description ? $this->sanitizeText((string) $project->description) : null,
                $project->owner?->name ? 'Responsable: ' . $project->owner->name : null,
                $this->routeUrl('projects.show', $project) ? 'Lien projet: ' . $this->routeUrl('projects.show', $project) : null,
            ])),
            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt->toIso8601String(),
            'all_day' => false,
            'timezone' => $this->defaultTimezone(),
            'client_id' => $project->client_id ? (int) $project->client_id : null,
            'source_type' => 'project',
            'source_id' => (int) $project->id,
            'source_label' => (string) $project->name,
        ];

        $event = null;
        if ($existingEventId !== '' && $existingCalendarId !== '') {
            try {
                $event = $this->calendarService->updateEvent($tenantId, $existingCalendarId, $existingEventId, $eventPayload);
            } catch (\Throwable) {
                $event = null;
            }
        }

        if (!$event) {
            $event = $this->calendarService->createEvent($tenantId, $eventPayload);
        }

        $this->updateProjectMetadata($project, 'google_calendar', [
            'calendar_id' => (string) ($event['calendar_id'] ?? ''),
            'event_id' => (string) ($event['event_id'] ?? ''),
            'html_link' => (string) ($event['html_link'] ?? ''),
            'summary' => (string) ($event['summary'] ?? ''),
            'scheduled_at' => now()->toIso8601String(),
        ]);

        $this->logProjectActivity(
            $tenantId,
            $project,
            null,
            'project_scheduled_calendar',
            'Projet planifié dans Google Calendar',
            [
                'calendar_id' => $event['calendar_id'] ?? null,
                'event_id' => $event['event_id'] ?? null,
            ],
            $this->actorId($automationEvent)
        );

        return [
            'result' => 'calendar_event_created',
            'message' => 'Kickoff projet planifié.',
            'project_id' => (int) $project->id,
            'calendar_id' => (string) ($event['calendar_id'] ?? ''),
            'event_id' => (string) ($event['event_id'] ?? ''),
            'target_url' => $event['html_link'] ?? ($this->routeUrl('projects.show', $project) ?: $this->routeUrl('google-calendar.index')),
            'target_blank' => true,
        ];
    }

    protected function scheduleProjectTaskCalendar(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->ensureCalendarAvailability($tenantId);

        $payload = $this->payload($automationEvent);
        $taskId = $this->modelId($payload, $suggestion, 'task_id', ProjectTask::class);
        if (!$taskId) {
            throw new RuntimeException('Tâche introuvable pour la planification.');
        }

        $task = $this->loadProjectTask($tenantId, $taskId);
        $project = $task->project;
        if (!$project) {
            throw new RuntimeException('Projet lie a cette tâche introuvable.');
        }

        [$startAt, $endAt] = $this->resolveTaskRange($task);
        $metadata = is_array($task->metadata) ? $task->metadata : [];
        $existingMeta = is_array($metadata['google_calendar'] ?? null) ? $metadata['google_calendar'] : [];
        $existingEventId = trim((string) ($existingMeta['event_id'] ?? ''));
        $existingCalendarId = trim((string) ($existingMeta['calendar_id'] ?? ''));

        $eventPayload = [
            'calendar_id' => $existingCalendarId !== '' ? $existingCalendarId : null,
            'summary' => '[Tâche] ' . $task->title,
            'description' => implode("\n", array_filter([
                'Projet: ' . $project->name,
                $task->description ? $this->sanitizeText((string) $task->description) : null,
                $task->assignee?->name ? 'Assignee: ' . $task->assignee->name : null,
                $this->routeUrl('projects.show', $project) ? 'Lien projet: ' . $this->routeUrl('projects.show', $project) : null,
            ])),
            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt->toIso8601String(),
            'all_day' => false,
            'timezone' => $this->defaultTimezone(),
            'client_id' => $task->client_id ? (int) $task->client_id : ($project->client_id ? (int) $project->client_id : null),
            'source_type' => 'task',
            'source_id' => (int) $task->id,
            'source_label' => (string) $task->title,
            'attendees' => filter_var((string) ($task->assignee?->email ?? ''), FILTER_VALIDATE_EMAIL)
                ? (string) $task->assignee->email
                : '',
        ];

        $event = null;
        if ($existingEventId !== '' && $existingCalendarId !== '') {
            try {
                $event = $this->calendarService->updateEvent($tenantId, $existingCalendarId, $existingEventId, $eventPayload);
            } catch (\Throwable) {
                $event = null;
            }
        }

        if (!$event) {
            $event = $this->calendarService->createEvent($tenantId, $eventPayload);
        }

        $metadata['google_calendar'] = [
            'calendar_id' => (string) ($event['calendar_id'] ?? ''),
            'event_id' => (string) ($event['event_id'] ?? ''),
            'html_link' => (string) ($event['html_link'] ?? ''),
            'summary' => (string) ($event['summary'] ?? ''),
            'scheduled_at' => now()->toIso8601String(),
        ];
        $task->update(['metadata' => $metadata]);

        $this->logProjectActivity(
            $tenantId,
            $project,
            $task,
            'task_scheduled_calendar',
            'Tâche planifiée dans Google Calendar',
            [
                'calendar_id' => $event['calendar_id'] ?? null,
                'event_id' => $event['event_id'] ?? null,
            ],
            $this->actorId($automationEvent)
        );

        return [
            'result' => 'calendar_event_created',
            'message' => 'Tâche planifiée dans Google Calendar.',
            'project_id' => (int) $project->id,
            'task_id' => (int) $task->id,
            'calendar_id' => (string) ($event['calendar_id'] ?? ''),
            'event_id' => (string) ($event['event_id'] ?? ''),
            'target_url' => $event['html_link'] ?? ($this->routeUrl('projects.show', $project) ?: $this->routeUrl('google-calendar.index')),
            'target_blank' => true,
        ];
    }

    protected function scheduleUserOnboardingMeeting(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->ensureCalendarAvailability($tenantId);

        $payload = $this->payload($automationEvent);
        $invitationId = $this->modelId($payload, $suggestion, 'invitation_id', UserInvitation::class);
        if (!$invitationId) {
            throw new RuntimeException('Invitation introuvable pour la planification.');
        }

        $invitation = $this->loadInvitation($tenantId, $invitationId);
        $invitation->markExpiredIfNeeded();
        if (!$invitation->isUsable()) {
            throw new RuntimeException("Cette invitation n'est plus active.");
        }

        $tenantName = trim((string) ($invitation->tenant?->name ?? 'equipe CRM'));
        $acceptUrl = $this->routeUrl('users.accept', (string) $invitation->token);
        $startAt = now($this->defaultTimezone())->addDay()->setTime(9, 30);
        $endAt = $startAt->copy()->addHour();

        $event = $this->calendarService->createEvent($tenantId, [
            'summary' => 'Onboarding equipe - ' . $invitation->email,
            'description' => implode("\n", array_filter([
                'Preparer l accueil du nouveau membre sur ' . $tenantName,
                'Email: ' . $invitation->email,
                $invitation->role_in_tenant ? 'Role: ' . $invitation->role_in_tenant : null,
                $invitation->invitedBy?->name ? 'Invite par: ' . $invitation->invitedBy->name : null,
                $acceptUrl ? 'Lien invitation: ' . $acceptUrl : null,
            ])),
            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt->toIso8601String(),
            'all_day' => false,
            'timezone' => $this->defaultTimezone(),
            'source_type' => 'user_invitation',
            'source_id' => (int) $invitation->id,
            'source_label' => (string) $invitation->email,
            'attendees' => filter_var((string) $invitation->email, FILTER_VALIDATE_EMAIL) ? (string) $invitation->email : '',
        ]);

        return [
            'result' => 'calendar_event_created',
            'message' => "Rendez-vous d'onboarding planifié.",
            'invitation_id' => (int) $invitation->id,
            'calendar_id' => (string) ($event['calendar_id'] ?? ''),
            'event_id' => (string) ($event['event_id'] ?? ''),
            'target_url' => $event['html_link'] ?? ($this->routeUrl('users.invitations') ?: $this->routeUrl('google-calendar.index')),
            'target_blank' => true,
        ];
    }

    protected function ensureCalendarAvailability(int $tenantId): void
    {
        $this->assertExtensionActive($tenantId, 'google-calendar', 'Google Calendar doit être installé pour cette automation.');

        if (!$this->calendarService->getToken($tenantId)) {
            throw new RuntimeException("Google Calendar n'est pas connecté pour ce tenant.");
        }
    }

    protected function defaultTimezone(): string
    {
        return (string) config('google-calendar.defaults.timezone', config('app.timezone', 'UTC'));
    }

    protected function resolveProjectRange(Project $project): array
    {
        $timezone = $this->defaultTimezone();

        $startAt = $project->start_date
            ? Carbon::parse($project->start_date->format('Y-m-d') . ' 09:00:00', $timezone)
            : now($timezone)->addHour()->minute(0)->second(0);

        $endAt = $project->due_date
            ? Carbon::parse($project->due_date->format('Y-m-d') . ' 18:00:00', $timezone)
            : $startAt->copy()->addHours(2);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt = $startAt->copy()->addHour();
        }

        return [$startAt, $endAt];
    }

    protected function resolveTaskRange(ProjectTask $task): array
    {
        $timezone = $this->defaultTimezone();

        $startAt = $task->start_date
            ? Carbon::parse($task->start_date->format('Y-m-d') . ' 09:00:00', $timezone)
            : ($task->due_date
                ? Carbon::parse($task->due_date->format('Y-m-d') . ' 09:00:00', $timezone)
                : now($timezone)->addHour()->minute(0)->second(0));

        $endAt = $task->due_date
            ? Carbon::parse($task->due_date->format('Y-m-d') . ' 17:00:00', $timezone)
            : $startAt->copy()->addHour();

        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt = $startAt->copy()->addHour();
        }

        return [$startAt, $endAt];
    }
}
