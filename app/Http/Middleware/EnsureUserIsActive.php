<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if ($user && !$user->is_active) {
            // Révoquer le token
            $user->currentAccessToken()->delete();
            
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été désactivé.',
                'code' => 'account_disabled'
            ], 403);
        }

        return $next($request);
    }
}