<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use Psr\Log\LoggerInterface;
use YamlNs\WppFramework\Admin\AdminForm;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Fields\FieldSanitizer;
use YamlNs\WppFramework\Http\PostInput;
use YamlNs\WppFramework\Meta\MetaFieldSanitizer;
use YamlNs\WppFramework\Support\Logger;
use YamlNs\WppFramework\Support\OptionRepository;

final class SupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            Logger::class,
            fn () => new Logger($this->container->get(PluginContext::class)),
        );

        $this->container->singleton(
            LoggerInterface::class,
            fn () => $this->container->get(Logger::class),
        );

        $this->container->singleton(AdminForm::class, fn () => new AdminForm());
        $this->container->singleton(PostInput::class, fn () => new PostInput());
        $this->container->singleton(FieldSanitizer::class, fn () => new FieldSanitizer());
        $this->container->singleton(MetaFieldSanitizer::class, fn () => new MetaFieldSanitizer());
        $this->container->singleton(OptionRepository::class, fn () => new OptionRepository());
    }
}
