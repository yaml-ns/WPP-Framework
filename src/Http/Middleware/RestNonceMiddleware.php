<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Http\Middleware;

use WP_Error;
use WP_REST_Request;
use YamlNs\WppFramework\Contracts\Middleware;

/**
 * Explicitly verifies the WordPress REST nonce (X-WP-Nonce: wp_rest).
 *
 * Use this only in non-standard contexts. For regular browser REST requests
 * with credentials, WordPress already verifies this nonce through the auth
 * cookie. This middleware is useful for contexts without cookies, such as
 * iframes, hybrid mobile apps or custom AJAX endpoints proxied through REST.
 *
 * For standard protection, prefer AuthMiddleware or CapabilityMiddleware.
 */
final class RestNonceMiddleware implements Middleware
{
    public function handle(WP_REST_Request $request): bool|WP_Error
    {
        $nonce = $request->get_header('X-WP-Nonce');

        if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('wpp_invalid_nonce', 'Invalid REST nonce.', ['status' => 403]);
        }

        return true;
    }
}
