<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Controllers;

use YamlNs\WppFramework\Contracts\RestController;
use YamlNs\WppFramework\Core\PluginContext;
use YamlNs\WppFramework\Http\Middleware\AdminMiddleware;
use YamlNs\WppFramework\Http\RestRouter;
use WP_REST_Response;

final class ExampleProtectedController extends BaseRestController implements RestController
{
    public function __construct(private RestRouter $router, PluginContext $context)
    {
        parent::__construct($context);
    }

    public function registerRoutes(): void
    {
        $this->router->get(
            $this->namespace,
            '/admin-example',
            [$this, 'index'],
            [AdminMiddleware::class]
        );
    }

    public function index(): WP_REST_Response
    {
        return $this->ok([
            'message' => 'Only admins can see this.',
        ]);
    }
}
