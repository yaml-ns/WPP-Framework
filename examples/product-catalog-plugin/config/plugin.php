<?php
declare(strict_types=1);

use ProductCatalogPlugin\PostTypes\ProductPostType;
use ProductCatalogPlugin\Shortcodes\ProductCatalogShortcode;
use ProductCatalogPlugin\Taxonomies\ProductCategoryTaxonomy;

return [
    'slug' => 'product-catalog-plugin',
    'name' => 'Product Catalog Plugin',
    'version' => '1.0.0',
    'text_domain' => 'product-catalog-plugin',
    'rest_namespace' => 'products/v1',
    'i18n' => [
        'path' => 'languages',
    ],
    'capabilities' => [
        'roles' => [
            'administrator' => [
                'manage_products',
            ],
        ],
    ],
    'lifecycle' => [
        'option' => 'product_catalog_plugin_version',
        'migrations' => [
            '1.0.0' => static function (): void {
                // Initial version marker.
            },
        ],
    ],
    'logger' => [
        'enabled' => true,
        'min_level' => 'warning',
    ],
    'post_types' => [
        ProductPostType::class,
    ],
    'taxonomies' => [
        ProductCategoryTaxonomy::class,
    ],
    'admin' => require __DIR__ . '/admin.php',
    'admin_crud' => require __DIR__ . '/admin-crud.php',
    'admin_forms' => require __DIR__ . '/admin-forms.php',
    'meta_boxes' => require __DIR__ . '/meta-boxes.php',
    'routes' => [
        __DIR__ . '/../routes/api.php',
    ],
    'shortcodes' => [
        'product_catalog' => [ProductCatalogShortcode::class, 'render'],
    ],
    'assets' => require __DIR__ . '/assets.php',
    'uninstall' => [
        'options' => [
            'product_catalog_plugin_version',
            'product_catalog_settings',
        ],
        'remove_capabilities' => true,
    ],
];
