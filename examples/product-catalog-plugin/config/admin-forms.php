<?php

declare(strict_types=1);

return [
    'forms' => [
        [
            'id' => 'product_catalog_settings',
            'action' => 'product_catalog_save_settings',
            'option' => 'product_catalog_settings',
            'capability' => 'manage_products',
            'fields' => [
                'default_limit' => [
                    'label' => 'Default product limit',
                    'type' => 'number',
                    'default' => 12,
                ],
                'display_featured_first' => [
                    'label' => 'Show featured products first',
                    'type' => 'checkbox',
                    'default' => '0',
                ],
                'in_stock_only' => [
                    'label' => 'Show only products in stock',
                    'type' => 'checkbox',
                    'default' => '0',
                ],
            ],
        ],
    ],
];
