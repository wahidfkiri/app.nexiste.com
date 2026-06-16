<?php

namespace Vendor\Automation\Data;

use Carbon\CarbonInterface;

class SuggestionDefinition
{
    public function __construct(
        public string $type,
        public string $label,
        public float $confidence = 0.5,
        public array $payload = [],
        public array $meta = [],
        public ?string $dedupeKey = null,
        public ?CarbonInterface $expiresAt = null,
    ) {
    }

    public static function make(
        string $type,
        string $label,
        float $confidence = 0.5,
        array $payload = [],
        array $meta = []
    ): self {
        return new self($type, $label, $confidence, $payload, $meta);
    }

    public function withDedupeKey(?string $dedupeKey): self
    {
        $clone = clone $this;
        $clone->dedupeKey = $dedupeKey;

        return $clone;
    }

    public function withExpiration(?CarbonInterface $expiresAt): self
    {
        $clone = clone $this;
        $clone->expiresAt = $expiresAt;

        return $clone;
    }
}
