<?php

namespace Vendor\Automation\Registries;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Vendor\Automation\Contracts\AutomationAction;

class ActionRegistry
{
    protected array $actions = [];

    public function __construct(
        protected Container $container,
        array $actions = []
    ) {
        foreach ($actions as $type => $actionClass) {
            $this->register((string) $type, (string) $actionClass);
        }
    }

    public function register(string $type, string $actionClass): self
    {
        $this->actions[$type] = $actionClass;

        return $this;
    }

    public function has(string $type): bool
    {
        return isset($this->actions[$type]);
    }

    public function resolve(string $type): ?AutomationAction
    {
        $actionClass = $this->actions[$type] ?? null;
        if (!$actionClass) {
            return null;
        }

        $action = $this->container->make($actionClass);
        if (!$action instanceof AutomationAction) {
            throw new InvalidArgumentException(sprintf(
                'L\'action automation [%s] doit implémenter %s.',
                $actionClass,
                AutomationAction::class
            ));
        }

        return $action;
    }
}
