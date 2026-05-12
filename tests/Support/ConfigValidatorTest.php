<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Support;

use PHPUnit\Framework\TestCase;
use YamlNs\WppFramework\Support\ConfigValidator;

final class ConfigValidatorTest extends TestCase
{
    public function test_invalid_shortcode_tag_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid shortcode tag');

        ConfigValidator::validate([
            'shortcodes' => [
                'bad tag' => static fn (): string => '',
            ],
        ]);
    }

    public function test_admin_form_requires_action_and_option_or_handler(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must define [option] or [handler]');

        ConfigValidator::validate([
            'admin_forms' => [
                'forms' => [
                    [
                        'id' => 'settings',
                        'action' => 'save_settings',
                    ],
                ],
            ],
        ]);
    }

    public function test_routes_must_be_string_paths(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Route file');

        ConfigValidator::validate([
            'routes' => [
                ['not-a-path'],
            ],
        ]);
    }

    public function test_plugin_identity_is_required_when_requested(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config key [slug] is required.');

        ConfigValidator::validate([], true);
    }

    public function test_route_file_must_exist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Route file not found');

        ConfigValidator::validate([
            'routes' => [
                __DIR__ . '/../missing-route-file.php',
            ],
        ]);
    }

    public function test_admin_crud_resource_requires_views_and_fields(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must define views [index] and [form]');

        ConfigValidator::validate([
            'admin_crud' => [
                'resources' => [
                    [
                        'slug' => 'books',
                        'repository' => 'BookRepository',
                        'views' => ['index' => 'admin/books/index'],
                        'fields' => [],
                    ],
                ],
            ],
        ]);
    }

    public function test_admin_crud_validates_filters_rules_and_options(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('filter [featured] must define [query] or [meta_key]');

        ConfigValidator::validate([
            'admin_crud' => [
                'resources' => [
                    [
                        'slug' => 'books',
                        'repository' => 'BookRepository',
                        'views' => [
                            'index' => 'admin/books/index',
                            'form' => 'admin/books/form',
                        ],
                        'fields' => [
                            'title' => ['type' => 'text'],
                        ],
                        'filters' => [
                            'featured' => [
                                'label' => 'Featured',
                                'type' => 'select',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_admin_crud_accepts_complete_resource_config(): void
    {
        $this->expectNotToPerformAssertions();

        ConfigValidator::validate([
            'admin_crud' => [
                'resources' => [
                    [
                        'slug' => 'books',
                        'repository' => 'BookRepository',
                        'policy' => 'BookPolicy',
                        'force_delete' => false,
                        'per_page' => 20,
                        'views' => [
                            'index' => 'admin/books/index',
                            'form' => 'admin/books/form',
                        ],
                        'fields' => [
                            'title' => [
                                'label' => 'Title',
                                'type' => 'text',
                                'required' => true,
                            ],
                            'genres' => [
                                'type' => 'select_multiple',
                                'options' => ['fiction' => 'Fiction'],
                            ],
                        ],
                        'filters' => [
                            's' => ['query' => 'search', 'type' => 'search'],
                            'featured' => [
                                'meta_key' => '_book_featured',
                                'type' => 'select',
                                'options' => ['1' => 'Yes'],
                            ],
                        ],
                        'rules' => [
                            'title' => ['required', 'string'],
                            'status' => 'nullable|string',
                        ],
                        'messages' => [
                            'title.required' => 'Title is required.',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_asset_localize_requires_object_name(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('localize must define [object_name]');

        ConfigValidator::validate([
            'assets' => [
                'frontend' => [
                    'scripts' => [
                        [
                            'handle' => 'app',
                            'src' => 'app.js',
                            'localize' => ['data' => []],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
