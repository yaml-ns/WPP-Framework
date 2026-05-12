<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Core;

use PHPUnit\Framework\TestCase;
use YamlNs\WppFramework\Core\Plugin;
use YamlNs\WppFramework\Core\PluginContext;

final class PluginTest extends TestCase
{
    protected function tearDown(): void
    {
        Plugin::reset();
    }

    public function test_instance_throws_on_context_collision(): void
    {
        $first = PluginContext::fromDirectory(__DIR__, [
            'slug' => 'same-plugin',
            'rest_namespace' => 'same/v1',
        ]);
        $second = PluginContext::fromDirectory(__DIR__, [
            'slug' => 'same-plugin',
            'rest_namespace' => 'other/v1',
        ]);

        Plugin::instance($first);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Plugin context collision');

        Plugin::instance($second);
    }
}
