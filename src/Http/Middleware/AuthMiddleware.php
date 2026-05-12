<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Middleware;

use WP_Error;
use WP_REST_Request;
use YamlNs\WppFramework\Contracts\Middleware;

final class AuthMiddleware implements Middleware
{
    public function handle(WP_REST_Request $request): bool|WP_Error
    {
        if (!is_user_logged_in()) {
            return new WP_Error('wpp_unauthorized', 'Authentication required.', ['status' => 401]);
        }

        return true;
    }
}
