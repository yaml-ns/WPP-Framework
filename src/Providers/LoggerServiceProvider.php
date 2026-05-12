<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Support\Logger;

final class LoggerServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     enabled?: bool,
     *     min_level?: string
     * } $logger
     */
    public function __construct(Container $container, private readonly array $logger = [])
    {
        parent::__construct($container);
    }

    public function register(): void
    {
        if (($this->logger['enabled'] ?? true) === false) {
            $this->container->singleton(LoggerInterface::class, fn () => new NullLogger());
            return;
        }

        $this->container->singleton(
            Logger::class,
            fn () => new Logger(
                $this->container->get(PluginContext::class),
                true,
                (string) ($this->logger['min_level'] ?? LogLevel::DEBUG),
            ),
        );

        $this->container->singleton(
            LoggerInterface::class,
            fn () => $this->container->get(Logger::class),
        );
    }
}
