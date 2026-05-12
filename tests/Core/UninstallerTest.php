<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Core;

use YamlNs\WppFramework\Core\Uninstaller;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class UninstallerTest extends TestCase
{
    public function test_uninstall_deletes_declared_options(): void
    {
        WordPressState::$options['plugin_version'] = '1.0.0';
        WordPressState::$options['plugin_settings'] = ['enabled' => '1'];

        Uninstaller::uninstall([
            'uninstall' => [
                'options' => ['plugin_version', 'plugin_settings'],
            ],
        ]);

        $this->assertArrayNotHasKey('plugin_version', WordPressState::$options);
        $this->assertArrayNotHasKey('plugin_settings', WordPressState::$options);
    }
}
