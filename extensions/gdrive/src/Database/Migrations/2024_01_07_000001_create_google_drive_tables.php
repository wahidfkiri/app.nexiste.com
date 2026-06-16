<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tokens OAuth2 par tenant ────────────────────────────────────────
        Schema::create('google_drive_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                  ->unique()                              // 1 token par tenant
                  ->constrained('tenants')
                  ->onDelete('cascade');
            $table->foreignId('connected_by')            // Qui a fait la connexion
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Tokens chiffrés (Eloquent encrypted cast)
            $table->text('access_token');                // Encrypted
            $table->text('refresh_token')->nullable();   // Encrypted
            $table->timestamp('token_expires_at')->nullable();

            // Infos compte Google
            $table->string('google_account_id', 50)->nullable();
            $table->string('google_email', 255)->nullable();
            $table->string('google_name', 255)->nullable();
            $table->string('google_avatar_url', 500)->nullable();
            $table->string('drive_root_folder_id', 100)->nullable();  // Dossier racine CRM
            $table->decimal('drive_quota_total_gb', 8, 2)->nullable();
            $table->decimal('drive_quota_used_gb', 8, 2)->nullable();

            // Statut
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        // ── Fichiers indexés (cache local des métadonnées Drive) ────────────
        Schema::create('google_drive_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Identifiants Google Drive
            $table->string('drive_id', 100);             // ID fichier dans l'API Google
            $table->string('parent_drive_id', 100)->nullable();

            // Métadonnées
            $table->string('name', 500);
            $table->string('mime_type', 200)->nullable();
            $table->boolean('is_folder')->default(false);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('web_view_link', 1000)->nullable();
            $table->string('web_content_link', 1000)->nullable();
            $table->string('thumbnail_link', 1000)->nullable();
            $table->string('icon_link', 500)->nullable();

            // Partage
            $table->boolean('is_shared')->default(false);
            $table->string('shared_with', 255)->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('drive_created_at')->nullable();
            $table->timestamp('drive_modified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->unique(['tenant_id', 'drive_id']);
            $table->index(['tenant_id', 'parent_drive_id']);
            $table->index(['tenant_id', 'is_folder']);
            $table->index(['tenant_id', 'mime_type']);
        });

        // ── Logs d'activité Drive ───────────────────────────────────────────
        Schema::create('google_drive_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('drive_file_id', 100)->nullable();
            $table->string('file_name', 500)->nullable();
            $table->string('action', 50);                // upload, download, rename, delete, move, create_folder, share
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'action', 'created_at']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_activity_logs');
        Schema::dropIfExists('google_drive_files');
        Schema::dropIfExists('google_drive_tokens');
    }
};