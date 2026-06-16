<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $tenantId = (int) session('current_tenant_id', $user->tenant_id ?? 0);

        $stats = [
            'active_apps' => 0,
            'users' => 0,
            'clients' => 0,
            'open_invoices' => 0,
            'tasks_due_soon' => 0,
            'stock_alerts' => 0,
        ];

        if ($tenantId > 0 && Schema::hasTable('tenant_extensions') && Schema::hasTable('extensions')) {
            $stats['active_apps'] = (int) DB::table('tenant_extensions')
                ->join('extensions', 'extensions.id', '=', 'tenant_extensions.extension_id')
                ->where('tenant_extensions.tenant_id', $tenantId)
                ->whereIn('tenant_extensions.status', ['active', 'trial'])
                ->where('extensions.status', 'active')
                ->count();
        }

        if ($tenantId > 0 && Schema::hasTable('users')) {
            $stats['users'] = (int) DB::table('users')
                ->where('tenant_id', $tenantId)
                ->count();
        }

        if ($tenantId > 0 && Schema::hasTable('clients')) {
            $stats['clients'] = (int) DB::table('clients')
                ->where('tenant_id', $tenantId)
                ->count();
        }

        if ($tenantId > 0 && Schema::hasTable('invoices')) {
            $stats['open_invoices'] = (int) DB::table('invoices')
                ->where('tenant_id', $tenantId)
                ->whereNotIn('status', ['paid', 'cancelled', 'refunded'])
                ->count();
        }

        if ($tenantId > 0 && Schema::hasTable('project_tasks')) {
            $stats['tasks_due_soon'] = (int) DB::table('project_tasks')
                ->where('tenant_id', $tenantId)
                ->whereNotIn('status', ['done'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->count();
        }

        if ($tenantId > 0 && Schema::hasTable('stock_articles') && Schema::hasTable('stock_movements')) {
            $articleStocks = DB::table('stock_articles')
                ->select([
                    'stock_articles.id',
                    'stock_articles.min_stock',
                ])
                ->selectSub(function ($query) use ($tenantId) {
                    $query->from('stock_movements')
                        ->selectRaw("COALESCE(SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.quantity ELSE -stock_movements.quantity END), 0)")
                        ->where('stock_movements.tenant_id', $tenantId)
                        ->whereColumn('stock_movements.article_id', 'stock_articles.id');
                }, 'current_stock')
                ->where('stock_articles.tenant_id', $tenantId);

            $stats['stock_alerts'] = (int) DB::query()
                ->fromSub($articleStocks, 'article_stocks')
                ->where('min_stock', '>', 0)
                ->whereColumn('current_stock', '<=', 'min_stock')
                ->count();
        }

        $summaryCards = collect([
            ['label' => 'Applications actives', 'value' => $stats['active_apps'], 'icon' => 'fa-plug-circle-check', 'tone' => 'blue'],
            ['label' => 'Utilisateurs', 'value' => $stats['users'], 'icon' => 'fa-users-gear', 'tone' => 'slate'],
            ['label' => 'Clients', 'value' => $stats['clients'], 'icon' => 'fa-address-book', 'tone' => 'green'],
            ['label' => 'Factures ouvertes', 'value' => $stats['open_invoices'], 'icon' => 'fa-file-invoice-dollar', 'tone' => $stats['open_invoices'] > 0 ? 'amber' : 'green'],
            ['label' => 'Tâches à 7 jours', 'value' => $stats['tasks_due_soon'], 'icon' => 'fa-list-check', 'tone' => $stats['tasks_due_soon'] > 0 ? 'amber' : 'green'],
            ['label' => 'Alertes stock', 'value' => $stats['stock_alerts'], 'icon' => 'fa-boxes-stacked', 'tone' => $stats['stock_alerts'] > 0 ? 'red' : 'green'],
        ]);

        $priorities = collect([
            ['label' => 'Factures ouvertes', 'value' => $stats['open_invoices'], 'tone' => $stats['open_invoices'] > 0 ? 'warning' : 'ok'],
            ['label' => 'Tâches à échéance (7 jours)', 'value' => $stats['tasks_due_soon'], 'tone' => $stats['tasks_due_soon'] > 0 ? 'warning' : 'ok'],
            ['label' => 'Alertes stock', 'value' => $stats['stock_alerts'], 'tone' => $stats['stock_alerts'] > 0 ? 'warning' : 'ok'],
        ]);

        $recentActivity = $this->buildRecentActivity($tenantId);
        $quickActions = collect([
            ['label' => 'Applications', 'icon' => 'fa-table-cells-large', 'url' => Route::has('applications') ? route('applications') : null],
            ['label' => 'Paramètres', 'icon' => 'fa-sliders', 'url' => Route::has('settings.global') ? route('settings.global') : null],
            ['label' => 'Profil', 'icon' => 'fa-user-gear', 'url' => Route::has('profile-settings') ? route('profile-settings') : null],
        ])->filter(fn (array $action) => ! empty($action['url']))->values();

        return view('home', [
            'user' => $user,
            'tenantId' => $tenantId,
            'stats' => $stats,
            'summaryCards' => $summaryCards,
            'priorities' => $priorities,
            'recentActivity' => $recentActivity,
            'quickActions' => $quickActions,
        ]);
    }

    private function buildRecentActivity(int $tenantId): Collection
    {
        if ($tenantId <= 0) {
            return collect();
        }

        $activity = collect();

        if (Schema::hasTable('clients')) {
            $clients = DB::table('clients')
                ->select(['company_name as title', 'created_at'])
                ->where('tenant_id', $tenantId)
                ->orderByDesc('created_at')
                ->limit(4)
                ->get()
                ->map(function ($row) {
                    return [
                        'icon' => 'fa-users',
                        'title' => 'Client ajouté',
                        'description' => $row->title ?: 'Client sans nom',
                        'at' => $row->created_at,
                    ];
                });

            $activity = $activity->merge($clients);
        }

        if (Schema::hasTable('invoices')) {
            $invoices = DB::table('invoices')
                ->select(['number', 'total', 'currency', 'created_at'])
                ->where('tenant_id', $tenantId)
                ->orderByDesc('created_at')
                ->limit(4)
                ->get()
                ->map(function ($row) {
                    $amount = number_format((float) ($row->total ?? 0), 2, ',', ' ');
                    $currency = strtoupper((string) ($row->currency ?? 'EUR'));

                    return [
                        'icon' => 'fa-file-invoice',
                        'title' => 'Facture créée',
                        'description' => trim(($row->number ?: 'Facture') . ' · ' . $amount . ' ' . $currency),
                        'at' => $row->created_at,
                    ];
                });

            $activity = $activity->merge($invoices);
        }

        if (Schema::hasTable('project_tasks')) {
            $tasks = DB::table('project_tasks')
                ->select(['title', 'status', 'updated_at'])
                ->where('tenant_id', $tenantId)
                ->orderByDesc('updated_at')
                ->limit(4)
                ->get()
                ->map(function ($row) {
                    return [
                        'icon' => 'fa-list-check',
                        'title' => 'Tâche mise à jour',
                        'description' => trim(($row->title ?: 'Tâche') . ' · ' . (string) ($row->status ?? 'in_progress')),
                        'at' => $row->updated_at,
                    ];
                });

            $activity = $activity->merge($tasks);
        }

        return $activity
            ->filter(fn (array $item) => ! empty($item['at']))
            ->sortByDesc('at')
            ->take(6)
            ->values();
    }
}
