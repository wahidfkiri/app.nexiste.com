<?php

namespace Vendor\User\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserContextMiddleware
{
    /**
     * Injecte le contexte utilisateur dans les vues (utile pour le layout global).
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Partager avec toutes les vues
            view()->share('currentUser', $user);
            view()->share('currentUserRoleLabel',
                config("user.tenant_roles.{$user->role_in_tenant}", $user->role_in_tenant ?? 'Membre')
            );
        }

        return $next($request);
    }
}