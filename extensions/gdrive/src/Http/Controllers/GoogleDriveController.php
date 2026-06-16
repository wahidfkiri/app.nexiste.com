<?php

namespace NexusExtensions\GoogleDrive\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\GoogleDrive\Http\Requests\GoogleDriveFolderRequest;
use NexusExtensions\GoogleDrive\Http\Requests\GoogleDriveUploadRequest;
use NexusExtensions\GoogleDrive\Services\GoogleDriveService;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Vendor\Automation\Services\AutomationReconnectNotificationService;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class GoogleDriveController extends Controller
{
    public function __construct(protected GoogleDriveService $service)
    {
    }

    public function index()
    {
        $tenantId = $this->tenantId();
        $storageReady = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $token = ($storageReady && $extensionActive) ? $this->service->getToken($tenantId) : null;
        $dropboxInstalled = $this->isTenantExtensionActive($tenantId, 'dropbox');

        return view('google-drive::drive.index', [
            'storageReady' => $storageReady,
            'extensionActive' => $extensionActive,
            'connected' => (bool) $token,
            'token' => $token,
            'dropboxInstalled' => $dropboxInstalled,
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
            return redirect()->route('google-drive.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('google-drive.index')->with('error', (string) $request->get('error_description', $request->get('error')));
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
                throw new RuntimeException(__('google-drive::messages.errors.oauth_state_mismatch'));
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);
            app(AutomationReconnectNotificationService::class)
                ->notifyForProvider($tenantId, $userId, 'google-drive', route('google-drive.index'));

            return redirect()->route('google-drive.index')->with('success', __('google-drive::messages.success.connected'));
        } catch (Throwable $e) {
            return redirect()->route('google-drive.index')->with('error', $e->getMessage());
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
                'message' => __('google-drive::messages.success.disconnected'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function filesData(Request $request): JsonResponse
    {
        $request->validate([
            'folder_id' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'page_token' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $this->service->listFiles(
                $tenantId,
                $request->filled('folder_id') ? (string) $request->string('folder_id') : null,
                1,
                (string) $request->string('search', ''),
                $request->filled('page_token') ? (string) $request->string('page_token') : null
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json([
                'success' => true,
                'data' => $this->service->getStorageStats($tenantId),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function createFolder(GoogleDriveFolderRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $folder = $this->service->createFolder(
                $tenantId,
                (string) $request->string('name'),
                $request->filled('parent_id') ? (string) $request->string('parent_id') : null
            );

            return response()->json([
                'success' => true,
                'message' => __('google-drive::messages.success.folder_created'),
                'data' => $folder,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function upload(GoogleDriveUploadRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $file = $this->service->uploadFile(
                $tenantId,
                $request->file('file'),
                $request->filled('parent_id') ? (string) $request->string('parent_id') : null
            );

            return response()->json([
                'success' => true,
                'message' => __('google-drive::messages.success.file_uploaded'),
                'data' => $file,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function rename(Request $request, string $fileId): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:500'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $file = $this->service->rename($tenantId, $fileId, (string) $request->string('name'));

            return response()->json(['success' => true, 'message' => __('google-drive::messages.success.file_renamed'), 'data' => $file]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function move(Request $request, string $fileId): JsonResponse
    {
        $request->validate([
            'target_folder_id' => ['required', 'string', 'max:255'],
            'current_folder_id' => ['required', 'string', 'max:255'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $file = $this->service->move(
                $tenantId,
                $fileId,
                (string) $request->string('target_folder_id'),
                (string) $request->string('current_folder_id')
            );

            return response()->json(['success' => true, 'message' => __('google-drive::messages.success.file_moved'), 'data' => $file]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function copy(Request $request, string $fileId): JsonResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:500'],
            'target_folder_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $file = $this->service->copy(
                $tenantId,
                $fileId,
                (string) $request->string('name', ''),
                $request->filled('target_folder_id') ? (string) $request->string('target_folder_id') : null
            );

            return response()->json(['success' => true, 'message' => __('google-drive::messages.success.file_copied'), 'data' => $file]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function delete(Request $request, string $fileId): JsonResponse
    {
        $request->validate([
            'permanent' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->delete($tenantId, $fileId, $request->boolean('permanent', false));

            return response()->json(['success' => true, 'message' => __('google-drive::messages.success.file_deleted')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function restore(string $fileId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $file = $this->service->restore($tenantId, $fileId);

            return response()->json(['success' => true, 'message' => __('google-drive::messages.success.file_restored'), 'data' => $file]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function trashData(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json([
                'success' => true,
                'data' => $this->service->listTrash($tenantId),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function emptyTrash(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->service->emptyTrash($tenantId);

            return response()->json(['success' => true, 'message' => __('google-drive::messages.success.trash_emptied')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'max:255'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $results = $this->service->search($tenantId, (string) $request->string('q'));

            return response()->json(['success' => true, 'data' => $results]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function share(Request $request, string $fileId): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'in:anyone,user,group,domain'],
            'role' => ['required', 'in:reader,commenter,writer,organizer,fileOrganizer'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $file = $this->service->share(
                $tenantId,
                $fileId,
                (string) $request->string('type'),
                (string) $request->string('role'),
                $request->filled('email') ? (string) $request->string('email') : null
            );

            return response()->json(['success' => true, 'message' => __('google-drive::messages.success.file_shared'), 'data' => $file]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function download(string $fileId): StreamedResponse
    {
        $tenantId = $this->tenantId();
        $this->ensureExtensionActivated($tenantId);
        $meta = $this->service->getFile($tenantId, $fileId);
        $content = $this->service->getDownloadStream($tenantId, $fileId);

        $fileName = $meta['name'] ?? ('download-' . $fileId);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $fileName, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    private function tenantId(): int
    {
        return (int) Auth::user()->tenant_id;
    }

    private function ensureExtensionActivated(int $tenantId): void
    {
        $this->assertStorageReady();

        if (!$this->isExtensionActive($tenantId)) {
            throw new RuntimeException(__('google-drive::messages.errors.extension_inactive'));
        }
    }

    private function isExtensionActive(int $tenantId): bool
    {
        return $this->isTenantExtensionActive($tenantId, 'google-drive');
    }

    private function isTenantExtensionActive(int $tenantId, string $slug): bool
    {
        $extension = Extension::query()->where('slug', $slug)->first();
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
        return Schema::hasTable('google_drive_tokens')
            && Schema::hasTable('google_drive_files')
            && Schema::hasTable('google_drive_activity_logs');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException(__('google-drive::messages.errors.storage_missing'));
        }
    }
}
