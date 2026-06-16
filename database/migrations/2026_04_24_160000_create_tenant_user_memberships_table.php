<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_user_memberships')) {
            Schema::create('tenant_user_memberships', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('role_in_tenant', 50)->default('user');
                $table->boolean('is_tenant_owner')->default(false);
                $table->string('status', 20)->default('active');
                $table->unsignedBigInteger('invited_by')->nullable();
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id']);
                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'role_in_tenant']);
                $table->index(['user_id', 'status']);
            });
        }

        if (Schema::hasTable('users')) {
            $now = now();
            $rows = DB::table('users')
                ->select(['id', 'tenant_id', 'role_in_tenant', 'is_tenant_owner', 'created_at', 'updated_at'])
                ->whereNotNull('tenant_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('tenant_user_memberships')->updateOrInsert(
                    [
                        'user_id' => (int) $row->id,
                        'tenant_id' => (int) $row->tenant_id,
                    ],
                    [
                        'role_in_tenant' => (string) ($row->role_in_tenant ?: 'user'),
                        'is_tenant_owner' => (bool) $row->is_tenant_owner,
                        'status' => 'active',
                        'joined_at' => $row->created_at ?: $now,
                        'updated_at' => $row->updated_at ?: $now,
                        'created_at' => $row->created_at ?: $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user_memberships');
    }
};
