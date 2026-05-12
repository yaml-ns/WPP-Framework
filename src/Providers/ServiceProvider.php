<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;

abstract class ServiceProvider
{
    public function __construct(protected Container $container)
    {
    }

    public function register(): void
    {
    }
    public function boot(): void
    {
    }
}
