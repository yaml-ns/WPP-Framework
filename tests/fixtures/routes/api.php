<?php
declare(strict_types=1);

use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Http\RestRouter;

return static function (RestRouter $router, PluginContext $context): void {
    $router->get($context->restNamespace(), '/fixture', static fn (): array => ['ok' => true]);
};
