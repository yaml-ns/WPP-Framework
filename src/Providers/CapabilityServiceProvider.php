<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;

final class CapabilityServiceProvider extends ServiceProvider
{
    /**
     * @param array{
     *     roles?: array<string, array<int, string>>,
     *     remove_on_deactivate?: bool
     * } $capabilities
     */
    public function __construct(Container $container, private readonly array $capabilities = [])
    {
        parent::__construct($container);
    }

    public function boot(): void
    {
        self::apply($this->capabilities);
    }

    /**
     * @param array<string, mixed> $capabilities
     */
    public static function activate(array $capabilities): void
    {
        self::apply($capabilities);
    }

    /**
     * @param array<string, mixed> $capabilities
     */
    public static function deactivate(array $capabilities): void
    {
        if (($capabilities['remove_on_deactivate'] ?? false) !== true) {
            return;
        }

        foreach ($capabilities['roles'] ?? [] as $roleName => $caps) {
            $role = get_role((string) $roleName);

            if ($role === null) {
                continue;
            }

            foreach ($caps as $capability) {
                $role->remove_cap((string) $capability);
            }
        }
    }

    /**
     * @param array<string, mixed> $capabilities
     */
    private static function apply(array $capabilities): void
    {
        foreach ($capabilities['roles'] ?? [] as $roleName => $caps) {
            $role = get_role((string) $roleName);

            if ($role === null) {
                continue;
            }

            foreach ($caps as $capability) {
                $role->add_cap((string) $capability);
            }
        }
    }
}
