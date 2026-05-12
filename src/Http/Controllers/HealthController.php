<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Controllers;

use WP_REST_Response;
use YamlNs\WppFramework\Contracts\RestController;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Http\RestRouter;

final class HealthController extends BaseRestController implements RestController
{
    public function __construct(private RestRouter $router, private PluginContext $context)
    {
        parent::__construct($context);
    }

    public function registerRoutes(): void
    {
        $this->router->get(
            $this->namespace,
            '/health',
            [$this, 'health'],
        );
    }

    public function health(): WP_REST_Response
    {
        return $this->ok([
            'status' => 'ok',
            'framework' => $this->context->name(),
            'version' => $this->context->version(),
        ]);
    }
}
