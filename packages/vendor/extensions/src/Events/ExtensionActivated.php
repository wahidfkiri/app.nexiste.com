<?php

namespace Vendor\Extensions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\Extensions\Models\TenantExtension;

class ExtensionActivated implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public TenantExtension $activation)
    {
    }

    public function automationSourceEvent(): string
    {
        return 'extension_activated';
    }

    public function automationTenantId(): int
    {
        return (int) $this->activation->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->activation->activated_by ? (int) $this->activation->activated_by : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->activation::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->activation->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->activation->loadMissing(['extension']);
    }

    public function automationContext(): array
    {
        $activation = $this->automationSource();
        $extension = $activation->extension;
        $slug = (string) ($extension?->slug ?? '');
        $workspaceUrl = null;

        if ($slug !== '') {
            if (Route::has($slug . '.index')) {
                $workspaceUrl = route($slug . '.index');
            } elseif (Route::has('marketplace.show')) {
                $workspaceUrl = route('marketplace.show', $slug);
            }
        }

        return [
            'extension_activation' => [
                'id' => (int) $activation->id,
                'status' => (string) $activation->status,
                'activated_by' => $activation->activated_by ? (int) $activation->activated_by : null,
                'activated_at' => optional($activation->activated_at)?->toIso8601String(),
                'extension_id' => $extension?->id ? (int) $extension->id : null,
                'extension_slug' => $slug,
                'extension_name' => (string) ($extension?->name ?? ''),
                'extension_category' => (string) ($extension?->category ?? ''),
                'workspace_url' => $workspaceUrl,
            ],
        ];
    }
}
