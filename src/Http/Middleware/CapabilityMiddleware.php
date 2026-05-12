<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Middleware;

use YamlNs\WppFramework\Contracts\Middleware;
use WP_Error;
use WP_REST_Request;

/**
 * Checks that the current user has a WordPress capability.
 *
 * Usage with the default capability ('read'): can be resolved by the container.
 *
 *   $this->router->get('wpp/v1', '/items', [$this, 'index'], [CapabilityMiddleware::class]);
 *
 * Usage with a custom capability: instantiate it manually.
 *
 *   $this->router->get('wpp/v1', '/items', [$this, 'index'], [new CapabilityMiddleware('edit_posts')]);
 */
final class CapabilityMiddleware implements Middleware
{
    public function __construct(private readonly string $capability = 'read') {}

    public function handle(WP_REST_Request $request): bool|WP_Error
    {
        if (!current_user_can($this->capability)) {
            return new WP_Error('wpp_forbidden', 'Forbidden.', ['status' => 403]);
        }

        return true;
    }
}
