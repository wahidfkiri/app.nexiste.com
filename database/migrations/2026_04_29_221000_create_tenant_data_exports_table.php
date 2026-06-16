<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_data_exports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('provider', 40);
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedTinyInteger('total_steps')->default(0);
            $table->unsignedTinyInteger('current_step_index')->nullable();
            $table->string('current_step_key', 80)->nullable();
            $table->string('current_step_label', 190)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('workspace_path', 500)->nullable();
            $table->string('local_zip_path', 500)->nullable();
            $table->string('remote_file_id', 255)->nullable();
            $table->string('remote_url', 1200)->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_data_exports');
    }
};
