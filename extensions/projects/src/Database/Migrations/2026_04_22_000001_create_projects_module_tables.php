<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->unsignedBigInteger('owner_id')->index();
                $table->string('name', 180);
                $table->string('slug', 220)->nullable();
                $table->text('description')->nullable();
                $table->string('status', 30)->default('planning')->index();
                $table->string('priority', 20)->default('medium');
                $table->date('start_date')->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->decimal('budget', 15, 2)->nullable();
                $table->unsignedTinyInteger('progress')->default(0);
                $table->string('color', 20)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'owner_id']);
                $table->index(['tenant_id', 'client_id']);
                $table->unique(['tenant_id', 'slug']);
            });
        }

        if (!Schema::hasTable('project_members')) {
            Schema::create('project_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('project_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('role', 30)->default('member');
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('invited_by')->nullable();
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'user_id']);
                $table->index(['tenant_id', 'project_id', 'role']);
            });
        }

        if (!Schema::hasTable('project_tasks')) {
            Schema::create('project_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('project_id')->index();
                $table->unsignedBigInteger('parent_task_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->index();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->string('title', 220);
                $table->text('description')->nullable();
                $table->string('status', 30)->default('todo')->index();
                $table->string('priority', 20)->default('medium')->index();
                $table->unsignedInteger('position')->default(0);
                $table->date('start_date')->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->decimal('estimate_hours', 8, 2)->nullable();
                $table->decimal('spent_hours', 8, 2)->nullable();
                $table->json('tags')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'project_id', 'status']);
                $table->index(['tenant_id', 'assigned_to', 'status']);
            });
        }

        if (!Schema::hasTable('project_task_comments')) {
            Schema::create('project_task_comments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('project_task_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->text('comment');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'project_task_id']);
            });
        }

        if (!Schema::hasTable('project_task_checklists')) {
            Schema::create('project_task_checklists', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('project_task_id')->index();
                $table->string('title', 255);
                $table->boolean('is_done')->default(false)->index();
                $table->unsignedInteger('position')->default(0);
                $table->unsignedBigInteger('done_by')->nullable()->index();
                $table->timestamp('done_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'project_task_id', 'is_done']);
            });
        }

        if (!Schema::hasTable('project_activities')) {
            Schema::create('project_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->unsignedBigInteger('project_task_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('event', 80)->index();
                $table->string('description', 255);
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'project_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_activities');
        Schema::dropIfExists('project_task_checklists');
        Schema::dropIfExists('project_task_comments');
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
    }
};
