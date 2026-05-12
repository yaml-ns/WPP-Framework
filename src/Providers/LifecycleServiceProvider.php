<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Core\PluginContext;

final class LifecycleServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     option?: string,
     *     migrations?: array<string, callable>
     * } $lifecycle
     */
    public function __construct(Container $container, array $lifecycle = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        // Lifecycle migrations are executed during plugin activation, not on
        // every request. The provider remains for backward-compatible config.
    }

    /**
     * @param array{
     *     option?: string,
     *     migrations?: array<string, callable>
     * } $lifecycle
     */
    public static function activate(Container $container, array $lifecycle = []): void
    {
        $context = $container->get(PluginContext::class);
        $option = (string) ($lifecycle['option'] ?? $context->slug() . '_version');
        $installed = (string) get_option($option, '0.0.0');

        if (version_compare($installed, $context->version(), '>=')) {
            return;
        }

        $migrations = $lifecycle['migrations'] ?? [];
        ksort($migrations, SORT_NATURAL);

        foreach ($migrations as $version => $migration) {
            if (version_compare($installed, (string) $version, '<') && version_compare((string) $version, $context->version(), '<=')) {
                $container->call($migration, [
                    'container' => $container,
                    Container::class => $container,
                ]);
            }
        }

        update_option($option, $context->version());
    }
}
