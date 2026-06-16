<?php

namespace NexusExtensions\Projects\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use NexusExtensions\Projects\Models\Project;
use Vendor\Automation\Contracts\AutomationContextEvent;

class ProjectCreated implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Project $project,
        public array $meta = []
    ) {
    }

    public function automationSourceEvent(): string
    {
        return 'project_created';
    }

    public function automationTenantId(): int
    {
        return (int) $this->project->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->project->owner_id ? (int) $this->project->owner_id : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->project::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->project->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->project;
    }

    public function automationContext(): array
    {
        return [
            'project' => [
                'id' => (int) $this->project->id,
                'name' => (string) $this->project->name,
                'status' => (string) $this->project->status,
                'priority' => (string) $this->project->priority,
                'client_id' => $this->project->client_id ? (int) $this->project->client_id : null,
                'client_name' => (string) optional($this->project->client)->company_name,
                'owner_id' => $this->project->owner_id ? (int) $this->project->owner_id : null,
                'start_date' => optional($this->project->start_date)?->toDateString(),
                'due_date' => optional($this->project->due_date)?->toDateString(),
            ],
            'meta' => $this->meta,
        ];
    }
}
