<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Contracts;

interface Hookable
{
    public function registerHooks(): void;
}
