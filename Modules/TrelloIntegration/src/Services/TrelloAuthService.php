<?php

namespace Modules\TrelloIntegration\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Modules\TrelloIntegration\Models\TrelloToken;
use RuntimeException;

class TrelloAuthService
{
    public function __construct(protected TrelloApiService $api)
    {
    }

    public function isConfigured(): bool
    {
        return trim((string) config('trello-integration.api.key')) !== '';
    }

    public function configurationStatus(): array
    {
        return $this->api->diagnoseConfiguration();
    }

    public function getAuthUrl(int $tenantId, int $userId): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('La connexion Trello n est pas encore configuree.');
        }

        $status = $this->configurationStatus();
        if (!($status['ready'] ?? false)) {
            throw new RuntimeException((string) ($status['message'] ?? 'La configuration Trello n est pas valide.'));
        }

        $state = $this->buildState($tenantId, $userId);
        $returnUrl = $this->resolveReturnUrl($state);

        return $this->api->getAuthorizeUrl($returnUrl);
    }

    public function parseState(string $state): array
    {
        $payload = json_decode(Crypt::decryptString($state), true);

        if (!is_array($payload) || !isset($payload['tenant_id'], $payload['user_id'])) {
            throw new RuntimeException('Etat OAuth Trello invalide.');
        }

        return $payload;
    }

    public function exchangeToken(string $token, int $tenantId, int $userId): TrelloToken
    {
        $profile = $this->api->getMemberProfile($token);

        return TrelloToken::query()->updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'connected_by' => $userId,
                'api_token' => $token,
                'scopes' => (array) config('trello-integration.auth.scopes', ['read', 'write']),
                'token_expiration' => (string) config('trello-integration.auth.expiration', '30days'),
                'token_expires_at' => $this->resolveTokenExpiry(),
                'trello_member_id' => (string) ($profile['id'] ?? ''),
                'trello_username' => (string) ($profile['username'] ?? ''),
                'trello_full_name' => (string) ($profile['fullName'] ?? ''),
                'trello_avatar_url' => (string) ($profile['avatarUrl'] ?? ''),
                'trello_profile_url' => (string) ($profile['url'] ?? ''),
                'is_active' => true,
                'connected_at' => now(),
                'disconnected_at' => null,
            ]
        )->fresh();
    }

    public function disconnect(int $tenantId): void
    {
        $token = TrelloToken::query()->where('tenant_id', $tenantId)->first();

        if (!$token) {
            return;
        }

        $token->update([
            'is_active' => false,
            'disconnected_at' => now(),
            'api_token' => '',
        ]);
    }

    public function invalidateToken(int $tenantId): void
    {
        $token = TrelloToken::query()->where('tenant_id', $tenantId)->first();

        if (!$token) {
            return;
        }

        $token->update([
            'is_active' => false,
            'disconnected_at' => now(),
            'api_token' => '',
        ]);
    }

    public function getToken(int $tenantId): ?TrelloToken
    {
        return TrelloToken::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    public function getTokenOrFail(int $tenantId): TrelloToken
    {
        $token = $this->getToken($tenantId);

        if (!$token || trim((string) $token->api_token) === '') {
            throw new RuntimeException('Trello n est pas connecte pour ce tenant.');
        }

        if ($token->is_expired) {
            $this->invalidateToken($tenantId);
            throw new RuntimeException('Session Trello expiree ou revoquee. Reconnectez votre compte Trello.');
        }

        return $token;
    }

    private function buildState(int $tenantId, int $userId): string
    {
        return Crypt::encryptString(json_encode([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'nonce' => Str::uuid()->toString(),
            'ts' => now()->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    private function resolveReturnUrl(string $state): string
    {
        $configured = trim((string) config('trello-integration.auth.redirect_uri', ''));

        if ($configured === '') {
            return route('trello-integration.callback', ['state' => $state]);
        }

        $base = Str::startsWith($configured, ['http://', 'https://'])
            ? $configured
            : url($configured);

        $separator = str_contains($base, '?') ? '&' : '?';

        return $base . $separator . 'state=' . urlencode($state);
    }

    private function resolveTokenExpiry(): ?\Illuminate\Support\Carbon
    {
        return match ((string) config('trello-integration.auth.expiration', '30days')) {
            '1hour' => now()->addHour(),
            '1day' => now()->addDay(),
            '30days' => now()->addDays(30),
            'never' => null,
            default => now()->addDays(30),
        };
    }
}
