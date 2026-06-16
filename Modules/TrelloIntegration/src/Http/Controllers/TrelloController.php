<?php

namespace Modules\TrelloIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Modules\TrelloIntegration\Models\TrelloBoard;
use Modules\TrelloIntegration\Models\TrelloCard;
use Modules\TrelloIntegration\Models\TrelloList;
use Modules\TrelloIntegration\Services\TrelloAuthService;
use Modules\TrelloIntegration\Services\TrelloSyncService;
use NexusExtensions\Projects\Models\Project;
use RuntimeException;
use Throwable;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class TrelloController extends Controller
{
    public function __construct(
        protected TrelloAuthService $authService,
        protected TrelloSyncService $syncService,
    ) {
    }

    public function index(Request $request)
    {
        $tenantId = $this->tenantId();
        $storageReady = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $configurationStatus = $this->authService->configurationStatus();
        $oauthConfigured = (bool) ($configurationStatus['configured'] ?? false);
        $oauthReady = (bool) ($configurationStatus['ready'] ?? false);
        $token = ($storageReady && $extensionActive) ? $this->authService->getToken($tenantId) : null;

        if ($token && TrelloBoard::query()->count() === 0) {
            try {
                $this->syncService->syncAll($tenantId);
                $token = $this->authService->getToken($tenantId);
            } catch (Throwable) {
                // Best effort only on first load.
            }
        }

        $boards = $token
            ? TrelloBoard::query()
                ->withCount(['lists', 'cards' => fn ($query) => $query->where('closed', false)])
                ->orderByDesc('starred')
                ->orderBy('name')
                ->get()
            : collect();

        $selectedBoardId = $request->integer('board');
        $selectedBoard = $selectedBoardId > 0
            ? $boards->firstWhere('id', $selectedBoardId)
            : $boards->firstWhere('closed', false);

        $projects = collect();
        if (class_exists(Project::class) && Schema::hasTable('projects')) {
            $projects = Project::query()
                ->select('id', 'name', 'status')
                ->latest('updated_at')
                ->limit((int) config('trello-integration.ui.max_projects_in_picker', 100))
                ->get();
        }

        $boardBootstrap = $this->syncService->serializeBoardsOverview($boards);
        $selectedBoardPayload = $selectedBoard ? $this->syncService->boardSnapshot($selectedBoard) : null;

        return view('trello-integration::trello.index', [
            'storageReady' => $storageReady,
            'extensionActive' => $extensionActive,
            'oauthConfigured' => $oauthConfigured,
            'oauthReady' => $oauthReady,
            'configurationStatus' => $configurationStatus,
            'connected' => (bool) $token,
            'token' => $token,
            'boards' => $boards,
            'projects' => $projects,
            'selectedBoard' => $selectedBoard,
            'trelloBootstrap' => [
                'boards' => $boardBootstrap,
                'selectedBoardId' => $selectedBoard?->id,
                'selectedBoard' => $selectedBoardPayload,
                'projects' => $projects->map(fn ($project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                ])->values()->all(),
                'routes' => [
                    'index' => route('trello-integration.index'),
                    'connect' => route('trello-integration.connect'),
                    'disconnect' => route('trello-integration.disconnect'),
                    'sync' => route('trello-integration.sync'),
                    'finalize' => route('trello-integration.oauth.finalize'),
                    'board' => route('trello-integration.boards.show', ['board' => '__BOARD__']),
                    'card' => route('trello-integration.cards.show', ['card' => '__CARD__']),
                    'cardUpdate' => route('trello-integration.cards.update', ['card' => '__CARD__']),
                    'cardMove' => route('trello-integration.cards.move', ['card' => '__CARD__']),
                    'cardArchive' => route('trello-integration.cards.archive', ['card' => '__CARD__']),
                    'listCreateCard' => route('trello-integration.lists.cards.store', ['list' => '__LIST__']),
                ],
            ],
        ]);
    }

    public function connect()
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            return redirect()->away($this->authService->getAuthUrl($tenantId, (int) Auth::id()));
        } catch (Throwable $e) {
            return redirect()->route('trello-integration.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        return view('trello-integration::trello.callback', [
            'state' => (string) $request->string('state'),
            'error' => (string) $request->string('error'),
        ]);
    }

    public function finalizeOauth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['required', 'string'],
            'token' => ['nullable', 'string'],
            'error' => ['nullable', 'string'],
        ]);

        if (!empty($validated['error'])) {
            return response()->json([
                'success' => false,
                'message' => (string) $validated['error'],
            ], 422);
        }

        if (empty($validated['token'])) {
            return response()->json([
                'success' => false,
                'message' => 'Token Trello manquant apres autorisation.',
            ], 422);
        }

        try {
            $state = $this->authService->parseState((string) $validated['state']);
            $tenantId = (int) $state['tenant_id'];
            $userId = (int) $state['user_id'];

            if ((int) Auth::id() !== $userId || $this->tenantId() !== $tenantId) {
                throw new RuntimeException('Etat OAuth Trello invalide pour la session en cours.');
            }

            $this->ensureExtensionActivated($tenantId);
            $this->authService->exchangeToken((string) $validated['token'], $tenantId, $userId);
            $this->syncService->syncAll($tenantId);

            return response()->json([
                'success' => true,
                'message' => 'Trello connecte avec succes.',
                'redirect' => route('trello-integration.index'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function disconnect(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $this->authService->disconnect($tenantId);

            return response()->json([
                'success' => true,
                'message' => 'Trello deconnecte.',
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function sync(): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $result = $this->syncService->syncAll($tenantId);

            return response()->json([
                'success' => true,
                'message' => 'Synchronisation Trello terminee.',
                'data' => $result,
                'boards' => $this->syncService->serializeBoardsOverview(
                    TrelloBoard::query()->withCount(['lists', 'cards' => fn ($query) => $query->where('closed', false)])->orderByDesc('starred')->orderBy('name')->get()
                ),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function board(TrelloBoard $board): JsonResponse
    {
        try {
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);
            $board = $this->syncService->syncBoard($tenantId, $board);

            return response()->json([
                'success' => true,
                'data' => $this->syncService->boardSnapshot($board),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function showCard(TrelloCard $card): JsonResponse
    {
        $card->load('link.project');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $card->id,
                'name' => $card->name,
                'description' => $card->description,
                'due_at' => optional($card->due_at)?->toIso8601String(),
                'url' => $card->url,
                'labels' => (array) $card->labels,
                'members' => (array) $card->members,
                'badges' => (array) $card->badges,
                'link' => $card->link ? [
                    'project_id' => $card->link->project_id,
                    'project_name' => optional($card->link->project)->name,
                    'notes' => $card->link->notes,
                ] : null,
            ],
        ]);
    }

    public function storeCard(Request $request, TrelloList $list): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due' => ['nullable', 'date'],
        ]);

        try {
            $card = $this->syncService->createCard($this->tenantId(), $list, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Carte Trello creee.',
                'data' => ['card_id' => $card->id],
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateCard(Request $request, TrelloCard $card): JsonResponse
    {
        $projectRule = ['nullable', 'integer'];

        if (class_exists(Project::class) && Schema::hasTable('projects')) {
            $projectRule[] = Rule::exists('projects', 'id')->where(function ($query) {
                $query->where('tenant_id', $this->tenantId());
            });
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due' => ['nullable', 'date'],
            'project_id' => $projectRule,
            'link_notes' => ['nullable', 'string'],
        ]);

        try {
            $card = $this->syncService->updateCard($this->tenantId(), $card, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Carte Trello mise a jour.',
                'data' => ['card_id' => $card->id],
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function moveCard(Request $request, TrelloCard $card): JsonResponse
    {
        $validated = $request->validate([
            'target_list_id' => ['required', 'integer', 'exists:trello_lists,id'],
            'position' => ['required', 'string', 'max:64'],
        ]);

        try {
            $targetList = TrelloList::query()->findOrFail((int) $validated['target_list_id']);
            if ($targetList->trello_board_id !== $card->trello_board_id) {
                throw new RuntimeException('La carte ne peut pas etre deplacee vers un autre board.');
            }

            $card = $this->syncService->moveCard($this->tenantId(), $card, $targetList, (string) $validated['position']);

            return response()->json([
                'success' => true,
                'message' => 'Carte deplacee.',
                'data' => ['card_id' => $card->id],
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function archiveCard(TrelloCard $card): JsonResponse
    {
        try {
            $card = $this->syncService->archiveCard($this->tenantId(), $card);

            return response()->json([
                'success' => true,
                'message' => 'Carte archivee.',
                'data' => ['card_id' => $card->id],
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function tenantId(): int
    {
        return (int) Auth::user()->tenant_id;
    }

    private function isStorageReady(): bool
    {
        return Schema::hasTable('trello_tokens')
            && Schema::hasTable('trello_boards')
            && Schema::hasTable('trello_lists')
            && Schema::hasTable('trello_cards')
            && Schema::hasTable('trello_links');
    }

    private function isExtensionActive(int $tenantId): bool
    {
        if (!class_exists(Extension::class) || !class_exists(TenantExtension::class) || !Schema::hasTable('extensions') || !Schema::hasTable('tenant_extensions')) {
            return false;
        }

        $extension = Extension::query()->where('slug', 'trello-integration')->where('status', 'active')->first();

        if (!$extension) {
            return false;
        }

        return TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();
    }

    private function ensureExtensionActivated(int $tenantId): void
    {
        if (!$this->isExtensionActive($tenantId)) {
            throw new RuntimeException('L extension Trello n est pas active pour ce tenant.');
        }
    }
}
