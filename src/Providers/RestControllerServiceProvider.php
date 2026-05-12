<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Contracts\RestController;
use YamlNs\WppFramework\Core\Container;

final class RestControllerServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     controllers?: array<int, class-string<RestController>|RestController>
     * } $rest
     */
    public function __construct(Container $container, private readonly array $rest = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        add_action('rest_api_init', function (): void {
            foreach ($this->rest['controllers'] ?? [] as $controller) {
                $this->resolveController($controller)->registerRoutes();
            }
        }, 10);
    }

    private function resolveController(string|RestController $controller): RestController
    {
        if ($controller instanceof RestController) {
            return $controller;
        }

        $instance = $this->container->get($controller);

        if (!$instance instanceof RestController) {
            throw new \RuntimeException(sprintf(
                'REST controller [%s] must implement %s.',
                $controller,
                RestController::class,
            ));
        }

        return $instance;
    }
}
