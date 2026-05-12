<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Controllers;

use WP_Error;
use WP_REST_Response;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Http\Validation\ValidationException;

abstract class BaseRestController
{
    protected string $namespace = 'wpp/v1';

    public function __construct(?PluginContext $context = null)
    {
        if ($context !== null) {
            $this->namespace = $context->restNamespace();
        }
    }

    protected function ok(mixed $data = null, int $status = 200): WP_REST_Response
    {
        return new WP_REST_Response($data, $status);
    }

    /**
     * @param array<int, mixed> $items
     */
    protected function paginated(array $items, int $total, int $totalPages, int $status = 200): WP_REST_Response
    {
        $response = $this->ok([
            'items' => $items,
            'total' => $total,
            'total_pages' => $totalPages,
        ], $status);

        $response->header('X-WP-Total', (string) $total);
        $response->header('X-WP-TotalPages', (string) $totalPages);

        return $response;
    }

    protected function created(mixed $data = null): WP_REST_Response
    {
        return $this->ok($data, 201);
    }

    protected function noContent(): WP_REST_Response
    {
        return $this->ok(null, 204);
    }

    protected function deleted(): WP_REST_Response
    {
        return $this->noContent();
    }

    protected function error(string $code, string $message, int $status = 400): WP_Error
    {
        return new WP_Error($code, $message, ['status' => $status]);
    }

    protected function notFound(string $message = 'Resource not found.'): WP_Error
    {
        return $this->error('resource_not_found', $message, 404);
    }

    protected function forbidden(string $message = 'Forbidden.'): WP_Error
    {
        return $this->error('wpp_forbidden', $message, 403);
    }

    protected function authorize(string $ability, mixed ...$arguments): ?WP_Error
    {
        $policy = $this->resolvePolicy();

        if ($policy === null) {
            throw new \RuntimeException(sprintf('No policy configured for %s.', static::class));
        }

        if (!method_exists($policy, $ability)) {
            throw new \RuntimeException(sprintf('Policy %s does not define [%s].', $policy::class, $ability));
        }

        $result = $policy->{$ability}(...$arguments);

        if ($result instanceof WP_Error) {
            return $result;
        }

        return $result === true ? null : $this->forbidden();
    }

    protected function handle(callable $callback): WP_REST_Response|WP_Error
    {
        try {
            return $callback();
        } catch (ValidationException $e) {
            return new WP_Error('wpp_validation_failed', $e->getMessage(), [
                'status' => 422,
                'errors' => $e->errors(),
            ]);
        } catch (\Throwable $e) {
            return $this->error('wpp_error', $e->getMessage(), 400);
        }
    }

    private function resolvePolicy(): ?object
    {
        $reflection = new \ReflectionObject($this);

        if (!$reflection->hasProperty('policy')) {
            return null;
        }

        $property = $reflection->getProperty('policy');

        if (!$property->isInitialized($this)) {
            return null;
        }

        /** @var object|null $policy */
        $policy = $property->getValue($this);

        return is_object($policy) ? $policy : null;
    }
}
