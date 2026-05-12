<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Core;

use YamlNs\WppFramework\Providers\RestServiceProvider;
use YamlNs\WppFramework\Providers\SecurityServiceProvider;
use YamlNs\WppFramework\Providers\ServiceProvider;
use YamlNs\WppFramework\Providers\SupportServiceProvider;
use YamlNs\WppFramework\Providers\ViewServiceProvider;

final class Plugin
{
    /**
     * One framework instance per WordPress plugin.
     *
     * @var array<string, self>
     */
    private static array $instances = [];

    private Container $container;

    /** @var ServiceProvider[] */
    private array $providers = [];

    private bool $booted = false;

    private function __construct(private readonly PluginContext $context)
    {
        $this->container = new Container();
        $this->container->instance(self::class, $this);
        $this->container->instance(Container::class, $this->container);
        $this->container->instance(PluginContext::class, $this->context);
    }

    public static function instance(string|PluginContext $context): self
    {
        $context = self::normalizeContext($context);
        $id = $context->id();

        if (isset(self::$instances[$id])) {
            $existing = self::$instances[$id];

            if ($existing->context()->fingerprint() !== $context->fingerprint()) {
                throw new \RuntimeException(sprintf(
                    'Plugin context collision detected for slug [%s]. Use a unique slug or plugin file.',
                    $context->slug(),
                ));
            }

            return $existing;
        }

        self::$instances[$id] = new self($context);

        return self::$instances[$id];
    }

    /**
     * Reset framework instances.
     *
     * @internal Reserved for the framework and tests. Consumer plugins should not
     * call this during a normal WordPress request.
     */
    public static function reset(?PluginContext $context = null): void
    {
        if ($context === null) {
            self::$instances = [];
            return;
        }

        unset(self::$instances[$context->id()]);
    }

    /**
     * Enregistre des service providers supplementaires avant boot().
     *
     * Default providers are SecurityServiceProvider and RestServiceProvider.
     * AdminServiceProvider is opt-in. Add it only if you want the framework admin
     * page inside your plugin:
     *
     *   $plugin = Plugin::instance($context);
     *   $c      = $plugin->container();
     *
     *   $plugin->withProviders([
     *       new AdminServiceProvider($c),
     *       new MyServiceProvider($c),
     *   ])->boot();
     *
     * @param ServiceProvider[] $providers
     */
    public function withProviders(array $providers): self
    {
        if ($this->booted) {
            throw new \RuntimeException('Cannot register providers after boot().');
        }

        $this->providers = array_merge($this->providers, $providers);

        return $this;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $defaults = [
            new SupportServiceProvider($this->container),
            new SecurityServiceProvider($this->container),
            new ViewServiceProvider($this->container),
            new RestServiceProvider($this->container),
        ];

        $this->providers = array_merge($defaults, $this->providers);

        foreach ($this->providers as $provider) {
            $provider->register();
        }

        foreach ($this->providers as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function context(): PluginContext
    {
        return $this->context;
    }

    private static function normalizeContext(string|PluginContext $context): PluginContext
    {
        if ($context instanceof PluginContext) {
            return $context;
        }

        return PluginContext::fromDirectory($context);
    }
}
