<?php

namespace Vendor\User\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\User\Models\UserInvitation;

class UserInvited implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public UserInvitation $invitation)
    {
    }

    public function automationSourceEvent(): string
    {
        return 'user_invited';
    }

    public function automationTenantId(): int
    {
        return (int) $this->invitation->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->invitation->invited_by ? (int) $this->invitation->invited_by : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->invitation::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->invitation->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->invitation->loadMissing(['tenant', 'invitedBy', 'role', 'invitedUser']);
    }

    public function automationContext(): array
    {
        $invitation = $this->automationSource();
        $acceptUrl = Route::has('users.accept')
            ? route('users.accept', (string) $invitation->token)
            : null;

        return [
            'invitation' => [
                'id' => (int) $invitation->id,
                'email' => (string) $invitation->email,
                'role_id' => $invitation->role_id ? (int) $invitation->role_id : null,
                'role_in_tenant' => (string) ($invitation->role_in_tenant ?? ''),
                'status' => (string) $invitation->status,
                'expires_at' => optional($invitation->expires_at)?->toIso8601String(),
                'invited_by' => $invitation->invited_by ? (int) $invitation->invited_by : null,
                'existing_user_id' => $invitation->user_id ? (int) $invitation->user_id : null,
                'accept_url' => $acceptUrl,
                'tenant_name' => (string) ($invitation->tenant?->name ?? ''),
            ],
        ];
    }
}
