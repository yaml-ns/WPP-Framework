<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Admin;

use YamlNs\WppFramework\Core\PluginContext;

abstract class BaseAdminPage
{
    public function __construct(protected PluginContext $context)
    {
    }

    abstract public function register(): void;

    protected function renderTemplate(string $template, array $data = []): void
    {
        $path = $this->context->path('templates/' . ltrim($template, '/'));

        if (!file_exists($path)) {
            wp_die(esc_html('Template not found: ' . $template));
        }

        $data['context'] ??= $this->context;

        (static function (string $__path, array $__data): void {
            extract($__data, EXTR_SKIP);
            require $__path;
        })($path, $data);
    }
}
