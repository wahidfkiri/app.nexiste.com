<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tokens OAuth2 par tenant ────────────────────────────────────────
        Schema::create('google_sheets_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                  ->unique()
                  ->constrained('tenants')
                  ->onDelete('cascade');
            $table->foreignId('connected_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Tokens chiffrés
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Infos compte Google
            $table->string('google_account_id', 50)->nullable();
            $table->string('google_email', 255)->nullable();
            $table->string('google_name', 255)->nullable();
            $table->string('google_avatar_url', 500)->nullable();

            // Statut
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'is_active'], 'gsheets_tok_tenant_active_idx');
        });

        // ── Spreadsheets indexées (cache local des métadonnées) ─────────────
        Schema::create('google_sheets_spreadsheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Identifiants Google
            $table->string('spreadsheet_id', 150);
            $table->string('title', 500);
            $table->string('locale', 20)->nullable();
            $table->string('timezone', 80)->nullable();

            // Liens
            $table->string('spreadsheet_url', 1000)->nullable();

            // Partage
            $table->boolean('is_shared')->default(false);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('drive_created_at')->nullable();
            $table->timestamp('drive_modified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'spreadsheet_id'], 'gsheets_ss_tenant_id_unq');
            $table->index(['tenant_id'], 'gsheets_ss_tenant_idx');
        });

        // ── Feuilles (sheets / onglets) ─────────────────────────────────────
        Schema::create('google_sheets_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('spreadsheet_local_id')
                  ->constrained('google_sheets_spreadsheets')
                  ->onDelete('cascade');

            $table->string('spreadsheet_id', 150);
            $table->unsignedInteger('sheet_id');
            $table->string('title', 500);
            $table->unsignedInteger('index')->default(0);
            $table->string('sheet_type', 50)->default('GRID');
            $table->unsignedInteger('row_count')->default(1000);
            $table->unsignedInteger('column_count')->default(26);
            $table->boolean('hidden')->default(false);

            $table->timestamps();

            $table->unique(['tenant_id', 'spreadsheet_id', 'sheet_id'], 'gsheets_sh_tenant_ss_sh_unq');
            $table->index(['tenant_id', 'spreadsheet_id'], 'gsheets_sh_tenant_ss_idx');
        });

        // ── Logs d'activité ─────────────────────────────────────────────────
        Schema::create('google_sheets_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('spreadsheet_id', 150)->nullable();
            $table->string('spreadsheet_title', 500)->nullable();
            $table->string('sheet_title', 500)->nullable();
            $table->string('action', 80);
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'action', 'created_at'], 'gsheets_log_tenant_action_idx');
            $table->index(['tenant_id', 'user_id'], 'gsheets_log_tenant_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_sheets_activity_logs');
        Schema::dropIfExists('google_sheets_sheets');
        Schema::dropIfExists('google_sheets_spreadsheets');
        Schema::dropIfExists('google_sheets_tokens');
    }
};