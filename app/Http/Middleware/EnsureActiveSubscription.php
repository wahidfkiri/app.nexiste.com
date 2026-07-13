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
 * Appliqué globalement (groupe de middleware « web ») : il couvre donc TOUTES
 * les routes de l'application (modules, extensions actuels et futurs) sans avoir
 * à modifier chaque fichier de routes.
 *
 * L'UUID/ID ne protège rien : ce contrôle d'accès s'ajoute à l'authentification,
 * aux permissions et à l'isolation tenant existantes (il ne les remplace pas).
 */
class EnsureActiveSubscription
{
    /**
     * Routes toujours accessibles, même sans abonnement actif
     * (sinon l'utilisateur ne pourrait ni payer, ni se déconnecter).
     */
    private const EXEMPT_ROUTE_PATTERNS = [
        'subscription.*',   // page de choix du forfait / paiement + retour PayPal
        'onboarding.*',     // finalisation de l'espace
        'logout',
        'login',
        'register',
        'password.*',
        'verification.*',
        'auth.*',           // connexion / callbacks OAuth
    ];

    public function __construct(private SubscriptionService $subscriptions)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request)) {
            return $next($request);
        }

        $tenant = $request->user()->tenant;

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

    private function isExempt(Request $request): bool
    {
        $user = $request->user();

        // Invités (pages de connexion, etc.) : rien à vérifier ici.
        if (! $user) {
            return true;
        }

        // Super-administrateurs : non soumis à l'abonnement.
        if (method_exists($user, 'hasRole') && ($user->hasRole('super_admin') || $user->hasRole('super-admin'))) {
            return true;
        }

        // Utilisateur sans tenant (ex. contexte global) : on laisse passer.
        if (! $user->tenant) {
            return true;
        }

        // Routes indispensables au parcours de paiement / déconnexion.
        return $request->routeIs(self::EXEMPT_ROUTE_PATTERNS);
    }
}
