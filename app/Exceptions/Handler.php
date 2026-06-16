<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation. Veuillez corriger les champs.',
                    'errors' => $e->errors(),
                ], 422);
            }

            return null;
        });

        $this->renderable(function (TokenMismatchException $e, $request) {
            return $this->sessionExpiredResponse($request);
        });

        $this->renderable(function (Throwable $e, $request) {
            if (!$e instanceof HttpExceptionInterface || (int) $e->getStatusCode() !== 419) {
                return null;
            }

            return $this->sessionExpiredResponse($request);
        });
    }

    protected function sessionExpiredResponse($request)
    {
        $message = 'Votre session a expiré. Merci de vous reconnecter.';
        $loginUrl = route('login');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect' => $loginUrl,
                'errors' => [
                    '_token' => ['Jeton CSRF invalide ou expiré.'],
                ],
            ], 419);
        }

        return redirect()
            ->guest($loginUrl)
            ->with('error', $message);
    }
}
