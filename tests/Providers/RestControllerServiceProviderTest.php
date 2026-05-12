<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Providers;

use YamlNs\WppFramework\Contracts\RestController;
use YamlNs\WppFramework\Providers\RestControllerServiceProvider;
use YamlNs\WppFramework\Tests\Support\TestCase;

final class DummyRestController implements RestController
{
    public int $registered = 0;

    public function registerRoutes(): void
    {
        $this->registered++;
    }
}

final class InvalidRestController {}

final class RestControllerServiceProviderTest extends TestCase
{
    public function test_boot_resolves_and_registers_controllers_on_rest_api_init(): void
    {
        $controller = new DummyRestController();
        $this->container->instance(DummyRestController::class, $controller);

        $provider = new RestControllerServiceProvider($this->container, [
            'controllers' => [
                DummyRestController::class,
            ],
        ]);

        $provider->boot();
        do_action('rest_api_init');

        $this->assertSame(1, $controller->registered);
    }

    public function test_invalid_controller_throws_clear_exception(): void
    {
        $provider = new RestControllerServiceProvider($this->container, [
            'controllers' => [
                InvalidRestController::class,
            ],
        ]);

        $provider->boot();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must implement');

        do_action('rest_api_init');
    }
}
