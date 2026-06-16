<?php

namespace Vendor\Automation\Contracts;

interface AutomationContextEvent
{
    public function automationSourceEvent(): string;

    public function automationTenantId(): int;

    public function automationUserId(): ?int;

    public function automationSourceType(): ?string;

    public function automationSourceId(): int|string|null;

    public function automationSource(): mixed;

    public function automationContext(): array;
}
