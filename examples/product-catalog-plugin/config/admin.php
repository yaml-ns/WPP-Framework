<?php
declare(strict_types=1);

return [
    'pages' => [
        [
            'parent_slug' => 'edit.php?post_type=product',
            'menu_title' => 'Settings',
            'page_title' => 'Product Catalog Settings',
            'capability' => 'manage_products',
            'slug' => 'product-catalog-settings',
            'template' => 'admin/settings',
        ],
    ],
];
