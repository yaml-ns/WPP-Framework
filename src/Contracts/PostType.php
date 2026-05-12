<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Contracts;

interface PostType
{
    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function args(): array;
}
