<?php

namespace NexusExtensions\Chatbot\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\Chatbot\Http\Requests\ChatbotCreateRoomRequest;
use NexusExtensions\Chatbot\Http\Requests\ChatbotSendMessageRequest;
use NexusExtensions\Chatbot\Models\ChatbotMessage;
use NexusExtensions\Chatbot\Models\ChatbotRoom;
use NexusExtensions\Chatbot\Services\ChatbotService;
use Throwable;

class ChatbotController extends Controller
{
    public function __construct(protected ChatbotService $service)
    {
    }

    public function index()
    {
        $storageReady = $this->isStorageReady();

        if ($storageReady) {
            $this->service->bootstrapTenant($this->tenantId(), Auth::user());
        }

        return view('chatbot::chatbot.index', [
            'storageReady' => $storageReady,
            'socketEnabled' => (bool) config('chatbot.socket.enabled', false),
            'socketClientUrl' => (string) config('chatbot.socket.client_url', ''),
            'socketPath' => (string) config('chatbot.socket.path', '/socket.io'),
            'socketNamespace' => (string) config('chatbot.socket.namespace', '/'),
            'maxFileSizeKb' => (int) config('chatbot.messages.max_file_size_kb', 10240),
            'allowedMimeTypes' => (array) config('chatbot.messages.allowed_mimes', []),
            'allowedExtensions' => (array) config('chatbot.messages.allowed_extensions', []),
            'iconChoices' => (array) config('chatbot.ui.icon_choices', []),
            'colorPalette' => (array) config('chatbot.ui.color_palette', []),
        ]);
    }

    public function roomsData(): JsonResponse
    {
        try {
            $this->assertStorageReady();

            $rooms = $this->service->listRooms($this->tenantId(), Auth::user());

            return response()->json([
                'success' => true,
                'data' => $rooms,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function storeRoom(ChatbotCreateRoomRequest $request): JsonResponse
    {
        try {
            $this->assertStorageReady();

            $room = $this->service->createRoom($this->tenantId(), Auth::user(), $request->validated());

            return response()->json([
                'success' => true,
                'message' => __('chatbot::messages.success.room_created'),
                'data' => $this->service->formatRoom($room, Auth::user()),
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateRoom(ChatbotCreateRoomRequest $request, ChatbotRoom $room): JsonResponse
    {
        try {
            $this->assertStorageReady();
            $this->assertRoomTenant($room);

            $room = $this->service->updateRoom($this->tenantId(), Auth::user(), $room, $request->validated());

            return response()->json([
                'success' => true,
                'message' => __('chatbot::messages.success.room_updated'),
                'data' => $this->service->formatRoom($room, Auth::user()),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroyRoom(ChatbotRoom $room): JsonResponse
    {
        try {
            $this->assertStorageReady();
            $this->assertRoomTenant($room);

            $this->service->archiveRoom($this->tenantId(), Auth::user(), $room);

            return response()->json([
                'success' => true,
                'message' => __('chatbot::messages.success.room_archived'),
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
        $maxFetch = max(20, min((int) config('chatbot.messages.max_fetch', 300), 1000));

        $request->validate([
            'room_id' => ['required', 'integer', 'exists:chatbot_rooms,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . $maxFetch],
        ]);

        try {
            $this->assertStorageReady();

            $messages = $this->service->getMessages($this->tenantId(), Auth::user(), $request->all());

            return response()->json([
                'success' => true,
                'data' => $messages->map(fn ($message) => $this->service->formatMessage($message, Auth::user()))->values(),
                'total' => $messages->count(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function sendMessage(ChatbotSendMessageRequest $request): JsonResponse
    {
        try {
            $this->assertStorageReady();

            $payload = $request->validated();
            $payload['files'] = $request->file('files', []);

            $message = $this->service->sendMessage($this->tenantId(), Auth::user(), $payload);

            return response()->json([
                'success' => true,
                'message' => __('chatbot::messages.success.message_sent'),
                'data' => $this->service->formatMessage($message, Auth::user()),
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroyMessage(ChatbotMessage $message): JsonResponse
    {
        try {
            $this->assertStorageReady();
            $this->assertMessageTenant($message);

            $this->service->deleteMessage($this->tenantId(), Auth::user(), $message);

            return response()->json([
                'success' => true,
                'message' => __('chatbot::messages.success.message_deleted'),
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
            $this->assertStorageReady();

            return response()->json([
                'success' => true,
                'data' => $this->service->getStats($this->tenantId(), Auth::user()),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function searchData(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $this->assertStorageReady();

            $limit = (int) ($request->integer('per_page') ?: 5);
            $rows = $this->service->searchMessages(
                $this->tenantId(),
                Auth::user(),
                (string) $request->string('q'),
                $limit
            );

            return response()->json([
                'success' => true,
                'data' => $rows,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function usersData(): JsonResponse
    {
        try {
            $this->assertStorageReady();

            return response()->json([
                'success' => true,
                'data' => $this->service->listTenantUsers($this->tenantId()),
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

    private function isStorageReady(): bool
    {
        return Schema::hasTable('chatbot_rooms')
            && Schema::hasTable('chatbot_room_members')
            && Schema::hasTable('chatbot_messages')
            && Schema::hasTable('chatbot_activity_logs');
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new \RuntimeException(__('chatbot::messages.errors.storage_missing'));
        }
    }

    private function assertRoomTenant(ChatbotRoom $room): void
    {
        if ((int) $room->tenant_id !== $this->tenantId()) {
            abort(404);
        }
    }

    private function assertMessageTenant(ChatbotMessage $message): void
    {
        if ((int) $message->tenant_id !== $this->tenantId()) {
            abort(404);
        }
    }
}
