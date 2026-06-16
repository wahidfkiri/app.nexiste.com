<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $isSuperAdmin = false;

        if (method_exists($user, 'hasAnyRole')) {
            $isSuperAdmin = $user->hasAnyRole(['super_admin', 'super-admin']);
        } elseif (method_exists($user, 'hasRole')) {
            $isSuperAdmin = $user->hasRole('super_admin') || $user->hasRole('super-admin');
        }

        if (!$isSuperAdmin) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acces reserve au super administrateur.',
                ], 403);
            }

            abort(403, 'Acces reserve au super administrateur.');
        }

        return $next($request);
    }
}
