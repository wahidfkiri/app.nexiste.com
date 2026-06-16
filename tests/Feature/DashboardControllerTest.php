<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
    }

    public function test_dashboard_uses_the_current_tenant_session_and_renders_module_data(): void
    {
        $this->seedTenants();
        $user = $this->createUser(baseTenantId: 1);
        $this->markOnboardingCompleted(1);
        session()->put('current_tenant_id', 2);
        $this->actingAs($user);

        $this->seedExtensionsForTenant(2, ['clients', 'invoice', 'stock', 'projects']);

        DB::table('clients')->insert([
            [
                'id' => 11,
                'tenant_id' => 1,
                'company_name' => 'Base Tenant Client',
                'contact_name' => 'Base Contact',
                'email' => 'base@example.test',
                'status' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 22,
                'tenant_id' => 2,
                'company_name' => 'Tenant Session Client',
                'contact_name' => 'Session Contact',
                'email' => 'session@example.test',
                'status' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('invoices')->insert([
            [
                'id' => 100,
                'tenant_id' => 1,
                'client_id' => 11,
                'number' => 'INV-BASE',
                'status' => 'sent',
                'currency' => 'EUR',
                'total' => 900,
                'amount_due' => 900,
                'issue_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 200,
                'tenant_id' => 2,
                'client_id' => 22,
                'number' => 'INV-SESSION',
                'status' => 'sent',
                'currency' => 'EUR',
                'total' => 450,
                'amount_due' => 450,
                'issue_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('payments')->insert([
            [
                'id' => 1,
                'tenant_id' => 2,
                'invoice_id' => 200,
                'user_id' => $user->id,
                'amount' => 150,
                'currency' => 'EUR',
                'payment_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('stock_articles')->insert([
            [
                'id' => 301,
                'tenant_id' => 2,
                'user_id' => $user->id,
                'sku' => 'SAFE-1',
                'name' => 'Healthy Article',
                'unit' => 'piece',
                'min_stock' => 3,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 302,
                'tenant_id' => 2,
                'user_id' => $user->id,
                'sku' => 'LOW-1',
                'name' => 'Critical Article',
                'unit' => 'piece',
                'min_stock' => 3,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('stock_movements')->insert([
            [
                'id' => 1,
                'tenant_id' => 2,
                'user_id' => $user->id,
                'article_id' => 301,
                'direction' => 'in',
                'quantity' => 10,
                'unit' => 'piece',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'tenant_id' => 2,
                'user_id' => $user->id,
                'article_id' => 302,
                'direction' => 'in',
                'quantity' => 1,
                'unit' => 'piece',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('stock_orders')->insert([
            [
                'id' => 401,
                'tenant_id' => 2,
                'number' => 'PO-SESSION',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('projects')->insert([
            [
                'id' => 501,
                'tenant_id' => 2,
                'client_id' => 22,
                'owner_id' => $user->id,
                'name' => 'Projet Session',
                'status' => 'active',
                'progress' => 55,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('project_tasks')->insert([
            [
                'id' => 601,
                'tenant_id' => 2,
                'project_id' => 501,
                'client_id' => 22,
                'created_by' => $user->id,
                'assigned_to' => $user->id,
                'title' => 'Task Session',
                'status' => 'todo',
                'priority' => 'high',
                'due_date' => now()->addDays(2)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $result = app(DashboardController::class)->index();

        $this->assertInstanceOf(View::class, $result);
        $this->assertSame('dashboard', $result->name());

        $data = $result->getData();

        $this->assertSame(2, $data['currentTenantId']);
        $this->assertTrue($data['hasClients']);
        $this->assertTrue($data['hasInvoice']);
        $this->assertTrue($data['hasStock']);
        $this->assertTrue($data['hasProjects']);

        $this->assertCount(1, $data['recentClients']);
        $this->assertSame('Tenant Session Client', $data['recentClients']->first()->company_name);

        $this->assertCount(1, $data['recentInvoices']);
        $this->assertSame('INV-SESSION', $data['recentInvoices']->first()->number);

        $this->assertCount(1, $data['criticalArticles']);
        $this->assertSame('Critical Article', $data['criticalArticles']->first()->name);

        $stockCard = collect($data['statsCards'])->firstWhere('label', 'Stock critique');
        $this->assertNotNull($stockCard);
        $this->assertSame('1', $stockCard['value']);

        $this->assertSame([1, 1], $data['stockChart']['data']);
        $this->assertSame(['Clients', 'Facturation', 'Stock', 'Commandes fournisseur', 'Projets', 'Tâches à échéance'], collect($data['moduleSummary'])->pluck('name')->all());
    }

    public function test_dashboard_skips_sections_for_uninstalled_modules(): void
    {
        $this->seedTenants();
        $user = $this->createUser(baseTenantId: 1);
        $this->markOnboardingCompleted(1);
        session()->put('current_tenant_id', 1);
        $this->actingAs($user);

        $this->seedExtensionsForTenant(1, ['clients']);

        DB::table('clients')->insert([
            'tenant_id' => 1,
            'company_name' => 'Client Only',
            'contact_name' => 'Solo Contact',
            'email' => 'clientonly@example.test',
            'status' => 'actif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoices')->insert([
            'tenant_id' => 1,
            'client_id' => 1,
            'number' => 'INV-SHOULD-NOT-SHOW',
            'status' => 'sent',
            'currency' => 'EUR',
            'total' => 300,
            'amount_due' => 300,
            'issue_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(DashboardController::class)->index();

        $this->assertInstanceOf(View::class, $result);

        $data = $result->getData();

        $this->assertTrue($data['hasClients']);
        $this->assertFalse($data['hasInvoice']);
        $this->assertFalse($data['hasStock']);
        $this->assertFalse($data['hasProjects']);
        $this->assertNull($data['financeChart']);
        $this->assertNull($data['stockChart']);
        $this->assertNull($data['taskChart']);
        $this->assertCount(0, $data['recentInvoices']);
        $this->assertSame(['Clients'], collect($data['moduleSummary'])->pluck('name')->all());
    }

    public function test_dashboard_does_not_show_data_for_inactive_extensions(): void
    {
        $this->seedTenants();
        $user = $this->createUser(baseTenantId: 1);
        $this->markOnboardingCompleted(1);
        session()->put('current_tenant_id', 1);
        $this->actingAs($user);

        DB::table('extensions')->insert([
            [
                'id' => 1,
                'slug' => 'invoice',
                'name' => 'Facturation',
                'category' => 'finance',
                'icon' => 'fa-file-invoice',
                'icon_bg_color' => '#7c3aed',
                'status' => 'active',
                'sort_order' => 20,
                'active_installs_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'slug' => 'dropbox',
                'name' => 'Dropbox',
                'category' => 'storage',
                'icon' => 'fa-dropbox',
                'icon_bg_color' => '#0061FF',
                'status' => 'active',
                'sort_order' => 30,
                'active_installs_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('tenant_extensions')->insert([
            [
                'tenant_id' => 1,
                'extension_id' => 1,
                'status' => 'inactive',
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => 1,
                'extension_id' => 2,
                'status' => 'inactive',
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('invoices')->insert([
            'tenant_id' => 1,
            'client_id' => 1,
            'number' => 'INV-INACTIVE',
            'status' => 'sent',
            'currency' => 'EUR',
            'total' => 250,
            'amount_due' => 250,
            'issue_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(DashboardController::class)->index();

        $this->assertInstanceOf(View::class, $result);

        $data = $result->getData();

        $this->assertFalse($data['hasInvoice']);
        $this->assertCount(0, $data['recentInvoices']);
        $this->assertCount(0, $data['integrationCards']);
        $this->assertCount(0, $data['installedByCategory']);
        $this->assertSame([], collect($data['moduleSummary'])->pluck('name')->all());

        $applicationsCard = collect($data['statsCards'])->firstWhere('label', 'Applications actives');
        $this->assertNotNull($applicationsCard);
        $this->assertSame('0', $applicationsCard['value']);
    }

    public function test_dashboard_does_not_show_extensions_inactive_in_catalog_even_if_tenant_activation_is_active(): void
    {
        $this->seedTenants();
        $user = $this->createUser(baseTenantId: 1);
        $this->markOnboardingCompleted(1);
        session()->put('current_tenant_id', 1);
        $this->actingAs($user);

        DB::table('extensions')->insert([
            [
                'id' => 1,
                'slug' => 'chatbot',
                'name' => 'Chatbot',
                'category' => 'communication',
                'icon' => 'fa-robot',
                'icon_bg_color' => '#2563eb',
                'status' => 'inactive',
                'sort_order' => 20,
                'active_installs_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('tenant_extensions')->insert([
            [
                'tenant_id' => 1,
                'extension_id' => 1,
                'status' => 'active',
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $result = app(DashboardController::class)->index();

        $this->assertInstanceOf(View::class, $result);

        $data = $result->getData();

        $this->assertCount(0, $data['integrationCards']);
        $this->assertCount(0, $data['installedByCategory']);

        $applicationsCard = collect($data['statsCards'])->firstWhere('label', 'Applications actives');
        $this->assertNotNull($applicationsCard);
        $this->assertSame('0', $applicationsCard['value']);
    }

    protected function createSchema(): void
    {
        Schema::connection('sqlite')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('currency')->nullable();
            $table->string('status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role_in_tenant')->nullable();
            $table->boolean('is_tenant_owner')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('extensions', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('icon')->nullable();
            $table->string('icon_bg_color')->nullable();
            $table->string('status')->default('active');
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('active_installs_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('tenant_extensions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('extension_id');
            $table->string('status')->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('number')->nullable();
            $table->string('status')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_due', 12, 2)->default(0);
            $table->date('issue_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency')->nullable();
            $table->date('payment_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('stock_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('name')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('min_stock', 12, 4)->default(0);
            $table->string('status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('article_id')->nullable();
            $table->string('direction')->nullable();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->string('unit')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('stock_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('number')->nullable();
            $table->string('status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('name')->nullable();
            $table->string('status')->nullable();
            $table->unsignedInteger('progress')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->nullable();
            $table->string('priority')->nullable();
            $table->date('due_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('project_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    protected function seedTenants(): void
    {
        DB::table('tenants')->insert([
            ['id' => 1, 'name' => 'Tenant Base', 'slug' => 'tenant-base', 'currency' => 'EUR', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Tenant Session', 'slug' => 'tenant-session', 'currency' => 'EUR', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function createUser(int $baseTenantId): User
    {
        return User::query()->create([
            'name' => 'Dashboard Tester',
            'email' => 'dashboard@example.test',
            'password' => bcrypt('secret'),
            'tenant_id' => $baseTenantId,
            'role_in_tenant' => 'owner',
            'is_tenant_owner' => true,
            'email_verified_at' => now(),
        ]);
    }

    protected function markOnboardingCompleted(int $tenantId): void
    {
        DB::table('tenant_settings')->insert([
            'tenant_id' => $tenantId,
            'key' => 'onboarding_completed_at',
            'value' => now()->toDateTimeString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function seedExtensionsForTenant(int $tenantId, array $slugs): void
    {
        $definitions = [
            'clients' => ['name' => 'Clients CRM', 'category' => 'productivity', 'icon' => 'fa-users', 'icon_bg_color' => '#2563eb', 'sort_order' => 10],
            'invoice' => ['name' => 'Facturation', 'category' => 'finance', 'icon' => 'fa-file-invoice', 'icon_bg_color' => '#7c3aed', 'sort_order' => 20],
            'stock' => ['name' => 'Stock', 'category' => 'productivity', 'icon' => 'fa-boxes-stacked', 'icon_bg_color' => '#0891b2', 'sort_order' => 30],
            'projects' => ['name' => 'Gestion Projets', 'category' => 'productivity', 'icon' => 'fa-diagram-project', 'icon_bg_color' => '#0ea5e9', 'sort_order' => 40],
        ];

        $extensionRows = [];
        $tenantRows = [];
        $id = 1;

        foreach ($slugs as $slug) {
            $definition = $definitions[$slug];

            $extensionRows[] = [
                'id' => $id,
                'slug' => $slug,
                'name' => $definition['name'],
                'category' => $definition['category'],
                'icon' => $definition['icon'],
                'icon_bg_color' => $definition['icon_bg_color'],
                'status' => 'active',
                'sort_order' => $definition['sort_order'],
                'active_installs_count' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $tenantRows[] = [
                'tenant_id' => $tenantId,
                'extension_id' => $id,
                'status' => 'active',
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $id++;
        }

        DB::table('extensions')->insert($extensionRows);
        DB::table('tenant_extensions')->insert($tenantRows);
    }
}
