<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Providers;

use YamlNs\WppFramework\Auth\ResourcePolicy;
use YamlNs\WppFramework\Providers\AdminCrudServiceProvider;
use YamlNs\WppFramework\Repositories\BaseRepository;
use YamlNs\WppFramework\Tests\Support\RedirectException;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class AdminCrudTestRepository extends BaseRepository
{
    protected function postType(): string
    {
        return 'book';
    }

    protected function metaFields(): array
    {
        return [
            'price' => '_book_price',
        ];
    }
}

final class AdminCrudTestPolicy extends ResourcePolicy
{
    protected function createCapability(): ?string
    {
        return 'manage_options';
    }

    protected function updateCapability(): ?string
    {
        return 'manage_options';
    }

    protected function deleteCapability(): ?string
    {
        return 'manage_options';
    }
}

final class AdminCrudServiceProviderTest extends TestCase
{
    public function test_boot_registers_menu_page_and_admin_post_actions(): void
    {
        $provider = new AdminCrudServiceProvider($this->container, $this->config());
        $provider->boot();

        do_action('admin_menu');

        $this->assertSame('books', WordPressState::$adminPages[0]['menu_slug']);
        $this->assertArrayHasKey('admin_post_books_store', WordPressState::$actions);
        $this->assertArrayHasKey('admin_post_books_update', WordPressState::$actions);
        $this->assertArrayHasKey('admin_post_books_delete', WordPressState::$actions);
        $this->assertArrayHasKey('admin_post_books_bulk', WordPressState::$actions);
    }

    public function test_store_creates_resource_and_redirects(): void
    {
        $provider = new AdminCrudServiceProvider($this->container, $this->config());
        $provider->boot();

        $_POST = [
            '_wpp_admin_form_books_store' => 'valid',
            'title' => '<b>Book</b>',
            'status' => 'draft',
            'price' => '12',
        ];

        try {
            do_action('admin_post_books_store');
            $this->fail('Expected redirect.');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('message=created', $e->location);
        }

        $this->assertSame('Book', WordPressState::$posts[1]->post_title);
        $this->assertSame('draft', WordPressState::$posts[1]->post_status);
        $this->assertSame(12.0, WordPressState::$postMeta[1]['_book_price']);
    }

    public function test_delete_checks_policy_and_deletes_resource(): void
    {
        WordPressState::$posts[10] = new \WP_Post(10, 'book', 'publish', 'Book');

        $provider = new AdminCrudServiceProvider($this->container, $this->config());
        $provider->boot();

        $_POST = [
            '_wpp_admin_form_books_delete' => 'valid',
            'id' => '10',
        ];

        try {
            do_action('admin_post_books_delete');
            $this->fail('Expected redirect.');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('message=deleted', $e->location);
        }

        $this->assertArrayNotHasKey(10, WordPressState::$posts);
    }

    public function test_bulk_delete_removes_selected_resources(): void
    {
        WordPressState::$posts[10] = new \WP_Post(10, 'book', 'publish', 'Book A');
        WordPressState::$posts[11] = new \WP_Post(11, 'book', 'publish', 'Book B');

        $provider = new AdminCrudServiceProvider($this->container, $this->config());
        $provider->boot();

        $_POST = [
            '_wpp_admin_form_books_bulk' => 'valid',
            'bulk_action' => 'delete',
            'ids' => ['10', '11'],
        ];

        try {
            do_action('admin_post_books_bulk');
            $this->fail('Expected redirect.');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('message=bulk_deleted', $e->location);
        }

        $this->assertArrayNotHasKey(10, WordPressState::$posts);
        $this->assertArrayNotHasKey(11, WordPressState::$posts);
    }

    public function test_bulk_endpoint_can_delete_single_resource_from_row_action(): void
    {
        WordPressState::$posts[12] = new \WP_Post(12, 'book', 'publish', 'Book C');

        $provider = new AdminCrudServiceProvider($this->container, $this->config());
        $provider->boot();

        $_POST = [
            '_wpp_admin_form_books_bulk' => 'valid',
            'delete_id' => '12',
        ];

        try {
            do_action('admin_post_books_bulk');
            $this->fail('Expected redirect.');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('message=bulk_deleted', $e->location);
        }

        $this->assertArrayNotHasKey(12, WordPressState::$posts);
    }

    public function test_validation_errors_redirect_back_to_form_with_flash_data(): void
    {
        $provider = new AdminCrudServiceProvider($this->container, $this->config());
        $provider->boot();

        $_POST = [
            '_wpp_admin_form_books_store' => 'valid',
            'title' => '',
            'status' => 'draft',
            'price' => '19.5',
        ];

        try {
            do_action('admin_post_books_store');
            $this->fail('Expected redirect.');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('action=create', $e->location);
            $this->assertStringContainsString('message=validation_failed', $e->location);
        }

        $this->assertSame([], WordPressState::$posts);
        $this->assertCount(1, WordPressState::$transients);
        $flash = array_values(WordPressState::$transients)[0];

        $this->assertSame(['title' => ['title is required.']], $flash['errors']);
        $this->assertSame(19.5, $flash['old']['price']);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return [
            'resources' => [
                [
                    'slug' => 'books',
                    'label' => 'Books',
                    'capability' => 'manage_options',
                    'repository' => AdminCrudTestRepository::class,
                    'policy' => AdminCrudTestPolicy::class,
                    'views' => [
                        'index' => 'admin/books/index',
                        'form' => 'admin/books/form',
                    ],
                    'fields' => [
                        'title' => ['type' => 'text'],
                        'status' => ['type' => 'text'],
                        'price' => ['type' => 'number', 'meta_key' => '_book_price'],
                    ],
                    'rules' => [
                        'title' => ['required', 'string'],
                        'status' => ['required', 'in:draft,publish'],
                        'price' => ['nullable', 'numeric'],
                    ],
                ],
            ],
        ];
    }
}
