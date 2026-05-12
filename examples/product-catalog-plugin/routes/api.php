<?php
declare(strict_types=1);

use ProductCatalogPlugin\Http\Controllers\ProductRestController;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Http\RestRouter;

return static function (RestRouter $router, PluginContext $context): void {
    $router->apiResource($context->restNamespace(), '/products', ProductRestController::class, [], [
        'page' => [
            'type' => 'integer',
            'default' => 1,
            'minimum' => 1,
        ],
        'per_page' => [
            'type' => 'integer',
            'default' => 12,
            'minimum' => 1,
            'maximum' => 100,
        ],
        'in_stock' => [
            'type' => 'boolean',
            'default' => false,
        ],
    ]);
};
