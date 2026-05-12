<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Http;

use Psr\Log\LoggerInterface;
use YamlNs\WppFramework\Contracts\Middleware;
use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Http\Validation\ValidationException;
use WP_Error;
use WP_REST_Request;

final class RestRouter
{
    /**
     * @var array<string, Route[]>
     */
    private array $routes = [];

    private bool $registered = false;

    public function __construct(private Container $container) {}

    /**
     * @param callable|array{0: string|object, 1: string} $handler
     * @param array<int, string|object> $middleware
     * @param array<string, mixed> $args
     */
    public function get(string $namespace, string $path, mixed $handler, array $middleware = [], array $args = []): void
    {
        $this->add($namespace, 'GET', $path, $handler, $middleware, $args);
    }

    /**
     * @param callable|array{0: string|object, 1: string} $handler
     * @param array<int, string|object> $middleware
     * @param array<string, mixed> $args
     */
    public function post(string $namespace, string $path, mixed $handler, array $middleware = [], array $args = []): void
    {
        $this->add($namespace, 'POST', $path, $handler, $middleware, $args);
    }

    /**
     * @param callable|array{0: string|object, 1: string} $handler
     * @param array<int, string|object> $middleware
     * @param array<string, mixed> $args
     */
    public function put(string $namespace, string $path, mixed $handler, array $middleware = [], array $args = []): void
    {
        $this->add($namespace, 'PUT', $path, $handler, $middleware, $args);
    }

    /**
     * @param callable|array{0: string|object, 1: string} $handler
     * @param array<int, string|object> $middleware
     * @param array<string, mixed> $args
     */
    public function delete(string $namespace, string $path, mixed $handler, array $middleware = [], array $args = []): void
    {
        $this->add($namespace, 'DELETE', $path, $handler, $middleware, $args);
    }

    /**
     * @param callable|array{0: string|object, 1: string} $handler
     * @param array<int, string|object> $middleware
     * @param array<string, mixed> $args
     */
    public function patch(string $namespace, string $path, mixed $handler, array $middleware = [], array $args = []): void
    {
        $this->add($namespace, 'PATCH', $path, $handler, $middleware, $args);
    }

    /**
     * @param class-string $controller
     * @param array<int, string|object> $middleware
     * @param array<string, mixed> $args
     */
    public function apiResource(string $namespaceOrPath, string $pathOrController, ?string $controller = null, array $middleware = [], array $args = []): void
    {
        [$namespace, $path, $controller] = $this->normalizeResourceArguments($namespaceOrPath, $pathOrController, $controller);

        $idPattern = (string) ($args['id_pattern'] ?? '\d+');
        unset($args['id_pattern']);

        $idPath = rtrim($path, '/') . '/(?P<id>' . $idPattern . ')';
        $idArgs = array_merge($args, [
            'id' => [
                'type' => $idPattern === '\d+' ? 'integer' : 'string',
                'required' => true,
            ],
        ]);

        $this->get($namespace, $path, [$controller, 'index'], $middleware, $args);
        $this->post($namespace, $path, [$controller, 'store'], $middleware, $args);
        $this->get($namespace, $idPath, [$controller, 'show'], $middleware, $idArgs);
        $this->add($namespace, ['PUT', 'PATCH'], $idPath, [$controller, 'update'], $middleware, $idArgs);
        $this->delete($namespace, $idPath, [$controller, 'destroy'], $middleware, $idArgs);
    }

    /**
     * @param class-string $controller
     * @param array<int, string|object> $middleware
     * @param array<string, mixed> $args
     */
    public function resource(string $namespaceOrPath, string $pathOrController, ?string $controller = null, array $middleware = [], array $args = []): void
    {
        $this->apiResource($namespaceOrPath, $pathOrController, $controller, $middleware, $args);
    }

    /**
     * @param callable|array{0: string|object, 1: string} $handler
     * @param array<int, string|object> $middleware
     * @param array<string, mixed> $args
     */
    public function add(string $namespace, string|array $methods, string $path, mixed $handler, array $middleware = [], array $args = []): void
    {
        $route = new Route($methods, $path, $handler, $middleware, $args);
        $this->routes[$namespace][] = $route;

        if ($this->registered) {
            $this->registerRoute($namespace, $route);
        }
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        foreach ($this->routes as $namespace => $routes) {
            foreach ($routes as $route) {
                $this->registerRoute($namespace, $route);
            }
        }
    }

    private function registerRoute(string $namespace, Route $route): void
    {
        register_rest_route($namespace, $route->path, [
            'methods' => $route->methods,
            'callback' => fn (WP_REST_Request $request) => $this->dispatch($route, $request),
            'permission_callback' => fn (WP_REST_Request $request) => $this->runMiddleware($route, $request),
            'args' => $route->args,
        ]);
    }

    private function dispatch(Route $route, WP_REST_Request $request): mixed
    {
        try {
            $handler = $route->handler;

            if (is_array($handler) && is_string($handler[0])) {
                $handler[0] = $this->container->get($handler[0]);
            }

            return $this->container->call($handler, [
                'request' => $request,
                WP_REST_Request::class => $request,
            ]);
        } catch (ValidationException $e) {
            return new WP_Error('wpp_validation_failed', $e->getMessage(), [
                'status' => 422,
                'errors' => $e->errors(),
            ]);
        } catch (\Throwable $e) {
            $this->logException($e);

            $message = defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : 'Internal server error.';

            return new WP_Error('wpp_rest_exception', $message, ['status' => 500]);
        }
    }

    private function runMiddleware(Route $route, WP_REST_Request $request): bool|WP_Error
    {
        foreach ($route->middleware as $middleware) {
            if (is_string($middleware)) {
                $middleware = $this->container->get($middleware);
            }

            if (!$middleware instanceof Middleware) {
                return new WP_Error('wpp_invalid_middleware', 'Invalid middleware.', ['status' => 500]);
            }

            $result = $middleware->handle($request);

            if ($result !== true) {
                return $result;
            }
        }

        return true;
    }

    /**
     * @return array{0: string, 1: string, 2: class-string}
     */
    private function normalizeResourceArguments(string $namespaceOrPath, string $pathOrController, ?string $controller): array
    {
        if ($controller !== null) {
            return [$namespaceOrPath, $pathOrController, $controller];
        }

        if (!$this->container->has(PluginContext::class)) {
            throw new \RuntimeException('Resource routes without explicit namespace require PluginContext in the container.');
        }

        /** @var PluginContext $context */
        $context = $this->container->get(PluginContext::class);

        return [$context->restNamespace(), $namespaceOrPath, $pathOrController];
    }

    private function logException(\Throwable $e): void
    {
        if (!$this->container->has(LoggerInterface::class)) {
            return;
        }

        /** @var LoggerInterface $logger */
        $logger = $this->container->get(LoggerInterface::class);
        $logger->error('REST route handler failed: {message}', [
            'message' => $e->getMessage(),
            'exception' => $e,
        ]);
    }
}
