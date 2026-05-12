<?php

declare(strict_types=1);

namespace ProductCatalogPlugin\Repositories;

use ProductCatalogPlugin\PostTypes\ProductPostType;
use WP_Post;
use WP_Query;
use YamlNs\WppFramework\Repositories\BaseRepository;

final class ProductRepository extends BaseRepository
{
    public function __construct(private readonly ProductPostType $postType)
    {
    }

    protected function postType(): string
    {
        return $this->postType->name();
    }

    protected function metaFields(): array
    {
        return [
            'price' => 'product_price',
            'sku' => 'product_sku',
            'stock' => 'product_stock',
            'external_url' => 'product_external_url',
            'featured' => 'product_featured',
        ];
    }

    protected function taxonomyFields(): array
    {
        return [
            'categories' => 'product_category',
        ];
    }

    public function latest(int $limit = 12, bool $featuredFirst = false, bool $inStockOnly = false): WP_Query
    {
        $args = [
            'posts_per_page' => max(1, $limit),
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if ($featuredFirst) {
            $featuredQuery = [
                'relation' => 'OR',
                'featured_clause' => [
                    'key' => 'product_featured',
                    'compare' => 'EXISTS',
                    'type' => 'NUMERIC',
                ],
                'missing_featured_clause' => [
                    'key' => 'product_featured',
                    'compare' => 'NOT EXISTS',
                ],
            ];
            $args['meta_query'] = [$featuredQuery];
            $args['orderby'] = [
                'featured_clause' => 'DESC',
                'date' => 'DESC',
            ];
        }

        if ($inStockOnly) {
            $args['meta_query'][] = [
                'key' => 'product_stock',
                'value' => 0,
                'compare' => '>',
                'type' => 'NUMERIC',
            ];
        }

        return $this->query($args);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(WP_Post $post): array
    {
        $data = parent::toArray($post);
        $categories = get_the_terms($post, 'product_category');
        $categories = is_array($categories) ? wp_list_pluck($categories, 'name') : [];

        $featured = get_post_meta($post->ID, 'product_featured', true);

        return array_merge($data, [
            'price' => get_post_meta($post->ID, 'product_price', true),
            'sku' => get_post_meta($post->ID, 'product_sku', true),
            'stock' => get_post_meta($post->ID, 'product_stock', true),
            'external_url' => get_post_meta($post->ID, 'product_external_url', true),
            'featured' => in_array($featured, [true, 1, '1'], true),
            'categories' => $categories,
        ]);
    }
}
