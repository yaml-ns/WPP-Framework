<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Security;

final class Sanitizer
{
    public function text(mixed $value): string
    {
        return sanitize_text_field((string) wp_unslash($value));
    }

    public function textarea(mixed $value): string
    {
        return sanitize_textarea_field((string) wp_unslash($value));
    }

    public function int(mixed $value): int
    {
        return absint($value);
    }

    public function email(mixed $value): string
    {
        return sanitize_email((string) wp_unslash($value));
    }

    public function url(mixed $value): string
    {
        return esc_url_raw((string) wp_unslash($value));
    }

    public function allowedValue(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
