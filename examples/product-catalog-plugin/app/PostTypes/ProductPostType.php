<?php
declare(strict_types=1);

namespace ProductCatalogPlugin\PostTypes;

use YamlNs\WppFramework\Contracts\PostType;

final class ProductPostType implements PostType
{
    public function name(): string
    {
        return 'product';
    }

    /**
     * @return array<string, mixed>
     */
    public function args(): array
    {
        return [
            'labels' => [
                'name' => 'Products',
                'singular_name' => 'Product',
                'add_new_item' => 'Add Product',
                'edit_item' => 'Edit Product',
                'new_item' => 'New Product',
                'view_item' => 'View Product',
                'search_items' => 'Search Products',
                'not_found' => 'No products found',
                'menu_name' => 'Products',
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'products'],
            'menu_icon' => 'dashicons-products',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        ];
    }
}
