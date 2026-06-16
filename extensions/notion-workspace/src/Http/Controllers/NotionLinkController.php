<?php

namespace NexusExtensions\NotionWorkspace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\NotionWorkspace\Http\Controllers\Concerns\InteractsWithNotionWorkspace;
use NexusExtensions\NotionWorkspace\Models\NotionPageLink;
use NexusExtensions\NotionWorkspace\Services\NotionWorkspaceApiService;
use Throwable;

class NotionLinkController extends Controller
{
    use InteractsWithNotionWorkspace;

    public function __construct(protected NotionWorkspaceApiService $service)
    {
    }

    public function index(): JsonResponse
    {
        try {
            $this->authorizePermission('notion.view');
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $links = $this->linkedPagesQuery($tenantId)->get()->map(fn (NotionPageLink $link) => $this->formatLink($link))->values();

            return response()->json([
                'success' => true,
                'data' => $links,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'notion_page_id' => ['required', 'string', 'max:100'],
            'notion_page_title' => ['required', 'string', 'max:255'],
            'notion_page_url' => ['nullable', 'url', 'max:2048'],
            'notion_parent_id' => ['nullable', 'string', 'max:100'],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'context_label' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        try {
            $this->authorizePermission('notion.share');
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $clientId = $request->filled('client_id') ? (int) $request->integer('client_id') : null;
            $projectId = $request->filled('project_id') ? (int) $request->integer('project_id') : null;

            $this->assertTenantClient($clientId);
            $this->assertTenantProject($projectId);

            $pageMeta = $this->service->refreshPageLinkMetadata($tenantId, (string) $request->string('notion_page_id'));

            $link = NotionPageLink::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'notion_page_id' => (string) $request->string('notion_page_id'),
                ],
                [
                    'notion_parent_id' => $pageMeta['parent']['page_id'] ?? $request->input('notion_parent_id'),
                    'notion_page_title' => $pageMeta['title'] ?: (string) $request->string('notion_page_title'),
                    'notion_page_url' => $pageMeta['url'] ?: $request->input('notion_page_url'),
                    'client_id' => $clientId,
                    'project_id' => $projectId,
                    'context_label' => $request->filled('context_label') ? (string) $request->string('context_label') : null,
                    'notes' => $request->filled('notes') ? (string) $request->string('notes') : null,
                    'linked_by' => (int) auth()->id(),
                    'last_synced_at' => now(),
                ]
            );

            $this->loadLinkRelations($link);

            return response()->json([
                'success' => true,
                'message' => __('notion-workspace::messages.success.link_created'),
                'data' => $this->formatLink($link),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(Request $request, NotionPageLink $link): JsonResponse
    {
        $request->validate([
            'client_id' => ['nullable', 'integer', 'min:1'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'context_label' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        try {
            $this->authorizePermission('notion.share');
            abort_if((int) $link->tenant_id !== $this->tenantId(), 404);
            $this->ensureExtensionActivated($this->tenantId());

            $clientId = $request->filled('client_id') ? (int) $request->integer('client_id') : null;
            $projectId = $request->filled('project_id') ? (int) $request->integer('project_id') : null;
            $this->assertTenantClient($clientId);
            $this->assertTenantProject($projectId);

            $pageMeta = $this->service->refreshPageLinkMetadata($this->tenantId(), $link->notion_page_id);

            $link->update([
                'notion_parent_id' => $pageMeta['parent']['page_id'] ?? $link->notion_parent_id,
                'notion_page_title' => $pageMeta['title'] ?: $link->notion_page_title,
                'notion_page_url' => $pageMeta['url'] ?: $link->notion_page_url,
                'client_id' => $clientId,
                'project_id' => $projectId,
                'context_label' => $request->filled('context_label') ? (string) $request->string('context_label') : null,
                'notes' => $request->filled('notes') ? (string) $request->string('notes') : null,
                'linked_by' => (int) auth()->id(),
                'last_synced_at' => now(),
            ]);

            $this->loadLinkRelations($link);

            return response()->json([
                'success' => true,
                'message' => __('notion-workspace::messages.success.link_updated'),
                'data' => $this->formatLink($link),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(NotionPageLink $link): JsonResponse
    {
        try {
            $this->authorizePermission('notion.share');
            abort_if((int) $link->tenant_id !== $this->tenantId(), 404);
            $this->ensureExtensionActivated($this->tenantId());

            $link->delete();

            return response()->json([
                'success' => true,
                'message' => __('notion-workspace::messages.success.link_deleted'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function formatLink(NotionPageLink $link): array
    {
        return [
            'id' => $link->id,
            'notion_page_id' => $link->notion_page_id,
            'notion_page_title' => $link->notion_page_title,
            'notion_page_url' => $link->notion_page_url,
            'notion_parent_id' => $link->notion_parent_id,
            'client_id' => $link->client_id,
            'client_name' => $link->relationLoaded('client') ? $link->client?->company_name : null,
            'project_id' => $link->project_id,
            'project_name' => $link->relationLoaded('project') ? $link->project?->name : null,
            'context_label' => $link->context_label,
            'notes' => $link->notes,
            'linked_by' => $link->relationLoaded('linkedBy') ? $link->linkedBy?->name : null,
            'last_synced_at' => optional($link->last_synced_at)->toIso8601String(),
            'updated_at' => optional($link->updated_at)->toIso8601String(),
        ];
    }

    private function loadLinkRelations(NotionPageLink $link): void
    {
        $relations = ['linkedBy:id,name'];

        if (class_exists(\Vendor\Client\Models\Client::class) && Schema::hasTable('clients')) {
            $relations[] = 'client:id,company_name';
        }

        if (class_exists(\NexusExtensions\Projects\Models\Project::class) && Schema::hasTable('projects')) {
            $relations[] = 'project:id,name';
        }

        $link->load($relations);
    }
}
