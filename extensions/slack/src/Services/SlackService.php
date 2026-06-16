<?php

namespace NexusExtensions\Slack\Services;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NexusExtensions\Slack\Models\SlackActivityLog;
use NexusExtensions\Slack\Models\SlackChannel;
use NexusExtensions\Slack\Models\SlackMessage;
use NexusExtensions\Slack\Models\SlackToken;
use RuntimeException;

class SlackService
{
    public function __construct(protected SlackSocketService $socketService)
    {
    }

    public function getAuthUrl(int $tenantId, int $userId): string
    {
        $clientId = trim((string) config('slack.oauth.client_id'));
        if ($clientId === '') {
            throw new RuntimeException(__('slack::messages.errors.client_id_missing'));
        }
        $this->assertValidSlackClientId($clientId);

        $redirectUri = $this->redirectUri();
        $this->assertWebRedirectUri($redirectUri);
        $this->assertRedirectUriCompatibleWithSlackOauth($redirectUri);

        $state = encrypt([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'nonce' => Str::uuid()->toString(),
            'ts' => now()->timestamp,
        ]);

        $query = http_build_query([
            'client_id' => $clientId,
            'scope' => implode(',', (array) config('slack.oauth.scopes', [])),
            'user_scope' => implode(',', (array) config('slack.oauth.user_scopes', [])),
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return 'https://slack.com/oauth/v2/authorize?' . $query;
    }

    public function parseState(string $encryptedState): array
    {
        $state = decrypt($encryptedState);
        if (!is_array($state) || !isset($state['tenant_id'], $state['user_id'])) {
            throw new RuntimeException(__('slack::messages.errors.oauth_state_invalid'));
        }

        $issuedAt = (int) ($state['ts'] ?? 0);
        if ($issuedAt <= 0 || abs(now()->timestamp - $issuedAt) > 900) {
            throw new RuntimeException(__('slack::messages.errors.oauth_state_expired'));
        }

        return $state;
    }

    public function exchangeCode(string $code, int $tenantId, int $userId): SlackToken
    {
        $clientId = trim((string) config('slack.oauth.client_id'));
        $clientSecret = trim((string) config('slack.oauth.client_secret'));

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException(__('slack::messages.errors.oauth_credentials_missing'));
        }
        $this->assertValidSlackClientId($clientId);
        $redirectUri = $this->redirectUri();
        $this->assertWebRedirectUri($redirectUri);
        $this->assertRedirectUriCompatibleWithSlackOauth($redirectUri);

        $response = Http::asForm()
            ->acceptJson()
            ->timeout((int) config('slack.api.timeout', 20))
            ->post($this->apiUrl('oauth.v2.access'), [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

        if (!$response->ok()) {
            throw new RuntimeException(__('slack::messages.errors.oauth_request_failed', ['status' => $response->status()]));
        }

        $data = $response->json();
        if (!is_array($data) || !($data['ok'] ?? false)) {
            throw new RuntimeException($this->extractSlackError($data, __('slack::messages.errors.oauth_exchange_failed')));
        }

        $botToken = trim((string) ($data['access_token'] ?? ''));
        if ($botToken === '') {
            throw new RuntimeException(__('slack::messages.errors.bot_token_missing'));
        }

        $authTest = $this->apiRequestWithToken($botToken, 'GET', 'auth.test');

        $token = SlackToken::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'connected_by' => $userId,
                'bot_token' => $botToken,
                'bot_user_id' => (string) ($data['bot_user_id'] ?? ($authTest['user_id'] ?? '')),
                'app_id' => (string) ($data['app_id'] ?? ''),
                'team_id' => (string) ($data['team']['id'] ?? ($authTest['team_id'] ?? '')),
                'team_name' => (string) ($data['team']['name'] ?? ($authTest['team'] ?? '')),
                'authed_user_id' => (string) ($data['authed_user']['id'] ?? ''),
                'scope' => (string) ($data['scope'] ?? ''),
                'is_active' => true,
                'connected_at' => now(),
                'disconnected_at' => null,
            ]
        );

        $this->syncChannels($tenantId);

        $selected = SlackChannel::forTenant($tenantId)
            ->where('is_archived', false)
            ->orderByDesc('is_selected')
            ->orderBy('name')
            ->first();

        if ($selected) {
            $this->selectChannel($tenantId, (string) $selected->channel_id);
        }

        $this->log($tenantId, 'connected', null, null, [
            'team_id' => $token->team_id,
            'team_name' => $token->team_name,
        ]);

        $this->socketService->emit($tenantId, 'connected', [
            'tenant_id' => $tenantId,
            'team_name' => $token->team_name,
        ]);

        return $token->fresh();
    }

    public function disconnect(int $tenantId): void
    {
        $token = SlackToken::forTenant($tenantId)->first();
        if (!$token) {
            return;
        }

        if (!empty($token->bot_token)) {
            try {
                $this->apiRequestWithToken((string) $token->bot_token, 'POST', 'auth.revoke', [
                    'test' => false,
                ]);
            } catch (\Throwable $e) {
                Log::warning('[Slack] auth.revoke failed', [
                    'tenant_id' => $tenantId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $token->update([
            'is_active' => false,
            'disconnected_at' => now(),
            'bot_token' => null,
            'selected_channel_id' => null,
            'selected_channel_name' => null,
        ]);

        SlackChannel::forTenant($tenantId)->update(['is_selected' => false]);

        $this->log($tenantId, 'disconnected');
        $this->socketService->emit($tenantId, 'disconnected', ['tenant_id' => $tenantId]);
    }

    public function getToken(int $tenantId): ?SlackToken
    {
        return SlackToken::forTenant($tenantId)->active()->first();
    }

    public function getTokenOrFail(int $tenantId): SlackToken
    {
        $token = $this->getToken($tenantId);
        if (!$token) {
            throw new RuntimeException(__('slack::messages.errors.not_connected'));
        }

        if (trim((string) $token->bot_token) === '') {
            throw new RuntimeException(__('slack::messages.errors.bot_token_missing_reconnect'));
        }

        return $token;
    }

    public function syncChannels(int $tenantId): int
    {
        $token = $this->getTokenOrFail($tenantId);
        $seen = [];
        $count = 0;
        $cursor = null;
        $limit = max(1, min((int) config('slack.api.page_size', 100), 1000));

        do {
            $payload = [
                'types' => (string) config('slack.api.channel_types', 'public_channel,private_channel,im,mpim'),
                'exclude_archived' => false,
                'limit' => $limit,
            ];
            if (!empty($cursor)) {
                $payload['cursor'] = $cursor;
            }

            $data = $this->apiRequest($token, 'GET', 'conversations.list', $payload);
            $channels = (array) ($data['channels'] ?? []);

            foreach ($channels as $channel) {
                $channelId = trim((string) ($channel['id'] ?? ''));
                if ($channelId === '') {
                    continue;
                }

                $seen[] = $channelId;
                $count++;

                SlackChannel::updateOrCreate(
                    ['tenant_id' => $tenantId, 'channel_id' => $channelId],
                    [
                        'name' => (string) ($channel['name'] ?? $channelId),
                        'is_private' => (bool) ($channel['is_private'] ?? false),
                        'is_im' => (bool) ($channel['is_im'] ?? false),
                        'is_mpim' => (bool) ($channel['is_mpim'] ?? false),
                        'is_archived' => (bool) ($channel['is_archived'] ?? false),
                        'is_member' => (bool) ($channel['is_member'] ?? false),
                        'num_members' => isset($channel['num_members']) ? (int) $channel['num_members'] : null,
                        'topic' => (string) (($channel['topic']['value'] ?? '') ?: ''),
                        'purpose' => (string) (($channel['purpose']['value'] ?? '') ?: ''),
                        'last_message_ts' => isset($channel['latest']['ts']) ? (string) $channel['latest']['ts'] : null,
                        'last_message_at' => isset($channel['latest']['ts']) ? $this->parseSlackTimestamp((string) $channel['latest']['ts']) : null,
                        'synced_at' => now(),
                        'raw' => $channel,
                    ]
                );
            }

            $cursor = (string) ($data['response_metadata']['next_cursor'] ?? '');
            if ($cursor === '') {
                $cursor = null;
            }
        } while ($cursor !== null);

        if (!empty($seen)) {
            SlackChannel::forTenant($tenantId)
                ->whereNotIn('channel_id', array_values(array_unique($seen)))
                ->update(['is_archived' => true, 'synced_at' => now()]);
        }

        $token->update(['last_sync_at' => now()]);

        $this->log($tenantId, 'sync_channels', null, null, ['count' => $count]);
        $this->socketService->emit($tenantId, 'channels.synced', [
            'tenant_id' => $tenantId,
            'count' => $count,
        ]);

        return $count;
    }

    public function selectChannel(int $tenantId, string $channelId): SlackChannel
    {
        $channel = SlackChannel::forTenant($tenantId)->where('channel_id', $channelId)->first();
        if (!$channel) {
            throw new RuntimeException(__('slack::messages.errors.channel_not_found'));
        }

        SlackChannel::forTenant($tenantId)->update(['is_selected' => false]);
        $channel->update(['is_selected' => true]);

        SlackToken::forTenant($tenantId)->update([
            'selected_channel_id' => $channel->channel_id,
            'selected_channel_name' => $channel->name,
        ]);

        $this->log($tenantId, 'select_channel', $channel->channel_id);

        return $channel->fresh();
    }

    public function syncMessages(
        int $tenantId,
        ?string $channelId = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): int {
        $token = $this->getTokenOrFail($tenantId);
        $channelId = $this->resolveChannelId($tenantId, $channelId);
        if ($channelId === null) {
            throw new RuntimeException(__('slack::messages.errors.channel_not_selected'));
        }

        $from = $from ?: now()->subDays((int) config('slack.api.sync_days_past', 14));
        $to = $to ?: now();

        $cursor = null;
        $count = 0;
        $limit = max(1, min((int) config('slack.api.message_page_size', 50), 200));

        do {
            $payload = [
                'channel' => $channelId,
                'limit' => $limit,
                'oldest' => (string) $from->timestamp,
                'latest' => (string) $to->timestamp,
                'inclusive' => false,
            ];
            if (!empty($cursor)) {
                $payload['cursor'] = $cursor;
            }

            $data = $this->apiRequest($token, 'GET', 'conversations.history', $payload);
            $messages = (array) ($data['messages'] ?? []);

            foreach ($messages as $rawMessage) {
                $saved = $this->upsertFromSlackMessage($tenantId, $channelId, $rawMessage);
                if ($saved) {
                    $count++;
                }
            }

            $cursor = (string) ($data['response_metadata']['next_cursor'] ?? '');
            if ($cursor === '') {
                $cursor = null;
            }
        } while ($cursor !== null);

        SlackChannel::forTenant($tenantId)
            ->where('channel_id', $channelId)
            ->update(['synced_at' => now()]);

        $token->update(['last_sync_at' => now()]);

        $this->log($tenantId, 'sync_messages', $channelId, null, ['count' => $count]);
        $this->socketService->emit($tenantId, 'messages.synced', [
            'tenant_id' => $tenantId,
            'channel_id' => $channelId,
            'count' => $count,
        ]);

        return $count;
    }

    public function sendMessage(int $tenantId, array $payload): array
    {
        $token = $this->getTokenOrFail($tenantId);
        $channelId = trim((string) ($payload['channel_id'] ?? ''));
        if ($channelId === '') {
            throw new RuntimeException(__('slack::messages.errors.channel_required'));
        }

        $text = trim((string) ($payload['text'] ?? ''));
        if ($text === '') {
            throw new RuntimeException(__('slack::messages.errors.message_required'));
        }

        $body = [
            'channel' => $channelId,
            'text' => $text,
            'mrkdwn' => true,
        ];

        $threadTs = trim((string) ($payload['thread_ts'] ?? ''));
        if ($threadTs !== '') {
            $body['thread_ts'] = $threadTs;
        }

        $result = $this->apiRequest($token, 'POST', 'chat.postMessage', $body);

        $rawMessage = (array) ($result['message'] ?? []);
        if (!isset($rawMessage['ts']) && isset($result['ts'])) {
            $rawMessage['ts'] = (string) $result['ts'];
        }
        if (!isset($rawMessage['user']) && isset($result['message']['user'])) {
            $rawMessage['user'] = (string) $result['message']['user'];
        }

        $model = $this->upsertFromSlackMessage($tenantId, $channelId, $rawMessage);
        $formatted = $model ? $this->formatMessage($model) : [
            'channel_id' => $channelId,
            'slack_ts' => (string) ($result['ts'] ?? ''),
            'text' => $text,
            'username' => __('slack::messages.common.me'),
        ];

        $this->log($tenantId, 'send_message', $channelId, (string) ($formatted['slack_ts'] ?? ''), [
            'thread_ts' => $threadTs ?: null,
        ]);

        $this->socketService->emit($tenantId, 'message.created', [
            'tenant_id' => $tenantId,
            'channel_id' => $channelId,
            'message' => $formatted,
        ]);

        return $formatted;
    }

    public function getLocalMessages(int $tenantId, array $filters = []): LengthAwarePaginator
    {
        $channelId = $this->resolveChannelId($tenantId, (string) ($filters['channel_id'] ?? ''));
        if ($channelId === null) {
            return SlackMessage::query()->whereRaw('1 = 0')->paginate(20);
        }

        $query = SlackMessage::query()
            ->forTenant($tenantId)
            ->where('channel_id', $channelId)
            ->where('is_deleted', false)
            ->orderByDesc('sent_at')
            ->orderByDesc('slack_ts');

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('text', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%");
            });
        }

        $perPage = max(1, min((int) ($filters['per_page'] ?? 40), 100));

        return $query->paginate($perPage);
    }

    public function listLocalChannels(int $tenantId): Collection
    {
        return SlackChannel::query()
            ->forTenant($tenantId)
            ->orderByDesc('is_selected')
            ->orderBy('is_archived')
            ->orderBy('name')
            ->get();
    }

    public function getStats(int $tenantId): array
    {
        $token = $this->getToken($tenantId);
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $weekStart = now()->subDays(7);

        return [
            'connected' => (bool) $token,
            'team_name' => $token?->team_name,
            'team_id' => $token?->team_id,
            'selected_channel_id' => $token?->selected_channel_id,
            'selected_channel_name' => $token?->selected_channel_name,
            'channels_count' => SlackChannel::forTenant($tenantId)->count(),
            'private_channels_count' => SlackChannel::forTenant($tenantId)->where('is_private', true)->count(),
            'messages_today' => SlackMessage::forTenant($tenantId)->whereBetween('sent_at', [$todayStart, $todayEnd])->count(),
            'messages_last_7_days' => SlackMessage::forTenant($tenantId)->where('sent_at', '>=', $weekStart)->count(),
            'messages_total' => SlackMessage::forTenant($tenantId)->count(),
            'socket_enabled' => (bool) config('slack.socket.enabled', false),
            'last_sync_at' => $token?->last_sync_at?->toIso8601String(),
        ];
    }

    public function formatChannel(SlackChannel $channel): array
    {
        return [
            'id' => $channel->id,
            'channel_id' => $channel->channel_id,
            'name' => $channel->name,
            'is_private' => $channel->is_private,
            'is_im' => $channel->is_im,
            'is_mpim' => $channel->is_mpim,
            'is_archived' => $channel->is_archived,
            'is_member' => $channel->is_member,
            'is_selected' => $channel->is_selected,
            'num_members' => $channel->num_members,
            'topic' => $channel->topic,
            'purpose' => $channel->purpose,
            'last_message_ts' => $channel->last_message_ts,
            'last_message_at' => $channel->last_message_at?->toIso8601String(),
            'last_message_display' => $channel->last_message_at?->format('d/m/Y H:i'),
        ];
    }

    public function formatMessage(SlackMessage $message): array
    {
        return [
            'id' => $message->id,
            'channel_id' => $message->channel_id,
            'slack_ts' => $message->slack_ts,
            'thread_ts' => $message->thread_ts,
            'user_id' => $message->user_id,
            'username' => $message->username ?: ($message->is_bot ? __('slack::messages.common.bot') : __('slack::messages.common.user')),
            'text' => $message->text,
            'is_bot' => (bool) $message->is_bot,
            'sent_at' => $message->sent_at?->toIso8601String(),
            'sent_display' => $message->sent_at?->format('d/m/Y H:i') ?? '-',
            'edited_at' => $message->edited_at?->toIso8601String(),
            'edited_display' => $message->edited_at?->format('d/m/Y H:i'),
            'attachments' => $message->attachments ?? [],
            'reactions' => $message->reactions ?? [],
            'blocks' => $message->blocks ?? [],
        ];
    }

    private function upsertFromSlackMessage(int $tenantId, string $channelId, array $rawMessage): ?SlackMessage
    {
        $ts = trim((string) ($rawMessage['ts'] ?? ''));
        if ($ts === '') {
            return null;
        }

        $isDeleted = (string) ($rawMessage['subtype'] ?? '') === 'message_deleted'
            || (bool) ($rawMessage['hidden'] ?? false);

        $editedTs = (string) ($rawMessage['edited']['ts'] ?? '');
        $sentAt = $this->parseSlackTimestamp($ts);

        $username = trim((string) ($rawMessage['username'] ?? ''));
        if ($username === '') {
            $username = trim((string) ($rawMessage['user_profile']['display_name'] ?? ''));
        }
        if ($username === '') {
            $username = trim((string) ($rawMessage['user_profile']['real_name'] ?? ''));
        }

        $model = SlackMessage::updateOrCreate(
            ['tenant_id' => $tenantId, 'channel_id' => $channelId, 'slack_ts' => $ts],
            [
                'thread_ts' => isset($rawMessage['thread_ts']) ? (string) $rawMessage['thread_ts'] : null,
                'user_id' => isset($rawMessage['user']) ? (string) $rawMessage['user'] : null,
                'username' => $username !== '' ? $username : null,
                'text' => isset($rawMessage['text']) ? (string) $rawMessage['text'] : null,
                'blocks' => (array) ($rawMessage['blocks'] ?? []),
                'attachments' => (array) ($rawMessage['attachments'] ?? []),
                'reactions' => (array) ($rawMessage['reactions'] ?? []),
                'is_bot' => (bool) (isset($rawMessage['bot_id']) || ($rawMessage['subtype'] ?? '') === 'bot_message'),
                'is_deleted' => $isDeleted,
                'sent_at' => $sentAt,
                'edited_at' => $editedTs !== '' ? $this->parseSlackTimestamp($editedTs) : null,
                'raw' => $rawMessage,
                'updated_by' => Auth::id(),
            ]
        );

        SlackChannel::forTenant($tenantId)
            ->where('channel_id', $channelId)
            ->update([
                'last_message_ts' => $ts,
                'last_message_at' => $sentAt,
            ]);

        return $model;
    }

    private function resolveChannelId(int $tenantId, ?string $channelId): ?string
    {
        $channelId = trim((string) $channelId);
        if ($channelId !== '') {
            return $channelId;
        }

        $token = SlackToken::forTenant($tenantId)->first();
        if ($token && !empty($token->selected_channel_id)) {
            return (string) $token->selected_channel_id;
        }

        $selected = SlackChannel::forTenant($tenantId)
            ->where('is_selected', true)
            ->orderByDesc('updated_at')
            ->first();

        if ($selected) {
            return (string) $selected->channel_id;
        }

        $fallback = SlackChannel::forTenant($tenantId)
            ->where('is_archived', false)
            ->orderBy('name')
            ->first();

        return $fallback ? (string) $fallback->channel_id : null;
    }

    private function apiRequest(SlackToken $token, string $method, string $endpoint, array $payload = []): array
    {
        return $this->apiRequestWithToken((string) $token->bot_token, $method, $endpoint, $payload);
    }

    private function apiRequestWithToken(string $botToken, string $method, string $endpoint, array $payload = []): array
    {
        $method = strtoupper($method);
        $request = Http::withToken($botToken)
            ->acceptJson()
            ->timeout((int) config('slack.api.timeout', 20));

        $url = $this->apiUrl($endpoint);

        $response = $method === 'GET'
            ? $request->get($url, $payload)
            : $request->asJson()->post($url, $payload);

        if (!$response->ok()) {
            throw new RuntimeException(__('slack::messages.errors.api_failed', ['endpoint' => $endpoint, 'status' => $response->status()]));
        }

        $data = $response->json();
        if (!is_array($data) || !($data['ok'] ?? false)) {
            throw new RuntimeException($this->extractSlackError($data, __('slack::messages.errors.api_failed_generic', ['endpoint' => $endpoint])));
        }

        return $data;
    }

    private function apiUrl(string $endpoint): string
    {
        $base = rtrim((string) config('slack.api.base_url', 'https://slack.com/api'), '/');
        return $base . '/' . ltrim($endpoint, '/');
    }

    private function redirectUri(): string
    {
        $configured = trim((string) config('slack.oauth.redirect_uri', ''));
        if ($configured !== '' && Str::contains($configured, '://') && !Str::startsWith($configured, ['http://', 'https://'])) {
            throw new RuntimeException(
                __('slack::messages.errors.redirect_uri_invalid_format')
            );
        }

        $path = '/extensions/slack/oauth/callback';
        if ($configured !== '' && !Str::startsWith($configured, ['http://', 'https://'])) {
            $path = '/' . ltrim($configured, '/');
        }

        if (Str::startsWith($configured, ['http://', 'https://'])) {
            return $configured;
        }

        $request = request();
        if ($request && $request->getSchemeAndHttpHost()) {
            return rtrim((string) $request->getSchemeAndHttpHost(), '/') . $path;
        }

        $appUrl = trim((string) config('app.url', ''));
        if (Str::startsWith($appUrl, ['http://', 'https://'])) {
            return rtrim($appUrl, '/') . $path;
        }

        return 'http://127.0.0.1:8000' . $path;
    }

    private function assertWebRedirectUri(string $redirectUri): void
    {
        if (!preg_match('~^https?://~i', $redirectUri)) {
            throw new RuntimeException(
                __('slack::messages.errors.redirect_uri_invalid')
            );
        }
    }

    private function assertRedirectUriCompatibleWithSlackOauth(string $redirectUri): void
    {
        $host = strtolower((string) parse_url($redirectUri, PHP_URL_HOST));
        if ($host !== 'localhost') {
            return;
        }

        $botScopes = array_values(array_filter(array_map(
            static fn ($scope) => trim((string) $scope),
            (array) config('slack.oauth.scopes', [])
        )));

        if ($botScopes === []) {
            return;
        }

        throw new RuntimeException(
            __('slack::messages.errors.redirect_uri_localhost_bot_scopes')
        );
    }

    private function assertValidSlackClientId(string $clientId): void
    {
        if (Str::contains($clientId, 'apps.googleusercontent.com')) {
            throw new RuntimeException(
                __('slack::messages.errors.client_id_google_detected')
            );
        }
    }

    private function parseSlackTimestamp(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $seconds = (float) $value;
        if ($seconds <= 0) {
            return null;
        }

        return Carbon::createFromTimestamp((int) floor($seconds));
    }

    private function extractSlackError(mixed $data, string $fallback): string
    {
        if (is_array($data)) {
            $error = trim((string) ($data['error'] ?? ''));
            if ($error !== '') {
                return __('slack::messages.common.api_error_prefix') . ' ' . $error;
            }
        }

        return $fallback;
    }

    private function log(
        int $tenantId,
        string $action,
        ?string $channelId = null,
        ?string $messageTs = null,
        ?array $metadata = null
    ): void {
        SlackActivityLog::create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'action' => $action,
            'channel_id' => $channelId,
            'message_ts' => $messageTs,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }
}
