<?php

namespace Vendor\GoogleCalendar\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;
use Vendor\Automation\Services\AutomationReconnectNotificationService;
use Vendor\Client\Models\Client;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\GoogleCalendar\Http\Requests\GoogleCalendarEventRequest;
use Vendor\GoogleCalendar\Http\Requests\GoogleCalendarSelectCalendarRequest;
use Vendor\GoogleCalendar\Models\GoogleCalendarCalendar;
use Vendor\GoogleCalendar\Services\GoogleCalendarService;

class GoogleCalendarController extends Controller
{
    public function __construct(protected GoogleCalendarService $service)
    {
        app()->setLocale('fr');
        Carbon::setLocale('fr');
    }

    public function index()
    {
        $tenantId = $this->tenantId();
        $storageReady = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $clientsInstalled = $this->isMarketplaceExtensionActive($tenantId, 'clients');
        $clientsTargetUrl = $this->resolveExtensionTargetUrl('clients', $clientsInstalled, 'clients.index');

        $token = ($storageReady && $extensionActive) ? $this->service->getToken($tenantId) : null;

        $calendars = ($storageReady && $extensionActive)
            ? GoogleCalendarCalendar::forTenant($tenantId)
                ->where('is_deleted', false)
                ->orderByDesc('is_selected')
                ->orderByDesc('is_primary')
                ->orderBy('summary')
                ->get()
            : collect();

        $clients = $clientsInstalled
            ? Client::query()->orderBy('company_name')->get(['id', 'company_name'])
            : collect();

        $prefill = [
            'source_type' => request()->query('source_type'),
            'source_id' => request()->query('source_id'),
            'source_label' => request()->query('source_label'),
            'client_id' => request()->query('client_id'),
            'summary' => request()->query('summary'),
            'description' => request()->query('description'),
        ];

        return view('google-calendar::calendar.index', [
            'storageReady' => $storageReady,
            'extensionActive' => $extensionActive,
            'connected' => (bool) $token,
            'token' => $token,
            'calendars' => $calendars,
            'clientsInstalled' => $clientsInstalled,
            'clientsTargetUrl' => $clientsTargetUrl,
            'clients' => $clients,
            'prefill' => $prefill,
            'jsI18n' => $this->jsI18n(),
        ]);
    }

    public function connect()
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $authUrl = $this->service->getAuthUrl($tenantId, Auth::id());

            return redirect()->away($authUrl);
        } catch (Throwable $e) {
            return redirect()->route('google-calendar.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('google-calendar.index')->with('error', __('google-calendar::messages.connection.oauth_cancelled'));
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
                throw new RuntimeException(__('google-calendar::messages.errors.oauth_state_mismatch'));
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);
            app(AutomationReconnectNotificationService::class)
                ->notifyForProvider($tenantId, $userId, 'google-calendar', route('google-calendar.index'));

            return redirect()->route('google-calendar.index')->with('success', __('google-calendar::messages.success.connected'));
        } catch (Throwable $e) {
            return redirect()->route('google-calendar.index')->with('error', $e->getMessage());
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
                'message' => __('google-calendar::messages.success.disconnected'),
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

            $calendars = GoogleCalendarCalendar::forTenant($tenantId)
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

    public function selectCalendar(GoogleCalendarSelectCalendarRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $calendar = $this->service->selectCalendar($tenantId, (string) $request->string('calendar_id'));

            return response()->json([
                'success' => true,
                'message' => __('google-calendar::messages.success.selected_calendar'),
                'data' => $calendar,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function eventsData(Request $request): JsonResponse
    {
        $request->validate([
            'calendar_id' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'refresh' => ['nullable', 'boolean'],
            'include_holidays' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            if ($request->boolean('refresh')) {
                $from = $request->filled('from') ? Carbon::parse((string) $request->string('from'))->startOfDay() : null;
                $to = $request->filled('to') ? Carbon::parse((string) $request->string('to'))->endOfDay() : null;

                $this->service->syncEvents(
                    $tenantId,
                    $request->filled('calendar_id') ? (string) $request->string('calendar_id') : null,
                    $from,
                    $to,
                    $request->boolean('include_holidays', true)
                );
            }

            $events = $this->service->getLocalEvents($tenantId, $request->all());

            return response()->json([
                'success' => true,
                'data' => $events->getCollection()->map(fn ($event) => $this->service->formatEvent($event))->values(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'from' => $events->firstItem(),
                'to' => $events->lastItem(),
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
            'include_holidays' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $this->service->syncCalendars($tenantId);

            $count = $this->service->syncEvents(
                $tenantId,
                $request->filled('calendar_id') ? (string) $request->string('calendar_id') : null,
                $request->filled('from') ? Carbon::parse((string) $request->string('from'))->startOfDay() : null,
                $request->filled('to') ? Carbon::parse((string) $request->string('to'))->endOfDay() : null,
                $request->boolean('include_holidays', true)
            );

            return response()->json([
                'success' => true,
                'message' => __('google-calendar::messages.success.synced_count', ['count' => $count]),
                'count' => $count,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storeEvent(GoogleCalendarEventRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $payload = $request->validated();

            if (!$this->isMarketplaceExtensionActive($tenantId, 'clients')) {
                unset($payload['client_id']);
            }

            $event = $this->service->createEvent($tenantId, $payload);

            return response()->json([
                'success' => true,
                'message' => __('google-calendar::messages.success.event_created'),
                'data' => $event,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateEvent(GoogleCalendarEventRequest $request, string $calendarId, string $eventId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $payload = $request->validated();
            $payload['calendar_id'] = $calendarId;

            if (!$this->isMarketplaceExtensionActive($tenantId, 'clients')) {
                unset($payload['client_id']);
            }

            $event = $this->service->updateEvent($tenantId, $calendarId, $eventId, $payload);

            return response()->json([
                'success' => true,
                'message' => __('google-calendar::messages.success.event_updated'),
                'data' => $event,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroyEvent(string $calendarId, string $eventId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $this->service->deleteEvent($tenantId, $calendarId, $eventId);

            return response()->json([
                'success' => true,
                'message' => __('google-calendar::messages.success.event_deleted'),
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

    private function resolveExtensionTargetUrl(string $slug, bool $isInstalled, ?string $installedRoute = null): string
    {
        if ($isInstalled && $installedRoute && Route::has($installedRoute)) {
            return route($installedRoute);
        }

        if (Route::has('marketplace.show')) {
            return route('marketplace.show', $slug);
        }

        if (Route::has('marketplace.index')) {
            return route('marketplace.index');
        }

        return Route::has('applications') ? route('applications') : url('/');
    }

    private function ensureExtensionActivated(int $tenantId): void
    {
        $this->assertStorageReady();

        if (!$this->isExtensionActive($tenantId)) {
            throw new RuntimeException(__('google-calendar::messages.errors.extension_inactive'));
        }
    }

    private function isExtensionActive(int $tenantId): bool
    {
        $slug = (string) config('google-calendar.slug', 'google-calendar');

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
        return Schema::hasTable('google_calendar_tokens')
            && Schema::hasTable('google_calendar_calendars')
            && Schema::hasTable('google_calendar_events')
            && Schema::hasTable('google_calendar_activity_logs');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException(__('google-calendar::messages.errors.storage_missing'));
        }
    }

    private function jsI18n(): array
    {
        return [
            'primary' => __('google-calendar::messages.calendars.primary'),
            'no_calendars_title' => __('google-calendar::messages.calendars.no_calendars_title'),
            'no_calendars_desc' => __('google-calendar::messages.calendars.no_calendars_desc'),
            'error' => __('google-calendar::messages.common.error'),
            'success' => __('google-calendar::messages.common.success'),
            'load_calendars_error' => __('google-calendar::messages.errors.load_calendars'),
            'select_calendar_error' => __('google-calendar::messages.errors.select_calendar'),
            'calendar_selected' => __('google-calendar::messages.success.calendar_selected'),
            'load_events_error' => __('google-calendar::messages.errors.load_events'),
            'count_results' => __('google-calendar::messages.table.count_results'),
            'pagination_showing' => __('google-calendar::messages.table.pagination_showing'),
            'sync_error' => __('google-calendar::messages.errors.sync'),
            'sync_success' => __('google-calendar::messages.success.sync'),
            'disconnect_confirm_title' => __('google-calendar::messages.confirm.disconnect_title'),
            'disconnect_confirm_message' => __('google-calendar::messages.confirm.disconnect_message'),
            'disconnect_confirm_button' => __('google-calendar::messages.confirm.disconnect_button'),
            'disconnect_success_title' => __('google-calendar::messages.success.disconnected_title'),
            'disconnect_success_message' => __('google-calendar::messages.success.disconnected_message'),
            'disconnect_error' => __('google-calendar::messages.errors.disconnect'),
            'modal_create' => __('google-calendar::messages.modal.create_event'),
            'modal_edit' => __('google-calendar::messages.modal.edit_event'),
            'delete_confirm_title' => __('google-calendar::messages.confirm.delete_title'),
            'delete_confirm_message' => __('google-calendar::messages.confirm.delete_message'),
            'delete_confirm_button' => __('google-calendar::messages.confirm.delete_button'),
            'delete_error' => __('google-calendar::messages.errors.delete'),
            'delete_success_title' => __('google-calendar::messages.success.deleted_title'),
            'delete_success_message' => __('google-calendar::messages.success.deleted_message'),
            'save_error' => __('google-calendar::messages.errors.save'),
            'save_success_message' => __('google-calendar::messages.success.saved'),
            'validation_title' => __('google-calendar::messages.common.validation'),
            'validation_error' => __('google-calendar::messages.errors.validation'),
            'validation_calendar' => __('google-calendar::messages.validation.calendar'),
            'validation_title_required' => __('google-calendar::messages.validation.title_required'),
            'validation_start' => __('google-calendar::messages.validation.start_required'),
            'validation_end' => __('google-calendar::messages.validation.end_required'),
            'validation_end_after_start' => __('google-calendar::messages.validation.end_after_start'),
            'validation_attendees' => __('google-calendar::messages.validation.attendees'),
            'validation_source' => __('google-calendar::messages.validation.source_type'),
            'holiday_badge' => __('google-calendar::messages.badges.holiday'),
            'open_google' => __('google-calendar::messages.actions.open_google'),
            'edit' => __('google-calendar::messages.actions.edit'),
            'delete' => __('google-calendar::messages.actions.delete'),
            'status_confirmed' => __('google-calendar::messages.status.confirmed'),
            'status_tentative' => __('google-calendar::messages.status.tentative'),
            'status_cancelled' => __('google-calendar::messages.status.cancelled'),
            'status_unknown' => __('google-calendar::messages.status.unknown'),
            'no_title' => __('google-calendar::messages.common.no_title'),
            'no_data_title' => __('google-calendar::messages.common.no_data_title'),
            'no_data_message' => __('google-calendar::messages.common.no_data_message'),
            'empty_filtered' => __('google-calendar::messages.table.empty_filtered'),
            'mode_no_events_title' => __('google-calendar::messages.mode.no_events_title'),
            'mode_no_events_message' => __('google-calendar::messages.mode.no_events_message'),
            'mode_load_error_title' => __('google-calendar::messages.mode.load_error_title'),
            'all_day' => __('google-calendar::messages.common.all_day'),
            'no_events' => __('google-calendar::messages.common.no_events'),
            'more' => __('google-calendar::messages.common.more'),
            'today' => __('google-calendar::messages.period.today'),
            'month' => __('google-calendar::messages.views.month'),
            'week' => __('google-calendar::messages.views.week'),
            'day' => __('google-calendar::messages.views.day'),
            'year' => __('google-calendar::messages.views.year'),
            'visibility_default' => __('google-calendar::messages.visibility.default'),
            'visibility_public' => __('google-calendar::messages.visibility.public'),
            'visibility_private' => __('google-calendar::messages.visibility.private'),
            'visibility_confidential' => __('google-calendar::messages.visibility.confidential'),
            'detail_empty' => __('google-calendar::messages.detail.empty'),
            'detail_no_attendees' => __('google-calendar::messages.detail.no_attendees'),
            'detail_no_description' => __('google-calendar::messages.detail.no_description'),
        ];
    }
}
