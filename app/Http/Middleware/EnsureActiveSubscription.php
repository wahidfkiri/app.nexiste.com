<?php

namespace App\Http\Middleware;

use App\Services\Billing\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie que le tenant possède un abonnement actif (ou en essai).
 * Sinon -> redirection vers la page de choix de forfait / paiement.
 *
 * L'UUID/ID ne protège rien : ce contrôle d'accès s'ajoute à l'authentification,
 * aux permissions et à l'isolation tenant existantes (il ne les remplace pas).
 */
class EnsureActiveSubscription
{
    public function __construct(private SubscriptionService $subscriptions)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = $user?->tenant;

        // Pas de tenant (ex. superadmin global) : on laisse passer.
        if (! $tenant) {
            return $next($request);
        }

        // Les super-administrateurs ne sont pas soumis à l'abonnement.
        if (method_exists($user, 'hasRole') && ($user->hasRole('super_admin') || $user->hasRole('super-admin'))) {
            return $next($request);
        }

        // Repli rapide sans requête : la colonne tenant.subscription_ends_at.
        $endsAt = $tenant->subscription_ends_at;
        if ($endsAt && $endsAt->isFuture()) {
            return $next($request);
        }

        // Vérification fine (statut de l'abonnement).
        if ($this->subscriptions->activeSubscription((int) $tenant->id)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(402, __('billing.access.required'));
        }

        return redirect()->route('subscription.plans')
            ->withErrors(['subscription' => __('billing.access.required')]);
    }
}
