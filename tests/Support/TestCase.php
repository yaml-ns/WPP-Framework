<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Support;

use PHPUnit\Framework\TestCase as BaseTestCase;
use YamlNs\WppFramework\Core\Container;
use YamlNs\WppFramework\Core\PluginContext;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        WordPressState::reset();

        $this->container = new Container();
        $this->container->instance(Container::class, $this->container);
        $this->container->instance(PluginContext::class, PluginContext::fromDirectory(__DIR__, [
            'slug' => 'test-plugin',
            'version' => '2.0.0',
            'rest_namespace' => 'test/v1',
        ]));
    }
}
