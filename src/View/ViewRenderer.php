<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\View;

use YamlNs\WppFramework\Core\PluginContext;

final class ViewRenderer
{
    /** @var array<string, string> */
    private array $sections = [];

    /** @var array<int, string> */
    private array $sectionStack = [];

    /** @var array<string, array<int, string>> */
    private array $stacks = [];

    /** @var array<int, string> */
    private array $pushStack = [];

    private ?string $layout = null;

    public function __construct(private readonly PluginContext $context) {}

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $path = $this->resolvePath($view);

        if (!file_exists($path)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        $this->sections = [];
        $this->sectionStack = [];
        $this->stacks = [];
        $this->pushStack = [];
        $this->layout = null;

        ob_start();

        try {
            $this->includeFile($path, $data);
            $content = (string) ob_get_clean();

            if (!isset($this->sections['content']) && trim($content) !== '') {
                $this->sections['content'] = $content;
            }

            if ($this->layout === null) {
                return $content;
            }

            $layout = $this->layout;
            $this->layout = null;

            return $this->renderLayout($layout, $data);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function include(string $view, array $data = []): void
    {
        $this->includeFile($this->resolvePath($view), $data);
    }

    public function extends(string $view): void
    {
        $this->layout = $view;
    }

    public function section(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);

        if ($name === null) {
            throw new \RuntimeException('No active view section.');
        }

        $this->sections[$name] = (string) ob_get_clean();
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function push(string $name): void
    {
        $this->pushStack[] = $name;
        ob_start();
    }

    public function endPush(): void
    {
        $name = array_pop($this->pushStack);

        if ($name === null) {
            throw new \RuntimeException('No active view stack.');
        }

        $this->stacks[$name][] = (string) ob_get_clean();
    }

    public function stack(string $name): string
    {
        return implode('', $this->stacks[$name] ?? []);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function includeFile(string $path, array $data): void
    {
        $data['context'] ??= $this->context;
        $data['view'] ??= $this;

        (static function (string $__path, array $__data): void {
            extract($__data, EXTR_SKIP);
            require $__path;
        })($path, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderLayout(string $layout, array $data): string
    {
        ob_start();

        try {
            $this->includeFile($this->resolvePath($layout), $data);

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    private function resolvePath(string $view): string
    {
        $view = ltrim($view, '/\\');

        if (str_starts_with($view, 'resources/views/')) {
            return $this->context->path($view);
        }

        if (str_ends_with($view, '.php')) {
            return $this->context->path('resources/views/' . $view);
        }

        return $this->context->path('resources/views/' . $view . '.php');
    }
}
