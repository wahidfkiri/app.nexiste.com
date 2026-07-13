<?php

namespace App\Services\Billing;

use App\Models\TenantSubscription;

class SubscriptionInvoiceService
{
    /**
     * Numéro de facture d'abonnement lisible et stable.
     */
    public function invoiceNumber(TenantSubscription $subscription): string
    {
        return 'ABO-' . $subscription->created_at?->format('Y') . '-' . str_pad((string) $subscription->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Génère le PDF (contenu binaire) de la facture d'abonnement.
     */
    public function buildPdf(TenantSubscription $subscription): string
    {
        $subscription->loadMissing(['tenant', 'plan', 'planPrice']);

        $pdf = app('dompdf.wrapper')
            ->loadView('billing.invoice-pdf', [
                'subscription' => $subscription,
                'number' => $this->invoiceNumber($subscription),
            ])
            ->setPaper('A4');

        return $pdf->output();
    }

    public function fileName(TenantSubscription $subscription): string
    {
        return 'facture-' . $this->invoiceNumber($subscription) . '.pdf';
    }
}
