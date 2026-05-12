<?php

declare(strict_types=1);

namespace ProductCatalogPlugin\Taxonomies;

use YamlNs\WppFramework\Contracts\Taxonomy;

final class ProductCategoryTaxonomy implements Taxonomy
{
    public function name(): string
    {
        return 'product_category';
    }

    public function objectType(): string|array
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
                'name' => 'Product Categories',
                'singular_name' => 'Product Category',
                'search_items' => 'Search categories',
                'all_items' => 'All categories',
                'edit_item' => 'Edit category',
                'update_item' => 'Update category',
                'add_new_item' => 'Add category',
                'new_item_name' => 'New category',
                'menu_name' => 'Categories',
            ],
            'public' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'product-category'],
        ];
    }
}
