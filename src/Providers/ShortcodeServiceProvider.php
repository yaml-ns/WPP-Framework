<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;

final class ShortcodeServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     shortcodes?: array<string, callable|array{0: class-string|object, 1: string}>
     * } $shortcodes
     */
    public function __construct(Container $container, private readonly array $shortcodes = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        foreach ($this->shortcodes['shortcodes'] ?? [] as $tag => $callback) {
            $tag = $this->validateTag((string) $tag);

            add_shortcode($tag, fn (array|string $atts = [], ?string $content = null): mixed => $this->container->call(
                $this->resolveCallback($callback),
                [
                    'atts' => is_array($atts) ? $atts : [],
                    'content' => $content,
                ]
            ));
        }
    }

    private function validateTag(string $tag): string
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $tag)) {
            throw new \RuntimeException("Invalid shortcode tag: {$tag}");
        }

        return $tag;
    }

    private function resolveCallback(mixed $callback): callable
    {
        if (is_array($callback) && isset($callback[0]) && is_string($callback[0]) && class_exists($callback[0])) {
            $callback[0] = $this->container->get($callback[0]);
        }

        if (!is_callable($callback)) {
            throw new \RuntimeException('Shortcode callback must be callable.');
        }

        return $callback;
    }
}
