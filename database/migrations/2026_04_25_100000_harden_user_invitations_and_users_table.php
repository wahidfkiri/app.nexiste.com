<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->softDeletes()->after('updated_at');
            });
        }

        if (!Schema::hasTable('user_invitations')) {
            return;
        }

        Schema::table('user_invitations', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_invitations', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('tenant_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('user_invitations', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('email')->constrained('roles')->nullOnDelete();
            }

            if (!Schema::hasColumn('user_invitations', 'status')) {
                $table->string('status', 20)->default('pending')->after('expires_at');
            }

            if (!Schema::hasColumn('user_invitations', 'pending_email_key')) {
                $table->string('pending_email_key')->nullable()->after('status');
            }
        });

        $this->backfillInvitationSecurityColumns();

        Schema::table('user_invitations', function (Blueprint $table): void {
            $table->index(['tenant_id', 'status'], 'user_invitations_tenant_status_idx');
            $table->index(['tenant_id', 'role_id'], 'user_invitations_tenant_role_idx');
            $table->unique(['tenant_id', 'pending_email_key'], 'user_invitations_tenant_pending_email_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('user_invitations')) {
            Schema::table('user_invitations', function (Blueprint $table): void {
                $table->dropUnique('user_invitations_tenant_pending_email_unique');
                $table->dropIndex('user_invitations_tenant_status_idx');
                $table->dropIndex('user_invitations_tenant_role_idx');

                if (Schema::hasColumn('user_invitations', 'pending_email_key')) {
                    $table->dropColumn('pending_email_key');
                }

                if (Schema::hasColumn('user_invitations', 'status')) {
                    $table->dropColumn('status');
                }

                if (Schema::hasColumn('user_invitations', 'role_id')) {
                    $table->dropConstrainedForeignId('role_id');
                }

                if (Schema::hasColumn('user_invitations', 'user_id')) {
                    $table->dropConstrainedForeignId('user_id');
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }

    private function backfillInvitationSecurityColumns(): void
    {
        $now = now();
        $rolesByName = Role::query()
            ->pluck('id', 'name')
            ->map(fn ($id) => (int) $id)
            ->all();

        $invitations = DB::table('user_invitations')
            ->orderBy('tenant_id')
            ->orderByRaw('LOWER(email)')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $latestPendingByTenantEmail = [];

        foreach ($invitations as $invitation) {
            $email = mb_strtolower(trim((string) $invitation->email));
            $status = $this->resolveStatus($invitation);
            $key = $invitation->tenant_id . '|' . $email;

            if ($status === 'pending') {
                if (isset($latestPendingByTenantEmail[$key])) {
                    $status = 'revoked';
                } else {
                    $latestPendingByTenantEmail[$key] = (int) $invitation->id;
                }
            }

            DB::table('user_invitations')
                ->where('id', (int) $invitation->id)
                ->update([
                    'email' => $email,
                    'user_id' => DB::table('users')->whereRaw('LOWER(email) = ?', [$email])->value('id'),
                    'role_id' => $rolesByName[(string) ($invitation->role_in_tenant ?? '')] ?? null,
                    'status' => $status,
                    'pending_email_key' => $status === 'pending' ? $email : null,
                    'revoked_at' => $status === 'revoked' && !$invitation->revoked_at ? $now : $invitation->revoked_at,
                    'revoked_reason' => $status === 'revoked' && !$invitation->revoked_reason
                        ? 'Invitation invalidee lors de la mise a niveau de securite.'
                        : $invitation->revoked_reason,
                ]);
        }
    }

    private function resolveStatus(object $invitation): string
    {
        if (!empty($invitation->accepted_at)) {
            return 'accepted';
        }

        if (!empty($invitation->revoked_at)) {
            return 'revoked';
        }

        if (!empty($invitation->expires_at) && strtotime((string) $invitation->expires_at) < time()) {
            return 'expired';
        }

        return 'pending';
    }
};
