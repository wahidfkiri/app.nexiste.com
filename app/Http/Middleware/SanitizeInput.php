<?php

namespace App\Http\Middleware;

use App\Support\Security\InputSanitizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    public function handle(Request $request, Closure $next): Response
    {
        $sanitized = InputSanitizer::sanitizeArray(
            $request->all(),
            [
                'strip_tags' => false,
                'except' => (array) config('security.sanitize.except', []),
                'allow_html' => (array) config('security.sanitize.allow_html', []),
            ]
        );

        $request->merge($sanitized);

        return $next($request);
    }
}
