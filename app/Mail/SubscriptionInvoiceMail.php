<?php

namespace App\Mail;

use App\Models\TenantSubscription;
use App\Services\Billing\SubscriptionInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TenantSubscription $subscription,
        public string $pdf,
    ) {
    }

    public function build(): self
    {
        $number = app(SubscriptionInvoiceService::class)->invoiceNumber($this->subscription);

        return $this
            ->subject(__('billing.invoice.subject', ['app' => config('app.name', 'CRM')]))
            ->view('emails.subscription-invoice')
            ->attachData($this->pdf, 'facture-' . $number . '.pdf', ['mime' => 'application/pdf']);
    }
}
