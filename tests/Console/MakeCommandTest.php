<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Console;

use PHPUnit\Framework\TestCase;
use YamlNs\WppFramework\Console\MakeCommand;

final class MakeCommandTest extends TestCase
{
    private string $pluginDir;

    protected function setUp(): void
    {
        $this->pluginDir = sys_get_temp_dir() . '/wpp_make_command_' . uniqid();
        mkdir($this->pluginDir, 0775, true);
        file_put_contents($this->pluginDir . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'DemoPlugin\\' => 'app/',
                ],
            ],
        ]));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->pluginDir);
    }

    public function test_make_resource_generates_expected_files(): void
    {
        $status = (new MakeCommand())->run([
            'wpp',
            'make:resource',
            'Product',
            '--plugin=' . $this->pluginDir,
        ]);

        $this->assertSame(0, $status);
        $this->assertFileExists($this->pluginDir . '/app/PostTypes/ProductPostType.php');
        $this->assertFileExists($this->pluginDir . '/app/Repositories/ProductRepository.php');
        $this->assertFileExists($this->pluginDir . '/app/Http/Controllers/ProductRestController.php');
        $this->assertFileExists($this->pluginDir . '/routes/product.php');

        $this->assertStringContainsString('extends BaseRepository', file_get_contents($this->pluginDir . '/app/Repositories/ProductRepository.php'));
        $routes = file_get_contents($this->pluginDir . '/routes/product.php');

        $this->assertStringContainsString("ProductRestController::class, 'index'", $routes);
        $this->assertStringContainsString("ProductRestController::class, 'show'", $routes);
    }

    public function test_make_crud_generates_controller_requests_and_routes(): void
    {
        $status = (new MakeCommand())->run([
            'wpp',
            'make:crud',
            'Property',
            '--plugin=' . $this->pluginDir,
        ]);

        $this->assertSame(0, $status);
        $this->assertFileExists($this->pluginDir . '/app/PostTypes/PropertyPostType.php');
        $this->assertFileExists($this->pluginDir . '/app/Repositories/PropertyRepository.php');
        $this->assertFileExists($this->pluginDir . '/app/Policies/PropertyPolicy.php');
        $this->assertFileExists($this->pluginDir . '/app/Http/Requests/StorePropertyRequest.php');
        $this->assertFileExists($this->pluginDir . '/app/Http/Requests/UpdatePropertyRequest.php');
        $this->assertFileExists($this->pluginDir . '/app/Http/Controllers/PropertyRestController.php');
        $this->assertFileExists($this->pluginDir . '/routes/property.php');

        $controller = file_get_contents($this->pluginDir . '/app/Http/Controllers/PropertyRestController.php');

        $this->assertStringContainsString('function store(StorePropertyRequest $request)', $controller);
        $this->assertStringContainsString('function update(UpdatePropertyRequest $request)', $controller);
        $this->assertStringContainsString('function destroy(WP_REST_Request $request)', $controller);
        $this->assertStringContainsString('protected readonly PropertyPolicy $policy', $controller);
        $this->assertStringContainsString("authorize('create')", $controller);
        $this->assertStringContainsString("apiResource('/properties'", file_get_contents($this->pluginDir . '/routes/property.php'));
    }

    public function test_make_admin_crud_generates_config_and_views(): void
    {
        $status = (new MakeCommand())->run([
            'wpp',
            'make:admin-crud',
            'Property',
            '--plugin=' . $this->pluginDir,
        ]);

        $this->assertSame(0, $status);
        $this->assertFileExists($this->pluginDir . '/config/admin-crud.php');
        $this->assertFileExists($this->pluginDir . '/resources/views/admin/property/index.php');
        $this->assertFileExists($this->pluginDir . '/resources/views/admin/property/form.php');

        $config = file_get_contents($this->pluginDir . '/config/admin-crud.php');
        $index = file_get_contents($this->pluginDir . '/resources/views/admin/property/index.php');
        $form = file_get_contents($this->pluginDir . '/resources/views/admin/property/form.php');

        $this->assertStringContainsString('PropertyRepository::class', $config);
        $this->assertStringContainsString('PropertyPolicy::class', $config);
        $this->assertStringContainsString('admin/property/index', $config);
        $this->assertStringContainsString("'filters' => [", $config);
        $this->assertStringContainsString("actionField(\$slug . '_bulk')", $index);
        $this->assertStringContainsString('name="bulk_action"', $index);
        $this->assertStringContainsString('name="ids[]"', $index);
        $this->assertStringContainsString('$totalPages > 1', $index);
        $this->assertStringContainsString('$errors !== []', $form);
        $this->assertStringContainsString("\$type === 'select_multiple'", $form);
        $this->assertStringContainsString("\$type === 'checkboxes'", $form);
        $this->assertStringContainsString('nonceFields($nonceId)', $form);
    }

    public function test_detects_plugin_namespace_that_points_to_app_directory(): void
    {
        file_put_contents($this->pluginDir . '/composer.json', json_encode([
            'autoload' => [
                'psr-4' => [
                    'SharedVendor\\' => 'src/',
                    'DemoPlugin\\' => 'app/',
                ],
            ],
        ]));

        $status = (new MakeCommand())->run([
            'wpp',
            'make:provider',
            'Catalog',
            '--plugin=' . $this->pluginDir,
        ]);

        $this->assertSame(0, $status);
        $provider = file_get_contents($this->pluginDir . '/app/Providers/CatalogServiceProvider.php');

        $this->assertStringContainsString('namespace DemoPlugin\\Providers;', $provider);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        ) as $path) {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }

        rmdir($dir);
    }
}
