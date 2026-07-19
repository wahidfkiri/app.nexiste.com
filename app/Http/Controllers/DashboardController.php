<?php

namespace App\Http\Controllers;

use App\Models\Draft;
use App\Services\DraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\TrelloIntegration\Models\TrelloBoard;
use Modules\TrelloIntegration\Models\TrelloToken;
use NexusExtensions\Chatbot\Models\ChatbotMessage;
use NexusExtensions\Dropbox\Models\DropboxFile;
use NexusExtensions\Dropbox\Models\DropboxToken;
use NexusExtensions\GoogleDocx\Models\GoogleDocxDocument;
use NexusExtensions\GoogleDocx\Models\GoogleDocxToken;
use NexusExtensions\GoogleDrive\Models\GoogleDriveFile;
use NexusExtensions\GoogleDrive\Models\GoogleDriveToken;
use NexusExtensions\GoogleGmail\Models\GoogleGmailMessage;
use NexusExtensions\GoogleGmail\Models\GoogleGmailToken;
use NexusExtensions\GoogleMeet\Models\GoogleMeetMeeting;
use NexusExtensions\GoogleMeet\Models\GoogleMeetToken;
use NexusExtensions\GoogleSheets\Models\GoogleSheetsSpreadsheet;
use NexusExtensions\GoogleSheets\Models\GoogleSheetsToken;
use NexusExtensions\NotionWorkspace\Models\NotionPageLink;
use NexusExtensions\NotionWorkspace\Models\NotionWorkspaceToken;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Projects\Models\ProjectActivity;
use NexusExtensions\Projects\Models\ProjectTask;
use NexusExtensions\Slack\Models\SlackMessage;
use NexusExtensions\Slack\Models\SlackToken;
use Vendor\Client\Models\Client;
use Vendor\CrmCore\Models\Tenant;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\GoogleCalendar\Models\GoogleCalendarEvent;
use Vendor\GoogleCalendar\Models\GoogleCalendarToken;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Payment;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\StockMovement;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', __('dashboard.session_expired'));
        }

        if ($user->tenant_id && OnboardingController::mustCompleteOnboarding($user)) {
            return redirect()->route('onboarding.show');
        }

        $tenantId = (int) session('current_tenant_id', $user->tenant_id ?? 0);
        if ($tenantId <= 0 || ! $user->hasTenantAccess($tenantId)) {
            abort(403, __('dashboard.access_denied'));
        }

        $tenant = Tenant::query()->find($tenantId) ?: $user->tenant;
        $currency = strtoupper((string) ($tenant->currency ?? 'EUR'));
        $apps = $this->activeTenantApps($tenantId);
        $slugs = $apps->pluck('extension.slug')->filter()->map(fn ($slug) => (string) $slug)->values();

        $access = [
            'clients' => $this->featureIsVisible($user, $tenantId, $slugs, 'clients', 'clients.read'),
            'invoice' => $this->featureIsVisible($user, $tenantId, $slugs, 'invoice', 'invoices.read'),
            'stock' => $this->featureIsVisible($user, $tenantId, $slugs, 'stock', 'stock.read'),
            'projects' => $this->featureIsVisible($user, $tenantId, $slugs, 'projects', null),
            'settings' => $this->canTenant($user, $tenantId, ['settings.read', 'settings.update']),
            'reports' => $this->canTenant($user, $tenantId, 'reports.read'),
        ];

        $range = [
            'month_start' => now()->startOfMonth(),
            'now' => now(),
            'previous_start' => now()->subMonthNoOverflow()->startOfMonth(),
            'previous_end' => now()->subMonthNoOverflow()->endOfMonth(),
            'today' => now()->toDateString(),
            'next_week' => now()->addDays(7)->toDateString(),
        ];

        $clients = $this->clientSnapshot($tenantId, $access, $range);
        $finance = $this->financeSnapshot($tenantId, $access, $range, $currency);
        $projects = $this->projectSnapshot($tenantId, $access, $range);
        $stock = $this->stockSnapshot($tenantId, $access);
        $integrations = $this->integrationSnapshot($apps, $tenantId);
        $activity = $this->activityFeed($tenantId, $access, $apps, $clients, $finance, $projects, $currency);
        $focus = $this->focusItems($finance, $projects, $stock, $currency);
        $modules = $this->moduleCards($access, $clients, $finance, $projects, $stock);

        $dashboard = [
            'meta' => [
                'title' => __('dashboard.meta.title'),
                'eyebrow' => null,
                'subtitle' => __('dashboard.meta.subtitle'),
                'tenant' => $tenant?->name ?: __('dashboard.meta.tenant_fallback'),
                'currency' => $currency,
                'date' => now()->translatedFormat('l d F Y'),
                'user' => [
                    'name' => $user->name ?: $user->email,
                    'role' => $user->role_in_tenant_label ?? $user->role_in_tenant ?? __('dashboard.meta.member'),
                    'initials' => $user->initials ?? strtoupper(substr((string) ($user->name ?: $user->email ?: 'U'), 0, 2)),
                ],
            ],
            'actions' => $this->quickActions($user, $tenantId, $access),
            'signals' => $this->signalCards($clients, $finance, $projects, $stock, $integrations, $currency),
            'modules' => $modules,
            'finance' => $finance,
            'projects' => $projects,
            'stock' => $stock,
            'integrations' => $integrations,
            'activity' => $activity,
            'focus' => $focus,
            'charts' => [
                'finance' => $finance['chart'],
                'workload' => $projects['chart'],
                'stock' => $stock['chart'],
                'integrations' => $integrations['chart'],
            ],
            'access' => $access,
        ];

        return view('dashboard', compact('dashboard'));
    }

    private function activeTenantApps(int $tenantId): Collection
    {
        if ($tenantId <= 0 || ! Schema::hasTable('tenant_extensions') || ! Schema::hasTable('extensions')) {
            return collect();
        }

        return TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['active', 'trial'])
            ->whereHas('extension', fn ($query) => $query->where('status', 'active'))
            ->with('extension')
            ->latest('activated_at')
            ->get()
            ->filter(fn (TenantExtension $activation) => $activation->extension !== null)
            ->values();
    }

    private function clientSnapshot(int $tenantId, array $access, array $range): array
    {
        $blank = [
            'enabled' => false,
            'total' => 0,
            'new_this_month' => 0,
            'new_previous_month' => 0,
            'recent' => collect(),
            'route' => $this->routeIfExists('clients.index'),
            'create_route' => $this->routeIfExists('clients.create'),
        ];

        if (! $access['clients'] || ! Schema::hasTable('clients')) {
            return $blank;
        }

        $query = $this->tenantQuery(Client::class, $tenantId);

        return array_merge($blank, [
            'enabled' => true,
            'total' => (int) (clone $query)->count(),
            'new_this_month' => (int) (clone $query)->whereBetween('created_at', [$range['month_start'], $range['now']])->count(),
            'new_previous_month' => (int) (clone $query)->whereBetween('created_at', [$range['previous_start'], $range['previous_end']])->count(),
            'recent' => $this->tenantQuery(Client::class, $tenantId)
                ->latest('created_at')
                ->limit(5)
                ->get(['id', 'company_name', 'contact_name', 'email', 'status', 'next_follow_up_at', 'created_at']),
        ]);
    }

    private function financeSnapshot(int $tenantId, array $access, array $range, string $currency): array
    {
        $blank = [
            'enabled' => false,
            'invoice_count' => 0,
            'open_count' => 0,
            'revenue_month' => 0.0,
            'revenue_previous' => 0.0,
            'payments_month' => 0.0,
            'payments_previous' => 0.0,
            'pending_amount' => 0.0,
            'recent_invoices' => collect(),
            'open_invoices' => collect(),
            'chart' => null,
            'route' => $this->routeIfExists('invoices.index'),
            'create_route' => $this->routeIfExists('invoices.create'),
        ];

        if (! $access['invoice'] || ! Schema::hasTable('invoices')) {
            return $blank;
        }

        $invoiceQuery = $this->tenantQuery(Invoice::class, $tenantId);
        $closedStatuses = ['paid', 'cancelled', 'refunded'];
        $paymentQuery = Schema::hasTable('payments') ? $this->tenantQuery(Payment::class, $tenantId) : null;
        $months = collect(range(5, 0))->map(fn ($offset) => now()->subMonthsNoOverflow($offset));

        return array_merge($blank, [
            'enabled' => true,
            'invoice_count' => (int) (clone $invoiceQuery)->count(),
            'open_count' => (int) (clone $invoiceQuery)->whereNotIn('status', $closedStatuses)->count(),
            'revenue_month' => $this->sumBase((clone $invoiceQuery)->whereBetween('issue_date', [$range['month_start']->toDateString(), $range['now']->toDateString()]), 'total'),
            'revenue_previous' => $this->sumBase((clone $invoiceQuery)->whereBetween('issue_date', [$range['previous_start']->toDateString(), $range['previous_end']->toDateString()]), 'total'),
            'payments_month' => $paymentQuery ? $this->sumBase((clone $paymentQuery)->whereBetween('payment_date', [$range['month_start']->toDateString(), $range['now']->toDateString()]), 'amount') : 0.0,
            'payments_previous' => $paymentQuery ? $this->sumBase((clone $paymentQuery)->whereBetween('payment_date', [$range['previous_start']->toDateString(), $range['previous_end']->toDateString()]), 'amount') : 0.0,
            'pending_amount' => $this->sumBase((clone $invoiceQuery)->whereNotIn('status', $closedStatuses), 'amount_due'),
            'recent_invoices' => $this->tenantQuery(Invoice::class, $tenantId)
                ->with(['client' => fn ($query) => $query->withoutGlobalScope('tenant')->select('id', 'tenant_id', 'company_name')])
                ->latest('created_at')
                ->limit(5)
                ->get(['id', 'client_id', 'number', 'status', 'total', 'currency', 'issue_date', 'due_date', 'created_at']),
            'open_invoices' => $this->tenantQuery(Invoice::class, $tenantId)
                ->with(['client' => fn ($query) => $query->withoutGlobalScope('tenant')->select('id', 'tenant_id', 'company_name')])
                ->whereNotIn('status', $closedStatuses)
                ->orderByRaw('COALESCE(due_date, issue_date) asc')
                ->limit(5)
                ->get(['id', 'client_id', 'number', 'status', 'total', 'amount_due', 'currency', 'issue_date', 'due_date']),
            'chart' => [
                'labels' => $months->map(fn ($month) => $month->translatedFormat('M'))->values()->all(),
                'invoices' => $months->map(function ($month) use ($tenantId) {
                    return round($this->sumBase(
                        $this->tenantQuery(Invoice::class, $tenantId)
                            ->whereBetween('issue_date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()]),
                        'total'
                    ), 2);
                })->values()->all(),
                'payments' => $months->map(function ($month) use ($tenantId, $paymentQuery) {
                    if (! $paymentQuery) {
                        return 0;
                    }

                    return round($this->sumBase(
                        $this->tenantQuery(Payment::class, $tenantId)
                            ->whereBetween('payment_date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()]),
                        'amount'
                    ), 2);
                })->values()->all(),
                'currency' => $currency,
            ],
        ]);
    }

    private function projectSnapshot(int $tenantId, array $access, array $range): array
    {
        $blank = [
            'enabled' => false,
            'total' => 0,
            'active' => 0,
            'due_soon' => 0,
            'status_counts' => ['todo' => 0, 'in_progress' => 0, 'review' => 0, 'done' => 0],
            'upcoming_tasks' => collect(),
            'chart' => null,
            'route' => $this->routeIfExists('projects.index'),
        ];

        if (! $access['projects'] || ! Schema::hasTable('projects')) {
            return $blank;
        }

        $projectQuery = $this->tenantQuery(Project::class, $tenantId);
        $statusCounts = $blank['status_counts'];
        $upcomingTasks = collect();
        $dueSoon = 0;

        if (Schema::hasTable('project_tasks')) {
            $taskQuery = $this->tenantQuery(ProjectTask::class, $tenantId);
            $statusCounts = [
                'todo' => (int) (clone $taskQuery)->where('status', 'todo')->count(),
                'in_progress' => (int) (clone $taskQuery)->where('status', 'in_progress')->count(),
                'review' => (int) (clone $taskQuery)->where('status', 'review')->count(),
                'done' => (int) (clone $taskQuery)->where('status', 'done')->count(),
            ];
            $dueSoon = (int) (clone $taskQuery)
                ->whereNotIn('status', ['done'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$range['today'], $range['next_week']])
                ->count();
            $upcomingTasks = $this->tenantQuery(ProjectTask::class, $tenantId)
                ->with(['project' => fn ($query) => $query->withoutGlobalScope('tenant')->select('id', 'tenant_id', 'name')])
                ->whereNotIn('status', ['done'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$range['today'], $range['next_week']])
                ->orderBy('due_date')
                ->limit(5)
                ->get(['id', 'project_id', 'title', 'status', 'priority', 'due_date']);
        }

        return array_merge($blank, [
            'enabled' => true,
            'total' => (int) (clone $projectQuery)->count(),
            'active' => (int) (clone $projectQuery)->whereIn('status', ['planning', 'active', 'on_hold'])->count(),
            'due_soon' => $dueSoon,
            'status_counts' => $statusCounts,
            'upcoming_tasks' => $upcomingTasks,
            'chart' => [
                'labels' => [
                    __('dashboard.charts.projects.todo'),
                    __('dashboard.charts.projects.in_progress'),
                    __('dashboard.charts.projects.review'),
                    __('dashboard.charts.projects.done'),
                ],
                'data' => array_values($statusCounts),
            ],
        ]);
    }

    private function stockSnapshot(int $tenantId, array $access): array
    {
        $blank = [
            'enabled' => false,
            'articles' => 0,
            'critical' => 0,
            'healthy' => 0,
            'pending_orders' => 0,
            'critical_articles' => collect(),
            'chart' => null,
            'route' => $this->routeIfExists('stock.articles.index'),
        ];

        if (! $access['stock'] || ! Schema::hasTable('stock_articles')) {
            return $blank;
        }

        $articles = (int) $this->tenantQuery(Article::class, $tenantId)->count();
        $critical = (int) DB::query()
            ->fromSub($this->stockArticlesQuery($tenantId), 'article_stocks')
            ->where('min_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->count();

        return array_merge($blank, [
            'enabled' => true,
            'articles' => $articles,
            'critical' => $critical,
            'healthy' => max(0, $articles - $critical),
            'pending_orders' => Schema::hasTable('stock_orders')
                ? (int) $this->tenantQuery(Order::class, $tenantId)->whereNotIn('status', ['received', 'cancelled'])->count()
                : 0,
            'critical_articles' => DB::query()
                ->fromSub($this->stockArticlesQuery($tenantId), 'article_stocks')
                ->where('min_stock', '>', 0)
                ->whereColumn('current_stock', '<=', 'min_stock')
                ->orderByRaw('current_stock - min_stock asc')
                ->limit(5)
                ->get(),
            'chart' => [
                'labels' => [__('dashboard.charts.stock.critical'), __('dashboard.charts.stock.healthy')],
                'data' => [$critical, max(0, $articles - $critical)],
            ],
        ]);
    }

    private function integrationSnapshot(Collection $apps, int $tenantId): array
    {
        $cards = $this->buildIntegrationCards($apps, $tenantId);
        $collection = collect($cards);

        return [
            'total' => $collection->count(),
            'connected' => $collection->where('state', 'connected')->count(),
            'attention' => $collection->where('state', 'attention')->count(),
            'installed' => $collection->where('state', 'installed')->count(),
            'cards' => $cards,
            'chart' => [
                'labels' => [
                    __('dashboard.charts.integrations.connected'),
                    __('dashboard.charts.integrations.attention'),
                    __('dashboard.charts.integrations.installed'),
                ],
                'data' => [
                    $collection->where('state', 'connected')->count(),
                    $collection->where('state', 'attention')->count(),
                    $collection->where('state', 'installed')->count(),
                ],
            ],
        ];
    }

    private function moduleCards(array $access, array $clients, array $finance, array $projects, array $stock): array
    {
        return array_values(array_filter([
            $access['clients'] ? [
                'name' => __('dashboard.modules.clients.name'),
                'label' => __('dashboard.modules.clients.label'),
                'icon' => 'fas fa-handshake',
                'value' => $clients['total'],
                'caption' => __('dashboard.modules.clients.caption', ['count' => $clients['new_this_month']]),
                'url' => $clients['route'],
                'accent' => '#2563eb',
            ] : null,
            $access['invoice'] ? [
                'name' => __('dashboard.modules.invoice.name'),
                'label' => __('dashboard.modules.invoice.label'),
                'icon' => 'fas fa-file-invoice-dollar',
                'value' => $finance['invoice_count'],
                'caption' => __('dashboard.modules.invoice.caption', ['count' => $finance['open_count']]),
                'url' => $finance['route'],
                'accent' => '#0f766e',
            ] : null,
            $access['projects'] ? [
                'name' => __('dashboard.modules.projects.name'),
                'label' => __('dashboard.modules.projects.label'),
                'icon' => 'fas fa-diagram-project',
                'value' => $projects['active'],
                'caption' => __('dashboard.modules.projects.caption', ['count' => $projects['due_soon']]),
                'url' => $projects['route'],
                'accent' => '#7c3aed',
            ] : null,
            $access['stock'] ? [
                'name' => __('dashboard.modules.stock.name'),
                'label' => __('dashboard.modules.stock.label'),
                'icon' => 'fas fa-boxes-stacked',
                'value' => $stock['articles'],
                'caption' => __('dashboard.modules.stock.caption', ['count' => $stock['critical']]),
                'url' => $stock['route'],
                'accent' => '#ea580c',
            ] : null,
        ]));
    }

    private function quickActions($user, int $tenantId, array $access): array
    {
        $actions = [];

        if ($access['clients'] && $this->canTenant($user, $tenantId, 'clients.create') && $this->routeIfExists('clients.create')) {
            $actions[] = ['label' => __('dashboard.actions.new_client'), 'icon' => 'fas fa-user-plus', 'url' => $this->routeIfExists('clients.create'), 'variant' => 'primary'];
        }

        if ($access['invoice'] && $this->canTenant($user, $tenantId, 'invoices.create') && $this->routeIfExists('invoices.create')) {
            $actions[] = ['label' => __('dashboard.actions.new_invoice'), 'icon' => 'fas fa-file-circle-plus', 'url' => $this->routeIfExists('invoices.create'), 'variant' => 'secondary'];
        }

        if ($access['projects'] && $this->routeIfExists('projects.index')) {
            $actions[] = ['label' => __('dashboard.actions.projects'), 'icon' => 'fas fa-diagram-project', 'url' => $this->routeIfExists('projects.index'), 'variant' => 'secondary'];
        }

        if ($this->routeIfExists('marketplace.index')) {
            $actions[] = ['label' => __('dashboard.actions.applications'), 'icon' => 'fas fa-store', 'url' => $this->routeIfExists('marketplace.index'), 'variant' => 'ghost'];
        }

        if ($access['settings'] && $this->routeIfExists('settings.global')) {
            $actions[] = ['label' => __('dashboard.actions.settings'), 'icon' => 'fas fa-sliders', 'url' => $this->routeIfExists('settings.global'), 'variant' => 'ghost'];
        }

        return $actions;
    }

    private function signalCards(array $clients, array $finance, array $projects, array $stock, array $integrations, string $currency): array
    {
        $priorityCount = (int) $projects['due_soon'] + (int) $stock['critical'] + (int) $finance['open_count'];

        return [
            [
                'label' => __('dashboard.signals.revenue_month'),
                'value' => $this->formatCompactMoney((float) $finance['revenue_month'], $currency),
                'hint' => $this->trendLabel((float) $finance['revenue_month'], (float) $finance['revenue_previous']),
                'tone' => $this->trendTone((float) $finance['revenue_month'], (float) $finance['revenue_previous']),
                'icon' => 'fas fa-chart-line',
            ],
            [
                'label' => __('dashboard.signals.payments'),
                'value' => $this->formatCompactMoney((float) $finance['payments_month'], $currency),
                'hint' => $this->trendLabel((float) $finance['payments_month'], (float) $finance['payments_previous']),
                'tone' => $this->trendTone((float) $finance['payments_month'], (float) $finance['payments_previous']),
                'icon' => 'fas fa-wallet',
            ],
            [
                'label' => __('dashboard.signals.clients'),
                'value' => number_format((int) $clients['total'], 0, ',', ' '),
                'hint' => __('dashboard.signals.clients_hint', ['count' => number_format((int) $clients['new_this_month'], 0, ',', ' ')]),
                'tone' => 'blue',
                'icon' => 'fas fa-users',
            ],
            [
                'label' => __('dashboard.signals.priorities'),
                'value' => number_format($priorityCount, 0, ',', ' '),
                'hint' => __('dashboard.signals.priorities_hint'),
                'tone' => $priorityCount > 0 ? 'orange' : 'green',
                'icon' => 'fas fa-bolt',
            ],
            [
                'label' => __('dashboard.signals.integrations'),
                'value' => number_format((int) $integrations['connected'], 0, ',', ' '),
                'hint' => __('dashboard.signals.integrations_hint', ['count' => $integrations['attention']]),
                'tone' => $integrations['attention'] > 0 ? 'red' : 'green',
                'icon' => 'fas fa-plug-circle-check',
            ],
        ];
    }

    private function activityFeed(int $tenantId, array $access, Collection $apps, array $clients, array $finance, array $projects, string $currency): Collection
    {
        $items = collect();

        if ($access['clients']) {
            $items = $items->merge($clients['recent']->map(fn (Client $client) => [
                'at' => $client->created_at,
                'icon' => 'fas fa-user-plus',
                'title' => __('dashboard.activity.client_created'),
                'description' => $client->company_name ?: ($client->contact_name ?: __('dashboard.activity.client_fallback')),
                'url' => $this->routeIfExists('clients.show', ['client' => $client->id]),
                'tone' => '#2563eb',
            ]));
        }

        if ($access['invoice']) {
            $items = $items->merge($finance['recent_invoices']->map(fn (Invoice $invoice) => [
                'at' => $invoice->created_at,
                'icon' => 'fas fa-file-invoice-dollar',
                'title' => __('dashboard.activity.invoice_created'),
                'description' => trim(($invoice->number ?: __('dashboard.activity.invoice_fallback')) . ' · ' . $this->formatMoney((float) $invoice->total, (string) ($invoice->currency ?: $currency))),
                'url' => $this->routeIfExists('invoices.show', ['invoice' => $invoice->id]),
                'tone' => '#0f766e',
            ]));
        }

        if ($access['projects'] && Schema::hasTable('project_activities')) {
            $items = $items->merge(
                $this->tenantQuery(ProjectActivity::class, $tenantId)
                    ->with(['project' => fn ($query) => $query->withoutGlobalScope('tenant')->select('id', 'tenant_id', 'name')])
                    ->latest('created_at')
                    ->limit(6)
                    ->get(['id', 'project_id', 'event', 'description', 'created_at'])
                    ->map(fn (ProjectActivity $activity) => [
                        'at' => $activity->created_at,
                        'icon' => 'fas fa-list-check',
                        'title' => __('dashboard.activity.project_updated'),
                        'description' => trim(($activity->description ?: $activity->event ?: __('dashboard.activity.project_update_fallback')) . ($activity->project?->name ? ' · ' . $activity->project->name : '')),
                        'url' => $activity->project_id ? $this->routeIfExists('projects.show', ['project' => $activity->project_id]) : null,
                        'tone' => '#7c3aed',
                    ])
            );
        }

        if (class_exists(Draft::class) && Schema::hasTable('drafts')) {
            $draftService = app(DraftService::class);
            $items = $items->merge(
                Draft::query()
                    ->forActor((int) Auth::id(), $tenantId)
                    ->notExpired()
                    ->latest('updated_at')
                    ->limit(4)
                    ->get(['id', 'type', 'route', 'updated_at'])
                    ->map(fn (Draft $draft) => [
                        'at' => $draft->updated_at,
                        'icon' => 'fas fa-pen-to-square',
                        'title' => __('dashboard.activity.draft_resume'),
                        'description' => __('dashboard.activity.draft_description', ['type' => ucfirst((string) $draft->type)]),
                        'url' => $draftService->resolveResumeUrl($draft),
                        'tone' => '#ea580c',
                    ])
            );
        }

        $items = $items->merge($apps->take(5)->map(fn (TenantExtension $activation) => [
            'at' => $activation->activated_at ?? $activation->created_at,
            'icon' => 'fas fa-puzzle-piece',
            'title' => __('dashboard.activity.app_active'),
            'description' => (string) ($activation->extension?->name ?: __('dashboard.activity.app_fallback')),
            'url' => $this->routeIfExists('marketplace.show', [(string) ($activation->extension?->slug ?? '')]),
            'tone' => '#0891b2',
        ]));

        return $items
            ->filter(fn (array $item) => ! empty($item['at']))
            ->sortByDesc(fn (array $item) => $item['at'])
            ->take(12)
            ->values();
    }

    private function focusItems(array $finance, array $projects, array $stock, string $currency): Collection
    {
        $items = collect();

        $items = $items->merge($finance['open_invoices']->map(fn (Invoice $invoice) => [
            'kind' => __('dashboard.focus.open_invoice'),
            'title' => $invoice->number ?: __('dashboard.focus.invoice_fallback'),
            'description' => $invoice->client?->company_name ?: __('dashboard.focus.missing_client'),
            'meta' => $this->formatMoney((float) ($invoice->amount_due ?? $invoice->total ?? 0), (string) ($invoice->currency ?: $currency)),
            'date' => $invoice->due_date,
            'url' => $this->routeIfExists('invoices.show', ['invoice' => $invoice->id]),
            'icon' => 'fas fa-file-invoice-dollar',
            'tone' => '#0f766e',
        ]));

        $items = $items->merge($projects['upcoming_tasks']->map(fn (ProjectTask $task) => [
            'kind' => __('dashboard.focus.upcoming_task'),
            'title' => $task->title,
            'description' => $task->project?->name ?: __('dashboard.focus.missing_project'),
            'meta' => (string) ($task->priority ?: $task->status),
            'date' => $task->due_date,
            'url' => $task->project_id ? $this->routeIfExists('projects.show', ['project' => $task->project_id]) : null,
            'icon' => 'fas fa-list-check',
            'tone' => '#7c3aed',
        ]));

        $items = $items->merge($stock['critical_articles']->map(fn ($article) => [
            'kind' => __('dashboard.focus.critical_stock'),
            'title' => $article->name ?: __('dashboard.focus.article_fallback'),
            'description' => $article->sku ?: __('dashboard.focus.missing_sku'),
            'meta' => number_format((float) ($article->current_stock ?? 0), 0, ',', ' ') . ' / ' . number_format((float) ($article->min_stock ?? 0), 0, ',', ' '),
            'date' => null,
            'url' => $stock['route'],
            'icon' => 'fas fa-box-open',
            'tone' => '#ea580c',
        ]));

        return $items->take(9)->values();
    }

    private function buildIntegrationCards(Collection $apps, int $tenantId): array
    {
        $definitions = [
            'notion-workspace' => ['name' => 'Notion', 'icon' => 'fas fa-book-open', 'color' => '#111827', 'token' => NotionWorkspaceToken::class, 'resource' => NotionPageLink::class, 'label' => 'pages', 'account' => 'notion_user_email', 'context' => 'notion_workspace_name', 'sync' => 'last_synced_at', 'route' => 'notion-workspace.index'],
            'trello-integration' => ['name' => 'Trello', 'icon' => 'fab fa-trello', 'color' => '#026aa7', 'token' => TrelloToken::class, 'resource' => TrelloBoard::class, 'label' => 'boards', 'account' => 'trello_username', 'context' => 'trello_full_name', 'sync' => 'last_synced_at', 'route' => 'trello-integration.index'],
            'google-drive' => ['name' => 'Drive', 'icon' => 'fab fa-google-drive', 'color' => '#4285f4', 'token' => GoogleDriveToken::class, 'resource' => GoogleDriveFile::class, 'label' => 'files', 'account' => 'google_email', 'context' => 'quota_formatted', 'sync' => 'last_sync_at', 'route' => 'google-drive.index'],
            'dropbox' => ['name' => 'Dropbox', 'icon' => 'fab fa-dropbox', 'color' => '#0061ff', 'token' => DropboxToken::class, 'resource' => DropboxFile::class, 'label' => 'files', 'account' => 'dropbox_email', 'context' => null, 'sync' => 'last_sync_at', 'route' => 'dropbox.index'],
            'google-calendar' => ['name' => 'Calendar', 'icon' => 'fas fa-calendar-days', 'color' => '#4285f4', 'token' => GoogleCalendarToken::class, 'resource' => GoogleCalendarEvent::class, 'label' => 'events', 'account' => 'google_email', 'context' => 'selected_calendar_summary', 'sync' => 'last_sync_at', 'route' => 'google-calendar.index'],
            'google-sheets' => ['name' => 'Sheets', 'icon' => 'fas fa-file-excel', 'color' => '#0f9d58', 'token' => GoogleSheetsToken::class, 'resource' => GoogleSheetsSpreadsheet::class, 'label' => 'spreadsheets', 'account' => 'google_email', 'context' => 'google_name', 'sync' => 'last_sync_at', 'route' => 'google-sheets.index'],
            'google-docx' => ['name' => 'Docs', 'icon' => 'fas fa-file-word', 'color' => '#1a73e8', 'token' => GoogleDocxToken::class, 'resource' => GoogleDocxDocument::class, 'label' => 'documents', 'account' => 'google_email', 'context' => 'google_name', 'sync' => 'last_sync_at', 'route' => 'google-docx.index'],
            'google-gmail' => ['name' => 'Gmail', 'icon' => 'fas fa-envelope-open-text', 'color' => '#ea4335', 'token' => GoogleGmailToken::class, 'resource' => GoogleGmailMessage::class, 'label' => 'messages', 'account' => 'google_email', 'context' => 'google_name', 'sync' => 'last_sync_at', 'route' => 'google-gmail.index'],
            'google-meet' => ['name' => 'Meet', 'icon' => 'fas fa-video', 'color' => '#34a853', 'token' => GoogleMeetToken::class, 'resource' => GoogleMeetMeeting::class, 'label' => 'meetings', 'account' => 'google_email', 'context' => 'selected_calendar_summary', 'sync' => 'last_sync_at', 'route' => 'google-meet.index'],
            'slack' => ['name' => 'Slack', 'icon' => 'fab fa-slack', 'color' => '#4a154b', 'token' => SlackToken::class, 'resource' => SlackMessage::class, 'label' => 'messages', 'account' => 'team_name', 'context' => 'selected_channel_name', 'sync' => 'last_sync_at', 'route' => 'slack.index'],
            'chatbot' => ['name' => 'Chatbot', 'icon' => 'fas fa-comments', 'color' => '#0ea5e9', 'token' => null, 'resource' => ChatbotMessage::class, 'label' => 'messages', 'account' => null, 'context' => null, 'sync' => null, 'route' => 'chatbot.index'],
        ];

        return $apps->map(function (TenantExtension $activation) use ($definitions, $tenantId) {
            $slug = (string) ($activation->extension?->slug ?? '');
            if (! isset($definitions[$slug])) {
                return null;
            }

            $definition = $definitions[$slug];
            $token = null;
            if ($definition['token'] && class_exists($definition['token']) && $this->modelTableExists($definition['token'])) {
                $token = $this->tenantQuery($definition['token'], $tenantId)->latest('id')->first();
            }

            $resourceCount = 0;
            if ($definition['resource'] && class_exists($definition['resource']) && $this->modelTableExists($definition['resource'])) {
                $resourceCount = (int) $this->tenantQuery($definition['resource'], $tenantId)->count();
            }

            $state = 'installed';
            $stateLabel = __('dashboard.integrations.states.installed');
            if ($slug === 'chatbot') {
                $state = 'connected';
                $stateLabel = __('dashboard.integrations.states.internal');
            } elseif ($token) {
                $isActive = isset($token->is_active) ? (bool) $token->is_active : true;
                $isExpired = method_exists($token, 'isExpired') ? (bool) $token->isExpired() : (bool) data_get($token, 'is_expired', false);
                $state = $isActive && ! $isExpired ? 'connected' : 'attention';
                $stateLabel = $state === 'connected' ? __('dashboard.integrations.states.connected') : __('dashboard.integrations.states.attention');
            }

            $account = $token && $definition['account'] ? (string) data_get($token, $definition['account']) : null;
            $context = $token && $definition['context'] ? (string) data_get($token, $definition['context']) : null;
            $lastSync = $token && $definition['sync'] ? data_get($token, $definition['sync']) : null;

            return [
                'slug' => $slug,
                'name' => $definition['name'],
                'icon' => (string) ($activation->extension?->icon ?: $definition['icon']),
                'icon_url' => $activation->extension?->icon_url,
                'color' => (string) ($activation->extension?->icon_bg_color ?: $definition['color']),
                'state' => $state,
                'state_label' => $stateLabel,
                'account' => $account,
                'context' => $context,
                'resource_count' => $resourceCount,
                'resource_label' => __('dashboard.integrations.resources.' . $definition['label']),
                'last_sync' => $lastSync,
                'url' => isset($definition['route']) ? $this->routeIfExists($definition['route']) : null,
            ];
        })->filter()->sortBy([['state', 'asc'], ['name', 'asc']])->values()->all();
    }

    private function featureIsVisible($user, int $tenantId, Collection $slugs, array|string $slug, array|string|null $permission): bool
    {
        if (! $this->hasInstalled($slugs, $slug)) {
            return false;
        }

        return $this->canTenant($user, $tenantId, $permission);
    }

    private function canTenant($user, int $tenantId, array|string|null $permissions): bool
    {
        if (empty($permissions)) {
            return true;
        }

        if ((bool) ($user->is_tenant_owner ?? false) || in_array((string) ($user->role_in_tenant ?? ''), ['owner', 'admin'], true)) {
            return true;
        }

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions') || ! method_exists($user, 'hasTenantPermission')) {
            return true;
        }

        try {
            return $user->hasTenantPermission($permissions, $tenantId);
        } catch (\Throwable) {
            return false;
        }
    }

    private function tenantQuery(string $modelClass, int $tenantId)
    {
        $table = (new $modelClass())->getTable();

        return $modelClass::query()
            ->withoutGlobalScope('tenant')
            ->where($table . '.tenant_id', $tenantId);
    }

    /**
     * Somme convertie dans la devise de base du tenant (montant × taux figé).
     * Permet d'agréger des documents libellés dans des devises différentes.
     */
    private function sumBase($query, string $column): float
    {
        return (float) $query
            ->selectRaw("COALESCE(SUM({$column} * COALESCE(exchange_rate, 1)), 0) as s")
            ->value('s');
    }

    private function stockArticlesQuery(int $tenantId)
    {
        return $this->tenantQuery(Article::class, $tenantId)
            ->select(['stock_articles.id', 'stock_articles.name', 'stock_articles.sku', 'stock_articles.min_stock', 'stock_articles.status'])
            ->addSelect([
                'current_stock' => StockMovement::query()
                    ->withoutGlobalScope('tenant')
                    ->selectRaw("COALESCE(SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.quantity ELSE -stock_movements.quantity END), 0)")
                    ->where('stock_movements.tenant_id', $tenantId)
                    ->whereColumn('stock_movements.article_id', 'stock_articles.id'),
            ]);
    }

    private function hasInstalled(Collection $installedSlugs, array|string $needle): bool
    {
        $needles = collect(is_array($needle) ? $needle : [$needle])->map(fn ($slug) => (string) $slug)->all();

        return $installedSlugs->intersect($needles)->isNotEmpty();
    }

    private function modelTableExists(string $modelClass): bool
    {
        if (! class_exists($modelClass)) {
            return false;
        }

        return Schema::hasTable((new $modelClass())->getTable());
    }

    private function routeIfExists(string $routeName, array|string $params = []): ?string
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return route($routeName, is_array($params) ? $params : [$params]);
    }

    private function trendLabel(float|int $current, float|int $previous): string
    {
        if ((float) $previous <= 0.0 && (float) $current <= 0.0) {
            return __('dashboard.trend.stable');
        }

        if ((float) $previous <= 0.0) {
            return __('dashboard.trend.new_growth');
        }

        $percent = (($current - $previous) / $previous) * 100;
        $prefix = $percent > 0 ? '+' : '';

        return __('dashboard.trend.vs_previous', ['percent' => $prefix . number_format($percent, 0, ',', ' ')]);
    }

    private function trendTone(float|int $current, float|int $previous): string
    {
        return (float) $current >= (float) $previous ? 'green' : 'red';
    }

    private function formatMoney(float|int $value, string $currency): string
    {
        return number_format((float) $value, 2, ',', ' ') . ' ' . strtoupper($currency);
    }

    private function formatCompactMoney(float|int $value, string $currency): string
    {
        $absolute = abs((float) $value);

        if ($absolute >= 1000000) {
            return number_format((float) $value / 1000000, 1, ',', ' ') . 'M ' . strtoupper($currency);
        }

        if ($absolute >= 1000) {
            return number_format((float) $value / 1000, 0, ',', ' ') . 'K ' . strtoupper($currency);
        }

        return number_format((float) $value, 0, ',', ' ') . ' ' . strtoupper($currency);
    }
}
