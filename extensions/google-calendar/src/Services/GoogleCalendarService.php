<?php

namespace Vendor\GoogleCalendar\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Vendor\Client\Models\Client as ClientModel;
use Vendor\GoogleCalendar\Models\GoogleCalendarActivityLog;
use Vendor\GoogleCalendar\Models\GoogleCalendarCalendar;
use Vendor\GoogleCalendar\Models\GoogleCalendarEvent;
use Vendor\GoogleCalendar\Models\GoogleCalendarToken;

class GoogleCalendarService
{
    private Client $oauthClient;
    private Client $apiClient;

    public function __construct()
    {
        $timeout = (int) config('google-calendar.api.timeout', 30);

        $this->oauthClient = new Client([
            'base_uri' => config('google-calendar.api.oauth_base_url', 'https://oauth2.googleapis.com/'),
            'timeout' => $timeout,
        ]);

        $this->apiClient = new Client([
            'base_uri' => config('google-calendar.api.base_url', 'https://www.googleapis.com/calendar/v3/'),
            'timeout' => $timeout,
        ]);
    }

    public function getAuthUrl(int $tenantId, int $userId): string
    {
        $clientId = (string) config('google-calendar.oauth.client_id');
        $redirectUri = $this->redirectUri();
        $scopes = (array) config('google-calendar.oauth.scopes', []);

        if ($clientId === '') {
            throw new RuntimeException(__('google-calendar::messages.errors.client_id_missing'));
        }

        $state = encrypt([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'nonce' => Str::uuid()->toString(),
            'ts' => now()->timestamp,
        ]);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return rtrim((string) config('google-calendar.api.auth_url', 'https://accounts.google.com/o/oauth2/v2/auth'), '?') . '?' . $query;
    }

    public function parseState(string $encryptedState): array
    {
        $state = decrypt($encryptedState);

        if (!is_array($state) || !isset($state['tenant_id'], $state['user_id'])) {
            throw new RuntimeException(__('google-calendar::messages.errors.invalid_oauth_state'));
        }

        return $state;
    }

    public function exchangeCode(string $code, int $tenantId, int $userId): GoogleCalendarToken
    {
        $clientId = (string) config('google-calendar.oauth.client_id');
        $clientSecret = (string) config('google-calendar.oauth.client_secret');
        $existingToken = GoogleCalendarToken::where('tenant_id', $tenantId)->first();

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException(__('google-calendar::messages.errors.oauth_credentials_missing'));
        }

        try {
            $response = $this->oauthClient->post('token', [
                'form_params' => [
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $this->redirectUri(),
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $tokenData = json_decode((string) $response->getBody(), true) ?: [];
        } catch (GuzzleException $e) {
            throw new RuntimeException(__('google-calendar::messages.errors.oauth_code_exchange', ['message' => $e->getMessage()]));
        }

        if (!empty($tokenData['error'])) {
            throw new RuntimeException((string) ($tokenData['error_description'] ?? $tokenData['error']));
        }

        $profile = $this->fetchGoogleProfile((string) ($tokenData['access_token'] ?? ''));

        $token = GoogleCalendarToken::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'connected_by' => $userId,
                'access_token' => $tokenData['access_token'] ?? '',
                // Preserve existing refresh token if Google does not return a new one.
                'refresh_token' => $tokenData['refresh_token'] ?? $existingToken?->refresh_token,
                'token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds((int) $tokenData['expires_in']) : now()->addHour(),
                'google_account_id' => $profile['id'] ?? null,
                'google_email' => $profile['email'] ?? null,
                'google_name' => $profile['name'] ?? null,
                'google_avatar_url' => $profile['picture'] ?? null,
                'is_active' => true,
                'connected_at' => now(),
                'disconnected_at' => null,
            ]
        );

        $calendars = $this->syncCalendars($tenantId);
        $selected = collect($calendars)->firstWhere('is_primary', true) ?? collect($calendars)->first();

        if ($selected) {
            $this->selectCalendar($tenantId, (string) $selected['calendar_id']);
        }

        $this->log($tenantId, 'connected', [
            'google_email' => $token->google_email,
            'google_name' => $token->google_name,
        ]);

        return $token->fresh();
    }

    public function disconnect(int $tenantId): void
    {
        $token = GoogleCalendarToken::where('tenant_id', $tenantId)->first();

        if (!$token) {
            return;
        }

        if ($token->access_token) {
            try {
                $this->oauthClient->post('revoke', [
                    'form_params' => [
                        'token' => $token->access_token,
                    ],
                ]);
            } catch (GuzzleException $e) {
                Log::warning('[GoogleCalendar] revoke token failed', ['message' => $e->getMessage()]);
            }
        }

        $token->update([
            'is_active' => false,
            'disconnected_at' => now(),
            'access_token' => '',
            'refresh_token' => null,
            'selected_calendar_id' => null,
            'selected_calendar_summary' => null,
        ]);

        GoogleCalendarCalendar::where('tenant_id', $tenantId)->update(['is_selected' => false]);

        $this->log($tenantId, 'disconnected');
    }

    public function getToken(int $tenantId): ?GoogleCalendarToken
    {
        return GoogleCalendarToken::where('tenant_id', $tenantId)->where('is_active', true)->first();
    }

    public function getTokenOrFail(int $tenantId): GoogleCalendarToken
    {
        $token = $this->getToken($tenantId);

        if (!$token) {
            throw new RuntimeException(__('google-calendar::messages.errors.not_connected'));
        }

        if ($token->is_expired) {
            $token = $this->refreshAccessToken($token);
        }

        return $token;
    }

    public function syncCalendars(int $tenantId): array
    {
        $token = $this->getTokenOrFail($tenantId);

        $response = $this->apiRequest('GET', 'users/me/calendarList', $token->access_token, [
            'query' => [
                'showHidden' => 'true',
                'minAccessRole' => 'reader',
            ],
        ]);

        $items = (array) ($response['items'] ?? []);

        $seen = [];
        foreach ($items as $item) {
            $calendarId = (string) ($item['id'] ?? '');
            if ($calendarId === '') {
                continue;
            }

            $seen[] = $calendarId;

            GoogleCalendarCalendar::updateOrCreate(
                ['tenant_id' => $tenantId, 'calendar_id' => $calendarId],
                [
                    'summary' => $item['summary'] ?? $calendarId,
                    'description' => $item['description'] ?? null,
                    'timezone' => $item['timeZone'] ?? null,
                    'background_color' => $item['backgroundColor'] ?? null,
                    'foreground_color' => $item['foregroundColor'] ?? null,
                    'access_role' => $item['accessRole'] ?? null,
                    'is_primary' => (bool) ($item['primary'] ?? false),
                    'is_holiday' => $this->isHolidayCalendarData($item),
                    'is_hidden' => (bool) ($item['hidden'] ?? false),
                    'is_deleted' => false,
                    'etag' => $item['etag'] ?? null,
                    'synced_at' => now(),
                ]
            );
        }

        GoogleCalendarCalendar::forTenant($tenantId)
            ->whereNotIn('calendar_id', $seen)
            ->update(['is_deleted' => true, 'synced_at' => now()]);

        $this->log($tenantId, 'sync_calendars', ['count' => count($items)]);

        return GoogleCalendarCalendar::forTenant($tenantId)
            ->where('is_deleted', false)
            ->orderByDesc('is_primary')
            ->orderBy('summary')
            ->get()
            ->toArray();
    }

    public function selectCalendar(int $tenantId, string $calendarId): GoogleCalendarCalendar
    {
        $calendar = GoogleCalendarCalendar::forTenant($tenantId)
            ->where('calendar_id', $calendarId)
            ->where('is_deleted', false)
            ->first();

        if (!$calendar) {
            throw new RuntimeException(__('google-calendar::messages.errors.calendar_missing'));
        }

        GoogleCalendarCalendar::forTenant($tenantId)->update(['is_selected' => false]);
        $calendar->update(['is_selected' => true]);

        GoogleCalendarToken::where('tenant_id', $tenantId)->update([
            'selected_calendar_id' => $calendar->calendar_id,
            'selected_calendar_summary' => $calendar->summary,
        ]);

        $this->log($tenantId, 'select_calendar', ['calendar_id' => $calendarId]);

        return $calendar;
    }

    public function syncEvents(
        int $tenantId,
        ?string $calendarId = null,
        ?Carbon $from = null,
        ?Carbon $to = null,
        bool $includeHolidays = true
    ): int
    {
        $token = $this->getTokenOrFail($tenantId);

        $calendarIds = $this->resolveSyncCalendarIds($tenantId, $calendarId, $includeHolidays);
        if (empty($calendarIds)) {
            throw new RuntimeException(__('google-calendar::messages.errors.no_calendar_selected'));
        }

        $from = $from ?: now()->subDays((int) config('google-calendar.api.sync_days_past', 30));
        $to = $to ?: now()->addDays((int) config('google-calendar.api.sync_days_future', 90));

        $count = 0;
        foreach ($calendarIds as $currentCalendarId) {
            $response = $this->apiRequest(
                'GET',
                'calendars/' . rawurlencode($currentCalendarId) . '/events',
                $token->access_token,
                [
                    'query' => [
                        'singleEvents' => 'true',
                        'orderBy' => 'startTime',
                        'showDeleted' => 'true',
                        'timeMin' => $from->copy()->utc()->format('c'),
                        'timeMax' => $to->copy()->utc()->format('c'),
                        'maxResults' => (int) config('google-calendar.api.page_size', 100),
                    ],
                ]
            );

            $isHolidayCalendar = GoogleCalendarCalendar::forTenant($tenantId)
                ->where('calendar_id', $currentCalendarId)
                ->value('is_holiday');

            foreach ((array) ($response['items'] ?? []) as $item) {
                $this->upsertGoogleEvent($tenantId, $currentCalendarId, $item, (bool) $isHolidayCalendar);
                $count++;
            }
        }

        GoogleCalendarToken::where('tenant_id', $tenantId)->update(['last_sync_at' => now()]);

        $this->log($tenantId, 'sync_events', [
            'calendar_ids' => $calendarIds,
            'include_holidays' => $includeHolidays,
            'count' => $count,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
        ]);

        return $count;
    }

    public function createEvent(int $tenantId, array $data): array
    {
        $token = $this->getTokenOrFail($tenantId);
        $calendarId = $this->resolveCalendarIdWithBootstrap($tenantId, (string) ($data['calendar_id'] ?? ''));

        if (!$calendarId) {
            throw new RuntimeException(__('google-calendar::messages.errors.no_google_calendar_available'));
        }

        $payload = $this->buildEventPayload($tenantId, $data);

        $result = $this->apiRequest(
            'POST',
            'calendars/' . rawurlencode($calendarId) . '/events',
            $token->access_token,
            ['json' => $payload]
        );

        $isHolidayCalendar = (bool) GoogleCalendarCalendar::forTenant($tenantId)
            ->where('calendar_id', $calendarId)
            ->value('is_holiday');

        $event = $this->upsertGoogleEvent($tenantId, $calendarId, $result, $isHolidayCalendar);

        $this->log($tenantId, 'create_event', [
            'calendar_id' => $calendarId,
            'event_id' => $event->google_event_id,
            'summary' => $event->summary,
        ]);

        return $this->formatEvent($event);
    }

    public function updateEvent(int $tenantId, string $calendarId, string $eventId, array $data): array
    {
        $token = $this->getTokenOrFail($tenantId);
        $calendarId = $this->resolveCalendarIdWithBootstrap($tenantId, $calendarId);

        if (!$calendarId) {
            throw new RuntimeException(__('google-calendar::messages.errors.no_google_calendar_available'));
        }

        $payload = $this->buildEventPayload($tenantId, $data);

        $result = $this->apiRequest(
            'PATCH',
            'calendars/' . rawurlencode($calendarId) . '/events/' . rawurlencode($eventId),
            $token->access_token,
            ['json' => $payload]
        );

        $isHolidayCalendar = (bool) GoogleCalendarCalendar::forTenant($tenantId)
            ->where('calendar_id', $calendarId)
            ->value('is_holiday');

        $event = $this->upsertGoogleEvent($tenantId, $calendarId, $result, $isHolidayCalendar);

        $this->log($tenantId, 'update_event', [
            'calendar_id' => $calendarId,
            'event_id' => $eventId,
            'summary' => $event->summary,
        ]);

        return $this->formatEvent($event);
    }

    public function deleteEvent(int $tenantId, string $calendarId, string $eventId): void
    {
        $token = $this->getTokenOrFail($tenantId);
        $calendarId = $this->resolveCalendarId($tenantId, $calendarId);

        if (!$calendarId) {
            throw new RuntimeException(__('google-calendar::messages.errors.no_calendar_selected'));
        }

        $this->apiRequest(
            'DELETE',
            'calendars/' . rawurlencode($calendarId) . '/events/' . rawurlencode($eventId),
            $token->access_token,
            []
        );

        GoogleCalendarEvent::forTenant($tenantId)
            ->where('google_calendar_id', $calendarId)
            ->where('google_event_id', $eventId)
            ->update([
                'is_deleted' => true,
                'status' => 'cancelled',
                'synced_at' => now(),
                'updated_by' => Auth::id(),
            ]);

        $this->log($tenantId, 'delete_event', [
            'calendar_id' => $calendarId,
            'event_id' => $eventId,
        ]);
    }

    public function getLocalEvents(int $tenantId, array $filters = [])
    {
        $query = GoogleCalendarEvent::query()
            ->forTenant($tenantId)
            ->where('is_deleted', false)
            ->orderByDesc('start_at');

        if (!empty($filters['calendar_id'])) {
            $query->where('google_calendar_id', $filters['calendar_id']);
        }

        if (array_key_exists('include_holidays', $filters) && !filter_var($filters['include_holidays'], FILTER_VALIDATE_BOOL)) {
            $query->where('is_holiday', false);
        }

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('summary', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['from'])) {
            $query->where('end_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (!empty($filters['to'])) {
            $query->where('start_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $query->paginate($perPage);
    }

    public function getStats(int $tenantId): array
    {
        $token = $this->getToken($tenantId);

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $futureEnd = now()->addDays(30);

        return [
            'connected' => (bool) $token,
            'google_email' => $token?->google_email,
            'google_name' => $token?->google_name,
            'connected_at' => $token?->connected_at?->toIso8601String(),
            'last_sync_at' => $token?->last_sync_at?->toIso8601String(),
            'calendars_count' => GoogleCalendarCalendar::forTenant($tenantId)->where('is_deleted', false)->count(),
            'events_today' => GoogleCalendarEvent::forTenant($tenantId)->where('is_deleted', false)->whereBetween('start_at', [$todayStart, $todayEnd])->count(),
            'events_this_month' => GoogleCalendarEvent::forTenant($tenantId)->where('is_deleted', false)->whereBetween('start_at', [$monthStart, $monthEnd])->count(),
            'events_next_30_days' => GoogleCalendarEvent::forTenant($tenantId)->where('is_deleted', false)->whereBetween('start_at', [now(), $futureEnd])->count(),
            'holiday_events_this_year' => GoogleCalendarEvent::forTenant($tenantId)
                ->where('is_deleted', false)
                ->where('is_holiday', true)
                ->whereBetween('start_at', [now()->startOfYear(), now()->endOfYear()])
                ->count(),
        ];
    }

    public function formatEvent(GoogleCalendarEvent $event): array
    {
        $start = $event->start_at;
        $end = $event->end_at;

        return [
            'id' => $event->id,
            'calendar_id' => $event->google_calendar_id,
            'event_id' => $event->google_event_id,
            'summary' => $event->summary,
            'description' => $event->description,
            'location' => $event->location,
            'client_id' => $event->client_id,
            'client_name' => $event->client_name,
            'source_type' => $event->source_type,
            'source_id' => $event->source_id,
            'source_label' => $event->source_label,
            'status' => $event->status,
            'visibility' => $event->visibility,
            'html_link' => $event->html_link,
            'all_day' => $event->all_day,
            'is_holiday' => $event->is_holiday,
            'start_at' => $start?->toIso8601String(),
            'end_at' => $end?->toIso8601String(),
            'start_display' => $this->displayDate($event->all_day, $start),
            'end_display' => $this->displayDate($event->all_day, $end, true),
            'attendees' => $event->attendees ?? [],
            'updated_at' => $event->updated_at?->toIso8601String(),
            'google_updated_at' => $event->google_updated_at?->toIso8601String(),
        ];
    }

    private function displayDate(bool $allDay, ?Carbon $date, bool $allDayEnd = false): ?string
    {
        if (!$date) {
            return null;
        }

        if (!$allDay) {
            return $date->format('d/m/Y H:i');
        }

        $display = $date->copy();
        if ($allDayEnd) {
            $display->subDay();
        }

        return $display->format('d/m/Y');
    }

    private function fetchGoogleProfile(string $accessToken): array
    {
        if ($accessToken === '') {
            return [];
        }

        try {
            $response = (new Client(['base_uri' => 'https://www.googleapis.com/']))->get('oauth2/v2/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
                'timeout' => (int) config('google-calendar.api.timeout', 30),
            ]);

            return json_decode((string) $response->getBody(), true) ?: [];
        } catch (GuzzleException $e) {
            Log::warning('[GoogleCalendar] fetch profile failed', ['message' => $e->getMessage()]);
            return [];
        }
    }

    private function refreshAccessToken(GoogleCalendarToken $token): GoogleCalendarToken
    {
        if (!$token->refresh_token) {
            $this->invalidateTokenAfterOAuthFailure($token, 'missing_refresh_token');
            throw new RuntimeException(__('google-calendar::messages.errors.refresh_token_missing'));
        }

        try {
            $response = $this->oauthClient->post('token', [
                'form_params' => [
                    'client_id' => (string) config('google-calendar.oauth.client_id'),
                    'client_secret' => (string) config('google-calendar.oauth.client_secret'),
                    'refresh_token' => $token->refresh_token,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true) ?: [];
        } catch (GuzzleException $e) {
            $payload = null;
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $payload = json_decode((string) $e->getResponse()->getBody(), true);
            }

            $oauthError = strtolower((string) ($payload['error'] ?? ''));
            $oauthDescription = (string) ($payload['error_description'] ?? '');

            if ($oauthError === 'invalid_grant') {
                $this->invalidateTokenAfterOAuthFailure($token, 'invalid_grant');
                throw new RuntimeException(__('google-calendar::messages.errors.session_expired'));
            }

            $details = $oauthDescription !== '' ? $oauthDescription : $e->getMessage();
            throw new RuntimeException(__('google-calendar::messages.errors.refresh_access_token', ['details' => $details]));
        }

        if (isset($data['error'])) {
            $oauthError = strtolower((string) $data['error']);
            $oauthDescription = (string) ($data['error_description'] ?? $data['error']);

            if ($oauthError === 'invalid_grant') {
                $this->invalidateTokenAfterOAuthFailure($token, 'invalid_grant');
                throw new RuntimeException(__('google-calendar::messages.errors.session_expired'));
            }

            throw new RuntimeException($oauthDescription);
        }

        $token->update([
            'access_token' => $data['access_token'] ?? $token->access_token,
            'refresh_token' => $data['refresh_token'] ?? $token->refresh_token,
            'token_expires_at' => isset($data['expires_in']) ? now()->addSeconds((int) $data['expires_in']) : now()->addHour(),
        ]);

        return $token->fresh();
    }

    private function invalidateTokenAfterOAuthFailure(GoogleCalendarToken $token, string $reason): void
    {
        try {
            $token->update([
                'is_active' => false,
                'disconnected_at' => now(),
                'access_token' => '',
                'refresh_token' => null,
                'selected_calendar_id' => null,
                'selected_calendar_summary' => null,
            ]);

            GoogleCalendarCalendar::forTenant((int) $token->tenant_id)->update(['is_selected' => false]);

            $this->log((int) $token->tenant_id, 'oauth_invalidated', ['reason' => $reason]);
        } catch (\Throwable $e) {
            Log::warning('[GoogleCalendar] invalidate token failed', ['message' => $e->getMessage()]);
        }
    }

    private function apiRequest(string $method, string $uri, string $accessToken, array $options = []): array
    {
        $headers = array_merge([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
        ], $options['headers'] ?? []);

        $requestOptions = Arr::except($options, ['headers']);
        $requestOptions['headers'] = $headers;

        try {
            $response = $this->apiClient->request($method, ltrim($uri, '/'), $requestOptions);
            $body = (string) $response->getBody();
            if ($body === '') {
                return [];
            }
            return json_decode($body, true) ?: [];
        } catch (GuzzleException $e) {
            $message = $e->getMessage();
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $payload = json_decode((string) $e->getResponse()->getBody(), true);
                $apiMessage = $payload['error']['message'] ?? null;
                if ($apiMessage) {
                    $message = $apiMessage;
                }
            }

            throw new RuntimeException(__('google-calendar::messages.errors.api', ['message' => $message]));
        }
    }

    private function buildEventPayload(int $tenantId, array $data): array
    {
        $timezone = (string) ($data['timezone'] ?? config('google-calendar.defaults.timezone', 'UTC'));
        $allDay = filter_var($data['all_day'] ?? false, FILTER_VALIDATE_BOOL);

        $startAt = Carbon::parse((string) $data['start_at'], $timezone);
        $endAt = Carbon::parse((string) $data['end_at'], $timezone);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            throw new RuntimeException(__('google-calendar::messages.validation.end_after_start'));
        }

        $attendees = [];
        $attendeesRaw = trim((string) ($data['attendees'] ?? ''));
        if ($attendeesRaw !== '') {
            $emails = collect(explode(',', $attendeesRaw))
                ->map(fn ($v) => trim($v))
                ->filter(fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL))
                ->unique()
                ->values();

            $attendees = $emails->map(fn ($email) => ['email' => $email])->all();
        }

        $payload = [
            'summary' => (string) ($data['summary'] ?? ''),
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'visibility' => $data['visibility'] ?? null,
            'transparency' => $data['transparency'] ?? null,
            'colorId' => $data['color_id'] ?? null,
        ];

        if ($allDay) {
            $startDate = $startAt->toDateString();
            $endDateExclusive = $endAt->copy()->startOfDay()->addDay();

            $payload['start'] = ['date' => $startDate];
            $payload['end'] = ['date' => $endDateExclusive->toDateString()];
        } else {
            $payload['start'] = [
                'dateTime' => $startAt->toRfc3339String(),
                'timeZone' => $timezone,
            ];

            $payload['end'] = [
                'dateTime' => $endAt->toRfc3339String(),
                'timeZone' => $timezone,
            ];
        }

        if (!empty($attendees)) {
            $payload['attendees'] = $attendees;
        }

        if (!empty($data['reminder_minutes'])) {
            $payload['reminders'] = [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => (int) $data['reminder_minutes']],
                ],
            ];
        }

        if (!empty($data['recurrence'])) {
            $payload['recurrence'] = [(string) $data['recurrence']];
        }

        $crmPrivate = [];

        $clientId = (int) ($data['client_id'] ?? 0);
        if ($clientId > 0) {
            $clientName = null;

            if (class_exists(ClientModel::class) && Schema::hasTable('clients')) {
                $client = ClientModel::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $clientId)
                    ->first();

                if (!$client) {
                    throw new RuntimeException(__('google-calendar::messages.errors.client_not_found'));
                }

                $clientName = (string) $client->company_name;
            }

            $crmPrivate['crm_client_id'] = (string) $clientId;
            if ($clientName !== null && $clientName !== '') {
                $crmPrivate['crm_client_name'] = $clientName;
            }
        }

        $sourceType = trim((string) ($data['source_type'] ?? ''));
        if (in_array($sourceType, ['project', 'project_task', 'manual'], true)) {
            $crmPrivate['crm_source_type'] = $sourceType;
        }

        $sourceId = (int) ($data['source_id'] ?? 0);
        if ($sourceId > 0) {
            $crmPrivate['crm_source_id'] = (string) $sourceId;
        }

        $sourceLabel = trim((string) ($data['source_label'] ?? ''));
        if ($sourceLabel !== '') {
            $crmPrivate['crm_source_label'] = $sourceLabel;
        }

        if (!empty($crmPrivate)) {
            $payload['extendedProperties'] = [
                'private' => $crmPrivate,
            ];
        }

        return array_filter($payload, static fn ($value) => $value !== null && $value !== '');
    }

    private function upsertGoogleEvent(int $tenantId, string $calendarId, array $raw, bool $isHoliday = false): GoogleCalendarEvent
    {
        $eventId = (string) ($raw['id'] ?? '');
        if ($eventId === '') {
            throw new RuntimeException(__('google-calendar::messages.errors.google_event_id_missing'));
        }

        $allDay = isset($raw['start']['date']) && !isset($raw['start']['dateTime']);

        $startAt = $allDay
            ? Carbon::parse((string) $raw['start']['date'], 'UTC')->startOfDay()
            : Carbon::parse((string) ($raw['start']['dateTime'] ?? now()->toRfc3339String()));

        $endAt = $allDay
            ? Carbon::parse((string) $raw['end']['date'], 'UTC')->startOfDay()
            : Carbon::parse((string) ($raw['end']['dateTime'] ?? now()->toRfc3339String()));
        $crmMeta = $this->extractCrmMeta($raw);

        $updateData = [
            'ical_uid' => $raw['iCalUID'] ?? null,
            'summary' => $raw['summary'] ?? __('google-calendar::messages.common.no_title'),
            'description' => $raw['description'] ?? null,
            'location' => $raw['location'] ?? null,
            'status' => $raw['status'] ?? null,
            'visibility' => $raw['visibility'] ?? null,
            'transparency' => $raw['transparency'] ?? null,
            'html_link' => $raw['htmlLink'] ?? null,
            'color_id' => $raw['colorId'] ?? null,
            'all_day' => $allDay,
            'is_holiday' => $isHoliday,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'start_timezone' => $raw['start']['timeZone'] ?? null,
            'end_timezone' => $raw['end']['timeZone'] ?? null,
            'recurrence' => $raw['recurrence'] ?? null,
            'attendees' => $raw['attendees'] ?? null,
            'reminders' => $raw['reminders'] ?? null,
            'conference_data' => $raw['conferenceData'] ?? null,
            'organizer_email' => $raw['organizer']['email'] ?? null,
            'organizer_name' => $raw['organizer']['displayName'] ?? null,
            'creator_email' => $raw['creator']['email'] ?? null,
            'creator_name' => $raw['creator']['displayName'] ?? null,
            'sequence' => $raw['sequence'] ?? null,
            'etag' => $raw['etag'] ?? null,
            'google_created_at' => !empty($raw['created']) ? Carbon::parse((string) $raw['created']) : null,
            'google_updated_at' => !empty($raw['updated']) ? Carbon::parse((string) $raw['updated']) : null,
            'synced_at' => now(),
            'is_deleted' => ($raw['status'] ?? null) === 'cancelled',
            'updated_by' => Auth::id(),
            'created_by' => Auth::id(),
        ];

        if (Schema::hasColumn('google_calendar_events', 'client_id')) {
            $updateData['client_id'] = $crmMeta['client_id'];
            $updateData['client_name'] = $crmMeta['client_name'];
            $updateData['source_type'] = $crmMeta['source_type'];
            $updateData['source_id'] = $crmMeta['source_id'];
            $updateData['source_label'] = $crmMeta['source_label'];
        }

        $event = GoogleCalendarEvent::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'google_calendar_id' => $calendarId,
                'google_event_id' => $eventId,
            ],
            $updateData
        );

        return $event;
    }

    private function extractCrmMeta(array $raw): array
    {
        $private = Arr::get($raw, 'extendedProperties.private', []);
        if (!is_array($private)) {
            $private = [];
        }

        $clientId = (int) ($private['crm_client_id'] ?? 0);
        $sourceId = (int) ($private['crm_source_id'] ?? 0);
        $sourceType = trim((string) ($private['crm_source_type'] ?? ''));

        if (!in_array($sourceType, ['project', 'project_task', 'manual'], true)) {
            $sourceType = null;
        }

        return [
            'client_id' => $clientId > 0 ? $clientId : null,
            'client_name' => trim((string) ($private['crm_client_name'] ?? '')) ?: null,
            'source_type' => $sourceType,
            'source_id' => $sourceId > 0 ? $sourceId : null,
            'source_label' => trim((string) ($private['crm_source_label'] ?? '')) ?: null,
        ];
    }

    private function resolveCalendarId(int $tenantId, ?string $calendarId = null): ?string
    {
        if ($calendarId) {
            return $calendarId;
        }

        $token = GoogleCalendarToken::where('tenant_id', $tenantId)->first();
        if ($token?->selected_calendar_id) {
            return $token->selected_calendar_id;
        }

        $selected = GoogleCalendarCalendar::forTenant($tenantId)->where('is_selected', true)->first();
        if ($selected) {
            return $selected->calendar_id;
        }

        $primary = GoogleCalendarCalendar::forTenant($tenantId)->where('is_primary', true)->first();
        if ($primary) {
            return $primary->calendar_id;
        }

        return GoogleCalendarCalendar::forTenant($tenantId)
            ->where('is_deleted', false)
            ->orderBy('summary')
            ->value('calendar_id');
    }

    private function resolveCalendarIdWithBootstrap(int $tenantId, ?string $calendarId = null): ?string
    {
        $resolved = $this->resolveCalendarId($tenantId, $calendarId);
        if ($resolved) {
            return $resolved;
        }

        try {
            $this->syncCalendars($tenantId);
        } catch (\Throwable $e) {
            Log::warning('[GoogleCalendar] resolve calendar bootstrap failed', [
                'tenant_id' => $tenantId,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->resolveCalendarId($tenantId, $calendarId);
    }

    private function resolveSyncCalendarIds(int $tenantId, ?string $calendarId = null, bool $includeHolidays = true): array
    {
        if ($calendarId) {
            $base = [$calendarId];
        } else {
            $resolved = $this->resolveCalendarId($tenantId, null);
            $base = $resolved ? [$resolved] : [];
        }

        if (!$includeHolidays) {
            return array_values(array_unique($base));
        }

        $holidayIds = GoogleCalendarCalendar::forTenant($tenantId)
            ->where('is_deleted', false)
            ->where('is_holiday', true)
            ->pluck('calendar_id')
            ->all();

        return array_values(array_unique(array_merge($base, $holidayIds)));
    }

    private function isHolidayCalendarData(array $item): bool
    {
        $id = strtolower((string) ($item['id'] ?? ''));
        $summary = strtolower((string) ($item['summary'] ?? ''));
        $description = strtolower((string) ($item['description'] ?? ''));

        if (str_contains($id, '#holiday@group.v.calendar.google.com')) {
            return true;
        }

        foreach (['holiday', 'holidays', 'jours feries', 'jours fériés', 'feriados', 'feste', 'feiertage'] as $needle) {
            if (str_contains($summary, $needle) || str_contains($description, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function redirectUri(): string
    {
        $path = (string) config('google-calendar.oauth.redirect_uri', '/extensions/google-calendar/oauth/callback');
        if (trim($path) === '') {
            $path = '/extensions/google-calendar/oauth/callback';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url($path);
    }

    private function log(int $tenantId, string $action, array $metadata = [], ?string $calendarId = null, ?string $eventId = null): void
    {
        try {
            GoogleCalendarActivityLog::create([
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'action' => $action,
                'calendar_id' => $calendarId,
                'event_id' => $eventId,
                'metadata' => $metadata,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[GoogleCalendar] log failed', ['message' => $e->getMessage()]);
        }
    }
}
