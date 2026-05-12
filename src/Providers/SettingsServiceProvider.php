<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;

final class SettingsServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     settings?: array<int, array<string, mixed>>,
     *     sections?: array<int, array<string, mixed>>,
     *     fields?: array<int, array<string, mixed>>
     * } $settings
     */
    public function __construct(Container $container, private readonly array $settings = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        add_action('admin_init', function (): void {
            foreach ($this->settings['settings'] ?? [] as $setting) {
                register_setting(
                    (string) $setting['option_group'],
                    (string) $setting['option_name'],
                    $setting['args'] ?? [],
                );
            }

            foreach ($this->settings['sections'] ?? [] as $section) {
                add_settings_section(
                    (string) $section['id'],
                    (string) $section['title'],
                    isset($section['callback']) ? $this->resolveCallback($section['callback']) : '__return_null',
                    (string) $section['page'],
                    $section['args'] ?? [],
                );
            }

            foreach ($this->settings['fields'] ?? [] as $field) {
                add_settings_field(
                    (string) $field['id'],
                    (string) $field['title'],
                    $this->resolveCallback($field['callback']),
                    (string) $field['page'],
                    $field['section'] ?? 'default',
                    $field['args'] ?? [],
                );
            }
        });
    }

    private function resolveCallback(mixed $callback): callable
    {
        if (is_array($callback) && isset($callback[0]) && is_string($callback[0]) && class_exists($callback[0])) {
            $callback[0] = $this->container->get($callback[0]);
        }

        if (!is_callable($callback)) {
            throw new \RuntimeException('Settings callback must be callable.');
        }

        return $callback;
    }
}
