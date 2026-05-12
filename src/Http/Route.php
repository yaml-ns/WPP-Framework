<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Http;

final class Route
{
    /**
     * @param string|string[] $methods
     * @param callable|array{0: string|object, 1: string} $handler
     * @param array<int, string|object> $middleware
     * @param array<string, mixed> $args
     */
    public function __construct(
        public readonly string|array $methods,
        public readonly string $path,
        public readonly mixed $handler,
        public readonly array $middleware = [],
        public readonly array $args = [],
    ) {
    }
}
