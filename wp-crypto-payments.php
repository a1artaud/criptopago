<?php
/**
 * Plugin Name: Criptopago
 * Plugin URI: https://github.com/yourusername/criptopago
 * Description: Accept Bitcoin and other cryptocurrencies in your Brazilian WooCommerce store
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: wp-crypto-payments
 * Domain Path: /languages
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Requires at least: 5.0
 *
 * @package Criptopago
 */

defined('ABSPATH') || exit;

/**
 * Declare HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Initialize the gateway class
 */
function criptopago_init_gateway_class() {
    // Check if WooCommerce is active
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Include the gateway class
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-crypto-gateway.php';

    // Register the gateway with WooCommerce
    add_filter('woocommerce_payment_gateways', 'criptopago_add_gateway_class');
}

/**
 * Add the gateway to WooCommerce
 */
function criptopago_add_gateway_class($gateways) {
    $gateways[] = 'WC_Gateway_CriptoPago';
    return $gateways;
}

/**
 * Add settings link on plugin page
 */
function criptopago_plugin_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=crypto">' . __('Settings', 'wp-crypto-payments') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Initialize the plugin
add_action('plugins_loaded', 'criptopago_init_gateway_class', 11);

// Add settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'criptopago_plugin_settings_link');