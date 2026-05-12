<?php
declare(strict_types=1);

use ProductCatalogPlugin\Policies\ProductPolicy;
use ProductCatalogPlugin\Repositories\ProductRepository;

return [
    'resources' => [
        [
            'slug' => 'product_catalog',
            'label' => 'Products',
            'menu_title' => 'Products',
            'page_title' => 'Products',
            'parent_slug' => 'edit.php?post_type=product',
            'capability' => 'manage_products',
            'force_delete' => false,
            'repository' => ProductRepository::class,
            'policy' => ProductPolicy::class,
            'views' => [
                'index' => 'admin/product/index',
                'form' => 'admin/product/form',
            ],
            'filters' => [
                's' => [
                    'label' => 'Search',
                    'query' => 'search',
                    'type' => 'search',
                ],
                'status' => [
                    'label' => 'Status',
                    'query' => 'post_status',
                    'type' => 'select',
                    'options' => [
                        'publish' => 'Published',
                        'draft' => 'Draft',
                    ],
                ],
                'featured' => [
                    'label' => 'Featured',
                    'type' => 'select',
                    'meta_key' => 'product_featured',
                    'options' => [
                        '1' => 'Featured',
                        '0' => 'Not featured',
                    ],
                ],
            ],
            'fields' => [
                'title' => [
                    'label' => 'Name',
                    'type' => 'text',
                    'required' => true,
                ],
                'content' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                ],
                'status' => [
                    'label' => 'Status',
                    'type' => 'select',
                    'default' => 'publish',
                    'options' => [
                        'draft' => 'Draft',
                        'publish' => 'Published',
                    ],
                ],
                'price' => [
                    'label' => 'Price',
                    'type' => 'float',
                    'meta_key' => 'product_price',
                ],
                'sku' => [
                    'label' => 'SKU',
                    'type' => 'text',
                    'meta_key' => 'product_sku',
                ],
                'stock' => [
                    'label' => 'Stock',
                    'type' => 'number',
                    'meta_key' => 'product_stock',
                ],
                'external_url' => [
                    'label' => 'External URL',
                    'type' => 'url',
                    'meta_key' => 'product_external_url',
                ],
                'featured' => [
                    'label' => 'Featured',
                    'type' => 'checkbox',
                    'meta_key' => 'product_featured',
                    'description' => 'Display this product first.',
                ],
            ],
            'rules' => [
                'title' => ['required', 'string', 'max:120'],
                'content' => ['nullable', 'string'],
                'status' => ['required', 'in:draft,publish'],
                'price' => ['required', 'numeric', 'min:0'],
                'sku' => ['nullable', 'string', 'max:80'],
                'stock' => ['nullable', 'integer', 'min:0'],
                'external_url' => ['nullable', 'url'],
            ],
        ],
    ],
];
