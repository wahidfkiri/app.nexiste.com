<?php

namespace NexusExtensions\Chatbot\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NexusExtensions\Chatbot\Models\ChatbotActivityLog;
use NexusExtensions\Chatbot\Models\ChatbotMessage;
use NexusExtensions\Chatbot\Models\ChatbotRoom;
use NexusExtensions\Chatbot\Models\ChatbotRoomMember;
use RuntimeException;

class ChatbotService
{
    public function __construct(protected ChatbotSocketService $socketService)
    {
    }

    public function bootstrapTenant(int $tenantId, User $user): ChatbotRoom
    {
        return $this->ensureDefaultRoom($tenantId, $user);
    }

    public function ensureDefaultRoom(int $tenantId, User $user): ChatbotRoom
    {
        $room = ChatbotRoom::query()
            ->forTenant($tenantId)
            ->where('is_default', true)
            ->first();

        if ($room) {
            return $room;
        }

        $room = ChatbotRoom::query()->create([
            'tenant_id' => $tenantId,
            'created_by' => (int) $user->id,
            'updated_by' => (int) $user->id,
            'room_uuid' => (string) Str::uuid(),
            'name' => 'general',
            'description' => __('chatbot::messages.defaults.general_description'),
            'icon' => 'fa-hashtag',
            'color' => '#2563eb',
            'is_private' => false,
            'is_archived' => false,
            'is_default' => true,
            'messages_count' => 0,
            'last_message_at' => null,
        ]);

        $this->syncRoomMembers($room, [(int) $user->id], $tenantId, (int) $user->id);

        $this->log($tenantId, (int) $user->id, 'room.default_created', $room->id);

        return $room;
    }

    public function listRooms(int $tenantId, User $user): Collection
    {
        return $this->roomsQueryForUser($tenantId, (int) $user->id)
            ->with([
                'members' => fn ($query) => $query->whereNull('left_at')->with('user:id,name,email'),
            ])
            ->orderByDesc('is_default')
            ->orderByDesc('last_message_at')
            ->orderBy('name')
            ->get()
            ->map(fn (ChatbotRoom $room) => $this->formatRoom($room, $user));
    }

    public function createRoom(int $tenantId, User $user, array $data): ChatbotRoom
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException(__('chatbot::messages.errors.room_name_required'));
        }

        if (
            ChatbotRoom::query()
                ->forTenant($tenantId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists()
        ) {
            throw new RuntimeException(__('chatbot::messages.errors.room_name_exists'));
        }

        $room = DB::transaction(function () use ($tenantId, $user, $data, $name) {
            $room = ChatbotRoom::query()->create([
                'tenant_id' => $tenantId,
                'created_by' => (int) $user->id,
                'updated_by' => (int) $user->id,
                'room_uuid' => (string) Str::uuid(),
                'name' => $name,
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'icon' => trim((string) ($data['icon'] ?? '')) ?: 'fa-comments',
                'color' => trim((string) ($data['color'] ?? '')) ?: '#0ea5e9',
                'is_private' => (bool) ($data['is_private'] ?? false),
                'is_archived' => false,
                'is_default' => false,
            ]);

            $memberIds = collect((array) ($data['member_ids'] ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ((bool) $room->is_private || !empty($memberIds)) {
                $this->syncRoomMembers($room, $memberIds, $tenantId, (int) $user->id);
            }

            return $room;
        });

        $this->log($tenantId, (int) $user->id, 'room.created', $room->id, null, [
            'name' => $room->name,
            'is_private' => (bool) $room->is_private,
        ]);

        $this->socketService->emit($tenantId, 'room.created', [
            'tenant_id' => $tenantId,
            'room' => $this->formatRoom($room->fresh(), $user),
        ]);

        return $room->fresh();
    }

    public function updateRoom(int $tenantId, User $user, ChatbotRoom $room, array $data): ChatbotRoom
    {
        $this->assertManageRoom($tenantId, $user, $room);

        $newName = trim((string) ($data['name'] ?? $room->name));
        if ($newName === '') {
            throw new RuntimeException(__('chatbot::messages.errors.room_name_required'));
        }

        if (
            ChatbotRoom::query()
                ->forTenant($tenantId)
                ->where('id', '!=', (int) $room->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($newName)])
                ->exists()
        ) {
            throw new RuntimeException(__('chatbot::messages.errors.room_name_exists'));
        }

        DB::transaction(function () use ($tenantId, $user, $room, $data, $newName) {
            $room->update([
                'name' => $newName,
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'icon' => trim((string) ($data['icon'] ?? '')) ?: 'fa-comments',
                'color' => trim((string) ($data['color'] ?? '')) ?: '#0ea5e9',
                'is_private' => (bool) ($data['is_private'] ?? false),
                'updated_by' => (int) $user->id,
            ]);

            if (array_key_exists('member_ids', $data) || (bool) $room->is_private) {
                $memberIds = collect((array) ($data['member_ids'] ?? []))
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();

                $this->syncRoomMembers($room, $memberIds, $tenantId, (int) $user->id);
            }
        });

        $fresh = $room->fresh();

        $this->log($tenantId, (int) $user->id, 'room.updated', $fresh->id);

        $this->socketService->emit($tenantId, 'room.updated', [
            'tenant_id' => $tenantId,
            'room' => $this->formatRoom($fresh, $user),
        ]);

        return $fresh;
    }

    public function archiveRoom(int $tenantId, User $user, ChatbotRoom $room): void
    {
        $this->assertManageRoom($tenantId, $user, $room);

        if ((bool) $room->is_default) {
            throw new RuntimeException(__('chatbot::messages.errors.default_room_delete_forbidden'));
        }

        $room->update([
            'is_archived' => true,
            'updated_by' => (int) $user->id,
        ]);

        $this->log($tenantId, (int) $user->id, 'room.deleted', $room->id);

        $this->socketService->emit($tenantId, 'room.deleted', [
            'tenant_id' => $tenantId,
            'room_id' => (int) $room->id,
        ]);
    }

    public function getMessages(int $tenantId, User $user, array $filters = []): Collection
    {
        $roomId = (int) ($filters['room_id'] ?? 0);
        if ($roomId <= 0) {
            throw new RuntimeException(__('chatbot::messages.errors.room_select'));
        }

        $room = $this->roomForUserOrFail($tenantId, $user, $roomId);

        $query = ChatbotMessage::query()
            ->forTenant($tenantId)
            ->where('room_id', (int) $room->id)
            ->where('is_deleted', false)
            ->orderByDesc('sent_at')
            ->orderByDesc('id');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('text', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%");
            });
        }

        $maxFetch = max(20, min((int) config('chatbot.messages.max_fetch', 300), 1000));
        $take = max(1, min((int) ($filters['per_page'] ?? $maxFetch), $maxFetch));

        $rows = $query->take($take)->get();

        return $rows
            ->sort(function (ChatbotMessage $left, ChatbotMessage $right) {
                $leftTs = $left->sent_at?->getTimestamp() ?? 0;
                $rightTs = $right->sent_at?->getTimestamp() ?? 0;

                if ($leftTs === $rightTs) {
                    return (int) $left->id <=> (int) $right->id;
                }

                return $leftTs <=> $rightTs;
            })
            ->values();
    }

    public function sendMessage(int $tenantId, User $user, array $payload): ChatbotMessage
    {
        $roomId = (int) ($payload['room_id'] ?? 0);
        if ($roomId <= 0) {
            throw new RuntimeException(__('chatbot::messages.errors.room_required'));
        }

        $room = $this->roomForUserOrFail($tenantId, $user, $roomId);

        $text = trim((string) ($payload['text'] ?? ''));
        $files = (array) ($payload['files'] ?? []);
        $attachments = $this->storeAttachments($tenantId, (int) $room->id, $files);

        if ($text === '' && empty($attachments)) {
            throw new RuntimeException(__('chatbot::messages.errors.message_empty'));
        }

        $messageType = 'text';
        if ($text !== '' && !empty($attachments)) {
            $messageType = 'mixed';
        } elseif (!empty($attachments)) {
            $messageType = 'file';
        }

        $message = DB::transaction(function () use ($tenantId, $user, $room, $payload, $text, $attachments, $messageType) {
            $message = ChatbotMessage::query()->create([
                'tenant_id' => $tenantId,
                'room_id' => (int) $room->id,
                'user_id' => (int) $user->id,
                'reply_to_message_id' => !empty($payload['reply_to_message_id']) ? (int) $payload['reply_to_message_id'] : null,
                'message_uuid' => (string) Str::uuid(),
                'message_type' => $messageType,
                'sender_name' => (string) ($user->name ?: __('chatbot::messages.defaults.user')),
                'text' => $text !== '' ? $text : null,
                'attachments' => $attachments,
                'sent_at' => now(),
            ]);

            $room->increment('messages_count');
            $room->update([
                'last_message_at' => now(),
                'updated_by' => (int) $user->id,
            ]);

            return $message;
        });

        $formatted = $this->formatMessage($message->fresh(), $user);

        $this->log($tenantId, (int) $user->id, 'message.sent', (int) $room->id, (int) $message->id, [
            'type' => $messageType,
        ]);

        $this->socketService->emit($tenantId, 'message.created', [
            'tenant_id' => $tenantId,
            'room_id' => (int) $room->id,
            'message' => $formatted,
        ]);

        return $message->fresh();
    }

    public function deleteMessage(int $tenantId, User $user, ChatbotMessage $message): void
    {
        $room = $this->roomForUserOrFail($tenantId, $user, (int) $message->room_id);

        if ((int) $message->user_id !== (int) $user->id && !$this->canManageRoom($user, $room)) {
            throw new RuntimeException(__('chatbot::messages.errors.message_delete_forbidden'));
        }

        $deleted = DB::transaction(function () use ($tenantId, $user, $room, $message) {
            if ((bool) $message->is_deleted) {
                return false;
            }

            $message->update([
                'is_deleted' => true,
                'deleted_at' => now(),
                'text' => null,
                'attachments' => [],
            ]);

            $room->messages_count = max(0, ((int) $room->messages_count) - 1);
            $room->last_message_at = ChatbotMessage::query()
                ->forTenant($tenantId)
                ->where('room_id', (int) $room->id)
                ->where('is_deleted', false)
                ->orderByDesc('sent_at')
                ->orderByDesc('id')
                ->value('sent_at');
            $room->updated_by = (int) $user->id;
            $room->save();

            return true;
        });

        if (!$deleted) {
            return;
        }

        $this->log($tenantId, (int) $user->id, 'message.deleted', (int) $room->id, (int) $message->id);

        $this->socketService->emit($tenantId, 'message.deleted', [
            'tenant_id' => $tenantId,
            'room_id' => (int) $room->id,
            'message_id' => (int) $message->id,
        ]);
    }

    public function getStats(int $tenantId, User $user): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $weekStart = now()->subDays(7);

        $rooms = $this->roomsQueryForUser($tenantId, (int) $user->id);

        return [
            'rooms_count' => (clone $rooms)->count(),
            'private_rooms_count' => (clone $rooms)->where('is_private', true)->count(),
            'messages_today' => ChatbotMessage::query()
                ->forTenant($tenantId)
                ->whereBetween('sent_at', [$todayStart, $todayEnd])
                ->where('is_deleted', false)
                ->count(),
            'messages_last_7_days' => ChatbotMessage::query()
                ->forTenant($tenantId)
                ->where('sent_at', '>=', $weekStart)
                ->where('is_deleted', false)
                ->count(),
            'messages_total' => ChatbotMessage::query()
                ->forTenant($tenantId)
                ->where('is_deleted', false)
                ->count(),
            'socket_enabled' => (bool) config('chatbot.socket.enabled', false),
        ];
    }

    public function searchMessages(int $tenantId, User $user, string $search, int $limit = 5): Collection
    {
        $search = trim($search);
        if ($search === '') {
            return collect();
        }

        $limit = max(1, min($limit, 20));
        $roomIds = $this->roomsQueryForUser($tenantId, (int) $user->id)->pluck('id');
        if ($roomIds->isEmpty()) {
            return collect();
        }

        return ChatbotMessage::query()
            ->forTenant($tenantId)
            ->whereIn('room_id', $roomIds->all())
            ->where('is_deleted', false)
            ->where(function ($query) use ($search) {
                $query->where('text', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%");
            })
            ->with('room:id,name')
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (ChatbotMessage $message) use ($user) {
                $row = $this->formatMessage($message, $user);
                $row['room_name'] = (string) ($message->room?->name ?? '');
                return $row;
            })
            ->values();
    }

    public function formatRoom(ChatbotRoom $room, User $user): array
    {
        $members = $this->activeMembersForRoom($room);
        $memberIds = $members->pluck('user_id')->map(fn ($id) => (int) $id)->values()->all();
        $membersPreview = $members
            ->map(function (ChatbotRoomMember $member) {
                return [
                    'user_id' => (int) $member->user_id,
                    'name' => (string) ($member->user?->name ?? __('chatbot::messages.defaults.user')),
                    'email' => (string) ($member->user?->email ?? ''),
                ];
            })
            ->values()
            ->all();

        return [
            'id' => (int) $room->id,
            'room_uuid' => (string) $room->room_uuid,
            'name' => (string) $room->name,
            'description' => (string) ($room->description ?? ''),
            'icon' => (string) ($room->icon ?: 'fa-comments'),
            'color' => (string) ($room->color ?: '#0ea5e9'),
            'is_private' => (bool) $room->is_private,
            'is_default' => (bool) $room->is_default,
            'messages_count' => (int) $room->messages_count,
            'last_message_at' => $room->last_message_at?->toIso8601String(),
            'last_message_display' => $room->last_message_at?->format('d/m/Y H:i'),
            'member_ids' => $memberIds,
            'members_count' => count($memberIds),
            'members_preview' => $membersPreview,
            'can_manage' => $this->canManageRoom($user, $room),
        ];
    }

    public function listTenantUsers(int $tenantId): Collection
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'name' => (string) ($user->name ?? __('chatbot::messages.defaults.user')),
                'email' => (string) ($user->email ?? ''),
            ])
            ->values();
    }

    public function formatMessage(ChatbotMessage $message, User $viewer): array
    {
        $attachments = collect((array) ($message->attachments ?? []))
            ->map(function ($file) {
                if (!is_array($file)) {
                    return null;
                }

                $mime = (string) ($file['mime_type'] ?? '');
                $previewable = Str::startsWith($mime, 'image/') || in_array($mime, ['application/pdf', 'text/plain'], true);

                return [
                    'id' => (string) ($file['id'] ?? ''),
                    'name' => (string) ($file['name'] ?? __('chatbot::messages.defaults.file')),
                    'mime_type' => $mime,
                    'size' => (int) ($file['size'] ?? 0),
                    'size_kb' => (int) ($file['size_kb'] ?? 0),
                    'url' => (string) ($file['url'] ?? '#'),
                    'previewable' => $previewable,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'id' => (int) $message->id,
            'message_uuid' => (string) $message->message_uuid,
            'room_id' => (int) $message->room_id,
            'user_id' => $message->user_id ? (int) $message->user_id : null,
            'sender_name' => (string) ($message->sender_name ?: __('chatbot::messages.defaults.user')),
            'message_type' => (string) $message->message_type,
            'text' => (string) ($message->text ?? ''),
            'attachments' => $attachments,
            'is_mine' => (int) ($message->user_id ?? 0) === (int) $viewer->id,
            'is_deleted' => (bool) $message->is_deleted,
            'sent_at' => $message->sent_at?->toIso8601String(),
            'sent_display' => $message->sent_at?->format('d/m/Y H:i') ?? '-',
        ];
    }

    public function roomForUserOrFail(int $tenantId, User $user, int $roomId): ChatbotRoom
    {
        $room = ChatbotRoom::query()->forTenant($tenantId)->where('id', $roomId)->first();
        if (!$room) {
            throw new RuntimeException(__('chatbot::messages.errors.room_not_found'));
        }

        if (!$this->canAccessRoom($room, (int) $user->id)) {
            throw new RuntimeException(__('chatbot::messages.errors.room_access_denied'));
        }

        return $room;
    }

    private function roomsQueryForUser(int $tenantId, int $userId)
    {
        return ChatbotRoom::query()
            ->forTenant($tenantId)
            ->where('is_archived', false)
            ->where(function ($q) use ($userId) {
                $q->where('is_private', false)
                    ->orWhere('created_by', $userId)
                    ->orWhereExists(function ($sub) use ($userId) {
                        $sub->selectRaw('1')
                            ->from('chatbot_room_members')
                            ->whereColumn('chatbot_room_members.room_id', 'chatbot_rooms.id')
                            ->where('chatbot_room_members.user_id', $userId)
                            ->whereNull('chatbot_room_members.left_at');
                    });
            });
    }

    private function canAccessRoom(ChatbotRoom $room, int $userId): bool
    {
        if (!(bool) $room->is_private) {
            return true;
        }

        if ((int) $room->created_by === $userId) {
            return true;
        }

        return ChatbotRoomMember::query()
            ->where('room_id', (int) $room->id)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
    }

    private function assertManageRoom(int $tenantId, User $user, ChatbotRoom $room): void
    {
        if ((int) $room->tenant_id !== $tenantId) {
            throw new RuntimeException(__('chatbot::messages.errors.room_invalid'));
        }

        if (!$this->canManageRoom($user, $room)) {
            throw new RuntimeException(__('chatbot::messages.errors.room_manage_forbidden'));
        }
    }

    private function canManageRoom(User $user, ChatbotRoom $room): bool
    {
        if ((int) $room->created_by === (int) $user->id) {
            return true;
        }

        $role = mb_strtolower((string) ($user->role_in_tenant ?? ''));
        if (in_array($role, ['owner', 'admin', 'superadmin', 'super_admin'], true)) {
            return true;
        }

        return ChatbotRoomMember::query()
            ->where('room_id', (int) $room->id)
            ->where('user_id', (int) $user->id)
            ->where('role', 'owner')
            ->whereNull('left_at')
            ->exists();
    }

    private function syncRoomMembers(ChatbotRoom $room, array $memberIds, int $tenantId, int $ownerId): void
    {
        $memberIds[] = $ownerId;
        $memberIds = collect($memberIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $validUserIds = User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $memberIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        foreach ($validUserIds as $memberId) {
            ChatbotRoomMember::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'room_id' => (int) $room->id,
                    'user_id' => (int) $memberId,
                ],
                [
                    'role' => ((int) $memberId === $ownerId) ? 'owner' : 'member',
                    'is_muted' => false,
                    'joined_at' => now(),
                    'left_at' => null,
                ]
            );
        }

        ChatbotRoomMember::query()
            ->where('tenant_id', $tenantId)
            ->where('room_id', (int) $room->id)
            ->whereNotIn('user_id', $validUserIds->all())
            ->delete();
    }

    private function activeMembersForRoom(ChatbotRoom $room): Collection
    {
        if ($room->relationLoaded('members')) {
            /** @var \Illuminate\Support\Collection $members */
            $members = $room->members;
            return $members
                ->filter(fn (ChatbotRoomMember $member) => $member->left_at === null)
                ->values();
        }

        return ChatbotRoomMember::query()
            ->where('tenant_id', (int) $room->tenant_id)
            ->where('room_id', (int) $room->id)
            ->whereNull('left_at')
            ->with('user:id,name,email')
            ->get();
    }

    private function storeAttachments(int $tenantId, int $roomId, array $files): array
    {
        $disk = (string) config('chatbot.messages.storage_disk', 'public');
        $basePath = trim((string) config('chatbot.messages.storage_path', 'chatbot/messages'), '/');
        $storedFiles = [];

        foreach ($files as $file) {
            if (!$file || !method_exists($file, 'getClientOriginalName')) {
                continue;
            }

            $originalName = (string) $file->getClientOriginalName();
            $mime = (string) $file->getClientMimeType();
            $extension = mb_strtolower((string) $file->getClientOriginalExtension());
            $safeStem = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $safeStem = $safeStem !== '' ? $safeStem : 'fichier';
            $filename = now()->format('YmdHis') . '-' . Str::random(8) . '-' . $safeStem;
            if ($extension !== '') {
                $filename .= '.' . $extension;
            }

            $dir = $basePath . '/tenant_' . $tenantId . '/room_' . $roomId;
            $path = $file->storeAs($dir, $filename, $disk);

            $storedFiles[] = [
                'id' => (string) Str::uuid(),
                'name' => $originalName !== '' ? $originalName : $filename,
                'filename' => $filename,
                'path' => $path,
                'mime_type' => $mime,
                'size' => (int) $file->getSize(),
                'size_kb' => (int) ceil(((int) $file->getSize()) / 1024),
                'url' => (string) Storage::disk($disk)->url($path),
            ];
        }

        return $storedFiles;
    }

    private function log(
        int $tenantId,
        int $userId,
        string $action,
        ?int $roomId = null,
        ?int $messageId = null,
        ?array $metadata = null
    ): void {
        ChatbotActivityLog::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'room_id' => $roomId,
            'message_id' => $messageId,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }
}
