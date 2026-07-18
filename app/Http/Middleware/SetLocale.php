<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Applique la langue de l'application à chaque requête web.
     *
     * Ordre de résolution :
     *   1. Choix explicite stocké en session (bascule immédiate)
     *   2. Langue personnelle de l'utilisateur enregistrée en base de données
     *   3. Langue du tenant courant enregistrée en base de données
     *   4. Langue par défaut de la configuration
     */
    public function handle(Request $request, Closure $next)
    {
        $supported = config('app.supported_locales', ['fr']);
        $fallback = config('app.locale', 'fr');

        $locale = $request->session()->get('app_locale');

        if (!in_array($locale, $supported, true)) {
            $user = $request->user();
            $candidates = [$user?->locale, $user?->tenant?->locale];
            $locale = $fallback;
            foreach ($candidates as $candidate) {
                if (in_array($candidate, $supported, true)) {
                    $locale = $candidate;
                    break;
                }
            }
        }

        App::setLocale($locale);
        \Carbon\Carbon::setLocale($locale);

        return $next($request);
    }
}
