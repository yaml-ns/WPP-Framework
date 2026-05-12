<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Providers;

use YamlNs\WppFramework\Security\Escaper;
use YamlNs\WppFramework\Security\Nonce;
use YamlNs\WppFramework\Security\Permission;
use YamlNs\WppFramework\Security\Sanitizer;

final class SecurityServiceProvider extends ServiceProvider
{
    // Sanitizer, Escaper, Nonce and Permission have constructors without
    // dependencies. The container resolves them automatically through reflection.
    // No explicit binding is required.
    //
    // To bind an interface to a concrete implementation, use:
    // $this->container->bind(SanitizerInterface::class, Sanitizer::class);
}
