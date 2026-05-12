<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\View;

use PHPUnit\Framework\TestCase;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\View\ViewRenderer;

final class ViewRendererTest extends TestCase
{
    private string $pluginDir;

    protected function setUp(): void
    {
        $this->pluginDir = sys_get_temp_dir() . '/wpp_views_' . uniqid();
        mkdir($this->pluginDir . '/resources/views/layouts', 0775, true);
        mkdir($this->pluginDir . '/resources/views/partials', 0775, true);
        mkdir($this->pluginDir . '/resources/views/pages', 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->pluginDir);
    }

    public function test_render_supports_layout_sections_includes_and_stacks(): void
    {
        file_put_contents($this->pluginDir . '/resources/views/layouts/app.php', <<<'PHP'
<html><body><?php echo $view->yield('content'); ?><?php echo $view->stack('scripts'); ?></body></html>
PHP);
        file_put_contents($this->pluginDir . '/resources/views/partials/title.php', <<<'PHP'
<h1><?php echo esc_html($title); ?></h1>
PHP);
        file_put_contents($this->pluginDir . '/resources/views/pages/show.php', <<<'PHP'
<?php $view->extends('layouts/app'); ?>
<?php $view->section('content'); ?>
<?php $view->include('partials/title', ['title' => $title]); ?>
<?php $view->endSection(); ?>
<?php $view->push('scripts'); ?><script>window.ready = true;</script><?php $view->endPush(); ?>
PHP);

        $renderer = new ViewRenderer(PluginContext::fromDirectory($this->pluginDir, [
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'rest_namespace' => 'test/v1',
        ]));

        $this->assertSame(
            '<html><body><h1>Book</h1><script>window.ready=true;</script></body></html>',
            preg_replace('/\s+/', '', $renderer->render('pages/show', ['title' => 'Book']))
        );
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $path) {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }

        rmdir($dir);
    }
}
