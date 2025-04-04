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
 */

defined('ABSPATH') || exit;

if (!class_exists('WP_Crypto_Payments')) {
    class WP_Crypto_Payments {
        public function __construct() {
            add_action('plugins_loaded', array($this, 'init'));
        }

        public function init() {
            // Check if WooCommerce is active
            if (!class_exists('WC_Payment_Gateway')) {
                add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
                return;
            }

            // Load plugin text domain
            load_plugin_textdomain('wp-crypto-payments', false, dirname(plugin_basename(__FILE__)) . '/languages');

            // Add the gateway to WooCommerce
            add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_class'));

            // Add settings link on plugin page
            $plugin = plugin_basename(__FILE__);
            add_filter("plugin_action_links_$plugin", array($this, 'plugin_settings_link'));
        }

        public function woocommerce_missing_notice() {
            ?>
            <div class="error">
                <p><?php _e('Criptopago requires WooCommerce to be installed and active.', 'wp-crypto-payments'); ?></p>
            </div>
            <?php
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
    new WP_Crypto_Payments();

    // Include the gateway class
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-crypto-gateway.php';
}