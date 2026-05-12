<?php

declare(strict_types=1);

use YamlNs\WppFramework\Wpp;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$pluginFile = __DIR__ . '/product-catalog-plugin.php';

require_once __DIR__ . '/vendor/autoload.php';

Wpp::uninstall($pluginFile, require __DIR__ . '/config/plugin.php');
