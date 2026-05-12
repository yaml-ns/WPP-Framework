<?php
declare(strict_types=1);

namespace YamlNs\WppFramework;

use Psr\Log\LoggerInterface;
use YamlNs\WppFramework\Core\Activator;
use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Core\Deactivator;
use YamlNs\WppFramework\Core\Plugin;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Core\Uninstaller;
use YamlNs\WppFramework\Providers\AdminPageServiceProvider;
use YamlNs\WppFramework\Providers\AdminCrudServiceProvider;
use YamlNs\WppFramework\Providers\AdminFormServiceProvider;
use YamlNs\WppFramework\Providers\AjaxServiceProvider;
use YamlNs\WppFramework\Providers\AssetServiceProvider;
use YamlNs\WppFramework\Providers\CapabilityServiceProvider;
use YamlNs\WppFramework\Providers\CronServiceProvider;
use YamlNs\WppFramework\Providers\I18nServiceProvider;
use YamlNs\WppFramework\Providers\LifecycleServiceProvider;
use YamlNs\WppFramework\Providers\LoggerServiceProvider;
use YamlNs\WppFramework\Providers\MetaBoxServiceProvider;
use YamlNs\WppFramework\Providers\PostTypeServiceProvider;
use YamlNs\WppFramework\Providers\RestControllerServiceProvider;
use YamlNs\WppFramework\Providers\RestRouteServiceProvider;
use YamlNs\WppFramework\Providers\ServiceProvider;
use YamlNs\WppFramework\Providers\SettingsServiceProvider;
use YamlNs\WppFramework\Providers\ShortcodeServiceProvider;
use YamlNs\WppFramework\Providers\TaxonomyServiceProvider;
use YamlNs\WppFramework\Support\ConfigValidator;

final class Wpp
{
    /**
     * @param array{
     *     file?: string,
     *     dir?: string,
     *     url?: string,
     *     slug?: string,
     *     name?: string,
     *     version?: string,
     *     text_domain?: string,
     *     rest_namespace?: string,
     *     admin?: array<string, mixed>,
     *     admin_crud?: array<string, mixed>,
     *     admin_forms?: array<string, mixed>,
     *     ajax?: array<string, mixed>,
     *     assets?: array<string, mixed>,
     *     capabilities?: array<string, mixed>,
     *     cron?: array<string, mixed>,
     *     i18n?: array<string, mixed>,
     *     lifecycle?: array<string, mixed>,
     *     logger?: array<string, mixed>,
     *     meta_boxes?: array<string, mixed>,
     *     post_types?: array<int|string, mixed>,
     *     rest_controllers?: array<int, mixed>,
     *     routes?: array<int|string, string>,
     *     settings?: array<string, mixed>,
     *     shortcodes?: array<string, mixed>,
     *     taxonomies?: array<int|string, mixed>,
     *     uninstall?: array<string, mixed>,
     *     providers?: array<int, class-string<ServiceProvider>|ServiceProvider|callable>,
     *     modules?: array<int, array{enabled?: bool, providers?: array<int, class-string<ServiceProvider>|ServiceProvider|callable>}>
     * } $config
     */
    public static function boot(string $pluginFile, array $config = []): Plugin
    {
        ConfigValidator::validate($config, true);

        $context = PluginContext::fromFile($pluginFile, $config);
        $plugin = Plugin::instance($context);

        $providerDefinitions = array_merge(
            self::configuredProviders($config),
            $config['providers'] ?? [],
            self::moduleProviders($config['modules'] ?? [])
        );

        if ($plugin->isBooted()) {
            if ($providerDefinitions !== []) {
                throw new \RuntimeException('Cannot boot an already booted plugin with additional providers.');
            }

            if ($plugin->container()->has(LoggerInterface::class)) {
                $plugin->container()->get(LoggerInterface::class)->warning('Wpp::boot() called on an already booted plugin without additional providers.');
            }

            return $plugin;
        }

        $providers = self::resolveProviders($plugin->container(), $providerDefinitions);

        if ($providers !== []) {
            $plugin->withProviders($providers);
        }

        $plugin->boot();

        return $plugin;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function activate(string $pluginFile, array $config = []): void
    {
        ConfigValidator::validate($config, true);

        $context = PluginContext::fromFile($pluginFile, $config);
        $plugin = Plugin::instance($context);

        Activator::activate($plugin->container(), $config);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function deactivate(string $pluginFile, array $config = []): void
    {
        // Deactivation intentionally accepts partial config. This keeps cleanup
        // available if a deploy broke the strict boot/activation config.
        $context = PluginContext::fromFile($pluginFile, $config);

        Deactivator::deactivate($config);
        Plugin::reset($context);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function uninstall(string $pluginFile, array $config = []): void
    {
        ConfigValidator::validate($config, true);

        $context = PluginContext::fromFile($pluginFile, $config);

        Uninstaller::uninstall($config);
        Plugin::reset($context);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<int, callable>
     */
    private static function configuredProviders(array $config): array
    {
        $providers = [];

        if (isset($config['admin'])) {
            $providers[] = static fn (Container $container) => new AdminPageServiceProvider($container, $config['admin']);
        }

        if (isset($config['admin_crud'])) {
            $providers[] = static fn (Container $container) => new AdminCrudServiceProvider($container, $config['admin_crud']);
        }

        if (isset($config['admin_forms'])) {
            $providers[] = static fn (Container $container) => new AdminFormServiceProvider($container, $config['admin_forms']);
        }

        if (isset($config['ajax'])) {
            $providers[] = static fn (Container $container) => new AjaxServiceProvider($container, $config['ajax']);
        }

        if (isset($config['assets'])) {
            $providers[] = static fn (Container $container) => new AssetServiceProvider($container, $config['assets']);
        }

        if (isset($config['capabilities'])) {
            $providers[] = static fn (Container $container) => new CapabilityServiceProvider($container, $config['capabilities']);
        }

        if (isset($config['cron'])) {
            $providers[] = static fn (Container $container) => new CronServiceProvider($container, $config['cron']);
        }

        if (isset($config['i18n'])) {
            $providers[] = static fn (Container $container) => new I18nServiceProvider($container, $config['i18n']);
        }

        if (isset($config['lifecycle'])) {
            $providers[] = static fn (Container $container) => new LifecycleServiceProvider($container, $config['lifecycle']);
        }

        if (isset($config['logger'])) {
            $providers[] = static fn (Container $container) => new LoggerServiceProvider($container, $config['logger']);
        }

        if (isset($config['meta_boxes'])) {
            $providers[] = static fn (Container $container) => new MetaBoxServiceProvider($container, $config['meta_boxes']);
        }

        if (isset($config['post_types'])) {
            $providers[] = static fn (Container $container) => new PostTypeServiceProvider($container, [
                'post_types' => $config['post_types'],
            ]);
        }

        if (isset($config['rest_controllers'])) {
            $providers[] = static fn (Container $container) => new RestControllerServiceProvider($container, [
                'controllers' => $config['rest_controllers'],
            ]);
        }

        if (isset($config['routes'])) {
            $providers[] = static fn (Container $container) => new RestRouteServiceProvider($container, [
                'files' => $config['routes'],
            ]);
        }

        if (isset($config['taxonomies'])) {
            $providers[] = static fn (Container $container) => new TaxonomyServiceProvider($container, [
                'taxonomies' => $config['taxonomies'],
            ]);
        }

        if (isset($config['settings'])) {
            $providers[] = static fn (Container $container) => new SettingsServiceProvider($container, $config['settings']);
        }

        if (isset($config['shortcodes'])) {
            $providers[] = static fn (Container $container) => new ShortcodeServiceProvider($container, [
                'shortcodes' => $config['shortcodes'],
            ]);
        }

        return $providers;
    }

    /**
     * @param array<int, array{enabled?: bool, providers?: array<int, class-string<ServiceProvider>|ServiceProvider|callable>}> $modules
     * @return array<int, class-string<ServiceProvider>|ServiceProvider|callable>
     */
    private static function moduleProviders(array $modules): array
    {
        $providers = [];

        foreach ($modules as $module) {
            if (($module['enabled'] ?? true) !== true) {
                continue;
            }

            $providers = array_merge($providers, $module['providers'] ?? []);
        }

        return $providers;
    }

    /**
     * @param array<int, class-string<ServiceProvider>|ServiceProvider|callable> $providers
     * @return ServiceProvider[]
     */
    private static function resolveProviders(Container $container, array $providers): array
    {
        $resolved = [];

        foreach ($providers as $provider) {
            if ($provider instanceof ServiceProvider) {
                $resolved[] = $provider;
                continue;
            }

            if (is_string($provider) && is_subclass_of($provider, ServiceProvider::class)) {
                $resolved[] = new $provider($container);
                continue;
            }

            if (is_callable($provider)) {
                $instance = $provider($container);

                if (!$instance instanceof ServiceProvider) {
                    throw new \RuntimeException('Provider factory must return a ServiceProvider instance.');
                }

                $resolved[] = $instance;
                continue;
            }

            throw new \RuntimeException('Invalid service provider definition.');
        }

        return $resolved;
    }
}
