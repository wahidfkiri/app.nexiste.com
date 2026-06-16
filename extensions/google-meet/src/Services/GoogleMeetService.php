<?php

namespace NexusExtensions\GoogleMeet\Services;

use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Oauth2;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NexusExtensions\GoogleMeet\Models\GoogleMeetActivityLog;
use NexusExtensions\GoogleMeet\Models\GoogleMeetCalendar;
use NexusExtensions\GoogleMeet\Models\GoogleMeetMeeting;
use NexusExtensions\GoogleMeet\Models\GoogleMeetToken;
use RuntimeException;
use Throwable;

class GoogleMeetService
{
    private ?GoogleClient $client = null;
    private ?Calendar $calendarService = null;

    public function makeClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId((string) config('google-meet.oauth.client_id'));
        $client->setClientSecret((string) config('google-meet.oauth.client_secret'));
        $client->setRedirectUri($this->redirectUri());
        $client->setScopes((array) config('google-meet.oauth.scopes', []));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        return $client;
    }

    public function getAuthUrl(int $tenantId, int $userId): string
    {
        $clientId = (string) config('google-meet.oauth.client_id');
        if ($clientId === '') {
            throw new RuntimeException(__('google-meet::messages.errors.client_id_missing'));
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
            throw new RuntimeException(__('google-meet::messages.errors.invalid_oauth_state'));
        }

        return $state;
    }

    public function exchangeCode(string $code, int $tenantId, int $userId): GoogleMeetToken
    {
        $client = $this->makeClient();
        $tokenData = $client->fetchAccessTokenWithAuthCode($code);
        $existingToken = GoogleMeetToken::forTenant($tenantId)->first();

        if (isset($tokenData['error'])) {
            throw new RuntimeException((string) ($tokenData['error_description'] ?? $tokenData['error']));
        }

        $client->setAccessToken($tokenData);

        $oauth2 = new Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        $token = GoogleMeetToken::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'connected_by' => $userId,
                'access_token' => $tokenData['access_token'] ?? '',
                'refresh_token' => $tokenData['refresh_token'] ?? $existingToken?->refresh_token,
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds((int) $tokenData['expires_in'])
                    : now()->addHour(),
                'google_account_id' => (string) ($userInfo->getId() ?? ''),
                'google_email' => (string) ($userInfo->getEmail() ?? ''),
                'google_name' => (string) ($userInfo->getName() ?? ''),
                'google_avatar_url' => (string) ($userInfo->getPicture() ?? ''),
                'is_active' => true,
                'connected_at' => now(),
                'disconnected_at' => null,
            ]
        );

        $this->client = $client;
        $this->calendarService = new Calendar($client);

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
        $token = GoogleMeetToken::forTenant($tenantId)->first();
        if (!$token) {
            return;
        }

        try {
            $client = $this->makeClient();
            if ($token->access_token) {
                $client->revokeToken($token->access_token);
            }
        } catch (Throwable $e) {
            Log::warning('[GoogleMeet] token revoke failed', ['message' => $e->getMessage()]);
        }

        $token->update([
            'is_active' => false,
            'disconnected_at' => now(),
            'access_token' => '',
            'refresh_token' => null,
            'selected_calendar_id' => null,
            'selected_calendar_summary' => null,
        ]);

        GoogleMeetCalendar::forTenant($tenantId)->update(['is_selected' => false]);

        $this->log($tenantId, 'disconnected');
    }

    public function getToken(int $tenantId): ?GoogleMeetToken
    {
        return GoogleMeetToken::forTenant($tenantId)->active()->first();
    }

    public function getTokenOrFail(int $tenantId): GoogleMeetToken
    {
        $token = $this->getToken($tenantId);
        if (!$token) {
            throw new RuntimeException(__('google-meet::messages.errors.not_connected'));
        }

        return $token;
    }

    public function getCalendarService(int $tenantId): Calendar
    {
        $token = $this->getTokenOrFail($tenantId);

        $client = $this->makeClient();
        $client->setAccessToken($token->toGoogleToken());

        if ($token->is_expired) {
            if (!$token->refresh_token) {
                $this->invalidateTokenAfterOAuthFailure($token, 'missing_refresh_token');
                throw new RuntimeException(__('google-meet::messages.errors.session_expired'));
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($token->refresh_token);
            if (isset($newToken['error'])) {
                if ($this->isRevokedOrExpiredOAuthError(
                    (string) ($newToken['error'] ?? ''),
                    (string) ($newToken['error_description'] ?? '')
                )) {
                    $this->invalidateTokenAfterOAuthFailure($token, 'invalid_grant');
                    throw new RuntimeException(__('google-meet::messages.errors.session_expired'));
                }
                throw new RuntimeException((string) ($newToken['error_description'] ?? $newToken['error']));
            }

            $token->update([
                'access_token' => $newToken['access_token'] ?? $token->access_token,
                'refresh_token' => $newToken['refresh_token'] ?? $token->refresh_token,
                'token_expires_at' => now()->addSeconds((int) ($newToken['expires_in'] ?? 3600)),
            ]);

            $client->setAccessToken($token->toGoogleToken());
        }

        $this->client = $client;
        $this->calendarService = new Calendar($client);

        return $this->calendarService;
    }

    public function syncCalendars(int $tenantId): array
    {
        $calendarService = $this->getCalendarService($tenantId);

        try {
            $list = $calendarService->calendarList->listCalendarList([
                'showHidden' => true,
                'minAccessRole' => 'reader',
            ]);

            $items = (array) ($list->getItems() ?? []);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e);
        }

        $seen = [];
        foreach ($items as $item) {
            $calendarId = trim((string) ($item->getId() ?? ''));
            if ($calendarId === '') {
                continue;
            }

            $seen[] = $calendarId;

            GoogleMeetCalendar::updateOrCreate(
                ['tenant_id' => $tenantId, 'calendar_id' => $calendarId],
                [
                    'summary' => (string) ($item->getSummary() ?? $calendarId),
                    'description' => $item->getDescription(),
                    'timezone' => $item->getTimeZone(),
                    'background_color' => $item->getBackgroundColor(),
                    'foreground_color' => $item->getForegroundColor(),
                    'access_role' => $item->getAccessRole(),
                    'is_primary' => (bool) $item->getPrimary(),
                    'is_hidden' => (bool) $item->getHidden(),
                    'is_deleted' => false,
                    'etag' => $item->getEtag(),
                    'synced_at' => now(),
                ]
            );
        }

        GoogleMeetCalendar::forTenant($tenantId)
            ->whereNotIn('calendar_id', $seen)
            ->update(['is_deleted' => true, 'synced_at' => now()]);

        $this->log($tenantId, 'sync_calendars', ['count' => count($items)]);

        return GoogleMeetCalendar::forTenant($tenantId)
            ->where('is_deleted', false)
            ->orderByDesc('is_selected')
            ->orderByDesc('is_primary')
            ->orderBy('summary')
            ->get()
            ->toArray();
    }

    public function selectCalendar(int $tenantId, string $calendarId): GoogleMeetCalendar
    {
        $calendar = GoogleMeetCalendar::forTenant($tenantId)
            ->where('calendar_id', $calendarId)
            ->where('is_deleted', false)
            ->first();

        if (!$calendar) {
            throw new RuntimeException(__('google-meet::messages.errors.calendar_missing'));
        }

        GoogleMeetCalendar::forTenant($tenantId)->update(['is_selected' => false]);
        $calendar->update(['is_selected' => true]);

        GoogleMeetToken::forTenant($tenantId)->update([
            'selected_calendar_id' => $calendar->calendar_id,
            'selected_calendar_summary' => $calendar->summary,
        ]);

        $this->log($tenantId, 'select_calendar', ['calendar_id' => $calendarId]);

        return $calendar;
    }

    public function syncMeetings(
        int $tenantId,
        ?string $calendarId = null,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): int {
        $calendarService = $this->getCalendarService($tenantId);
        $calendarIds = $this->resolveSyncCalendarIds($tenantId, $calendarId);

        if (empty($calendarIds)) {
            throw new RuntimeException(__('google-meet::messages.errors.no_calendar_selected'));
        }

        $from = $from ?: now()->subDays((int) config('google-meet.api.sync_days_past', 30));
        $to = $to ?: now()->addDays((int) config('google-meet.api.sync_days_future', 90));

        $count = 0;

        foreach ($calendarIds as $currentCalendarId) {
            $pageToken = null;

            do {
                try {
                    $response = $calendarService->events->listEvents($currentCalendarId, [
                        'singleEvents' => true,
                        'orderBy' => 'startTime',
                        'showDeleted' => true,
                        'timeMin' => $from->copy()->utc()->format('c'),
                        'timeMax' => $to->copy()->utc()->format('c'),
                        'maxResults' => (int) config('google-meet.api.page_size', 100),
                        'pageToken' => $pageToken,
                    ]);

                    $items = (array) ($response->getItems() ?? []);
                    $pageToken = $response->getNextPageToken();
                } catch (Throwable $e) {
                    throw $this->translateGoogleApiException($e);
                }

                foreach ($items as $item) {
                    $this->handleSyncedEvent($tenantId, $currentCalendarId, $item);
                    $count++;
                }
            } while (!empty($pageToken));
        }

        GoogleMeetToken::forTenant($tenantId)->update(['last_sync_at' => now()]);

        $this->log($tenantId, 'sync_meetings', [
            'calendar_ids' => $calendarIds,
            'count' => $count,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
        ]);

        return $count;
    }

    public function createMeeting(int $tenantId, array $data): array
    {
        $calendarService = $this->getCalendarService($tenantId);
        $calendarId = $this->resolveCalendarId($tenantId, (string) ($data['calendar_id'] ?? ''));

        if (!$calendarId) {
            throw new RuntimeException(__('google-meet::messages.errors.no_calendar_selected'));
        }

        $payload = $this->buildMeetingPayload($data, true);

        try {
            $event = new GoogleCalendarEvent($payload);
            $created = $calendarService->events->insert($calendarId, $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => (string) ($data['send_updates'] ?? config('google-meet.defaults.send_updates', 'all')),
            ]);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e);
        }

        $meeting = $this->upsertGoogleMeeting($tenantId, $calendarId, $created);

        $this->log($tenantId, 'create_meeting', [
            'calendar_id' => $calendarId,
            'event_id' => $meeting->google_event_id,
            'meet_link' => $meeting->meet_link,
        ], $calendarId, $meeting->google_event_id);

        return $this->formatMeeting($meeting);
    }

    public function updateMeeting(int $tenantId, string $calendarId, string $eventId, array $data): array
    {
        $calendarService = $this->getCalendarService($tenantId);
        $calendarId = $this->resolveCalendarId($tenantId, $calendarId);

        if (!$calendarId) {
            throw new RuntimeException(__('google-meet::messages.errors.no_calendar_selected'));
        }

        try {
            $current = $calendarService->events->get($calendarId, $eventId, ['conferenceDataVersion' => 1]);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e, $eventId);
        }

        $createMeetLink = filter_var($data['create_meet_link'] ?? false, FILTER_VALIDATE_BOOL);
        $hasMeetLink = $this->extractMeetLinkFromEvent($current) !== null;

        $payload = $this->buildMeetingPayload($data, $createMeetLink && !$hasMeetLink);

        try {
            $patchEvent = new GoogleCalendarEvent($payload);
            $updated = $calendarService->events->patch($calendarId, $eventId, $patchEvent, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => (string) ($data['send_updates'] ?? config('google-meet.defaults.send_updates', 'all')),
            ]);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e, $eventId);
        }

        $meeting = $this->upsertGoogleMeeting($tenantId, $calendarId, $updated);

        $this->log($tenantId, 'update_meeting', [
            'calendar_id' => $calendarId,
            'event_id' => $eventId,
        ], $calendarId, $eventId);

        return $this->formatMeeting($meeting);
    }

    public function deleteMeeting(int $tenantId, string $calendarId, string $eventId): void
    {
        $calendarService = $this->getCalendarService($tenantId);
        $calendarId = $this->resolveCalendarId($tenantId, $calendarId);

        if (!$calendarId) {
            throw new RuntimeException(__('google-meet::messages.errors.no_calendar_selected'));
        }

        try {
            $calendarService->events->delete($calendarId, $eventId, [
                'sendUpdates' => 'all',
            ]);
        } catch (Throwable $e) {
            throw $this->translateGoogleApiException($e, $eventId);
        }

        GoogleMeetMeeting::forTenant($tenantId)
            ->where('google_calendar_id', $calendarId)
            ->where('google_event_id', $eventId)
            ->update([
                'is_deleted' => true,
                'status' => 'cancelled',
                'synced_at' => now(),
                'updated_by' => Auth::id(),
            ]);

        $this->log($tenantId, 'delete_meeting', [
            'calendar_id' => $calendarId,
            'event_id' => $eventId,
        ], $calendarId, $eventId);
    }

    public function getLocalMeetings(int $tenantId, array $filters = [])
    {
        $query = GoogleMeetMeeting::query()
            ->forTenant($tenantId)
            ->where('is_deleted', false)
            ->orderBy('start_at');

        if (!empty($filters['calendar_id'])) {
            $query->where('google_calendar_id', (string) $filters['calendar_id']);
        }

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('summary', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%")
                    ->orWhere('organizer_email', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['from'])) {
            $query->where('end_at', '>=', Carbon::parse((string) $filters['from'])->startOfDay());
        }

        if (!empty($filters['to'])) {
            $query->where('start_at', '<=', Carbon::parse((string) $filters['to'])->endOfDay());
        }

        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $query->paginate($perPage);
    }

    public function getStats(int $tenantId): array
    {
        $token = $this->getToken($tenantId);

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $weekEnd = now()->addDays(7)->endOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return [
            'connected' => (bool) $token,
            'google_email' => $token?->google_email,
            'google_name' => $token?->google_name,
            'connected_at' => $token?->connected_at?->toIso8601String(),
            'last_sync_at' => $token?->last_sync_at?->toIso8601String(),
            'calendars_count' => GoogleMeetCalendar::forTenant($tenantId)
                ->where('is_deleted', false)
                ->count(),
            'meetings_today' => GoogleMeetMeeting::forTenant($tenantId)
                ->where('is_deleted', false)
                ->whereBetween('start_at', [$todayStart, $todayEnd])
                ->count(),
            'meetings_next_7_days' => GoogleMeetMeeting::forTenant($tenantId)
                ->where('is_deleted', false)
                ->whereBetween('start_at', [now(), $weekEnd])
                ->count(),
            'meetings_this_month' => GoogleMeetMeeting::forTenant($tenantId)
                ->where('is_deleted', false)
                ->whereBetween('start_at', [$monthStart, $monthEnd])
                ->count(),
            'active_meet_links' => GoogleMeetMeeting::forTenant($tenantId)
                ->where('is_deleted', false)
                ->whereNotNull('meet_link')
                ->count(),
        ];
    }

    public function formatMeeting(GoogleMeetMeeting $meeting): array
    {
        return [
            'id' => $meeting->id,
            'calendar_id' => $meeting->google_calendar_id,
            'event_id' => $meeting->google_event_id,
            'summary' => $meeting->summary,
            'description' => $meeting->description,
            'location' => $meeting->location,
            'status' => $meeting->status,
            'visibility' => $meeting->visibility,
            'html_link' => $meeting->html_link,
            'meet_link' => $meeting->meet_link,
            'conference_id' => $meeting->conference_id,
            'conference_type' => $meeting->conference_type,
            'start_at' => $meeting->start_at?->toIso8601String(),
            'end_at' => $meeting->end_at?->toIso8601String(),
            'start_display' => $meeting->start_at?->format('d/m/Y H:i'),
            'end_display' => $meeting->end_at?->format('d/m/Y H:i'),
            'attendees' => $meeting->attendees ?? [],
            'organizer_email' => $meeting->organizer_email,
            'updated_at' => $meeting->updated_at?->toIso8601String(),
            'google_updated_at' => $meeting->google_updated_at?->toIso8601String(),
        ];
    }

    private function buildMeetingPayload(array $data, bool $createMeetLink): array
    {
        $timezone = (string) ($data['timezone'] ?? config('google-meet.defaults.timezone', 'Europe/Paris'));

        $startAt = Carbon::parse((string) $data['start_at'], $timezone);
        $endAt = Carbon::parse((string) $data['end_at'], $timezone);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            throw new RuntimeException(__('google-meet::messages.errors.end_after_start'));
        }

        $attendees = $this->parseAttendees((string) ($data['attendees'] ?? ''));

        $payload = [
            'summary' => (string) ($data['summary'] ?? ''),
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'visibility' => $data['visibility'] ?? null,
            'start' => [
                'dateTime' => $startAt->toRfc3339String(),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $endAt->toRfc3339String(),
                'timeZone' => $timezone,
            ],
        ];

        if (!empty($attendees)) {
            $payload['attendees'] = $attendees;
        }

        if ($createMeetLink) {
            $payload['conferenceData'] = [
                'createRequest' => [
                    'requestId' => Str::uuid()->toString(),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ];
        }

        return array_filter($payload, static fn ($value) => $value !== null && $value !== '');
    }

    private function parseAttendees(string $raw): array
    {
        $emails = collect(preg_split('/[,;\n]+/', $raw) ?: [])
            ->map(fn ($v) => trim((string) $v))
            ->map(function (string $v): string {
                if (preg_match('/<([^>]+)>/', $v, $matches) === 1) {
                    return trim((string) $matches[1]);
                }

                return trim($v, " \t\n\r\0\x0B\"'");
            })
            ->filter(fn (string $v) => $v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL))
            ->map(fn (string $v) => Str::lower($v))
            ->unique()
            ->values();

        return $emails->map(fn (string $email) => ['email' => $email])->all();
    }

    private function resolveCalendarId(int $tenantId, ?string $calendarId = null): ?string
    {
        $candidate = trim((string) ($calendarId ?? ''));
        if ($candidate !== '') {
            return $candidate;
        }

        $token = GoogleMeetToken::forTenant($tenantId)->first();
        if ($token?->selected_calendar_id) {
            return (string) $token->selected_calendar_id;
        }

        $selected = GoogleMeetCalendar::forTenant($tenantId)
            ->where('is_selected', true)
            ->where('is_deleted', false)
            ->first();

        if ($selected) {
            return (string) $selected->calendar_id;
        }

        $primary = GoogleMeetCalendar::forTenant($tenantId)
            ->where('is_primary', true)
            ->where('is_deleted', false)
            ->first();

        if ($primary) {
            return (string) $primary->calendar_id;
        }

        return GoogleMeetCalendar::forTenant($tenantId)
            ->where('is_deleted', false)
            ->orderBy('summary')
            ->value('calendar_id');
    }

    private function resolveSyncCalendarIds(int $tenantId, ?string $calendarId = null): array
    {
        $candidate = trim((string) ($calendarId ?? ''));
        if ($candidate !== '') {
            return [$candidate];
        }

        $resolved = $this->resolveCalendarId($tenantId, null);

        return $resolved ? [$resolved] : [];
    }

    private function handleSyncedEvent(int $tenantId, string $calendarId, GoogleCalendarEvent $item): void
    {
        $eventId = trim((string) ($item->getId() ?? ''));
        if ($eventId === '') {
            return;
        }

        $existing = GoogleMeetMeeting::forTenant($tenantId)
            ->where('google_calendar_id', $calendarId)
            ->where('google_event_id', $eventId)
            ->first();

        $meetLink = $this->extractMeetLinkFromEvent($item);
        $status = strtolower((string) ($item->getStatus() ?? ''));

        if (!$meetLink && $status !== 'cancelled') {
            if ($existing) {
                $existing->update([
                    'is_deleted' => true,
                    'status' => 'cancelled',
                    'synced_at' => now(),
                    'updated_by' => Auth::id(),
                ]);
            }
            return;
        }

        $this->upsertGoogleMeeting($tenantId, $calendarId, $item);
    }

    private function upsertGoogleMeeting(int $tenantId, string $calendarId, GoogleCalendarEvent $event): GoogleMeetMeeting
    {
        $eventId = trim((string) ($event->getId() ?? ''));
        if ($eventId === '') {
            throw new RuntimeException(__('google-meet::messages.errors.event_id_missing'));
        }

        $start = $event->getStart();
        $end = $event->getEnd();

        $startAt = null;
        $endAt = null;

        if ($start?->getDateTime()) {
            $startAt = Carbon::parse((string) $start->getDateTime());
        } elseif ($start?->getDate()) {
            $startAt = Carbon::parse((string) $start->getDate(), 'UTC')->startOfDay();
        }

        if ($end?->getDateTime()) {
            $endAt = Carbon::parse((string) $end->getDateTime());
        } elseif ($end?->getDate()) {
            $endAt = Carbon::parse((string) $end->getDate(), 'UTC')->startOfDay();
        }

        $conferenceData = $event->getConferenceData();
        $conferenceArr = $conferenceData ? json_decode(json_encode($conferenceData), true) : null;

        $meeting = GoogleMeetMeeting::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'google_calendar_id' => $calendarId,
                'google_event_id' => $eventId,
            ],
            [
                'ical_uid' => $event->getICalUID(),
                'summary' => (string) ($event->getSummary() ?? __('google-meet::messages.common.no_title')),
                'description' => $event->getDescription(),
                'location' => $event->getLocation(),
                'status' => $event->getStatus(),
                'visibility' => $event->getVisibility(),
                'html_link' => $event->getHtmlLink(),
                'meet_link' => $this->extractMeetLinkFromEvent($event),
                'conference_id' => $conferenceData?->getConferenceId(),
                'conference_type' => $conferenceData?->getConferenceSolution()?->getKey()?->getType(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'start_timezone' => $start?->getTimeZone(),
                'end_timezone' => $end?->getTimeZone(),
                'attendees' => $event->getAttendees() ? json_decode(json_encode($event->getAttendees()), true) : null,
                'conference_data' => $conferenceArr,
                'metadata' => [
                    'hangout_link' => $event->getHangoutLink(),
                ],
                'organizer_email' => $event->getOrganizer()?->getEmail(),
                'organizer_name' => $event->getOrganizer()?->getDisplayName(),
                'creator_email' => $event->getCreator()?->getEmail(),
                'creator_name' => $event->getCreator()?->getDisplayName(),
                'sequence' => $event->getSequence(),
                'etag' => $event->getEtag(),
                'google_created_at' => $event->getCreated() ? Carbon::parse((string) $event->getCreated()) : null,
                'google_updated_at' => $event->getUpdated() ? Carbon::parse((string) $event->getUpdated()) : null,
                'synced_at' => now(),
                'is_deleted' => strtolower((string) ($event->getStatus() ?? '')) === 'cancelled',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );

        return $meeting;
    }

    private function extractMeetLinkFromEvent(GoogleCalendarEvent $event): ?string
    {
        $hangout = trim((string) ($event->getHangoutLink() ?? ''));
        if ($hangout !== '') {
            return $hangout;
        }

        $conferenceData = $event->getConferenceData();
        if (!$conferenceData) {
            return null;
        }

        $entryPoints = $conferenceData->getEntryPoints() ?? [];
        foreach ($entryPoints as $entry) {
            $type = strtolower((string) ($entry->getEntryPointType() ?? ''));
            $uri = trim((string) ($entry->getUri() ?? ''));
            if ($uri === '') {
                continue;
            }

            if ($type === 'video' || str_contains($uri, 'meet.google.com')) {
                return $uri;
            }
        }

        return null;
    }

    private function redirectUri(): string
    {
        $path = (string) config('google-meet.oauth.redirect_uri', '/extensions/google-meet/oauth/callback');
        if (trim($path) === '') {
            $path = '/extensions/google-meet/oauth/callback';
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

    private function invalidateTokenAfterOAuthFailure(GoogleMeetToken $token, string $reason): void
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

            GoogleMeetCalendar::forTenant((int) $token->tenant_id)->update(['is_selected' => false]);

            $this->log((int) $token->tenant_id, 'oauth_invalidated', ['reason' => $reason]);
        } catch (Throwable $e) {
            Log::warning('[GoogleMeet] invalidate token failed', ['message' => $e->getMessage()]);
        }
    }

    private function translateGoogleApiException(Throwable $e, ?string $eventId = null): RuntimeException
    {
        $raw = (string) $e->getMessage();
        $msg = Str::lower($raw);

        if (str_contains($msg, 'invalid_grant')) {
            return new RuntimeException(__('google-meet::messages.errors.google_session_invalid'));
        }

        if (str_contains($msg, 'not found') || str_contains($msg, 'requested entity was not found')) {
            $suffix = $eventId ? ' (ID: ' . $eventId . ')' : '';
            return new RuntimeException(__('google-meet::messages.errors.google_event_not_found') . $suffix . '.');
        }

        if (str_contains($msg, 'permission') || str_contains($msg, 'forbidden') || str_contains($msg, 'insufficient')) {
            return new RuntimeException(__('google-meet::messages.errors.google_permission_denied'));
        }

        if (str_contains($msg, 'access blocked') || str_contains($msg, 'access_denied')) {
            return new RuntimeException(__('google-meet::messages.errors.google_access_blocked'));
        }

        return new RuntimeException($raw !== '' ? $raw : __('google-meet::messages.errors.unexpected'));
    }

    private function log(
        int $tenantId,
        string $action,
        array $metadata = [],
        ?string $calendarId = null,
        ?string $eventId = null
    ): void {
        try {
            GoogleMeetActivityLog::create([
                'tenant_id' => $tenantId,
                'user_id' => Auth::id(),
                'action' => $action,
                'calendar_id' => $calendarId,
                'event_id' => $eventId,
                'metadata' => $metadata,
                'ip_address' => request()?->ip(),
            ]);
        } catch (Throwable $e) {
            Log::debug('[GoogleMeet] activity log skipped', ['message' => $e->getMessage()]);
        }
    }
}
