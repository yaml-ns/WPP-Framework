<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Core\PluginContext;

final class AssetServiceProvider extends ServiceProvider
{
    private PluginContext $context;

    /**
     * @param array{
     *     admin?: array{styles?: array<int, array<string, mixed>>, scripts?: array<int, array<string, mixed>>},
     *     frontend?: array{styles?: array<int, array<string, mixed>>, scripts?: array<int, array<string, mixed>>}
     * } $assets
     */
    public function __construct(
        Container $container,
        private readonly array $assets = [],
    ) {
        parent::__construct($container);
        $this->context = $container->get(PluginContext::class);
    }

    public function boot(): void
    {
        if (isset($this->assets['admin'])) {
            add_action('admin_enqueue_scripts', function (?string $hook = null): void {
                $this->enqueueGroup($this->assets['admin'], $hook);
            });
        }

        if (isset($this->assets['frontend'])) {
            add_action('wp_enqueue_scripts', function (): void {
                $this->enqueueGroup($this->assets['frontend']);
            });
        }
    }

    /**
     * @param array{styles?: array<int, array<string, mixed>>, scripts?: array<int, array<string, mixed>>} $group
     */
    private function enqueueGroup(array $group, ?string $hook = null): void
    {
        foreach ($group['styles'] ?? [] as $style) {
            if (!$this->shouldEnqueue($style, $hook)) {
                continue;
            }

            $this->enqueueStyle($style);
        }

        foreach ($group['scripts'] ?? [] as $script) {
            if (!$this->shouldEnqueue($script, $hook)) {
                continue;
            }

            $this->enqueueScript($script);
        }
    }

    /**
     * @param array<string, mixed> $style
     */
    private function enqueueStyle(array $style): void
    {
        wp_enqueue_style(
            $style['handle'] ?? $this->context->slug() . '-style',
            $this->assetUrl((string) ($style['src'] ?? '')),
            $style['deps'] ?? [],
            $style['version'] ?? $this->context->version(),
            $style['media'] ?? 'all',
        );
    }

    /**
     * @param array<string, mixed> $script
     */
    private function enqueueScript(array $script): void
    {
        $handle = $script['handle'] ?? $this->context->slug() . '-script';

        wp_enqueue_script(
            $handle,
            $this->assetUrl((string) ($script['src'] ?? '')),
            $script['deps'] ?? [],
            $script['version'] ?? $this->context->version(),
            $script['args'] ?? ($script['in_footer'] ?? true),
        );

        if (isset($script['localize'])) {
            wp_localize_script(
                (string) $handle,
                (string) $script['localize']['object_name'],
                $script['localize']['data'] ?? [],
            );
        }
    }

    /**
     * @param array<string, mixed> $asset
     */
    private function shouldEnqueue(array $asset, ?string $hook): bool
    {
        if (!isset($asset['only'])) {
            return true;
        }

        if (is_callable($asset['only'])) {
            return (bool) $asset['only']($hook);
        }

        return in_array($hook, (array) $asset['only'], true);
    }

    private function assetUrl(string $src): string
    {
        if ($src === '' || str_starts_with($src, 'http://') || str_starts_with($src, 'https://') || str_starts_with($src, '//')) {
            return $src;
        }

        return $this->context->assetUrl($src);
    }
}
