<?php
/**
 * Plugin Name: WebP Inplace Converter
 * Plugin URI: https://ecommy.com/webp-inplace-converter
 * Description: Converts and serves images in WebP format
 * Version: 1.0.6
 * Author: ecommy - your ecommerce experts
 * Author URI: https://ecommy.com
 * Text Domain: webp-inplace-converter
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WEBP_INPLACE_CONVERTER_VERSION', '1.0.6');
define('WEBP_INPLACE_CONVERTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEBP_INPLACE_CONVERTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load required files
require_once WEBP_INPLACE_CONVERTER_PLUGIN_DIR . 'includes/class-image-processor.php';
require_once WEBP_INPLACE_CONVERTER_PLUGIN_DIR . 'includes/class-wordpress-integration.php';
require_once WEBP_INPLACE_CONVERTER_PLUGIN_DIR . 'includes/class-webp-inplace-converter.php';
require_once WEBP_INPLACE_CONVERTER_PLUGIN_DIR . 'admin/class-admin.php';

// Register activation/deactivation hooks
register_activation_hook(__FILE__, array('WebP_Inplace_Converter', 'activate'));
register_deactivation_hook(__FILE__, array('WebP_Inplace_Converter', 'deactivate'));

// Initialize the plugin
function run_webp_inplace_converter() {
    $plugin = new WebP_Inplace_Converter();
    $plugin->init();
}
run_webp_inplace_converter();
