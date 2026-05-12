<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Http;

final class PostInput
{
    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $source = wp_unslash($_POST);

        return is_array($source) ? $source : [];
    }
}
