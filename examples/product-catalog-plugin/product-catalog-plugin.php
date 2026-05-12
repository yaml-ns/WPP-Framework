<?php
/**
 * Plugin Name: Product Catalog Plugin
 * Description: Example plugin that creates and displays a product catalog.
 * Version: 1.0.0
 * Author: Yaml Ns
 * Requires PHP: 8.1
 */

declare(strict_types=1);

use YamlNs\WppFramework\Wpp;

if (!defined('ABSPATH')) {
    exit;
}

$pluginFile = __FILE__;
$pluginDir = plugin_dir_path($pluginFile);

require_once $pluginDir . 'vendor/autoload.php';

$config = require $pluginDir . 'config/plugin.php';

register_activation_hook($pluginFile, static function () use ($pluginFile, $config): void {
    Wpp::activate($pluginFile, $config);
});

add_action('plugins_loaded', static function () use ($pluginFile, $config): void {
    Wpp::boot($pluginFile, $config);
});

register_deactivation_hook($pluginFile, static function () use ($pluginFile, $config): void {
    Wpp::deactivate($pluginFile, $config);
});
