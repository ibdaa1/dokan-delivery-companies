<?php

/**
 * AJAX Handler Class
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Dokan Delivery Companies AJAX Class
 */
class Dokan_Delivery_Companies_Ajax
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_ajax_dokan_delivery_add_shipping_zone', array($this, 'add_shipping_zone'));
        add_action('wp_ajax_dokan_delivery_update_shipping_zone', array($this, 'update_shipping_zone'));
        add_action('wp_ajax_dokan_delivery_delete_shipping_zone', array($this, 'delete_shipping_zone'));
        add_action('wp_ajax_dokan_delivery_update_order_status', array($this, 'update_order_status'));
        add_action('wp_ajax_dokan_delivery_get_states', array($this, 'get_states'));
        add_action('wp_ajax_dokan_delivery_process_payout', array($this, 'process_payout'));

        // Non-logged in users
        add_action('wp_ajax_nopriv_dokan_delivery_get_states', array($this, 'get_states'));

        // Load dashboard tabs via AJAX
        add_action('wp_ajax_dokan_delivery_load_tab', array($this, 'load_tab'));
        add_action('wp_ajax_nopriv_dokan_delivery_load_tab', array($this, 'load_tab'));
    }

    /**
     * Add shipping zone
     */
    public function add_shipping_zone()
    {
        if (! wp_verify_nonce($_POST['nonce'], 'dokan_delivery_ajax')) {
            wp_die(__('Security check failed', 'dokan-delivery-companies'));
        }

        if (! current_user_can('dokan_delivery_company')) {
            wp_die(__('Permission denied', 'dokan-delivery-companies'));
        }

        $data = array(
            'delivery_company_id' => $this->get_delivery_company_id(),
            'zone_name' => sanitize_text_field($_POST['zone_name']),
            'zone_type' => sanitize_text_field($_POST['zone_type']),
            'zone_value' => sanitize_text_field($_POST['zone_value']),
            'shipping_rate' => floatval($_POST['shipping_rate']),
            'free_shipping_threshold' => isset($_POST['free_shipping_threshold']) ? floatval($_POST['free_shipping_threshold']) : null,
            'estimated_delivery_days' => intval($_POST['estimated_delivery_days']),
        );

        $validation = Dokan_Delivery_Shipping_Zone::validate_zone_data($data);

        if ($validation !== true) {
            wp_send_json_error(array('message' => implode('<br>', $validation)));
        }

        $zone_id = Dokan_Delivery_Shipping_Zone::create($data);

        if ($zone_id) {
            wp_send_json_success(array('message' => __('Shipping zone added successfully.', 'dokan-delivery-companies')));
        } else {
            wp_send_json_error(array('message' => __('Failed to add shipping zone.', 'dokan-delivery-companies')));
        }
    }

    /**
     * Update shipping zone
     */
    public function update_shipping_zone()
    {
        if (! wp_verify_nonce($_POST['nonce'], 'dokan_delivery_ajax')) {
            wp_die(__('Security check failed', 'dokan-delivery-companies'));
        }

        if (! current_user_can('dokan_delivery_company')) {
            wp_die(__('Permission denied', 'dokan-delivery-companies'));
        }

        $zone_id = intval($_POST['zone_id']);
        $zone = new Dokan_Delivery_Shipping_Zone($zone_id);

        if (! $zone->get_data()) {
            wp_send_json_error(array('message' => __('Shipping zone not found.', 'dokan-delivery-companies')));
        }

        // Check if zone belongs to current delivery company
        if ($zone->get_data('delivery_company_id') != $this->get_delivery_company_id()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'dokan-delivery-companies')));
        }

        $zone->set_data('zone_name', sanitize_text_field($_POST['zone_name']));
        $zone->set_data('zone_type', sanitize_text_field($_POST['zone_type']));
        $zone->set_data('zone_value', sanitize_text_field($_POST['zone_value']));
        $zone->set_data('shipping_rate', floatval($_POST['shipping_rate']));
        $zone->set_data('free_shipping_threshold', isset($_POST['free_shipping_threshold']) ? floatval($_POST['free_shipping_threshold']) : null);
        $zone->set_data('estimated_delivery_days', intval($_POST['estimated_delivery_days']));

        $validation = Dokan_Delivery_Shipping_Zone::validate_zone_data($zone->get_data());

        if ($validation !== true) {
            wp_send_json_error(array('message' => implode('<br>', $validation)));
        }

        if ($zone->save()) {
            wp_send_json_success(array('message' => __('Shipping zone updated successfully.', 'dokan-delivery-companies')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update shipping zone.', 'dokan-delivery-companies')));
        }
    }

    /**
     * Delete shipping zone
     */
    public function delete_shipping_zone()
    {
        if (! wp_verify_nonce($_POST['nonce'], 'dokan_delivery_ajax')) {
            wp_die(__('Security check failed', 'dokan-delivery-companies'));
        }

        if (! current_user_can('dokan_delivery_company')) {
            wp_die(__('Permission denied', 'dokan-delivery-companies'));
        }

        $zone_id = intval($_POST['zone_id']);
        $zone = new Dokan_Delivery_Shipping_Zone($zone_id);

        if (! $zone->get_data()) {
            wp_send_json_error(array('message' => __('Shipping zone not found.', 'dokan-delivery-companies')));
        }

        // Check if zone belongs to current delivery company
        if ($zone->get_data('delivery_company_id') != $this->get_delivery_company_id()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'dokan-delivery-companies')));
        }

        if ($zone->delete()) {
            wp_send_json_success(array('message' => __('Shipping zone deleted successfully.', 'dokan-delivery-companies')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete shipping zone.', 'dokan-delivery-companies')));
        }
    }

    /**
     * Update order status
     */
    public function update_order_status()
    {
        if (! wp_verify_nonce($_POST['nonce'], 'dokan_delivery_ajax')) {
            wp_die(__('Security check failed', 'dokan-delivery-companies'));
        }

        if (! current_user_can('dokan_delivery_company')) {
            wp_die(__('Permission denied', 'dokan-delivery-companies'));
        }

        $order_id = intval($_POST['order_id']);
        $status = sanitize_text_field($_POST['status']);
        $notes = sanitize_textarea_field($_POST['notes']);

        $delivery_order = new Dokan_Delivery_Order_Manager($order_id);

        if (! $delivery_order->get_data()) {
            wp_send_json_error(array('message' => __('Delivery order not found.', 'dokan-delivery-companies')));
        }

        // Check if order belongs to current delivery company
        if ($delivery_order->get_data('delivery_company_id') != $this->get_delivery_company_id()) {
            wp_send_json_error(array('message' => __('Permission denied.', 'dokan-delivery-companies')));
        }

        if ($delivery_order->update_status($status, $notes)) {
            wp_send_json_success(array('message' => __('Order status updated successfully.', 'dokan-delivery-companies')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update order status.', 'dokan-delivery-companies')));
        }
    }

    /**
     * Get states for country
     */
    public function get_states()
    {
        $country_code = sanitize_text_field($_POST['country_code']);
        $states = Dokan_Delivery_Shipping_Zone::get_states($country_code);

        wp_send_json_success($states);
    }


    /**
     * Load dashboard tab HTML via AJAX
     */
    public function load_tab()
    {
        // Accept both GET/POST; no auth required for viewing (but tabs like shipping-zones require login)
        $tab = isset($_REQUEST['tab']) ? sanitize_text_field($_REQUEST['tab']) : '';
        if (empty($tab)) {
            $tab = 'orders';
        }

        // Debug: log AJAX tab requests and the current user for troubleshooting
        $ajax_log_msg = sprintf("Dokan Delivery AJAX: load_tab called tab=%s user_id=%s", $tab, get_current_user_id());
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($ajax_log_msg);
        }

        // Also write to plugin-local log so we have logs even if WP_DEBUG is off
        $log_dir = DOKAN_DELIVERY_COMPANIES_PATH . '/logs';
        if (! file_exists($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        @file_put_contents($log_dir . '/debug.log', date('Y-m-d H:i:s') . ' ' . $ajax_log_msg . PHP_EOL, FILE_APPEND | LOCK_EX);

        // Basic security: if user must be logged in for some tabs
        if (in_array($tab, array('shipping-zones', 'earnings', 'profile')) && ! is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view this tab.', 'dokan-delivery-companies')));
        }

        ob_start();

        $user_id = get_current_user_id();
        $delivery_company = Dokan_Delivery_Company::get_by_user_id($user_id);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($delivery_company) {
                error_log(sprintf("Dokan Delivery AJAX: load_tab found delivery_company id=%s for user_id=%s", $delivery_company->get_data('id'), $user_id));
            } else {
                error_log(sprintf("Dokan Delivery AJAX: load_tab - delivery company not found for user_id=%s", $user_id));
            }
        }

        if (! $delivery_company) {
            wp_send_json_error(array('message' => __('Delivery company not found.', 'dokan-delivery-companies')));
        }

        $delivery_company_id = $delivery_company->get_data('id');

        // include the tab renderers partial to have the render_* functions available
        $partial = plugin_dir_path(__FILE__) . '/../templates/partials/tab-renderers.php';
        if (file_exists($partial)) {
            include_once $partial;
        }

        switch ($tab) {
            case 'orders':
                if (function_exists('render_orders_tab')) {
                    render_orders_tab($delivery_company_id);
                } else {
                    wp_send_json_error(array('message' => __('Orders renderer not available.', 'dokan-delivery-companies')));
                }
                break;
            case 'shipping-zones':
                if (function_exists('render_shipping_zones_tab')) {
                    render_shipping_zones_tab($delivery_company_id);
                } else {
                    wp_send_json_error(array('message' => __('Shipping zones renderer not available.', 'dokan-delivery-companies')));
                }
                break;
            case 'earnings':
                if (function_exists('render_earnings_tab')) {
                    render_earnings_tab($delivery_company_id);
                } else {
                    wp_send_json_error(array('message' => __('Earnings renderer not available.', 'dokan-delivery-companies')));
                }
                break;
            case 'profile':
                if (function_exists('render_profile_tab')) {
                    render_profile_tab($delivery_company);
                } else {
                    wp_send_json_error(array('message' => __('Profile renderer not available.', 'dokan-delivery-companies')));
                }
                break;
            default:
                if (function_exists('render_orders_tab')) {
                    render_orders_tab($delivery_company_id);
                } else {
                    wp_send_json_error(array('message' => __('Orders renderer not available.', 'dokan-delivery-companies')));
                }
                break;
        }

        $html = ob_get_clean();

        wp_send_json_success($html);
    }

    /**
     * Process payout
     */
    public function process_payout()
    {
        if (! wp_verify_nonce($_POST['nonce'], 'dokan_delivery_ajax')) {
            wp_die(__('Security check failed', 'dokan-delivery-companies'));
        }

        if (! current_user_can('dokan_delivery_company')) {
            wp_die(__('Permission denied', 'dokan-delivery-companies'));
        }

        $earnings_ids = array_map('intval', $_POST['earnings_ids']);
        $method = sanitize_text_field($_POST['method']);
        $method_data = array();

        switch ($method) {
            case 'bank_transfer':
                $method_data = array(
                    'account_number' => sanitize_text_field($_POST['account_number']),
                    'routing_number' => sanitize_text_field($_POST['routing_number']),
                    'bank_name' => sanitize_text_field($_POST['bank_name']),
                );
                break;

            case 'paypal':
                $method_data = array(
                    'paypal_email' => sanitize_email($_POST['paypal_email']),
                );
                break;

            case 'manual':
                $method_data = array(
                    'notes' => sanitize_textarea_field($_POST['notes']),
                );
                break;
        }

        $delivery_company_id = $this->get_delivery_company_id();

        if (Dokan_Delivery_Payout_Manager::process_payout($delivery_company_id, $earnings_ids, $method, $method_data)) {
            wp_send_json_success(array('message' => __('Payout processed successfully.', 'dokan-delivery-companies')));
        } else {
            wp_send_json_error(array('message' => __('Failed to process payout.', 'dokan-delivery-companies')));
        }
    }

    /**
     * Get delivery company ID for current user
     *
     * @return int
     */
    private function get_delivery_company_id()
    {
        $user_id = get_current_user_id();
        $company = Dokan_Delivery_Company::get_by_user_id($user_id);

        if ($company) {
            return $company->get_data('id');
        }

        return 0;
    }
}
