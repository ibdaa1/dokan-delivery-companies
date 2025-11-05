<?php

/**
 * Lightweight integration test for delivery company mapping.
 * Run via WP-CLI from the WordPress root:
 *
 * wp eval-file wp-content/plugins/dokan-delivery-companies/tools/test-order-mapping.php
 */

if (! defined('WPINC')) {
    echo "This file must be run inside WordPress (use wp eval-file).\n";
    exit(1);
}

global $wpdb;

// Find or create a simple product
$product_id = 0;
$products = get_posts(array('post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1));

if (! empty($products)) {
    $product_id = $products[0]->ID;
    // Ensure product has an author (vendor). Use user ID 1 as fallback.
    wp_update_post(array('ID' => $product_id, 'post_author' => 1));
    echo "Using existing product ID: $product_id\n";
} else {
    $product_id = wp_insert_post(array(
        'post_title' => 'Test Product for Delivery Mapping',
        'post_content' => 'Test product',
        'post_status' => 'publish',
        'post_type' => 'product',
        'post_author' => 1,
    ));

    if (is_wp_error($product_id) || ! $product_id) {
        echo "Failed to create test product.\n";
        exit(1);
    }

    // Set product type and price using WooCommerce CRUD if available
    if (class_exists('WC_Product')) {
        $p = wc_get_product($product_id);
        if ($p) {
            $p->set_regular_price('10');
            $p->save();
        }
    }

    echo "Created product ID: $product_id\n";
}

// Create a new order
if (! function_exists('wc_create_order')) {
    echo "WooCommerce functions not available. Make sure WooCommerce is active.\n";
    exit(1);
}

$order = wc_create_order();

// Add product to order
$product = wc_get_product($product_id);
$order->add_product($product, 1);

// Set a shipping item with delivery_company_id meta
$shipping = new WC_Order_Item_Shipping();
$shipping->set_method_title('Test Delivery Company');
$shipping->set_method_id('dokan_delivery_company_shipping:123');
$shipping->set_total(5);
$shipping->add_meta_data('delivery_company_id', 123, true);
$order->add_item($shipping);

$order->calculate_totals();
$order_id = $order->save();

echo "Created WC order ID: $order_id\n";

// Instantiate hooks class if needed and call create_delivery_order
if (! class_exists('Dokan_Delivery_Companies_Hooks')) {
    echo "Dokan Delivery hooks class not found. Make sure the plugin is active.\n";
    exit(1);
}

$hooks = new Dokan_Delivery_Companies_Hooks();
$hooks->create_delivery_order($order_id);

// Verify delivery order created in plugin table
$table = get_option('dokan_delivery_orders_table');

if (! $table) {
    echo "Plugin delivery orders table option not found (plugin may not be installed).\n";
    exit(1);
}

$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE order_id = %d", $order_id));

if ($row) {
    echo "SUCCESS: Delivery order created. delivery_company_id={$row->delivery_company_id}, shipping_cost={$row->shipping_cost}\n";
    exit(0);
} else {
    echo "FAIL: Delivery order was not created. Check plugin logs and ensure vendor mapping exists.\n";
    exit(2);
}
