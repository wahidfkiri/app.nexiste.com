<?php

namespace Vendor\Automation\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Client\Models\Client;
use Vendor\Invoice\Events\QuoteCreated;
use Vendor\Invoice\Models\Quote;
use Vendor\Invoice\Services\InvoiceService;

class CreateQuoteAutomationAction extends AbstractAutomationAction
{
    public function __construct(
        \Vendor\Automation\Services\ExtensionAvailabilityService $extensions,
        protected InvoiceService $invoiceService
    ) {
        parent::__construct($extensions);
    }

    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'invoice', 'Le module Facturation doit être installé pour créer un devis.');

        $payload = $this->payload($automationEvent);
        $clientId = $this->modelId($payload, $suggestion, 'client_id', Client::class);
        if (!$clientId) {
            throw new RuntimeException('Client introuvable pour la création du devis.');
        }

        $client = $this->loadClient($tenantId, $clientId);
        $actor = $this->resolveActorUser($automationEvent);
        $currency = $this->defaultCurrency();
        $validityDays = max(1, (int) config('invoice.quote_validity_days', 30));

        $quote = null;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $quote = Quote::query()->create([
                    'tenant_id' => $tenantId,
                    'user_id' => (int) $actor->id,
                    'client_id' => (int) $client->id,
                    'number' => $this->invoiceService->generateQuoteNumber($tenantId),
                    'reference' => 'AUTO-' . now()->format('YmdHis') . '-' . ($attempt + 1),
                    'status' => 'draft',
                    'currency' => $currency,
                    'exchange_rate' => 1,
                    'issue_date' => now()->toDateString(),
                    'valid_until' => now()->addDays($validityDays)->toDateString(),
                    'subtotal' => 0,
                    'discount_type' => 'none',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_rate' => (float) config('invoice.tax.default_rate', 20),
                    'tax_amount' => 0,
                    'withholding_tax_rate' => 0,
                    'withholding_tax_amount' => 0,
                    'total' => 0,
                    'notes' => 'Devis créé automatiquement depuis la suggestion CRM pour ' . $this->clientDisplayName($client) . '.',
                    'internal_notes' => 'Automation event #' . (int) $automationEvent->id,
                ]);
                break;
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === 4 || !str_contains(strtolower($e->getMessage()), 'quotes_number_unique')) {
                    throw $e;
                }
            }
        }

        if (!$quote) {
            throw new RuntimeException('Impossible de créer le devis brouillon automatique.');
        }

        $quote = $quote->fresh(['client']);

        event(new QuoteCreated($quote, [
            'created_via' => 'automation',
            'automation_event_id' => (int) $automationEvent->id,
        ]));

        return [
            'result' => 'quote_created',
            'message' => 'Devis brouillon créé avec succès.',
            'quote_id' => (int) $quote->id,
            'quote_number' => (string) $quote->number,
            'client_id' => (int) $client->id,
            'target_url' => $this->routeUrl('invoices.quotes.edit', $quote)
                ?: $this->routeUrl('invoices.quotes.show', $quote)
                ?: $this->routeUrl('invoices.quotes.index'),
        ];
    }
}
