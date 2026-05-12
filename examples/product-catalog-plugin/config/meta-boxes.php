<?php

declare(strict_types=1);

return [
    'boxes' => [
        [
            'id' => 'product_details',
            'title' => 'Product Details',
            'screen' => 'product',
            'context' => 'normal',
            'priority' => 'high',
            'fields' => [
                'product_price' => [
                    'label' => 'Price',
                    'type' => 'number',
                    'description' => 'Public product price.',
                    'show_in_rest' => true,
                ],
                'product_sku' => [
                    'label' => 'SKU',
                    'type' => 'text',
                    'description' => 'Internal product reference.',
                    'show_in_rest' => true,
                ],
                'product_stock' => [
                    'label' => 'Stock',
                    'type' => 'number',
                    'description' => 'Available stock quantity.',
                    'show_in_rest' => true,
                ],
                'product_external_url' => [
                    'label' => 'External URL',
                    'type' => 'url',
                    'description' => 'Optional external product page.',
                    'show_in_rest' => true,
                ],
                'product_featured' => [
                    'label' => 'Featured',
                    'type' => 'checkbox',
                    'description' => 'Display this product first.',
                    'show_in_rest' => true,
                ],
            ],
        ],
    ],
];
