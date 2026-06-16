<?php

namespace NexusExtensions\Projects\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Projects\Models\ProjectTask;
use Vendor\Automation\Contracts\AutomationContextEvent;

class ProjectTaskCreated implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Project $project,
        public ProjectTask $task,
        public array $meta = []
    ) {
    }

    public function automationSourceEvent(): string
    {
        return 'project_task_created';
    }

    public function automationTenantId(): int
    {
        return (int) $this->task->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->task->created_by ? (int) $this->task->created_by : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->task::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->task->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->task;
    }

    public function automationContext(): array
    {
        $project = $this->project->loadMissing(['client', 'owner']);
        $task = $this->task->loadMissing(['assignee', 'creator', 'client']);
        $projectMetadata = is_array($project->metadata) ? $project->metadata : [];
        $taskMetadata = is_array($task->metadata) ? $task->metadata : [];

        return [
            'project' => [
                'id' => (int) $project->id,
                'name' => (string) $project->name,
                'status' => (string) $project->status,
                'client_id' => $project->client_id ? (int) $project->client_id : null,
                'client_name' => (string) optional($project->client)->company_name,
                'owner_id' => $project->owner_id ? (int) $project->owner_id : null,
                'has_drive_folder' => !empty($projectMetadata['drive_folder_id']) || !empty($projectMetadata['drive_folder']) || !empty($projectMetadata['dropbox_folder_id']) || !empty($projectMetadata['dropbox_folder']),
                'has_team_channel' => !empty($projectMetadata['chatbot_room']) || !empty($projectMetadata['slack_channel']),
            ],
            'task' => [
                'id' => (int) $task->id,
                'title' => (string) $task->title,
                'status' => (string) $task->status,
                'priority' => (string) ($task->priority ?? ''),
                'project_id' => (int) $task->project_id,
                'client_id' => $task->client_id ? (int) $task->client_id : null,
                'client_name' => (string) optional($task->client)->company_name,
                'assigned_to' => $task->assigned_to ? (int) $task->assigned_to : null,
                'start_date' => optional($task->start_date)?->toDateString(),
                'due_date' => optional($task->due_date)?->toDateString(),
                'calendar_synced' => !empty($taskMetadata['google_calendar']['event_id']),
            ],
            'meta' => $this->meta,
        ];
    }
}
