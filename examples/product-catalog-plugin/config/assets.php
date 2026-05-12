<?php
declare(strict_types=1);

return [
    'frontend' => [
        'styles' => [
            [
                'handle' => 'product-catalog-front',
                'src' => 'assets/front.css',
            ],
        ],
    ],
    'admin' => [
        'styles' => [
            [
                'handle' => 'product-catalog-admin',
                'src' => 'assets/front.css',
                'only' => ['post.php', 'post-new.php', 'edit.php'],
            ],
        ],
    ],
];
