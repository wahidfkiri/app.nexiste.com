<?php

namespace NexusExtensions\GoogleGmail\Http\Controllers;

use App\Support\Desktop\DesktopOAuthResponder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\GoogleGmail\Http\Requests\GoogleGmailForwardRequest;
use NexusExtensions\GoogleGmail\Http\Requests\GoogleGmailReplyRequest;
use NexusExtensions\GoogleGmail\Http\Requests\GoogleGmailSendEmailRequest;
use NexusExtensions\GoogleGmail\Http\Requests\GoogleGmailSettingsRequest;
use NexusExtensions\GoogleGmail\Services\GoogleGmailService;
use RuntimeException;
use Throwable;
use Vendor\Automation\Services\AutomationReconnectNotificationService;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class GoogleGmailController extends Controller
{
    public function __construct(
        protected GoogleGmailService $service,
        protected DesktopOAuthResponder $desktopOAuthResponder
    )
    {
    }

    public function index()
    {
        $tenantId = $this->tenantId();
        $storageReady = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $token = ($storageReady && $extensionActive) ? $this->service->getToken($tenantId) : null;

        return view('google-gmail::gmail.index', [
            'storageReady' => $storageReady,
            'extensionActive' => $extensionActive,
            'connected' => (bool) $token,
            'token' => $token,
            'settings' => ($storageReady && $extensionActive) ? $this->service->getSettings($tenantId) : [],
            'socketEnabled' => (bool) config('google-gmail.socket.enabled', false),
            'socketClientUrl' => (string) config('google-gmail.socket.client_url', ''),
            'socketPath' => (string) config('google-gmail.socket.path', '/socket.io'),
            'socketNamespace' => (string) config('google-gmail.socket.namespace', '/'),
            'jsI18n' => $this->jsI18n(),
        ]);
    }

    public function connect(Request $request)
    {
        $desktop = $this->desktopOAuthResponder->isDesktopRequest($request);
        $desktopReturn = $this->desktopOAuthResponder->sanitizeReturnPath(
            (string) $request->query('desktop_return', route('google-gmail.index', [], false)),
            route('google-gmail.index', [], false)
        );

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $authUrl = $this->service->getAuthUrl($tenantId, (int) Auth::id(), [
                'desktop' => $desktop,
                'desktop_return' => $desktopReturn,
            ]);

            if ($desktop && $this->shouldReturnDesktopJson($request)) {
                return response()->json([
                    'success' => true,
                    'open_url' => $authUrl,
                ]);
            }

            return redirect()->away($authUrl);
        } catch (Throwable $e) {
            if ($desktop && $this->shouldReturnDesktopJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('google-gmail.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        $desktopFallbackPath = route('google-gmail.index', [], false);
        $isDesktop = false;
        $desktopReturn = $desktopFallbackPath;
        $state = null;

        try {
            if ($request->filled('state')) {
                $state = $this->service->parseState((string) $request->string('state'));
                $isDesktop = (bool) ($state['desktop'] ?? false);
                $desktopReturn = $this->desktopOAuthResponder->sanitizeReturnPath(
                    (string) ($state['desktop_return'] ?? $desktopFallbackPath),
                    $desktopFallbackPath
                );
            }

            if ($request->filled('error')) {
                throw new RuntimeException((string) $request->get('error_description', $request->get('error')));
            }

            $request->validate([
                'code' => ['required', 'string'],
                'state' => ['required', 'string'],
            ]);

            $state ??= $this->service->parseState((string) $request->string('state'));
            $tenantId = (int) $state['tenant_id'];
            $userId = (int) $state['user_id'];

            if (!$isDesktop) {
                if (!Auth::check() || (int) Auth::id() !== $userId || (int) Auth::user()->tenant_id !== $tenantId) {
                    throw new RuntimeException('Etat OAuth invalide pour cette session.');
                }
            }

            if ($isDesktop && !$desktopReturn) {
                $desktopReturn = $desktopFallbackPath;
            }

            if ($tenantId <= 0 || $userId <= 0) {
                throw new RuntimeException('Etat OAuth invalide pour cette session.');
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);
            app(AutomationReconnectNotificationService::class)
                ->notifyForProvider($tenantId, $userId, 'google-gmail', route('google-gmail.index'));

            if ($isDesktop) {
                return $this->desktopOAuthResponder->renderSuccess(
                    $desktopReturn,
                    'Google Gmail connecte avec succes.',
                    'Google Gmail'
                );
            }

            return redirect()->route('google-gmail.index')->with('success', 'Google Gmail connecte avec succes.');
        } catch (Throwable $e) {
            if ($isDesktop) {
                return $this->desktopOAuthResponder->renderError(
                    $desktopReturn,
                    $e->getMessage(),
                    'Google Gmail'
                );
            }

            return redirect()->route('google-gmail.index')->with('error', $e->getMessage());
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
                'message' => 'Google Gmail deconnecte.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'refresh' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json([
                'success' => true,
                'data' => $this->service->getStats($tenantId, $request->boolean('refresh', false)),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function labelsData(Request $request): JsonResponse
    {
        $request->validate([
            'refresh' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json([
                'success' => true,
                'data' => $this->service->listLabels($tenantId, $request->boolean('refresh')),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function snapshotData(Request $request): JsonResponse
    {
        $request->validate([
            'refresh' => ['nullable', 'boolean'],
            'label_id' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:500'],
            'page_token' => ['nullable', 'string', 'max:255'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:50'],
            'include_spam_trash' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $this->service->getMailboxSnapshot(
                $tenantId,
                $request->boolean('refresh', false),
                (string) $request->string('label_id', 'INBOX'),
                (string) $request->string('q', ''),
                $request->filled('page_token') ? (string) $request->string('page_token') : null,
                (int) $request->integer('max_results', (int) config('google-gmail.api.max_results', 25)),
                $request->boolean('include_spam_trash', false)
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function messagesData(Request $request): JsonResponse
    {
        $request->validate([
            'label_id' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:500'],
            'page_token' => ['nullable', 'string', 'max:255'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:50'],
            'include_spam_trash' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $this->service->listMessages(
                $tenantId,
                (string) $request->string('label_id', 'INBOX'),
                (string) $request->string('q', ''),
                $request->filled('page_token') ? (string) $request->string('page_token') : null,
                (int) $request->integer('max_results', (int) config('google-gmail.api.max_results', 25)),
                $request->boolean('include_spam_trash', false)
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function settingsData(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json([
                'success' => true,
                'data' => $this->service->getSettings($tenantId),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function saveSettings(GoogleGmailSettingsRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json([
                'success' => true,
                'message' => 'Parametres Gmail enregistres.',
                'data' => $this->service->updateSettings($tenantId, $request->validated()),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function showMessage(string $messageId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json([
                'success' => true,
                'data' => $this->service->getMessage($tenantId, $messageId),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function showThread(string $threadId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return response()->json([
                'success' => true,
                'data' => $this->service->getThread($tenantId, $threadId),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function sendEmail(GoogleGmailSendEmailRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $request->validated();
            $attachments = $request->file('attachments', []);

            $message = $this->service->sendEmail($tenantId, $data, is_array($attachments) ? $attachments : []);

            return response()->json([
                'success' => true,
                'message' => 'Email envoye avec succes.',
                'data' => $message,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function replyEmail(GoogleGmailReplyRequest $request, string $messageId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $request->validated();
            $attachments = $request->file('attachments', []);

            $message = $this->service->replyToMessage($tenantId, $messageId, $data, is_array($attachments) ? $attachments : []);

            return response()->json([
                'success' => true,
                'message' => 'Reponse envoyee.',
                'data' => $message,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function forwardEmail(GoogleGmailForwardRequest $request, string $messageId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $request->validated();
            $attachments = $request->file('attachments', []);

            $message = $this->service->forwardMessage($tenantId, $messageId, $data, is_array($attachments) ? $attachments : []);

            return response()->json([
                'success' => true,
                'message' => 'Email transfere.',
                'data' => $message,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function markRead(string $messageId): JsonResponse
    {
        return $this->jsonMessageAction(fn (int $tenantId) => $this->service->markRead($tenantId, $messageId), 'Email marque comme lu.');
    }

    public function markUnread(string $messageId): JsonResponse
    {
        return $this->jsonMessageAction(fn (int $tenantId) => $this->service->markUnread($tenantId, $messageId), 'Email marque comme non lu.');
    }

    public function star(string $messageId): JsonResponse
    {
        return $this->jsonMessageAction(fn (int $tenantId) => $this->service->star($tenantId, $messageId), 'Email ajoute aux favoris.');
    }

    public function unstar(string $messageId): JsonResponse
    {
        return $this->jsonMessageAction(fn (int $tenantId) => $this->service->unstar($tenantId, $messageId), 'Email retire des favoris.');
    }

    public function archive(string $messageId): JsonResponse
    {
        return $this->jsonMessageAction(fn (int $tenantId) => $this->service->archive($tenantId, $messageId), 'Email archive.');
    }

    public function trash(string $messageId): JsonResponse
    {
        return $this->jsonMessageAction(fn (int $tenantId) => $this->service->trash($tenantId, $messageId), 'Email deplace vers la corbeille.');
    }

    public function untrash(string $messageId): JsonResponse
    {
        return $this->jsonMessageAction(fn (int $tenantId) => $this->service->untrash($tenantId, $messageId), 'Email restaure depuis la corbeille.');
    }

    public function deleteMessage(string $messageId): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $this->service->deleteMessage($tenantId, $messageId);

            return response()->json([
                'success' => true,
                'message' => 'Email supprime definitivement.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function downloadAttachment(Request $request, string $messageId, string $attachmentId)
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $attachment = $this->service->downloadAttachment($tenantId, $messageId, $attachmentId);
            $disposition = $request->boolean('inline') ? 'inline' : 'attachment';
            $filename = str_replace('"', '', (string) $attachment['file_name']);
            $filenameStar = rawurlencode($filename);

            return response($attachment['content'], 200, [
                'Content-Type' => $attachment['mime'],
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"; filename*=UTF-8\'\'' . $filenameStar,
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function jsonMessageAction(callable $callback, string $successMessage): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $message = $callback($tenantId);

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => $message,
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

    private function ensureExtensionActivated(int $tenantId): void
    {
        $this->assertStorageReady();

        if (!$this->isExtensionActive($tenantId)) {
            throw new RuntimeException('Google Gmail n est pas active pour ce tenant. Activez l application depuis le Marketplace.');
        }
    }

    private function isExtensionActive(int $tenantId): bool
    {
        $extension = Extension::query()->where('slug', 'google-gmail')->first();
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
        return Schema::hasTable('google_gmail_tokens')
            && Schema::hasTable('google_gmail_messages')
            && Schema::hasTable('google_gmail_labels')
            && Schema::hasTable('google_gmail_activity_logs')
            && Schema::hasTable('google_gmail_settings');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException('Les tables Google Gmail sont absentes. Executez: php artisan migrate');
        }
    }

    private function jsI18n(): array
    {
        return [
            'preview' => __('google-gmail::messages.attachments.preview'),
            'download' => __('google-gmail::messages.attachments.download'),
            'preview_loading' => __('google-gmail::messages.attachments.preview_loading'),
            'download_loading' => __('google-gmail::messages.attachments.download_loading'),
            'download_error' => __('google-gmail::messages.errors.download_attachment'),
            'preview_error' => __('google-gmail::messages.errors.preview_attachment'),
            'preview_not_supported' => __('google-gmail::messages.attachments.preview_not_supported'),
            'preview_file' => __('google-gmail::messages.attachments.preview_file'),
            'open_link_error' => __('google-gmail::messages.errors.open_link'),
            'error' => __('google-gmail::messages.common.error'),
            'validation' => __('google-gmail::messages.common.validation'),
            'attachment' => __('google-gmail::messages.attachments.attachment'),
            'close' => __('google-gmail::messages.common.close'),
        ];
    }

    private function shouldReturnDesktopJson(Request $request): bool
    {
        return $request->boolean('desktop_fetch') || $request->expectsJson() || $request->ajax();
    }
}
