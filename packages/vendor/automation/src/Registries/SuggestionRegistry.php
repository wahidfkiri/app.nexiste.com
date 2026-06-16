<?php

namespace Vendor\Automation\Registries;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Vendor\Automation\Contracts\SuggestionProvider;

class SuggestionRegistry
{
    protected array $providersByEvent = [];

    public function __construct(
        protected Container $container,
        array $providers = []
    ) {
        foreach ($providers as $sourceEvent => $providerClasses) {
            $this->register($sourceEvent, $providerClasses);
        }
    }

    public function register(string|array $sourceEvents, string|array $providerClasses): self
    {
        foreach ((array) $sourceEvents as $sourceEvent) {
            foreach ((array) $providerClasses as $providerClass) {
                $this->providersByEvent[(string) $sourceEvent][] = (string) $providerClass;
            }
        }

        return $this;
    }

    public function providersFor(string $sourceEvent): array
    {
        $providerClasses = array_values(array_unique(array_merge(
            $this->providersByEvent[$sourceEvent] ?? [],
            $this->providersByEvent['*'] ?? []
        )));

        return array_map(function (string $providerClass): SuggestionProvider {
            $provider = $this->container->make($providerClass);

            if (!$provider instanceof SuggestionProvider) {
                throw new InvalidArgumentException(sprintf(
                    'Le provider automation [%s] doit implémenter %s.',
                    $providerClass,
                    SuggestionProvider::class
                ));
            }

            return $provider;
        }, $providerClasses);
    }
}
