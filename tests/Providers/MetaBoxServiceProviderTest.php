<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Providers;

use YamlNs\WppFramework\Providers\MetaBoxServiceProvider;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class MetaBoxServiceProviderTest extends TestCase
{
    public function test_save_post_unslashes_sanitizes_and_persists_fields(): void
    {
        $provider = new MetaBoxServiceProvider($this->container, [
            'boxes' => [
                [
                    'id' => 'details',
                    'title' => 'Details',
                    'screen' => 'book',
                    'fields' => [
                        'book_title' => ['type' => 'text'],
                        'book_price' => ['type' => 'number'],
                        'book_enabled' => ['type' => 'checkbox'],
                    ],
                ],
            ],
        ]);

        $provider->boot();

        $_POST = [
            '_wpp_meta_box_details' => 'valid',
            'book_title' => 'L\\\'Appartement <strong>test</strong>',
            'book_price' => '19.5',
            'book_enabled' => '1',
        ];

        do_action('save_post', 10);

        $this->assertSame("L'Appartement test", WordPressState::$postMeta[10]['book_title']);
        $this->assertSame(19.5, WordPressState::$postMeta[10]['book_price']);
        $this->assertSame('1', WordPressState::$postMeta[10]['book_enabled']);
    }

    public function test_save_post_skips_boxes_for_other_post_types(): void
    {
        WordPressState::$posts[10] = new \WP_Post(10, 'page', 'publish');

        $provider = new MetaBoxServiceProvider($this->container, [
            'boxes' => [
                [
                    'id' => 'details',
                    'title' => 'Details',
                    'screen' => 'book',
                    'fields' => [
                        'book_title' => ['type' => 'text'],
                    ],
                ],
            ],
        ]);

        $provider->boot();

        $_POST = [
            '_wpp_meta_box_details' => 'valid',
            'book_title' => 'Should not save',
        ];

        do_action('save_post', 10);

        $this->assertArrayNotHasKey(10, WordPressState::$postMeta);
    }
}
