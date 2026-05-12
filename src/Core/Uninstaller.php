<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Core;

use YamlNs\WppFramework\Providers\CapabilityServiceProvider;

final class Uninstaller
{
    /**
     * @param array<string, mixed> $config
     */
    public static function uninstall(array $config = []): void
    {
        foreach ($config['uninstall']['options'] ?? [] as $option) {
            delete_option((string) $option);
        }

        foreach ($config['uninstall']['site_options'] ?? [] as $option) {
            delete_site_option((string) $option);
        }

        if (($config['uninstall']['remove_capabilities'] ?? false) === true && isset($config['capabilities'])) {
            $capabilities = $config['capabilities'];
            $capabilities['remove_on_deactivate'] = true;

            CapabilityServiceProvider::deactivate($capabilities);
        }
    }
}
