<?php

namespace App\Services\Billing;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanPrice;
use App\Models\TenantSubscription;
use App\Mail\SubscriptionInvoiceMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Vendor\CrmCore\Models\Tenant;

class SubscriptionService
{
    public function __construct(private SubscriptionInvoiceService $invoices)
    {
    }

    /**
     * La période d'essai n'est utilisable qu'une seule fois par espace (tenant).
     */
    public function hasUsedTrial(int $tenantId): bool
    {
        return TenantSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('is_trial', true)
            ->exists();
    }

    public function activeSubscription(int $tenantId): ?TenantSubscription
    {
        return TenantSubscription::query()
            ->activeForTenant($tenantId)
            ->latest('ends_at')
            ->first();
    }

    /**
     * Démarre une période d'essai (une seule fois). Retourne l'abonnement créé.
     */
    public function startTrial(Tenant $tenant, SubscriptionPlan $plan): TenantSubscription
    {
        abort_if($this->hasUsedTrial((int) $tenant->id), 422, __('billing.onboarding.trial_once_used'));

        $days = max(1, (int) ($plan->trial_days ?: 14));
        $endsAt = now()->addDays($days);

        return DB::transaction(function () use ($tenant, $plan, $endsAt) {
            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'plan_price_id' => null,
                'payment_method' => 'trial',
                'status' => 'trialing',
                'is_trial' => true,
                'amount' => 0,
                'currency' => $plan->currency ?: 'EUR',
                'starts_at' => now(),
                'ends_at' => $endsAt,
                'trial_ends_at' => $endsAt,
            ]);

            $tenant->forceFill([
                'trial_ends_at' => $endsAt,
                'subscription_ends_at' => $endsAt,
            ])->save();

            $this->sendInvoiceSafely($subscription);

            return $subscription;
        });
    }

    /**
     * Active un abonnement payant (appelé après confirmation du paiement).
     */
    public function activatePaid(Tenant $tenant, SubscriptionPlan $plan, SubscriptionPlanPrice $price, string $provider, array $meta = []): TenantSubscription
    {
        $endsAt = now()->addMonths(max(1, (int) $price->period_months));

        return DB::transaction(function () use ($tenant, $plan, $price, $provider, $meta, $endsAt) {
            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'plan_price_id' => $price->id,
                'payment_method' => $provider,
                'status' => 'active',
                'is_trial' => false,
                'amount' => $price->price,
                'currency' => $plan->currency ?: 'EUR',
                'starts_at' => now(),
                'ends_at' => $endsAt,
                'trial_ends_at' => null,
                'meta' => $meta,
            ]);

            $tenant->forceFill(['subscription_ends_at' => $endsAt])->save();

            $this->sendInvoiceSafely($subscription);

            return $subscription;
        });
    }

    private function sendInvoiceSafely(TenantSubscription $subscription): void
    {
        try {
            $pdf = $this->invoices->buildPdf($subscription);
            $email = $subscription->tenant->email;
            if ($email) {
                Mail::to($email)->send(new SubscriptionInvoiceMail($subscription, $pdf));
            }
        } catch (\Throwable $e) {
            // Ne bloque jamais l'activation si l'e-mail échoue : on journalise.
            Log::warning('Subscription invoice email failed: ' . $e->getMessage());
        }
    }
}
