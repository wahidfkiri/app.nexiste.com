<?php

namespace Modules\TrelloIntegration\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class TrelloApiService
{
    public function diagnoseConfiguration(): array
    {
        $key = trim((string) config('trello-integration.api.key'));
        $redirectUri = $this->resolveRedirectUri();

        if ($key === '') {
            return [
                'configured' => false,
                'ready' => false,
                'status' => 'missing_key',
                'message' => 'La cle API Trello n est pas configuree.',
                'details' => [],
            ];
        }

        $cacheKey = 'trello-integration:diagnostics:' . sha1($key . '|' . $redirectUri);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($key, $redirectUri) {
            $baseProbe = $this->runAuthorizeProbe([
                'key' => $key,
                'name' => (string) config('trello-integration.app.name', config('app.name') . ' Trello'),
                'scope' => implode(',', (array) config('trello-integration.auth.scopes', ['read', 'write'])),
                'expiration' => '1day',
                'response_type' => 'token',
            ]);

            if ($baseProbe['status'] === 'invalid_key') {
                return [
                    'configured' => true,
                    'ready' => false,
                    'status' => 'invalid_key',
                    'message' => 'La cle API Trello est invalide ou n est liee a aucun Power-Up actif.',
                    'details' => [
                        'hint' => 'Verifiez Trello Power-Ups Admin > votre Power-Up > API Key.',
                        'redirect_uri' => $redirectUri,
                    ],
                ];
            }

            if ($baseProbe['status'] !== 'ok') {
                return [
                    'configured' => true,
                    'ready' => false,
                    'status' => 'unreachable',
                    'message' => 'Impossible de valider la configuration Trello pour le moment.',
                    'details' => [
                        'hint' => 'Reessayez dans quelques instants ou verifiez l acces reseau a Trello.',
                        'redirect_uri' => $redirectUri,
                    ],
                ];
            }

            $redirectProbe = $this->runAuthorizeProbe([
                'key' => $key,
                'name' => (string) config('trello-integration.app.name', config('app.name') . ' Trello'),
                'scope' => implode(',', (array) config('trello-integration.auth.scopes', ['read', 'write'])),
                'expiration' => '1day',
                'response_type' => 'token',
                'callback_method' => 'fragment',
                'return_url' => $redirectUri,
            ]);

            if ($redirectProbe['status'] === 'invalid_return_url') {
                return [
                    'configured' => true,
                    'ready' => false,
                    'status' => 'invalid_return_url',
                    'message' => 'L URL de retour Trello n est pas autorisee pour cette cle API.',
                    'details' => [
                        'hint' => 'Ajoutez au minimum l origine https://localhost dans Allowed Origins.',
                        'redirect_uri' => $redirectUri,
                    ],
                ];
            }

            return [
                'configured' => true,
                'ready' => true,
                'status' => 'ok',
                'message' => 'La configuration Trello est valide.',
                'details' => [
                    'redirect_uri' => $redirectUri,
                ],
            ];
        });
    }

    public function getAuthorizeUrl(string $returnUrl): string
    {
        $query = http_build_query([
            'key' => $this->apiKey(),
            'name' => (string) config('trello-integration.app.name', config('app.name') . ' Trello'),
            'scope' => implode(',', (array) config('trello-integration.auth.scopes', ['read', 'write'])),
            'expiration' => (string) config('trello-integration.auth.expiration', '30days'),
            'response_type' => 'token',
            'callback_method' => 'fragment',
            'return_url' => $returnUrl,
        ]);

        return 'https://trello.com/1/authorize?' . $query;
    }

    public function getMemberProfile(string $token): array
    {
        return $this->request('get', 'members/me', $token, [
            'fields' => 'id,username,fullName,avatarUrl,url',
        ]);
    }

    public function getBoards(string $token): array
    {
        return $this->request('get', 'members/me/boards', $token, [
            'filter' => 'all',
            'fields' => 'id,name,desc,url,closed,dateLastActivity,prefs,idOrganization,starred',
        ]);
    }

    public function getBoard(string $token, string $boardId): array
    {
        return $this->request('get', 'boards/' . rawurlencode($boardId), $token, [
            'fields' => 'id,name,desc,url,closed,dateLastActivity,prefs,idOrganization,starred',
        ]);
    }

    public function getBoardLists(string $token, string $boardId): array
    {
        return $this->request('get', 'boards/' . rawurlencode($boardId) . '/lists', $token, [
            'filter' => 'all',
            'fields' => 'id,name,closed,pos,idBoard',
        ]);
    }

    public function getBoardCards(string $token, string $boardId): array
    {
        return $this->request('get', 'boards/' . rawurlencode($boardId) . '/cards', $token, [
            'filter' => 'all',
            'fields' => 'id,idBoard,idList,name,desc,closed,pos,due,dateLastActivity,labels,idMembers,url,shortUrl,badges,cover',
            'members' => 'true',
            'member_fields' => 'id,avatarUrl,fullName,initials,username',
        ]);
    }

    public function getCard(string $token, string $cardId): array
    {
        return $this->request('get', 'cards/' . rawurlencode($cardId), $token, [
            'fields' => 'id,idBoard,idList,name,desc,closed,pos,due,dateLastActivity,labels,idMembers,url,shortUrl,badges,cover',
            'members' => 'true',
            'member_fields' => 'id,avatarUrl,fullName,initials,username',
        ]);
    }

    public function createCard(string $token, string $listId, array $payload): array
    {
        $body = [
            'idList' => $listId,
            'name' => (string) ($payload['name'] ?? ''),
            'desc' => (string) ($payload['description'] ?? ''),
            'pos' => (string) ($payload['pos'] ?? 'bottom'),
        ];

        if (!empty($payload['due'])) {
            $body['due'] = $payload['due'];
        }

        return $this->request('post', 'cards', $token, [], $body);
    }

    public function updateCard(string $token, string $cardId, array $payload): array
    {
        $body = [];

        foreach (['name', 'desc', 'idList', 'pos', 'closed'] as $field) {
            if (array_key_exists($field, $payload)) {
                $body[$field] = $payload[$field];
            }
        }

        if (array_key_exists('due', $payload)) {
            $body['due'] = $payload['due'] ?: '';
        }

        return $this->request('put', 'cards/' . rawurlencode($cardId), $token, [], $body);
    }

    public function archiveCard(string $token, string $cardId): array
    {
        return $this->updateCard($token, $cardId, ['closed' => 'true']);
    }

    private function request(string $method, string $path, string $token, array $query = [], array $body = []): array
    {
        $request = Http::baseUrl(rtrim((string) config('trello-integration.api.base_url', 'https://api.trello.com/1'), '/'))
            ->acceptJson()
            ->timeout((int) config('trello-integration.api.timeout', 25));

        $authQuery = array_merge($query, [
            'key' => $this->apiKey(),
            'token' => $token,
        ]);

        $response = match (strtolower($method)) {
            'get' => $request->get($path, $authQuery),
            'post' => $request->asForm()->post($path, array_merge($authQuery, $body)),
            'put' => $request->asForm()->put($path, array_merge($authQuery, $body)),
            'delete' => $request->delete($path, $authQuery),
            default => throw new RuntimeException('Methode Trello API non supportee.'),
        };

        if (in_array($response->status(), [401, 403], true)) {
            throw new RuntimeException('Session Trello expiree ou revoquee. Reconnectez votre compte Trello.');
        }

        if ($response->status() === 429) {
            throw new RuntimeException('Trello limite temporairement les requetes. Reessayez dans quelques instants.');
        }

        if (!$response->successful()) {
            $message = (string) ($response->json('message') ?? $response->json('error') ?? $response->body());
            throw new RuntimeException($message !== '' ? $message : 'Impossible de communiquer avec Trello.');
        }

        return (array) $response->json();
    }

    private function apiKey(): string
    {
        $key = trim((string) config('trello-integration.api.key'));

        if ($key === '') {
            throw new RuntimeException('La cle API Trello n est pas configuree.');
        }

        return $key;
    }

    private function runAuthorizeProbe(array $query): array
    {
        try {
            $response = Http::timeout((int) config('trello-integration.api.timeout', 25))
                ->withHeaders(['Accept' => 'text/html,application/xhtml+xml'])
                ->get('https://trello.com/1/authorize', $query);

            $body = (string) $response->body();

            if (Str::contains($body, 'App not found')) {
                return ['status' => 'invalid_key'];
            }

            if (Str::contains($body, 'Invalid return_url')) {
                return ['status' => 'invalid_return_url'];
            }

            if ($response->successful()) {
                return ['status' => 'ok'];
            }
        } catch (\Throwable) {
            // Fallback below.
        }

        return ['status' => 'unreachable'];
    }

    private function resolveRedirectUri(): string
    {
        $configured = trim((string) config('trello-integration.auth.redirect_uri', ''));

        if ($configured === '') {
            return route('trello-integration.callback');
        }

        return Str::startsWith($configured, ['http://', 'https://'])
            ? $configured
            : url($configured);
    }
}
