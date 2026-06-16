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

class NotionApiController extends Controller
{
    use InteractsWithNotionWorkspace;

    public function __construct(protected NotionWorkspaceApiService $service)
    {
    }

    public function pages(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'cursor' => ['nullable', 'string', 'max:255'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $this->authorizePermission('notion.view');
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $this->service->searchPages(
                $tenantId,
                (string) $request->string('query', ''),
                $request->filled('cursor') ? (string) $request->string('cursor') : null,
                (int) $request->integer('page_size', (int) config('notion-workspace.api.page_size', 20))
            );

            $linked = NotionPageLink::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('notion_page_id', collect($data['results'])->pluck('id')->filter()->all())
                ->get()
                ->keyBy('notion_page_id');

            $results = collect($data['results'])->map(function (array $page) use ($linked) {
                $link = $linked->get($page['id']);
                $page['link'] = $link ? [
                    'id' => $link->id,
                    'client_id' => $link->client_id,
                    'project_id' => $link->project_id,
                    'context_label' => $link->context_label,
                    'notes' => $link->notes,
                ] : null;
                return $page;
            })->values()->all();

            return response()->json([
                'success' => true,
                'data' => $results,
                'has_more' => $data['has_more'],
                'next_cursor' => $data['next_cursor'],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(string $pageId): JsonResponse
    {
        try {
            $this->authorizePermission('notion.view');
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $data = $this->service->getPage($tenantId, $pageId);
            $linkQuery = NotionPageLink::query()
                ->where('tenant_id', $tenantId)
                ->where('notion_page_id', $pageId);

            $relations = [];
            if (class_exists(\Vendor\Client\Models\Client::class) && Schema::hasTable('clients')) {
                $relations[] = 'client:id,company_name';
            }
            if (class_exists(\NexusExtensions\Projects\Models\Project::class) && Schema::hasTable('projects')) {
                $relations[] = 'project:id,name';
            }
            if (!empty($relations)) {
                $linkQuery->with($relations);
            }

            $link = $linkQuery->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'page' => $data['page'],
                    'blocks' => $data['blocks'],
                    'link' => $link ? [
                        'id' => $link->id,
                        'client_id' => $link->client_id,
                        'client_name' => $link->relationLoaded('client') ? $link->client?->company_name : null,
                        'project_id' => $link->project_id,
                        'project_name' => $link->relationLoaded('project') ? $link->project?->name : null,
                        'context_label' => $link->context_label,
                        'notes' => $link->notes,
                    ] : null,
                ],
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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content' => ['nullable', 'string', 'max:20000'],
            'parent_page_id' => ['nullable', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $this->authorizePermission('notion.create');
            $tenantId = $this->tenantId();
            $this->ensureExtensionActivated($tenantId);

            $page = $this->service->createPage($tenantId, $validated);

            return response()->json([
                'success' => true,
                'message' => __('notion-workspace::messages.success.page_created'),
                'data' => $page,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
