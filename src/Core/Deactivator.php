<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Core;

use YamlNs\WppFramework\Providers\CapabilityServiceProvider;
use YamlNs\WppFramework\Providers\CronServiceProvider;

final class Deactivator
{
    /**
     * @param array<string, mixed> $config
     */
    public static function deactivate(array $config = []): void
    {
        if (isset($config['cron'])) {
            CronServiceProvider::deactivate($config['cron']);
        }

        if (isset($config['capabilities'])) {
            CapabilityServiceProvider::deactivate($config['capabilities']);
        }

        flush_rewrite_rules();
    }
}
