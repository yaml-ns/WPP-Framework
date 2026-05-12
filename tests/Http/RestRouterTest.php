<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Http;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use WP_Error;
use WP_REST_Request;
use YamlNs\WppFramework\Contracts\Middleware;
use YamlNs\WppFramework\Http\Requests\FormRequest;
use YamlNs\WppFramework\Http\RestRouter;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class ArrayLogger extends AbstractLogger
{
    /** @var array<int, array{level: mixed, message: string|\Stringable, context: array<string, mixed>}> */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = compact('level', 'message', 'context');
    }
}

final class PassingMiddleware implements Middleware
{
    public function handle(WP_REST_Request $request): bool|WP_Error
    {
        WordPressState::$options['passing_middleware_ran'] = true;

        return true;
    }
}

final class BlockingMiddleware implements Middleware
{
    public function handle(WP_REST_Request $request): bool|WP_Error
    {
        return new WP_Error('blocked', 'Blocked.', ['status' => 403]);
    }
}

final class SampleFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}

final class SampleResourceController
{
    public function index(): array
    {
        return [];
    }
}

final class RestRouterTest extends TestCase
{
    public function test_dispatch_converts_handler_exception_to_wp_error_and_logs_it(): void
    {
        $logger = new ArrayLogger();
        $this->container->instance(LoggerInterface::class, $logger);

        $router = new RestRouter($this->container);
        $router->get('test/v1', '/boom', static function (): never {
            throw new \RuntimeException('boom');
        });

        $router->register();

        $callback = WordPressState::$registeredRestRoutes[0]['args']['callback'];
        $result = $callback(new \WP_REST_Request());

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('wpp_rest_exception', $result->code);
        $this->assertSame(['status' => 500], $result->data);
        $this->assertSame('error', $logger->records[0]['level']);
        $this->assertSame('boom', $logger->records[0]['context']['message']);
    }

    public function test_permission_callback_runs_middleware_chain_and_stops_on_error(): void
    {
        $this->container->instance(PassingMiddleware::class, new PassingMiddleware());
        $this->container->instance(BlockingMiddleware::class, new BlockingMiddleware());

        $router = new RestRouter($this->container);
        $router->get('test/v1', '/protected', static fn (): array => [], [
            PassingMiddleware::class,
            BlockingMiddleware::class,
        ]);

        $router->register();

        $permission = WordPressState::$registeredRestRoutes[0]['args']['permission_callback'];
        $result = $permission(new WP_REST_Request());

        $this->assertTrue(WordPressState::$options['passing_middleware_ran']);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('blocked', $result->code);
        $this->assertSame(['status' => 403], $result->data);
    }

    public function test_dispatch_converts_validation_exception_to_422_wp_error(): void
    {
        $router = new RestRouter($this->container);
        $router->post('test/v1', '/submit', static function (SampleFormRequest $request): array {
            return $request->validated();
        });

        $router->register();

        $callback = WordPressState::$registeredRestRoutes[0]['args']['callback'];
        $result = $callback(new WP_REST_Request(['email' => 'not-an-email']));

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('wpp_validation_failed', $result->code);
        $this->assertSame(422, $result->data['status']);
        $this->assertArrayHasKey('email', $result->data['errors']);
    }

    public function test_api_resource_registers_standard_restful_routes_with_context_namespace(): void
    {
        $router = new RestRouter($this->container);

        $router->apiResource('/items', SampleResourceController::class);
        $router->register();

        $this->assertSame([
            ['GET', '/items'],
            ['POST', '/items'],
            ['GET', '/items/(?P<id>\d+)'],
            [['PUT', 'PATCH'], '/items/(?P<id>\d+)'],
            ['DELETE', '/items/(?P<id>\d+)'],
        ], array_map(
            static fn (array $route): array => [$route['args']['methods'], $route['route']],
            WordPressState::$registeredRestRoutes,
        ));

        $this->assertSame('test/v1', WordPressState::$registeredRestRoutes[0]['namespace']);
    }

    public function test_api_resource_accepts_custom_id_pattern(): void
    {
        $router = new RestRouter($this->container);

        $router->apiResource('/items', SampleResourceController::class, args: [
            'id_pattern' => '[a-zA-Z0-9_-]+',
        ]);
        $router->register();

        $this->assertSame('/items/(?P<id>[a-zA-Z0-9_-]+)', WordPressState::$registeredRestRoutes[2]['route']);
        $this->assertSame('string', WordPressState::$registeredRestRoutes[2]['args']['args']['id']['type']);
    }

    public function test_add_after_register_registers_route_immediately(): void
    {
        $router = new RestRouter($this->container);
        $router->register();

        $router->get('test/v1', '/late', static fn (): array => []);

        $this->assertSame('/late', WordPressState::$registeredRestRoutes[0]['route']);
    }
}
