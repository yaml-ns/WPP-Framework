<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Contracts;

interface Taxonomy
{
    public function name(): string;

    /**
     * @return string|string[]
     */
    public function objectType(): string|array;

    /**
     * @return array<string, mixed>
     */
    public function args(): array;
}
