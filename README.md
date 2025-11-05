# Dokan Delivery Companies Plugin

A comprehensive WordPress plugin that extends Dokan marketplace functionality with automated delivery company management.

## Features

### 🚚 Delivery Company Management
- **User Registration**: Dedicated registration system for delivery companies
- **Company Profiles**: Complete company information management
- **Status Management**: Admin approval system for delivery companies
- **Role-based Access**: Custom user roles and capabilities

### 📍 Shipping Zone Management
- **Flexible Zones**: Support for country, state, city, and postal code zones
- **Rate Configuration**: Set shipping rates and free shipping thresholds
- **Delivery Estimates**: Configure estimated delivery days
- **Zone Validation**: Comprehensive validation system

### 🛒 Checkout Integration
- **Automatic Detection**: Detects vendors without shipping methods
- **Smart Assignment**: Finds appropriate delivery companies for customer addresses
- **Dynamic Pricing**: Calculates shipping costs based on zones and thresholds
- **Seamless Experience**: Integrates with WooCommerce checkout process

### 📦 Order Management
- **Order Tracking**: Complete order lifecycle management
- **Status Updates**: Real-time status updates (pending, assigned, picked up, in transit, delivered)
- **Notifications**: Email notifications for all stakeholders
- **Tracking Numbers**: Optional tracking number support

### 💰 Payout System
- **Earnings Tracking**: Complete earnings and commission management
- **Multiple Methods**: Support for bank transfer, PayPal, and manual payouts
- **Commission Control**: Configurable commission rates
- **Payout Requests**: Self-service payout request system

### 🎛️ Admin Interface
- **Company Management**: Approve, activate, and manage delivery companies
- **Settings Panel**: Configure plugin behavior and commission rates
- **Order Overview**: Monitor all delivery orders
- **Reporting**: Track earnings and performance

## Installation

1. **Upload Plugin**: Place the `dokan-delivery-companies` folder in `/wp-content/plugins/`
2. **Activate**: Activate the plugin through the WordPress admin
3. **Configure**: Go to Delivery Companies > Settings to configure the plugin
4. **Add Companies**: Create delivery company accounts through the admin or registration page

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- Dokan Lite/Pro
- PHP 7.4+

## Usage

### For Delivery Companies

1. **Registration**: Visit `/delivery-company-registration/` to apply
2. **Dashboard**: Access `/delivery-company-dashboard/` after approval
3. **Shipping Zones**: Set up service areas and rates
4. **Order Management**: Accept and track delivery orders
5. **Earnings**: Monitor and request payouts

### For Vendors

1. **No Setup Required**: Vendors don't need to configure shipping
2. **Order Notifications**: Receive notifications when orders are assigned
3. **Order Tracking**: Monitor delivery status through vendor dashboard

### For Customers

1. **Seamless Checkout**: Delivery options appear automatically
2. **Transparent Pricing**: See delivery costs before checkout
3. **Order Tracking**: Receive updates on delivery status

### For Administrators

1. **Company Approval**: Review and approve delivery company applications
2. **Settings Management**: Configure commission rates and behavior
3. **Order Monitoring**: Track all delivery orders and performance
4. **Payout Processing**: Handle delivery company payouts

## Database Schema

The plugin creates four main tables:

- `wp_dokan_delivery_companies`: Company information
- `wp_dokan_delivery_shipping_zones`: Shipping zones and rates
- `wp_dokan_delivery_orders`: Order tracking and management
- `wp_dokan_delivery_earnings`: Earnings and payout tracking

## Hooks and Filters

### Actions
- `dokan_delivery_order_created`: Fired when a delivery order is created
- `dokan_delivery_order_status_changed`: Fired when order status changes
- `dokan_delivery_payout_processed`: Fired when payout is processed

### Filters
- `dokan_delivery_shipping_cost`: Modify shipping cost calculation
- `dokan_delivery_commission_rate`: Modify commission rate
- `dokan_delivery_company_status`: Modify company status logic

## Configuration

### Settings

- **Enable Delivery Companies**: Toggle plugin functionality
- **Commission Rate**: Set commission percentage for delivery companies
- **Auto Assign Orders**: Automatically assign orders to delivery companies
- **Notification Email**: Email for delivery company notifications

### Shipping Zone Types

- **Country**: Service entire countries (e.g., US, CA)
- **State**: Service specific states/provinces (e.g., CA, NY)
- **City**: Service specific cities (e.g., New York, Los Angeles)
- **Postal Code**: Service specific postal codes (e.g., 10001, 10002)

## API Endpoints

### AJAX Actions
- `dokan_delivery_add_shipping_zone`: Add new shipping zone
- `dokan_delivery_update_shipping_zone`: Update existing zone
- `dokan_delivery_delete_shipping_zone`: Delete shipping zone
- `dokan_delivery_update_order_status`: Update order status
- `dokan_delivery_process_payout`: Process payout request
- `dokan_delivery_get_states`: Get states for country

## Customization

### Templates
Override templates by copying them to your theme:
- `templates/delivery-company-registration.php`
- `templates/delivery-company-dashboard.php`

### Styling
Customize appearance by overriding CSS:
- `assets/css/delivery-companies.css`

### JavaScript
Extend functionality by modifying:
- `assets/js/delivery-companies.js`

## Troubleshooting

### Common Issues

1. **Delivery companies not appearing**: Check if companies are approved and active
2. **Shipping zones not working**: Verify zone configuration and customer address
3. **Orders not assigning**: Check auto-assign settings and zone coverage
4. **Payout issues**: Verify payout method configuration

### Debug Mode

Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support and feature requests, please contact the plugin developer or submit issues through the appropriate channels.

## Changelog

### Version 1.0.0
- Initial release
- Delivery company registration and management
- Shipping zone configuration
- Order management system
- Payout processing
- Admin interface
- Frontend dashboard

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for Dokan marketplace platform integration.
