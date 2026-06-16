<?php

namespace NexusExtensions\GoogleGmail\Services;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message as GmailMessage;
use Google\Service\Gmail\ModifyMessageRequest;
use Google\Service\Oauth2;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NexusExtensions\GoogleGmail\Models\GoogleGmailActivityLog;
use NexusExtensions\GoogleGmail\Models\GoogleGmailLabel;
use NexusExtensions\GoogleGmail\Models\GoogleGmailMessage;
use NexusExtensions\GoogleGmail\Models\GoogleGmailSetting;
use NexusExtensions\GoogleGmail\Models\GoogleGmailToken;
use RuntimeException;
use Throwable;

class GoogleGmailService
{
    private ?GoogleClient $client = null;
    private ?Gmail $gmailService = null;

    public function __construct(protected GoogleGmailSocketService $socketService)
    {
    }

    public function makeClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId((string) config('google-gmail.oauth.client_id'));
        $client->setClientSecret((string) config('google-gmail.oauth.client_secret'));
        $client->setRedirectUri($this->redirectUri());
        $client->setScopes((array) config('google-gmail.oauth.scopes', []));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        return $client;
    }

    public function getAuthUrl(int $tenantId, int $userId, array $context = []): string
    {
        $clientId = (string) config('google-gmail.oauth.client_id');
        if ($clientId === '') {
            throw new RuntimeException('GOOGLE_GMAIL_CLIENT_ID is missing.');
        }

        $client = $this->makeClient();
        $state = encrypt([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'nonce' => Str::uuid()->toString(),
            'ts' => now()->timestamp,
            'desktop' => (bool) ($context['desktop'] ?? false),
            'desktop_return' => trim((string) ($context['desktop_return'] ?? '')) ?: null,
        ]);

        $client->setState($state);

        return $client->createAuthUrl();
    }

    public function parseState(string $encryptedState): array
    {
        $state = decrypt($encryptedState);
        if (!is_array($state) || !isset($state['tenant_id'], $state['user_id'])) {
            throw new RuntimeException('Invalid OAuth state.');
        }

        $issuedAt = (int) ($state['ts'] ?? 0);
        $now = now()->timestamp;

        if ($issuedAt <= 0 || $issuedAt < ($now - 900) || $issuedAt > ($now + 300)) {
            throw new RuntimeException('OAuth state expired.');
        }

        return $state;
    }

    public function exchangeCode(string $code, int $tenantId, int $userId): GoogleGmailToken
    {
        $client = $this->makeClient();
        $tokenData = $client->fetchAccessTokenWithAuthCode($code);
        $existingToken = GoogleGmailToken::forTenant($tenantId)->first();

        if (isset($tokenData['error'])) {
            throw new RuntimeException((string) ($tokenData['error_description'] ?? $tokenData['error']));
        }

        $client->setAccessToken($tokenData);
        $oauth2 = new Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        $gmail = new Gmail($client);
        $profile = $gmail->users->getProfile('me');

        $token = GoogleGmailToken::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'connected_by' => $userId,
                'access_token' => $tokenData['access_token'] ?? '',
                // Google does not always return a new refresh_token on reconnect.
                'refresh_token' => $tokenData['refresh_token'] ?? $existingToken?->refresh_token,
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds((int) $tokenData['expires_in'])
                    : now()->addHour(),
                'google_account_id' => (string) ($userInfo->getId() ?? ''),
                'google_email' => (string) ($userInfo->getEmail() ?? $profile->getEmailAddress()),
                'google_name' => (string) ($userInfo->getName() ?? ''),
                'google_avatar_url' => (string) ($userInfo->getPicture() ?? ''),
                'history_id' => (string) ($profile->getHistoryId() ?? ''),
                'is_active' => true,
                'connected_at' => now(),
                'disconnected_at' => null,
            ]
        );

        $this->client = $client;
        $this->gmailService = $gmail;

        $this->listLabels($tenantId, true);
        $this->log($tenantId, 'connected', null, null, ['google_email' => $token->google_email]);
        $this->safeEmitMailboxRealtime($tenantId, 'connected', [
            'actor_user_id' => $userId,
            'google_email' => $token->google_email,
        ], true, true);

        return $token->fresh();
    }

    public function disconnect(int $tenantId): void
    {
        $token = GoogleGmailToken::forTenant($tenantId)->first();
        if (!$token) {
            return;
        }

        try {
            $client = $this->makeClient();
            if ($token->access_token) {
                $client->revokeToken($token->access_token);
            }
        } catch (Throwable $e) {
            Log::warning('[GoogleGmail] token revoke failed', ['message' => $e->getMessage()]);
        }

        $token->update([
            'is_active' => false,
            'disconnected_at' => now(),
            'access_token' => '',
            'refresh_token' => null,
        ]);

        $this->log($tenantId, 'disconnected');
        $this->safeEmitMailboxRealtime($tenantId, 'disconnected', [
            'actor_user_id' => Auth::id(),
            'reason' => 'manual_disconnect',
        ], false);
    }

    public function getToken(int $tenantId): ?GoogleGmailToken
    {
        return GoogleGmailToken::forTenant($tenantId)->active()->first();
    }

    public function getSettings(int $tenantId): array
    {
        return $this->settingsToArray($this->getOrCreateSettingsModel($tenantId));
    }

    public function updateSettings(int $tenantId, array $payload): array
    {
        $settings = $this->getOrCreateSettingsModel($tenantId);
        $signatureHtml = trim((string) ($payload['signature_html'] ?? ''));

        $mainLabels = array_values(array_unique(array_filter(array_map(
            fn ($value) => trim((string) $value),
            (array) ($payload['main_labels'] ?? $settings->main_labels ?? $this->defaultMainLabels())
        ))));

        if (count($mainLabels) > 10) {
            $mainLabels = array_slice($mainLabels, 0, 10);
        }

        $settings->fill([
            'signature_enabled' => (bool) ($payload['signature_enabled'] ?? false),
            'signature_html' => $signatureHtml !== '' ? $signatureHtml : null,
            'signature_text' => $signatureHtml !== '' ? trim(strip_tags($signatureHtml)) : null,
            'signature_on_replies' => (bool) ($payload['signature_on_replies'] ?? true),
            'signature_on_forwards' => (bool) ($payload['signature_on_forwards'] ?? true),
            'default_cc' => $this->parseEmailsString((string) ($payload['default_cc'] ?? '')),
            'default_bcc' => $this->parseEmailsString((string) ($payload['default_bcc'] ?? '')),
            'polling_interval_seconds' => max(15, min(300, (int) ($payload['polling_interval_seconds'] ?? 45))),
            'main_labels' => !empty($mainLabels) ? $mainLabels : $this->defaultMainLabels(),
        ]);

        $settings->save();

        $this->log($tenantId, 'update_settings', null, null, [
            'signature_enabled' => (bool) $settings->signature_enabled,
            'polling_interval_seconds' => (int) $settings->polling_interval_seconds,
        ]);

        $this->safeEmitMailboxRealtime($tenantId, 'settings.updated', [
            'actor_user_id' => Auth::id(),
            'settings' => $this->settingsToArray($settings),
        ], false);

        return $this->settingsToArray($settings);
    }

    public function getGmailService(int $tenantId): Gmail
    {
        if ($this->gmailService) {
            return $this->gmailService;
        }

        $token = $this->getValidToken($tenantId);
        $client = $this->makeClient();
        $client->setAccessToken($token->toGoogleToken());

        if ($token->is_expired) {
            if (!$token->refresh_token) {
                $this->invalidateTokenAfterOAuthFailure($token, 'missing_refresh_token');
                throw new RuntimeException('Session Google Gmail expiree ou revoquee. Reconnectez votre compte Google.');
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
                    throw new RuntimeException('Session Google Gmail expiree ou revoquee. Reconnectez votre compte Google.');
                }

                throw new RuntimeException((string) ($newToken['error_description'] ?? $newToken['error']));
            }
        }

        $this->client = $client;
        $this->gmailService = new Gmail($client);

        return $this->gmailService;
    }

    public function listLabels(int $tenantId, bool $refresh = false): array
    {
        if (!$refresh) {
            $cached = GoogleGmailLabel::forTenant($tenantId)
                ->orderByRaw("FIELD(type, 'system', 'user')")
                ->orderBy('name')
                ->get();

            $token = GoogleGmailToken::forTenant($tenantId)->first();
            $isFreshCache = $cached->isNotEmpty()
                && $token?->last_sync_at
                && $token->last_sync_at->greaterThan(now()->subMinutes(2));

            if ($isFreshCache) {
                return $cached->map(fn (GoogleGmailLabel $label) => $this->formatStoredLabel($label))
                    ->values()
                    ->all();
            }
        }

        $gmail = $this->getGmailService($tenantId);

        try {
            $apiLabels = (array) ($gmail->users_labels->listUsersLabels('me')->getLabels() ?? []);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e);
        }

        $rows = [];
        foreach ($apiLabels as $label) {
            $detailedLabel = $label;
            $labelId = trim((string) ($label->getId() ?? ''));

            if ($labelId !== '') {
                try {
                    // More reliable counters than listUsersLabels on some Gmail accounts.
                    $detailedLabel = $gmail->users_labels->get('me', $labelId);
                } catch (Throwable $e) {
                    Log::debug('[GoogleGmail] label detail fetch failed', [
                        'tenant_id' => $tenantId,
                        'label_id' => $labelId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $rows[] = $this->hydrateLabelCountsFallback($tenantId, $this->formatLabelFromApi($detailedLabel));
        }

        foreach ($rows as $row) {
            GoogleGmailLabel::updateOrCreate(
                ['tenant_id' => $tenantId, 'label_id' => $row['label_id']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'messages_total' => $row['messages_total'],
                    'messages_unread' => $row['messages_unread'],
                    'threads_total' => $row['threads_total'],
                    'threads_unread' => $row['threads_unread'],
                    'color_background' => $row['color_background'],
                    'color_text' => $row['color_text'],
                    'is_visible' => $row['is_visible'],
                ]
            );
        }

        GoogleGmailToken::forTenant($tenantId)->update(['last_sync_at' => now()]);

        return GoogleGmailLabel::forTenant($tenantId)
            ->orderByRaw("FIELD(type, 'system', 'user')")
            ->orderBy('name')
            ->get()
            ->map(fn (GoogleGmailLabel $label) => $this->formatStoredLabel($label))
            ->values()
            ->all();
    }

    public function listMessages(
        int $tenantId,
        string $labelId = 'INBOX',
        string $query = '',
        ?string $pageToken = null,
        int $maxResults = 25,
        bool $includeSpamTrash = false
    ): array {
        $gmail = $this->getGmailService($tenantId);

        $max = max(1, min(50, $maxResults > 0 ? $maxResults : (int) config('google-gmail.api.max_results', 25)));

        $params = [
            'maxResults' => $max,
            'includeSpamTrash' => $includeSpamTrash,
        ];

        $labelId = trim($labelId);
        if ($labelId !== '' && !in_array(strtoupper($labelId), ['ALL', 'ANY'], true)) {
            // Google PHP Gmail client expects labelIds as string for single-label filtering.
            $params['labelIds'] = $labelId;
        }

        if (trim($query) !== '') {
            $params['q'] = trim($query);
        }

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        try {
            $list = $gmail->users_messages->listUsersMessages('me', $params);
            $items = (array) ($list->getMessages() ?? []);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e);
        }

        $messages = [];
        foreach ($items as $item) {
            $messageId = (string) $item->getId();
            if ($messageId === '') {
                continue;
            }

            try {
                $meta = $gmail->users_messages->get('me', $messageId, [
                    'format' => 'metadata',
                    'metadataHeaders' => ['From', 'To', 'Cc', 'Subject', 'Date', 'Message-ID'],
                ]);

                $formatted = $this->formatMessageFromApi($meta, false);
                $messages[] = $formatted;
                $this->syncMessageToLocal($tenantId, $formatted);
            } catch (Throwable $e) {
                Log::warning('[GoogleGmail] message list item failed', [
                    'tenant_id' => $tenantId,
                    'message_id' => $messageId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        GoogleGmailToken::forTenant($tenantId)->update(['last_sync_at' => now()]);

        return [
            'messages' => $messages,
            'next_page_token' => $list->getNextPageToken(),
            'result_size_estimate' => (int) ($list->getResultSizeEstimate() ?? count($messages)),
        ];
    }

    public function getMessage(int $tenantId, string $messageId): array
    {
        $id = $this->normalizeMessageId($messageId);
        $gmail = $this->getGmailService($tenantId);

        try {
            $message = $gmail->users_messages->get('me', $id, ['format' => 'full']);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e, $id);
        }

        $formatted = $this->formatMessageFromApi($message, true);
        $this->syncMessageToLocal($tenantId, $formatted, true);
        GoogleGmailToken::forTenant($tenantId)->update(['last_sync_at' => now()]);

        return $formatted;
    }

    public function getThread(int $tenantId, string $threadId): array
    {
        $value = trim(rawurldecode($threadId));
        if ($value === '') {
            throw new RuntimeException('Thread id is required.');
        }

        $gmail = $this->getGmailService($tenantId);

        try {
            $thread = $gmail->users_threads->get('me', $value, [
                'format' => 'metadata',
                'metadataHeaders' => ['From', 'To', 'Cc', 'Subject', 'Date', 'Message-ID'],
            ]);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e, $value);
        }

        $messages = [];
        foreach ((array) ($thread->getMessages() ?? []) as $message) {
            $formatted = $this->formatMessageFromApi($message, false);
            $messages[] = $formatted;
            $this->syncMessageToLocal($tenantId, $formatted);
        }

        usort($messages, function (array $a, array $b): int {
            $aDate = strtotime((string) ($a['sent_at'] ?? $a['internal_date'] ?? '')) ?: 0;
            $bDate = strtotime((string) ($b['sent_at'] ?? $b['internal_date'] ?? '')) ?: 0;
            return $aDate <=> $bDate;
        });

        return [
            'thread_id' => $value,
            'messages' => $messages,
        ];
    }

    public function sendEmail(int $tenantId, array $payload, array $attachments = []): array
    {
        $settings = $this->getOrCreateSettingsModel($tenantId);
        $to = $this->parseEmailsString((string) ($payload['to'] ?? ''));
        if (empty($to)) {
            throw new RuntimeException('At least one recipient is required.');
        }

        $cc = $this->mergeEmails(
            $this->parseEmailsString((string) ($payload['cc'] ?? '')),
            (array) ($settings->default_cc ?? [])
        );
        $bcc = $this->mergeEmails(
            $this->parseEmailsString((string) ($payload['bcc'] ?? '')),
            (array) ($settings->default_bcc ?? [])
        );

        $token = $this->getValidToken($tenantId);
        $messageBody = $this->applySignatureToBody(
            (string) ($payload['body_text'] ?? ''),
            (string) ($payload['body_html'] ?? ''),
            $settings,
            'compose'
        );

        $mime = $this->buildMimeMessage([
            'from_email' => $token->google_email ?: 'me',
            'from_name' => $token->google_name ?: null,
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'subject' => (string) ($payload['subject'] ?? '(No subject)'),
            'body_text' => $messageBody['body_text'],
            'body_html' => $messageBody['body_html'],
        ], $attachments);

        $gmail = $this->getGmailService($tenantId);

        try {
            $message = new GmailMessage();
            $message->setRaw($this->base64UrlEncode($mime));

            $sent = $gmail->users_messages->send('me', $message);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e);
        }

        $sentId = (string) $sent->getId();
        $result = $this->getMessage($tenantId, $sentId);

        $this->log($tenantId, 'send_email', $sentId, (string) ($result['thread_id'] ?? null), [
            'to' => $to,
            'subject' => (string) ($payload['subject'] ?? ''),
        ]);

        $this->safeEmitMailboxRealtime($tenantId, 'message.sent', [
            'actor_user_id' => Auth::id(),
            'message' => $result,
        ]);

        return $result;
    }

    public function replyToMessage(int $tenantId, string $messageId, array $payload, array $attachments = []): array
    {
        $settings = $this->getOrCreateSettingsModel($tenantId);
        $original = $this->getMessage($tenantId, $messageId);
        $headers = array_change_key_case((array) ($original['headers'] ?? []), CASE_LOWER);

        $replyTo = $headers['reply-to'] ?? $headers['from'] ?? '';
        $to = $this->parseEmailsString((string) $replyTo);

        if (empty($to)) {
            throw new RuntimeException('Unable to resolve recipient for reply.');
        }

        $subject = $this->normalizeReplySubject((string) ($headers['subject'] ?? ''));
        $cc = $this->mergeEmails(
            $this->parseEmailsString((string) ($payload['cc'] ?? '')),
            (array) ($settings->default_cc ?? [])
        );
        $bcc = $this->mergeEmails(
            $this->parseEmailsString((string) ($payload['bcc'] ?? '')),
            (array) ($settings->default_bcc ?? [])
        );

        $token = $this->getValidToken($tenantId);
        $messageBody = $this->applySignatureToBody(
            (string) ($payload['body_text'] ?? ''),
            (string) ($payload['body_html'] ?? ''),
            $settings,
            'reply'
        );

        $mime = $this->buildMimeMessage([
            'from_email' => $token->google_email ?: 'me',
            'from_name' => $token->google_name ?: null,
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'subject' => $subject,
            'body_text' => $messageBody['body_text'],
            'body_html' => $messageBody['body_html'],
            'in_reply_to' => $headers['message-id'] ?? null,
            'references' => trim(($headers['references'] ?? '') . ' ' . ($headers['message-id'] ?? '')),
        ], $attachments);

        $gmail = $this->getGmailService($tenantId);

        try {
            $message = new GmailMessage();
            $message->setRaw($this->base64UrlEncode($mime));
            if (!empty($original['thread_id'])) {
                $message->setThreadId((string) $original['thread_id']);
            }

            $sent = $gmail->users_messages->send('me', $message);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e);
        }

        $sentId = (string) $sent->getId();
        $result = $this->getMessage($tenantId, $sentId);

        $this->log($tenantId, 'reply_email', $sentId, (string) ($result['thread_id'] ?? null), [
            'source_message_id' => $this->normalizeMessageId($messageId),
            'to' => $to,
        ]);

        $this->safeEmitMailboxRealtime($tenantId, 'message.replied', [
            'actor_user_id' => Auth::id(),
            'message' => $result,
            'source_message_id' => $this->normalizeMessageId($messageId),
        ]);

        return $result;
    }

    public function forwardMessage(int $tenantId, string $messageId, array $payload, array $attachments = []): array
    {
        $settings = $this->getOrCreateSettingsModel($tenantId);
        $to = $this->parseEmailsString((string) ($payload['to'] ?? ''));
        if (empty($to)) {
            throw new RuntimeException('At least one recipient is required for forwarding.');
        }

        $cc = $this->mergeEmails(
            $this->parseEmailsString((string) ($payload['cc'] ?? '')),
            (array) ($settings->default_cc ?? [])
        );
        $bcc = $this->mergeEmails(
            $this->parseEmailsString((string) ($payload['bcc'] ?? '')),
            (array) ($settings->default_bcc ?? [])
        );

        $original = $this->getMessage($tenantId, $messageId);
        $headers = array_change_key_case((array) ($original['headers'] ?? []), CASE_LOWER);
        $subject = $this->normalizeForwardSubject((string) ($headers['subject'] ?? ''));

        $quotedText = trim((string) ($original['body_text'] ?? $original['snippet'] ?? ''));
        $introText = trim((string) ($payload['body_text'] ?? ''));
        $bodyText = trim($introText . "\n\n---------- Forwarded message ----------\n" . $quotedText);

        $introHtml = trim((string) ($payload['body_html'] ?? ''));
        $quotedHtml = (string) ($original['body_html'] ?? '');
        if ($quotedHtml === '') {
            $quotedHtml = '<pre style="white-space:pre-wrap">' . e($quotedText) . '</pre>';
        }

        $bodyHtml = trim($introHtml . '<hr><div>' . $quotedHtml . '</div>');

        $token = $this->getValidToken($tenantId);
        $messageBody = $this->applySignatureToBody($bodyText, $bodyHtml, $settings, 'forward');

        $mime = $this->buildMimeMessage([
            'from_email' => $token->google_email ?: 'me',
            'from_name' => $token->google_name ?: null,
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'subject' => $subject,
            'body_text' => $messageBody['body_text'],
            'body_html' => $messageBody['body_html'],
        ], $attachments);

        $gmail = $this->getGmailService($tenantId);

        try {
            $message = new GmailMessage();
            $message->setRaw($this->base64UrlEncode($mime));

            $sent = $gmail->users_messages->send('me', $message);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e);
        }

        $sentId = (string) $sent->getId();
        $result = $this->getMessage($tenantId, $sentId);

        $this->log($tenantId, 'forward_email', $sentId, (string) ($result['thread_id'] ?? null), [
            'source_message_id' => $this->normalizeMessageId($messageId),
            'to' => $to,
        ]);

        $this->safeEmitMailboxRealtime($tenantId, 'message.forwarded', [
            'actor_user_id' => Auth::id(),
            'message' => $result,
            'source_message_id' => $this->normalizeMessageId($messageId),
        ]);

        return $result;
    }

    public function markRead(int $tenantId, string $messageId): array
    {
        return $this->modifyMessageLabels($tenantId, $messageId, [], ['UNREAD'], 'mark_read');
    }

    public function markUnread(int $tenantId, string $messageId): array
    {
        return $this->modifyMessageLabels($tenantId, $messageId, ['UNREAD'], [], 'mark_unread');
    }

    public function star(int $tenantId, string $messageId): array
    {
        return $this->modifyMessageLabels($tenantId, $messageId, ['STARRED'], [], 'star');
    }

    public function unstar(int $tenantId, string $messageId): array
    {
        return $this->modifyMessageLabels($tenantId, $messageId, [], ['STARRED'], 'unstar');
    }

    public function archive(int $tenantId, string $messageId): array
    {
        return $this->modifyMessageLabels($tenantId, $messageId, [], ['INBOX'], 'archive');
    }

    public function trash(int $tenantId, string $messageId): array
    {
        $id = $this->normalizeMessageId($messageId);
        $gmail = $this->getGmailService($tenantId);

        try {
            $gmail->users_messages->trash('me', $id);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e, $id);
        }

        $updated = $this->getMessage($tenantId, $id);
        $this->log($tenantId, 'trash', $id, (string) ($updated['thread_id'] ?? null));
        $this->safeEmitMailboxRealtime($tenantId, 'message.updated', [
            'actor_user_id' => Auth::id(),
            'message' => $updated,
            'action' => 'trash',
        ]);

        return $updated;
    }

    public function untrash(int $tenantId, string $messageId): array
    {
        $id = $this->normalizeMessageId($messageId);
        $gmail = $this->getGmailService($tenantId);

        try {
            $gmail->users_messages->untrash('me', $id);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e, $id);
        }

        $updated = $this->getMessage($tenantId, $id);
        $this->log($tenantId, 'untrash', $id, (string) ($updated['thread_id'] ?? null));
        $this->safeEmitMailboxRealtime($tenantId, 'message.updated', [
            'actor_user_id' => Auth::id(),
            'message' => $updated,
            'action' => 'untrash',
        ]);

        return $updated;
    }

    public function deleteMessage(int $tenantId, string $messageId): void
    {
        $id = $this->normalizeMessageId($messageId);
        $gmail = $this->getGmailService($tenantId);

        try {
            $gmail->users_messages->delete('me', $id);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e, $id);
        }

        GoogleGmailMessage::forTenant($tenantId)
            ->where('gmail_message_id', $id)
            ->delete();

        $this->log($tenantId, 'delete_message', $id, null);
        $this->safeEmitMailboxRealtime($tenantId, 'message.deleted', [
            'actor_user_id' => Auth::id(),
            'message_id' => $id,
            'action' => 'delete',
        ]);
    }

    public function downloadAttachment(int $tenantId, string $messageId, string $attachmentId): array
    {
        $msgId = $this->normalizeMessageId($messageId);
        $attId = trim(rawurldecode($attachmentId));

        if ($attId === '') {
            throw new RuntimeException('Attachment id is required.');
        }

        $message = $this->getMessage($tenantId, $msgId);
        $attachmentMeta = collect((array) ($message['attachments'] ?? []))
            ->first(fn (array $row) => (string) ($row['attachment_id'] ?? '') === $attId);

        $gmail = $this->getGmailService($tenantId);

        try {
            $body = $gmail->users_messages_attachments->get('me', $msgId, $attId);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e, $msgId);
        }

        $binary = $this->decodeBase64Url((string) $body->getData());
        $metaFileName = (string) ($attachmentMeta['filename'] ?? '');
        $metaMime = (string) ($attachmentMeta['mime_type'] ?? '');
        $detectedMime = $this->guessMimeFromBinary($binary);
        $mime = $this->normalizeMimeType($metaMime !== '' ? $metaMime : ($detectedMime ?: 'application/octet-stream'));

        $fileName = $this->sanitizeAttachmentFilename($metaFileName);
        if ($fileName === '') {
            $ext = $this->extensionFromMime($mime);
            $shortId = substr(preg_replace('/[^a-zA-Z0-9]+/', '', $attId), 0, 12);
            if ($shortId === '') {
                $shortId = substr(md5($attId), 0, 8);
            }
            $fileName = 'piece-jointe-' . $shortId . ($ext !== '' ? '.' . $ext : '');
        }

        $this->log($tenantId, 'download_attachment', $msgId, (string) ($message['thread_id'] ?? null), [
            'attachment_id' => $attId,
            'file_name' => $fileName,
        ]);

        return [
            'file_name' => $fileName,
            'mime' => $mime,
            'content' => $binary,
        ];
    }

    public function getStats(int $tenantId, bool $refreshLabels = false): array
    {
        $token = $this->getToken($tenantId);
        if (!$token) {
            return $this->formatMailboxStats($tenantId, null, []);
        }

        $labels = $this->listLabels($tenantId, $refreshLabels);
        if (empty($labels) && !$refreshLabels) {
            $labels = $this->listLabels($tenantId, true);
        }

        return $this->formatMailboxStats($tenantId, $token, $labels);
    }

    public function getMailboxSnapshot(
        int $tenantId,
        bool $refresh = false,
        string $labelId = 'INBOX',
        string $query = '',
        ?string $pageToken = null,
        int $maxResults = 25,
        bool $includeSpamTrash = false
    ): array {
        $settings = $this->getSettings($tenantId);
        $token = $this->getToken($tenantId);

        if (!$token) {
            return [
                'settings' => $settings,
                'labels' => [],
                'stats' => $this->formatMailboxStats($tenantId, null, []),
                'messages' => [
                    'messages' => [],
                    'next_page_token' => null,
                    'result_size_estimate' => 0,
                ],
            ];
        }

        $labels = $this->listLabels($tenantId, $refresh);
        if (empty($labels) && !$refresh) {
            $labels = $this->listLabels($tenantId, true);
        }

        return [
            'settings' => $settings,
            'labels' => $labels,
            'stats' => $this->formatMailboxStats($tenantId, $token, $labels),
            'messages' => $this->listMessages(
                $tenantId,
                $labelId,
                $query,
                $pageToken,
                $maxResults,
                $includeSpamTrash
            ),
        ];
    }

    public function syncMailboxRealtime(int $tenantId, bool $force = false): array
    {
        if (!config('google-gmail.socket.enabled', false)) {
            return ['changed' => false, 'emitted' => false];
        }

        $token = $this->getToken($tenantId);
        if (!$token) {
            return ['changed' => false, 'emitted' => false];
        }

        $gmail = $this->getGmailService($tenantId);

        try {
            $profile = $gmail->users->getProfile('me');
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e);
        }

        $previousHistoryId = (string) ($token->history_id ?? '');
        $currentHistoryId = trim((string) ($profile->getHistoryId() ?? ''));
        $hasChanges = $force
            || $previousHistoryId === ''
            || $currentHistoryId === ''
            || !hash_equals($previousHistoryId, $currentHistoryId);

        if (!$hasChanges) {
            return [
                'changed' => false,
                'emitted' => false,
                'history_id' => $currentHistoryId,
                'previous_history_id' => $previousHistoryId,
            ];
        }

        $payload = $this->buildMailboxRealtimePayload($tenantId, true, true);
        $payload['history_id'] = $currentHistoryId !== '' ? $currentHistoryId : $previousHistoryId;
        $payload['previous_history_id'] = $previousHistoryId;
        $payload['reason'] = 'scheduler_sync';

        if ($currentHistoryId !== '' && $currentHistoryId !== $previousHistoryId) {
            GoogleGmailToken::forTenant($tenantId)->update([
                'history_id' => $currentHistoryId,
            ]);
        }

        $emitted = $this->socketService->emit($tenantId, 'mailbox.synced', $payload);

        return [
            'changed' => true,
            'emitted' => $emitted,
            'history_id' => $payload['history_id'],
            'previous_history_id' => $previousHistoryId,
        ];
    }

    private function modifyMessageLabels(int $tenantId, string $messageId, array $add, array $remove, string $action): array
    {
        $id = $this->normalizeMessageId($messageId);
        $gmail = $this->getGmailService($tenantId);

        $request = new ModifyMessageRequest();
        $request->setAddLabelIds($add);
        $request->setRemoveLabelIds($remove);

        try {
            $gmail->users_messages->modify('me', $id, $request);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e, $id);
        }

        $updated = $this->getMessage($tenantId, $id);
        $this->log($tenantId, $action, $id, (string) ($updated['thread_id'] ?? null), [
            'add_labels' => $add,
            'remove_labels' => $remove,
        ]);

        $this->safeEmitMailboxRealtime($tenantId, 'message.updated', [
            'actor_user_id' => Auth::id(),
            'message' => $updated,
            'action' => $action,
        ]);

        return $updated;
    }

    private function safeEmitMailboxRealtime(
        int $tenantId,
        string $event,
        array $payload = [],
        bool $refreshSnapshot = true,
        bool $includeInboxMessages = false
    ): void {
        try {
            $packet = $refreshSnapshot
                ? array_merge($this->buildMailboxRealtimePayload($tenantId, true, $includeInboxMessages), $payload)
                : $payload;

            $this->socketService->emit($tenantId, $event, $packet);
        } catch (Throwable $e) {
            Log::debug('[GoogleGmail][Socket] emit skipped', [
                'tenant_id' => $tenantId,
                'event' => $event,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function buildMailboxRealtimePayload(
        int $tenantId,
        bool $refreshLabels = true,
        bool $includeInboxMessages = false
    ): array {
        $token = $this->getToken($tenantId);
        $labels = $token ? $this->listLabels($tenantId, $refreshLabels) : [];

        $payload = [
            'connected' => (bool) $token,
            'stats' => $this->formatMailboxStats($tenantId, $token, $labels),
            'labels' => $labels,
        ];

        if ($includeInboxMessages && $token) {
            $preview = $this->listMessages(
                $tenantId,
                'INBOX',
                '',
                null,
                $this->normalizeSocketPreviewLimit((int) config('google-gmail.socket.scheduler_preview_limit', 25)),
                false
            );

            $payload['selected_label'] = 'INBOX';
            $payload['messages'] = (array) ($preview['messages'] ?? []);
            $payload['next_page_token'] = $preview['next_page_token'] ?? null;
            $payload['result_size_estimate'] = (int) ($preview['result_size_estimate'] ?? 0);
        }

        return $payload;
    }

    private function formatMailboxStats(int $tenantId, ?GoogleGmailToken $token, array $labels): array
    {
        if (!$token) {
            return [
                'connected' => false,
                'google_email' => null,
                'google_name' => null,
                'connected_at' => null,
                'last_sync_at' => null,
                'inbox_total' => 0,
                'unread_total' => 0,
                'sent_total' => 0,
                'draft_total' => 0,
                'trash_total' => 0,
                'starred_total' => 0,
                'local_messages' => 0,
            ];
        }

        $byId = collect($labels)->keyBy('label_id');

        $inbox = (int) ($byId->get('INBOX')['messages_total'] ?? 0);
        $unread = (int) ($byId->get('INBOX')['messages_unread'] ?? ($byId->get('UNREAD')['messages_total'] ?? 0));
        $sent = (int) ($byId->get('SENT')['messages_total'] ?? 0);
        $draft = (int) ($byId->get('DRAFT')['messages_total'] ?? 0);
        $trash = (int) ($byId->get('TRASH')['messages_total'] ?? 0);
        $starred = (int) ($byId->get('STARRED')['messages_total'] ?? 0);

        return [
            'connected' => true,
            'google_email' => $token->google_email,
            'google_name' => $token->google_name,
            'connected_at' => $token->connected_at?->toIso8601String(),
            'last_sync_at' => $token->last_sync_at?->toIso8601String(),
            'inbox_total' => $inbox,
            'unread_total' => $unread,
            'sent_total' => $sent,
            'draft_total' => $draft,
            'trash_total' => $trash,
            'starred_total' => $starred,
            'local_messages' => GoogleGmailMessage::forTenant($tenantId)->count(),
        ];
    }

    private function normalizeSocketPreviewLimit(int $value): int
    {
        return max(5, min(50, $value > 0 ? $value : 25));
    }

    private function buildMimeMessage(array $data, array $attachments = []): string
    {
        $fromEmail = (string) ($data['from_email'] ?? 'me');
        $fromName = trim((string) ($data['from_name'] ?? ''));

        $to = (array) ($data['to'] ?? []);
        $cc = (array) ($data['cc'] ?? []);
        $bcc = (array) ($data['bcc'] ?? []);

        $subject = (string) ($data['subject'] ?? '(No subject)');
        $bodyText = (string) ($data['body_text'] ?? '');
        $bodyHtml = (string) ($data['body_html'] ?? '');

        if (trim($bodyText) === '' && trim($bodyHtml) === '') {
            $bodyText = '(empty)';
        }

        $headers = [];
        $headers[] = 'Date: ' . now()->toRfc2822String();

        if ($fromName !== '') {
            $headers[] = 'From: ' . $this->encodeHeader($fromName) . ' <' . $fromEmail . '>';
        } else {
            $headers[] = 'From: ' . $fromEmail;
        }

        $headers[] = 'To: ' . implode(', ', $to);
        if (!empty($cc)) {
            $headers[] = 'Cc: ' . implode(', ', $cc);
        }
        if (!empty($bcc)) {
            $headers[] = 'Bcc: ' . implode(', ', $bcc);
        }

        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'MIME-Version: 1.0';

        $inReplyTo = trim((string) ($data['in_reply_to'] ?? ''));
        $references = trim((string) ($data['references'] ?? ''));

        if ($inReplyTo !== '') {
            $headers[] = 'In-Reply-To: ' . $inReplyTo;
        }

        if ($references !== '') {
            $headers[] = 'References: ' . $references;
        }

        $hasAttachments = !empty($attachments);
        $hasText = trim($bodyText) !== '';
        $hasHtml = trim($bodyHtml) !== '';

        $body = '';

        if ($hasAttachments) {
            $mixedBoundary = 'mix_' . Str::random(24);
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"';

            $bodyParts = [];

            if ($hasText && $hasHtml) {
                $altBoundary = 'alt_' . Str::random(24);
                $alt = [];
                $alt[] = '--' . $altBoundary;
                $alt[] = 'Content-Type: text/plain; charset="UTF-8"';
                $alt[] = 'Content-Transfer-Encoding: quoted-printable';
                $alt[] = '';
                $alt[] = quoted_printable_encode($bodyText);
                $alt[] = '';
                $alt[] = '--' . $altBoundary;
                $alt[] = 'Content-Type: text/html; charset="UTF-8"';
                $alt[] = 'Content-Transfer-Encoding: quoted-printable';
                $alt[] = '';
                $alt[] = quoted_printable_encode($bodyHtml);
                $alt[] = '';
                $alt[] = '--' . $altBoundary . '--';

                $bodyParts[] = '--' . $mixedBoundary;
                $bodyParts[] = 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"';
                $bodyParts[] = '';
                $bodyParts[] = implode("\r\n", $alt);
            } else {
                $contentType = $hasHtml ? 'text/html' : 'text/plain';
                $content = $hasHtml ? $bodyHtml : $bodyText;

                $bodyParts[] = '--' . $mixedBoundary;
                $bodyParts[] = 'Content-Type: ' . $contentType . '; charset="UTF-8"';
                $bodyParts[] = 'Content-Transfer-Encoding: quoted-printable';
                $bodyParts[] = '';
                $bodyParts[] = quoted_printable_encode($content);
            }

            foreach ($attachments as $attachment) {
                if (!$attachment instanceof UploadedFile) {
                    continue;
                }

                if (!$attachment->isValid()) {
                    continue;
                }

                $filename = $attachment->getClientOriginalName() ?: ('file-' . Str::random(6));
                $mime = $attachment->getMimeType() ?: 'application/octet-stream';
                $content = chunk_split(base64_encode((string) file_get_contents($attachment->getRealPath())));

                $bodyParts[] = '--' . $mixedBoundary;
                $bodyParts[] = 'Content-Type: ' . $mime . '; name="' . addslashes($filename) . '"';
                $bodyParts[] = 'Content-Transfer-Encoding: base64';
                $bodyParts[] = 'Content-Disposition: attachment; filename="' . addslashes($filename) . '"';
                $bodyParts[] = '';
                $bodyParts[] = $content;
            }

            $bodyParts[] = '--' . $mixedBoundary . '--';
            $body = implode("\r\n", $bodyParts);
        } elseif ($hasText && $hasHtml) {
            $boundary = 'alt_' . Str::random(24);
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

            $body = implode("\r\n", [
                '--' . $boundary,
                'Content-Type: text/plain; charset="UTF-8"',
                'Content-Transfer-Encoding: quoted-printable',
                '',
                quoted_printable_encode($bodyText),
                '',
                '--' . $boundary,
                'Content-Type: text/html; charset="UTF-8"',
                'Content-Transfer-Encoding: quoted-printable',
                '',
                quoted_printable_encode($bodyHtml),
                '',
                '--' . $boundary . '--',
            ]);
        } else {
            $contentType = $hasHtml ? 'text/html' : 'text/plain';
            $content = $hasHtml ? $bodyHtml : $bodyText;

            $headers[] = 'Content-Type: ' . $contentType . '; charset="UTF-8"';
            $headers[] = 'Content-Transfer-Encoding: quoted-printable';
            $body = quoted_printable_encode($content);
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function formatLabelFromApi($label): array
    {
        $color = $label->getColor();
        $labelId = (string) ($label->getId() ?? '');

        return [
            'label_id' => $labelId,
            'name' => $this->localizeSystemLabelName($labelId, (string) ($label->getName() ?? '')),
            'type' => (string) ($label->getType() ?? 'user'),
            'messages_total' => (int) ($label->getMessagesTotal() ?? 0),
            'messages_unread' => (int) ($label->getMessagesUnread() ?? 0),
            'threads_total' => (int) ($label->getThreadsTotal() ?? 0),
            'threads_unread' => (int) ($label->getThreadsUnread() ?? 0),
            'color_background' => $color ? (string) ($color->getBackgroundColor() ?? '') : null,
            'color_text' => $color ? (string) ($color->getTextColor() ?? '') : null,
            'is_visible' => true,
        ];
    }

    private function formatStoredLabel(GoogleGmailLabel $label): array
    {
        return [
            'label_id' => $label->label_id,
            'name' => $this->localizeSystemLabelName((string) $label->label_id, (string) $label->name),
            'type' => $label->type,
            'messages_total' => (int) $label->messages_total,
            'messages_unread' => (int) $label->messages_unread,
            'threads_total' => (int) $label->threads_total,
            'threads_unread' => (int) $label->threads_unread,
            'color_background' => $label->color_background,
            'color_text' => $label->color_text,
            'is_visible' => (bool) $label->is_visible,
        ];
    }

    private function hydrateLabelCountsFallback(int $tenantId, array $row): array
    {
        $rawLabelId = trim((string) ($row['label_id'] ?? ''));
        $labelId = strtoupper($rawLabelId);
        $labelType = strtolower((string) ($row['type'] ?? ''));

        if ($rawLabelId === '') {
            return $row;
        }

        // Apply fallback only for known system labels to avoid wrong counts on user labels.
        if ($labelType !== 'system') {
            return $row;
        }

        $trackedSystemLabels = [
            'INBOX',
            'SENT',
            'DRAFT',
            'STARRED',
            'TRASH',
            'SPAM',
            'IMPORTANT',
            'UNREAD',
            'CATEGORY_PERSONAL',
            'CATEGORY_UPDATES',
            'CATEGORY_PROMOTIONS',
            'CATEGORY_SOCIAL',
            'CATEGORY_FORUMS',
        ];

        if (!in_array($labelId, $trackedSystemLabels, true)) {
            return $row;
        }

        $includeSpamTrash = in_array($labelId, ['SPAM', 'TRASH'], true);
        if ((int) ($row['messages_total'] ?? 0) <= 0) {
            $estimated = $this->estimateMessagesTotal($tenantId, $rawLabelId, $includeSpamTrash);
            if ($estimated !== null) {
                $row['messages_total'] = $estimated;
            }
        }

        if ($labelId === 'INBOX' && (int) ($row['messages_unread'] ?? 0) <= 0) {
            $estimatedUnread = $this->estimateMessagesTotal($tenantId, 'INBOX', false, 'is:unread');
            if ($estimatedUnread !== null) {
                $row['messages_unread'] = $estimatedUnread;
            }
        }

        if ($labelId === 'UNREAD' && (int) ($row['messages_unread'] ?? 0) <= 0) {
            $row['messages_unread'] = (int) ($row['messages_total'] ?? 0);
        }

        return $row;
    }

    private function estimateMessagesTotal(
        int $tenantId,
        string $labelId,
        bool $includeSpamTrash = false,
        ?string $query = null
    ): ?int {
        try {
            $gmail = $this->getGmailService($tenantId);

            $params = [
                'maxResults' => 1,
                // Google PHP Gmail client expects labelIds as string for single-label filtering.
                'labelIds' => trim($labelId),
                'includeSpamTrash' => $includeSpamTrash,
            ];

            if ($query !== null && trim($query) !== '') {
                $params['q'] = trim($query);
            }

            $result = $gmail->users_messages->listUsersMessages('me', $params);

            return (int) ($result->getResultSizeEstimate() ?? 0);
        } catch (Throwable) {
            return null;
        }
    }

    private function localizeSystemLabelName(string $labelId, string $fallbackName): string
    {
        $map = [
            'INBOX' => 'Boite de reception',
            'SENT' => 'Envoyes',
            'DRAFT' => 'Brouillons',
            'TRASH' => 'Corbeille',
            'SPAM' => 'Spam',
            'STARRED' => 'Favoris',
            'IMPORTANT' => 'Importants',
            'UNREAD' => 'Non lus',
            'CATEGORY_PERSONAL' => 'Personnel',
            'CATEGORY_UPDATES' => 'Mises a jour',
            'CATEGORY_PROMOTIONS' => 'Promotions',
            'CATEGORY_SOCIAL' => 'Reseaux sociaux',
            'CATEGORY_FORUMS' => 'Forums',
            'CHAT' => 'Chats',
            'SCHEDULED' => 'Programmes',
            'ALL' => 'Tous les messages',
        ];

        $key = strtoupper(trim($labelId));
        if (isset($map[$key])) {
            return $map[$key];
        }

        return trim($fallbackName) !== '' ? $fallbackName : $labelId;
    }

    private function formatMessageFromApi($message, bool $full = false): array
    {
        $payload = $message->getPayload();
        $headers = $this->headersToMap((array) ($payload?->getHeaders() ?? []));
        $labelIds = (array) ($message->getLabelIds() ?? []);

        $internalDateMs = (int) ($message->getInternalDate() ?? 0);
        $internalDate = $internalDateMs > 0 ? now()->setTimestamp((int) floor($internalDateMs / 1000)) : null;

        $bodyText = '';
        $bodyHtml = '';
        $attachments = [];

        if ($full && $payload) {
            $this->extractMessageBodyAndAttachments($payload, $bodyText, $bodyHtml, $attachments);
        }

        $sender = (string) ($headers['from'] ?? '');
        $toRecipients = $this->parseEmailsString((string) ($headers['to'] ?? ''));
        $ccRecipients = $this->parseEmailsString((string) ($headers['cc'] ?? ''));

        $sentAt = null;
        if (!empty($headers['date'])) {
            try {
                $sentAt = Carbon::parse((string) $headers['date']);
            } catch (Throwable) {
                $sentAt = null;
            }
        }

        return [
            'message_id' => (string) ($message->getId() ?? ''),
            'thread_id' => (string) ($message->getThreadId() ?? ''),
            'message_id_header' => (string) ($headers['message-id'] ?? ''),
            'subject' => (string) ($headers['subject'] ?? '(No subject)'),
            'from' => $sender,
            'to' => $toRecipients,
            'cc' => $ccRecipients,
            'snippet' => (string) ($message->getSnippet() ?? ''),
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
            'label_ids' => $labelIds,
            'is_read' => !in_array('UNREAD', $labelIds, true),
            'is_starred' => in_array('STARRED', $labelIds, true),
            'has_attachments' => !empty($attachments),
            'attachments' => $attachments,
            'headers' => $headers,
            'sent_at' => $sentAt?->toIso8601String(),
            'internal_date' => $internalDate?->toIso8601String(),
            'web_url' => 'https://mail.google.com/mail/u/0/#all/' . (string) ($message->getId() ?? ''),
        ];
    }

    private function extractMessageBodyAndAttachments($part, string &$bodyText, string &$bodyHtml, array &$attachments): void
    {
        $mimeType = (string) ($part->getMimeType() ?? '');
        $filename = (string) ($part->getFilename() ?? '');
        $body = $part->getBody();

        $data = (string) ($body?->getData() ?? '');
        $attachmentId = (string) ($body?->getAttachmentId() ?? '');
        $size = (int) ($body?->getSize() ?? 0);

        if ($filename !== '' && $attachmentId !== '') {
            $attachments[] = [
                'attachment_id' => $attachmentId,
                'filename' => $filename,
                'mime_type' => $mimeType,
                'size' => $size,
            ];
        }

        if ($data !== '') {
            $decoded = $this->decodeBase64Url($data);
            if ($mimeType === 'text/plain') {
                $bodyText .= $decoded;
            } elseif ($mimeType === 'text/html') {
                $bodyHtml .= $decoded;
            }
        }

        foreach ((array) ($part->getParts() ?? []) as $child) {
            $this->extractMessageBodyAndAttachments($child, $bodyText, $bodyHtml, $attachments);
        }
    }

    private function syncMessageToLocal(int $tenantId, array $message, bool $withBody = false): void
    {
        try {
            $payload = [
                'thread_id' => (string) ($message['thread_id'] ?? ''),
                'message_id_header' => (string) ($message['message_id_header'] ?? ''),
                'subject' => (string) ($message['subject'] ?? ''),
                'sender' => (string) ($message['from'] ?? ''),
                'to_recipients' => (array) ($message['to'] ?? []),
                'cc_recipients' => (array) ($message['cc'] ?? []),
                'snippet' => (string) ($message['snippet'] ?? ''),
                'label_ids' => (array) ($message['label_ids'] ?? []),
                'has_attachments' => (bool) ($message['has_attachments'] ?? false),
                'is_read' => (bool) ($message['is_read'] ?? false),
                'is_starred' => (bool) ($message['is_starred'] ?? false),
                'sent_at' => !empty($message['sent_at']) ? Carbon::parse((string) $message['sent_at']) : null,
                'gmail_internal_date' => !empty($message['internal_date']) ? Carbon::parse((string) $message['internal_date']) : null,
                'web_url' => (string) ($message['web_url'] ?? ''),
                'last_synced_at' => now(),
                'modified_by' => Auth::id(),
            ];

            if ($withBody) {
                $payload['body_text'] = (string) ($message['body_text'] ?? '');
                $payload['body_html'] = (string) ($message['body_html'] ?? '');
            }

            GoogleGmailMessage::updateOrCreate(
                ['tenant_id' => $tenantId, 'gmail_message_id' => (string) ($message['message_id'] ?? '')],
                array_merge($payload, ['created_by' => Auth::id()])
            );
        } catch (Throwable $e) {
            Log::debug('[GoogleGmail] local sync skipped', ['message' => $e->getMessage()]);
        }
    }

    private function getOrCreateSettingsModel(int $tenantId): GoogleGmailSetting
    {
        return GoogleGmailSetting::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'signature_enabled' => false,
                'signature_on_replies' => true,
                'signature_on_forwards' => true,
                'default_cc' => [],
                'default_bcc' => [],
                'polling_interval_seconds' => 45,
                'main_labels' => $this->defaultMainLabels(),
            ]
        );
    }

    private function settingsToArray(GoogleGmailSetting $settings): array
    {
        return [
            'signature_enabled' => (bool) $settings->signature_enabled,
            'signature_html' => (string) ($settings->signature_html ?? ''),
            'signature_text' => (string) ($settings->signature_text ?? ''),
            'signature_on_replies' => (bool) $settings->signature_on_replies,
            'signature_on_forwards' => (bool) $settings->signature_on_forwards,
            'default_cc' => array_values(array_filter((array) ($settings->default_cc ?? []))),
            'default_bcc' => array_values(array_filter((array) ($settings->default_bcc ?? []))),
            'polling_interval_seconds' => (int) ($settings->polling_interval_seconds ?: 45),
            'main_labels' => !empty($settings->main_labels)
                ? array_values((array) $settings->main_labels)
                : $this->defaultMainLabels(),
        ];
    }

    private function defaultMainLabels(): array
    {
        return [
            'INBOX',
            'STARRED',
            'SENT',
            'DRAFT',
            'IMPORTANT',
            'UNREAD',
            'CATEGORY_PERSONAL',
            'CATEGORY_UPDATES',
            'CATEGORY_PROMOTIONS',
            'TRASH',
        ];
    }

    private function mergeEmails(array ...$collections): array
    {
        $merged = [];
        foreach ($collections as $emails) {
            foreach ((array) $emails as $email) {
                $value = trim((string) $email);
                if ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $merged[] = Str::lower($value);
            }
        }

        return array_values(array_unique($merged));
    }

    private function applySignatureToBody(
        string $bodyText,
        string $bodyHtml,
        GoogleGmailSetting $settings,
        string $context = 'compose'
    ): array {
        if (!$settings->signature_enabled) {
            return [
                'body_text' => $bodyText,
                'body_html' => $bodyHtml,
            ];
        }

        if ($context === 'reply' && !$settings->signature_on_replies) {
            return [
                'body_text' => $bodyText,
                'body_html' => $bodyHtml,
            ];
        }

        if ($context === 'forward' && !$settings->signature_on_forwards) {
            return [
                'body_text' => $bodyText,
                'body_html' => $bodyHtml,
            ];
        }

        $signatureHtml = trim((string) ($settings->signature_html ?? ''));
        $signatureText = trim((string) ($settings->signature_text ?? strip_tags($signatureHtml)));

        if ($signatureHtml === '' && $signatureText === '') {
            return [
                'body_text' => $bodyText,
                'body_html' => $bodyHtml,
            ];
        }

        $updatedHtml = trim($bodyHtml);
        if ($signatureHtml !== '') {
            $signatureBlock = '<div style="margin-top:12px;padding-top:10px;border-top:1px solid #dbe1ea;">' . $signatureHtml . '</div>';
            $updatedHtml = trim($updatedHtml) !== '' ? ($updatedHtml . $signatureBlock) : $signatureBlock;
        }

        $updatedText = trim($bodyText);
        if ($signatureText !== '') {
            $signatureBlockText = "\n\n-- \n" . $signatureText;
            $updatedText = trim($updatedText) !== '' ? ($updatedText . $signatureBlockText) : trim($signatureText);
        }

        return [
            'body_text' => $updatedText,
            'body_html' => $updatedHtml,
        ];
    }

    private function headersToMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $header) {
            $name = Str::lower((string) ($header->getName() ?? ''));
            if ($name === '') {
                continue;
            }
            $map[$name] = (string) ($header->getValue() ?? '');
        }

        return $map;
    }

    private function parseEmailsString(string $value): array
    {
        $parts = preg_split('/[,;\n]+/', $value) ?: [];
        $emails = [];

        foreach ($parts as $part) {
            $candidate = trim($part);
            if ($candidate === '') {
                continue;
            }

            if (preg_match('/<([^>]+)>/', $candidate, $matches) === 1) {
                $candidate = trim($matches[1]);
            }

            $candidate = trim($candidate, " \t\n\r\0\x0B\"'");

            if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $emails[] = Str::lower($candidate);
            }
        }

        return array_values(array_unique($emails));
    }

    private function normalizeReplySubject(string $subject): string
    {
        $clean = trim($subject);
        if ($clean === '') {
            return 'Re: (No subject)';
        }

        if (preg_match('/^\s*re\s*:/i', $clean) === 1) {
            return $clean;
        }

        return 'Re: ' . $clean;
    }

    private function normalizeForwardSubject(string $subject): string
    {
        $clean = trim($subject);
        if ($clean === '') {
            return 'Fwd: (No subject)';
        }

        if (preg_match('/^\s*fwd\s*:/i', $clean) === 1 || preg_match('/^\s*fw\s*:/i', $clean) === 1) {
            return $clean;
        }

        return 'Fwd: ' . $clean;
    }

    private function encodeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
        }

        return $value;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decodeBase64Url(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($value, true);

        return $decoded === false ? '' : $decoded;
    }

    private function guessMimeFromBinary(string $binary): ?string
    {
        if ($binary === '') {
            return null;
        }

        if (!function_exists('finfo_open')) {
            return null;
        }

        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return null;
        }

        $mime = @finfo_buffer($finfo, $binary) ?: null;
        @finfo_close($finfo);

        if (!is_string($mime) || trim($mime) === '') {
            return null;
        }

        return $this->normalizeMimeType($mime);
    }

    private function normalizeMimeType(string $mime): string
    {
        $value = strtolower(trim($mime));
        if ($value === '') {
            return 'application/octet-stream';
        }

        if (str_contains($value, ';')) {
            $value = trim((string) explode(';', $value)[0]);
        }

        return $value !== '' ? $value : 'application/octet-stream';
    }

    private function sanitizeAttachmentFilename(string $fileName): string
    {
        $value = trim($fileName);
        if ($value === '') {
            return '';
        }

        $value = str_replace(["\r", "\n", "\0"], '', $value);
        $value = str_replace(['\\', '/'], '-', $value);
        $value = trim($value, " .\t\n\r\0\x0B");

        return $value;
    }

    private function extensionFromMime(string $mime): string
    {
        $map = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'text/plain' => 'txt',
            'text/html' => 'html',
            'application/json' => 'json',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'video/mp4' => 'mp4',
            'application/zip' => 'zip',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        ];

        return $map[$this->normalizeMimeType($mime)] ?? '';
    }

    private function getValidToken(int $tenantId): GoogleGmailToken
    {
        $token = $this->getToken($tenantId);
        if (!$token) {
            throw new RuntimeException('Google Gmail is not connected for this tenant.');
        }

        return $token;
    }

    private function redirectUri(): string
    {
        $path = (string) config('google-gmail.oauth.redirect_uri', '/extensions/google-gmail/oauth/callback');
        if (trim($path) === '') {
            $path = '/extensions/google-gmail/oauth/callback';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url($path);
    }

    private function normalizeMessageId(string $messageId): string
    {
        $value = trim(rawurldecode($messageId));
        if ($value === '') {
            throw new RuntimeException('Google Gmail message id is required.');
        }

        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (str_starts_with($value, '{') && str_ends_with($value, '}')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $candidate = (string) ($decoded['message_id'] ?? $decoded['id'] ?? '');
                if ($candidate !== '') {
                    $value = trim($candidate);
                }
            }
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = (string) parse_url($value, PHP_URL_PATH);
            if (preg_match('/([a-zA-Z0-9\-_]{10,})$/', $path, $matches) === 1) {
                return $matches[1];
            }

            parse_str((string) parse_url($value, PHP_URL_QUERY), $query);
            if (!empty($query['message_id']) && is_string($query['message_id'])) {
                $candidate = trim((string) $query['message_id']);
                if (preg_match('/^[a-zA-Z0-9\-_]{10,}$/', $candidate) === 1) {
                    return $candidate;
                }
            }
        }

        if (preg_match('/^[a-zA-Z0-9\-_]{10,}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/([a-zA-Z0-9\-_]{16,})/', $value, $matches) === 1) {
            return $matches[1];
        }

        throw new RuntimeException('Invalid Google Gmail message id.');
    }

    private function translateGoogleApiException(Throwable $e, ?string $messageId = null): RuntimeException
    {
        $raw = (string) $e->getMessage();
        $msg = Str::lower($raw);

        if (
            str_contains($msg, 'invalid_grant')
            || str_contains($msg, 'expired or revoked')
            || str_contains($msg, 'token has been expired or revoked')
        ) {
            return new RuntimeException('Session Google Gmail expiree ou revoquee. Reconnectez Google Gmail.');
        }

        $isNotFound = str_contains($msg, 'not found')
            || str_contains($msg, 'requested entity was not found')
            || str_contains($msg, 'not_found');

        if ($isNotFound) {
            $idInfo = $messageId ? ' (ID: ' . $messageId . ')' : '';
            return new RuntimeException('Email introuvable dans Gmail' . $idInfo . '.');
        }

        $isPermission = str_contains($msg, 'permission')
            || str_contains($msg, 'forbidden')
            || str_contains($msg, 'insufficient');

        if ($isPermission) {
            return new RuntimeException('Google a refuse la requete. Verifiez les scopes OAuth et les autorisations du compte.');
        }

        $isInvalidGrant = str_contains($msg, 'invalid_grant');
        if ($isInvalidGrant) {
            return new RuntimeException('Session Google invalide ou expiree. Reconnectez Google Gmail.');
        }

        if (str_contains($msg, 'access blocked') || str_contains($msg, 'access_denied')) {
            return new RuntimeException('Acces Google bloque. Verifiez l ecran de consentement OAuth, les URI de redirection et l etat de publication.');
        }

        if (str_contains($msg, 'message too large') || str_contains($msg, 'size limit') || str_contains($msg, 'too large')) {
            return new RuntimeException('Email trop volumineux. Reduisez la taille des pieces jointes puis reessayez.');
        }

        return new RuntimeException($raw !== '' ? $raw : 'Unexpected Google Gmail error.');
    }

    private function isRevokedOrExpiredOAuthError(string $error, string $description = ''): bool
    {
        $full = Str::lower(trim($error . ' ' . $description));

        return str_contains($full, 'invalid_grant')
            || str_contains($full, 'expired or revoked')
            || str_contains($full, 'token has been expired or revoked');
    }

    private function invalidateTokenAfterOAuthFailure(GoogleGmailToken $token, string $reason): void
    {
        try {
            $token->update([
                'is_active' => false,
                'disconnected_at' => now(),
                'access_token' => '',
                'refresh_token' => null,
            ]);

            $this->log((int) $token->tenant_id, 'oauth_invalidated', null, null, ['reason' => $reason]);
        } catch (Throwable $e) {
            Log::warning('[GoogleGmail] invalidate token failed', ['message' => $e->getMessage()]);
        }
    }

    private function log(
        int $tenantId,
        string $action,
        ?string $messageId = null,
        ?string $threadId = null,
        array $metadata = []
    ): void {
        try {
            GoogleGmailActivityLog::create([
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'gmail_message_id' => $messageId,
                'thread_id' => $threadId,
                'action' => $action,
                'metadata' => $metadata,
                'ip_address' => request()?->ip(),
            ]);
        } catch (Throwable $e) {
            Log::debug('[GoogleGmail] activity log skipped', ['message' => $e->getMessage()]);
        }
    }
}
