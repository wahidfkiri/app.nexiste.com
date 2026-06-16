<?php

namespace NexusExtensions\GoogleDocx\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\GoogleDocx\Http\Requests\GoogleDocxAppendTextRequest;
use NexusExtensions\GoogleDocx\Http\Requests\GoogleDocxCreateDocumentRequest;
use NexusExtensions\GoogleDocx\Http\Requests\GoogleDocxReplaceTextRequest;
use NexusExtensions\GoogleDocx\Services\GoogleDocxService;
use RuntimeException;
use Throwable;
use Vendor\Automation\Services\AutomationReconnectNotificationService;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class GoogleDocxController extends Controller
{
    public function __construct(protected GoogleDocxService $service)
    {
    }

    public function index()
    {
        $tenantId = $this->tenantId();
        $storageReady = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $token = ($storageReady && $extensionActive) ? $this->service->getToken($tenantId) : null;

        return view('google-docx::docs.index', [
            'storageReady' => $storageReady,
            'extensionActive' => $extensionActive,
            'connected' => (bool) $token,
            'token' => $token,
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
            return redirect()->route('google-docx.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('google-docx.index')->with('error', (string) $request->get('error_description', $request->get('error')));
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
                throw new RuntimeException(__('google-docx::messages.errors.oauth_state_mismatch'));
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);
            app(AutomationReconnectNotificationService::class)
                ->notifyForProvider($tenantId, $userId, 'google-docx', route('google-docx.index'));

            return redirect()->route('google-docx.index')->with('success', __('google-docx::messages.success.connected'));
        } catch (Throwable $e) {
            return redirect()->route('google-docx.index')->with('error', $e->getMessage());
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
                'message' => __('google-docx::messages.success.disconnected'),
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

    public function documentsData(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'page_token' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $this->service->listDocuments(
                $tenantId,
                (string) $request->string('search', ''),
                $request->filled('page_token') ? (string) $request->string('page_token') : null
            );

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function showDocument(string $documentId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $data = $this->service->getDocument($tenantId, $documentId);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function createDocument(GoogleDocxCreateDocumentRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $document = $this->service->createDocument(
                $tenantId,
                (string) $request->string('title'),
                (string) $request->string('content', '')
            );

            return response()->json([
                'success' => true,
                'message' => __('google-docx::messages.success.document_created'),
                'data' => $document,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function renameDocument(Request $request, string $documentId): JsonResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:500']]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $document = $this->service->renameDocument($tenantId, $documentId, (string) $request->string('title'));

            return response()->json(['success' => true, 'message' => __('google-docx::messages.success.document_renamed'), 'data' => $document]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function duplicateDocument(Request $request, string $documentId): JsonResponse
    {
        $request->validate(['title' => ['nullable', 'string', 'max:500']]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $document = $this->service->duplicateDocument(
                $tenantId,
                $documentId,
                (string) $request->string('title', '')
            );

            return response()->json(['success' => true, 'message' => __('google-docx::messages.success.document_duplicated'), 'data' => $document]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteDocument(string $documentId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->deleteDocument($tenantId, $documentId);

            return response()->json(['success' => true, 'message' => __('google-docx::messages.success.document_deleted')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function appendText(GoogleDocxAppendTextRequest $request, string $documentId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $document = $this->service->appendText($tenantId, $documentId, (string) $request->string('text'));

            return response()->json([
                'success' => true,
                'message' => __('google-docx::messages.success.text_appended'),
                'data' => $document,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function replaceText(GoogleDocxReplaceTextRequest $request, string $documentId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $document = $this->service->replaceText(
                $tenantId,
                $documentId,
                (string) $request->string('search'),
                (string) $request->string('replace', ''),
                $request->boolean('match_case')
            );

            return response()->json([
                'success' => true,
                'message' => __('google-docx::messages.success.replace_done'),
                'data' => $document,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function exportDocument(Request $request, string $documentId)
    {
        $request->validate([
            'format' => ['nullable', 'in:txt,html,pdf,docx'],
        ], [
            'format.in' => __('google-docx::messages.validation.format_in'),
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $export = $this->service->exportDocument($tenantId, $documentId, (string) $request->string('format', 'txt'));

            return response($export['content'], 200, [
                'Content-Type' => $export['mime'],
                'Content-Disposition' => 'attachment; filename="' . $export['file_name'] . '"',
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function tenantId(): int
    {
        return (int) Auth::user()->tenant_id;
    }

    private function ensureExtensionActivated(int $tenantId): void
    {
        $this->assertStorageReady();

        if (!$this->isExtensionActive($tenantId)) {
            throw new RuntimeException(__('google-docx::messages.errors.extension_inactive'));
        }
    }

    private function isExtensionActive(int $tenantId): bool
    {
        $extension = Extension::query()->where('slug', 'google-docx')->first();
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
        return Schema::hasTable('google_docx_tokens')
            && Schema::hasTable('google_docx_documents')
            && Schema::hasTable('google_docx_activity_logs');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException(__('google-docx::messages.errors.storage_missing'));
        }
    }
}
