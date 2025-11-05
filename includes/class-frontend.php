<?php

/**
 * Frontend Interface Class
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Dokan Delivery Companies Frontend Class
 */
class Dokan_Delivery_Companies_Frontend
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('template_redirect', array($this, 'handle_delivery_company_registration'));
        add_action('woocommerce_checkout_process', array($this, 'handle_checkout_process'));
        add_action('woocommerce_checkout_order_processed', array($this, 'handle_order_processed'));
    }

    /**
     * Initialize frontend
     */
    public function init()
    {
        // Add delivery company registration page
        add_rewrite_rule('^delivery-company-registration/?$', 'index.php?delivery_company_registration=1', 'top');
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_filter('template_include', array($this, 'template_include'));

        // Add delivery company dashboard
        add_rewrite_rule('^delivery-company-dashboard/?$', 'index.php?delivery_company_dashboard=1', 'top');
        add_rewrite_rule('^delivery-company-dashboard/([^/]+)/?$', 'index.php?delivery_company_dashboard=1&dashboard_tab=$matches[1]', 'top');
    }

    /**
     * Add query vars
     *
     * @param array $vars Query vars
     * @return array
     */
    public function add_query_vars($vars)
    {
        $vars[] = 'delivery_company_registration';
        $vars[] = 'delivery_company_dashboard';
        $vars[] = 'dashboard_tab';

        return $vars;
    }

    /**
     * Template include
     *
     * @param string $template Template path
     * @return string
     */
    public function template_include($template)
    {
        if (get_query_var('delivery_company_registration')) {
            return $this->get_registration_template();
        }

        if (get_query_var('delivery_company_dashboard')) {
            return $this->get_dashboard_template();
        }

        return $template;
    }

    /**
     * Get registration template
     *
     * @return string
     */
    private function get_registration_template()
    {
        $template_path = DOKAN_DELIVERY_COMPANIES_PATH . '/templates/delivery-company-registration.php';

        if (file_exists($template_path)) {
            return $template_path;
        }

        return $this->get_default_registration_template();
    }

    /**
     * Get dashboard template
     *
     * @return string
     */
    private function get_dashboard_template()
    {
        $template_path = DOKAN_DELIVERY_COMPANIES_PATH . '/templates/delivery-company-dashboard.php';

        if (file_exists($template_path)) {
            return $template_path;
        }

        return $this->get_default_dashboard_template();
    }

    /**
     * Get default registration template
     *
     * @return string
     */
    private function get_default_registration_template()
    {
        add_action('wp_head', array($this, 'registration_head'));

        return get_template_directory() . '/index.php';
    }

    /**
     * Get default dashboard template
     *
     * @return string
     */
    private function get_default_dashboard_template()
    {
        add_action('wp_head', array($this, 'dashboard_head'));

        return get_template_directory() . '/index.php';
    }

    /**
     * Registration head
     */
    public function registration_head()
    {
?>
        <style>
            .delivery-company-registration {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }

            .delivery-company-registration h1 {
                text-align: center;
                margin-bottom: 30px;
            }

            .delivery-company-registration .form-group {
                margin-bottom: 20px;
            }

            .delivery-company-registration label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }

            .delivery-company-registration input,
            .delivery-company-registration select,
            .delivery-company-registration textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .delivery-company-registration .submit-btn {
                background: #0073aa;
                color: white;
                padding: 15px 30px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }

            .delivery-company-registration .submit-btn:hover {
                background: #005a87;
            }
        </style>
    <?php
    }

    /**
     * Dashboard head
     */
    public function dashboard_head()
    {
    ?>
        <style>
            .delivery-company-dashboard {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }

            .delivery-company-dashboard h1 {
                text-align: center;
                margin-bottom: 30px;
            }

            .dashboard-tabs {
                display: flex;
                border-bottom: 1px solid #ddd;
                margin-bottom: 20px;
            }

            .dashboard-tab {
                padding: 10px 20px;
                background: #f1f1f1;
                border: 1px solid #ddd;
                border-bottom: none;
                cursor: pointer;
                margin-right: 5px;
            }

            .dashboard-tab.active {
                background: white;
                border-bottom: 1px solid white;
            }

            .dashboard-content {
                background: white;
                border: 1px solid #ddd;
                padding: 20px;
            }

            .dashboard-table {
                width: 100%;
                border-collapse: collapse;
            }

            .dashboard-table th,
            .dashboard-table td {
                padding: 10px;
                border: 1px solid #ddd;
                text-align: left;
            }

            .dashboard-table th {
                background: #f1f1f1;
            }

            .status-pending {
                color: #ff6600;
            }

            .status-assigned {
                color: #0066cc;
            }

            .status-picked_up {
                color: #009900;
            }

            .status-in_transit {
                color: #0066cc;
            }

            .status-delivered {
                color: #009900;
            }

            .status-cancelled {
                color: #cc0000;
            }
        </style>
<?php
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts()
    {
        if (get_query_var('delivery_company_registration') || get_query_var('delivery_company_dashboard')) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-tabs');

            // Plugin front-end assets
            wp_enqueue_script(
                'dokan-delivery-companies-js',
                DOKAN_DELIVERY_COMPANIES_ASSETS . '/js/delivery-companies.js',
                array('jquery'),
                DOKAN_DELIVERY_COMPANIES_VERSION,
                true
            );

            wp_enqueue_style(
                'dokan-delivery-companies-css',
                DOKAN_DELIVERY_COMPANIES_ASSETS . '/css/delivery-companies.css',
                array(),
                DOKAN_DELIVERY_COMPANIES_VERSION
            );

            // Localize script with ajax URL, nonce and countries list
            $countries = Dokan_Delivery_Shipping_Zone::get_countries();

            wp_localize_script('dokan-delivery-companies-js', 'dokan_delivery_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('dokan_delivery_ajax'),
                'countries' => $countries,
            ));
        }
    }

    /**
     * Handle delivery company registration
     */
    public function handle_delivery_company_registration()
    {
        if (! get_query_var('delivery_company_registration')) {
            return;
        }

        if (! is_user_logged_in()) {
            wp_redirect(wp_login_url(home_url('/delivery-company-registration/')));
            exit;
        }

        if ($_POST && isset($_POST['delivery_company_registration'])) {
            $this->process_registration();
        }
    }

    /**
     * Process registration
     */
    private function process_registration()
    {
        $data = array(
            'user_id' => get_current_user_id(),
            'company_name' => sanitize_text_field($_POST['company_name']),
            'contact_person' => sanitize_text_field($_POST['contact_person']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'postal_code' => sanitize_text_field($_POST['postal_code']),
            'country' => sanitize_text_field($_POST['country']),
        );

        $company_id = Dokan_Delivery_Company::create($data);

        if ($company_id) {
            wp_redirect(home_url('/delivery-company-dashboard/?message=registration_success'));
            exit;
        } else {
            wp_redirect(home_url('/delivery-company-registration/?message=registration_error'));
            exit;
        }
    }

    /**
     * Handle checkout process
     */
    public function handle_checkout_process()
    {
        if (! get_option('dokan_delivery_companies_enabled', 'yes')) {
            return;
        }

        // Check if any vendor has no shipping method
        $cart = WC()->cart;
        $vendors_without_shipping = array();

        foreach ($cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $vendor_id = dokan_get_vendor_by_product($product_id);

            if ($vendor_id) {
                // Check if vendor has shipping methods
                $shipping_methods = $this->dokan_get_vendor_shipping_methods($vendor_id);

                if (empty($shipping_methods)) {
                    $vendors_without_shipping[] = $vendor_id;
                }
            }
        }

        if (! empty($vendors_without_shipping)) {
            // Find delivery company for customer address
            $customer_address = array(
                'country' => WC()->checkout()->get_value('shipping_country'),
                'state' => WC()->checkout()->get_value('shipping_state'),
                'city' => WC()->checkout()->get_value('shipping_city'),
                'postal_code' => WC()->checkout()->get_value('shipping_postcode'),
            );

            $delivery_company = $this->find_delivery_company_for_address($customer_address);

            if ($delivery_company) {
                $shipping_cost = $delivery_company->get_shipping_cost($customer_address, $cart->get_total('edit'));

                if ($shipping_cost > 0) {
                    // Add shipping cost to cart
                    $cart->add_fee(__('Delivery Fee', 'dokan-delivery-companies'), $shipping_cost);
                }
            }
        }
    }

    /**
     * Handle order processed
     */
    public function handle_order_processed($order_id)
    {
        if (! get_option('dokan_delivery_companies_enabled', 'yes')) {
            return;
        }

        $order = wc_get_order($order_id);

        if (! $order) {
            return;
        }

        // Check if any vendor has no shipping method
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

        if (! empty($vendors_without_shipping)) {
            // Find delivery company for customer address
            $customer_address = array(
                'country' => $order->get_shipping_country(),
                'state' => $order->get_shipping_state(),
                'city' => $order->get_shipping_city(),
                'postal_code' => $order->get_shipping_postcode(),
            );

            $delivery_company = $this->find_delivery_company_for_address($customer_address);

            if ($delivery_company) {
                // Create delivery order
                Dokan_Delivery_Order_Manager::create_from_wc_order($order_id, $delivery_company->get_data('id'));
            }
        }
    }

    /**
     * Find delivery company for address
     *
     * @param array $address Customer address
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
