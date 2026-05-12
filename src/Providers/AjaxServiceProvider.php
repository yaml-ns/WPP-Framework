<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;

final class AjaxServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     actions?: array<int, array{
     *         action: string,
     *         callback: callable,
     *         public?: bool
     *     }>
     * } $ajax
     */
    public function __construct(Container $container, private readonly array $ajax = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        foreach ($this->ajax['actions'] ?? [] as $action) {
            $hook = (string) $action['action'];
            $callback = $this->resolveCallback($action['callback']);

            add_action('wp_ajax_' . $hook, fn (): mixed => $this->container->call($callback));

            if (($action['public'] ?? false) === true) {
                add_action('wp_ajax_nopriv_' . $hook, fn (): mixed => $this->container->call($callback));
            }
        }
    }

    private function resolveCallback(mixed $callback): callable
    {
        if (is_array($callback) && isset($callback[0]) && is_string($callback[0]) && class_exists($callback[0])) {
            $callback[0] = $this->container->get($callback[0]);
        }

        if (!is_callable($callback)) {
            throw new \RuntimeException('AJAX callback must be callable.');
        }

        return $callback;
    }
}
