<?php

namespace Vendor\Automation\Actions;

use NexusExtensions\Projects\Models\ProjectTask;
use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;
use Vendor\User\Models\UserInvitation;

class CreateProjectTaskAutomationAction extends AbstractAutomationAction
{
    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return match ((string) $automationEvent->action_type) {
            'create_payment_followup_task' => $this->createPaymentFollowupTask($automationEvent, $suggestion),
            'create_quote_followup_task' => $this->createQuoteFollowupTask($automationEvent, $suggestion),
            'create_user_onboarding_task' => $this->createUserOnboardingTask($automationEvent, $suggestion),
            default => throw new RuntimeException('Type de tâche projet non pris en charge.'),
        };
    }

    protected function createPaymentFollowupTask(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'projects', "L'extension Projets doit être active pour créer une tâche de suivi.");

        $payload = $this->payload($automationEvent);
        $invoiceId = $this->modelId($payload, $suggestion, 'invoice_id', Invoice::class);
        if (!$invoiceId) {
            throw new RuntimeException('Facture introuvable pour la création de la tâche.');
        }

        $invoice = $this->loadInvoice($tenantId, $invoiceId);
        $actor = $this->resolveActorUser($automationEvent);
        $project = $this->resolveClientProjectOrAutomationProject(
            $tenantId,
            $invoice->client_id ? (int) $invoice->client_id : null,
            $actor,
            'Suivi paiements',
            'billing-followups',
            'Projet système pour centraliser les relances de paiements.'
        );

        $task = ProjectTask::query()->create([
            'tenant_id' => $tenantId,
            'project_id' => (int) $project->id,
            'client_id' => $invoice->client_id ? (int) $invoice->client_id : null,
            'created_by' => (int) $actor->id,
            'assigned_to' => (int) $actor->id,
            'title' => 'Relancer paiement ' . $invoice->number,
            'description' => implode("\n", array_filter([
                'Relance automatique suite a la création de la facture ' . $invoice->number . '.',
                $invoice->client ? 'Client: ' . $this->clientDisplayName($invoice->client) : null,
                'Montant a suivre: ' . $this->formatMoney((float) $invoice->amount_due, (string) $invoice->currency),
                $invoice->due_date ? 'Echeance: ' . $invoice->due_date->format('d/m/Y') : null,
                $this->routeUrl('invoices.show', $invoice) ? 'Lien facture: ' . $this->routeUrl('invoices.show', $invoice) : null,
            ])),
            'status' => $this->defaultTaskStatus(),
            'priority' => 'high',
            'position' => 0,
            'start_date' => now()->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'estimate_hours' => 0,
            'spent_hours' => 0,
            'tags' => ['facture', 'paiement', 'automation'],
            'metadata' => [
                'automation' => [
                    'source_type' => 'invoice',
                    'source_id' => (int) $invoice->id,
                    'automation_event_id' => (int) $automationEvent->id,
                ],
            ],
        ]);

        $project->recalculateProgress();

        $this->logProjectActivity(
            $tenantId,
            $project,
            $task,
            'automation_task_created',
            'Tâche de suivi paiement créée automatiquement',
            ['invoice_id' => (int) $invoice->id],
            (int) $actor->id
        );

        return [
            'result' => 'task_created',
            'message' => 'Tâche de suivi paiement créée dans Projets.',
            'project_id' => (int) $project->id,
            'task_id' => (int) $task->id,
            'invoice_id' => (int) $invoice->id,
            'target_url' => $this->routeUrl('projects.show', $project) ?: $this->routeUrl('projects.index'),
        ];
    }

    protected function createQuoteFollowupTask(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'projects', "L'extension Projets doit être active pour créer une tâche de suivi.");

        $payload = $this->payload($automationEvent);
        $quoteId = $this->modelId($payload, $suggestion, 'quote_id', Quote::class);
        if (!$quoteId) {
            throw new RuntimeException('Devis introuvable pour la création de la tâche.');
        }

        $quote = $this->loadQuote($tenantId, $quoteId);
        $actor = $this->resolveActorUser($automationEvent);
        $project = $this->resolveClientProjectOrAutomationProject(
            $tenantId,
            $quote->client_id ? (int) $quote->client_id : null,
            $actor,
            'Suivi devis commerciaux',
            'quote-followups',
            'Projet système pour centraliser les suivis de devis.'
        );

        $task = ProjectTask::query()->create([
            'tenant_id' => $tenantId,
            'project_id' => (int) $project->id,
            'client_id' => $quote->client_id ? (int) $quote->client_id : null,
            'created_by' => (int) $actor->id,
            'assigned_to' => (int) $actor->id,
            'title' => 'Relancer devis ' . $quote->number,
            'description' => implode("\n", array_filter([
                'Relance automatique suite a la création du devis ' . $quote->number . '.',
                $quote->client ? 'Client: ' . $this->clientDisplayName($quote->client) : null,
                'Montant: ' . $this->formatMoney((float) $quote->total, (string) $quote->currency),
                $quote->valid_until ? 'Valable jusqu au: ' . $quote->valid_until->format('d/m/Y') : null,
                $this->routeUrl('invoices.quotes.show', $quote) ? 'Lien devis: ' . $this->routeUrl('invoices.quotes.show', $quote) : null,
            ])),
            'status' => $this->defaultTaskStatus(),
            'priority' => 'medium',
            'position' => 0,
            'start_date' => now()->toDateString(),
            'due_date' => $quote->valid_until?->toDateString(),
            'estimate_hours' => 0,
            'spent_hours' => 0,
            'tags' => ['devis', 'relance', 'automation'],
            'metadata' => [
                'automation' => [
                    'source_type' => 'quote',
                    'source_id' => (int) $quote->id,
                    'automation_event_id' => (int) $automationEvent->id,
                ],
            ],
        ]);

        $project->recalculateProgress();

        $this->logProjectActivity(
            $tenantId,
            $project,
            $task,
            'automation_task_created',
            'Tâche de suivi devis créée automatiquement',
            ['quote_id' => (int) $quote->id],
            (int) $actor->id
        );

        return [
            'result' => 'task_created',
            'message' => 'Tâche de suivi devis créée dans Projets.',
            'project_id' => (int) $project->id,
            'task_id' => (int) $task->id,
            'quote_id' => (int) $quote->id,
            'target_url' => $this->routeUrl('projects.show', $project) ?: $this->routeUrl('projects.index'),
        ];
    }

    protected function createUserOnboardingTask(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'projects', "L'extension Projets doit être active pour créer une tâche d'onboarding.");

        $payload = $this->payload($automationEvent);
        $invitationId = $this->modelId($payload, $suggestion, 'invitation_id', UserInvitation::class);
        if (!$invitationId) {
            throw new RuntimeException('Invitation introuvable pour la création de la tâche.');
        }

        $invitation = $this->loadInvitation($tenantId, $invitationId);
        $invitation->markExpiredIfNeeded();
        if (!$invitation->isUsable()) {
            throw new RuntimeException("Cette invitation n'est plus active.");
        }

        $actor = $this->resolveActorUser($automationEvent);
        $project = $this->findOrCreateAutomationProject(
            $tenantId,
            $actor,
            'Onboarding équipe',
            'team-onboarding',
            "Projet système pour suivre l'intégration des nouveaux membres."
        );

        $acceptUrl = $this->routeUrl('users.accept', (string) $invitation->token);
        $task = ProjectTask::query()->create([
            'tenant_id' => $tenantId,
            'project_id' => (int) $project->id,
            'client_id' => null,
            'created_by' => (int) $actor->id,
            'assigned_to' => (int) $actor->id,
            'title' => "Finaliser l'onboarding de " . $invitation->email,
            'description' => implode("\n", array_filter([
                'Invitation en attente pour ' . $invitation->email . '.',
                $invitation->role_in_tenant ? 'Role prevu: ' . $invitation->role_in_tenant : null,
                $invitation->expires_at ? 'Expiration: ' . $invitation->expires_at->format('d/m/Y H:i') : null,
                $acceptUrl ? 'Lien invitation: ' . $acceptUrl : null,
            ])),
            'status' => $this->defaultTaskStatus(),
            'priority' => 'medium',
            'position' => 0,
            'start_date' => now()->toDateString(),
            'due_date' => $invitation->expires_at?->toDateString(),
            'estimate_hours' => 0,
            'spent_hours' => 0,
            'tags' => ['users', 'onboarding', 'automation'],
            'metadata' => [
                'automation' => [
                    'source_type' => 'user_invitation',
                    'source_id' => (int) $invitation->id,
                    'automation_event_id' => (int) $automationEvent->id,
                ],
            ],
        ]);

        $project->recalculateProgress();

        $this->logProjectActivity(
            $tenantId,
            $project,
            $task,
            'automation_task_created',
            "Tâche d'onboarding créée automatiquement",
            ['invitation_id' => (int) $invitation->id],
            (int) $actor->id
        );

        return [
            'result' => 'task_created',
            'message' => "Tâche d'onboarding créée dans Projets.",
            'project_id' => (int) $project->id,
            'task_id' => (int) $task->id,
            'invitation_id' => (int) $invitation->id,
            'target_url' => $this->routeUrl('projects.show', $project) ?: $this->routeUrl('projects.index'),
        ];
    }
}
