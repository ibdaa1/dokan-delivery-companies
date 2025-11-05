<?php

/**
 * Installation and database setup
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Dokan Delivery Companies Install Class
 */
class Dokan_Delivery_Companies_Install
{

    /**
     * Install plugin
     */
    public function install()
    {
        $this->create_tables();
        $this->create_user_roles();
        $this->create_default_options();
    }

    /**
     * Create database tables
     */
    private function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Delivery Companies table
        $table_delivery_companies = $wpdb->prefix . 'dokan_delivery_companies';
        $sql_delivery_companies = "CREATE TABLE $table_delivery_companies (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            company_name varchar(255) NOT NULL,
            contact_person varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            address text NOT NULL,
            city varchar(100) NOT NULL,
            state varchar(100) NOT NULL,
            postal_code varchar(20) NOT NULL,
            country varchar(100) NOT NULL,
            status enum('active','inactive','pending') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Shipping Zones table
        $table_shipping_zones = $wpdb->prefix . 'dokan_delivery_shipping_zones';
        $sql_shipping_zones = "CREATE TABLE $table_shipping_zones (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            delivery_company_id bigint(20) NOT NULL,
            zone_name varchar(255) NOT NULL,
            zone_type enum('country','state','city','postal') NOT NULL,
            zone_value text NOT NULL,
            shipping_rate decimal(10,2) NOT NULL,
            free_shipping_threshold decimal(10,2) DEFAULT NULL,
            estimated_delivery_days int(11) DEFAULT 3,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY delivery_company_id (delivery_company_id),
            KEY zone_type (zone_type),
            KEY status (status)
        ) $charset_collate;";

        // Delivery Orders table
        $table_delivery_orders = $wpdb->prefix . 'dokan_delivery_orders';
        $sql_delivery_orders = "CREATE TABLE $table_delivery_orders (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            delivery_company_id bigint(20) NOT NULL,
            vendor_id bigint(20) NOT NULL,
            customer_id bigint(20) NOT NULL,
            shipping_cost decimal(10,2) NOT NULL,
            pickup_address text NOT NULL,
            delivery_address text NOT NULL,
            status enum('pending','assigned','picked_up','in_transit','delivered','cancelled') DEFAULT 'pending',
            tracking_number varchar(100) DEFAULT NULL,
            pickup_date datetime DEFAULT NULL,
            delivery_date datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY order_id (order_id),
            KEY delivery_company_id (delivery_company_id),
            KEY vendor_id (vendor_id),
            KEY customer_id (customer_id),
            KEY status (status)
        ) $charset_collate;";

        // Delivery Company Earnings table
        $table_earnings = $wpdb->prefix . 'dokan_delivery_earnings';
        $sql_earnings = "CREATE TABLE $table_earnings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            delivery_company_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            commission_rate decimal(5,2) DEFAULT 0.00,
            commission_amount decimal(10,2) DEFAULT 0.00,
            net_amount decimal(10,2) NOT NULL,
            status enum('pending','paid','cancelled') DEFAULT 'pending',
            paid_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY delivery_company_id (delivery_company_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql_delivery_companies);
        dbDelta($sql_shipping_zones);
        dbDelta($sql_delivery_orders);
        dbDelta($sql_earnings);

        // Store table names in options
        update_option('dokan_delivery_companies_table', $table_delivery_companies);
        update_option('dokan_delivery_shipping_zones_table', $table_shipping_zones);
        update_option('dokan_delivery_orders_table', $table_delivery_orders);
        update_option('dokan_delivery_earnings_table', $table_earnings);
    }

    /**
     * Create user roles
     */
    private function create_user_roles()
    {
        // Add delivery company role
        add_role('delivery_company', __('Delivery Company', 'dokan-delivery-companies'), array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => false,
        ));

        // Add capabilities to delivery company role
        $role = get_role('delivery_company');
        if ($role) {
            $role->add_cap('dokan_delivery_company');
            $role->add_cap('dokan_view_delivery_orders');
            $role->add_cap('dokan_manage_delivery_orders');
            $role->add_cap('dokan_view_delivery_earnings');
        }
    }

    /**
     * Create default options
     */
    private function create_default_options()
    {
        $default_options = array(
            'dokan_delivery_companies_enabled' => 'yes',
            'dokan_delivery_companies_commission_rate' => 5.00,
            'dokan_delivery_companies_auto_assign' => 'yes',
            'dokan_delivery_companies_notification_email' => get_option('admin_email'),
        );

        foreach ($default_options as $option_name => $option_value) {
            if (! get_option($option_name)) {
                update_option($option_name, $option_value);
            }
        }
    }
}
