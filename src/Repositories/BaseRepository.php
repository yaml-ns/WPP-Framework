<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Repositories;

use WP_Error;
use WP_Post;
use WP_Query;

abstract class BaseRepository
{
    abstract protected function postType(): string;

    /**
     * @return array<int, string>
     */
    protected function writableFields(): array
    {
        return ['post_title', 'post_content', 'post_excerpt', 'post_status', 'post_name', 'menu_order'];
    }

    /**
     * Return either ['field_name'] or ['input_name' => 'meta_key'].
     *
     * @return array<int|string, string>
     */
    protected function metaFields(): array
    {
        return [];
    }

    /**
     * Return either ['taxonomy_name'] or ['input_name' => 'taxonomy_name'].
     *
     * @return array<int|string, string>
     */
    protected function taxonomyFields(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $args
     */
    public function query(array $args = []): WP_Query
    {
        return $this->primeCaches(new WP_Query(array_merge([
            'post_type' => $this->postType(),
            'post_status' => 'publish',
        ], $args)));
    }

    public function all(int $limit = -1): WP_Query
    {
        return $this->query([
            'posts_per_page' => $limit,
        ]);
    }

    public function latest(int $limit = 12): WP_Query
    {
        return $this->query([
            'posts_per_page' => max(1, $limit),
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    public function paginate(int $page = 1, int $perPage = 12): WP_Query
    {
        return $this->query([
            'posts_per_page' => max(1, $perPage),
            'paged' => max(1, $page),
        ]);
    }

    /**
     * @param array<string, mixed> $args
     */
    public function where(array $args): WP_Query
    {
        return $this->query($args);
    }

    public function find(int $id): ?WP_Post
    {
        $post = get_post($id);

        if (!$post instanceof WP_Post || $post->post_type !== $this->postType() || $post->post_status !== 'publish') {
            return null;
        }

        return $post;
    }

    public function findAny(int $id): ?WP_Post
    {
        $post = get_post($id);

        if (!$post instanceof WP_Post || $post->post_type !== $this->postType()) {
            return null;
        }

        return $post;
    }

    public function findOrFail(int $id): WP_Post
    {
        $post = $this->find($id);

        if ($post === null) {
            throw new \RuntimeException("Post [{$id}] not found.");
        }

        return $post;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): WP_Post
    {
        $result = wp_insert_post($this->postData($attributes, true), true);

        if ($result instanceof WP_Error) {
            throw new \RuntimeException($this->wpErrorMessage($result));
        }

        $id = (int) $result;
        $this->syncMeta($id, $attributes);
        $this->syncTaxonomies($id, $attributes);

        return $this->findFresh($id);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int|WP_Post $post, array $attributes): WP_Post
    {
        $target = $post instanceof WP_Post ? $post : get_post($post);

        if (!$target instanceof WP_Post || $target->post_type !== $this->postType()) {
            $id = $post instanceof WP_Post ? $post->ID : $post;
            throw new \RuntimeException("Post [{$id}] not found.");
        }

        $id = $target->ID;
        $result = wp_update_post(array_merge(['ID' => $id], $this->postData($attributes, false)), true);

        if ($result instanceof WP_Error) {
            throw new \RuntimeException($this->wpErrorMessage($result));
        }

        $this->syncMeta($id, $attributes);
        $this->syncTaxonomies($id, $attributes);

        return $this->findFresh($id);
    }

    public function delete(int|WP_Post $post, bool $force = false): bool
    {
        $target = $post instanceof WP_Post ? $post : get_post($post);

        if (!$target instanceof WP_Post || $target->post_type !== $this->postType()) {
            return false;
        }

        $deleted = wp_delete_post($target->ID, $force);

        return $deleted instanceof WP_Post || $deleted === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(WP_Post $post): array
    {
        return [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'link' => get_permalink($post),
            'excerpt' => get_the_excerpt($post),
        ];
    }

    protected function primeCaches(WP_Query $query): WP_Query
    {
        if ($query->posts !== []) {
            $posts = $query->posts;
            update_post_caches($posts, $this->postType(), true, true);
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function postData(array $attributes, bool $creating): array
    {
        $data = $creating ? ['post_type' => $this->postType()] : [];
        $aliases = [
            'title' => 'post_title',
            'content' => 'post_content',
            'excerpt' => 'post_excerpt',
            'status' => 'post_status',
            'slug' => 'post_name',
        ];

        foreach ($aliases as $input => $field) {
            if (array_key_exists($input, $attributes)) {
                $data[$field] = $attributes[$input];
            }
        }

        foreach ($this->writableFields() as $field) {
            if (array_key_exists($field, $attributes)) {
                $data[$field] = $attributes[$field];
            }
        }

        if ($creating && !isset($data['post_status'])) {
            $data['post_status'] = 'publish';
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function syncMeta(int $postId, array $attributes): void
    {
        foreach ($this->metaFields() as $attribute => $metaKey) {
            $attribute = is_int($attribute) ? $metaKey : (string) $attribute;

            if (array_key_exists($attribute, $attributes)) {
                update_post_meta($postId, $metaKey, $attributes[$attribute]);
            }
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function syncTaxonomies(int $postId, array $attributes): void
    {
        foreach ($this->taxonomyFields() as $attribute => $taxonomy) {
            $attribute = is_int($attribute) ? $taxonomy : (string) $attribute;

            if (array_key_exists($attribute, $attributes)) {
                wp_set_object_terms($postId, $attributes[$attribute], $taxonomy);
            }
        }
    }

    private function findFresh(int $id): WP_Post
    {
        $post = get_post($id);

        if (!$post instanceof WP_Post || $post->post_type !== $this->postType()) {
            throw new \RuntimeException("Post [{$id}] could not be loaded.");
        }

        return $post;
    }

    private function wpErrorMessage(WP_Error $error): string
    {
        if (method_exists($error, 'get_error_message')) {
            return (string) $error->get_error_message();
        }

        return $error->message ?? 'WordPress operation failed.';
    }
}
