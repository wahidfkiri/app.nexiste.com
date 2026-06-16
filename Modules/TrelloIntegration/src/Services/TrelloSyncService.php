<?php

namespace Modules\TrelloIntegration\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TrelloIntegration\Models\TrelloBoard;
use Modules\TrelloIntegration\Models\TrelloCard;
use Modules\TrelloIntegration\Models\TrelloLink;
use Modules\TrelloIntegration\Models\TrelloList;
use RuntimeException;

class TrelloSyncService
{
    public function __construct(
        protected TrelloApiService $api,
        protected TrelloAuthService $auth,
    ) {
    }

    public function syncAll(int $tenantId): array
    {
        return $this->withToken($tenantId, function (string $token) use ($tenantId) {
            $boards = $this->api->getBoards($token);
            $now = now();

            DB::transaction(function () use ($tenantId, $boards, $token, $now) {
                $this->upsertBoards($tenantId, $boards, $now);
                $boardMap = TrelloBoard::query()->where('tenant_id', $tenantId)->get()->keyBy('trello_id');

                foreach ($boards as $boardPayload) {
                    $remoteBoardId = (string) ($boardPayload['id'] ?? '');
                    $board = $boardMap->get($remoteBoardId);
                    if (!$board) {
                        continue;
                    }

                    $lists = $this->api->getBoardLists($token, $remoteBoardId);
                    $cards = $this->api->getBoardCards($token, $remoteBoardId);

                    $this->upsertLists($tenantId, $board->id, $lists, $now);
                    $listMap = TrelloList::query()
                        ->where('tenant_id', $tenantId)
                        ->where('trello_board_id', $board->id)
                        ->get()
                        ->keyBy('trello_id');

                    $this->upsertCards($tenantId, $board->id, $cards, $listMap, $now);
                }
            });

            $tokenModel = $this->auth->getToken($tenantId);
            $tokenModel?->update(['last_synced_at' => $now]);

            return [
                'boards_count' => TrelloBoard::query()->count(),
                'lists_count' => TrelloList::query()->count(),
                'cards_count' => TrelloCard::query()->count(),
                'last_synced_at' => $now->toIso8601String(),
            ];
        });
    }

    public function syncBoard(int $tenantId, TrelloBoard $board): TrelloBoard
    {
        return $this->withToken($tenantId, function (string $token) use ($tenantId, $board) {
            $now = now();
            $remoteBoard = $this->api->getBoard($token, $board->trello_id);
            $remoteLists = $this->api->getBoardLists($token, $board->trello_id);
            $remoteCards = $this->api->getBoardCards($token, $board->trello_id);

            DB::transaction(function () use ($tenantId, $board, $remoteBoard, $remoteLists, $remoteCards, $now) {
                $this->upsertBoards($tenantId, [$remoteBoard], $now);
                $freshBoard = TrelloBoard::query()->findOrFail($board->id);
                $this->upsertLists($tenantId, $freshBoard->id, $remoteLists, $now);
                $listMap = TrelloList::query()
                    ->where('tenant_id', $tenantId)
                    ->where('trello_board_id', $freshBoard->id)
                    ->get()
                    ->keyBy('trello_id');
                $this->upsertCards($tenantId, $freshBoard->id, $remoteCards, $listMap, $now);
            });

            $this->auth->getToken($tenantId)?->update(['last_synced_at' => $now]);

            return TrelloBoard::query()->findOrFail($board->id);
        });
    }

    public function createCard(int $tenantId, TrelloList $list, array $payload): TrelloCard
    {
        return $this->withToken($tenantId, function (string $token) use ($tenantId, $list, $payload) {
            $created = $this->api->createCard($token, $list->trello_id, [
                'name' => $payload['name'] ?? '',
                'description' => $payload['description'] ?? '',
                'due' => $payload['due'] ?? null,
                'pos' => 'bottom',
            ]);

            $remoteCard = $this->api->getCard($token, (string) ($created['id'] ?? ''));
            $card = $this->upsertSingleCard($tenantId, $list->trello_board_id, $remoteCard);

            return $card->fresh('link.project');
        });
    }

    public function updateCard(int $tenantId, TrelloCard $card, array $payload): TrelloCard
    {
        return $this->withToken($tenantId, function (string $token) use ($tenantId, $card, $payload) {
            $apiPayload = [];

            if (array_key_exists('name', $payload)) {
                $apiPayload['name'] = (string) $payload['name'];
            }

            if (array_key_exists('description', $payload)) {
                $apiPayload['desc'] = (string) $payload['description'];
            }

            if (array_key_exists('due', $payload)) {
                $apiPayload['due'] = $payload['due'] ?: '';
            }

            $this->api->updateCard($token, $card->trello_id, $apiPayload);
            $remoteCard = $this->api->getCard($token, $card->trello_id);
            $freshCard = $this->upsertSingleCard($tenantId, $card->trello_board_id, $remoteCard);

            if (array_key_exists('project_id', $payload) || array_key_exists('link_notes', $payload)) {
                $this->syncCardLink($tenantId, $freshCard, $payload['project_id'] ?? null, $payload['link_notes'] ?? null, (int) auth()->id());
            }

            return $freshCard->fresh('link.project');
        });
    }

    public function moveCard(int $tenantId, TrelloCard $card, TrelloList $targetList, string $position): TrelloCard
    {
        return $this->withToken($tenantId, function (string $token) use ($tenantId, $card, $targetList, $position) {
            $this->api->updateCard($token, $card->trello_id, [
                'idList' => $targetList->trello_id,
                'pos' => $position,
            ]);

            $remoteCard = $this->api->getCard($token, $card->trello_id);

            return $this->upsertSingleCard($tenantId, $card->trello_board_id, $remoteCard)->fresh('link.project');
        });
    }

    public function archiveCard(int $tenantId, TrelloCard $card): TrelloCard
    {
        return $this->withToken($tenantId, function (string $token) use ($tenantId, $card) {
            $this->api->updateCard($token, $card->trello_id, ['closed' => 'true']);
            $remoteCard = $this->api->getCard($token, $card->trello_id);

            return $this->upsertSingleCard($tenantId, $card->trello_board_id, $remoteCard)->fresh('link.project');
        });
    }

    public function syncCardLink(int $tenantId, TrelloCard $card, ?int $projectId, ?string $notes, int $linkedBy): ?TrelloLink
    {
        if (!$projectId && trim((string) $notes) === '') {
            $card->link()?->delete();
            return null;
        }

        return TrelloLink::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'trello_card_id' => $card->id],
            [
                'project_id' => $projectId ?: null,
                'notes' => trim((string) $notes) !== '' ? trim((string) $notes) : null,
                'linked_by' => $linkedBy,
                'linked_at' => now(),
            ]
        );
    }

    public function boardSnapshot(TrelloBoard $board): array
    {
        $board->load([
            'lists' => fn ($query) => $query->orderBy('position'),
            'lists.cards' => fn ($query) => $query->where('closed', false)->orderBy('position'),
            'lists.cards.link.project',
        ]);

        return [
            'id' => $board->id,
            'trello_id' => $board->trello_id,
            'name' => $board->name,
            'description' => $board->description,
            'url' => $board->url,
            'closed' => (bool) $board->closed,
            'starred' => (bool) $board->starred,
            'background_color' => $board->background_color,
            'background_image_url' => $board->background_image_url,
            'last_activity_at' => optional($board->last_activity_at)?->toIso8601String(),
            'last_synced_at' => optional($board->last_synced_at)?->toIso8601String(),
            'lists' => $board->lists
                ->where('closed', false)
                ->map(fn (TrelloList $list) => [
                    'id' => $list->id,
                    'trello_id' => $list->trello_id,
                    'name' => $list->name,
                    'position' => (float) $list->position,
                    'cards_count' => $list->cards->count(),
                    'cards' => $list->cards->map(fn (TrelloCard $card) => $this->serializeCard($card))->values()->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    public function serializeBoardsOverview(Collection $boards): array
    {
        return $boards->map(function (TrelloBoard $board) {
            return [
                'id' => $board->id,
                'trello_id' => $board->trello_id,
                'name' => $board->name,
                'description' => $board->description,
                'url' => $board->url,
                'closed' => (bool) $board->closed,
                'starred' => (bool) $board->starred,
                'background_color' => $board->background_color,
                'background_image_url' => $board->background_image_url,
                'cards_count' => (int) ($board->cards_count ?? 0),
                'lists_count' => (int) ($board->lists_count ?? 0),
                'last_activity_at' => optional($board->last_activity_at)?->toIso8601String(),
                'last_synced_at' => optional($board->last_synced_at)?->toIso8601String(),
            ];
        })->values()->all();
    }

    private function serializeCard(TrelloCard $card): array
    {
        return [
            'id' => $card->id,
            'trello_id' => $card->trello_id,
            'list_id' => $card->trello_list_id,
            'board_id' => $card->trello_board_id,
            'name' => $card->name,
            'description' => $card->description,
            'url' => $card->url,
            'short_url' => $card->short_url,
            'position' => (float) $card->position,
            'closed' => (bool) $card->closed,
            'due_at' => optional($card->due_at)?->toIso8601String(),
            'last_activity_at' => optional($card->last_activity_at)?->toIso8601String(),
            'labels' => (array) $card->labels,
            'members' => (array) $card->members,
            'badges' => (array) $card->badges,
            'cover_color' => $card->cover_color,
            'cover_image_url' => $card->cover_image_url,
            'link' => $card->link ? [
                'id' => $card->link->id,
                'project_id' => $card->link->project_id,
                'project_name' => optional($card->link->project)->name,
                'notes' => $card->link->notes,
                'linked_at' => optional($card->link->linked_at)?->toIso8601String(),
            ] : null,
        ];
    }

    private function upsertBoards(int $tenantId, array $boards, $timestamp): void
    {
        $rows = collect($boards)->map(function (array $board) use ($tenantId, $timestamp) {
            return [
                'tenant_id' => $tenantId,
                'trello_id' => (string) ($board['id'] ?? ''),
                'name' => (string) ($board['name'] ?? 'Board sans nom'),
                'description' => (string) ($board['desc'] ?? ''),
                'url' => (string) ($board['url'] ?? ''),
                'workspace_id' => Arr::get($board, 'idOrganization'),
                'background_color' => Arr::get($board, 'prefs.backgroundColor'),
                'background_image_url' => Arr::get($board, 'prefs.backgroundImage'),
                'closed' => (bool) ($board['closed'] ?? false),
                'starred' => (bool) ($board['starred'] ?? false),
                'last_activity_at' => $this->parseDate($board['dateLastActivity'] ?? null),
                'last_synced_at' => $timestamp,
                'raw_payload' => json_encode($board, JSON_UNESCAPED_UNICODE),
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ];
        })->filter(fn (array $row) => $row['trello_id'] !== '')->values()->all();

        if ($rows !== []) {
            DB::table('trello_boards')->upsert(
                $rows,
                ['tenant_id', 'trello_id'],
                ['name', 'description', 'url', 'workspace_id', 'background_color', 'background_image_url', 'closed', 'starred', 'last_activity_at', 'last_synced_at', 'raw_payload', 'updated_at']
            );
        }
    }

    private function upsertLists(int $tenantId, int $boardId, array $lists, $timestamp): void
    {
        $rows = collect($lists)->map(function (array $list) use ($tenantId, $boardId, $timestamp) {
            return [
                'tenant_id' => $tenantId,
                'trello_board_id' => $boardId,
                'trello_id' => (string) ($list['id'] ?? ''),
                'name' => (string) ($list['name'] ?? 'Liste'),
                'position' => $this->normalizePosition($list['pos'] ?? null),
                'closed' => (bool) ($list['closed'] ?? false),
                'last_synced_at' => $timestamp,
                'raw_payload' => json_encode($list, JSON_UNESCAPED_UNICODE),
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ];
        })->filter(fn (array $row) => $row['trello_id'] !== '')->values()->all();

        if ($rows !== []) {
            DB::table('trello_lists')->upsert(
                $rows,
                ['tenant_id', 'trello_id'],
                ['trello_board_id', 'name', 'position', 'closed', 'last_synced_at', 'raw_payload', 'updated_at']
            );
        }
    }

    private function upsertCards(int $tenantId, int $boardId, array $cards, Collection $listMap, $timestamp): void
    {
        $rows = collect($cards)->map(function (array $card) use ($tenantId, $boardId, $listMap, $timestamp) {
            $remoteListId = (string) ($card['idList'] ?? '');
            $localListId = optional($listMap->get($remoteListId))->id;

            return [
                'tenant_id' => $tenantId,
                'trello_board_id' => $boardId,
                'trello_list_id' => $localListId,
                'trello_id' => (string) ($card['id'] ?? ''),
                'name' => (string) ($card['name'] ?? 'Carte'),
                'description' => (string) ($card['desc'] ?? ''),
                'url' => (string) ($card['url'] ?? ''),
                'short_url' => (string) ($card['shortUrl'] ?? ''),
                'position' => $this->normalizePosition($card['pos'] ?? null),
                'due_at' => $this->parseDate($card['due'] ?? null),
                'last_activity_at' => $this->parseDate($card['dateLastActivity'] ?? null),
                'closed' => (bool) ($card['closed'] ?? false),
                'labels' => json_encode((array) ($card['labels'] ?? []), JSON_UNESCAPED_UNICODE),
                'members' => json_encode($this->normalizeMembers($card), JSON_UNESCAPED_UNICODE),
                'badges' => json_encode((array) ($card['badges'] ?? []), JSON_UNESCAPED_UNICODE),
                'cover_color' => Arr::get($card, 'cover.color'),
                'cover_image_url' => Arr::get($card, 'cover.scaled.0.url') ?? Arr::get($card, 'cover.url'),
                'last_synced_at' => $timestamp,
                'raw_payload' => json_encode($card, JSON_UNESCAPED_UNICODE),
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ];
        })->filter(fn (array $row) => $row['trello_id'] !== '')->values()->all();

        if ($rows !== []) {
            DB::table('trello_cards')->upsert(
                $rows,
                ['tenant_id', 'trello_id'],
                ['trello_board_id', 'trello_list_id', 'name', 'description', 'url', 'short_url', 'position', 'due_at', 'last_activity_at', 'closed', 'labels', 'members', 'badges', 'cover_color', 'cover_image_url', 'last_synced_at', 'raw_payload', 'updated_at']
            );
        }
    }

    private function upsertSingleCard(int $tenantId, int $boardId, array $card): TrelloCard
    {
        $list = TrelloList::query()->where('tenant_id', $tenantId)->where('trello_id', (string) ($card['idList'] ?? ''))->first();
        $timestamp = now();

        DB::table('trello_cards')->upsert([
            [
                'tenant_id' => $tenantId,
                'trello_board_id' => $boardId,
                'trello_list_id' => $list?->id,
                'trello_id' => (string) ($card['id'] ?? ''),
                'name' => (string) ($card['name'] ?? 'Carte'),
                'description' => (string) ($card['desc'] ?? ''),
                'url' => (string) ($card['url'] ?? ''),
                'short_url' => (string) ($card['shortUrl'] ?? ''),
                'position' => $this->normalizePosition($card['pos'] ?? null),
                'due_at' => $this->parseDate($card['due'] ?? null),
                'last_activity_at' => $this->parseDate($card['dateLastActivity'] ?? null),
                'closed' => (bool) ($card['closed'] ?? false),
                'labels' => json_encode((array) ($card['labels'] ?? []), JSON_UNESCAPED_UNICODE),
                'members' => json_encode($this->normalizeMembers($card), JSON_UNESCAPED_UNICODE),
                'badges' => json_encode((array) ($card['badges'] ?? []), JSON_UNESCAPED_UNICODE),
                'cover_color' => Arr::get($card, 'cover.color'),
                'cover_image_url' => Arr::get($card, 'cover.scaled.0.url') ?? Arr::get($card, 'cover.url'),
                'last_synced_at' => $timestamp,
                'raw_payload' => json_encode($card, JSON_UNESCAPED_UNICODE),
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ],
        ], ['tenant_id', 'trello_id'], ['trello_board_id', 'trello_list_id', 'name', 'description', 'url', 'short_url', 'position', 'due_at', 'last_activity_at', 'closed', 'labels', 'members', 'badges', 'cover_color', 'cover_image_url', 'last_synced_at', 'raw_payload', 'updated_at']);

        return TrelloCard::query()->where('tenant_id', $tenantId)->where('trello_id', (string) ($card['id'] ?? ''))->firstOrFail();
    }

    private function withToken(int $tenantId, callable $callback): mixed
    {
        $token = $this->auth->getTokenOrFail($tenantId);

        try {
            return $callback((string) $token->api_token);
        } catch (RuntimeException $e) {
            if (str_contains(mb_strtolower($e->getMessage()), 'session trello expiree')) {
                $this->auth->invalidateToken($tenantId);
            }

            throw $e;
        }
    }

    private function parseDate(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    private function normalizePosition(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 4, '.', '');
    }

    private function normalizeMembers(array $card): array
    {
        $members = (array) ($card['members'] ?? []);
        if ($members !== []) {
            return $members;
        }

        return collect((array) ($card['idMembers'] ?? []))
            ->map(fn ($memberId) => ['id' => (string) $memberId])
            ->values()
            ->all();
    }
}
