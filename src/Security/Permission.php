<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Security;

use WP_Error;

final class Permission
{
    public function can(string $capability): bool
    {
        return current_user_can($capability);
    }

    public function require(string $capability): void
    {
        if (!$this->can($capability)) {
            throw new \RuntimeException('Forbidden.');
        }
    }

    public function rest(string $capability): bool|WP_Error
    {
        if (!$this->can($capability)) {
            return new WP_Error('wpp_forbidden', 'Forbidden.', ['status' => 403]);
        }

        return true;
    }

    public function assertOwner(int $ownerId): void
    {
        if ((int) get_current_user_id() !== $ownerId && !current_user_can('manage_options')) {
            throw new \RuntimeException('Forbidden.');
        }
    }
}
