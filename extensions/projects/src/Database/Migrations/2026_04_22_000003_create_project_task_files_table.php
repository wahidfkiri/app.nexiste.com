<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_task_files')) {
            Schema::create('project_task_files', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('project_task_id')->index();
                $table->unsignedBigInteger('uploaded_by')->nullable()->index();

                $table->string('drive_file_id', 120)->index();
                $table->string('name', 500);
                $table->string('mime_type', 180)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('web_view_link', 900)->nullable();
                $table->string('download_link', 900)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'project_task_id', 'drive_file_id'], 'ptf_tenant_task_drive_unique');
                $table->index(['tenant_id', 'project_task_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_files');
    }
};
