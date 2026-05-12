<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Core;

use YamlNs\WppFramework\Providers\CapabilityServiceProvider;
use YamlNs\WppFramework\Providers\LifecycleServiceProvider;
use YamlNs\WppFramework\Providers\PostTypeServiceProvider;
use YamlNs\WppFramework\Providers\TaxonomyServiceProvider;

final class Activator
{
    /**
     * @param array<string, mixed> $config
     */
    public static function activate(Container $container, array $config = []): void
    {
        if (isset($config['post_types'])) {
            PostTypeServiceProvider::activate($container, [
                'post_types' => $config['post_types'],
            ]);
        }

        if (isset($config['taxonomies'])) {
            TaxonomyServiceProvider::activate($container, [
                'taxonomies' => $config['taxonomies'],
            ]);
        }

        if (isset($config['capabilities'])) {
            CapabilityServiceProvider::activate($config['capabilities']);
        }

        if (isset($config['lifecycle'])) {
            LifecycleServiceProvider::activate($container, $config['lifecycle']);
        }

        flush_rewrite_rules();
    }
}
