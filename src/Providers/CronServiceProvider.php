<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;

final class CronServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     events?: array<int, array{
     *         hook: string,
     *         recurrence?: string,
     *         callback?: callable,
     *         timestamp?: int,
     *         args?: array<int, mixed>
     *     }>
     * } $cron
     */
    public function __construct(Container $container, private readonly array $cron = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        foreach ($this->cron['events'] ?? [] as $event) {
            if (isset($event['callback'])) {
                add_action((string) $event['hook'], $event['callback']);
            }

            $args = $event['args'] ?? [];

            if (!wp_next_scheduled((string) $event['hook'], $args)) {
                wp_schedule_event(
                    $event['timestamp'] ?? time(),
                    $event['recurrence'] ?? 'hourly',
                    (string) $event['hook'],
                    $args
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $cron
     */
    public static function deactivate(array $cron): void
    {
        foreach ($cron['events'] ?? [] as $event) {
            wp_clear_scheduled_hook(
                (string) $event['hook'],
                $event['args'] ?? []
            );
        }
    }
}
