<?php

namespace NexusExtensions\Dropbox\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NexusExtensions\Dropbox\Models\DropboxActivityLog;
use NexusExtensions\Dropbox\Models\DropboxFile;
use NexusExtensions\Dropbox\Models\DropboxToken;
use RuntimeException;
use Symfony\Component\Mime\MimeTypes;

class DropboxService
{
    public function getAuthUrl(int $tenantId, int $userId): string
    {
        $clientId = trim((string) config('dropbox.oauth.client_id'));
        if ($clientId === '') {
            throw new RuntimeException(__('dropbox::messages.errors.client_id_missing'));
        }

        $state = encrypt([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'nonce' => Str::uuid()->toString(),
            'ts' => now()->timestamp,
        ]);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'token_access_type' => (string) config('dropbox.oauth.token_access_type', 'offline'),
            'scope' => implode(' ', (array) config('dropbox.oauth.scopes', [])),
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return rtrim((string) config('dropbox.api.auth_url'), '?') . '?' . $query;
    }

    public function parseState(string $encryptedState): array
    {
        $state = decrypt($encryptedState);
        if (!is_array($state) || !isset($state['tenant_id'], $state['user_id'])) {
            throw new RuntimeException(__('dropbox::messages.errors.invalid_oauth_state'));
        }

        return $state;
    }

    public function exchangeCode(string $code, int $tenantId, int $userId): DropboxToken
    {
        $existingToken = DropboxToken::forTenant($tenantId)->first();
        $response = $this->oauthClient()->post((string) config('dropbox.api.token_url'), [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => (string) config('dropbox.oauth.client_id'),
            'client_secret' => (string) config('dropbox.oauth.client_secret'),
            'redirect_uri' => $this->redirectUri(),
        ]);

        $tokenData = $this->parseOauthResponse($response);
        $accessToken = (string) ($tokenData['access_token'] ?? '');
        if ($accessToken === '') {
            throw new RuntimeException(__('dropbox::messages.errors.missing_access_token'));
        }

        $account = $this->apiWithAccessToken($accessToken, 'users/get_current_account');
        $space = $this->apiWithAccessToken($accessToken, 'users/get_space_usage');
        $rootFolder = $this->ensureRootFolder($tenantId, $accessToken);

        $token = DropboxToken::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'connected_by' => $userId,
                'access_token' => $accessToken,
                'refresh_token' => $tokenData['refresh_token'] ?? $existingToken?->refresh_token,
                'token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds((int) $tokenData['expires_in']) : now()->addHour(),
                'dropbox_account_id' => (string) ($account['account_id'] ?? ''),
                'dropbox_email' => (string) ($account['email'] ?? ''),
                'dropbox_name' => trim((string) data_get($account, 'name.display_name', '')),
                'dropbox_avatar_url' => (string) ($account['profile_photo_url'] ?? ''),
                'dropbox_root_id' => (string) ($rootFolder['id'] ?? ''),
                'dropbox_root_path' => (string) ($rootFolder['path_lower'] ?? ''),
                'space_quota_total_gb' => $this->quotaTotalGb($space),
                'space_quota_used_gb' => round(((int) ($space['used'] ?? 0)) / 1073741824, 2),
                'is_active' => true,
                'last_sync_at' => now(),
                'connected_at' => now(),
                'disconnected_at' => null,
            ]
        );

        $this->log($tenantId, null, null, 'connected', [
            'dropbox_email' => (string) ($account['email'] ?? ''),
        ]);

        return $token->fresh();
    }

    public function disconnect(int $tenantId): void
    {
        $token = DropboxToken::forTenant($tenantId)->first();
        if (!$token) {
            return;
        }

        try {
            if (!empty($token->access_token)) {
                $this->sendJsonRequest($this->jsonClient((string) $token->access_token), 'auth/token/revoke');
            }
        } catch (\Throwable $e) {
            Log::warning('[Dropbox] echec de revocation du token', ['message' => $e->getMessage()]);
        }

        $token->update([
            'is_active' => false,
            'disconnected_at' => now(),
            'access_token' => '',
            'refresh_token' => null,
        ]);

        $this->log($tenantId, null, null, 'disconnected');
    }

    public function getToken(int $tenantId): ?DropboxToken
    {
        return DropboxToken::forTenant($tenantId)->active()->first();
    }

    public function listFiles(int $tenantId, ?string $folderId = null, int $page = 1, string $search = '', ?string $pageToken = null): array
    {
        $token = $this->getValidToken($tenantId);
        if (trim($search) !== '') {
            return [
                'files' => $this->search($tenantId, $search),
                'next_page_token' => null,
                'folder_id' => $folderId ?: $this->rootReference($token),
                'root_folder' => $this->rootReference($token),
            ];
        }

        $reference = $folderId ?: $this->rootReference($token);
        if ($pageToken) {
            $data = $this->api($tenantId, 'files/list_folder/continue', ['cursor' => $pageToken]);
        } else {
            $path = $this->normalizeListPath($reference, $token);
            $data = $this->api($tenantId, 'files/list_folder', [
                'path' => $path,
                'recursive' => false,
                'include_deleted' => false,
                'include_media_info' => false,
                'limit' => (int) config('dropbox.api.page_size', 100),
            ]);
        }

        $entries = collect((array) ($data['entries'] ?? []))
            ->filter(fn (array $entry) => ($entry['.tag'] ?? '') !== 'deleted')
            ->map(function (array $entry) use ($tenantId) {
                $this->syncMetadataToLocal($entry, $tenantId);

                return $this->formatMetadata($entry);
            })
            ->values()
            ->all();

        DropboxToken::forTenant($tenantId)->update(['last_sync_at' => now()]);

        return [
            'files' => $entries,
            'next_page_token' => ($data['has_more'] ?? false) ? ($data['cursor'] ?? null) : null,
            'folder_id' => $reference,
            'root_folder' => $this->rootReference($token),
        ];
    }

    public function createFolder(int $tenantId, string $name, ?string $parentId = null): array
    {
        $parentPath = $this->resolveFolderPath($tenantId, $parentId);
        $targetPath = $this->joinPath($parentPath, $name);

        $data = $this->api($tenantId, 'files/create_folder_v2', [
            'path' => $targetPath,
            'autorename' => true,
        ]);

        $metadata = (array) ($data['metadata'] ?? []);
        $this->syncMetadataToLocal($metadata, $tenantId);
        $this->log($tenantId, $metadata['id'] ?? null, $metadata['name'] ?? $name, 'create_folder');

        return $this->formatMetadata($metadata);
    }

    public function uploadFile(int $tenantId, UploadedFile $file, ?string $parentId = null): array
    {
        $mimeType = (string) ($file->getMimeType() ?: 'application/octet-stream');
        $allowed = (array) config('dropbox.allowed_mime_types', []);
        if (!empty($allowed) && !in_array($mimeType, $allowed, true)) {
            throw new RuntimeException(__('dropbox::messages.errors.file_type_not_allowed', ['mime' => $mimeType]));
        }

        $maxBytes = (int) config('dropbox.api.max_file_size_mb', 100) * 1024 * 1024;
        if ((int) $file->getSize() > $maxBytes) {
            throw new RuntimeException(__('dropbox::messages.errors.file_too_large'));
        }

        $parentPath = $this->resolveFolderPath($tenantId, $parentId);
        $targetPath = $this->joinPath($parentPath, (string) $file->getClientOriginalName());
        $accessToken = $this->ensureAccessToken($tenantId);

        $response = $this->sendContentRequest(
            $this->contentClient($accessToken),
            'files/upload',
            [
                'path' => $targetPath,
                'mode' => 'add',
                'autorename' => true,
                'mute' => false,
                'strict_conflict' => false,
            ],
            (string) file_get_contents($file->getRealPath()),
            'application/octet-stream'
        );

        $metadata = $this->handleResponse($response, 'files/upload');
        $this->syncMetadataToLocal($metadata, $tenantId);
        $this->log($tenantId, $metadata['id'] ?? null, $metadata['name'] ?? $file->getClientOriginalName(), 'upload', [
            'size' => (int) $file->getSize(),
        ]);

        return $this->formatMetadata($metadata);
    }

    public function rename(int $tenantId, string $fileId, string $newName): array
    {
        $sourcePath = $this->resolveFilePath($tenantId, $fileId);
        $targetPath = $this->joinPath($this->parentPath($sourcePath), $newName);
        $data = $this->moveInternal($tenantId, $sourcePath, $targetPath);
        $this->log($tenantId, $fileId, $newName, 'rename', ['from' => $sourcePath, 'to' => $targetPath]);

        return $data;
    }

    public function move(int $tenantId, string $fileId, string $targetFolderId, string $currentFolderId): array
    {
        $sourcePath = $this->resolveFilePath($tenantId, $fileId);
        $targetFolderPath = $this->resolveFolderPath($tenantId, $targetFolderId);
        $targetPath = $this->joinPath($targetFolderPath, basename($sourcePath));
        $data = $this->moveInternal($tenantId, $sourcePath, $targetPath);
        $this->log($tenantId, $fileId, $data['name'] ?? basename($targetPath), 'move', [
            'from' => $sourcePath,
            'to' => $targetPath,
            'requested_current_folder' => $currentFolderId,
        ]);

        return $data;
    }

    public function copy(int $tenantId, string $fileId, string $newName, ?string $targetFolderId = null): array
    {
        $sourcePath = $this->resolveFilePath($tenantId, $fileId);
        $targetFolderPath = $this->resolveFolderPath($tenantId, $targetFolderId);
        $targetPath = $this->joinPath($targetFolderPath, $newName !== '' ? $newName : ('Copie - ' . basename($sourcePath)));
        $data = $this->api($tenantId, 'files/copy_v2', [
            'from_path' => $sourcePath,
            'to_path' => $targetPath,
            'autorename' => true,
            'allow_shared_folder' => true,
        ]);

        $metadata = (array) ($data['metadata'] ?? []);
        $this->syncMetadataToLocal($metadata, $tenantId);
        $this->log($tenantId, $metadata['id'] ?? null, $metadata['name'] ?? basename($targetPath), 'copy');

        return $this->formatMetadata($metadata);
    }

    public function delete(int $tenantId, string $fileId, bool $permanent = false): bool
    {
        $record = DropboxFile::withTrashed()->forTenant($tenantId)->where('dropbox_id', $fileId)->first();
        $path = $this->resolveFilePath($tenantId, $fileId);
        $name = (string) ($record?->name ?: basename($path));

        if ($permanent) {
            $this->api($tenantId, 'files/permanently_delete', ['path' => $path]);
            if ($record) {
                $record->forceDelete();
            }
            $this->log($tenantId, $fileId, $name, 'delete_permanent');

            return true;
        }

        $this->api($tenantId, 'files/delete_v2', ['path' => $path]);
        if ($record && !$record->trashed()) {
            $record->delete();
        }
        $this->log($tenantId, $fileId, $name, 'delete_trash');

        return true;
    }

    public function restore(int $tenantId, string $fileId): array
    {
        $record = DropboxFile::withTrashed()->forTenant($tenantId)->where('dropbox_id', $fileId)->first();
        if (!$record || !$record->trashed()) {
            throw new RuntimeException(__('dropbox::messages.errors.trash_file_not_found'));
        }

        if (!$record->path_lower || !$record->rev) {
            throw new RuntimeException(__('dropbox::messages.errors.trash_revision_missing'));
        }

        $data = $this->api($tenantId, 'files/restore', [
            'path' => $record->path_lower,
            'rev' => $record->rev,
        ]);

        $metadata = (array) ($data['metadata'] ?? $data);
        $this->syncMetadataToLocal($metadata, $tenantId, true);
        $this->log($tenantId, $fileId, $metadata['name'] ?? $record->name, 'restore');

        return $this->formatMetadata($metadata);
    }

    public function getDownloadStream(int $tenantId, string $fileId): string
    {
        $accessToken = $this->ensureAccessToken($tenantId);
        $path = $this->resolveFilePath($tenantId, $fileId);

        $response = $this->sendContentRequest(
            $this->contentClient($accessToken),
            'files/download',
            ['path' => $path]
        );

        if ($response->successful()) {
            $this->log($tenantId, $fileId, basename($path), 'download');

            return (string) $response->body();
        }

        $exportResponse = $this->sendContentRequest(
            $this->contentClient($accessToken),
            'files/export',
            ['path' => $path]
        );

        if (!$exportResponse->successful()) {
            throw new RuntimeException($this->extractErrorMessage($response, __('dropbox::messages.errors.download_failed')));
        }

        $this->log($tenantId, $fileId, basename($path), 'download_export');

        return (string) $exportResponse->body();
    }

    public function share(int $tenantId, string $fileId, string $type = 'anyone', string $role = 'reader', ?string $email = null): array
    {
        $path = $this->resolveFilePath($tenantId, $fileId);

        try {
            $data = $this->api($tenantId, 'sharing/create_shared_link_with_settings', [
                'path' => $path,
                'settings' => [
                    'requested_visibility' => 'public',
                ],
            ]);
        } catch (\Throwable $e) {
            $links = $this->api($tenantId, 'sharing/list_shared_links', [
                'path' => $path,
                'direct_only' => true,
            ]);
            $existing = (array) (collect((array) ($links['links'] ?? []))->first() ?: []);
            if (empty($existing['url'])) {
                throw $e;
            }
            $data = $existing;
        }

        $sharedUrl = (string) ($data['url'] ?? '');
        $record = DropboxFile::withTrashed()->forTenant($tenantId)->where('dropbox_id', $fileId)->first();
        if ($record) {
            $record->forceFill([
                'shared_link' => $sharedUrl,
                'web_view_link' => $sharedUrl,
                'is_shared' => $sharedUrl !== '',
            ])->save();
        }

        $meta = $this->getFile($tenantId, $fileId);
        $meta['web_view_link'] = $sharedUrl !== '' ? $sharedUrl : ($meta['web_view_link'] ?? null);
        $meta['download_link'] = $sharedUrl !== '' ? $sharedUrl : ($meta['download_link'] ?? null);
        $meta['shared'] = $sharedUrl !== '';

        $this->log($tenantId, $fileId, $meta['name'] ?? basename($path), 'share', [
            'type' => $type,
            'role' => $role,
            'email' => $email,
            'shared_url' => $sharedUrl,
        ]);

        return $meta;
    }

    public function getFile(int $tenantId, string $fileId): array
    {
        $metadata = $this->metadataById($tenantId, $fileId);

        return $this->formatMetadata($metadata);
    }

    public function listTrash(int $tenantId): array
    {
        return DropboxFile::onlyTrashed()
            ->forTenant($tenantId)
            ->orderByDesc('deleted_at')
            ->get()
            ->map(function (DropboxFile $file) {
                return [
                    'id' => $file->dropbox_id,
                    'name' => $file->name,
                    'mime_type' => $file->mime_type,
                    'is_folder' => (bool) $file->is_folder,
                    'size_formatted' => $file->size_formatted,
                    'modified_at' => optional($file->server_modified_at ?: $file->updated_at)?->toIso8601String(),
                    'path_display' => $file->path_display,
                ];
            })
            ->values()
            ->all();
    }

    public function emptyTrash(int $tenantId): bool
    {
        $items = DropboxFile::onlyTrashed()->forTenant($tenantId)->get();

        foreach ($items as $item) {
            try {
                if ($item->path_lower) {
                    $this->api($tenantId, 'files/permanently_delete', ['path' => $item->path_lower]);
                }
            } catch (\Throwable $e) {
                Log::debug('[Dropbox] suppression definitive ignoree', [
                    'file_id' => $item->dropbox_id,
                    'message' => $e->getMessage(),
                ]);
            }

            $item->forceDelete();
        }

        $this->log($tenantId, null, null, 'empty_trash');

        return true;
    }

    public function search(int $tenantId, string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        try {
            $token = $this->getValidToken($tenantId);
            $data = $this->api($tenantId, 'files/search_v2', [
                'query' => $query,
                'options' => [
                    'path' => $token->dropbox_root_path ?: '',
                    'filename_only' => false,
                    'max_results' => 50,
                ],
            ]);

            return collect((array) ($data['matches'] ?? []))
                ->map(function (array $match) use ($tenantId) {
                    $metadata = (array) (data_get($match, 'metadata.metadata') ?? data_get($match, 'metadata') ?? []);
                    if (($metadata['.tag'] ?? '') === 'deleted' || empty($metadata)) {
                        return null;
                    }

                    $this->syncMetadataToLocal($metadata, $tenantId);

                    return $this->formatMetadata($metadata);
                })
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return DropboxFile::forTenant($tenantId)
                ->search($query)
                ->limit(50)
                ->get()
                ->map(function (DropboxFile $file) {
                    $icon = $this->iconFor($file->mime_type, (bool) $file->is_folder);

                    return [
                        'id' => $file->dropbox_id,
                        'name' => $file->name,
                        'mime_type' => $file->mime_type,
                        'is_folder' => (bool) $file->is_folder,
                        'is_trashed' => false,
                        'size_bytes' => (int) $file->size_bytes,
                        'size_formatted' => $file->size_formatted,
                        'web_view_link' => $file->web_view_link,
                        'download_link' => $file->download_link,
                        'thumbnail' => $file->thumbnail_link,
                        'icon_link' => null,
                        'icon' => $icon['icon'],
                        'color' => $icon['color'],
                        'shared' => (bool) $file->is_shared,
                        'created_at' => optional($file->created_at)?->toIso8601String(),
                        'modified_at' => optional($file->server_modified_at ?: $file->updated_at)?->toIso8601String(),
                        'parents' => $file->parent_path_lower ? [$file->parent_path_lower] : [],
                        'extension' => strtolower(pathinfo($file->name, PATHINFO_EXTENSION)),
                    ];
                })
                ->values()
                ->all();
        }
    }

    public function getStorageStats(int $tenantId): array
    {
        $token = $this->getValidToken($tenantId);
        $space = $this->api($tenantId, 'users/get_space_usage');
        $totalBytes = $this->quotaTotalBytes($space);
        $usedBytes = (int) ($space['used'] ?? 0);
        $freeBytes = max(0, $totalBytes - $usedBytes);

        $token->update([
            'space_quota_total_gb' => round($totalBytes / 1073741824, 2),
            'space_quota_used_gb' => round($usedBytes / 1073741824, 2),
            'last_sync_at' => now(),
        ]);

        return [
            'total_bytes' => $totalBytes,
            'used_bytes' => $usedBytes,
            'free_bytes' => $freeBytes,
            'total_gb' => round($totalBytes / 1073741824, 2),
            'used_gb' => round($usedBytes / 1073741824, 2),
            'free_gb' => round($freeBytes / 1073741824, 2),
            'percent_used' => $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 1) : 0,
            'dropbox_email' => $token->dropbox_email,
        ];
    }

    public function getOpenUrl(int $tenantId, string $fileId): string
    {
        $metadata = $this->metadataById($tenantId, $fileId);
        if (($metadata['.tag'] ?? '') === 'folder') {
            return (string) $this->folderUrl((string) ($metadata['path_display'] ?? ''));
        }

        $path = $this->resolveFilePath($tenantId, $fileId);
        $data = $this->api($tenantId, 'files/get_temporary_link', [
            'path' => $path,
        ]);

        return (string) ($data['link'] ?? $this->folderUrl((string) ($metadata['path_display'] ?? $path)));
    }

    private function moveInternal(int $tenantId, string $sourcePath, string $targetPath): array
    {
        $data = $this->api($tenantId, 'files/move_v2', [
            'from_path' => $sourcePath,
            'to_path' => $targetPath,
            'autorename' => false,
            'allow_shared_folder' => true,
            'allow_ownership_transfer' => false,
        ]);

        $metadata = (array) ($data['metadata'] ?? []);
        $this->syncMetadataToLocal($metadata, $tenantId);

        return $this->formatMetadata($metadata);
    }

    private function getValidToken(int $tenantId): DropboxToken
    {
        $token = $this->getToken($tenantId);
        if (!$token) {
            throw new RuntimeException(__('dropbox::messages.errors.not_connected'));
        }

        return $token;
    }

    private function ensureAccessToken(int $tenantId, bool $forceRefresh = false): string
    {
        $token = $this->getValidToken($tenantId);

        if (!$forceRefresh && !$token->is_expired && !empty($token->access_token)) {
            return (string) $token->access_token;
        }

        if (empty($token->refresh_token)) {
            $this->invalidateTokenAfterOAuthFailure($token, 'missing_refresh_token');
            throw new RuntimeException(__('dropbox::messages.errors.refresh_token_missing'));
        }

        $response = $this->oauthClient()->post((string) config('dropbox.api.token_url'), [
            'grant_type' => 'refresh_token',
            'refresh_token' => (string) $token->refresh_token,
            'client_id' => (string) config('dropbox.oauth.client_id'),
            'client_secret' => (string) config('dropbox.oauth.client_secret'),
        ]);

        try {
            $data = $this->parseOauthResponse($response);
        } catch (RuntimeException $e) {
            $message = mb_strtolower($e->getMessage());
            if (str_contains($message, 'invalid_grant') || str_contains($message, 'refresh token')) {
                $this->invalidateTokenAfterOAuthFailure($token, 'invalid_grant');
                throw new RuntimeException(__('dropbox::messages.errors.session_expired'));
            }

            throw $e;
        }

        $newAccessToken = (string) ($data['access_token'] ?? '');
        if ($newAccessToken === '') {
            throw new RuntimeException(__('dropbox::messages.errors.refresh_failed'));
        }

        $token->update([
            'access_token' => $newAccessToken,
            'token_expires_at' => isset($data['expires_in']) ? now()->addSeconds((int) $data['expires_in']) : now()->addHour(),
            'last_sync_at' => now(),
        ]);

        return $newAccessToken;
    }

    private function invalidateTokenAfterOAuthFailure(DropboxToken $token, string $reason): void
    {
        try {
            $token->update([
                'is_active' => false,
                'disconnected_at' => now(),
                'access_token' => '',
                'refresh_token' => null,
            ]);

            $this->log((int) $token->tenant_id, null, null, 'oauth_invalidated', ['reason' => $reason]);
        } catch (\Throwable $e) {
            Log::warning('[Dropbox] echec de l invalidation du token', ['message' => $e->getMessage()]);
        }
    }

    private function api(int $tenantId, string $endpoint, array $payload = []): array
    {
        $accessToken = $this->ensureAccessToken($tenantId);
        $response = $this->sendJsonRequest($this->jsonClient($accessToken), $endpoint, $payload);

        if ($response->status() === 401) {
            $accessToken = $this->ensureAccessToken($tenantId, true);
            $response = $this->sendJsonRequest($this->jsonClient($accessToken), $endpoint, $payload);
        }

        return $this->handleResponse($response, $endpoint);
    }

    private function apiWithAccessToken(string $accessToken, string $endpoint, array $payload = []): array
    {
        $response = $this->sendJsonRequest($this->jsonClient($accessToken), $endpoint, $payload);

        return $this->handleResponse($response, $endpoint);
    }

    private function sendJsonRequest(PendingRequest $client, string $endpoint, array $payload = []): Response
    {
        try {
            if ($payload === []) {
                return $client
                    ->withBody('null', 'application/json')
                    ->post($endpoint);
            }

            return $client->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException($this->networkErrorMessage($endpoint, $e), 0, $e);
        }
    }

    private function jsonClient(string $accessToken): PendingRequest
    {
        return Http::baseUrl((string) config('dropbox.api.base_url'))
            ->withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('dropbox.api.connect_timeout', 30))
            ->timeout((int) config('dropbox.api.timeout', 45))
            ->retry((int) config('dropbox.api.retry_attempts', 3), 400, null, false);
    }

    private function contentClient(string $accessToken): PendingRequest
    {
        return Http::baseUrl((string) config('dropbox.api.content_url'))
            ->withToken($accessToken)
            ->connectTimeout((int) config('dropbox.api.connect_timeout', 30))
            ->timeout((int) config('dropbox.api.content_timeout', 180))
            ->retry((int) config('dropbox.api.retry_attempts', 3), 600, null, false);
    }

    private function oauthClient(): PendingRequest
    {
        return Http::asForm()
            ->acceptJson()
            ->connectTimeout((int) config('dropbox.api.connect_timeout', 30))
            ->timeout((int) config('dropbox.api.timeout', 45))
            ->retry((int) config('dropbox.api.retry_attempts', 3), 400, null, false);
    }

    private function sendContentRequest(
        PendingRequest $client,
        string $endpoint,
        array $apiArg,
        string $body = '',
        ?string $contentType = null
    ): Response {
        $headers = [
            'Dropbox-API-Arg' => json_encode($apiArg, JSON_UNESCAPED_SLASHES),
        ];

        if ($contentType !== null && $contentType !== '') {
            $headers['Content-Type'] = $contentType;
        }

        try {
            return $client
                ->withHeaders($headers)
                ->send('POST', $endpoint, ['body' => $body]);
        } catch (ConnectionException $e) {
            throw new RuntimeException($this->networkErrorMessage($endpoint, $e), 0, $e);
        }
    }

    private function handleResponse(Response $response, string $endpoint): array
    {
        if (!$response->successful()) {
            throw new RuntimeException($this->extractErrorMessage($response, 'Erreur Dropbox sur ' . $endpoint));
        }

        return (array) $response->json();
    }

    private function parseOauthResponse(Response $response): array
    {
        if (!$response->successful()) {
            throw new RuntimeException($this->extractErrorMessage($response, __('dropbox::messages.errors.auth_finalize_failed')));
        }

        return (array) $response->json();
    }

    private function extractErrorMessage(Response $response, string $fallback): string
    {
        $json = $response->json();
        if (is_array($json)) {
            $summary = (string) ($json['error_summary'] ?? $json['error_description'] ?? $json['error'] ?? '');
            if ($summary !== '') {
                if (str_contains($summary, 'required scope')) {
                    preg_match("/required scope '([^']+)'/", $summary, $matches);
                    $scope = $matches[1] ?? 'inconnu';

                    return "Votre application Dropbox n'a pas encore le scope requis '{$scope}'. "
                        . "Activez ce scope dans Dropbox App Console > Permissions, puis reconnectez Dropbox dans le CRM.";
                }
                return $summary;
            }
        }

        $body = trim((string) $response->body());

        return $body !== '' ? $body : $fallback;
    }

    private function networkErrorMessage(string $endpoint, ConnectionException $e): string
    {
        $message = mb_strtolower($e->getMessage());

        if (str_contains($message, 'curl error 28') || str_contains($message, 'timeout was reached')) {
            return str_contains($endpoint, 'files/upload')
                ? 'Le transfert vers Dropbox a pris trop de temps. Verifiez votre connexion Internet puis relancez la sauvegarde.'
                : 'Dropbox ne repond pas dans les temps. Verifiez votre connexion Internet puis reessayez.';
        }

        if (str_contains($message, 'failed to connect') || str_contains($message, 'could not resolve host')) {
            return 'Impossible de joindre Dropbox pour le moment. Verifiez votre connexion Internet puis reessayez.';
        }

        return 'Une erreur reseau a empeche la communication avec Dropbox. Reessayez dans quelques instants.';
    }

    private function ensureRootFolder(int $tenantId, string $accessToken): array
    {
        $appFolder = '/' . trim(str_replace('/', '-', (string) config('app.name', 'NexusCRM')));
        $tenantFolder = $this->joinPath($appFolder, 'Tenant-' . $tenantId);

        $this->ensureFolderPathExists($accessToken, $appFolder);

        return $this->ensureFolderPathExists($accessToken, $tenantFolder);
    }

    private function ensureFolderPathExists(string $accessToken, string $path): array
    {
        $metadataResponse = $this->jsonClient($accessToken)->post('files/get_metadata', [
            'path' => $path,
            'include_media_info' => false,
        ]);

        if ($metadataResponse->successful()) {
            return (array) $metadataResponse->json();
        }

        $createResponse = $this->jsonClient($accessToken)->post('files/create_folder_v2', [
            'path' => $path,
            'autorename' => false,
        ]);
        $created = $this->handleResponse($createResponse, 'files/create_folder_v2');

        return (array) ($created['metadata'] ?? []);
    }

    private function metadataById(int $tenantId, string $fileId): array
    {
        $record = DropboxFile::withTrashed()->forTenant($tenantId)->where('dropbox_id', $fileId)->first();
        if ($record && $record->path_lower && !$record->trashed()) {
            return [
                '.tag' => $record->is_folder ? 'folder' : 'file',
                'id' => $record->dropbox_id,
                'name' => $record->name,
                'path_lower' => $record->path_lower,
                'path_display' => $record->path_display,
                'rev' => $record->rev,
                'size' => $record->size_bytes,
                'server_modified' => optional($record->server_modified_at)?->toIso8601String(),
                'client_modified' => optional($record->client_modified_at)?->toIso8601String(),
            ];
        }

        $metadata = $this->api($tenantId, 'files/get_metadata', [
            'path' => 'id:' . $fileId,
            'include_media_info' => false,
        ]);
        $this->syncMetadataToLocal($metadata, $tenantId);

        return $metadata;
    }

    private function resolveFilePath(int $tenantId, string $fileId): string
    {
        $record = DropboxFile::withTrashed()->forTenant($tenantId)->where('dropbox_id', $fileId)->first();
        if ($record && $record->path_lower) {
            return (string) $record->path_lower;
        }

        $metadata = $this->metadataById($tenantId, $fileId);
        $path = (string) ($metadata['path_lower'] ?? '');
        if ($path === '') {
            throw new RuntimeException(__('dropbox::messages.errors.resolve_path_failed'));
        }

        return $path;
    }

    private function resolveFolderPath(int $tenantId, ?string $reference): string
    {
        $token = $this->getValidToken($tenantId);
        $reference = trim((string) $reference);

        if ($reference === '') {
            return (string) ($token->dropbox_root_path ?: '');
        }

        if (Str::startsWith($reference, '/')) {
            return $reference;
        }

        $record = DropboxFile::withTrashed()->forTenant($tenantId)->where('dropbox_id', $reference)->first();
        if ($record && $record->path_lower) {
            return (string) $record->path_lower;
        }

        if (Str::startsWith($reference, 'id:')) {
            $metadata = $this->api($tenantId, 'files/get_metadata', [
                'path' => $reference,
                'include_media_info' => false,
            ]);
            $path = (string) ($metadata['path_lower'] ?? '');
            if ($path !== '') {
                $this->syncMetadataToLocal($metadata, $tenantId);

                return $path;
            }
        }

        return $reference;
    }

    private function syncMetadataToLocal(array $metadata, int $tenantId, bool $restore = false): void
    {
        if (($metadata['.tag'] ?? '') === 'deleted' || empty($metadata['id'])) {
            return;
        }

        $pathLower = (string) ($metadata['path_lower'] ?? '');
        $file = DropboxFile::withTrashed()->forTenant($tenantId)->where('dropbox_id', (string) $metadata['id'])->first();
        if (!$file && $pathLower !== '') {
            $file = DropboxFile::withTrashed()->forTenant($tenantId)->where('path_lower', $pathLower)->first();
        }

        $attributes = [
            'tenant_id' => $tenantId,
            'dropbox_id' => (string) $metadata['id'],
            'parent_path_lower' => $this->parentPath($pathLower),
            'path_lower' => $pathLower ?: null,
            'path_display' => (string) ($metadata['path_display'] ?? ''),
            'rev' => (string) ($metadata['rev'] ?? ''),
            'name' => (string) ($metadata['name'] ?? basename($pathLower)),
            'mime_type' => $this->guessMimeType((string) ($metadata['name'] ?? ''), ($metadata['.tag'] ?? '') === 'folder'),
            'is_folder' => ($metadata['.tag'] ?? '') === 'folder',
            'size_bytes' => (int) ($metadata['size'] ?? 0),
            'web_view_link' => $this->folderUrl((string) ($metadata['path_display'] ?? '')),
            'thumbnail_link' => null,
            'download_link' => null,
            'is_shared' => !empty($file?->shared_link),
            'shared_link' => $file?->shared_link,
            'created_by' => Auth::id(),
            'modified_by' => Auth::id(),
            'client_modified_at' => $this->parseTimestamp($metadata['client_modified'] ?? null),
            'server_modified_at' => $this->parseTimestamp($metadata['server_modified'] ?? null),
        ];

        if ($file) {
            $file->fill($attributes)->save();
            if ($file->trashed()) {
                $file->restore();
            }

            return;
        }

        DropboxFile::create($attributes);
    }

    private function formatMetadata(array $metadata): array
    {
        $isFolder = ($metadata['.tag'] ?? '') === 'folder';
        $mimeType = $this->guessMimeType((string) ($metadata['name'] ?? ''), $isFolder);
        $icon = $this->iconFor($mimeType, $isFolder);
        $pathLower = (string) ($metadata['path_lower'] ?? '');
        $sharedLink = DropboxFile::withTrashed()->where('dropbox_id', (string) ($metadata['id'] ?? ''))->value('shared_link');

        return [
            'id' => (string) ($metadata['id'] ?? ''),
            'name' => (string) ($metadata['name'] ?? ''),
            'mime_type' => $mimeType,
            'is_folder' => $isFolder,
            'is_trashed' => false,
            'size_bytes' => (int) ($metadata['size'] ?? 0),
            'size_formatted' => $this->formatSize((int) ($metadata['size'] ?? 0), $isFolder),
            'web_view_link' => $sharedLink ?: $this->folderUrl((string) ($metadata['path_display'] ?? '')),
            'download_link' => $sharedLink,
            'thumbnail' => null,
            'icon_link' => null,
            'icon' => $icon['icon'],
            'color' => $icon['color'],
            'shared' => !empty($sharedLink),
            'created_at' => $metadata['client_modified'] ?? null,
            'modified_at' => $metadata['server_modified'] ?? null,
            'parents' => $this->parentPath($pathLower) ? [$this->parentPath($pathLower)] : [],
            'extension' => strtolower(pathinfo((string) ($metadata['name'] ?? ''), PATHINFO_EXTENSION)),
            'path_display' => (string) ($metadata['path_display'] ?? ''),
            'path_lower' => $pathLower,
        ];
    }

    private function guessMimeType(string $fileName, bool $isFolder): string
    {
        if ($isFolder) {
            return 'folder';
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeTypes = MimeTypes::getDefault()->getMimeTypes($extension);

        return (string) ($mimeTypes[0] ?? 'application/octet-stream');
    }

    private function iconFor(?string $mimeType, bool $isFolder): array
    {
        $icons = (array) config('dropbox.mime_icons', []);
        if ($isFolder) {
            return $icons['folder'] ?? ['icon' => 'fa-folder', 'color' => '#0061ff'];
        }

        return $icons[(string) $mimeType] ?? $icons['default'] ?? ['icon' => 'fa-file', 'color' => '#64748b'];
    }

    private function quotaTotalBytes(array $space): int
    {
        $allocation = (array) ($space['allocation'] ?? []);
        $tag = (string) ($allocation['.tag'] ?? '');

        return match ($tag) {
            'individual' => (int) ($allocation['allocated'] ?? 0),
            'team' => (int) ($allocation['allocated'] ?? 0),
            default => 0,
        };
    }

    private function quotaTotalGb(array $space): float
    {
        return round($this->quotaTotalBytes($space) / 1073741824, 2);
    }

    private function parseTimestamp(mixed $value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private function rootReference(DropboxToken $token): string
    {
        return (string) ($token->dropbox_root_id ?: $token->dropbox_root_path ?: '');
    }

    private function normalizeListPath(string $reference, DropboxToken $token): string
    {
        if ($reference === $token->dropbox_root_id) {
            return (string) ($token->dropbox_root_path ?: '');
        }

        if (Str::startsWith($reference, 'id:') || Str::startsWith($reference, '/')) {
            return $reference;
        }

        return $reference !== '' ? $reference : (string) ($token->dropbox_root_path ?: '');
    }

    private function joinPath(?string $parentPath, string $name): string
    {
        $cleanParent = trim((string) $parentPath);
        $cleanParent = $cleanParent === '/' ? '' : rtrim($cleanParent, '/');
        $cleanName = trim(str_replace('\\', '-', str_replace('/', '-', $name)));
        if ($cleanName === '') {
            throw new RuntimeException(__('dropbox::messages.errors.invalid_name'));
        }

        return ($cleanParent !== '' ? $cleanParent : '') . '/' . $cleanName;
    }

    private function parentPath(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || $path === '/') {
            return null;
        }

        $parent = dirname($path);
        if ($parent === '.' || $parent === '\\' || $parent === '/') {
            return null;
        }

        return str_replace('\\', '/', $parent);
    }

    private function folderUrl(string $pathDisplay): ?string
    {
        $pathDisplay = trim($pathDisplay);
        if ($pathDisplay === '') {
            return null;
        }

        return 'https://www.dropbox.com/home' . str_replace('%2F', '/', rawurlencode($pathDisplay));
    }

    private function formatSize(int $bytes, bool $isFolder): string
    {
        if ($isFolder || $bytes <= 0) {
            return '-';
        }
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }

    private function redirectUri(): string
    {
        $path = (string) config('dropbox.oauth.redirect_uri', '/extensions/dropbox/oauth/callback');
        if (trim($path) === '') {
            $path = '/extensions/dropbox/oauth/callback';
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url($path);
    }

    private function log(int $tenantId, ?string $fileId, ?string $fileName, string $action, array $metadata = []): void
    {
        try {
            DropboxActivityLog::create([
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'dropbox_file_id' => $fileId,
                'file_name' => $fileName,
                'action' => $action,
                'metadata' => $metadata,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('[Dropbox] ecriture du journal ignoree', ['message' => $e->getMessage()]);
        }
    }
}
