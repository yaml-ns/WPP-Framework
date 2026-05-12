<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Repositories;

use YamlNs\WppFramework\Repositories\BaseRepository;
use YamlNs\WppFramework\Tests\Support\TestCase;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class TestRepository extends BaseRepository
{
    protected function postType(): string
    {
        return 'book';
    }

    protected function metaFields(): array
    {
        return [
            'isbn' => '_book_isbn',
            'rating',
        ];
    }

    protected function taxonomyFields(): array
    {
        return [
            'genres' => 'genre',
        ];
    }
}

final class BaseRepositoryTest extends TestCase
{
    public function test_latest_builds_wordpress_query_with_post_type_defaults(): void
    {
        $query = (new TestRepository())->latest(5);

        $this->assertSame('book', $query->args['post_type']);
        $this->assertSame('publish', $query->args['post_status']);
        $this->assertSame(5, $query->args['posts_per_page']);
        $this->assertSame('date', $query->args['orderby']);
    }

    public function test_find_returns_only_matching_published_post_type(): void
    {
        WordPressState::$posts[10] = new \WP_Post(10, 'book', 'publish');
        WordPressState::$posts[11] = new \WP_Post(11, 'post', 'publish');
        WordPressState::$posts[12] = new \WP_Post(12, 'book', 'draft');

        $repository = new TestRepository();

        $this->assertSame(10, $repository->find(10)?->ID);
        $this->assertNull($repository->find(11));
        $this->assertNull($repository->find(12));
        $this->assertSame(12, $repository->findAny(12)?->ID);
    }

    public function test_to_array_returns_default_shape(): void
    {
        $data = (new TestRepository())->toArray(new \WP_Post(10, 'book', 'publish'));

        $this->assertSame([
            'id' => 10,
            'title' => 'Post 10',
            'link' => 'https://example.test/?p=10',
            'excerpt' => 'Excerpt 10',
        ], $data);
    }

    public function test_create_writes_post_meta_and_terms(): void
    {
        $post = (new TestRepository())->create([
            'title' => 'Clean Architecture',
            'content' => 'A practical book.',
            'excerpt' => 'Practical book.',
            'status' => 'draft',
            'isbn' => '978-0134494166',
            'rating' => 5,
            'genres' => ['architecture', 'software'],
        ]);

        $this->assertSame(1, $post->ID);
        $this->assertSame('book', $post->post_type);
        $this->assertSame('draft', $post->post_status);
        $this->assertSame('Clean Architecture', $post->post_title);
        $this->assertSame('978-0134494166', WordPressState::$postMeta[1]['_book_isbn']);
        $this->assertSame(5, WordPressState::$postMeta[1]['rating']);
        $this->assertSame(['architecture', 'software'], WordPressState::$objectTerms[1]['genre']);
    }

    public function test_update_changes_post_and_declared_meta(): void
    {
        WordPressState::$posts[10] = new \WP_Post(10, 'book', 'publish', 'Old title');

        $post = (new TestRepository())->update(10, [
            'title' => 'New title',
            'isbn' => '123',
        ]);

        $this->assertSame('New title', $post->post_title);
        $this->assertSame('123', WordPressState::$postMeta[10]['_book_isbn']);
    }

    public function test_delete_removes_matching_post_type_only(): void
    {
        WordPressState::$posts[10] = new \WP_Post(10, 'book', 'publish');
        WordPressState::$posts[11] = new \WP_Post(11, 'post', 'publish');

        $repository = new TestRepository();

        $this->assertTrue($repository->delete(10, true));
        $this->assertFalse($repository->delete(11, true));
        $this->assertArrayNotHasKey(10, WordPressState::$posts);
        $this->assertArrayHasKey(10, WordPressState::$deletedPosts);
        $this->assertArrayHasKey(11, WordPressState::$posts);
    }
}
