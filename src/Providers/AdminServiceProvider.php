<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Admin\FrameworkAdminPage;
use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Core\PluginContext;

final class AdminServiceProvider extends ServiceProvider
{
    private readonly PluginContext $context;

    public function __construct(Container $container, string|PluginContext|null $context = null)
    {
        parent::__construct($container);
        $this->context = $context instanceof PluginContext
            ? $context
            : ($context !== null ? PluginContext::fromDirectory($context) : $container->get(PluginContext::class));
    }

    public function register(): void
    {
        $this->container->instance(
            FrameworkAdminPage::class,
            new FrameworkAdminPage($this->context)
        );
    }

    public function boot(): void
    {
        add_action('admin_menu', [$this->container->get(FrameworkAdminPage::class), 'register']);
    }
}
