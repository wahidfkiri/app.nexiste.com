<?php

namespace Vendor\Automation\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AutomationReconnectResolver
{
    protected const PROVIDERS = [
        'google-gmail' => [
            'label' => 'Google Gmail',
            'route' => 'google-gmail.index',
            'patterns' => [
                'google gmail',
                'gmail n est pas connecte',
                'session google gmail expiree',
                'reconnectez google gmail',
                'reconnect your gmail',
            ],
        ],
        'google-calendar' => [
            'label' => 'Google Calendar',
            'route' => 'google-calendar.index',
            'patterns' => [
                'google calendar',
                'calendar n est pas connecte',
                'calendar is not connected',
                'session google calendar expiree',
                'reconnectez google calendar',
                'reconnect google calendar',
            ],
        ],
        'google-drive' => [
            'label' => 'Google Drive',
            'route' => 'google-drive.index',
            'patterns' => [
                'google drive',
                'drive n est pas connecte',
                'drive is not connected',
                'session google drive expiree',
                'reconnectez google drive',
                'reconnect google drive',
            ],
        ],
        'dropbox' => [
            'label' => 'Dropbox',
            'route' => 'dropbox.index',
            'patterns' => [
                'dropbox n est pas connecte',
                'dropbox is not connected',
                'dropbox demande une reconnexion',
                'reconnectez dropbox',
                'reconnect dropbox',
                'refresh token manquant',
                'invalid_access_token',
                'expired_access_token',
                'invalid_grant',
            ],
        ],
        'slack' => [
            'label' => 'Slack',
            'route' => 'slack.index',
            'patterns' => [
                'slack n est pas connecte',
                'slack is not connected',
                'slack bot token is missing',
                'reconnect your slack workspace',
                'reconnectez slack',
                'invalid_auth',
                'token_revoked',
                'account_inactive',
            ],
        ],
        'google-meet' => [
            'label' => 'Google Meet',
            'route' => 'google-meet.index',
            'patterns' => [
                'google meet',
                'session google meet expiree',
                'reconnectez google meet',
                'reconnect google meet',
            ],
        ],
        'google-sheets' => [
            'label' => 'Google Sheets',
            'route' => 'google-sheets.index',
            'patterns' => [
                'google sheets',
                'session google sheets expiree',
                'reconnectez google sheets',
                'reconnect google sheets',
            ],
        ],
        'google-docx' => [
            'label' => 'Google Docs',
            'route' => 'google-docx.index',
            'patterns' => [
                'google docs',
                'session google docs expiree',
                'reconnectez google docs',
                'reconnect google docs',
            ],
        ],
        'notion-workspace' => [
            'label' => 'Notion Workspace',
            'route' => 'notion-workspace.index',
            'patterns' => [
                'notion workspace',
                'notion n est pas connecte',
                'notion is not connected',
                'session notion expiree',
                'session notion workspace expiree',
                'reconnectez notion',
                'reconnect notion',
                'reconnectez votre workspace notion',
                'reconnect your notion workspace',
            ],
        ],
    ];

    public static function resolve(?string $message): ?array
    {
        $normalized = Str::lower(trim((string) $message));
        if ($normalized === '') {
            return null;
        }

        foreach (self::PROVIDERS as $slug => $provider) {
            foreach ($provider['patterns'] as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    return self::decorate($slug, $provider);
                }
            }
        }

        return null;
    }

    public static function messageRequiresReconnect(?string $message): bool
    {
        return self::resolve($message) !== null;
    }

    public static function providerLabel(string $slug): string
    {
        $key = 'automation::automation.presenter.integrations.' . $slug;

        return __($key) !== $key
            ? __($key)
            : (string) (self::PROVIDERS[$slug]['label'] ?? ucfirst(str_replace('-', ' ', $slug)));
    }

    public static function providerRoute(string $slug): ?string
    {
        return self::PROVIDERS[$slug]['route'] ?? null;
    }

    protected static function decorate(string $slug, array $provider): array
    {
        $routeName = (string) ($provider['route'] ?? '');
        $url = $routeName !== '' && Route::has($routeName) ? route($routeName) : null;

        return [
            'slug' => $slug,
            'label' => self::providerLabel($slug),
            'route' => $routeName !== '' ? $routeName : null,
            'url' => $url,
        ];
    }
}
