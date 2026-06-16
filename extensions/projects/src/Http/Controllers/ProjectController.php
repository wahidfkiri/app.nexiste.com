<?php

namespace NexusExtensions\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DraftService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use NexusExtensions\GoogleDrive\Models\GoogleDriveFile;
use NexusExtensions\GoogleDrive\Services\GoogleDriveService;
use NexusExtensions\Projects\Events\ProjectCreated;
use NexusExtensions\Projects\Events\ProjectTaskCreated;
use NexusExtensions\Projects\Http\Requests\ProjectStoreRequest;
use NexusExtensions\Projects\Http\Requests\ProjectTaskStoreRequest;
use NexusExtensions\Projects\Http\Requests\ProjectTaskUpdateRequest;
use NexusExtensions\Projects\Http\Requests\ProjectUpdateRequest;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Projects\Models\ProjectActivity;
use NexusExtensions\Projects\Models\ProjectFile;
use NexusExtensions\Projects\Models\ProjectMember;
use NexusExtensions\Projects\Models\ProjectTask;
use NexusExtensions\Projects\Models\ProjectTaskChecklist;
use NexusExtensions\Projects\Models\ProjectTaskComment;
use NexusExtensions\Projects\Models\ProjectTaskFile;
use RuntimeException;
use Vendor\Client\Models\Client;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Automation\Services\AutomationSuggestionPresenter;
use Vendor\GoogleCalendar\Services\GoogleCalendarService;

class ProjectController extends Controller
{
    public function index()
    {
        $this->authorizePermission('projects.view');
        $tenantId = (int) auth()->user()->tenant_id;
        $clientsInstalled = $this->isMarketplaceExtensionActive($tenantId, 'clients');
        $googleCalendarInstalled = $this->isMarketplaceExtensionActive($tenantId, 'google-calendar');

        return view('projects::projects.index', [
            'statuses' => config('projects.project_statuses', []),
            'priorities' => config('projects.priorities', []),
            'clients' => $clientsInstalled
                ? Client::query()->orderBy('company_name')->get(['id', 'company_name'])
                : collect(),
            'users' => User::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'clientsInstalled' => $clientsInstalled,
            'clientsTargetUrl' => $this->resolveExtensionTargetUrl('clients', $clientsInstalled, 'clients.index'),
            'googleCalendarInstalled' => $googleCalendarInstalled,
            'googleCalendarTargetUrl' => $this->resolveExtensionTargetUrl('google-calendar', $googleCalendarInstalled, 'google-calendar.index'),
        ]);
    }

    public function show(Project $project)
    {
        $this->authorizeProjectAccess($project, 'projects.view');
        $tenantId = (int) auth()->user()->tenant_id;
        $clientsInstalled = $this->isMarketplaceExtensionActive($tenantId, 'clients');
        $googleCalendarInstalled = $this->isMarketplaceExtensionActive($tenantId, 'google-calendar');
        $taskStatuses = $this->resolveProjectStatusMap($project);

        $project->load([
            'client:id,company_name,contact_name,email,phone',
            'owner:id,name,email',
            'members.user:id,name,email,status,role_in_tenant',
        ]);

        return view('projects::projects.show', [
            'project' => $project,
            'taskStatuses' => $taskStatuses,
            'priorities' => config('projects.priorities', []),
            'clients' => $clientsInstalled
                ? Client::query()->orderBy('company_name')->get(['id', 'company_name'])
                : collect(),
            'users' => User::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'clientsInstalled' => $clientsInstalled,
            'clientsTargetUrl' => $this->resolveExtensionTargetUrl('clients', $clientsInstalled, 'clients.index'),
            'googleCalendarInstalled' => $googleCalendarInstalled,
            'googleCalendarTargetUrl' => $this->resolveExtensionTargetUrl('google-calendar', $googleCalendarInstalled, 'google-calendar.index'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission('projects.view');

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', 'string', 'max:40'],
            'client_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'sort_by' => ['nullable', 'in:name,status,priority,progress,due_date,created_at'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ]);

        $query = Project::query()
            ->with([
                'client:id,company_name',
                'owner:id,name',
            ])
            ->withCount(['tasks', 'members']);

        if (!$this->hasAnyPermission(['projects.admin']) && !$this->isTenantAdmin()) {
            $userId = (int) auth()->id();
            $query->where(function ($q) use ($userId) {
                $q->where('owner_id', $userId)
                    ->orWhereHas('members', fn ($m) => $m->where('user_id', $userId)->where('is_active', true));
            });
        }

        if ($request->filled('search')) {
            $term = (string) $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', (string) $request->string('priority'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->integer('client_id'));
        }

        $sortBy = (string) $request->string('sort_by', 'created_at');
        $sortDir = (string) $request->string('sort_dir', 'desc');
        $perPage = (int) $request->integer('per_page', 15);

        $projects = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        return response()->json([
            'data' => $projects->getCollection()->map(fn (Project $project) => $this->formatProject($project))->values(),
            'current_page' => $projects->currentPage(),
            'last_page' => $projects->lastPage(),
            'per_page' => $projects->perPage(),
            'total' => $projects->total(),
            'from' => $projects->firstItem(),
            'to' => $projects->lastItem(),
        ]);
    }

    public function stats(): JsonResponse
    {
        $this->authorizePermission('projects.view');

        $query = Project::query();

        if (!$this->hasAnyPermission(['projects.admin']) && !$this->isTenantAdmin()) {
            $userId = (int) auth()->id();
            $query->where(function ($q) use ($userId) {
                $q->where('owner_id', $userId)
                    ->orWhereHas('members', fn ($m) => $m->where('user_id', $userId)->where('is_active', true));
            });
        }

        $total = (clone $query)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => (clone $query)->where('status', 'active')->count(),
                'planning' => (clone $query)->where('status', 'planning')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'delayed' => (clone $query)->whereNotIn('status', ['completed', 'archived'])->whereDate('due_date', '<', now()->toDateString())->count(),
            ],
        ]);
    }

    public function store(ProjectStoreRequest $request): JsonResponse
    {
        $this->authorizePermission('projects.create');

        try {
            $syncGoogleCalendar = false;
            $calendarId = null;

            $project = DB::transaction(function () use ($request, &$syncGoogleCalendar, &$calendarId) {
                $payload = $request->validated();
                $syncGoogleCalendar = filter_var($payload['sync_google_calendar'] ?? false, FILTER_VALIDATE_BOOL);
                $calendarId = isset($payload['calendar_id']) ? trim((string) $payload['calendar_id']) : null;
                unset($payload['sync_google_calendar'], $payload['calendar_id']);
                $payload['client_id'] = $this->resolveTenantClientId($payload['client_id'] ?? null, (int) auth()->user()->tenant_id);
                $payload['owner_id'] = (int) auth()->id();
                $payload['status'] = $payload['status'] ?? 'planning';
                $payload['priority'] = $payload['priority'] ?? 'medium';
                $payload['progress'] = 0;
                $payload['slug'] = $this->makeProjectSlug((string) $payload['name']);

                $project = Project::query()->create($payload);

                ProjectMember::query()->updateOrCreate(
                    ['project_id' => $project->id, 'user_id' => (int) auth()->id()],
                    ['role' => 'owner', 'is_active' => true, 'joined_at' => now(), 'invited_by' => (int) auth()->id()]
                );

                $memberIds = collect($payload['member_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique();
                if ($memberIds->isNotEmpty()) {
                    $allowedUserIds = User::query()
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->whereIn('id', $memberIds->values())
                        ->pluck('id');

                    foreach ($allowedUserIds as $userId) {
                        ProjectMember::query()->updateOrCreate(
                            ['project_id' => $project->id, 'user_id' => (int) $userId],
                            ['role' => $userId === auth()->id() ? 'owner' : 'member', 'is_active' => true, 'joined_at' => now(), 'invited_by' => (int) auth()->id()]
                        );
                    }
                }

                $this->logActivity('project_created', 'Projet cree', $project, null, ['name' => $project->name]);

                return $project;
            });

            $calendarSync = $syncGoogleCalendar
                ? $this->syncProjectCalendarOptional($project, $calendarId)
                : ['event' => null, 'warning' => null, 'action_url' => null];
            app(DraftService::class)->forgetFromRequest($request);

            event(new ProjectCreated(
                $project->fresh(['client:id,company_name', 'owner:id,name,email']),
                [
                    'created_via' => $request->expectsJson() ? 'api' : 'web',
                    'calendar_requested' => $syncGoogleCalendar,
                    'calendar_synced' => !empty($calendarSync['event']),
                    'calendar_warning' => $calendarSync['warning'],
                ]
            ));

            $message = $calendarSync['event']
                ? 'Projet cree et planifie dans Google Calendar.'
                : ($syncGoogleCalendar && $calendarSync['warning']
                    ? 'Projet cree, mais synchronisation Google Calendar echouee.'
                    : 'Projet cree avec succes.');

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $this->formatProject($project->fresh(['client:id,company_name', 'owner:id,name'])->loadCount(['tasks', 'members'])),
                'automation' => app(AutomationSuggestionPresenter::class)->buildPromptForSource(
                    'project_created',
                    $project::class,
                    $project->getKey(),
                    (int) $project->tenant_id
                ),
                'redirect' => route('projects.show', $project),
                'calendar' => $calendarSync['event'],
                'calendar_warning' => $calendarSync['warning'],
                'calendar_action_url' => $calendarSync['action_url'],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(ProjectUpdateRequest $request, Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.update');

        try {
            $payload = $request->validated();
            $syncGoogleCalendar = filter_var($payload['sync_google_calendar'] ?? false, FILTER_VALIDATE_BOOL);
            $calendarId = isset($payload['calendar_id']) ? trim((string) $payload['calendar_id']) : null;
            unset($payload['sync_google_calendar'], $payload['calendar_id']);
            $payload['client_id'] = $this->resolveTenantClientId($payload['client_id'] ?? null, (int) auth()->user()->tenant_id);

            if (($payload['status'] ?? null) === 'completed' && !$project->completed_at) {
                $payload['completed_at'] = now();
                $payload['progress'] = 100;
            }

            $project->update($payload);

            $calendarSync = $syncGoogleCalendar
                ? $this->syncProjectCalendarOptional($project, $calendarId)
                : ['event' => null, 'warning' => null, 'action_url' => null];
            app(DraftService::class)->forgetFromRequest($request);

            $this->logActivity('project_updated', 'Projet mis a jour', $project, null, $payload);

            return response()->json([
                'success' => true,
                'message' => $calendarSync['event']
                    ? 'Projet mis a jour et synchronise avec Google Calendar.'
                    : ($syncGoogleCalendar && $calendarSync['warning']
                        ? 'Projet mis a jour, mais synchronisation Google Calendar echouee.'
                        : 'Projet mis a jour.'),
                'data' => $this->formatProject($project->fresh(['client:id,company_name', 'owner:id,name'])->loadCount(['tasks', 'members'])),
                'calendar' => $calendarSync['event'],
                'calendar_warning' => $calendarSync['warning'],
                'calendar_action_url' => $calendarSync['action_url'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.delete');

        try {
            DB::transaction(function () use ($project) {
                ProjectTask::query()->where('project_id', $project->id)->delete();
                ProjectMember::query()->where('project_id', $project->id)->delete();
                ProjectActivity::query()->where('project_id', $project->id)->delete();
                $project->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Projet supprime.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function syncMembers(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_members');

        $request->validate([
            'members' => ['required', 'array', 'min:1'],
            'members.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'members.*.role' => ['nullable', 'in:owner,manager,member,viewer'],
        ]);

        try {
            DB::transaction(function () use ($request, $project) {
                $incoming = collect($request->input('members', []));

                $tenantUserIds = User::query()
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->whereIn('id', $incoming->pluck('user_id')->all())
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $allowed = $incoming
                    ->filter(fn ($row) => in_array((int) ($row['user_id'] ?? 0), $tenantUserIds, true))
                    ->map(function ($row) use ($project) {
                        $uid = (int) $row['user_id'];
                        $role = (string) ($row['role'] ?? 'member');

                        if ($uid === (int) $project->owner_id) {
                            $role = 'owner';
                        }

                        return [
                            'user_id' => $uid,
                            'role' => $role,
                        ];
                    })
                    ->unique('user_id')
                    ->values();

                if (!$allowed->pluck('user_id')->contains((int) $project->owner_id)) {
                    $allowed->push(['user_id' => (int) $project->owner_id, 'role' => 'owner']);
                }

                $currentIds = ProjectMember::query()->where('project_id', $project->id)->pluck('user_id')->map(fn ($id) => (int) $id);
                $newIds = $allowed->pluck('user_id');

                $toDelete = $currentIds->diff($newIds)->all();
                if (!empty($toDelete)) {
                    ProjectMember::query()->where('project_id', $project->id)->whereIn('user_id', $toDelete)->delete();
                }

                foreach ($allowed as $row) {
                    ProjectMember::query()->updateOrCreate(
                        ['project_id' => $project->id, 'user_id' => (int) $row['user_id']],
                        [
                            'role' => $row['role'],
                            'is_active' => true,
                            'joined_at' => now(),
                            'invited_by' => (int) auth()->id(),
                        ]
                    );
                }
            });

            $this->logActivity('members_synced', 'Membres du projet synchronises', $project, null, ['updated_by' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Membres mis a jour.',
                'data' => ProjectMember::query()
                    ->where('project_id', $project->id)
                    ->with('user:id,name,email')
                    ->orderByRaw("FIELD(role,'owner','manager','member','viewer')")
                    ->orderBy('id')
                    ->get(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function tasksData(Project $project, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');
        $statusMap = $this->resolveProjectStatusMap($project);
        $allowedStatuses = array_keys($statusMap);

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in($allowedStatuses)],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        $query = ProjectTask::query()
            ->where('project_id', $project->id)
            ->with(['assignee:id,name,email', 'creator:id,name', 'client:id,company_name'])
            ->withCount(['comments', 'checklist as checklist_total', 'checklist as checklist_done' => fn ($q) => $q->where('is_done', true)]);

        if ($request->filled('search')) {
            $term = (string) $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('assignee', fn ($u) => $u->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', (int) $request->integer('assigned_to'));
        }

        $tasks = $query->orderBy('position')->orderByDesc('id')->get();

        foreach ($tasks->pluck('status')->filter()->unique() as $status) {
            $statusKey = (string) $status;
            if (!array_key_exists($statusKey, $statusMap)) {
                $statusMap[$statusKey] = $this->formatStatusLabel($statusKey);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $tasks->map(fn (ProjectTask $task) => $this->formatTask($task))->values(),
            'grouped' => collect($statusMap)
                ->keys()
                ->mapWithKeys(fn ($status) => [$status => $tasks->where('status', $status)->map(fn ($task) => $this->formatTask($task))->values()]),
            'status_map' => $statusMap,
        ]);
    }

    public function boardsData(Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');

        return response()->json([
            'success' => true,
            'data' => $this->resolveProjectBoards($project),
            'status_map' => $this->resolveProjectStatusMap($project),
            'can_manage' => $this->canManageProjectBoards($project),
        ]);
    }

    public function storeBoard(Project $project, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');
        abort_if(!$this->canManageProjectBoards($project), 403, 'Seul le proprietaire (ou admin) peut creer des boards.');
        $statusMap = $this->resolveProjectStatusMap($project);

        $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['string', Rule::in(array_keys($statusMap))],
            'columns' => ['nullable', 'array', 'min:1'],
            'columns.*.label' => ['required', 'string', 'max:40'],
            'columns.*.status' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $metadata = is_array($project->metadata) ? $project->metadata : [];
            $boards = collect($metadata['boards'] ?? [])
                ->filter(fn ($row) => is_array($row) && isset($row['id'], $row['name']))
                ->values();
            $columns = $this->resolveBoardColumnsFromRequest($request, $statusMap);
            $statuses = collect($columns)->pluck('key')->filter()->unique()->values()->all();

            $board = [
                'id' => 'board_' . Str::lower(Str::random(12)),
                'name' => trim((string) $request->string('name')),
                'columns' => $columns,
                'statuses' => $statuses,
                'created_by' => (int) auth()->id(),
                'created_at' => now()->toIso8601String(),
            ];

            $boards->push($board);
            $metadata['boards'] = $boards->all();
            $project->update(['metadata' => $metadata]);

            $this->logActivity('board_created', 'Board cree: ' . $board['name'], $project, null, [
                'board_id' => $board['id'],
                'statuses' => $board['statuses'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Board cree avec succes.',
                'data' => $this->resolveProjectBoards($project->fresh()),
                'status_map' => $this->resolveProjectStatusMap($project->fresh()),
                'board_id' => $board['id'],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function storeTask(Project $project, ProjectTaskStoreRequest $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');

        try {
            $payload = $request->validated();
            $statusMap = $this->resolveProjectStatusMap($project);
            $defaultStatus = array_key_exists('todo', $statusMap)
                ? 'todo'
                : (array_key_first($statusMap) ?: 'todo');
            $syncGoogleCalendar = filter_var($payload['sync_google_calendar'] ?? false, FILTER_VALIDATE_BOOL);
            $calendarId = isset($payload['calendar_id']) ? trim((string) $payload['calendar_id']) : null;
            unset($payload['sync_google_calendar'], $payload['calendar_id']);
            $status = (string) ($payload['status'] ?? $defaultStatus);
            if (!array_key_exists($status, $statusMap)) {
                throw new RuntimeException('Statut de tache invalide.');
            }
            $payload['client_id'] = $this->resolveTenantClientId($payload['client_id'] ?? null, (int) auth()->user()->tenant_id);

            if (!empty($payload['assigned_to'])) {
                $this->ensureTenantUser((int) $payload['assigned_to']);
            }

            $payload['project_id'] = $project->id;
            $payload['created_by'] = (int) auth()->id();
            $payload['status'] = $status;
            $payload['position'] = ((int) ProjectTask::query()->where('project_id', $project->id)->where('status', $status)->max('position')) + 1;

            if (!empty($payload['tags']) && is_string($payload['tags'])) {
                $payload['tags'] = collect(explode(',', $payload['tags']))->map(fn ($v) => trim((string) $v))->filter()->values()->all();
            }

            if ($status === 'done') {
                $payload['completed_at'] = now();
            }

            $task = ProjectTask::query()->create($payload);
            $project->recalculateProgress();

            $calendarSync = $syncGoogleCalendar
                ? $this->syncTaskCalendarOptional($project, $task, $calendarId)
                : ['event' => null, 'warning' => null, 'action_url' => null];
            app(DraftService::class)->forgetFromRequest($request);

            $this->logActivity('task_created', 'Tache creee: ' . $task->title, $project, $task, ['status' => $task->status]);

            event(new ProjectTaskCreated(
                $project->fresh(['client:id,company_name', 'owner:id,name']),
                $task->fresh(['assignee:id,name,email', 'creator:id,name', 'client:id,company_name']),
                [
                    'calendar_requested' => $syncGoogleCalendar,
                    'calendar_synced' => !empty($calendarSync['event']),
                    'calendar_warning' => $calendarSync['warning'],
                ]
            ));

            return response()->json([
                'success' => true,
                'message' => $calendarSync['event']
                    ? 'Tache creee et planifiee dans Google Calendar.'
                    : ($syncGoogleCalendar && $calendarSync['warning']
                        ? 'Tache creee, mais synchronisation Google Calendar echouee.'
                        : 'Tache creee avec succes.'),
                'data' => $this->formatTask($task->fresh(['assignee:id,name,email', 'creator:id,name', 'client:id,company_name'])->loadCount(['comments', 'checklist as checklist_total', 'checklist as checklist_done' => fn ($q) => $q->where('is_done', true)])),
                'calendar' => $calendarSync['event'],
                'calendar_warning' => $calendarSync['warning'],
                'calendar_action_url' => $calendarSync['action_url'],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateTask(Project $project, ProjectTask $task, ProjectTaskUpdateRequest $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        try {
            $payload = $request->validated();
            $statusMap = $this->resolveProjectStatusMap($project);
            $syncGoogleCalendar = filter_var($payload['sync_google_calendar'] ?? false, FILTER_VALIDATE_BOOL);
            $calendarId = isset($payload['calendar_id']) ? trim((string) $payload['calendar_id']) : null;
            unset($payload['sync_google_calendar'], $payload['calendar_id']);
            $payload['client_id'] = $this->resolveTenantClientId($payload['client_id'] ?? null, (int) auth()->user()->tenant_id);

            if (array_key_exists('status', $payload)) {
                $nextStatus = (string) $payload['status'];
                if (!array_key_exists($nextStatus, $statusMap)) {
                    throw new RuntimeException('Statut de tache invalide.');
                }
            }

            if (!empty($payload['assigned_to'])) {
                $this->ensureTenantUser((int) $payload['assigned_to']);
            }

            if (!empty($payload['tags']) && is_string($payload['tags'])) {
                $payload['tags'] = collect(explode(',', $payload['tags']))->map(fn ($v) => trim((string) $v))->filter()->values()->all();
            }

            if (($payload['status'] ?? $task->status) === 'done' && !$task->completed_at) {
                $payload['completed_at'] = now();
            }

            if (($payload['status'] ?? $task->status) !== 'done') {
                $payload['completed_at'] = null;
            }

            $task->update($payload);
            $project->recalculateProgress();

            $calendarSync = $syncGoogleCalendar
                ? $this->syncTaskCalendarOptional($project, $task, $calendarId)
                : ['event' => null, 'warning' => null, 'action_url' => null];
            app(DraftService::class)->forgetFromRequest($request);

            $this->logActivity('task_updated', 'Tache mise a jour: ' . $task->title, $project, $task, $payload);

            return response()->json([
                'success' => true,
                'message' => $calendarSync['event']
                    ? 'Tache mise a jour et synchronisee avec Google Calendar.'
                    : ($syncGoogleCalendar && $calendarSync['warning']
                        ? 'Tache mise a jour, mais synchronisation Google Calendar echouee.'
                        : 'Tache mise a jour.'),
                'data' => $this->formatTask($task->fresh(['assignee:id,name,email', 'creator:id,name', 'client:id,company_name'])->loadCount(['comments', 'checklist as checklist_total', 'checklist as checklist_done' => fn ($q) => $q->where('is_done', true)])),
                'calendar' => $calendarSync['event'],
                'calendar_warning' => $calendarSync['warning'],
                'calendar_action_url' => $calendarSync['action_url'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function moveTask(Project $project, ProjectTask $task, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        $request->validate([
            'status' => ['required', 'string', 'max:30'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $statusMap = $this->resolveProjectStatusMap($project);
            $newStatus = (string) $request->string('status');
            if (!array_key_exists($newStatus, $statusMap)) {
                throw new RuntimeException('Statut de tache invalide.');
            }
            $position = (int) $request->integer('position', 0);

            $task->status = $newStatus;
            $task->position = max(0, $position);
            $task->completed_at = $newStatus === 'done' ? ($task->completed_at ?: now()) : null;
            $task->save();

            $project->recalculateProgress();
            $this->logActivity('task_moved', 'Tache deplacee: ' . $task->title, $project, $task, ['status' => $newStatus, 'position' => $position]);

            return response()->json([
                'success' => true,
                'message' => 'Tache deplacee.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroyTask(Project $project, ProjectTask $task): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        try {
            DB::transaction(function () use ($project, $task) {
                ProjectTaskChecklist::query()->where('project_task_id', $task->id)->delete();
                ProjectTaskComment::query()->where('project_task_id', $task->id)->delete();
                $task->delete();
                $project->recalculateProgress();
            });

            $this->logActivity('task_deleted', 'Tache supprimee: ' . $task->title, $project, null, ['task_id' => $task->id]);

            return response()->json([
                'success' => true,
                'message' => 'Tache supprimee.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function scheduleProjectCalendar(Project $project, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.update');

        $request->validate([
            'calendar_id' => ['nullable', 'string', 'max:255'],
        ]);

        $calendarId = $request->filled('calendar_id') ? trim((string) $request->string('calendar_id')) : null;

        $sync = $this->syncProjectCalendarOptional($project, $calendarId);
        if (!$sync['event']) {
            return response()->json([
                'success' => false,
                'message' => $sync['warning'] ?: 'Planification du projet impossible.',
                'action_url' => $sync['action_url'] ?: $this->resolveExtensionTargetUrl('google-calendar', true, 'google-calendar.index'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Projet planifie dans Google Calendar avec succes.',
            'data' => $sync['event'],
        ]);
    }

    public function scheduleTaskCalendar(Project $project, ProjectTask $task, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        $request->validate([
            'calendar_id' => ['nullable', 'string', 'max:255'],
        ]);

        $calendarId = $request->filled('calendar_id') ? trim((string) $request->string('calendar_id')) : null;
        $sync = $this->syncTaskCalendarOptional($project, $task, $calendarId);

        if (!$sync['event']) {
            return response()->json([
                'success' => false,
                'message' => $sync['warning'] ?: 'Planification de la tache impossible.',
                'action_url' => $sync['action_url'] ?: $this->resolveExtensionTargetUrl('google-calendar', true, 'google-calendar.index'),
            ], 422);
        }

        $taskFresh = $task->fresh(['assignee:id,name,email', 'creator:id,name', 'client:id,company_name'])
            ->loadCount([
                'comments',
                'checklist as checklist_total',
                'checklist as checklist_done' => fn ($q) => $q->where('is_done', true),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Tache planifiee dans Google Calendar avec succes.',
            'data' => $sync['event'],
            'task' => $this->formatTask($taskFresh),
        ]);
    }

    public function commentsData(Project $project, ProjectTask $task): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');
        $this->ensureTaskBelongsToProject($project, $task);

        $comments = ProjectTaskComment::query()
            ->where('project_task_id', $task->id)
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(function (ProjectTaskComment $comment) {
                return [
                    'id' => (int) $comment->id,
                    'comment' => (string) $comment->comment,
                    'user_id' => (int) $comment->user_id,
                    'user' => $comment->user,
                    'created_at' => optional($comment->created_at)->toIso8601String(),
                    'updated_at' => optional($comment->updated_at)->toIso8601String(),
                    'can_edit' => $this->canManageTaskComment($comment),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    public function addComment(Project $project, ProjectTask $task, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.comment');
        $this->ensureTaskBelongsToProject($project, $task);

        $request->validate([
            'comment' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $comment = ProjectTaskComment::query()->create([
                'project_task_id' => $task->id,
                'user_id' => (int) auth()->id(),
                'comment' => (string) $request->string('comment'),
            ]);

            $this->logActivity('task_commented', 'Commentaire ajoute sur: ' . $task->title, $project, $task, ['comment_id' => $comment->id]);

            return response()->json([
                'success' => true,
                'message' => 'Commentaire ajoute.',
                'data' => $comment->load('user:id,name,email'),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateComment(Project $project, ProjectTask $task, ProjectTaskComment $comment, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.comment');
        $this->ensureTaskBelongsToProject($project, $task);
        abort_if((int) $comment->project_task_id !== (int) $task->id, 404);
        abort_if(!$this->canManageTaskComment($comment), 403, 'Vous ne pouvez pas modifier ce commentaire.');

        $request->validate([
            'comment' => ['required', 'string', 'max:4000'],
        ]);

        $comment->comment = (string) $request->string('comment');
        $comment->save();

        return response()->json([
            'success' => true,
            'message' => 'Commentaire mis a jour.',
            'data' => [
                'id' => (int) $comment->id,
                'comment' => (string) $comment->comment,
                'user_id' => (int) $comment->user_id,
                'updated_at' => optional($comment->updated_at)->toIso8601String(),
            ],
        ]);
    }

    public function destroyComment(Project $project, ProjectTask $task, ProjectTaskComment $comment): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.comment');
        $this->ensureTaskBelongsToProject($project, $task);
        abort_if((int) $comment->project_task_id !== (int) $task->id, 404);
        abort_if(!$this->canManageTaskComment($comment), 403, 'Vous ne pouvez pas supprimer ce commentaire.');

        $commentId = (int) $comment->id;
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commentaire supprime.',
            'comment_id' => $commentId,
        ]);
    }

    public function checklistStore(Project $project, ProjectTask $task, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $item = ProjectTaskChecklist::query()->create([
            'project_task_id' => $task->id,
            'title' => (string) $request->string('title'),
            'position' => ((int) ProjectTaskChecklist::query()->where('project_task_id', $task->id)->max('position')) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checklist ajoutee.',
            'data' => $item,
        ], 201);
    }

    public function checklistData(Project $project, ProjectTask $task): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');
        $this->ensureTaskBelongsToProject($project, $task);

        $items = ProjectTaskChecklist::query()
            ->where('project_task_id', $task->id)
            ->orderBy('position')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function checklistUpdate(Project $project, ProjectTask $task, ProjectTaskChecklist $item, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);
        abort_if((int) $item->project_task_id !== (int) $task->id, 404);

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $item->title = (string) $request->string('title');
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Checklist mise a jour.',
            'data' => $item,
        ]);
    }

    public function checklistToggle(Project $project, ProjectTask $task, ProjectTaskChecklist $item): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        abort_if((int) $item->project_task_id !== (int) $task->id, 404);

        $item->is_done = !$item->is_done;
        $item->done_by = $item->is_done ? (int) auth()->id() : null;
        $item->done_at = $item->is_done ? now() : null;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Checklist mise a jour.',
            'data' => $item,
        ]);
    }

    public function checklistDestroy(Project $project, ProjectTask $task, ProjectTaskChecklist $item): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        abort_if((int) $item->project_task_id !== (int) $task->id, 404);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checklist supprimee.',
        ]);
    }

    public function filesData(Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');

        $files = ProjectFile::query()
            ->where('project_id', $project->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $files,
        ]);
    }

    public function uploadFile(Project $project, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');

        $request->validate([
            'file' => ['required', 'file', 'max:' . ((int) config('google-drive.api.max_file_size_mb', 100) * 1024)],
        ]);

        try {
            $tenantId = (int) auth()->user()->tenant_id;
            $drive = $this->ensureGoogleDriveAvailable($tenantId);
            $folderId = $this->ensureProjectDriveFolder($project, $drive, $tenantId);

            $meta = $drive->uploadFile($tenantId, $request->file('file'), $folderId);

            $row = ProjectFile::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'project_id' => $project->id,
                    'drive_file_id' => (string) ($meta['id'] ?? ''),
                ],
                [
                    'uploaded_by' => (int) auth()->id(),
                    'name' => (string) ($meta['name'] ?? 'Fichier'),
                    'mime_type' => (string) ($meta['mime_type'] ?? ''),
                    'size_bytes' => (int) ($meta['size_bytes'] ?? 0),
                    'web_view_link' => $meta['web_view_link'] ?? null,
                    'download_link' => $meta['download_link'] ?? null,
                    'meta' => $meta,
                ]
            );

            $this->logActivity('file_uploaded', 'Fichier ajoute au projet', $project, null, [
                'drive_file_id' => $row->drive_file_id,
                'name' => $row->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fichier ajoute avec succes.',
                'data' => $row,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function deleteFile(Project $project, ProjectFile $file, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');

        abort_if((int) $file->project_id !== (int) $project->id, 404);

        try {
            $tenantId = (int) auth()->user()->tenant_id;
            $drive = $this->ensureGoogleDriveAvailable($tenantId);
            $drive->delete($tenantId, (string) $file->drive_file_id, false);

            $this->logActivity('file_deleted', 'Fichier supprime du projet', $project, null, [
                'drive_file_id' => $file->drive_file_id,
                'name' => $file->name,
            ]);

            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fichier supprime.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function taskFilesData(Project $project, ProjectTask $task): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.view');
        $this->ensureTaskBelongsToProject($project, $task);

        $files = ProjectTaskFile::query()
            ->where('project_task_id', $task->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $files,
        ]);
    }

    public function taskUploadFile(Project $project, ProjectTask $task, Request $request): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        $request->validate([
            'file' => ['required', 'file', 'max:' . ((int) config('google-drive.api.max_file_size_mb', 100) * 1024)],
        ]);

        try {
            $tenantId = (int) auth()->user()->tenant_id;
            $drive = $this->ensureGoogleDriveAvailable($tenantId);
            $folderId = $this->ensureTaskDriveFolder($project, $task, $drive, $tenantId);

            $meta = $drive->uploadFile($tenantId, $request->file('file'), $folderId);

            $row = ProjectTaskFile::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'project_task_id' => $task->id,
                    'drive_file_id' => (string) ($meta['id'] ?? ''),
                ],
                [
                    'uploaded_by' => (int) auth()->id(),
                    'name' => (string) ($meta['name'] ?? 'Fichier'),
                    'mime_type' => (string) ($meta['mime_type'] ?? ''),
                    'size_bytes' => (int) ($meta['size_bytes'] ?? 0),
                    'web_view_link' => $meta['web_view_link'] ?? null,
                    'download_link' => $meta['download_link'] ?? null,
                    'meta' => $meta,
                ]
            );

            $this->logActivity('task_file_uploaded', 'Fichier ajoute a la tache', $project, $task, [
                'drive_file_id' => $row->drive_file_id,
                'name' => $row->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fichier ajoute a la tache.',
                'data' => $row,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function taskDeleteFile(Project $project, ProjectTask $task, ProjectTaskFile $file): JsonResponse
    {
        $this->authorizeProjectAccess($project, 'projects.manage_tasks');
        $this->ensureTaskBelongsToProject($project, $task);

        abort_if((int) $file->project_task_id !== (int) $task->id, 404);

        try {
            $tenantId = (int) auth()->user()->tenant_id;
            $drive = $this->ensureGoogleDriveAvailable($tenantId);
            $drive->delete($tenantId, (string) $file->drive_file_id, false);

            $this->logActivity('task_file_deleted', 'Fichier supprime de la tache', $project, $task, [
                'drive_file_id' => $file->drive_file_id,
                'name' => $file->name,
            ]);

            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fichier supprime.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function formatProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'slug' => $project->slug,
            'description' => $project->description,
            'status' => $project->status,
            'priority' => $project->priority,
            'start_date' => optional($project->start_date)->format('Y-m-d'),
            'due_date' => optional($project->due_date)->format('Y-m-d'),
            'completed_at' => optional($project->completed_at)->format('Y-m-d H:i:s'),
            'budget' => $project->budget,
            'progress' => (int) $project->progress,
            'color' => $project->color,
            'client_id' => $project->client_id,
            'client_name' => $project->client?->company_name,
            'google_calendar' => $this->extractCalendarMeta($project->metadata),
            'owner_id' => $project->owner_id,
            'owner_name' => $project->owner?->name,
            'tasks_count' => (int) ($project->tasks_count ?? 0),
            'members_count' => (int) ($project->members_count ?? 0),
            'created_at' => optional($project->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function formatTask(ProjectTask $task): array
    {
        return [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'position' => (int) $task->position,
            'assigned_to' => $task->assigned_to,
            'assignee_name' => $task->assignee?->name,
            'creator_name' => $task->creator?->name,
            'client_id' => $task->client_id,
            'client_name' => $task->client?->company_name,
            'google_calendar' => $this->extractCalendarMeta($task->metadata),
            'start_date' => optional($task->start_date)->format('Y-m-d'),
            'due_date' => optional($task->due_date)->format('Y-m-d'),
            'completed_at' => optional($task->completed_at)->format('Y-m-d H:i:s'),
            'estimate_hours' => $task->estimate_hours,
            'spent_hours' => $task->spent_hours,
            'tags' => $task->tags ?: [],
            'comments_count' => (int) ($task->comments_count ?? 0),
            'checklist_total' => (int) ($task->checklist_total ?? 0),
            'checklist_done' => (int) ($task->checklist_done ?? 0),
            'created_at' => optional($task->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function makeProjectSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'projet';
        $slug = $base;
        $suffix = 1;

        while (Project::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function authorizeProjectAccess(Project $project, string $permission): void
    {
        $this->authorizePermission($permission);

        if ($this->hasAnyPermission(['projects.admin']) || $this->isTenantAdmin()) {
            return;
        }

        $userId = (int) auth()->id();
        $isOwner = (int) $project->owner_id === $userId;
        $isMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->exists();

        abort_if(!$isOwner && !$isMember, 403, 'Acces non autorise a ce projet.');
    }

    private function canManageProjectBoards(Project $project): bool
    {
        if ($this->isTenantAdmin() || $this->hasAnyPermission(['projects.admin'])) {
            return true;
        }

        return (int) $project->owner_id === (int) auth()->id();
    }

    private function resolveProjectBoards(Project $project): array
    {
        $statusMap = $this->resolveProjectStatusMap($project);
        $defaultColumns = collect($statusMap)
            ->map(fn ($label, $key) => [
                'key' => (string) $key,
                'label' => trim((string) $label) !== '' ? trim((string) $label) : $this->formatStatusLabel((string) $key),
            ])
            ->values()
            ->all();

        $default = [
            'id' => 'default',
            'name' => 'Board principal',
            'columns' => $defaultColumns,
            'statuses' => array_keys($statusMap),
            'is_default' => true,
        ];

        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $custom = collect($metadata['boards'] ?? [])
            ->filter(fn ($row) => is_array($row) && !empty($row['id']) && !empty($row['name']))
            ->map(function (array $row) use ($defaultColumns, $statusMap) {
                $columns = $this->normalizeBoardColumns($row, $statusMap);
                if (empty($columns)) {
                    $columns = $defaultColumns;
                }

                $statuses = collect($columns)
                    ->pluck('key')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (empty($statuses)) {
                    $statuses = array_keys($statusMap);
                    $columns = $defaultColumns;
                }

                return [
                    'id' => (string) $row['id'],
                    'name' => trim((string) $row['name']) !== '' ? trim((string) $row['name']) : 'Board',
                    'columns' => $columns,
                    'statuses' => $statuses,
                    'is_default' => false,
                ];
            })
            ->values()
            ->all();

        return array_merge([$default], $custom);
    }

    private function resolveProjectStatusMap(Project $project): array
    {
        $statusMap = collect(config('projects.task_statuses', []))
            ->mapWithKeys(function ($label, $key) {
                $statusKey = $this->sanitizeStatusKey((string) $key);
                if ($statusKey === '') {
                    return [];
                }

                $statusLabel = trim((string) $label) !== '' ? trim((string) $label) : $this->formatStatusLabel($statusKey);
                return [$statusKey => $statusLabel];
            })
            ->all();

        $metadata = is_array($project->metadata) ? $project->metadata : [];
        foreach (($metadata['boards'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach ($this->normalizeBoardColumns($row, $statusMap) as $column) {
                $statusKey = $this->sanitizeStatusKey((string) ($column['key'] ?? ''));
                if ($statusKey === '') {
                    continue;
                }

                $statusMap[$statusKey] = trim((string) ($column['label'] ?? '')) !== ''
                    ? trim((string) $column['label'])
                    : ($statusMap[$statusKey] ?? $this->formatStatusLabel($statusKey));
            }
        }

        $taskStatuses = ProjectTask::query()
            ->where('project_id', $project->id)
            ->select('status')
            ->distinct()
            ->pluck('status');

        foreach ($taskStatuses as $status) {
            $statusKey = mb_substr(trim((string) $status), 0, 30);
            if ($statusKey === '') {
                continue;
            }

            if (!array_key_exists($statusKey, $statusMap)) {
                $statusMap[$statusKey] = $this->formatStatusLabel($statusKey);
            }
        }

        return $statusMap;
    }

    private function resolveBoardColumnsFromRequest(Request $request, array $statusMap): array
    {
        $reserved = array_keys($statusMap);
        $columns = [];

        $legacyStatuses = collect($request->input('statuses', []))
            ->map(fn ($status) => $this->sanitizeStatusKey((string) $status))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($legacyStatuses as $status) {
            if (!array_key_exists($status, $statusMap)) {
                continue;
            }

            $columns[] = [
                'key' => $status,
                'label' => $statusMap[$status] ?? $this->formatStatusLabel($status),
            ];
        }

        $requestedColumns = $request->input('columns', []);
        if (is_array($requestedColumns)) {
            foreach ($requestedColumns as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $label = trim((string) ($row['label'] ?? ''));
                if ($label === '') {
                    continue;
                }

                $explicitStatus = $this->sanitizeStatusKey((string) ($row['status'] ?? ''));
                if ($explicitStatus !== '' && array_key_exists($explicitStatus, $statusMap)) {
                    $key = $explicitStatus;
                } else {
                    $key = $this->generateUniqueStatusKey($label, $reserved);
                }

                if ($key === '' || collect($columns)->contains(fn ($column) => (string) ($column['key'] ?? '') === $key)) {
                    continue;
                }

                $columns[] = [
                    'key' => $key,
                    'label' => $label,
                ];
                $reserved[] = $key;
            }
        }

        if (empty($columns)) {
            return collect($statusMap)
                ->map(fn ($label, $key) => ['key' => (string) $key, 'label' => (string) $label])
                ->values()
                ->all();
        }

        return $columns;
    }

    private function normalizeBoardColumns(array $boardRow, array $statusMap): array
    {
        $columns = collect($boardRow['columns'] ?? [])
            ->filter(fn ($column) => is_array($column))
            ->map(function (array $column) use ($statusMap) {
                $key = $this->sanitizeStatusKey((string) ($column['key'] ?? $column['status'] ?? ''));
                if ($key === '') {
                    return null;
                }

                $label = trim((string) ($column['label'] ?? ''));
                if ($label === '') {
                    $label = $statusMap[$key] ?? $this->formatStatusLabel($key);
                }

                return ['key' => $key, 'label' => $label];
            })
            ->filter()
            ->unique(fn ($column) => (string) ($column['key'] ?? ''))
            ->values()
            ->all();

        if (!empty($columns)) {
            return $columns;
        }

        return collect($boardRow['statuses'] ?? [])
            ->map(fn ($status) => $this->sanitizeStatusKey((string) $status))
            ->filter()
            ->unique()
            ->map(fn ($status) => [
                'key' => $status,
                'label' => $statusMap[$status] ?? $this->formatStatusLabel($status),
            ])
            ->values()
            ->all();
    }

    private function sanitizeStatusKey(string $status): string
    {
        $key = trim($status);
        if ($key === '') {
            return '';
        }

        $key = preg_replace('/\s+/', '_', $key) ?? '';
        $key = preg_replace('/[^A-Za-z0-9_\-]/', '', $key) ?? '';
        $key = Str::lower(trim($key, '_-'));
        if ($key === '') {
            return '';
        }

        return mb_substr($key, 0, 30);
    }

    private function formatStatusLabel(string $status): string
    {
        $raw = str_replace(['_', '-'], ' ', $status);
        $label = trim(Str::of($raw)->headline()->toString());

        return $label !== '' ? $label : 'Colonne';
    }

    private function generateUniqueStatusKey(string $label, array $reservedKeys = []): string
    {
        $slug = Str::slug($label, '_');
        $base = $this->sanitizeStatusKey($slug);
        if ($base === '') {
            $base = 'colonne';
        }

        $reserved = collect($reservedKeys)
            ->map(fn ($key) => (string) $key)
            ->filter()
            ->values()
            ->all();

        if (!in_array($base, $reserved, true)) {
            return $base;
        }

        for ($i = 2; $i <= 99; $i++) {
            $suffix = '_' . $i;
            $trimmedBase = mb_substr($base, 0, max(1, 30 - strlen($suffix)));
            $candidate = $trimmedBase . $suffix;
            if (!in_array($candidate, $reserved, true)) {
                return $candidate;
            }
        }

        return $this->sanitizeStatusKey($base . '_' . Str::lower(Str::random(3)));
    }

    private function authorizePermission(string $permission): void
    {
        if ($this->isTenantAdmin()) {
            return;
        }

        if ($this->userHasPermission($permission)) {
            return;
        }

        if ($permission === 'projects.view' && $this->hasAnyPermission(['projects.create', 'projects.update', 'projects.manage_tasks', 'projects.comment'])) {
            return;
        }

        abort(403, 'Permission insuffisante: ' . $permission);
    }

    private function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->userHasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    private function userHasPermission(string $permission): bool
    {
        $user = auth()->user();
        $tenantId = (int) ($user->tenant_id ?? 0);

        if (method_exists($user, 'hasTenantPermission')) {
            try {
                return $user->hasTenantPermission($permission, $tenantId);
            } catch (\Throwable) {
                return false;
            }
        }

        return $user->can($permission);
    }

    private function isTenantAdmin(): bool
    {
        return in_array((string) auth()->user()->role_in_tenant, ['owner', 'admin'], true)
            || (bool) auth()->user()->is_tenant_owner;
    }

    private function ensureTaskBelongsToProject(Project $project, ProjectTask $task): void
    {
        abort_if((int) $task->project_id !== (int) $project->id, 404, 'Tache introuvable pour ce projet.');
    }

    private function canManageTaskComment(ProjectTaskComment $comment): bool
    {
        if ($this->isTenantAdmin() || $this->hasAnyPermission(['projects.admin', 'projects.manage_tasks'])) {
            return true;
        }

        return (int) $comment->user_id === (int) auth()->id();
    }

    private function ensureTenantUser(int $userId): void
    {
        $exists = User::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('id', $userId)
            ->exists();

        abort_if(!$exists, 422, 'Utilisateur invalide pour ce tenant.');
    }

    private function logActivity(string $event, string $description, Project $project, ?ProjectTask $task = null, array $payload = []): void
    {
        ProjectActivity::query()->create([
            'project_id' => $project->id,
            'project_task_id' => $task?->id,
            'user_id' => auth()->id(),
            'event' => $event,
            'description' => $description,
            'payload' => $payload,
        ]);
    }

    private function resolveTenantClientId($clientId, int $tenantId): ?int
    {
        $clientId = (int) ($clientId ?? 0);
        if ($clientId <= 0) {
            return null;
        }

        if (!$this->isMarketplaceExtensionActive($tenantId, 'clients')) {
            return null;
        }

        $exists = Client::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $clientId)
            ->exists();

        if (!$exists) {
            throw new RuntimeException('Client invalide pour ce tenant.');
        }

        return $clientId;
    }

    private function syncProjectCalendarOptional(Project $project, ?string $calendarId = null): array
    {
        try {
            return [
                'event' => $this->syncProjectCalendarOrFail($project, $calendarId),
                'warning' => null,
                'action_url' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('[Projects] sync project calendar failed', [
                'tenant_id' => (int) auth()->user()->tenant_id,
                'project_id' => (int) $project->id,
                'calendar_id' => $calendarId,
                'message' => $e->getMessage(),
            ]);

            return [
                'event' => null,
                'warning' => $e->getMessage(),
                'action_url' => $this->resolveCalendarActionUrlForError($e->getMessage()),
            ];
        }
    }

    private function syncTaskCalendarOptional(Project $project, ProjectTask $task, ?string $calendarId = null): array
    {
        try {
            return [
                'event' => $this->syncTaskCalendarOrFail($project, $task, $calendarId),
                'warning' => null,
                'action_url' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('[Projects] sync task calendar failed', [
                'tenant_id' => (int) auth()->user()->tenant_id,
                'project_id' => (int) $project->id,
                'task_id' => (int) $task->id,
                'calendar_id' => $calendarId,
                'message' => $e->getMessage(),
            ]);

            return [
                'event' => null,
                'warning' => $e->getMessage(),
                'action_url' => $this->resolveCalendarActionUrlForError($e->getMessage()),
            ];
        }
    }

    private function syncProjectCalendarOrFail(Project $project, ?string $calendarId = null): array
    {
        $tenantId = (int) auth()->user()->tenant_id;
        if (!$this->isMarketplaceExtensionActive($tenantId, 'google-calendar')) {
            throw new RuntimeException("Google Calendar n'est pas installe pour ce tenant. Installez l'application depuis Marketplace.");
        }

        /** @var GoogleCalendarService $calendarService */
        $calendarService = app(GoogleCalendarService::class);

        if (!$calendarService->getToken($tenantId)) {
            throw new RuntimeException("Google Calendar n'est pas connecte. Ouvrez l'application et connectez votre compte Google.");
        }

        [$startAt, $endAt] = $this->resolveProjectScheduleRange($project);
        $clientId = $this->resolveTenantClientId($project->client_id ? (int) $project->client_id : null, $tenantId);
        $existingMeta = $this->extractCalendarMeta($project->metadata) ?: [];
        $existingEventId = trim((string) ($existingMeta['event_id'] ?? ''));
        $existingCalendarId = trim((string) ($existingMeta['calendar_id'] ?? ''));
        $resolvedCalendarId = trim((string) ($calendarId ?: $existingCalendarId));

        $description = trim(implode("\n", array_filter([
            'Projet CRM: ' . $project->name,
            $project->description ? strip_tags((string) $project->description) : null,
            'Responsable: ' . ($project->owner?->name ?: '-'),
            $project->due_date ? 'Echeance: ' . $project->due_date->format('d/m/Y') : null,
            'Lien projet: ' . route('projects.show', $project),
        ])));

        $eventPayload = array_filter([
            'calendar_id' => $resolvedCalendarId !== '' ? $resolvedCalendarId : null,
            'summary' => '[Projet] ' . $project->name,
            'description' => $description,
            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt->toIso8601String(),
            'all_day' => false,
            'timezone' => (string) config('google-calendar.defaults.timezone', config('app.timezone', 'UTC')),
            'client_id' => $clientId,
            'source_type' => 'project',
            'source_id' => (int) $project->id,
            'source_label' => $project->name,
        ], static fn ($value) => $value !== null && $value !== '');

        $event = null;
        if ($existingEventId !== '' && $resolvedCalendarId !== '') {
            try {
                $event = $calendarService->updateEvent($tenantId, $resolvedCalendarId, $existingEventId, $eventPayload);
            } catch (\Throwable $e) {
                $event = null;
            }
        }

        if (!$event) {
            $event = $calendarService->createEvent($tenantId, $eventPayload);
        }

        $this->attachProjectCalendarMeta($project, $event);
        $this->logActivity('project_scheduled_calendar', 'Projet planifie dans Google Calendar', $project, null, [
            'calendar_id' => $event['calendar_id'] ?? null,
            'event_id' => $event['event_id'] ?? null,
        ]);

        return $event;
    }

    private function syncTaskCalendarOrFail(Project $project, ProjectTask $task, ?string $calendarId = null): array
    {
        $tenantId = (int) auth()->user()->tenant_id;
        if (!$this->isMarketplaceExtensionActive($tenantId, 'google-calendar')) {
            throw new RuntimeException("Google Calendar n'est pas installe pour ce tenant. Installez l'application depuis Marketplace.");
        }

        /** @var GoogleCalendarService $calendarService */
        $calendarService = app(GoogleCalendarService::class);

        if (!$calendarService->getToken($tenantId)) {
            throw new RuntimeException("Google Calendar n'est pas connecte. Ouvrez l'application et connectez votre compte Google.");
        }

        [$startAt, $endAt] = $this->resolveTaskScheduleRange($project, $task);
        $clientId = $this->resolveTenantClientId(
            $task->client_id ? (int) $task->client_id : ($project->client_id ? (int) $project->client_id : null),
            $tenantId
        );
        $existingMeta = $this->extractCalendarMeta($task->metadata) ?: [];
        $existingEventId = trim((string) ($existingMeta['event_id'] ?? ''));
        $existingCalendarId = trim((string) ($existingMeta['calendar_id'] ?? ''));
        $resolvedCalendarId = trim((string) ($calendarId ?: $existingCalendarId));

        $description = trim(implode("\n", array_filter([
            'Tache CRM: ' . $task->title,
            'Projet: ' . $project->name,
            $task->description ? strip_tags((string) $task->description) : null,
            $task->assignee?->name ? 'Assigne a: ' . $task->assignee->name : null,
            $task->due_date ? 'Echeance: ' . $task->due_date->format('d/m/Y') : null,
            'Lien projet: ' . route('projects.show', $project),
        ])));

        $eventPayload = array_filter([
            'calendar_id' => $resolvedCalendarId !== '' ? $resolvedCalendarId : null,
            'summary' => '[Tache] ' . $task->title,
            'description' => $description,
            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt->toIso8601String(),
            'all_day' => false,
            'timezone' => (string) config('google-calendar.defaults.timezone', config('app.timezone', 'UTC')),
            'client_id' => $clientId,
            'source_type' => 'project_task',
            'source_id' => (int) $task->id,
            'source_label' => $task->title,
        ], static fn ($value) => $value !== null && $value !== '');

        $event = null;
        if ($existingEventId !== '' && $resolvedCalendarId !== '') {
            try {
                $event = $calendarService->updateEvent($tenantId, $resolvedCalendarId, $existingEventId, $eventPayload);
            } catch (\Throwable $e) {
                $event = null;
            }
        }

        if (!$event) {
            $event = $calendarService->createEvent($tenantId, $eventPayload);
        }

        $this->attachTaskCalendarMeta($task, $event);
        $this->logActivity('task_scheduled_calendar', 'Tache planifiee dans Google Calendar', $project, $task, [
            'calendar_id' => $event['calendar_id'] ?? null,
            'event_id' => $event['event_id'] ?? null,
        ]);

        return $event;
    }

    private function resolveCalendarActionUrlForError(string $message): string
    {
        $normalized = Str::lower($message);
        $isInstallError = str_contains($normalized, 'installe')
            || str_contains($normalized, 'installer')
            || str_contains($normalized, 'marketplace');

        return $this->resolveExtensionTargetUrl(
            'google-calendar',
            !$isInstallError,
            'google-calendar.index'
        );
    }

    private function resolveProjectScheduleRange(Project $project): array
    {
        $timezone = (string) config('google-calendar.defaults.timezone', config('app.timezone', 'UTC'));

        $startAt = $project->start_date
            ? Carbon::parse($project->start_date->format('Y-m-d') . ' 09:00:00', $timezone)
            : now($timezone)->copy()->addHour()->minute(0)->second(0);

        $endAt = $project->due_date
            ? Carbon::parse($project->due_date->format('Y-m-d') . ' 18:00:00', $timezone)
            : $startAt->copy()->addHours(2);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt = $startAt->copy()->addHour();
        }

        return [$startAt, $endAt];
    }

    private function resolveTaskScheduleRange(Project $project, ProjectTask $task): array
    {
        $timezone = (string) config('google-calendar.defaults.timezone', config('app.timezone', 'UTC'));

        if ($task->start_date) {
            $startAt = Carbon::parse($task->start_date->format('Y-m-d') . ' 09:00:00', $timezone);
        } elseif ($project->start_date) {
            $startAt = Carbon::parse($project->start_date->format('Y-m-d') . ' 09:00:00', $timezone);
        } else {
            $startAt = now($timezone)->copy()->addHour()->minute(0)->second(0);
        }

        if ($task->due_date) {
            $endAt = Carbon::parse($task->due_date->format('Y-m-d') . ' 18:00:00', $timezone);
        } elseif ($project->due_date) {
            $endAt = Carbon::parse($project->due_date->format('Y-m-d') . ' 18:00:00', $timezone);
        } else {
            $endAt = $startAt->copy()->addHours(2);
        }

        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt = $startAt->copy()->addHour();
        }

        return [$startAt, $endAt];
    }

    private function attachProjectCalendarMeta(Project $project, array $event): void
    {
        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $metadata['google_calendar'] = [
            'calendar_id' => (string) ($event['calendar_id'] ?? ''),
            'event_id' => (string) ($event['event_id'] ?? ''),
            'html_link' => (string) ($event['html_link'] ?? ''),
            'summary' => (string) ($event['summary'] ?? ''),
            'scheduled_at' => now()->toIso8601String(),
        ];

        $project->update(['metadata' => $metadata]);
    }

    private function attachTaskCalendarMeta(ProjectTask $task, array $event): void
    {
        $metadata = is_array($task->metadata) ? $task->metadata : [];
        $metadata['google_calendar'] = [
            'calendar_id' => (string) ($event['calendar_id'] ?? ''),
            'event_id' => (string) ($event['event_id'] ?? ''),
            'html_link' => (string) ($event['html_link'] ?? ''),
            'summary' => (string) ($event['summary'] ?? ''),
            'scheduled_at' => now()->toIso8601String(),
        ];

        $task->update(['metadata' => $metadata]);
    }

    private function extractCalendarMeta($metadata): ?array
    {
        if (!is_array($metadata) || !is_array($metadata['google_calendar'] ?? null)) {
            return null;
        }

        return $metadata['google_calendar'];
    }

    private function isMarketplaceExtensionActive(int $tenantId, string $slug): bool
    {
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

    private function resolveExtensionTargetUrl(string $slug, bool $isInstalled, ?string $installedRoute = null): string
    {
        if ($isInstalled && $installedRoute && Route::has($installedRoute)) {
            return route($installedRoute);
        }

        if (Route::has('marketplace.show')) {
            return route('marketplace.show', $slug);
        }

        if (Route::has('marketplace.index')) {
            return route('marketplace.index');
        }

        return Route::has('applications') ? route('applications') : url('/');
    }

    private function ensureGoogleDriveAvailable(int $tenantId): GoogleDriveService
    {
        $extension = Extension::query()->where('slug', 'google-drive')->first();
        if (!$extension) {
            throw new RuntimeException("L'application Google Drive n'est pas disponible. Installez-la depuis Marketplace.");
        }

        $isActive = TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();

        if (!$isActive) {
            throw new RuntimeException("Google Drive n'est pas installe pour ce tenant. Installez l'application Google Drive depuis Marketplace.");
        }

        $service = app(GoogleDriveService::class);
        $token = $service->getToken($tenantId);
        if (!$token) {
            throw new RuntimeException("Google Drive n'est pas connecte. Ouvrez l'application Google Drive et cliquez sur Connecter.");
        }

        return $service;
    }

    private function ensureProjectDriveFolder(Project $project, GoogleDriveService $drive, int $tenantId): string
    {
        $meta = is_array($project->metadata) ? $project->metadata : [];
        $existing = (string) ($meta['drive_folder_id'] ?? '');
        if ($existing !== '') {
            try {
                $drive->getFile($tenantId, $existing);
                return $existing;
            } catch (\Throwable $e) {
                // continue: folder missing or inaccessible, recreate.
            }
        }

        $token = $drive->getToken($tenantId);
        $rootId = $token?->drive_root_folder_id ?: null;

        $projectsFolder = null;
        if ($rootId) {
            $projectsFolder = GoogleDriveFile::forTenant($tenantId)
                ->where('is_folder', true)
                ->where('name', 'Projets')
                ->where('parent_drive_id', $rootId)
                ->first();
        }

        $projectsFolderId = $projectsFolder?->drive_id;
        if (!$projectsFolderId) {
            $created = $drive->createFolder($tenantId, 'Projets', $rootId);
            $projectsFolderId = (string) ($created['id'] ?? '');
        }

        if ($projectsFolderId === '') {
            throw new RuntimeException('Impossible de creer le dossier Drive pour les projets.');
        }

        $projectFolderName = 'Projet-' . (int) $project->id;
        $projectFolder = GoogleDriveFile::forTenant($tenantId)
            ->where('is_folder', true)
            ->where('name', $projectFolderName)
            ->where('parent_drive_id', $projectsFolderId)
            ->first();

        $projectFolderId = $projectFolder?->drive_id;
        if (!$projectFolderId) {
            $created = $drive->createFolder($tenantId, $projectFolderName, $projectsFolderId);
            $projectFolderId = (string) ($created['id'] ?? '');
        }

        if ($projectFolderId === '') {
            throw new RuntimeException('Impossible de creer le dossier Drive du projet.');
        }

        $meta['drive_folder_id'] = $projectFolderId;
        $project->update(['metadata' => $meta]);

        return $projectFolderId;
    }

    private function ensureTaskDriveFolder(Project $project, ProjectTask $task, GoogleDriveService $drive, int $tenantId): string
    {
        $meta = is_array($task->metadata) ? $task->metadata : [];
        $existing = (string) ($meta['drive_folder_id'] ?? '');
        if ($existing !== '') {
            try {
                $drive->getFile($tenantId, $existing);
                return $existing;
            } catch (\Throwable $e) {
                // continue
            }
        }

        $projectFolderId = $this->ensureProjectDriveFolder($project, $drive, $tenantId);

        $tasksFolder = GoogleDriveFile::forTenant($tenantId)
            ->where('is_folder', true)
            ->where('name', 'Taches')
            ->where('parent_drive_id', $projectFolderId)
            ->first();

        $tasksFolderId = $tasksFolder?->drive_id;
        if (!$tasksFolderId) {
            $created = $drive->createFolder($tenantId, 'Taches', $projectFolderId);
            $tasksFolderId = (string) ($created['id'] ?? '');
        }

        if ($tasksFolderId === '') {
            throw new RuntimeException('Impossible de creer le dossier Drive des taches.');
        }

        $taskFolderName = 'Tache-' . (int) $task->id;
        $taskFolder = GoogleDriveFile::forTenant($tenantId)
            ->where('is_folder', true)
            ->where('name', $taskFolderName)
            ->where('parent_drive_id', $tasksFolderId)
            ->first();

        $taskFolderId = $taskFolder?->drive_id;
        if (!$taskFolderId) {
            $created = $drive->createFolder($tenantId, $taskFolderName, $tasksFolderId);
            $taskFolderId = (string) ($created['id'] ?? '');
        }

        if ($taskFolderId === '') {
            throw new RuntimeException('Impossible de creer le dossier Drive de la tache.');
        }

        $meta['drive_folder_id'] = $taskFolderId;
        $task->update(['metadata' => $meta]);

        return $taskFolderId;
    }
}


