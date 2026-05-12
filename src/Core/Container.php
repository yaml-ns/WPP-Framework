<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use WP_REST_Request;
use YamlNs\WppFramework\Http\Requests\FormRequest;

final class Container
{
    /**
     * Transient factories: new instance on each get().
     *
     * @var array<string, string|callable>
     */
    private array $bindings = [];

    /**
     * Singleton factories: resolved once, then cached.
     *
     * @var array<string, string|callable>
     */
    private array $singletons = [];

    /**
     * Already resolved instances: singletons and explicit instance() values.
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * @var array<string, array<int, string>>
     */
    private array $tags = [];

    /**
     * @var array<string, array<string, string|callable>>
     */
    private array $contextual = [];

    /**
     * Current resolution stack, used to detect circular dependencies.
     *
     * @var array<string, true>
     */
    private array $resolving = [];

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Transient binding: new instance on each get().
     */
    public function bind(string $abstract, string|callable|null $concrete = null): void
    {
        unset($this->instances[$abstract]); // invalidate any previous cached value
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    /**
     * Singleton binding: same instance for every get().
     */
    public function singleton(string $abstract, string|callable|null $concrete = null): void
    {
        unset($this->instances[$abstract]);
        $this->singletons[$abstract] = $concrete ?? $abstract;
    }

    /**
     * Register an already built object as a singleton.
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * @param string|array<int, string> $abstracts
     */
    public function tag(string|array $abstracts, string $tag): void
    {
        foreach ((array) $abstracts as $abstract) {
            $abstract = $this->resolveAlias($abstract);

            if (!in_array($abstract, $this->tags[$tag] ?? [], true)) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    /**
     * @return array<int, object>
     */
    public function tagged(string $tag): array
    {
        return array_map(fn (string $abstract): object => $this->get($abstract), $this->tags[$tag] ?? []);
    }

    public function when(string $concrete): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    public function addContextualBinding(string $concrete, string $abstract, string|callable $implementation): void
    {
        $this->contextual[$concrete][$this->resolveAlias($abstract)] = $implementation;
    }

    // -------------------------------------------------------------------------
    // Resolution
    // -------------------------------------------------------------------------

    public function has(string $abstract): bool
    {
        $abstract = $this->resolveAlias($abstract);

        return isset($this->instances[$abstract])
            || isset($this->singletons[$abstract])
            || isset($this->bindings[$abstract])
            || class_exists($abstract);
    }

    public function forget(string $abstract): void
    {
        $this->forgetInstance($abstract);
    }

    public function forgetInstance(string $abstract): void
    {
        $abstract = $this->resolveAlias($abstract);
        unset($this->instances[$abstract]);
    }

    public function forgetBinding(string $abstract): void
    {
        $abstract = $this->resolveAlias($abstract);
        unset($this->instances[$abstract], $this->singletons[$abstract], $this->bindings[$abstract]);
    }

    /**
     * Resolve an instance.
     * - singleton / instance(): same object on each call
     * - bind(): new object on each call
     * - autowiring: new object on each call
     */
    public function get(string $abstract): object
    {
        $abstract = $this->resolveAlias($abstract);

        // 1. Cached instance: resolved singleton or explicit instance().
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // 2. Unresolved singleton: resolve and cache it.
        if (isset($this->singletons[$abstract])) {
            return $this->instances[$abstract] = $this->resolve($this->singletons[$abstract]);
        }

        // 3. Transient binding: resolve without caching.
        if (isset($this->bindings[$abstract])) {
            return $this->resolve($this->bindings[$abstract]);
        }

        // 4. Pure autowiring: new object on each call.
        return $this->build($abstract);
    }

    public function make(string $abstract): object
    {
        return $this->get($abstract);
    }

    /**
     * Call a callable with dependency injection.
     *
     * Explicit parameters can be passed by variable name or by fully qualified
     * class name:
     *
     *   $container->call([$ctrl, 'index'], ['request' => $req]);
     *   $container->call([$ctrl, 'index'], [WP_REST_Request::class => $req]);
     *
     * @param array<string, mixed> $parameters
     */
    public function call(callable $callback, array $parameters = []): mixed
    {
        $reflection = is_array($callback)
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction(Closure::fromCallable($callback));

        $dependencies = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            if (array_key_exists($name, $parameters)) {
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $typeName = $type->getName();

                    if (
                        is_subclass_of($typeName, FormRequest::class)
                        && $parameters[$name] instanceof WP_REST_Request
                    ) {
                        $dependencies[] = new $typeName($parameters[$name]);
                        continue;
                    }
                }

                $dependencies[] = $parameters[$name];
                continue;
            }

            if ($type instanceof \ReflectionUnionType) {
                throw new \RuntimeException(
                    "Union types are not supported for autowiring: \${$name} in callback",
                );
            }

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();

                if (array_key_exists($typeName, $parameters)) {
                    $dependencies[] = $parameters[$typeName];
                    continue;
                }

                if (is_subclass_of($typeName, FormRequest::class) && isset($parameters[WP_REST_Request::class])) {
                    $dependencies[] = new $typeName($parameters[WP_REST_Request::class]);
                    continue;
                }

                $dependencies[] = $this->get($typeName);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new \RuntimeException("Cannot resolve parameter '{$name}' while calling callback.");
        }

        return $callback(...$dependencies);
    }

    /**
     * Build an instance through autowiring without caching it.
     */
    public function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Class {$class} does not exist.");
        }

        if (isset($this->resolving[$class])) {
            $chain = implode(' -> ', array_keys($this->resolving));
            throw new \RuntimeException("Circular dependency detected: {$chain} -> {$class}");
        }

        $this->resolving[$class] = true;

        try {
            $reflection = new ReflectionClass($class);

            if (!$reflection->isInstantiable()) {
                throw new \RuntimeException("Class {$class} is not instantiable.");
            }

            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                return new $class();
            }

            $dependencies = [];

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof \ReflectionUnionType) {
                    throw new \RuntimeException(
                        "Union types are not supported for autowiring: \${$parameter->getName()} in {$class}",
                    );
                }

                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $dependencies[] = $this->resolveDependencyFor($class, $type->getName());
                    continue;
                }

                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new \RuntimeException("Cannot resolve dependency '{$parameter->getName()}' for {$class}");
            }

            return $reflection->newInstanceArgs($dependencies);
        } finally {
            unset($this->resolving[$class]);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function resolve(string|callable $concrete): object
    {
        if (is_callable($concrete)) {
            $result = $concrete($this);

            if (!is_object($result)) {
                throw new \RuntimeException('Container factory must return an object.');
            }

            return $result;
        }

        return $this->build($concrete);
    }

    private function resolveAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    private function resolveDependencyFor(string $class, string $abstract): object
    {
        $abstract = $this->resolveAlias($abstract);

        if (!isset($this->contextual[$class][$abstract])) {
            return $this->get($abstract);
        }

        return $this->resolve($this->contextual[$class][$abstract]);
    }
}
