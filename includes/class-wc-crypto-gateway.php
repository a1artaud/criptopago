<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Criptopago Payment Gateway
 *
 * @class WC_Gateway_CriptoPago
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_CriptoPago extends WC_Payment_Gateway {
    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id = 'crypto';
        $this->icon = ''; // URL to the icon
        $this->has_fields = true;
        $this->method_title = 'Criptopago';
        $this->method_description = __('Accept cryptocurrency payments in your store', 'wp-crypto-payments');

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Define properties
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->bitcoin_address = $this->get_option('bitcoin_address');
        $this->price_api = $this->get_option('price_api', 'coingecko');
        $this->price_valid_minutes = $this->get_option('price_valid_minutes', 15);

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thank_you_page'));
    }

    /**
     * Check if this gateway is available for the current cart/order.
     *
     * @return bool
     */
    public function is_available() {
        // Basic availability check
        if ('yes' !== $this->enabled) {
            return false;
        }

        // Check if we have a Bitcoin address configured
        if (empty($this->bitcoin_address)) {
            return false;
        }

        // Check if we're on checkout or in admin
        if (is_admin()) {
            return true;
        }

        // Get the order total
        $total = 0;
        if (isset(WC()->cart)) {
            $total = WC()->cart->get_total('');
        }

        // Don't show gateway for zero-value carts
        if ($total <= 0) {
            return false;
        }

        // Check if we can get a Bitcoin price
        if (!$this->get_btc_price()) {
            return false;
        }

        // Check if the store currency is BRL
        if ('BRL' !== get_woocommerce_currency()) {
            return false;
        }

        return true;
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'wp-crypto-payments'),
                'type' => 'checkbox',
                'label' => __('Enable Criptopago', 'wp-crypto-payments'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'wp-crypto-payments'),
                'type' => 'text',
                'description' => __('Payment method title that the customer will see on your checkout.', 'wp-crypto-payments'),
                'default' => __('Criptopago', 'wp-crypto-payments'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'wp-crypto-payments'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'wp-crypto-payments'),
                'default' => __('Pay with Bitcoin or other cryptocurrencies', 'wp-crypto-payments'),
                'desc_tip' => true,
            ),
            'bitcoin_address' => array(
                'title' => __('Bitcoin Address', 'wp-crypto-payments'),
                'type' => 'text',
                'description' => __('Your Bitcoin wallet address where payments will be sent.', 'wp-crypto-payments'),
                'default' => '',
                'desc_tip' => true,
            ),
            'price_api' => array(
                'title' => __('Price API', 'wp-crypto-payments'),
                'type' => 'select',
                'description' => __('Choose which API to use for Bitcoin price conversion.', 'wp-crypto-payments'),
                'options' => array(
                    'coingecko' => 'CoinGecko',
                    'binance' => 'Binance',
                ),
                'default' => 'coingecko',
                'desc_tip' => true,
            ),
            'price_valid_minutes' => array(
                'title' => __('Price Valid Duration', 'wp-crypto-payments'),
                'type' => 'number',
                'description' => __('How many minutes the Bitcoin price is valid for payment.', 'wp-crypto-payments'),
                'default' => 15,
                'desc_tip' => true,
            ),
        );
    }

    /**
     * Get Bitcoin price from API
     */
    private function get_btc_price() {
        $api_url = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=brl';
        
        $response = wp_remote_get($api_url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['bitcoin']['brl'])) {
            return $data['bitcoin']['brl'];
        }
        
        return false;
    }

    /**
     * Calculate BTC amount from order total
     */
    private function calculate_btc_amount($order_total) {
        $btc_price = $this->get_btc_price();
        
        if (!$btc_price) {
            return false;
        }
        
        return $order_total / $btc_price;
    }

    /**
     * Payment fields displayed on the checkout page
     */
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        
        $cart_total = WC()->cart ? WC()->cart->get_total('') : 0;
        $btc_amount = $this->calculate_btc_amount($cart_total);
        
        if ($btc_amount) {
            ?>
            <div id="crypto-payment-form">
                <p class="form-row">
                    <?php 
                    printf(
                        __('Total amount in BTC: %s (valid for %d minutes)', 'wp-crypto-payments'),
                        number_format($btc_amount, 8),
                        $this->price_valid_minutes
                    ); 
                    ?>
                </p>
                <p class="form-row">
                    <?php _e('The exact BTC amount will be shown after order placement.', 'wp-crypto-payments'); ?>
                </p>
            </div>
            <?php
        } else {
            ?>
            <div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
                <ul class="woocommerce-error">
                    <li><?php _e('Unable to fetch Bitcoin price. Please try again later.', 'wp-crypto-payments'); ?></li>
                </ul>
            </div>
            <?php
        }
    }

    /**
     * Process the payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $btc_amount = $this->calculate_btc_amount($order->get_total());
        
        if (!$btc_amount) {
            wc_add_notice(__('Unable to calculate Bitcoin amount. Please try again.', 'wp-crypto-payments'), 'error');
            return;
        }
        
        // Store BTC amount and current time in order meta
        $order->update_meta_data('_btc_amount', $btc_amount);
        $order->update_meta_data('_btc_price_time', current_time('timestamp'));
        $order->save();

        // Mark as pending payment
        $order->update_status('on-hold', __('Awaiting cryptocurrency payment', 'wp-crypto-payments'));

        // Reduce stock levels
        wc_reduce_stock_levels($order_id);

        // Remove cart
        WC()->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    /**
     * Thank you page
     */
    public function thank_you_page($order_id) {
        $order = wc_get_order($order_id);
        if ($order->get_payment_method() === $this->id) {
            $btc_amount = $order->get_meta('_btc_amount');
            $price_time = $order->get_meta('_btc_price_time');
            $valid_until = $price_time + ($this->price_valid_minutes * 60);
            $now = current_time('timestamp');
            
            ?>
            <h2><?php _e('Payment Instructions', 'wp-crypto-payments'); ?></h2>
            
            <?php if ($now <= $valid_until): ?>
                <p><?php _e('Please send exactly this amount in BTC:', 'wp-crypto-payments'); ?></p>
                <p class="btc-amount"><strong><?php echo number_format($btc_amount, 8); ?> BTC</strong></p>
                <p><?php _e('To this Bitcoin address:', 'wp-crypto-payments'); ?></p>
                <p class="btc-address"><strong><?php echo esc_html($this->bitcoin_address); ?></strong></p>
                <p class="btc-timer">
                    <?php 
                    printf(
                        __('This price is valid until: %s', 'wp-crypto-payments'),
                        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $valid_until)
                    ); 
                    ?>
                </p>
            <?php else: ?>
                <p class="woocommerce-error">
                    <?php _e('The payment window has expired. Please contact the store for updated payment information.', 'wp-crypto-payments'); ?>
                </p>
            <?php endif; ?>
            
            <p><?php _e('Your order will be processed once the payment is confirmed on the blockchain.', 'wp-crypto-payments'); ?></p>
            <?php
        }
    }
}