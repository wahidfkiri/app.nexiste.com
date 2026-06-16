<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $idempotencyKey = trim((string) (
            $request->headers->get('Idempotency-Key')
            ?: $request->headers->get('X-Request-Id')
            ?: $request->input('_request_id')
        ));

        if ($idempotencyKey === '') {
            return $next($request);
        }

        $ttlSeconds = (int) config('security.idempotency.ttl_seconds', 30);
        $cacheKey = $this->buildCacheKey($request, $idempotencyKey);

        if (Cache::has($cacheKey)) {
            $message = __('security.duplicate_submission');
            if ($request->expectsJson() || $request->ajax()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $message,
                    'errors' => [
                        '_request_id' => [$message],
                    ],
                ], 409);
            }

            return back()->withErrors(['general' => $message])->withInput();
        }

        Cache::put($cacheKey, now()->toIso8601String(), now()->addSeconds($ttlSeconds));

        return $next($request);
    }

    private function buildCacheKey(Request $request, string $idempotencyKey): string
    {
        $userKey = $request->user()?->id ?: $request->ip();
        $route = $request->route()?->uri() ?: $request->path();
        $base = implode('|', [
            'idem',
            $userKey,
            strtoupper($request->method()),
            $route,
            $idempotencyKey,
        ]);

        return 'request:' . hash('sha256', $base);
    }
}
