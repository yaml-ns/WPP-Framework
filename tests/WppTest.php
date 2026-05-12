<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests;

use YamlNs\WppFramework\Core\Plugin;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;
use YamlNs\WppFramework\Wpp;

final class WppTest extends TestCase
{
    protected function tearDown(): void
    {
        Plugin::reset();
    }

    public function test_activate_and_deactivate_delegate_lifecycle_cron_capabilities_and_rewrites(): void
    {
        $file = __DIR__ . '/fixtures/test-plugin.php';
        $config = [
            'slug' => 'test-plugin',
            'rest_namespace' => 'test/v1',
            'capabilities' => [
                'remove_on_deactivate' => true,
                'roles' => [
                    'administrator' => ['manage_test'],
                ],
            ],
            'lifecycle' => [
                'option' => 'test_plugin_version',
            ],
            'cron' => [
                'events' => [
                    [
                        'hook' => 'test_hook',
                    ],
                ],
            ],
        ];

        Wpp::activate($file, $config);

        $this->assertTrue(WordPressState::$roles['administrator']['manage_test']);
        $this->assertSame('1.0.0', WordPressState::$options['test_plugin_version']);

        Wpp::deactivate($file, $config);

        $this->assertArrayNotHasKey('manage_test', WordPressState::$roles['administrator']);
        $this->assertSame('test_hook', WordPressState::$clearedCron[0]['hook']);
        $this->assertSame(['hard', 'hard'], WordPressState::$flushedRewriteRules);
    }
}
