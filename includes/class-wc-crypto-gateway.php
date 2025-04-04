<?php
class WC_Crypto_Gateway extends WC_Payment_Gateway {
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

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thank_you_page'));
    }

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
        );
    }

    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        ?>
        <div id="crypto-payment-form">
            <p class="form-row">
                <?php _e('You will receive payment instructions after placing the order.', 'wp-crypto-payments'); ?>
            </p>
        </div>
        <?php
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

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

    public function thank_you_page($order_id) {
        $order = wc_get_order($order_id);
        if ($order->get_payment_method() === $this->id) {
            ?>
            <h2><?php _e('Payment Instructions', 'wp-crypto-payments'); ?></h2>
            <p><?php _e('Please send the exact amount in BTC to the following address:', 'wp-crypto-payments'); ?></p>
            <p><strong><?php echo esc_html($this->bitcoin_address); ?></strong></p>
            <p><?php _e('Your order will be processed once the payment is confirmed on the blockchain.', 'wp-crypto-payments'); ?></p>
            <?php
        }
    }
}