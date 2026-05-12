<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\View\ViewRenderer;

final class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            ViewRenderer::class,
            fn () => new ViewRenderer($this->container->get(PluginContext::class)),
        );
    }
}
