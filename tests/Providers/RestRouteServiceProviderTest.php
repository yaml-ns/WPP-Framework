<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Providers;

use YamlNs\WppFramework\Http\RestRouter;
use YamlNs\WppFramework\Providers\RestRouteServiceProvider;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class RestRouteServiceProviderTest extends TestCase
{
    public function test_boot_loads_route_files_on_rest_api_init(): void
    {
        $this->container->singleton(RestRouter::class, fn () => new RestRouter($this->container));

        $provider = new RestRouteServiceProvider($this->container, [
            'files' => [
                dirname(__DIR__) . '/fixtures/routes/api.php',
            ],
        ]);

        $provider->boot();
        do_action('rest_api_init');

        $this->container->get(RestRouter::class)->register();

        $this->assertSame('test/v1', WordPressState::$registeredRestRoutes[0]['namespace']);
        $this->assertSame('/fixture', WordPressState::$registeredRestRoutes[0]['route']);
    }
}
