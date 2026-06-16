<?php

namespace NexusExtensions\GoogleDocx\Services;

use Google\Client as GoogleClient;
use Google\Service\Docs;
use Google\Service\Docs\BatchUpdateDocumentRequest;
use Google\Service\Docs\InsertTextRequest;
use Google\Service\Docs\Request as DocsRequest;
use Google\Service\Docs\ReplaceAllTextRequest;
use Google\Service\Docs\SubstringMatchCriteria;
use Google\Service\Drive;
use Google\Service\Oauth2;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NexusExtensions\GoogleDocx\Models\GoogleDocxActivityLog;
use NexusExtensions\GoogleDocx\Models\GoogleDocxDocument;
use NexusExtensions\GoogleDocx\Models\GoogleDocxToken;
use RuntimeException;

class GoogleDocxService
{
    private ?GoogleClient $client = null;
    private ?Docs $docsService = null;
    private ?Drive $driveService = null;

    public function makeClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId((string) config('google-docx.oauth.client_id'));
        $client->setClientSecret((string) config('google-docx.oauth.client_secret'));
        $client->setRedirectUri($this->redirectUri());
        $client->setScopes((array) config('google-docx.oauth.scopes', []));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        return $client;
    }

    public function getAuthUrl(int $tenantId, int $userId): string
    {
        $clientId = (string) config('google-docx.oauth.client_id');
        if ($clientId === '') {
            throw new RuntimeException(__('google-docx::messages.errors.client_id_missing'));
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
            throw new RuntimeException(__('google-docx::messages.errors.invalid_oauth_state'));
        }

        return $state;
    }

    public function exchangeCode(string $code, int $tenantId, int $userId): GoogleDocxToken
    {
        $client = $this->makeClient();
        $tokenData = $client->fetchAccessTokenWithAuthCode($code);
        $existingToken = GoogleDocxToken::forTenant($tenantId)->first();

        if (isset($tokenData['error'])) {
            throw new RuntimeException((string) ($tokenData['error_description'] ?? $tokenData['error']));
        }

        $client->setAccessToken($tokenData);
        $oauth2 = new Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        $token = GoogleDocxToken::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'connected_by' => $userId,
                'access_token' => $tokenData['access_token'] ?? '',
                'refresh_token' => $tokenData['refresh_token'] ?? $existingToken?->refresh_token,
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds((int) $tokenData['expires_in'])
                    : now()->addHour(),
                'google_account_id' => $userInfo->getId(),
                'google_email' => $userInfo->getEmail(),
                'google_name' => $userInfo->getName(),
                'google_avatar_url' => $userInfo->getPicture(),
                'is_active' => true,
                'connected_at' => now(),
                'disconnected_at' => null,
            ]
        );

        $this->client = $client;
        $this->docsService = new Docs($client);
        $this->driveService = new Drive($client);

        $this->log($tenantId, 'connected', null, null, ['google_email' => $token->google_email]);

        return $token->fresh();
    }

    public function disconnect(int $tenantId): void
    {
        $token = GoogleDocxToken::forTenant($tenantId)->first();
        if (!$token) {
            return;
        }

        try {
            $client = $this->makeClient();
            if ($token->access_token) {
                $client->revokeToken($token->access_token);
            }
        } catch (\Throwable $e) {
            Log::warning('[GoogleDocx] token revoke failed', ['message' => $e->getMessage()]);
        }

        $token->update([
            'is_active' => false,
            'disconnected_at' => now(),
            'access_token' => '',
            'refresh_token' => null,
        ]);

        $this->log($tenantId, 'disconnected');
    }

    public function getToken(int $tenantId): ?GoogleDocxToken
    {
        return GoogleDocxToken::forTenant($tenantId)->active()->first();
    }

    public function getDocsService(int $tenantId): Docs
    {
        if ($this->docsService) {
            return $this->docsService;
        }

        $token = $this->getValidToken($tenantId);
        $client = $this->makeClient();
        $client->setAccessToken($token->toGoogleToken());

        if ($token->is_expired) {
            if (!$token->refresh_token) {
                $this->invalidateTokenAfterOAuthFailure($token, 'missing_refresh_token');
                throw new RuntimeException(__('google-docx::messages.errors.session_expired'));
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
                    throw new RuntimeException(__('google-docx::messages.errors.session_expired'));
                }

                throw new RuntimeException((string) ($newToken['error_description'] ?? $newToken['error']));
            }
        }

        $this->client = $client;
        $this->docsService = new Docs($client);
        $this->driveService = new Drive($client);

        return $this->docsService;
    }

    public function getDriveService(int $tenantId): Drive
    {
        if ($this->driveService) {
            return $this->driveService;
        }

        $this->getDocsService($tenantId);

        return $this->driveService;
    }

    public function listDocuments(int $tenantId, string $search = '', ?string $pageToken = null): array
    {
        $drive = $this->getDriveService($tenantId);

        $query = "mimeType='application/vnd.google-apps.document' and trashed = false";
        if ($search !== '') {
            $escaped = addslashes($search);
            $query = "mimeType='application/vnd.google-apps.document' and name contains '{$escaped}' and trashed = false";
        }

        $params = [
            'q' => $query,
            'fields' => 'nextPageToken,files(id,name,mimeType,createdTime,modifiedTime,webViewLink,shared)',
            'pageSize' => (int) config('google-docx.api.page_size', 50),
            'orderBy' => 'modifiedTime desc',
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        try {
            $result = $drive->files->listFiles($params);
        } catch (\Throwable $e) {
            throw $this->translateGoogleApiException($e);
        }

        $documents = [];
        foreach ((array) $result->getFiles() as $file) {
            $documents[] = $this->formatDocumentFromDrive($file);
            $this->syncDocumentToLocal($file, $tenantId);
        }

        GoogleDocxToken::forTenant($tenantId)->update(['last_sync_at' => now()]);

        return [
            'documents' => $documents,
            'next_page_token' => $result->getNextPageToken(),
        ];
    }

    public function getDocument(int $tenantId, string $documentId): array
    {
        $documentId = $this->normalizeDocumentId($documentId);
        $docs = $this->getDocsService($tenantId);
        $drive = $this->getDriveService($tenantId);

        try {
            $document = $docs->documents->get($documentId);
            $file = $drive->files->get($documentId, [
                'fields' => 'id,name,mimeType,createdTime,modifiedTime,webViewLink,shared',
            ]);
        } catch (\Throwable $e) {
            throw $this->translateGoogleApiException($e, $documentId);
        }

        $this->syncDocumentToLocal($file, $tenantId, $document);

        return $this->formatDocument($document, $file);
    }

    public function createDocument(int $tenantId, string $title, string $content = ''): array
    {
        $docs = $this->getDocsService($tenantId);

        try {
            $document = $docs->documents->create(new \Google\Service\Docs\Document([
                'title' => $title,
            ]));

            $documentId = (string) $document->getDocumentId();

            if (trim($content) !== '') {
                $this->appendText($tenantId, $documentId, $content);
            }

            $created = $this->getDocument($tenantId, $documentId);
            $this->log($tenantId, 'create_document', $documentId, $title);

            return $created;
        } catch (\Throwable $e) {
            throw $this->translateGoogleApiException($e);
        }
    }

    public function renameDocument(int $tenantId, string $documentId, string $newTitle): array
    {
        $documentId = $this->normalizeDocumentId($documentId);
        $drive = $this->getDriveService($tenantId);

        try {
            $updated = $drive->files->update($documentId, new Drive\DriveFile(['name' => $newTitle]), [
                'fields' => 'id,name,mimeType,createdTime,modifiedTime,webViewLink,shared',
            ]);
        } catch (\Throwable $e) {
            throw $this->translateGoogleApiException($e, $documentId);
        }

        $this->syncDocumentToLocal($updated, $tenantId);
        $this->log($tenantId, 'rename_document', $documentId, $newTitle);

        return $this->getDocument($tenantId, $documentId);
    }

    public function duplicateDocument(int $tenantId, string $documentId, string $newTitle = ''): array
    {
        $documentId = $this->normalizeDocumentId($documentId);
        $drive = $this->getDriveService($tenantId);

        $name = trim($newTitle);
        if ($name === '') {
            $existingTitle = GoogleDocxDocument::forTenant($tenantId)
                ->where('document_id', $documentId)
                ->value('title');
            $name = 'Copie de ' . ($existingTitle ?: $documentId);
        }

        try {
            $copied = $drive->files->copy($documentId, new Drive\DriveFile(['name' => $name]), [
                'fields' => 'id,name,mimeType,createdTime,modifiedTime,webViewLink,shared',
            ]);
        } catch (\Throwable $e) {
            throw $this->translateGoogleApiException($e, $documentId);
        }

        $newId = (string) $copied->getId();
        $this->syncDocumentToLocal($copied, $tenantId);
        $this->log($tenantId, 'duplicate_document', $newId, $name, ['source_document_id' => $documentId]);

        return $this->getDocument($tenantId, $newId);
    }

    public function deleteDocument(int $tenantId, string $documentId): bool
    {
        $documentId = $this->normalizeDocumentId($documentId);
        $drive = $this->getDriveService($tenantId);

        $title = GoogleDocxDocument::forTenant($tenantId)
            ->where('document_id', $documentId)
            ->value('title');

        try {
            $drive->files->delete($documentId);
        } catch (\Throwable $e) {
            throw $this->translateGoogleApiException($e, $documentId);
        }

        GoogleDocxDocument::forTenant($tenantId)
            ->where('document_id', $documentId)
            ->delete();

        $this->log($tenantId, 'delete_document', $documentId, $title ?: $documentId);

        return true;
    }

    public function appendText(int $tenantId, string $documentId, string $text): array
    {
        $documentId = $this->normalizeDocumentId($documentId);
        $docs = $this->getDocsService($tenantId);

        $doc = $docs->documents->get($documentId);
        $insertAt = $this->getDocumentEndIndex($doc);

        $requests = [
            new DocsRequest([
                'insertText' => new InsertTextRequest([
                    'location' => ['index' => $insertAt],
                    'text' => "\n" . $text,
                ]),
            ]),
        ];

        try {
            $docs->documents->batchUpdate($documentId, new BatchUpdateDocumentRequest([
                'requests' => $requests,
            ]));
        } catch (\Throwable $e) {
            throw $this->translateGoogleApiException($e, $documentId);
        }

        $this->log($tenantId, 'append_text', $documentId, null, [
            'chars_added' => mb_strlen($text),
        ]);

        return $this->getDocument($tenantId, $documentId);
    }

    public function replaceText(int $tenantId, string $documentId, string $search, string $replace = '', bool $matchCase = false): array
    {
        $documentId = $this->normalizeDocumentId($documentId);
        $docs = $this->getDocsService($tenantId);

        $requests = [
            new DocsRequest([
                'replaceAllText' => new ReplaceAllTextRequest([
                    'containsText' => new SubstringMatchCriteria([
                        'text' => $search,
                        'matchCase' => $matchCase,
                    ]),
                    'replaceText' => $replace,
                ]),
            ]),
        ];

        try {
            $response = $docs->documents->batchUpdate($documentId, new BatchUpdateDocumentRequest([
                'requests' => $requests,
            ]));
        } catch (\Throwable $e) {
            throw $this->translateGoogleApiException($e, $documentId);
        }

        $occurrences = 0;
        $replies = (array) $response->getReplies();
        if (!empty($replies) && method_exists($replies[0], 'getReplaceAllText')) {
            $occurrences = (int) ($replies[0]->getReplaceAllText()?->getOccurrencesChanged() ?? 0);
        }

        $this->log($tenantId, 'replace_text', $documentId, null, [
            'search' => $search,
            'replace' => $replace,
            'occurrences' => $occurrences,
        ]);

        $document = $this->getDocument($tenantId, $documentId);
        $document['replace_occurrences'] = $occurrences;

        return $document;
    }

    public function exportDocument(int $tenantId, string $documentId, string $format = 'txt'): array
    {
        $documentId = $this->normalizeDocumentId($documentId);
        $drive = $this->getDriveService($tenantId);

        $formats = [
            'txt' => ['mime' => 'text/plain', 'ext' => 'txt'],
            'html' => ['mime' => 'text/html', 'ext' => 'html'],
            'pdf' => ['mime' => 'application/pdf', 'ext' => 'pdf'],
            'docx' => ['mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'ext' => 'docx'],
        ];

        $cfg = $formats[$format] ?? $formats['txt'];

        try {
            $meta = $drive->files->get($documentId, [
                'fields' => 'id,name',
            ]);

            $response = $drive->files->export($documentId, $cfg['mime'], ['alt' => 'media']);
            $content = (string) $response->getBody()->getContents();
        } catch (\Throwable $e) {
            throw $this->translateGoogleApiException($e, $documentId);
        }

        $base = Str::slug((string) $meta->getName(), '-');
        if ($base === '') {
            $base = 'document-google-docs';
        }

        $fileName = $base . '.' . $cfg['ext'];

        $this->log($tenantId, 'export_document', $documentId, (string) $meta->getName(), [
            'format' => $format,
            'file_name' => $fileName,
        ]);

        return [
            'file_name' => $fileName,
            'mime' => $cfg['mime'],
            'content' => $content,
        ];
    }

    public function getStats(int $tenantId): array
    {
        $token = $this->getToken($tenantId);

        return [
            'connected' => (bool) $token,
            'google_email' => $token?->google_email,
            'google_name' => $token?->google_name,
            'connected_at' => $token?->connected_at?->toIso8601String(),
            'last_sync_at' => $token?->last_sync_at?->toIso8601String(),
            'total_documents' => GoogleDocxDocument::forTenant($tenantId)->count(),
        ];
    }

    private function formatDocument($doc, $driveFile = null): array
    {
        $title = (string) ($doc->getTitle() ?: $driveFile?->getName() ?: 'Sans titre');
        $docId = (string) $doc->getDocumentId();
        $text = $this->extractPlainText($doc);

        return [
            'document_id' => $docId,
            'title' => $title,
            'document_url' => $driveFile?->getWebViewLink() ?: ('https://docs.google.com/document/d/' . $docId . '/edit'),
            'mime_type' => $driveFile?->getMimeType() ?: 'application/vnd.google-apps.document',
            'is_shared' => (bool) ($driveFile?->getShared() ?? false),
            'revision_id' => (int) ($doc->getRevisionId() ?? 0),
            'body_text' => $text,
            'content_chars' => mb_strlen($text),
            'created_at' => $driveFile?->getCreatedTime(),
            'modified_at' => $driveFile?->getModifiedTime(),
        ];
    }

    private function formatDocumentFromDrive($file): array
    {
        return [
            'document_id' => $file->getId(),
            'title' => $file->getName(),
            'document_url' => $file->getWebViewLink(),
            'mime_type' => $file->getMimeType(),
            'is_shared' => (bool) $file->getShared(),
            'created_at' => $file->getCreatedTime(),
            'modified_at' => $file->getModifiedTime(),
        ];
    }

    private function syncDocumentToLocal($file, int $tenantId, $document = null): void
    {
        try {
            $text = $document ? $this->extractPlainText($document) : '';

            GoogleDocxDocument::updateOrCreate(
                ['tenant_id' => $tenantId, 'document_id' => $file->getId()],
                [
                    'title' => $file->getName(),
                    'document_url' => $file->getWebViewLink(),
                    'mime_type' => $file->getMimeType(),
                    'is_shared' => (bool) $file->getShared(),
                    'revision_id' => $document ? (int) ($document->getRevisionId() ?? 0) : null,
                    'content_chars' => $document ? mb_strlen($text) : 0,
                    'created_by' => Auth::id(),
                    'modified_by' => Auth::id(),
                    'drive_created_at' => $file->getCreatedTime(),
                    'drive_modified_at' => $file->getModifiedTime(),
                ]
            );
        } catch (\Throwable $e) {
            Log::debug('[GoogleDocx] local sync skipped', ['message' => $e->getMessage()]);
        }
    }

    private function extractPlainText($doc): string
    {
        $body = $doc->getBody();
        if (!$body) {
            return '';
        }

        $content = (array) $body->getContent();
        $text = '';

        foreach ($content as $structuralElement) {
            $paragraph = $structuralElement->getParagraph();
            if (!$paragraph) {
                continue;
            }

            foreach ((array) $paragraph->getElements() as $element) {
                $run = $element->getTextRun();
                if ($run) {
                    $text .= (string) $run->getContent();
                }
            }
        }

        return trim($text);
    }

    private function getDocumentEndIndex($doc): int
    {
        $content = (array) ($doc->getBody()?->getContent() ?? []);
        if (empty($content)) {
            return 1;
        }

        $last = end($content);
        $endIndex = (int) ($last?->getEndIndex() ?? 1);

        return max(1, $endIndex - 1);
    }

    private function getValidToken(int $tenantId): GoogleDocxToken
    {
        $token = $this->getToken($tenantId);
        if (!$token) {
            throw new RuntimeException(__('google-docx::messages.errors.not_connected'));
        }

        return $token;
    }

    private function redirectUri(): string
    {
        $path = (string) config('google-docx.oauth.redirect_uri', '/extensions/google-docx/oauth/callback');
        if (trim($path) === '') {
            $path = '/extensions/google-docx/oauth/callback';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url($path);
    }

    private function normalizeDocumentId(string $documentId): string
    {
        $value = trim(rawurldecode($documentId));
        if ($value === '') {
            throw new RuntimeException(__('google-docx::messages.errors.document_id_missing'));
        }

        $value = trim($value, " \t\n\r\0\x0B'\"");

        if (str_starts_with($value, '{') && str_ends_with($value, '}')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $candidate = (string) ($decoded['document_id'] ?? $decoded['id'] ?? $decoded['documentId'] ?? '');
                if ($candidate !== '') {
                    $value = trim($candidate);
                }
            }
        }

        if (preg_match('#/document/d/([a-zA-Z0-9\-_]+)#', $value, $matches) === 1) {
            return $matches[1];
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = (string) parse_url($value, PHP_URL_PATH);
            if (preg_match('#/document/d/([a-zA-Z0-9\-_]+)#', $path, $matches) === 1) {
                return $matches[1];
            }

            parse_str((string) parse_url($value, PHP_URL_QUERY), $query);
            if (!empty($query['id']) && is_string($query['id'])) {
                $candidate = trim((string) $query['id']);
                if (preg_match('/^[a-zA-Z0-9\-_]{15,}$/', $candidate) === 1) {
                    return $candidate;
                }
            }

            throw new RuntimeException(__('google-docx::messages.errors.document_url_invalid'));
        }

        if (preg_match('/^[a-zA-Z0-9\-_]{15,}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/([a-zA-Z0-9\-_]{20,})/', $value, $matches) === 1) {
            return $matches[1];
        }

        throw new RuntimeException(__('google-docx::messages.errors.document_id_invalid'));
    }

    private function translateGoogleApiException(\Throwable $e, ?string $documentId = null): RuntimeException
    {
        $raw = (string) $e->getMessage();
        $message = Str::lower($raw);

        if (
            str_contains($message, 'invalid_grant')
            || str_contains($message, 'expired or revoked')
            || str_contains($message, 'token has been expired or revoked')
        ) {
            return new RuntimeException(__('google-docx::messages.errors.session_expired'));
        }

        $isNotFound = str_contains($message, 'requested entity was not found')
            || str_contains($message, 'not_found')
            || str_contains($message, 'notfound');

        if ($isNotFound) {
            $idInfo = $documentId ? " (ID: {$documentId})" : '';
            return new RuntimeException(__('google-docx::messages.errors.document_not_found') . $idInfo . '.');
        }

        $isPermissionDenied = str_contains($message, 'permission')
            || str_contains($message, 'forbidden')
            || str_contains($message, 'insufficient');

        if ($isPermissionDenied) {
            return new RuntimeException(__('google-docx::messages.errors.permission_denied'));
        }

        return new RuntimeException($raw !== '' ? $raw : __('google-docx::messages.errors.unexpected'));
    }

    private function isRevokedOrExpiredOAuthError(string $error, string $description = ''): bool
    {
        $full = Str::lower(trim($error . ' ' . $description));

        return str_contains($full, 'invalid_grant')
            || str_contains($full, 'expired or revoked')
            || str_contains($full, 'token has been expired or revoked');
    }

    private function invalidateTokenAfterOAuthFailure(GoogleDocxToken $token, string $reason): void
    {
        try {
            $token->update([
                'is_active' => false,
                'disconnected_at' => now(),
                'access_token' => '',
                'refresh_token' => null,
            ]);

            $this->log((int) $token->tenant_id, 'oauth_invalidated', null, null, ['reason' => $reason]);
        } catch (\Throwable $e) {
            Log::warning('[GoogleDocx] invalidate token failed', ['message' => $e->getMessage()]);
        }
    }

    private function log(
        int $tenantId,
        string $action,
        ?string $documentId = null,
        ?string $documentTitle = null,
        array $metadata = []
    ): void {
        try {
            GoogleDocxActivityLog::create([
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'document_id' => $documentId,
                'document_title' => $documentTitle,
                'action' => $action,
                'metadata' => $metadata,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('[GoogleDocx] activity log skipped', ['message' => $e->getMessage()]);
        }
    }
}
