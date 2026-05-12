<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Core\PluginContext;

final class I18nServiceProvider extends ServiceProvider
{
    private PluginContext $context;

    /**
     * @param array{path?: string, text_domain?: string} $i18n
     */
    public function __construct(Container $container, private readonly array $i18n = [])
    {
        parent::__construct($container);
        $this->context = $container->get(PluginContext::class);
    }

    public function boot(): void
    {
        load_plugin_textdomain(
            (string) ($this->i18n['text_domain'] ?? $this->context->textDomain()),
            false,
            $this->relativeLanguagePath()
        );
    }

    private function relativeLanguagePath(): string
    {
        $path = trim((string) ($this->i18n['path'] ?? 'languages'), '/\\');

        return basename($this->context->dir()) . '/' . $path;
    }
}
