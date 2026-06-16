<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter les champs d'invitation et statut sur la table users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active','inactive','invited','suspended'])->default('active')->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
            if (!Schema::hasColumn('users', 'invitation_token')) {
                $table->string('invitation_token', 128)->nullable()->unique()->after('last_login_ip');
            }
            if (!Schema::hasColumn('users', 'invitation_sent_at')) {
                $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token');
            }
            if (!Schema::hasColumn('users', 'invitation_accepted_at')) {
                $table->timestamp('invitation_accepted_at')->nullable()->after('invitation_sent_at');
            }
            if (!Schema::hasColumn('users', 'invited_by')) {
                $table->unsignedBigInteger('invited_by')->nullable()->after('invitation_accepted_at');
            }
            if (!Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 100)->nullable()->after('invited_by');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 100)->nullable()->after('job_title');
            }
        });

        // Table des invitations (historique et tokens actifs)
        if (!Schema::hasTable('user_invitations')) {
            Schema::create('user_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
                $table->string('email');
                $table->string('role_in_tenant', 50)->default('user');
                $table->string('token', 128)->unique();
                $table->timestamp('expires_at');
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->string('revoked_reason')->nullable();
                $table->unsignedTinyInteger('resend_count')->default(0);
                $table->timestamp('last_resent_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'email']);
                $table->index(['token']);
                $table->index(['tenant_id', 'role_in_tenant']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitations');

        Schema::table('users', function (Blueprint $table) {
            $cols = [
                'status','avatar','phone','last_login_at','last_login_ip',
                'invitation_token','invitation_sent_at','invitation_accepted_at',
                'invited_by','job_title','department',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};