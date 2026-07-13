<?php

namespace App\Services\Billing;

use GuzzleHttp\Client;
use RuntimeException;

/**
 * Couche paiement. Deux drivers :
 *  - manual : validation immédiate (virement / hors-ligne), fonctionne sans clé.
 *  - paypal : API REST v2 (mode sandbox par défaut), clés lues depuis config/services.
 *
 * Aucune clé secrète n'est stockée en base ; tout vient de .env / config.
 */
class PaymentManager
{
    public function isConfigured(string $provider): bool
    {
        return match ($provider) {
            'manual' => true,
            'paypal' => filled(config('services.paypal.client_id')) && filled(config('services.paypal.client_secret')),
            'stripe' => filled(config('services.stripe.secret')),
            default => false,
        };
    }

    private function paypalBaseUrl(): string
    {
        return config('services.paypal.mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function paypalClient(): Client
    {
        return new Client([
            'base_uri' => $this->paypalBaseUrl(),
            'timeout' => 20,
        ]);
    }

    private function paypalToken(): string
    {
        $res = $this->paypalClient()->post('/v1/oauth2/token', [
            'auth' => [config('services.paypal.client_id'), config('services.paypal.client_secret')],
            'form_params' => ['grant_type' => 'client_credentials'],
        ]);

        $data = json_decode((string) $res->getBody(), true);
        $token = $data['access_token'] ?? null;

        if (! $token) {
            throw new RuntimeException('PayPal: jeton d’accès indisponible.');
        }

        return $token;
    }

    /**
     * Crée une commande PayPal et renvoie [order_id, approval_url].
     */
    public function createPaypalOrder(float $amount, string $currency, string $description, string $returnUrl, string $cancelUrl): array
    {
        if (! $this->isConfigured('paypal')) {
            throw new RuntimeException('PayPal n’est pas configuré (clés manquantes dans .env).');
        }

        $res = $this->paypalClient()->post('/v2/checkout/orders', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->paypalToken(),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'description' => mb_substr($description, 0, 127),
                    'amount' => [
                        'currency_code' => strtoupper($currency),
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'brand_name' => (string) config('app.name', 'CRM'),
                    'user_action' => 'PAY_NOW',
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                ],
            ],
        ]);

        $data = json_decode((string) $res->getBody(), true);
        $approval = collect($data['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if (empty($data['id']) || ! $approval) {
            throw new RuntimeException('PayPal: création de commande impossible.');
        }

        return ['order_id' => $data['id'], 'approval_url' => $approval];
    }

    /**
     * Capture (encaisse) une commande PayPal approuvée. Retourne true si payé.
     */
    public function capturePaypalOrder(string $orderId): bool
    {
        $res = $this->paypalClient()->post("/v2/checkout/orders/{$orderId}/capture", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->paypalToken(),
                'Content-Type' => 'application/json',
            ],
        ]);

        $data = json_decode((string) $res->getBody(), true);

        return ($data['status'] ?? null) === 'COMPLETED';
    }
}
