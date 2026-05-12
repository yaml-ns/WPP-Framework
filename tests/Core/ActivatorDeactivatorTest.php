<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Core;

use YamlNs\WppFramework\Core\Activator;
use YamlNs\WppFramework\Core\Deactivator;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class ActivatorDeactivatorTest extends TestCase
{
    public function test_activate_registers_structures_capabilities_lifecycle_and_flushes_rewrites(): void
    {
        Activator::activate($this->container, [
            'post_types' => [
                'book' => [
                    'label' => 'Books',
                ],
            ],
            'taxonomies' => [
                'genre' => [
                    'object_type' => 'book',
                    'label' => 'Genres',
                ],
            ],
            'capabilities' => [
                'roles' => [
                    'administrator' => ['manage_books'],
                ],
            ],
            'lifecycle' => [
                'option' => 'test_plugin_version',
            ],
        ]);

        $this->assertSame('book', WordPressState::$registeredPostTypes[0]['post_type']);
        $this->assertSame('genre', WordPressState::$registeredTaxonomies[0]['name']);
        $this->assertTrue(WordPressState::$roles['administrator']['manage_books']);
        $this->assertSame('2.0.0', WordPressState::$options['test_plugin_version']);
        $this->assertSame(['hard'], WordPressState::$flushedRewriteRules);
    }

    public function test_deactivate_clears_cron_removes_capabilities_when_requested_and_flushes_rewrites(): void
    {
        WordPressState::$roles['administrator']['manage_books'] = true;

        Deactivator::deactivate([
            'cron' => [
                'events' => [
                    [
                        'hook' => 'test_cron',
                        'args' => [1],
                    ],
                ],
            ],
            'capabilities' => [
                'remove_on_deactivate' => true,
                'roles' => [
                    'administrator' => ['manage_books'],
                ],
            ],
        ]);

        $this->assertSame('test_cron', WordPressState::$clearedCron[0]['hook']);
        $this->assertArrayNotHasKey('manage_books', WordPressState::$roles['administrator']);
        $this->assertSame(['hard'], WordPressState::$flushedRewriteRules);
    }
}
