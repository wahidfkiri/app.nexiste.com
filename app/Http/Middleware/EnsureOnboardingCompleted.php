<?php

namespace App\Http\Middleware;

use App\Http\Controllers\OnboardingController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            return $next($request);
        }

        if (
            $request->routeIs('onboarding.*')
            || $request->is('onboarding')
            || $request->is('onboarding/*')
            || $request->routeIs('logout')
            || $request->is('logout')
            || $request->routeIs('verification.*')
            || $request->routeIs('password.*')
        ) {
            return $next($request);
        }

        if (!OnboardingController::mustCompleteOnboarding($user)) {
            return $next($request);
        }

        $redirect = route('onboarding.show');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Finalisez la configuration de votre compte avant de continuer.',
                'redirect' => $redirect,
            ], 409);
        }

        return redirect()->to($redirect);
    }
}
