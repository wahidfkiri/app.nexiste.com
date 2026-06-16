<?php

namespace NexusExtensions\Slack\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\Slack\Http\Requests\SlackSelectChannelRequest;
use NexusExtensions\Slack\Http\Requests\SlackSendMessageRequest;
use NexusExtensions\Slack\Models\SlackChannel;
use NexusExtensions\Slack\Services\SlackService;
use RuntimeException;
use Throwable;
use Vendor\Automation\Services\AutomationReconnectNotificationService;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class SlackController extends Controller
{
    public function __construct(protected SlackService $service)
    {
    }

    public function index()
    {
        $tenantId = $this->tenantId();
        $storageReady = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $token = ($storageReady && $extensionActive) ? $this->service->getToken($tenantId) : null;

        $channels = ($storageReady && $extensionActive)
            ? SlackChannel::forTenant($tenantId)
                ->orderByDesc('is_selected')
                ->orderBy('name')
                ->get()
            : collect();

        return view('slack::slack.index', [
            'storageReady' => $storageReady,
            'extensionActive' => $extensionActive,
            'connected' => (bool) $token,
            'token' => $token,
            'channels' => $channels,
            'socketEnabled' => (bool) config('slack.socket.enabled', false),
            'socketClientUrl' => (string) config('slack.socket.client_url', ''),
            'socketPath' => (string) config('slack.socket.path', '/socket.io'),
            'socketNamespace' => (string) config('slack.socket.namespace', '/'),
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
            return redirect()->route('slack.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('slack.index')
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
                throw new RuntimeException(__('slack::messages.errors.oauth_state_mismatch'));
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);
            app(AutomationReconnectNotificationService::class)
                ->notifyForProvider($tenantId, $userId, 'slack', route('slack.index'));

            return redirect()->route('slack.index')->with('success', __('slack::messages.success.connected'));
        } catch (Throwable $e) {
            return redirect()->route('slack.index')->with('error', $e->getMessage());
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
                'message' => __('slack::messages.success.disconnected'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function channelsData(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            if ($request->boolean('refresh')) {
                $this->service->syncChannels($tenantId);
            }

            $channels = $this->service->listLocalChannels($tenantId)
                ->map(fn ($channel) => $this->service->formatChannel($channel))
                ->values();

            return response()->json([
                'success' => true,
                'data' => $channels,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function selectChannel(SlackSelectChannelRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $channel = $this->service->selectChannel($tenantId, (string) $request->string('channel_id'));

            return response()->json([
                'success' => true,
                'message' => __('slack::messages.success.channel_selected'),
                'data' => $this->service->formatChannel($channel),
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
            'channel_id' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'refresh' => ['nullable', 'boolean'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $channelId = $request->filled('channel_id') ? (string) $request->string('channel_id') : null;
            $from = $request->filled('from') ? Carbon::parse((string) $request->string('from'))->startOfDay() : null;
            $to = $request->filled('to') ? Carbon::parse((string) $request->string('to'))->endOfDay() : null;

            if ($request->boolean('refresh')) {
                $this->service->syncMessages($tenantId, $channelId, $from, $to);
            }

            $messages = $this->service->getLocalMessages($tenantId, $request->all());

            return response()->json([
                'success' => true,
                'data' => $messages->getCollection()->map(fn ($message) => $this->service->formatMessage($message))->values(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'from' => $messages->firstItem(),
                'to' => $messages->lastItem(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function sendMessage(SlackSendMessageRequest $request): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $message = $this->service->sendMessage($tenantId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => __('slack::messages.success.message_sent'),
                'data' => $message,
            ], 201);
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
            'channel_id' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $channelsCount = $this->service->syncChannels($tenantId);
            $messagesCount = $this->service->syncMessages(
                $tenantId,
                $request->filled('channel_id') ? (string) $request->string('channel_id') : null
            );

            return response()->json([
                'success' => true,
                'message' => __('slack::messages.success.sync_done', ['channels' => $channelsCount, 'messages' => $messagesCount]),
                'channels_count' => $channelsCount,
                'messages_count' => $messagesCount,
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
            throw new RuntimeException(__('slack::messages.errors.extension_inactive'));
        }
    }

    private function isExtensionActive(int $tenantId): bool
    {
        $slug = (string) config('slack.slug', 'slack');
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

    private function isStorageReady(): bool
    {
        return Schema::hasTable('slack_tokens')
            && Schema::hasTable('slack_channels')
            && Schema::hasTable('slack_messages')
            && Schema::hasTable('slack_activity_logs');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException(__('slack::messages.errors.storage_missing'));
        }
    }
}
