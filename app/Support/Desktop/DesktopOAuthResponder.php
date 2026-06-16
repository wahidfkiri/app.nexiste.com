<?php

namespace App\Support\Desktop;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DesktopOAuthResponder
{
    public const CLIENT_TAURI = 'tauri';

    public function isDesktopRequest(Request $request): bool
    {
        $client = strtolower(trim((string) (
            $request->query('desktop')
            ?: $request->query('desktop_client')
            ?: $request->header('X-Desktop-Client')
            ?: ''
        )));

        return $client === self::CLIENT_TAURI;
    }

    public function sanitizeReturnPath(?string $value, string $fallback = '/'): string
    {
        $fallback = $this->normalizeFallback($fallback);
        $candidate = trim((string) $value);

        if ($candidate === '') {
            return $fallback;
        }

        if (str_starts_with($candidate, '/')) {
            return $candidate;
        }

        if (preg_match('#^https?://#i', $candidate) === 1) {
            $origin = rtrim(url('/'), '/');
            if (!str_starts_with($candidate, $origin)) {
                return $fallback;
            }

            $parts = parse_url($candidate);
            $path = (string) ($parts['path'] ?? '/');
            $query = isset($parts['query']) ? '?' . $parts['query'] : '';
            $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

            return ($path !== '' ? $path : '/') . $query . $fragment;
        }

        return $fallback;
    }

    public function appendMessage(string $path, string $message, string $type = 'notice'): string
    {
        $path = $this->sanitizeReturnPath($path, '/');
        $param = $type === 'error' ? 'desktop_error' : 'desktop_notice';
        $parts = parse_url($path);
        $basePath = (string) ($parts['path'] ?? '/');
        $query = [];

        if (!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }

        $query[$param] = $message;
        $queryString = http_build_query($query);
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $basePath . ($queryString !== '' ? '?' . $queryString : '') . $fragment;
    }

    public function renderSuccess(string $path, string $message, string $title = 'Connexion reussie'): View
    {
        return $this->renderBridge(
            $this->appendMessage($path, $message, 'notice'),
            $title,
            $message,
            'success'
        );
    }

    public function renderError(string $path, string $message, string $title = 'Connexion impossible'): View
    {
        return $this->renderBridge(
            $this->appendMessage($path, $message, 'error'),
            $title,
            $message,
            'error'
        );
    }

    private function renderBridge(string $path, string $title, string $message, string $status): View
    {
        return view('desktop.oauth-bridge', [
            'title' => $title,
            'message' => $message,
            'targetPath' => $path,
            'targetUrl' => url($path),
            'deepLinkUrl' => 'nexuscrm://oauth/complete?path=' . rawurlencode($path),
            'status' => $status,
        ]);
    }

    private function normalizeFallback(string $fallback): string
    {
        $value = trim($fallback);

        if ($value === '') {
            return '/';
        }

        return str_starts_with($value, '/') ? $value : '/' . ltrim($value, '/');
    }
}
