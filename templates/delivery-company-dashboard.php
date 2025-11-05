<?php

/**
 * Delivery Company Dashboard Template
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

// Require login
if (! is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/delivery-company-dashboard/')));
    exit;
}

$user_id = get_current_user_id();
$delivery_company = Dokan_Delivery_Company::get_by_user_id($user_id);

if (! $delivery_company) {
    wp_redirect(home_url('/delivery-company-registration/'));
    exit;
}

$current_tab = get_query_var('dashboard_tab') ?: 'orders';
$delivery_company_id = $delivery_company->get_data('id');

get_header();
?>

<div class="delivery-company-dashboard">
    <h1><?php echo esc_html($delivery_company->get_data('company_name')); ?> - <?php _e('Dashboard', 'dokan-delivery-companies'); ?></h1>

    <div class="dashboard-tabs">
        <a href="<?php echo esc_url(home_url('/delivery-company-dashboard/orders/')); ?>" class="dashboard-tab <?php echo $current_tab === 'orders' ? 'active' : ''; ?>"><?php _e('Orders', 'dokan-delivery-companies'); ?></a>
        <a href="<?php echo esc_url(home_url('/delivery-company-dashboard/shipping-zones/')); ?>" class="dashboard-tab <?php echo $current_tab === 'shipping-zones' ? 'active' : ''; ?>"><?php _e('Shipping Zones', 'dokan-delivery-companies'); ?></a>
        <a href="<?php echo esc_url(home_url('/delivery-company-dashboard/earnings/')); ?>" class="dashboard-tab <?php echo $current_tab === 'earnings' ? 'active' : ''; ?>"><?php _e('Earnings', 'dokan-delivery-companies'); ?></a>
        <a href="<?php echo esc_url(home_url('/delivery-company-dashboard/profile/')); ?>" class="dashboard-tab <?php echo $current_tab === 'profile' ? 'active' : ''; ?>"><?php _e('Profile', 'dokan-delivery-companies'); ?></a>
    </div>

    <div class="dashboard-content">
        <?php
        // Load tab renderer partial and render current tab
        $partial = dirname(__FILE__) . '/partials/tab-renderers.php';
        if (file_exists($partial)) {
            include_once $partial;
        }

        switch ($current_tab) {
            case 'orders':
                render_orders_tab($delivery_company_id);
                break;
            case 'shipping-zones':
                render_shipping_zones_tab($delivery_company_id);
                break;
            case 'earnings':
                render_earnings_tab($delivery_company_id);
                break;
            case 'profile':
                render_profile_tab($delivery_company);
                break;
            default:
                render_orders_tab($delivery_company_id);
                break;
        }
        ?>
    </div>
</div>

<?php
get_footer();
