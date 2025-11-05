<?php

/**
 * Order Management Class
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Dokan Delivery Order Manager Class
 */
class Dokan_Delivery_Order_Manager
{

    /**
     * Write plugin debug message to plugin log file (always writes, regardless of WP_DEBUG).
     *
     * @param string $message
     * @return void
     */
    private static function write_plugin_log($message)
    {
        if (! defined('DOKAN_DELIVERY_COMPANIES_PATH')) {
            return;
        }

        $log_dir = DOKAN_DELIVERY_COMPANIES_PATH . '/logs';
        if (! file_exists($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }

        $log_file = $log_dir . '/debug.log';
        $line     = sprintf("%s %s\n", date('Y-m-d H:i:s'), $message);
        @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
    }

    private $data = array();
    private $id   = 0;

    public function __construct($id = 0)
    {
        if ($id > 0) {
            $this->id = $id;
            $this->load_data();
        }
    }

    private function load_data()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_orders_table');
        if (empty($table_name)) {
            $table_name = $wpdb->prefix . 'dokan_delivery_orders';
        }

        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $this->id));
        if ($order) {
            $this->data = (array) $order;
        }
    }

    public function get_data($key = '')
    {
        return empty($key) ? $this->data : (isset($this->data[$key]) ? $this->data[$key] : null);
    }

    public function set_data($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function save()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_orders_table');
        if (empty($table_name)) {
            $table_name = $wpdb->prefix . 'dokan_delivery_orders';
        }

        if ($this->id > 0) {
            // Update existing order
            $result = $wpdb->update(
                $table_name,
                $this->data,
                array('id' => $this->id)
            );

            return $result !== false;
        } else {
            // Insert new order
            $result = $wpdb->insert($table_name, $this->data);

            if ($result) {
                $this->id = $wpdb->insert_id;
                return $this->id;
            }

            return false;
        }
    }

    public static function create_from_wc_order($order_id, $delivery_company_id)
    {
        $wc_order = wc_get_order($order_id);
        if (! $wc_order) {
            return false;
        }

        $vendor_id = dokan_get_seller_id_by_order($order_id);
        if (! $vendor_id) {
            return false;
        }

        $shipping_cost    = $wc_order->get_shipping_total();
        $pickup_address   = self::get_vendor_address($vendor_id);
        $delivery_address = self::get_customer_address($wc_order);

        $delivery_order = new self();
        $delivery_order->set_data('order_id', $order_id);
        $delivery_order->set_data('delivery_company_id', $delivery_company_id);
        $delivery_order->set_data('vendor_id', $vendor_id);
        $delivery_order->set_data('customer_id', $wc_order->get_customer_id());
        $delivery_order->set_data('shipping_cost', $shipping_cost);
        $delivery_order->set_data('pickup_address', $pickup_address);
        $delivery_order->set_data('delivery_address', $delivery_address);
        $delivery_order->set_data('status', 'pending');
        $delivery_order->set_data('created_at', current_time('mysql'));

        $result = $delivery_order->save();

        global $wpdb;
        if (! $result) {
            $msg = sprintf(
                'Dokan Delivery: FAILED to create delivery_order for WC order %d (delivery_company_id=%d, vendor_id=%s). shipping_cost=%s. wpdb->last_error=%s',
                $order_id,
                $delivery_company_id,
                $vendor_id,
                $shipping_cost,
                isset($wpdb->last_error) ? $wpdb->last_error : ''
            );
            self::write_plugin_log($msg);
        } else {
            $msg = sprintf(
                'Dokan Delivery: created delivery_order id=%s for WC order %d -> delivery_company_id=%d, vendor_id=%s, shipping_cost=%s',
                $result,
                $order_id,
                $delivery_company_id,
                $vendor_id,
                $shipping_cost
            );
            self::write_plugin_log($msg);

            self::create_earnings_record($delivery_order->id, $delivery_company_id, $order_id, $shipping_cost);
            self::send_order_notifications($delivery_order->id);
        }

        return $result;
    }

    private static function get_vendor_address($vendor_id)
    {
        $vendor = dokan()->vendor->get($vendor_id);
        if (! $vendor) {
            return '';
        }

        $address = array_filter(array(
            $vendor->get_address(),
            $vendor->get_city(),
            $vendor->get_state(),
            $vendor->get_zip(),
            $vendor->get_country(),
        ));

        return implode(', ', $address);
    }

    private static function get_customer_address($order)
    {
        $address = array_filter(array(
            $order->get_shipping_address_1(),
            $order->get_shipping_address_2(),
            $order->get_shipping_city(),
            $order->get_shipping_state(),
            $order->get_shipping_postcode(),
            $order->get_shipping_country(),
        ));

        return implode(', ', $address);
    }

    private static function create_earnings_record($delivery_order_id, $delivery_company_id, $order_id, $amount)
    {
        global $wpdb;

        $commission_rate   = floatval(get_option('dokan_delivery_companies_commission_rate', 5.00));
        $commission_amount = ($amount * $commission_rate) / 100;
        $net_amount        = $amount - $commission_amount;

        $table_name = get_option('dokan_delivery_earnings_table');
        if (empty($table_name)) {
            $table_name = $wpdb->prefix . 'dokan_delivery_earnings';
        }

        $wpdb->insert(
            $table_name,
            array(
                'delivery_company_id' => $delivery_company_id,
                'order_id'            => $order_id,
                'amount'              => $amount,
                'commission_rate'     => $commission_rate,
                'commission_amount'   => $commission_amount,
                'net_amount'          => $net_amount,
                'status'              => 'pending',
                'created_at'          => current_time('mysql'),
            )
        );
    }

    private static function send_order_notifications($delivery_order_id)
    {
        $delivery_order = new self($delivery_order_id);
        if (! $delivery_order->get_data()) {
            return;
        }

        $delivery_company = new Dokan_Delivery_Company($delivery_order->get_data('delivery_company_id'));
        $company_email    = $delivery_company->get_data('email');

        if ($company_email) {
            wp_mail(
                $company_email,
                sprintf(__('New Delivery Order #%d', 'dokan-delivery-companies'), $delivery_order_id),
                sprintf(__('You have a new delivery order. Order ID: %d', 'dokan-delivery-companies'), $delivery_order->get_data('order_id'))
            );
        }

        $vendor = get_user_by('id', $delivery_order->get_data('vendor_id'));
        if ($vendor) {
            wp_mail(
                $vendor->user_email,
                sprintf(__('Delivery Order Assigned #%d', 'dokan-delivery-companies'), $delivery_order_id),
                sprintf(__('Your order has been assigned to a delivery company. Order ID: %d', 'dokan-delivery-companies'), $delivery_order->get_data('order_id'))
            );
        }
    }

    public function update_status($status, $notes = '')
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_orders_table');
        if (empty($table_name)) {
            $table_name = $wpdb->prefix . 'dokan_delivery_orders';
        }

        $update_data = array('status' => $status);
        if ($notes) {
            $update_data['notes'] = $notes;
        }

        if ($status === 'picked_up' && ! $this->get_data('pickup_date')) {
            $update_data['pickup_date'] = current_time('mysql');
        }
        if ($status === 'delivered' && ! $this->get_data('delivery_date')) {
            $update_data['delivery_date'] = current_time('mysql');
        }

        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $this->id)
        );

        if ($result) {
            foreach ($update_data as $key => $value) {
                $this->set_data($key, $value);
            }
            $this->send_status_notification($status);
        }

        return $result !== false;
    }

    private function send_status_notification($status)
    {
        $wc_order = wc_get_order($this->get_data('order_id'));
        if (! $wc_order) {
            return;
        }

        $customer_email = $wc_order->get_billing_email();
        $status_labels  = array(
            'assigned'   => __('assigned to delivery company', 'dokan-delivery-companies'),
            'picked_up'  => __('picked up', 'dokan-delivery-companies'),
            'in_transit' => __('in transit', 'dokan-delivery-companies'),
            'delivered'  => __('delivered', 'dokan-delivery-companies'),
        );

        if (isset($status_labels[$status]) && $customer_email) {
            wp_mail(
                $customer_email,
                sprintf(__('Order Update - %s', 'dokan-delivery-companies'), $wc_order->get_order_number()),
                sprintf(__('Your order #%s has been %s.', 'dokan-delivery-companies'), $wc_order->get_order_number(), $status_labels[$status])
            );
        }
    }

    public static function get_by_delivery_company_id($delivery_company_id, $status = '')
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_orders_table');
        if (empty($table_name)) {
            $table_name = $wpdb->prefix . 'dokan_delivery_orders';
        }

        $sql    = "SELECT * FROM $table_name WHERE delivery_company_id = %d";
        $params = array(intval($delivery_company_id));

        if ($status) {
            $sql     .= " AND status = %s";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        $prepared = call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $params));
        $orders   = $wpdb->get_results($prepared);

        return $orders;
    }

    public static function get_by_vendor_id($vendor_id)
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_orders_table');
        if (empty($table_name)) {
            $table_name = $wpdb->prefix . 'dokan_delivery_orders';
        }

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE vendor_id = %d ORDER BY created_at DESC", $vendor_id)
        );
    }

    public static function get_status_options()
    {
        return array(
            'pending'   => __('Pending', 'dokan-delivery-companies'),
            'assigned'  => __('Assigned', 'dokan-delivery-companies'),
            'picked_up' => __('Picked Up', 'dokan-delivery-companies'),
            'in_transit' => __('In Transit', 'dokan-delivery-companies'),
            'delivered' => __('Delivered', 'dokan-delivery-companies'),
            'cancelled' => __('Cancelled', 'dokan-delivery-companies'),
        );
    }
}
