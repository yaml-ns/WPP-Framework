<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Core;

final class ContextualBindingBuilder
{
    private string $abstract = '';

    public function __construct(
        private readonly Container $container,
        private readonly string $concrete,
    ) {
    }

    public function needs(string $abstract): self
    {
        $this->abstract = $abstract;

        return $this;
    }

    public function give(string|callable $implementation): void
    {
        if ($this->abstract === '') {
            throw new \RuntimeException('Contextual binding requires needs() before give().');
        }

        $this->container->addContextualBinding($this->concrete, $this->abstract, $implementation);
    }
}
