<?php

namespace NexusExtensions\GoogleSheets\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\GoogleSheets\Http\Requests\GoogleSheetsCreateSpreadsheetRequest;
use NexusExtensions\GoogleSheets\Http\Requests\GoogleSheetsWriteRangeRequest;
use NexusExtensions\GoogleSheets\Services\GoogleSheetsService;
use RuntimeException;
use Throwable;
use Vendor\Automation\Services\AutomationReconnectNotificationService;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class GoogleSheetsController extends Controller
{
    public function __construct(protected GoogleSheetsService $service)
    {
    }

    // ── Pages ──────────────────────────────────────────────────────────────

    public function index()
    {
        $tenantId        = $this->tenantId();
        $storageReady    = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $token           = ($storageReady && $extensionActive) ? $this->service->getToken($tenantId) : null;

        return view('google-sheets::sheets.index', [
            'storageReady'    => $storageReady,
            'extensionActive' => $extensionActive,
            'connected'       => (bool) $token,
            'token'           => $token,
        ]);
    }

    // ── OAuth ──────────────────────────────────────────────────────────────

    public function connect()
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $authUrl  = $this->service->getAuthUrl($tenantId, (int) Auth::id());

            return redirect()->away($authUrl);
        } catch (Throwable $e) {
            return redirect()->route('google-sheets.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('google-sheets.index')
                ->with('error', (string) $request->get('error_description', $request->get('error')));
        }

        $request->validate([
            'code'  => ['required', 'string'],
            'state' => ['required', 'string'],
        ], [
            'code.required' => __('google-sheets::messages.validation.auth_code_required'),
            'code.string' => __('google-sheets::messages.validation.auth_code_string'),
            'state.required' => __('google-sheets::messages.validation.oauth_state_required'),
            'state.string' => __('google-sheets::messages.validation.oauth_state_string'),
        ]);

        try {
            $state    = $this->service->parseState((string) $request->string('state'));
            $tenantId = (int) $state['tenant_id'];
            $userId   = (int) $state['user_id'];

            if ((int) Auth::id() !== $userId || (int) Auth::user()->tenant_id !== $tenantId) {
                throw new RuntimeException(__('google-sheets::messages.errors.oauth_state_mismatch'));
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);
            app(AutomationReconnectNotificationService::class)
                ->notifyForProvider($tenantId, $userId, 'google-sheets', route('google-sheets.index'));

            return redirect()->route('google-sheets.index')
                ->with('success', __('google-sheets::messages.success.connected'));
        } catch (Throwable $e) {
            return redirect()->route('google-sheets.index')->with('error', $e->getMessage());
        }
    }

    public function disconnect(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->disconnect($tenantId);

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.disconnected')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Spreadsheets ────────────────────────────────────────────────────────

    public function spreadsheetsData(Request $request): JsonResponse
    {
        $request->validate([
            'search'     => ['nullable', 'string', 'max:255'],
            'page_token' => ['nullable', 'string', 'max:255'],
        ], [
            'search.string' => __('google-sheets::messages.validation.search_string'),
            'search.max' => __('google-sheets::messages.validation.search_max'),
            'page_token.string' => __('google-sheets::messages.validation.page_token_string'),
            'page_token.max' => __('google-sheets::messages.validation.page_token_max'),
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $this->service->listSpreadsheets(
                $tenantId,
                (string) $request->string('search', ''),
                $request->filled('page_token') ? (string) $request->string('page_token') : null
            );

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function showSpreadsheet(string $spreadsheetId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data = $this->service->getSpreadsheet($tenantId, $spreadsheetId);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function createSpreadsheet(GoogleSheetsCreateSpreadsheetRequest $request): JsonResponse
    {
        try {
            $tenantId    = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $spreadsheet = $this->service->createSpreadsheet(
                $tenantId,
                (string) $request->string('title'),
                (array) ($request->input('sheet_titles', []))
            );

            return response()->json([
                'success' => true,
                'message' => __('google-sheets::messages.success.spreadsheet_created'),
                'data'    => $spreadsheet,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function renameSpreadsheet(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:500']], [
            'title.required' => __('google-sheets::messages.validation.title_required'),
            'title.string' => __('google-sheets::messages.validation.title_string'),
            'title.max' => __('google-sheets::messages.validation.title_max'),
        ]);

        try {
            $tenantId    = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $spreadsheet = $this->service->renameSpreadsheet($tenantId, $spreadsheetId, (string) $request->string('title'));

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.spreadsheet_renamed'), 'data' => $spreadsheet]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteSpreadsheet(string $spreadsheetId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->deleteSpreadsheet($tenantId, $spreadsheetId);

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.spreadsheet_deleted')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function duplicateSpreadsheet(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate(['title' => ['nullable', 'string', 'max:500']], [
            'title.string' => __('google-sheets::messages.validation.title_string'),
            'title.max' => __('google-sheets::messages.validation.title_max'),
        ]);

        try {
            $tenantId    = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $spreadsheet = $this->service->duplicateSpreadsheet(
                $tenantId,
                $spreadsheetId,
                (string) $request->string('title', '')
            );

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.spreadsheet_duplicated'), 'data' => $spreadsheet]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Sheets (onglets) ────────────────────────────────────────────────────

    public function addSheet(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:100']], [
            'title.required' => __('google-sheets::messages.validation.sheet_title_required'),
            'title.string' => __('google-sheets::messages.validation.sheet_title_string'),
            'title.max' => __('google-sheets::messages.validation.sheet_title_max'),
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $sheet    = $this->service->addSheet($tenantId, $spreadsheetId, (string) $request->string('title'));

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.sheet_added'), 'data' => $sheet], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function renameSheet(Request $request, string $spreadsheetId, int $sheetId): JsonResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:100']], [
            'title.required' => __('google-sheets::messages.validation.sheet_title_required'),
            'title.string' => __('google-sheets::messages.validation.sheet_title_string'),
            'title.max' => __('google-sheets::messages.validation.sheet_title_max'),
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->renameSheet($tenantId, $spreadsheetId, $sheetId, (string) $request->string('title'));

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.sheet_renamed')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteSheet(string $spreadsheetId, int $sheetId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->deleteSheet($tenantId, $spreadsheetId, $sheetId);

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.sheet_deleted')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Data (cellules) ─────────────────────────────────────────────────────

    public function readRange(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate(['range' => ['required', 'string', 'max:255']], [
            'range.required' => __('google-sheets::messages.validation.range_required'),
            'range.string' => __('google-sheets::messages.validation.range_string'),
            'range.max' => __('google-sheets::messages.validation.range_max'),
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data     = $this->service->readRange($tenantId, $spreadsheetId, (string) $request->string('range'));

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function writeRange(GoogleSheetsWriteRangeRequest $request, string $spreadsheetId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data     = $this->service->writeRange(
                $tenantId,
                $spreadsheetId,
                (string) $request->string('range'),
                (array) $request->input('values')
            );

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.data_written'), 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function appendRows(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate([
            'range'    => ['required', 'string', 'max:255'],
            'values'   => ['required', 'array'],
            'values.*' => ['array'],
        ], [
            'range.required' => __('google-sheets::messages.validation.range_required'),
            'range.string' => __('google-sheets::messages.validation.range_string'),
            'range.max' => __('google-sheets::messages.validation.range_max'),
            'values.required' => __('google-sheets::messages.validation.values_required'),
            'values.array' => __('google-sheets::messages.validation.values_array'),
            'values.*.array' => __('google-sheets::messages.validation.values_array'),
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data     = $this->service->appendRows(
                $tenantId,
                $spreadsheetId,
                (string) $request->string('range'),
                (array) $request->input('values')
            );

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.rows_appended'), 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function clearRange(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate(['range' => ['required', 'string', 'max:255']], [
            'range.required' => __('google-sheets::messages.validation.range_required'),
            'range.string' => __('google-sheets::messages.validation.range_string'),
            'range.max' => __('google-sheets::messages.validation.range_max'),
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->clearRange($tenantId, $spreadsheetId, (string) $request->string('range'));

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.range_cleared')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function batchRead(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate([
            'ranges'   => ['required', 'array', 'min:1', 'max:20'],
            'ranges.*' => ['string', 'max:255'],
        ], [
            'ranges.required' => __('google-sheets::messages.validation.ranges_required'),
            'ranges.array' => __('google-sheets::messages.validation.ranges_array'),
            'ranges.min' => __('google-sheets::messages.validation.ranges_min'),
            'ranges.max' => __('google-sheets::messages.validation.ranges_max'),
            'ranges.*.string' => __('google-sheets::messages.validation.range_string'),
            'ranges.*.max' => __('google-sheets::messages.validation.range_max'),
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data     = $this->service->batchRead($tenantId, $spreadsheetId, (array) $request->input('ranges'));

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function batchWrite(Request $request, string $spreadsheetId): JsonResponse
    {
        $request->validate([
            'data'            => ['required', 'array', 'min:1', 'max:20'],
            'data.*.range'    => ['required', 'string', 'max:255'],
            'data.*.values'   => ['required', 'array'],
            'data.*.values.*' => ['array'],
        ], [
            'data.required' => __('google-sheets::messages.validation.data_required'),
            'data.array' => __('google-sheets::messages.validation.data_array'),
            'data.min' => __('google-sheets::messages.validation.data_min'),
            'data.max' => __('google-sheets::messages.validation.data_max'),
            'data.*.range.required' => __('google-sheets::messages.validation.range_required'),
            'data.*.range.string' => __('google-sheets::messages.validation.range_string'),
            'data.*.range.max' => __('google-sheets::messages.validation.range_max'),
            'data.*.values.required' => __('google-sheets::messages.validation.values_required'),
            'data.*.values.array' => __('google-sheets::messages.validation.values_array'),
            'data.*.values.*.array' => __('google-sheets::messages.validation.values_array'),
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $result   = $this->service->batchWrite($tenantId, $spreadsheetId, (array) $request->input('data'));

            return response()->json(['success' => true, 'message' => __('google-sheets::messages.success.batch_write_done'), 'data' => $result]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Stats ───────────────────────────────────────────────────────────────

    public function stats(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json(['success' => true, 'data' => $this->service->getStats($tenantId)]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function tenantId(): int
    {
        return (int) Auth::user()->tenant_id;
    }

    private function ensureExtensionActivated(int $tenantId): void
    {
        $this->assertStorageReady();
        if (!$this->isExtensionActive($tenantId)) {
            throw new RuntimeException(__('google-sheets::messages.errors.extension_inactive'));
        }
    }

    private function isExtensionActive(int $tenantId): bool
    {
        $extension = Extension::query()->where('slug', 'google-sheets')->first();
        if (!$extension) return false;

        return TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();
    }

    private function isStorageReady(): bool
    {
        return Schema::hasTable('google_sheets_tokens')
            && Schema::hasTable('google_sheets_spreadsheets')
            && Schema::hasTable('google_sheets_sheets')
            && Schema::hasTable('google_sheets_activity_logs');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException(__('google-sheets::messages.errors.storage_missing'));
        }
    }
}
