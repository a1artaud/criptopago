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

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

/**
 * Declare HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

if (!class_exists('WP_Crypto_Payments')) {
    class WP_Crypto_Payments {
        public function __construct() {
            add_action('plugins_loaded', array($this, 'init'));
        }

        public function init() {
            // Load plugin text domain
            load_plugin_textdomain('wp-crypto-payments', false, dirname(plugin_basename(__FILE__)) . '/languages');

            // Include the gateway class
            require_once plugin_dir_path(__FILE__) . 'includes/class-wc-crypto-gateway.php';

            // Add the gateway to WooCommerce
            add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_class'));

            // Add settings link on plugin page
            $plugin = plugin_basename(__FILE__);
            add_filter("plugin_action_links_$plugin", array($this, 'plugin_settings_link'));
        }

        public function add_gateway_class($gateways) {
            $gateways[] = 'WC_Crypto_Gateway';
            return $gateways;
        }

        public function plugin_settings_link($links) {
            $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=crypto">' . __('Settings', 'wp-crypto-payments') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }
    }

    // Initialize the plugin
    add_action('plugins_loaded', function() {
        new WP_Crypto_Payments();
    });
}