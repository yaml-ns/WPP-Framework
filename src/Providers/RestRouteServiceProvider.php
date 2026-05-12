<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Http\RestRouter;

final class RestRouteServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     files?: array<int|string, string>
     * } $routes
     */
    public function __construct(Container $container, private readonly array $routes = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        add_action('rest_api_init', function (): void {
            foreach ($this->routes['files'] ?? [] as $path) {
                $this->loadRouteFile((string) $path);
            }
        }, 10);
    }

    private function loadRouteFile(string $path): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Route file not found: {$path}");
        }

        $router = $this->container->get(RestRouter::class);
        $context = $this->container->get(PluginContext::class);
        $container = $this->container;

        $result = (static function (string $__path, RestRouter $router, PluginContext $context, Container $container): mixed {
            return require $__path;
        })($path, $router, $context, $container);

        if ($result === null || $result === true || $result === 1) {
            return;
        }

        if (!is_callable($result)) {
            throw new \RuntimeException("Route file [{$path}] must return a callable or null.");
        }

        $this->container->call($result, [
            'router' => $router,
            RestRouter::class => $router,
            'context' => $context,
            PluginContext::class => $context,
            'container' => $container,
            Container::class => $container,
        ]);
    }
}
