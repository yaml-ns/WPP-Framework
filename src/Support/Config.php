<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Support;

final class Config
{
    public function option(string $key, mixed $default = null): mixed
    {
        $value = get_option($key, $default);
        return $value === false ? $default : $value;
    }

    public function updateOption(string $key, mixed $value): bool
    {
        return update_option($key, $value);
    }
}
