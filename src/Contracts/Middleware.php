<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Contracts;

use WP_Error;
use WP_REST_Request;

interface Middleware
{
    public function handle(WP_REST_Request $request): bool|WP_Error;
}
