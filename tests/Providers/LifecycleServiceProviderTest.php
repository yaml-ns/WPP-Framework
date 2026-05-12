<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Providers;

use YamlNs\WppFramework\Providers\LifecycleServiceProvider;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class LifecycleServiceProviderTest extends TestCase
{
    public function test_activate_runs_pending_migrations_once_and_updates_version(): void
    {
        WordPressState::$options['test_plugin_version'] = '1.0.0';
        $ran = [];

        LifecycleServiceProvider::activate($this->container, [
            'option' => 'test_plugin_version',
            'migrations' => [
                '2.0.0' => static function () use (&$ran): void {
                    $ran[] = '2.0.0';
                },
                '1.5.0' => static function () use (&$ran): void {
                    $ran[] = '1.5.0';
                },
            ],
        ]);

        $this->assertSame(['1.5.0', '2.0.0'], $ran);
        $this->assertSame('2.0.0', WordPressState::$options['test_plugin_version']);

        LifecycleServiceProvider::activate($this->container, [
            'option' => 'test_plugin_version',
            'migrations' => [
                '2.0.0' => static function () use (&$ran): void {
                    $ran[] = 'again';
                },
            ],
        ]);

        $this->assertSame(['1.5.0', '2.0.0'], $ran);
    }
}
