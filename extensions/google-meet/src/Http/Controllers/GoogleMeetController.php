<?php

namespace NexusExtensions\GoogleMeet\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\GoogleMeet\Http\Requests\GoogleMeetMeetingRequest;
use NexusExtensions\GoogleMeet\Http\Requests\GoogleMeetSelectCalendarRequest;
use NexusExtensions\GoogleMeet\Models\GoogleMeetCalendar;
use NexusExtensions\GoogleMeet\Services\GoogleMeetService;
use RuntimeException;
use Throwable;
use Vendor\Automation\Services\AutomationReconnectNotificationService;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class GoogleMeetController extends Controller
{
    public function __construct(protected GoogleMeetService $service)
    {
    }

    public function index()
    {
        $tenantId = $this->tenantId();
        $storageReady = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $googleCalendarInstalled = $this->isMarketplaceExtensionActive($tenantId, 'google-calendar');

        $token = ($storageReady && $extensionActive) ? $this->service->getToken($tenantId) : null;

        $calendars = ($storageReady && $extensionActive)
            ? GoogleMeetCalendar::forTenant($tenantId)
                ->where('is_deleted', false)
                ->orderByDesc('is_selected')
                ->orderByDesc('is_primary')
                ->orderBy('summary')
                ->get()
            : collect();

        return view('google-meet::meet.index', [
            'storageReady' => $storageReady,
            'extensionActive' => $extensionActive,
            'connected' => (bool) $token,
            'token' => $token,
            'calendars' => $calendars,
            'googleCalendarInstalled' => $googleCalendarInstalled,
            'googleCalendarTargetUrl' => $this->resolveGoogleCalendarTargetUrl($googleCalendarInstalled),
        ]);
    }

    public function connect()
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $authUrl = $this->service->getAuthUrl($tenantId, (int) Auth::id());

            return redirect()->away($authUrl);
        } catch (Throwable $e) {
            return redirect()->route('google-meet.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('google-meet.index')
                ->with('error', (string) $request->get('error_description', $request->get('error')));
        }

        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $state = $this->service->parseState((string) $request->string('state'));

            $tenantId = (int) $state['tenant_id'];
            $userId = (int) $state['user_id'];

            if ((int) Auth::id() !== $userId || (int) Auth::user()->tenant_id !== $tenantId) {
                throw new RuntimeException(__('google-meet::messages.errors.oauth_state_mismatch'));
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);
            app(AutomationReconnectNotificationService::class)
                ->notifyForProvider($tenantId, $userId, 'google-meet', route('google-meet.index'));

            return redirect()->route('google-meet.index')->with('success', __('google-meet::messages.success.connected'));
        } catch (Throwable $e) {
            return redirect()->route('google-meet.index')->with('error', $e->getMessage());
        }
    }

    public function disconnect(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->disconnect($tenantId);

            return response()->json([
                'success' => true,
                'message' => __('google-meet::messages.success.disconnected'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function calendarsData(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            if ($request->boolean('refresh')) {
                $this->service->syncCalendars($tenantId);
            }

            $calendars = GoogleMeetCalendar::forTenant($tenantId)
                ->where('is_deleted', false)
                ->orderByDesc('is_selected')
                ->orderByDesc('is_primary')
                ->orderBy('summary')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $calendars,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function selectCalendar(GoogleMeetSelectCalendarRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $calendar = $this->service->selectCalendar($tenantId, (string) $request->string('calendar_id'));

            return response()->json([
                'success' => true,
                'message' => __('google-meet::messages.success.calendar_selected'),
                'data' => $calendar,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function meetingsData(Request $request): JsonResponse
    {
        $request->validate([
            'calendar_id' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'refresh' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            if ($request->boolean('refresh')) {
                $this->service->syncMeetings(
                    $tenantId,
                    $request->filled('calendar_id') ? (string) $request->string('calendar_id') : null,
                    $request->filled('from') ? Carbon::parse((string) $request->string('from'))->startOfDay() : null,
                    $request->filled('to') ? Carbon::parse((string) $request->string('to'))->endOfDay() : null
                );
            }

            $meetings = $this->service->getLocalMeetings($tenantId, $request->all());

            return response()->json([
                'success' => true,
                'data' => $meetings->getCollection()->map(fn ($meeting) => $this->service->formatMeeting($meeting))->values(),
                'current_page' => $meetings->currentPage(),
                'last_page' => $meetings->lastPage(),
                'per_page' => $meetings->perPage(),
                'total' => $meetings->total(),
                'from' => $meetings->firstItem(),
                'to' => $meetings->lastItem(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json([
                'success' => true,
                'data' => $this->service->getStats($tenantId),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'calendar_id' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $this->service->syncCalendars($tenantId);

            $count = $this->service->syncMeetings(
                $tenantId,
                $request->filled('calendar_id') ? (string) $request->string('calendar_id') : null,
                $request->filled('from') ? Carbon::parse((string) $request->string('from'))->startOfDay() : null,
                $request->filled('to') ? Carbon::parse((string) $request->string('to'))->endOfDay() : null
            );

            return response()->json([
                'success' => true,
                'message' => __('google-meet::messages.success.sync_count', ['count' => $count]),
                'count' => $count,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storeMeeting(GoogleMeetMeetingRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $meeting = $this->service->createMeeting($tenantId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => __('google-meet::messages.success.meeting_created'),
                'data' => $meeting,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateMeeting(GoogleMeetMeetingRequest $request, string $calendarId, string $eventId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $payload = $request->validated();
            $payload['calendar_id'] = $calendarId;

            $meeting = $this->service->updateMeeting($tenantId, $calendarId, $eventId, $payload);

            return response()->json([
                'success' => true,
                'message' => __('google-meet::messages.success.meeting_updated'),
                'data' => $meeting,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroyMeeting(string $calendarId, string $eventId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $this->service->deleteMeeting($tenantId, $calendarId, $eventId);

            return response()->json([
                'success' => true,
                'message' => __('google-meet::messages.success.meeting_deleted'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function tenantId(): int
    {
        return (int) Auth::user()->tenant_id;
    }

    private function resolveGoogleCalendarTargetUrl(bool $isInstalled): string
    {
        if ($isInstalled && \Route::has('google-calendar.index')) {
            return route('google-calendar.index');
        }

        if (\Route::has('marketplace.show')) {
            return route('marketplace.show', 'google-calendar');
        }

        if (\Route::has('marketplace.index')) {
            return route('marketplace.index');
        }

        return \Route::has('applications') ? route('applications') : url('/');
    }

    private function isMarketplaceExtensionActive(int $tenantId, string $slug): bool
    {
        $extension = Extension::query()->where('slug', $slug)->first();
        if (!$extension) {
            return false;
        }

        return TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', (int) $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();
    }

    private function ensureExtensionActivated(int $tenantId): void
    {
        $this->assertStorageReady();

        if (!$this->isExtensionActive($tenantId)) {
            throw new RuntimeException(__('google-meet::messages.errors.extension_inactive'));
        }
    }

    private function isExtensionActive(int $tenantId): bool
    {
        $slug = (string) config('google-meet.slug', 'google-meet');

        $extension = Extension::where('slug', $slug)->first();
        if (!$extension) {
            return false;
        }

        return TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();
    }

    private function isStorageReady(): bool
    {
        return Schema::hasTable('google_meet_tokens')
            && Schema::hasTable('google_meet_calendars')
            && Schema::hasTable('google_meet_meetings')
            && Schema::hasTable('google_meet_activity_logs');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException(__('google-meet::messages.errors.storage_missing'));
        }
    }
}
