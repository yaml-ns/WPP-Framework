<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Security;

final class Escaper
{
    public function html(mixed $value): string
    {
        return esc_html((string) $value);
    }

    public function attr(mixed $value): string
    {
        return esc_attr((string) $value);
    }

    public function url(mixed $value): string
    {
        return esc_url((string) $value);
    }

    public function textarea(mixed $value): string
    {
        return esc_textarea((string) $value);
    }
}
