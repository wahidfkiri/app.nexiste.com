<?php

namespace Tests\Feature\Automation;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Services\AutomationReconnectNotificationService;

class AutomationReconnectNotificationServiceTest extends TestCase
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

        if (!Route::has('google-calendar.index')) {
            Route::get('/extensions/google-calendar', fn () => 'ok')->name('google-calendar.index');
        }
    }

    public function test_sync_for_suggestion_creates_pending_reconnect_notification_for_secondary_tenant_member(): void
    {
        $user = $this->createUser(baseTenantId: 1);
        $this->attachMembership($user->id, 2);
        $suggestion = $this->createFailedReconnectSuggestion($user->id, 2);

        app(AutomationReconnectNotificationService::class)->syncForSuggestion($suggestion->fresh());

        $notification = $user->notifications()->latest('created_at')->first();

        $this->assertNotNull($notification);
        $this->assertSame('automation_suggestion_pending', $notification->data['notification_kind']);
        $this->assertSame('pending_reconnect', $notification->data['resume_state']);
        $this->assertSame('google-calendar', $notification->data['provider_slug']);
        $this->assertStringContainsString('automation_resume=1', $notification->data['action_url']);
        $this->assertStringContainsString('automation_resume_state=pending_reconnect', $notification->data['action_url']);
    }

    public function test_notify_for_provider_updates_notification_as_reconnected(): void
    {
        $user = $this->createUser(baseTenantId: 1);
        $this->attachMembership($user->id, 2);
        $suggestion = $this->createFailedReconnectSuggestion($user->id, 2);
        $service = app(AutomationReconnectNotificationService::class);

        $service->syncForSuggestion($suggestion->fresh());
        $count = $service->notifyForProvider(2, $user->id, 'google-calendar', '/extensions/google-calendar');

        $notification = $user->notifications()->latest('updated_at')->first();

        $this->assertSame(1, $count);
        $this->assertNotNull($notification);
        $this->assertSame('reconnected', $notification->data['resume_state']);
        $this->assertStringContainsString('automation_resume_state=reconnected', $notification->data['action_url']);
    }

    protected function createSchema(): void
    {
        Schema::connection('sqlite')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
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

        Schema::connection('sqlite')->create('tenant_user_memberships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('active');
            $table->string('role_in_tenant')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->boolean('is_tenant_owner')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('automation_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('source_event');
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->string('type');
            $table->string('label');
            $table->decimal('confidence', 5, 2)->default(0.50);
            $table->text('payload')->nullable();
            $table->text('meta')->nullable();
            $table->string('status')->default('pending');
            $table->string('dedupe_key')->nullable();
            $table->string('pending_dedupe_key')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('automation_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('triggered_by_suggestion_id')->nullable();
            $table->string('event_name');
            $table->string('action_type');
            $table->text('payload')->nullable();
            $table->text('response')->nullable();
            $table->string('status')->default('queued');
            $table->string('idempotency_key')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    protected function createUser(int $baseTenantId): User
    {
        DB::table('tenants')->insert([
            ['id' => 1, 'name' => 'Tenant 1', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Tenant 2', 'created_at' => now(), 'updated_at' => now()],
        ]);

        return User::query()->create([
            'name' => 'Automation User',
            'email' => 'automation@example.test',
            'password' => bcrypt('secret'),
            'tenant_id' => $baseTenantId,
            'role_in_tenant' => 'owner',
            'is_tenant_owner' => true,
        ]);
    }

    protected function attachMembership(int $userId, int $tenantId): void
    {
        DB::table('tenant_user_memberships')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'status' => 'active',
            'role_in_tenant' => 'manager',
            'is_tenant_owner' => false,
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createFailedReconnectSuggestion(int $userId, int $tenantId): AutomationSuggestion
    {
        $suggestion = AutomationSuggestion::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'source_event' => 'client_created',
            'source_type' => 'Vendor\\Client\\Models\\Client',
            'source_id' => '99',
            'type' => 'create_followup_meeting',
            'label' => 'Planifier un rendez-vous',
            'confidence' => 0.90,
            'payload' => [],
            'meta' => ['integration' => 'google-calendar'],
            'status' => 'pending',
            'dedupe_key' => 'abc',
            'pending_dedupe_key' => '2:abc',
            'expires_at' => now()->addDay(),
        ]);

        DB::table('automation_events')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'triggered_by_suggestion_id' => $suggestion->id,
            'event_name' => 'automation.execute.create_followup_meeting',
            'action_type' => 'create_followup_meeting',
            'status' => 'failed',
            'last_error' => 'Google Calendar n est plus connecte pour ce tenant. Reconnectez Google Calendar puis relancez cette automation.',
            'attempts' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $suggestion->fresh();
    }
}
