<?php

namespace NexusExtensions\NotionWorkspace\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use NexusExtensions\NotionWorkspace\Models\NotionWorkspaceToken;
use RuntimeException;

class NotionWorkspaceApiService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('notion-workspace.api.base_url', 'https://api.notion.com/v1/'),
            'timeout' => (int) config('notion-workspace.api.timeout', 30),
        ]);
    }

    public function getAuthUrl(int $tenantId, int $userId): string
    {
        $clientId = (string) config('notion-workspace.oauth.client_id');
        $redirectUri = $this->redirectUri();

        if ($clientId === '') {
            throw new RuntimeException(__('notion-workspace::messages.errors.client_id_missing'));
        }

        $state = encrypt([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'nonce' => Str::uuid()->toString(),
            'ts' => now()->timestamp,
        ]);

        $query = http_build_query([
            'owner' => 'user',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
        ]);

        return rtrim((string) config('notion-workspace.api.auth_url', 'https://api.notion.com/v1/oauth/authorize'), '?') . '?' . $query;
    }

    public function parseState(string $encryptedState): array
    {
        $state = decrypt($encryptedState);

        if (!is_array($state) || !isset($state['tenant_id'], $state['user_id'])) {
            throw new RuntimeException(__('notion-workspace::messages.errors.oauth_state_invalid'));
        }

        return $state;
    }

    public function exchangeCode(string $code, int $tenantId, int $userId): NotionWorkspaceToken
    {
        $existingToken = NotionWorkspaceToken::query()->where('tenant_id', $tenantId)->first();

        $data = $this->oauthTokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
        ]);

        return $this->persistTokenPayload($tenantId, $userId, $data, $existingToken);
    }

    public function disconnect(int $tenantId): void
    {
        $token = NotionWorkspaceToken::query()->where('tenant_id', $tenantId)->first();

        if (!$token) {
            return;
        }

        if ($token->access_token) {
            try {
                $this->client->post('oauth/revoke', [
                    'headers' => $this->baseHeaders([
                        'Authorization' => 'Basic ' . $this->basicCredential(),
                    ]),
                    'json' => [
                        'token' => $token->access_token,
                    ],
                ]);
            } catch (GuzzleException) {
                // Best effort revoke only.
            }
        }

        $token->update([
            'is_active' => false,
            'disconnected_at' => now(),
            'access_token' => '',
            'refresh_token' => null,
        ]);
    }

    public function getToken(int $tenantId): ?NotionWorkspaceToken
    {
        return NotionWorkspaceToken::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    public function getTokenOrFail(int $tenantId): NotionWorkspaceToken
    {
        $token = $this->getToken($tenantId);

        if (!$token) {
            throw new RuntimeException(__('notion-workspace::messages.errors.not_connected'));
        }

        return $token;
    }

    public function searchPages(int $tenantId, string $query = '', ?string $cursor = null, int $pageSize = 20): array
    {
        $pageSize = max(1, min($pageSize, 100));

        $payload = [
            'page_size' => $pageSize,
            'filter' => [
                'property' => 'object',
                'value' => 'page',
            ],
            'sort' => [
                'direction' => 'descending',
                'timestamp' => 'last_edited_time',
            ],
        ];

        if (trim($query) !== '') {
            $payload['query'] = trim($query);
        }

        if ($cursor) {
            $payload['start_cursor'] = $cursor;
        }

        $response = $this->apiRequest($tenantId, 'POST', 'search', [
            'json' => $payload,
        ]);

        return [
            'results' => collect((array) ($response['results'] ?? []))
                ->map(fn (array $page) => $this->normalizePage($page))
                ->values()
                ->all(),
            'has_more' => (bool) ($response['has_more'] ?? false),
            'next_cursor' => $response['next_cursor'] ?? null,
        ];
    }

    public function getPage(int $tenantId, string $pageId): array
    {
        $page = $this->apiRequest($tenantId, 'GET', 'pages/' . rawurlencode($pageId));
        $blocks = $this->retrieveBlockChildrenRecursively($tenantId, $pageId);

        return [
            'page' => $this->normalizePage($page),
            'blocks' => $blocks,
        ];
    }

    public function createPage(int $tenantId, array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException(__('notion-workspace::messages.errors.page_title_required'));
        }

        $body = [
            'parent' => !empty($payload['parent_page_id'])
                ? ['type' => 'page_id', 'page_id' => (string) $payload['parent_page_id']]
                : ['type' => 'workspace', 'workspace' => true],
            'properties' => [
                'title' => [
                    'title' => [[
                        'type' => 'text',
                        'text' => ['content' => $title],
                    ]],
                ],
            ],
        ];

        $children = $this->buildParagraphChildren((string) ($payload['content'] ?? ''));
        if (!empty($children)) {
            $body['children'] = $children;
        }

        $icon = trim((string) ($payload['icon'] ?? ''));
        if ($icon !== '') {
            $body['icon'] = [
                'type' => 'emoji',
                'emoji' => $icon,
            ];
        }

        $page = $this->apiRequest($tenantId, 'POST', 'pages', [
            'json' => $body,
        ]);

        return $this->normalizePage($page);
    }

    public function refreshPageLinkMetadata(int $tenantId, string $pageId): array
    {
        $page = $this->apiRequest($tenantId, 'GET', 'pages/' . rawurlencode($pageId));
        return $this->normalizePage($page);
    }

    private function retrieveBlockChildrenRecursively(int $tenantId, string $blockId): array
    {
        $results = [];
        $cursor = null;

        do {
            $query = ['page_size' => (int) config('notion-workspace.api.block_page_size', 100)];
            if ($cursor) {
                $query['start_cursor'] = $cursor;
            }

            $response = $this->apiRequest($tenantId, 'GET', 'blocks/' . rawurlencode($blockId) . '/children', [
                'query' => $query,
            ]);

            foreach ((array) ($response['results'] ?? []) as $block) {
                if (!is_array($block)) {
                    continue;
                }

                $normalized = $this->normalizeBlock($block);
                if (($block['has_children'] ?? false) === true) {
                    $normalized['children'] = $this->retrieveBlockChildrenRecursively($tenantId, (string) $block['id']);
                }

                $results[] = $normalized;
            }

            $cursor = $response['has_more'] ?? false ? ($response['next_cursor'] ?? null) : null;
        } while ($cursor);

        return $results;
    }

    private function normalizePage(array $page): array
    {
        return [
            'id' => (string) ($page['id'] ?? ''),
            'title' => $this->extractPageTitle($page),
            'url' => (string) ($page['url'] ?? ''),
            'parent' => [
                'type' => (string) data_get($page, 'parent.type', ''),
                'page_id' => data_get($page, 'parent.page_id'),
                'workspace' => data_get($page, 'parent.workspace'),
                'data_source_id' => data_get($page, 'parent.data_source_id'),
            ],
            'icon' => $this->normalizeIcon($page['icon'] ?? null),
            'cover' => $this->normalizeCover($page['cover'] ?? null),
            'is_archived' => (bool) ($page['is_archived'] ?? false),
            'in_trash' => (bool) ($page['in_trash'] ?? false),
            'created_time' => $page['created_time'] ?? null,
            'last_edited_time' => $page['last_edited_time'] ?? null,
            'created_by' => data_get($page, 'created_by.id'),
            'last_edited_by' => data_get($page, 'last_edited_by.id'),
        ];
    }

    private function normalizeBlock(array $block): array
    {
        $type = (string) ($block['type'] ?? 'unsupported');
        $payload = is_array($block[$type] ?? null) ? $block[$type] : [];

        return [
            'id' => (string) ($block['id'] ?? ''),
            'type' => $type,
            'has_children' => (bool) ($block['has_children'] ?? false),
            'plain_text' => $this->extractBlockPlainText($type, $payload),
            'checked' => (bool) data_get($payload, 'checked', false),
            'language' => data_get($payload, 'language'),
            'icon' => $this->normalizeIcon(data_get($payload, 'icon')),
            'color' => (string) data_get($payload, 'color', 'default'),
            'url' => $this->extractBlockUrl($type, $payload),
            'table_width' => (int) data_get($payload, 'table_width', 0),
            'cells' => $this->normalizeTableCells($type, $payload),
            'children' => [],
        ];
    }

    private function extractPageTitle(array $page): string
    {
        $properties = (array) ($page['properties'] ?? []);

        if (isset($properties['title']['title']) && is_array($properties['title']['title'])) {
            return $this->plainTextFromRichText($properties['title']['title']) ?: __('notion-workspace::messages.defaults.untitled');
        }

        foreach ($properties as $property) {
            if (($property['type'] ?? null) === 'title') {
                return $this->plainTextFromRichText((array) ($property['title'] ?? [])) ?: __('notion-workspace::messages.defaults.untitled');
            }
        }

        return __('notion-workspace::messages.defaults.untitled');
    }

    private function normalizeIcon(mixed $icon): ?array
    {
        if (!is_array($icon)) {
            return null;
        }

        $type = (string) ($icon['type'] ?? '');

        if ($type === 'emoji') {
            return ['type' => 'emoji', 'value' => (string) ($icon['emoji'] ?? '')];
        }

        if ($type === 'external') {
            return ['type' => 'image', 'value' => (string) data_get($icon, 'external.url', '')];
        }

        if ($type === 'file') {
            return ['type' => 'image', 'value' => (string) data_get($icon, 'file.url', '')];
        }

        return null;
    }

    private function normalizeCover(mixed $cover): ?string
    {
        if (!is_array($cover)) {
            return null;
        }

        return match ((string) ($cover['type'] ?? '')) {
            'external' => (string) data_get($cover, 'external.url', ''),
            'file' => (string) data_get($cover, 'file.url', ''),
            default => null,
        };
    }

    private function extractBlockPlainText(string $type, array $payload): string
    {
        return match ($type) {
            'paragraph', 'heading_1', 'heading_2', 'heading_3', 'bulleted_list_item', 'numbered_list_item', 'to_do', 'quote', 'callout', 'toggle' => $this->plainTextFromRichText((array) ($payload['rich_text'] ?? [])),
            'code' => $this->plainTextFromRichText((array) ($payload['rich_text'] ?? [])),
            'table_row' => collect((array) ($payload['cells'] ?? []))
                ->map(fn ($cell) => $this->plainTextFromRichText(is_array($cell) ? $cell : []))
                ->implode(' | '),
            'child_page' => (string) ($payload['title'] ?? __('notion-workspace::messages.defaults.child_page')),
            default => '',
        };
    }

    private function extractBlockUrl(string $type, array $payload): ?string
    {
        return match ($type) {
            'image' => (string) (data_get($payload, 'external.url') ?: data_get($payload, 'file.url') ?: ''),
            'bookmark', 'embed', 'link_preview' => (string) ($payload['url'] ?? ''),
            default => null,
        } ?: null;
    }

    private function normalizeTableCells(string $type, array $payload): array
    {
        if ($type !== 'table_row') {
            return [];
        }

        return collect((array) ($payload['cells'] ?? []))
            ->map(fn ($cell) => $this->plainTextFromRichText(is_array($cell) ? $cell : []))
            ->values()
            ->all();
    }

    private function plainTextFromRichText(array $richText): string
    {
        return collect($richText)
            ->map(fn ($item) => (string) ($item['plain_text'] ?? data_get($item, 'text.content', '')))
            ->implode('');
    }

    private function buildParagraphChildren(string $content): array
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', trim($content)) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter();

        return $lines->take(50)->map(function (string $line) {
            return [
                'object' => 'block',
                'type' => 'paragraph',
                'paragraph' => [
                    'rich_text' => [[
                        'type' => 'text',
                        'text' => ['content' => Str::limit($line, 1900, '')],
                    ]],
                ],
            ];
        })->values()->all();
    }

    private function persistTokenPayload(int $tenantId, int $userId, array $data, ?NotionWorkspaceToken $existingToken = null): NotionWorkspaceToken
    {
        $ownerType = (string) data_get($data, 'owner.type', '');
        $ownerUser = $ownerType === 'user' ? (array) data_get($data, 'owner.user', []) : [];
        $connectedBy = $userId > 0 ? $userId : (int) ($existingToken?->connected_by ?? 0);

        return NotionWorkspaceToken::query()->updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'connected_by' => $connectedBy ?: null,
                'access_token' => (string) ($data['access_token'] ?? $existingToken?->access_token ?? ''),
                'refresh_token' => $data['refresh_token'] ?? $existingToken?->refresh_token,
                'token_expires_at' => isset($data['expires_in']) ? now()->addSeconds((int) $data['expires_in']) : $existingToken?->token_expires_at,
                'notion_workspace_id' => $data['workspace_id'] ?? $existingToken?->notion_workspace_id,
                'notion_workspace_name' => $data['workspace_name'] ?? $existingToken?->notion_workspace_name,
                'notion_workspace_icon' => $data['workspace_icon'] ?? $existingToken?->notion_workspace_icon,
                'notion_bot_id' => $data['bot_id'] ?? $existingToken?->notion_bot_id,
                'notion_owner_type' => $ownerType ?: $existingToken?->notion_owner_type,
                'notion_user_id' => $ownerUser['id'] ?? $existingToken?->notion_user_id,
                'notion_user_name' => $ownerUser['name'] ?? $existingToken?->notion_user_name,
                'notion_user_email' => data_get($ownerUser, 'person.email') ?? $existingToken?->notion_user_email,
                'notion_user_avatar_url' => $ownerUser['avatar_url'] ?? $existingToken?->notion_user_avatar_url,
                'is_active' => true,
                'connected_at' => $existingToken?->connected_at ?? now(),
                'disconnected_at' => null,
            ]
        )->fresh();
    }

    private function oauthTokenRequest(array $json): array
    {
        try {
            $response = $this->client->post('oauth/token', [
                'headers' => $this->baseHeaders([
                    'Authorization' => 'Basic ' . $this->basicCredential(),
                ]),
                'json' => $json,
            ]);

            return json_decode((string) $response->getBody(), true) ?: [];
        } catch (GuzzleException $e) {
            $message = $e->getMessage();
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $payload = json_decode((string) $e->getResponse()->getBody(), true);
                $message = (string) ($payload['message'] ?? $payload['error'] ?? $message);
            }

            throw new RuntimeException(__('notion-workspace::messages.errors.oauth_finalize_failed', ['message' => $message]));
        }
    }

    private function refreshAccessToken(NotionWorkspaceToken $token): NotionWorkspaceToken
    {
        if (!$token->refresh_token) {
            $this->invalidateTokenAfterOAuthFailure($token, 'missing_refresh_token');
            throw new RuntimeException(__('notion-workspace::messages.errors.session_expired'));
        }

        try {
            $response = $this->client->post('oauth/token', [
                'headers' => $this->baseHeaders([
                    'Authorization' => 'Basic ' . $this->basicCredential(),
                ]),
                'json' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $token->refresh_token,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true) ?: [];
        } catch (GuzzleException $e) {
            $statusCode = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            $payload = method_exists($e, 'getResponse') && $e->getResponse()
                ? json_decode((string) $e->getResponse()->getBody(), true)
                : [];
            $message = (string) ($payload['message'] ?? $payload['error'] ?? $e->getMessage());

            if (in_array($statusCode, [400, 401], true)) {
                $this->invalidateTokenAfterOAuthFailure($token, 'refresh_failed');
                throw new RuntimeException(__('notion-workspace::messages.errors.session_expired'));
            }

            throw new RuntimeException(__('notion-workspace::messages.errors.session_refresh_failed', ['message' => $message]));
        }

        return $this->persistTokenPayload((int) $token->tenant_id, (int) ($token->connected_by ?? 0), $data, $token);
    }

    private function invalidateTokenAfterOAuthFailure(NotionWorkspaceToken $token, string $reason): void
    {
        try {
            $token->update([
                'is_active' => false,
                'disconnected_at' => now(),
                'access_token' => '',
                'refresh_token' => null,
            ]);
        } catch (\Throwable) {
            // Swallow secondary failure.
        }
    }

    private function apiRequest(int $tenantId, string $method, string $uri, array $options = [], bool $retrying = false): array
    {
        $token = $this->getTokenOrFail($tenantId);

        if ($token->is_expired) {
            $token = $this->refreshAccessToken($token);
        }

        $headers = array_merge([
            'Authorization' => 'Bearer ' . $token->access_token,
        ], $options['headers'] ?? []);

        $requestOptions = Arr::except($options, ['headers']);
        $requestOptions['headers'] = $this->baseHeaders($headers);

        try {
            $response = $this->client->request($method, ltrim($uri, '/'), $requestOptions);
            $body = (string) $response->getBody();

            NotionWorkspaceToken::query()
                ->where('tenant_id', $tenantId)
                ->update(['last_synced_at' => now()]);

            return $body === '' ? [] : (json_decode($body, true) ?: []);
        } catch (GuzzleException $e) {
            $statusCode = method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            $payload = method_exists($e, 'getResponse') && $e->getResponse()
                ? json_decode((string) $e->getResponse()->getBody(), true)
                : [];

            if (!$retrying && $statusCode === 401 && $token->refresh_token) {
                $this->refreshAccessToken($token);
                return $this->apiRequest($tenantId, $method, $uri, $options, true);
            }

            if ($statusCode === 401) {
                $this->invalidateTokenAfterOAuthFailure($token, 'api_unauthorized');
                throw new RuntimeException(__('notion-workspace::messages.errors.session_expired'));
            }

            $message = (string) ($payload['message'] ?? $payload['error'] ?? $e->getMessage());
            throw new RuntimeException(__('notion-workspace::messages.errors.api_error', ['message' => $message]));
        }
    }

    private function baseHeaders(array $headers = []): array
    {
        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Notion-Version' => (string) config('notion-workspace.api.version', '2026-03-11'),
        ], $headers);
    }

    private function basicCredential(): string
    {
        $clientId = (string) config('notion-workspace.oauth.client_id');
        $clientSecret = (string) config('notion-workspace.oauth.client_secret');

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException(__('notion-workspace::messages.errors.oauth_credentials_missing'));
        }

        return base64_encode($clientId . ':' . $clientSecret);
    }

    public function redirectUri(): string
    {
        $path = (string) config('notion-workspace.oauth.redirect_uri', '/extensions/notion-workspace/oauth/callback');
        if (trim($path) === '') {
            $path = '/extensions/notion-workspace/oauth/callback';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url($path);
    }
}
