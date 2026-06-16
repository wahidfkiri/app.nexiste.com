<?php

namespace Vendor\CrmCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantOwnerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Vérifier si l'utilisateur est propriétaire du tenant
        if (!$user->is_tenant_owner && $user->role_in_tenant !== 'owner') {
            abort(403, 'Vous devez être propriétaire du compte pour effectuer cette action.');
        }
        
        return $next($request);
    }
}