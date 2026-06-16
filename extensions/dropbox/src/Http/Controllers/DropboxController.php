<?php

namespace NexusExtensions\Dropbox\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\Dropbox\Http\Requests\DropboxFolderRequest;
use NexusExtensions\Dropbox\Http\Requests\DropboxUploadRequest;
use NexusExtensions\Dropbox\Services\DropboxService;
use RuntimeException;
use Throwable;
use Vendor\Automation\Services\AutomationReconnectNotificationService;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class DropboxController extends Controller
{
    public function __construct(protected DropboxService $service)
    {
    }

    public function index()
    {
        $tenantId = $this->tenantId();
        $storageReady = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $token = ($storageReady && $extensionActive) ? $this->service->getToken($tenantId) : null;
        $googleDriveInstalled = $this->isTenantExtensionActive($tenantId, 'google-drive');

        return view('dropbox::drive.index', [
            'storageReady' => $storageReady,
            'extensionActive' => $extensionActive,
            'connected' => (bool) $token,
            'token' => $token,
            'googleDriveInstalled' => $googleDriveInstalled,
        ]);
    }

    public function connect()
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return redirect()->away($this->service->getAuthUrl($tenantId, (int) Auth::id()));
        } catch (Throwable $e) {
            return redirect()->route('dropbox.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('dropbox.index')->with('error', (string) $request->get('error_description', $request->get('error')));
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
                throw new RuntimeException(__('dropbox::messages.errors.oauth_session_mismatch'));
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);
            app(AutomationReconnectNotificationService::class)
                ->notifyForProvider($tenantId, $userId, 'dropbox', route('dropbox.index'));

            return redirect()->route('dropbox.index')->with('success', __('dropbox::messages.success.connected'));
        } catch (Throwable $e) {
            return redirect()->route('dropbox.index')->with('error', $e->getMessage());
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
                'message' => __('dropbox::messages.success.disconnected'),
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
            'folder_id' => ['nullable', 'string', 'max:500'],
            'search' => ['nullable', 'string', 'max:255'],
            'page_token' => ['nullable', 'string', 'max:500'],
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

    public function createFolder(DropboxFolderRequest $request): JsonResponse
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
                'message' => __('dropbox::messages.success.folder_created'),
                'data' => $folder,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function upload(DropboxUploadRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $parentId = $request->filled('parent_id') ? (string) $request->string('parent_id') : null;
            $uploaded = [];

            foreach ((array) $request->file('files', []) as $file) {
                if (!$file) {
                    continue;
                }

                $uploaded[] = $this->service->uploadFile($tenantId, $file, $parentId);
            }

            return response()->json([
                'success' => true,
                'message' => count($uploaded) > 1
                    ? __('dropbox::messages.success.files_uploaded')
                    : __('dropbox::messages.success.file_uploaded'),
                'data' => $uploaded,
                'uploaded_count' => count($uploaded),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function rename(Request $request, string $fileId): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $file = $this->service->rename($tenantId, $fileId, (string) $request->string('name'));

            return response()->json(['success' => true, 'message' => __('dropbox::messages.success.item_renamed'), 'data' => $file]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function move(Request $request, string $fileId): JsonResponse
    {
        $request->validate([
            'target_folder_id' => ['required', 'string', 'max:500'],
            'current_folder_id' => ['required', 'string', 'max:500'],
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

            return response()->json(['success' => true, 'message' => __('dropbox::messages.success.item_moved'), 'data' => $file]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function copy(Request $request, string $fileId): JsonResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'target_folder_id' => ['nullable', 'string', 'max:500'],
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

            return response()->json(['success' => true, 'message' => __('dropbox::messages.success.item_copied'), 'data' => $file]);
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

            return response()->json(['success' => true, 'message' => __('dropbox::messages.success.item_deleted')]);
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

            return response()->json(['success' => true, 'message' => __('dropbox::messages.success.item_restored'), 'data' => $file]);
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

            return response()->json(['success' => true, 'message' => __('dropbox::messages.success.trash_emptied')]);
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
            'type' => ['nullable', 'string', 'max:50'],
            'role' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $file = $this->service->share(
                $tenantId,
                $fileId,
                (string) $request->string('type', 'anyone'),
                (string) $request->string('role', 'reader'),
                $request->filled('email') ? (string) $request->string('email') : null
            );

            return response()->json(['success' => true, 'message' => __('dropbox::messages.success.share_link_created'), 'data' => $file]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function open(string $fileId)
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return redirect()->away($this->service->getOpenUrl($tenantId, $fileId));
        } catch (Throwable $e) {
            return redirect()->route('dropbox.index')->with('error', $e->getMessage());
        }
    }

    public function download(string $fileId)
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $meta = $this->service->getFile($tenantId, $fileId);
            $content = $this->service->getDownloadStream($tenantId, $fileId);
            $fileName = $meta['name'] ?? ('dropbox-' . $fileId);

            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $fileName, [
                'Content-Type' => 'application/octet-stream',
            ]);
        } catch (Throwable $e) {
            return redirect()->route('dropbox.index')->with('error', $e->getMessage());
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
            throw new RuntimeException(__('dropbox::messages.errors.extension_inactive'));
        }
    }

    private function isExtensionActive(int $tenantId): bool
    {
        return $this->isTenantExtensionActive($tenantId, 'dropbox');
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
        return Schema::hasTable('dropbox_tokens')
            && Schema::hasTable('dropbox_files')
            && Schema::hasTable('dropbox_activity_logs');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException(__('dropbox::messages.errors.storage_missing'));
        }
    }
}
