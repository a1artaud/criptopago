# Criptopago

A WordPress plugin that enables Brazilian e-commerce merchants to accept cryptocurrency payments in their WooCommerce stores.

## Features

- Accept Bitcoin (BTC) payments
- Simple integration with WooCommerce
- Portuguese (Brazil) language support
- Clear payment instructions for customers
- Easy setup for merchants

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Installation

1. Upload the `wp-crypto-payments` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > Settings > Payments
4. Enable "Criptopago" and configure your Bitcoin wallet address

## Configuration

1. Enable/Disable: Turn the payment method on or off
2. Title: Set the payment method name displayed to customers
3. Description: Add a description for the payment method
4. Bitcoin Address: Enter your Bitcoin wallet address to receive payments

## Usage

After installation and configuration:

1. Customers can select cryptocurrency payment during checkout
2. They will receive payment instructions with your Bitcoin address
3. Orders will be marked as "on-hold" until payment is confirmed
4. Merchants should manually verify payments and update order status

## Security Considerations

- Always verify that payments match the order amount
- Consider implementing additional verification steps
- Keep your wallet address information secure
- Regularly check for plugin updates

## Support

For support, please open an issue on the GitHub repository or contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.