<?php

/**
 * Plugin Activation Test
 * 
 * This file can be used to test if the plugin activates correctly
 * Run this file directly to check for any activation issues
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    // Load WordPress
    require_once('../../../wp-load.php');
}

// Check if Dokan is active
if (! class_exists('WeDevs_Dokan')) {
    echo "❌ Dokan plugin is not active\n";
    exit;
}

if (! function_exists('dokan')) {
    echo "❌ Dokan function is not available\n";
    exit;
}

if (! class_exists('WooCommerce')) {
    echo "❌ WooCommerce is not active\n";
    exit;
}

// Check if our plugin classes exist
$required_classes = array(
    'Dokan_Delivery_Companies',
    'Dokan_Delivery_Company',
    'Dokan_Delivery_Shipping_Zone',
    'Dokan_Delivery_Order_Manager',
    'Dokan_Delivery_Payout_Manager',
    'Dokan_Delivery_Companies_Admin',
    'Dokan_Delivery_Companies_Frontend',
    'Dokan_Delivery_Companies_Ajax',
    'Dokan_Delivery_Companies_Hooks',
);

foreach ($required_classes as $class) {
    if (! class_exists($class)) {
        echo "❌ Class $class is not loaded\n";
        exit;
    }
}

// Check if database tables exist
global $wpdb;

$tables = array(
    'dokan_delivery_companies',
    'dokan_delivery_shipping_zones',
    'dokan_delivery_orders',
    'dokan_delivery_earnings',
);

foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

    if (! $exists) {
        echo "❌ Database table $table_name does not exist\n";
        exit;
    }
}

// Check if user roles exist
if (! get_role('delivery_company')) {
    echo "❌ Delivery company user role does not exist\n";
    exit;
}

echo "✅ All checks passed! Plugin should work correctly.\n";
echo "✅ Dokan Delivery Companies plugin is ready to use.\n";
echo "\n";
echo "Next steps:\n";
echo "1. Go to WordPress Admin > Delivery Companies to manage companies\n";
echo "2. Visit /delivery-company-registration/ to register new companies\n";
echo "3. Visit /delivery-company-dashboard/ to access company dashboard\n";
echo "4. Configure settings in Delivery Companies > Settings\n";

