<?php

namespace NexusExtensions\NotionWorkspace\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\NotionWorkspace\Models\NotionPageLink;
use NexusExtensions\NotionWorkspace\Models\NotionWorkspaceToken;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

trait InteractsWithNotionWorkspace
{
    protected function authorizePermission(string $permission): void
    {
        if ($this->isTenantAdmin() || $this->userHasPermission($permission)) {
            return;
        }

        if ($permission === 'notion.view' && $this->userHasPermission('notion.create')) {
            return;
        }

        abort(403, __('notion-workspace::messages.errors.permission_insufficient', ['permission' => $permission]));
    }

    protected function userHasPermission(string $permission): bool
    {
        $user = auth()->user();
        $tenantId = (int) ($user->tenant_id ?? 0);

        if (method_exists($user, 'hasTenantPermission')) {
            try {
                return $user->hasTenantPermission($permission, $tenantId);
            } catch (\Throwable) {
                return false;
            }
        }

        return $user->can($permission);
    }

    protected function isTenantAdmin(): bool
    {
        return in_array((string) auth()->user()->role_in_tenant, ['owner', 'admin'], true)
            || (bool) auth()->user()->is_tenant_owner;
    }

    protected function tenantId(): int
    {
        return (int) auth()->user()->tenant_id;
    }

    protected function oauthConfigured(): bool
    {
        return trim((string) config('notion-workspace.oauth.client_id')) !== ''
            && trim((string) config('notion-workspace.oauth.client_secret')) !== '';
    }

    protected function isStorageReady(): bool
    {
        return Schema::hasTable('notion_workspace_tokens')
            && Schema::hasTable('notion_page_links');
    }

    protected function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            abort(500, __('notion-workspace::messages.errors.storage_missing'));
        }
    }

    protected function ensureExtensionActivated(int $tenantId): void
    {
        $this->assertStorageReady();

        if (!$this->isExtensionActive($tenantId)) {
            abort(422, __('notion-workspace::messages.errors.extension_inactive'));
        }
    }

    protected function isExtensionActive(int $tenantId): bool
    {
        $slug = (string) config('notion-workspace.slug', 'notion-workspace');
        $extension = Extension::query()->where('slug', $slug)->first();
        if (!$extension) {
            return false;
        }

        return TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', (int) $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();
    }

    protected function connectedToken(int $tenantId): ?NotionWorkspaceToken
    {
        return NotionWorkspaceToken::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    protected function linkedPagesQuery(int $tenantId)
    {
        $query = NotionPageLink::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('updated_at');

        $relations = ['linkedBy:id,name'];
        if (class_exists(\Vendor\Client\Models\Client::class) && Schema::hasTable('clients')) {
            $relations[] = 'client:id,company_name';
        }
        if (class_exists(\NexusExtensions\Projects\Models\Project::class) && Schema::hasTable('projects')) {
            $relations[] = 'project:id,name';
        }

        return $query->with($relations);
    }

    protected function clientsCollection(): Collection
    {
        if (!class_exists(\Vendor\Client\Models\Client::class) || !Schema::hasTable('clients')) {
            return new Collection();
        }

        return \Vendor\Client\Models\Client::query()
            ->where('tenant_id', $this->tenantId())
            ->orderBy('company_name')
            ->get(['id', 'company_name']);
    }

    protected function projectsCollection(): Collection
    {
        if (!class_exists(\NexusExtensions\Projects\Models\Project::class) || !Schema::hasTable('projects')) {
            return new Collection();
        }

        return \NexusExtensions\Projects\Models\Project::query()
            ->where('tenant_id', $this->tenantId())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function assertTenantClient(?int $clientId): ?object
    {
        if (!$clientId) {
            return null;
        }

        if (!class_exists(\Vendor\Client\Models\Client::class) || !Schema::hasTable('clients')) {
            abort(422, __('notion-workspace::messages.errors.clients_module_missing'));
        }

        $client = \Vendor\Client\Models\Client::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $clientId)
            ->first();

        abort_if(!$client, 422, __('notion-workspace::messages.errors.client_invalid'));

        return $client;
    }

    protected function assertTenantProject(?int $projectId): ?object
    {
        if (!$projectId) {
            return null;
        }

        if (!class_exists(\NexusExtensions\Projects\Models\Project::class) || !Schema::hasTable('projects')) {
            abort(422, __('notion-workspace::messages.errors.projects_module_missing'));
        }

        $project = \NexusExtensions\Projects\Models\Project::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $projectId)
            ->first();

        abort_if(!$project, 422, __('notion-workspace::messages.errors.project_invalid'));

        return $project;
    }
}
