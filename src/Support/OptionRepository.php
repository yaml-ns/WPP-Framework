<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Support;

final class OptionRepository
{
    public function get(string $key, mixed $default = null): mixed
    {
        return get_option($key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    public function update(string $key, mixed $value): bool
    {
        return update_option($key, $value);
    }
}
