<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Admin\AdminForm;
use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Support\OptionRepository;
use YamlNs\WppFramework\View\ViewRenderer;

final class AdminPageServiceProvider extends ServiceProvider
{
    private PluginContext $context;
    private ViewRenderer $viewRenderer;

    /**
     * @param array{
     *     pages?: array<int, array<string, mixed>>
     * } $admin
     */
    public function __construct(Container $container, private readonly array $admin = [])
    {
        parent::__construct($container);
        $this->context = $container->get(PluginContext::class);
        $this->viewRenderer = $container->get(ViewRenderer::class);
    }

    public function boot(): void
    {
        add_action('admin_menu', function (): void {
            foreach ($this->admin['pages'] ?? [] as $page) {
                $this->registerPage($page);
            }
        });
    }

    /**
     * @param array<string, mixed> $page
     */
    private function registerPage(array $page): void
    {
        $callback = fn (): mixed => $this->renderPage($page);

        if (isset($page['parent_slug'])) {
            add_submenu_page(
                (string) $page['parent_slug'],
                (string) ($page['page_title'] ?? $page['menu_title']),
                (string) $page['menu_title'],
                (string) ($page['capability'] ?? 'manage_options'),
                (string) ($page['slug'] ?? $this->context->slug()),
                $callback,
                $page['position'] ?? null,
            );

            return;
        }

        add_menu_page(
            (string) ($page['page_title'] ?? $page['menu_title']),
            (string) $page['menu_title'],
            (string) ($page['capability'] ?? 'manage_options'),
            (string) ($page['slug'] ?? $this->context->slug()),
            $callback,
            $page['icon'] ?? 'dashicons-admin-generic',
            $page['position'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $page
     */
    private function renderPage(array $page): mixed
    {
        $capability = (string) ($page['capability'] ?? 'manage_options');

        if (!current_user_can($capability)) {
            wp_die(esc_html__('Forbidden', 'wpp-framework'));
        }

        if (isset($page['callback'])) {
            $callback = $this->resolveCallback($page['callback']);

            return $this->container->call($callback);
        }

        if (isset($page['template'])) {
            $data = $page['data'] ?? [];
            $data['context'] ??= $this->context;
            $data['adminForm'] ??= $this->container->get(AdminForm::class);
            $data['options'] ??= $this->container->get(OptionRepository::class);

            echo $this->viewRenderer->render((string) $page['template'], $data);
            return null;
        }

        return null;
    }

    private function resolveCallback(mixed $callback): callable
    {
        if (is_array($callback) && isset($callback[0]) && is_string($callback[0]) && class_exists($callback[0])) {
            $callback[0] = $this->container->get($callback[0]);
        }

        if (!is_callable($callback)) {
            throw new \RuntimeException('Admin page callback must be callable.');
        }

        return $callback;
    }
}
