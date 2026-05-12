<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Http\Controllers\HealthController;
use YamlNs\WppFramework\Http\RestRouter;

final class RestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // RestRouter en premier : HealthController en depend via autowiring.
        $this->container->singleton(RestRouter::class, fn () => new RestRouter($this->container));

        // Enregistrement explicite pour garantir l'ordre de resolution,
        // independamment de l'ordre d'appel des providers custom.
        $this->container->singleton(
            HealthController::class,
            fn () => new HealthController(
                $this->container->get(RestRouter::class),
                $this->container->get(PluginContext::class)
            )
        );
    }

    public function boot(): void
    {
        add_action('rest_api_init', function (): void {
            $this->container->get(HealthController::class)->registerRoutes();
        }, 9);

        add_action('rest_api_init', function (): void {
            $this->container->get(RestRouter::class)->register();
        }, 100);
    }
}
