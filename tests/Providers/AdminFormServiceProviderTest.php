<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Providers;

use YamlNs\WppFramework\Providers\AdminFormServiceProvider;
use YamlNs\WppFramework\Tests\Support\RedirectException;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class AdminFormServiceProviderTest extends TestCase
{
    public function test_boot_registers_admin_post_action_and_saves_sanitized_option(): void
    {
        $provider = new AdminFormServiceProvider($this->container, [
            'forms' => [
                [
                    'id' => 'settings',
                    'action' => 'save_settings',
                    'option' => 'plugin_settings',
                    'fields' => [
                        'limit' => ['type' => 'number'],
                        'enabled' => ['type' => 'checkbox'],
                        'title' => ['type' => 'text'],
                        'categories' => ['type' => 'select_multiple'],
                    ],
                ],
            ],
        ]);

        $provider->boot();

        $_POST = [
            '_wpp_admin_form_settings' => 'valid',
            '_wp_http_referer' => 'https://example.test/wp-admin/admin.php?page=settings',
            'limit' => '12',
            'enabled' => '1',
            'title' => '<b>Hello</b>',
            'categories' => ['<b>a</b>', 'b'],
        ];

        try {
            do_action('admin_post_save_settings');
            $this->fail('Expected redirect.');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('settings-updated=1', $e->location);
        }

        $this->assertSame([
            'limit' => 12,
            'enabled' => '1',
            'title' => 'Hello',
            'categories' => ['a', 'b'],
        ], WordPressState::$options['plugin_settings']);
    }
}
