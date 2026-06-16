<?php

namespace NexusExtensions\NotionWorkspace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use NexusExtensions\NotionWorkspace\Http\Controllers\Concerns\InteractsWithNotionWorkspace;
use NexusExtensions\NotionWorkspace\Services\NotionWorkspaceApiService;
use RuntimeException;
use Throwable;
use Vendor\Automation\Services\AutomationReconnectNotificationService;

class NotionWorkspaceController extends Controller
{
    use InteractsWithNotionWorkspace;

    public function __construct(protected NotionWorkspaceApiService $service)
    {
    }

    public function index()
    {
        $this->authorizePermission('notion.view');

        $tenantId = $this->tenantId();
        $storageReady = $this->isStorageReady();
        $extensionActive = $storageReady && $this->isExtensionActive($tenantId);
        $token = ($storageReady && $extensionActive) ? $this->connectedToken($tenantId) : null;

        $clients = $this->clientsCollection();
        $projects = $this->projectsCollection();
        $linkedPages = ($storageReady && $extensionActive)
            ? $this->linkedPagesQuery($tenantId)->get()
            : collect();
        $workspaceName = trim((string) ($token?->notion_workspace_name ?? __('notion-workspace::messages.defaults.workspace_name')));
        $workspaceImageUrl = null;
        $workspaceUserAvatarUrl = null;

        if ($token) {
            $workspaceIcon = trim((string) ($token->notion_workspace_icon ?? ''));
            $userAvatar = trim((string) ($token->notion_user_avatar_url ?? ''));

            if (filter_var($workspaceIcon, FILTER_VALIDATE_URL)) {
                $workspaceImageUrl = $workspaceIcon;
            }

            if (filter_var($userAvatar, FILTER_VALIDATE_URL)) {
                $workspaceUserAvatarUrl = $userAvatar;
            }
        }

        $workspaceInitials = Str::of($workspaceName)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $segment) => Str::upper(Str::substr($segment, 0, 1)))
            ->implode('');

        if ($workspaceInitials === '') {
            $workspaceInitials = 'N';
        }

        return view('notion-workspace::notion.index', [
            'storageReady' => $storageReady,
            'extensionActive' => $extensionActive,
            'oauthConfigured' => $this->oauthConfigured(),
            'connected' => (bool) $token,
            'token' => $token,
            'workspaceImageUrl' => $workspaceImageUrl,
            'workspaceImageAlt' => $workspaceName,
            'workspaceInitials' => $workspaceInitials,
            'workspaceUserAvatarUrl' => $workspaceUserAvatarUrl,
            'redirectUri' => $this->service->redirectUri(),
            'clients' => $clients,
            'projects' => $projects,
            'linkedPages' => $linkedPages,
            'linkedPagesBootstrap' => $linkedPages->map(fn ($link) => [
                'id' => $link->id,
                'notion_page_id' => $link->notion_page_id,
                'notion_page_title' => $link->notion_page_title,
                'notion_page_url' => $link->notion_page_url,
                'client_id' => $link->client_id,
                'client_name' => $link->relationLoaded('client') ? $link->client?->company_name : null,
                'project_id' => $link->project_id,
                'project_name' => $link->relationLoaded('project') ? $link->project?->name : null,
                'context_label' => $link->context_label,
                'notes' => $link->notes,
                'updated_at' => optional($link->updated_at)->toIso8601String(),
            ])->values()->all(),
            'clientsBootstrap' => $clients->map(fn ($client) => [
                'id' => $client->id,
                'company_name' => $client->company_name,
            ])->values()->all(),
            'projectsBootstrap' => $projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ])->values()->all(),
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
            return redirect()->route('notion-workspace.index')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('notion-workspace.index')
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
                throw new RuntimeException(__('notion-workspace::messages.errors.oauth_state_mismatch'));
            }

            $this->ensureExtensionActivated($tenantId);
            $this->service->exchangeCode((string) $request->string('code'), $tenantId, $userId);
            app(AutomationReconnectNotificationService::class)
                ->notifyForProvider($tenantId, $userId, 'notion-workspace', route('notion-workspace.index'));

            return redirect()->route('notion-workspace.index')->with('success', __('notion-workspace::messages.success.connected'));
        } catch (Throwable $e) {
            return redirect()->route('notion-workspace.index')->with('error', $e->getMessage());
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
                'message' => __('notion-workspace::messages.success.disconnected'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
