<?php

namespace Vendor\Automation\Contracts;

interface SuggestionProvider
{
    public function suggest(string $sourceEvent, array $context = []): iterable;
}
