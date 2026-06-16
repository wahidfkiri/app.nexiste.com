<?php

namespace NexusExtensions\GoogleDrive\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\Oauth2;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NexusExtensions\GoogleDrive\Models\GoogleDriveActivityLog;
use NexusExtensions\GoogleDrive\Models\GoogleDriveFile;
use NexusExtensions\GoogleDrive\Models\GoogleDriveToken;
use RuntimeException;

class GoogleDriveService
{
    private ?GoogleClient $client = null;
    private ?Drive $driveService = null;

    public function makeClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId((string) config('google-drive.oauth.client_id'));
        $client->setClientSecret((string) config('google-drive.oauth.client_secret'));
        $client->setRedirectUri($this->redirectUri());
        $client->setScopes((array) config('google-drive.oauth.scopes', []));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        return $client;
    }

    public function getAuthUrl(int $tenantId, int $userId): string
    {
        $clientId = (string) config('google-drive.oauth.client_id');
        if ($clientId === '') {
            throw new RuntimeException(__('google-drive::messages.errors.client_id_missing'));
        }

        $client = $this->makeClient();
        $state = encrypt([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'nonce' => Str::uuid()->toString(),
            'ts' => now()->timestamp,
        ]);

        $client->setState($state);

        return $client->createAuthUrl();
    }

    public function parseState(string $encryptedState): array
    {
        $state = decrypt($encryptedState);
        if (!is_array($state) || !isset($state['tenant_id'], $state['user_id'])) {
            throw new RuntimeException(__('google-drive::messages.errors.invalid_oauth_state'));
        }

        return $state;
    }

    public function exchangeCode(string $code, int $tenantId, int $userId): GoogleDriveToken
    {
        $client = $this->makeClient();
        $tokenData = $client->fetchAccessTokenWithAuthCode($code);
        $existingToken = GoogleDriveToken::forTenant($tenantId)->first();

        if (isset($tokenData['error'])) {
            throw new RuntimeException((string) ($tokenData['error_description'] ?? $tokenData['error']));
        }

        $client->setAccessToken($tokenData);

        $oauth2 = new Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        $driveService = new Drive($client);
        $about = $driveService->about->get(['fields' => 'storageQuota,user']);
        $quota = $about->getStorageQuota();

        $driveToken = GoogleDriveToken::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'connected_by' => $userId,
                'access_token' => $tokenData['access_token'] ?? '',
                'refresh_token' => $tokenData['refresh_token'] ?? $existingToken?->refresh_token,
                'token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds((int) $tokenData['expires_in']) : now()->addHour(),
                'google_account_id' => $userInfo->getId(),
                'google_email' => $userInfo->getEmail(),
                'google_name' => $userInfo->getName(),
                'google_avatar_url' => $userInfo->getPicture(),
                'drive_quota_total_gb' => $quota && $quota->getLimit() ? round(((int) $quota->getLimit()) / 1073741824, 2) : null,
                'drive_quota_used_gb' => $quota ? round(((int) $quota->getUsage()) / 1073741824, 2) : null,
                'is_active' => true,
                'connected_at' => now(),
                'disconnected_at' => null,
            ]
        );

        $this->client = $client;
        $this->driveService = $driveService;
        $rootFolderId = $this->ensureRootFolder($driveToken);

        $driveToken->update(['drive_root_folder_id' => $rootFolderId]);
        $this->log($tenantId, null, null, 'connected', ['google_email' => $userInfo->getEmail()]);

        return $driveToken->fresh();
    }

    public function disconnect(int $tenantId): void
    {
        $token = GoogleDriveToken::forTenant($tenantId)->first();
        if (!$token) {
            return;
        }

        try {
            $client = $this->makeClient();
            if ($token->access_token) {
                $client->revokeToken($token->access_token);
            }
        } catch (\Throwable $e) {
            Log::warning('[GoogleDrive] token revoke failed', ['message' => $e->getMessage()]);
        }

        $token->update([
            'is_active' => false,
            'disconnected_at' => now(),
            'access_token' => '',
            'refresh_token' => null,
        ]);

        $this->log($tenantId, null, null, 'disconnected');
    }

    public function getToken(int $tenantId): ?GoogleDriveToken
    {
        return GoogleDriveToken::forTenant($tenantId)->active()->first();
    }

    public function getDriveService(int $tenantId): Drive
    {
        if ($this->driveService) {
            return $this->driveService;
        }

        $token = $this->getValidToken($tenantId);
        $client = $this->makeClient();
        $client->setAccessToken($token->toGoogleToken());

        if ($token->is_expired) {
            if (!$token->refresh_token) {
                $this->invalidateTokenAfterOAuthFailure($token, 'missing_refresh_token');
                throw new RuntimeException(__('google-drive::messages.errors.session_expired'));
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($token->refresh_token);
            if (!isset($newToken['error'])) {
                $token->update([
                    'access_token' => $newToken['access_token'] ?? $token->access_token,
                    'refresh_token' => $newToken['refresh_token'] ?? $token->refresh_token,
                    'token_expires_at' => now()->addSeconds((int) ($newToken['expires_in'] ?? 3600)),
                ]);
                $client->setAccessToken($newToken);
            } else {
                if ($this->isRevokedOrExpiredOAuthError(
                    (string) ($newToken['error'] ?? ''),
                    (string) ($newToken['error_description'] ?? '')
                )) {
                    $this->invalidateTokenAfterOAuthFailure($token, 'invalid_grant');
                    throw new RuntimeException(__('google-drive::messages.errors.session_expired'));
                }

                throw new RuntimeException((string) ($newToken['error_description'] ?? $newToken['error']));
            }
        }

        $this->client = $client;
        $this->driveService = new Drive($client);

        return $this->driveService;
    }

    public function listFiles(int $tenantId, ?string $folderId = null, int $page = 1, string $search = '', ?string $pageToken = null): array
    {
        $drive = $this->getDriveService($tenantId);
        $token = $this->getValidToken($tenantId);
        $rootFolder = $token->drive_root_folder_id;
        $parent = $folderId ?: ($rootFolder ?: 'root');

        $query = "'{$parent}' in parents and trashed = false";
        if ($search !== '') {
            $escaped = addslashes($search);
            $query = "name contains '{$escaped}' and trashed = false";
        }

        $params = [
            'q' => $query,
            'fields' => 'nextPageToken,files(id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,shared,trashed)',
            'pageSize' => (int) config('google-drive.api.page_size', 50),
            'orderBy' => 'folder,name',
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        try {
            $result = $drive->files->listFiles($params);
            $files = [];

            foreach ((array) $result->getFiles() as $file) {
                $files[] = $this->formatFile($file);
                $this->syncFileToLocal($file, $tenantId, $parent);
            }

            return [
                'files' => $files,
                'next_page_token' => $result->getNextPageToken(),
                'folder_id' => $parent,
                'root_folder' => $rootFolder,
            ];
        } catch (GoogleServiceException $e) {
            throw new RuntimeException(__('google-drive::messages.errors.list_files', ['message' => $e->getMessage()]));
        }
    }

    public function createFolder(int $tenantId, string $name, ?string $parentId = null): array
    {
        $drive = $this->getDriveService($tenantId);
        $token = $this->getValidToken($tenantId);
        $parent = $parentId ?: ($token->drive_root_folder_id ?: 'root');

        $folder = $drive->files->create(new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parent],
        ]), ['fields' => 'id,name,mimeType,createdTime,webViewLink,parents,shared']);

        $this->syncFileToLocal($folder, $tenantId, $parent);
        $this->log($tenantId, $folder->getId(), $folder->getName(), 'create_folder');

        return $this->formatFile($folder);
    }

    public function uploadFile(int $tenantId, UploadedFile $file, ?string $parentId = null): array
    {
        $drive = $this->getDriveService($tenantId);
        $token = $this->getValidToken($tenantId);
        $parent = $parentId ?: ($token->drive_root_folder_id ?: 'root');
        $mimeType = (string) $file->getMimeType();
        $name = (string) $file->getClientOriginalName();

        $allowed = (array) config('google-drive.allowed_mime_types', []);
        if (!empty($allowed) && !in_array($mimeType, $allowed, true)) {
            throw new RuntimeException(__('google-drive::messages.errors.file_type_not_allowed', ['mime' => $mimeType]));
        }

        $maxBytes = (int) config('google-drive.api.max_file_size_mb', 100) * 1024 * 1024;
        if ((int) $file->getSize() > $maxBytes) {
            throw new RuntimeException(__('google-drive::messages.errors.file_too_large'));
        }

        $uploadedFile = $drive->files->create(
            new DriveFile([
                'name' => $name,
                'parents' => [$parent],
            ]),
            [
                'data' => file_get_contents($file->getRealPath()),
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,shared',
            ]
        );

        $this->syncFileToLocal($uploadedFile, $tenantId, $parent);
        $this->log($tenantId, $uploadedFile->getId(), $uploadedFile->getName(), 'upload', ['size' => $file->getSize()]);

        return $this->formatFile($uploadedFile);
    }

    public function rename(int $tenantId, string $fileId, string $newName): array
    {
        $drive = $this->getDriveService($tenantId);
        $beforeName = $this->getFileName($tenantId, $fileId);

        $updatedFile = $drive->files->update($fileId, new DriveFile(['name' => $newName]), [
            'fields' => 'id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,shared',
        ]);

        $this->syncFileToLocal($updatedFile, $tenantId, (string) ($updatedFile->getParents()[0] ?? ''));
        $this->log($tenantId, $fileId, $newName, 'rename', ['old_name' => $beforeName, 'new_name' => $newName]);

        return $this->formatFile($updatedFile);
    }

    public function move(int $tenantId, string $fileId, string $targetFolderId, string $currentFolderId): array
    {
        $drive = $this->getDriveService($tenantId);
        $updatedFile = $drive->files->update($fileId, new DriveFile(), [
            'addParents' => $targetFolderId,
            'removeParents' => $currentFolderId,
            'fields' => 'id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,shared',
        ]);

        $this->syncFileToLocal($updatedFile, $tenantId, $targetFolderId);
        $this->log($tenantId, $fileId, $updatedFile->getName(), 'move', ['from' => $currentFolderId, 'to' => $targetFolderId]);

        return $this->formatFile($updatedFile);
    }

    public function copy(int $tenantId, string $fileId, string $newName, ?string $targetFolderId = null): array
    {
        $drive = $this->getDriveService($tenantId);
        $token = $this->getValidToken($tenantId);
        $parent = $targetFolderId ?: ($token->drive_root_folder_id ?: 'root');
        $name = $newName !== '' ? $newName : ('Copie de ' . $this->getFileName($tenantId, $fileId));

        $copiedFile = $drive->files->copy($fileId, new DriveFile([
            'name' => $name,
            'parents' => [$parent],
        ]), [
            'fields' => 'id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,shared',
        ]);

        $this->syncFileToLocal($copiedFile, $tenantId, $parent);
        $this->log($tenantId, $copiedFile->getId(), $copiedFile->getName(), 'copy');

        return $this->formatFile($copiedFile);
    }

    public function delete(int $tenantId, string $fileId, bool $permanent = false): bool
    {
        $drive = $this->getDriveService($tenantId);
        $name = $this->getFileName($tenantId, $fileId);

        if ($permanent) {
            $drive->files->delete($fileId);
        } else {
            $drive->files->update($fileId, new DriveFile(['trashed' => true]));
        }

        GoogleDriveFile::forTenant($tenantId)->where('drive_id', $fileId)->delete();
        $this->log($tenantId, $fileId, $name, $permanent ? 'delete_permanent' : 'delete_trash');

        return true;
    }

    public function restore(int $tenantId, string $fileId): array
    {
        $drive = $this->getDriveService($tenantId);
        $restored = $drive->files->update($fileId, new DriveFile(['trashed' => false]), [
            'fields' => 'id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,shared',
        ]);

        $this->syncFileToLocal($restored, $tenantId, (string) ($restored->getParents()[0] ?? ''));
        $this->log($tenantId, $fileId, $restored->getName(), 'restore');

        return $this->formatFile($restored);
    }

    public function getDownloadStream(int $tenantId, string $fileId): string
    {
        $drive = $this->getDriveService($tenantId);
        $file = $drive->files->get($fileId, ['fields' => 'id,name,mimeType']);
        $mime = (string) $file->getMimeType();

        $nativeMap = [
            'application/vnd.google-apps.document' => 'application/pdf',
            'application/vnd.google-apps.spreadsheet' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.google-apps.presentation' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        if (isset($nativeMap[$mime])) {
            $response = $drive->files->export($fileId, $nativeMap[$mime], ['alt' => 'media']);
        } else {
            $response = $drive->files->get($fileId, ['alt' => 'media']);
        }

        $this->log($tenantId, $fileId, $file->getName(), 'download');

        return (string) $response->getBody()->getContents();
    }

    public function share(int $tenantId, string $fileId, string $type = 'anyone', string $role = 'reader', ?string $email = null): array
    {
        $drive = $this->getDriveService($tenantId);

        $permissionData = ['type' => $type, 'role' => $role];
        if ($email) {
            $permissionData['emailAddress'] = $email;
        }

        $drive->permissions->create($fileId, new Permission($permissionData));
        $file = $drive->files->get($fileId, ['fields' => 'id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,shared']);
        $this->syncFileToLocal($file, $tenantId, (string) ($file->getParents()[0] ?? ''));
        $this->log($tenantId, $fileId, $file->getName(), 'share', ['type' => $type, 'role' => $role, 'email' => $email]);

        return $this->formatFile($file);
    }

    public function getFile(int $tenantId, string $fileId): array
    {
        $drive = $this->getDriveService($tenantId);
        $file = $drive->files->get($fileId, [
            'fields' => 'id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,shared,description',
        ]);

        return $this->formatFile($file);
    }

    public function listTrash(int $tenantId): array
    {
        $drive = $this->getDriveService($tenantId);
        $result = $drive->files->listFiles([
            'q' => 'trashed = true',
            'fields' => 'files(id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,shared,trashed)',
            'pageSize' => 100,
            'orderBy' => 'modifiedTime desc',
        ]);

        return collect((array) $result->getFiles())->map(fn ($file) => $this->formatFile($file))->values()->all();
    }

    public function emptyTrash(int $tenantId): bool
    {
        $drive = $this->getDriveService($tenantId);
        $drive->files->emptyTrash();
        $this->log($tenantId, null, null, 'empty_trash');

        return true;
    }

    public function search(int $tenantId, string $query): array
    {
        $drive = $this->getDriveService($tenantId);
        $escaped = addslashes($query);
        $result = $drive->files->listFiles([
            'q' => "name contains '{$escaped}' and trashed = false",
            'fields' => 'files(id,name,mimeType,size,webViewLink,webContentLink,thumbnailLink,iconLink,createdTime,modifiedTime,parents,shared)',
            'pageSize' => 50,
            'orderBy' => 'modifiedTime desc',
        ]);

        return collect((array) $result->getFiles())->map(fn ($file) => $this->formatFile($file))->values()->all();
    }

    public function getStorageStats(int $tenantId): array
    {
        $drive = $this->getDriveService($tenantId);
        $about = $drive->about->get(['fields' => 'storageQuota,user']);
        $quota = $about->getStorageQuota();

        $totalBytes = (int) ($quota?->getLimit() ?? 0);
        $usedBytes = (int) ($quota?->getUsage() ?? 0);
        $freeBytes = max(0, $totalBytes - $usedBytes);

        GoogleDriveToken::forTenant($tenantId)->update([
            'drive_quota_total_gb' => round($totalBytes / 1073741824, 2),
            'drive_quota_used_gb' => round($usedBytes / 1073741824, 2),
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
            'google_email' => $about->getUser()?->getEmailAddress(),
        ];
    }

    private function getValidToken(int $tenantId): GoogleDriveToken
    {
        $token = $this->getToken($tenantId);
        if (!$token) {
            throw new RuntimeException(__('google-drive::messages.errors.not_connected'));
        }

        return $token;
    }

    private function formatFile(DriveFile $file): array
    {
        $mimeType = (string) $file->getMimeType();
        $isFolder = $mimeType === 'application/vnd.google-apps.folder';
        $icons = (array) config('google-drive.mime_icons', []);
        $iconCfg = $isFolder
            ? ($icons['application/vnd.google-apps.folder'] ?? ['icon' => 'fa-folder', 'color' => '#f59e0b'])
            : ($icons[$mimeType] ?? $icons['default'] ?? ['icon' => 'fa-file', 'color' => '#64748b']);

        return [
            'id' => $file->getId(),
            'name' => $file->getName(),
            'mime_type' => $mimeType,
            'is_folder' => $isFolder,
            'is_trashed' => (bool) $file->getTrashed(),
            'size_bytes' => (int) ($file->getSize() ?? 0),
            'size_formatted' => $this->formatSize((int) ($file->getSize() ?? 0)),
            'web_view_link' => $file->getWebViewLink(),
            'download_link' => $file->getWebContentLink(),
            'thumbnail' => $file->getThumbnailLink(),
            'icon_link' => $file->getIconLink(),
            'icon' => $iconCfg['icon'],
            'color' => $iconCfg['color'],
            'shared' => (bool) $file->getShared(),
            'created_at' => $file->getCreatedTime(),
            'modified_at' => $file->getModifiedTime(),
            'parents' => $file->getParents() ?? [],
            'extension' => strtolower(pathinfo((string) $file->getName(), PATHINFO_EXTENSION)),
        ];
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes <= 0) {
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

    private function syncFileToLocal(DriveFile $file, int $tenantId, string $parentId): void
    {
        try {
            GoogleDriveFile::updateOrCreate(
                ['tenant_id' => $tenantId, 'drive_id' => $file->getId()],
                [
                    'parent_drive_id' => $parentId ?: null,
                    'name' => $file->getName(),
                    'mime_type' => $file->getMimeType(),
                    'is_folder' => $file->getMimeType() === 'application/vnd.google-apps.folder',
                    'size_bytes' => (int) ($file->getSize() ?? 0),
                    'web_view_link' => $file->getWebViewLink(),
                    'web_content_link' => $file->getWebContentLink(),
                    'thumbnail_link' => $file->getThumbnailLink(),
                    'icon_link' => $file->getIconLink(),
                    'is_shared' => (bool) $file->getShared(),
                    'drive_created_at' => $file->getCreatedTime(),
                    'drive_modified_at' => $file->getModifiedTime(),
                    'modified_by' => Auth::id(),
                    'created_by' => Auth::id(),
                ]
            );
        } catch (\Throwable $e) {
            Log::debug('[GoogleDrive] local sync skipped', ['message' => $e->getMessage()]);
        }
    }

    private function ensureRootFolder(GoogleDriveToken $token): string
    {
        if ($token->drive_root_folder_id) {
            return $token->drive_root_folder_id;
        }

        $folder = $this->driveService->files->create(new DriveFile([
            'name' => config('app.name', 'NexusCRM') . ' - Documents',
            'mimeType' => 'application/vnd.google-apps.folder',
        ]), ['fields' => 'id']);

        return (string) $folder->getId();
    }

    private function getFileName(int $tenantId, string $fileId): string
    {
        return (string) (GoogleDriveFile::forTenant($tenantId)->where('drive_id', $fileId)->value('name') ?: $fileId);
    }

    private function redirectUri(): string
    {
        $path = (string) config('google-drive.oauth.redirect_uri', '/extensions/google-drive/oauth/callback');
        if (trim($path) === '') {
            $path = '/extensions/google-drive/oauth/callback';
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url($path);
    }

    private function isRevokedOrExpiredOAuthError(string $error, string $description = ''): bool
    {
        $full = Str::lower(trim($error . ' ' . $description));

        return str_contains($full, 'invalid_grant')
            || str_contains($full, 'expired or revoked')
            || str_contains($full, 'token has been expired or revoked');
    }

    private function invalidateTokenAfterOAuthFailure(GoogleDriveToken $token, string $reason): void
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
            Log::warning('[GoogleDrive] invalidate token failed', ['message' => $e->getMessage()]);
        }
    }

    private function log(int $tenantId, ?string $fileId, ?string $fileName, string $action, array $metadata = []): void
    {
        try {
            GoogleDriveActivityLog::create([
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'drive_file_id' => $fileId,
                'file_name' => $fileName,
                'action' => $action,
                'metadata' => $metadata,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('[GoogleDrive] activity log skipped', ['message' => $e->getMessage()]);
        }
    }
}
