<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Middleware;

use WP_Error;
use WP_REST_Request;
use YamlNs\WppFramework\Contracts\Middleware;

final class AdminMiddleware implements Middleware
{
    public function handle(WP_REST_Request $request): bool|WP_Error
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error('wpp_forbidden', 'Admin access required.', ['status' => 403]);
        }

        return true;
    }
}
