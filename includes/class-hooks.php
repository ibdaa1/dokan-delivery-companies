<?php

/**
 * Hooks Integration Class
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Dokan Delivery Companies Hooks Class
 */
class Dokan_Delivery_Companies_Hooks
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // WooCommerce hooks
        // Register shipping method when WooCommerce initializes
        add_action('woocommerce_shipping_init', array($this, 'maybe_include_shipping_method'));
        add_filter('woocommerce_package_rates', array($this, 'add_delivery_rates'), 10, 2);
        add_action('woocommerce_checkout_order_processed', array($this, 'create_delivery_order'));

        // Dokan hooks
        add_action('dokan_vendor_dashboard_nav', array($this, 'add_delivery_orders_nav'));
        add_action('dokan_vendor_dashboard_content', array($this, 'delivery_orders_content'));

        // User registration hooks
        add_action('user_register', array($this, 'handle_user_registration'));
        add_action('wp_login', array($this, 'handle_user_login'), 10, 2);

        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu_items'));
        add_action('admin_notices', array($this, 'admin_notices'));

        // Email hooks
        add_action('woocommerce_email_before_order_table', array($this, 'add_delivery_info_to_email'), 10, 4);

        // Order status hooks
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 3);
    }

    /**
     * Add delivery shipping option to checkout
     */
    public function add_delivery_shipping_option()
    {
        // Deprecated: shipping method class now handles rate exposure. Keep this
        // method for backward compatibility when the shipping method is not enabled.
        if (apply_filters('dokan_delivery_companies_force_fee', false)) {
            if (! get_option('dokan_delivery_companies_enabled', 'yes')) {
                return;
            }

            $cart = WC()->cart;

            if (! $cart) {
                return;
            }

            $vendors_without_shipping = $this->get_vendors_without_shipping();

            if (empty($vendors_without_shipping)) {
                return;
            }

            $customer_address = $this->get_customer_address();

            if (empty($customer_address['country'])) {
                return;
            }

            $delivery_company = $this->find_delivery_company_for_address($customer_address);

            if (! $delivery_company) {
                return;
            }

            $shipping_cost = $delivery_company->get_shipping_cost($customer_address, $cart->get_total('edit'));

            if ($shipping_cost > 0) {
                $cart->add_fee(__('Delivery Fee', 'dokan-delivery-companies'), $shipping_cost);
            }
        }
    }

    /**
     * Create delivery order after checkout
     */
    public function create_delivery_order($order_id)
    {
        if (! get_option('dokan_delivery_companies_enabled', 'yes')) {
            return;
        }

        $order = wc_get_order($order_id);

        if (! $order) {
            return;
        }

        // Check if any vendor has no shipping method
        $vendors_without_shipping = $this->get_vendors_without_shipping_from_order($order);

        if (empty($vendors_without_shipping)) {
            return;
        }

        // Get customer address
        $customer_address = array(
            'country' => $order->get_shipping_country(),
            'state' => $order->get_shipping_state(),
            'city' => $order->get_shipping_city(),
            'postal_code' => $order->get_shipping_postcode(),
        );

        // Try to determine delivery company from the chosen shipping rate(s) on the order.
        $delivery_company_id = null;

        // Inspect shipping items for meta we attach in the shipping method
        foreach ($order->get_items('shipping') as $shipping_item) {
            // Prefer explicit meta if present (we set this when adding rates)
            $meta_company = $shipping_item->get_meta('delivery_company_id', true);

            if (! empty($meta_company)) {
                $delivery_company_id = absint($meta_company);
                break;
            }
        }

        // Fallback: determine by address using previous logic
        if (empty($delivery_company_id)) {
            $delivery_company = $this->find_delivery_company_for_address($customer_address);

            if (! $delivery_company) {
                return;
            }

            $delivery_company_id = $delivery_company->get_data('id');
        }

        // Create delivery order and log for verification
        Dokan_Delivery_Order_Manager::create_from_wc_order($order_id, $delivery_company_id);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('Dokan Delivery: created delivery order for WC order %d -> delivery_company_id=%s', $order_id, $delivery_company_id));
        }
    }

    /**
     * Add delivery rates to shipping packages
     */
    public function add_delivery_rates($rates, $package)
    {
        if (! get_option('dokan_delivery_companies_enabled', 'yes')) {
            return $rates;
        }

        // If the WooCommerce shipping method is active, leave rate calculation
        // to that method. We add a compatibility fallback: if the shipping method
        // is not registered, provide a single aggregated delivery rate.

        // Check if our shipping method class exists and registered rates already
        // — if so, do nothing here.
        foreach ($rates as $rate_id => $rate_obj) {
            if (strpos($rate_id, 'dokan_delivery_company_shipping') !== false) {
                return $rates;
            }
        }

        // Compatibility fallback: determine a single delivery company and return its rate
        $vendors_without_shipping = $this->get_vendors_without_shipping_from_package($package);

        if (empty($vendors_without_shipping)) {
            return $rates;
        }

        $customer_address = array(
            'country' => isset($package['destination']['country']) ? $package['destination']['country'] : '',
            'state' => isset($package['destination']['state']) ? $package['destination']['state'] : '',
            'city' => isset($package['destination']['city']) ? $package['destination']['city'] : '',
            'postal_code' => isset($package['destination']['postcode']) ? $package['destination']['postcode'] : '',
        );

        $delivery_company = $this->find_delivery_company_for_address($customer_address);

        if (! $delivery_company) {
            return $rates;
        }

        $shipping_cost = $delivery_company->get_shipping_cost($customer_address, $package['contents_cost']);

        if ($shipping_cost > 0) {
            $method_id = 'dokan_delivery_company_shipping';
            $rate = new WC_Shipping_Rate(
                $method_id,
                $delivery_company->get_data('company_name') ?: __('Delivery Company', 'dokan-delivery-companies'),
                $shipping_cost,
                array(),
                $method_id
            );

            $rates[$method_id] = $rate;
        }

        return $rates;
    }

    /**
     * Include shipping method file if available
     */
    public function maybe_include_shipping_method()
    {
        $file = DOKAN_DELIVERY_COMPANIES_PATH . '/includes/class-shipping-method.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }

    /**
     * Add delivery orders navigation to vendor dashboard
     */
    public function add_delivery_orders_nav($navs)
    {
        $navs['delivery-orders'] = array(
            'title' => __('Delivery Orders', 'dokan-delivery-companies'),
            'icon' => '<i class="fas fa-truck"></i>',
            'url' => dokan_get_navigation_url('delivery-orders'),
            'pos' => 50,
        );

        return $navs;
    }

    /**
     * Delivery orders content for vendor dashboard
     */
    public function delivery_orders_content()
    {
        if (! isset($_GET['delivery-orders'])) {
            return;
        }

        $vendor_id = dokan_get_current_user_id();
        $orders = Dokan_Delivery_Order_Manager::get_by_vendor_id($vendor_id);

?>
        <div class="dokan-delivery-orders">
            <h2><?php _e('Delivery Orders', 'dokan-delivery-companies'); ?></h2>

            <?php if (empty($orders)) : ?>
                <p><?php _e('No delivery orders found.', 'dokan-delivery-companies'); ?></p>
            <?php else : ?>
                <table class="dokan-table">
                    <thead>
                        <tr>
                            <th><?php _e('Order ID', 'dokan-delivery-companies'); ?></th>
                            <th><?php _e('Delivery Company', 'dokan-delivery-companies'); ?></th>
                            <th><?php _e('Status', 'dokan-delivery-companies'); ?></th>
                            <th><?php _e('Shipping Cost', 'dokan-delivery-companies'); ?></th>
                            <th><?php _e('Created', 'dokan-delivery-companies'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order) : ?>
                            <?php
                            $delivery_company = new Dokan_Delivery_Company($order->delivery_company_id);
                            $wc_order = wc_get_order($order->order_id);
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(dokan_get_navigation_url('orders') . '?order_id=' . $order->order_id); ?>">
                                        #<?php echo esc_html($wc_order ? $wc_order->get_order_number() : $order->order_id); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($delivery_company->get_data('company_name')); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($order->status); ?>">
                                        <?php echo esc_html(ucfirst($order->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo wc_price($order->shipping_cost); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($order->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php
    }

    /**
     * Handle user registration
     */
    public function handle_user_registration($user_id)
    {
        // Check if user registered as delivery company
        if (isset($_POST['user_role']) && $_POST['user_role'] === 'delivery_company') {
            // Set user role
            $user = new WP_User($user_id);
            $user->set_role('delivery_company');
        }
    }

    /**
     * Handle user login
     */
    public function handle_user_login($user_login, $user)
    {
        // Check if user is delivery company
        if (in_array('delivery_company', $user->roles)) {
            // Redirect to delivery company dashboard
            wp_redirect(home_url('/delivery-company-dashboard/'));
            exit;
        }
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu_items()
    {
        // Add submenu to WooCommerce orders
        add_submenu_page(
            'woocommerce',
            __('Delivery Orders', 'dokan-delivery-companies'),
            __('Delivery Orders', 'dokan-delivery-companies'),
            'manage_woocommerce',
            'delivery-orders',
            array($this, 'delivery_orders_admin_page')
        );
    }

    /**
     * Delivery orders admin page
     */
    public function delivery_orders_admin_page()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_orders_table');
        $orders = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 50");

    ?>
        <div class="wrap">
            <h1><?php _e('Delivery Orders', 'dokan-delivery-companies'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Order ID', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Delivery Company', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Vendor', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Status', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Shipping Cost', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Created', 'dokan-delivery-companies'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)) : ?>
                        <tr>
                            <td colspan="6"><?php _e('No delivery orders found.', 'dokan-delivery-companies'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($orders as $order) : ?>
                            <?php
                            $delivery_company = new Dokan_Delivery_Company($order->delivery_company_id);
                            $vendor = get_user_by('id', $order->vendor_id);
                            $wc_order = wc_get_order($order->order_id);
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->order_id . '&action=edit')); ?>">
                                        #<?php echo esc_html($wc_order ? $wc_order->get_order_number() : $order->order_id); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($delivery_company->get_data('company_name')); ?></td>
                                <td><?php echo esc_html($vendor ? $vendor->display_name : 'N/A'); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($order->status); ?>">
                                        <?php echo esc_html(ucfirst($order->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo wc_price($order->shipping_cost); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($order->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Admin notices
     */
    public function admin_notices()
    {
        if (! get_option('dokan_delivery_companies_enabled', 'yes')) {
            return;
        }

        $screen = get_current_screen();

        if ($screen && strpos($screen->id, 'dokan-delivery-companies') !== false) {
            $companies_count = count(Dokan_Delivery_Company::get_active_companies());

            if ($companies_count === 0) {
        ?>
                <div class="notice notice-warning">
                    <p><?php _e('No active delivery companies found. Please add delivery companies to enable the functionality.', 'dokan-delivery-companies'); ?></p>
                </div>
            <?php
            }
        }
    }

    /**
     * Add delivery info to email
     */
    public function add_delivery_info_to_email($order, $sent_to_admin, $plain_text, $email)
    {
        if (! get_option('dokan_delivery_companies_enabled', 'yes')) {
            return;
        }

        global $wpdb;

        $table_name = get_option('dokan_delivery_orders_table');
        $delivery_order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE order_id = %d",
            $order->get_id()
        ));

        if (! $delivery_order) {
            return;
        }

        $delivery_company = new Dokan_Delivery_Company($delivery_order->delivery_company_id);

        if ($plain_text) {
            echo "\n" . __('Delivery Information:', 'dokan-delivery-companies') . "\n";
            echo __('Delivery Company:', 'dokan-delivery-companies') . ' ' . $delivery_company->get_data('company_name') . "\n";
            echo __('Status:', 'dokan-delivery-companies') . ' ' . ucfirst($delivery_order->status) . "\n";
            echo __('Tracking Number:', 'dokan-delivery-companies') . ' ' . ($delivery_order->tracking_number ?: __('Not available', 'dokan-delivery-companies')) . "\n";
        } else {
            ?>
            <h3><?php _e('Delivery Information', 'dokan-delivery-companies'); ?></h3>
            <table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1">
                <tr>
                    <th style="text-align:left; border: 1px solid #eee;"><?php _e('Delivery Company', 'dokan-delivery-companies'); ?></th>
                    <td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html($delivery_company->get_data('company_name')); ?></td>
                </tr>
                <tr>
                    <th style="text-align:left; border: 1px solid #eee;"><?php _e('Status', 'dokan-delivery-companies'); ?></th>
                    <td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html(ucfirst($delivery_order->status)); ?></td>
                </tr>
                <tr>
                    <th style="text-align:left; border: 1px solid #eee;"><?php _e('Tracking Number', 'dokan-delivery-companies'); ?></th>
                    <td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html($delivery_order->tracking_number ?: __('Not available', 'dokan-delivery-companies')); ?></td>
                </tr>
            </table>
<?php
        }
    }

    /**
     * Handle order status change
     */
    public function handle_order_status_change($order_id, $old_status, $new_status)
    {
        if (! get_option('dokan_delivery_companies_enabled', 'yes')) {
            return;
        }

        // If order is cancelled, cancel delivery order
        if ($new_status === 'cancelled') {
            global $wpdb;

            $table_name = get_option('dokan_delivery_orders_table');
            $wpdb->update(
                $table_name,
                array('status' => 'cancelled'),
                array('order_id' => $order_id),
                array('%s'),
                array('%d')
            );
        }
    }

    /**
     * Get vendors without shipping methods
     *
     * @return array
     */
    private function get_vendors_without_shipping()
    {
        $cart = WC()->cart;
        $vendors_without_shipping = array();

        foreach ($cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $vendor_id = dokan_get_vendor_by_product($product_id);

            if ($vendor_id) {
                $shipping_methods = $this->dokan_get_vendor_shipping_methods($vendor_id);

                if (empty($shipping_methods)) {
                    $vendors_without_shipping[] = $vendor_id;
                }
            }
        }

        return array_unique($vendors_without_shipping);
    }

    /**
     * Get vendors without shipping methods from order
     *
     * @param WC_Order $order
     * @return array
     */
    private function get_vendors_without_shipping_from_order($order)
    {
        $vendors_without_shipping = array();

        foreach ($order->get_items() as $item) {
            /** @var WC_Order_Item_Product $item */
            $product_id = $item->get_product_id();
            $vendor_id = dokan_get_vendor_by_product($product_id);

            if ($vendor_id) {
                $shipping_methods = $this->dokan_get_vendor_shipping_methods($vendor_id);

                if (empty($shipping_methods)) {
                    $vendors_without_shipping[] = $vendor_id;
                }
            }
        }

        return array_unique($vendors_without_shipping);
    }

    /**
     * Get vendors without shipping methods from package
     *
     * @param array $package
     * @return array
     */
    private function get_vendors_without_shipping_from_package($package)
    {
        $vendors_without_shipping = array();

        foreach ($package['contents'] as $item) {
            $product_id = $item['product_id'];
            $vendor_id = dokan_get_vendor_by_product($product_id);

            if ($vendor_id) {
                $shipping_methods = $this->dokan_get_vendor_shipping_methods($vendor_id);

                if (empty($shipping_methods)) {
                    $vendors_without_shipping[] = $vendor_id;
                }
            }
        }

        return array_unique($vendors_without_shipping);
    }

    /**
     * Get customer address
     *
     * @return array
     */
    private function get_customer_address()
    {
        return array(
            'country' => WC()->checkout()->get_value('shipping_country'),
            'state' => WC()->checkout()->get_value('shipping_state'),
            'city' => WC()->checkout()->get_value('shipping_city'),
            'postal_code' => WC()->checkout()->get_value('shipping_postcode'),
        );
    }

    /**
     * Find delivery company for address
     *
     * @param array $address
     * @return Dokan_Delivery_Company|null
     */
    private function find_delivery_company_for_address($address)
    {
        $companies = Dokan_Delivery_Company::get_active_companies();

        foreach ($companies as $company_data) {
            $company = new Dokan_Delivery_Company($company_data->id);

            if ($company->serves_address($address)) {
                return $company;
            }
        }

        return null;
    }

    /**
     * Get vendor shipping methods (helper function)
     *
     * @param int $vendor_id Vendor ID
     * @return array
     */
    private function dokan_get_vendor_shipping_methods($vendor_id)
    {
        // Check if vendor has any shipping methods configured
        // This is a simplified check - in a real implementation, you'd check
        // the vendor's shipping settings, zones, etc.

        $vendor = dokan()->vendor->get($vendor_id);

        if (! $vendor) {
            return array();
        }

        // Check if vendor has shipping enabled
        $shipping_enabled = $vendor->get_shipping_enabled();

        if (! $shipping_enabled) {
            return array();
        }

        // Check if vendor has shipping zones
        $shipping_zones = $vendor->get_shipping_zones();

        if (empty($shipping_zones)) {
            return array();
        }

        // Return shipping methods if vendor has configured them
        return $shipping_zones;
    }
}
