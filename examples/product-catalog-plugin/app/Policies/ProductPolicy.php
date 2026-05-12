<?php
declare(strict_types=1);

namespace ProductCatalogPlugin\Policies;

use YamlNs\WppFramework\Auth\ResourcePolicy;

final class ProductPolicy extends ResourcePolicy
{
    protected function createCapability(): ?string
    {
        return 'manage_products';
    }

    protected function updateCapability(): ?string
    {
        return 'manage_products';
    }

    protected function deleteCapability(): ?string
    {
        return 'manage_products';
    }
}
