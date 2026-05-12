<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Shortcodes;

use YamlNs\WppFramework\View\ViewRenderer;

abstract class BaseShortcode
{
    public function __construct(protected readonly ViewRenderer $viewRenderer)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = []): string
    {
        return $this->viewRenderer->render($template, $data);
    }
}
