<?php

/**
 * Tab renderer partial
 * Contains functions to render dashboard tabs (orders, shipping zones, earnings, profile)
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Render orders tab
 */
function render_orders_tab($delivery_company_id)
{
    $orders = Dokan_Delivery_Order_Manager::get_by_delivery_company_id($delivery_company_id);
    $status_options = Dokan_Delivery_Order_Manager::get_status_options();

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf("Dokan Delivery: render_orders_tab called for delivery_company_id=%s orders_count=%d", $delivery_company_id, is_array($orders) ? count($orders) : 0));
    }

?>
    <h2><?php _e('Delivery Orders', 'dokan-delivery-companies'); ?></h2>

    <?php if (empty($orders)) : ?>
        <p><?php _e('No delivery orders found.', 'dokan-delivery-companies'); ?></p>
    <?php else : ?>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th><?php _e('Order ID', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Vendor', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Status', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Shipping Cost', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Created', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Actions', 'dokan-delivery-companies'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : ?>
                    <?php
                    $vendor = get_user_by('id', $order->vendor_id);
                    $wc_order = wc_get_order($order->order_id);
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->order_id . '&action=edit')); ?>" target="_blank">
                                #<?php echo esc_html($wc_order ? $wc_order->get_order_number() : $order->order_id); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($vendor ? $vendor->display_name : 'N/A'); ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($order->status); ?>">
                                <?php echo esc_html($status_options[$order->status]); ?>
                            </span>
                        </td>
                        <td><?php echo wc_price($order->shipping_cost); ?></td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($order->created_at))); ?></td>
                        <td>
                            <?php if ($order->status === 'pending') : ?>
                                <button class="btn btn-primary update-status-btn" data-order-id="<?php echo esc_attr($order->id); ?>" data-status="assigned">
                                    <?php _e('Accept', 'dokan-delivery-companies'); ?>
                                </button>
                            <?php elseif ($order->status === 'assigned') : ?>
                                <button class="btn btn-success update-status-btn" data-order-id="<?php echo esc_attr($order->id); ?>" data-status="picked_up">
                                    <?php _e('Picked Up', 'dokan-delivery-companies'); ?>
                                </button>
                            <?php elseif ($order->status === 'picked_up') : ?>
                                <button class="btn btn-primary update-status-btn" data-order-id="<?php echo esc_attr($order->id); ?>" data-status="in_transit">
                                    <?php _e('In Transit', 'dokan-delivery-companies'); ?>
                                </button>
                            <?php elseif ($order->status === 'in_transit') : ?>
                                <button class="btn btn-success update-status-btn" data-order-id="<?php echo esc_attr($order->id); ?>" data-status="delivered">
                                    <?php _e('Delivered', 'dokan-delivery-companies'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif;
}


/**
 * Render shipping zones tab
 */
function render_shipping_zones_tab($delivery_company_id)
{
    $zones = Dokan_Delivery_Shipping_Zone::get_by_delivery_company_id($delivery_company_id);
    $zone_type_options = Dokan_Delivery_Shipping_Zone::get_zone_type_options();
    $countries = Dokan_Delivery_Shipping_Zone::get_countries();

    ?>
    <h2><?php _e('Shipping Zones', 'dokan-delivery-companies'); ?></h2>

    <div class="add-zone-form" style="margin-bottom: 30px;">
        <h3><?php _e('Add New Shipping Zone', 'dokan-delivery-companies'); ?></h3>
        <form id="add-zone-form">
            <div class="form-group">
                <label for="zone_name"><?php _e('Zone Name', 'dokan-delivery-companies'); ?></label>
                <input type="text" name="zone_name" id="zone_name" required>
            </div>

            <div class="form-group">
                <label for="zone_type"><?php _e('Zone Type', 'dokan-delivery-companies'); ?></label>
                <select name="zone_type" id="zone_type" required>
                    <option value=""><?php _e('Select Zone Type', 'dokan-delivery-companies'); ?></option>
                    <?php foreach ($zone_type_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="zone_value"><?php _e('Zone Value', 'dokan-delivery-companies'); ?></label>
                <select name="zone_value" id="zone_value" required>
                    <option value=""><?php _e('Select Country', 'dokan-delivery-companies'); ?></option>
                    <?php foreach ($countries as $code => $name) : ?>
                        <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="description" id="zone_value_help"><?php _e('Select one or more values after choosing the zone type.', 'dokan-delivery-companies'); ?></small>
            </div>

            <div class="form-group">
                <label for="shipping_rate"><?php _e('Shipping Rate', 'dokan-delivery-companies'); ?></label>
                <input type="number" name="shipping_rate" id="shipping_rate" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="free_shipping_threshold"><?php _e('Free Shipping Threshold', 'dokan-delivery-companies'); ?></label>
                <input type="number" name="free_shipping_threshold" id="free_shipping_threshold" step="0.01" min="0">
            </div>

            <div class="form-group">
                <label for="estimated_delivery_days"><?php _e('Estimated Delivery Days', 'dokan-delivery-companies'); ?></label>
                <input type="number" name="estimated_delivery_days" id="estimated_delivery_days" min="1" value="3" required>
            </div>

            <button type="submit" class="btn btn-primary"><?php _e('Add Zone', 'dokan-delivery-companies'); ?></button>
        </form>
    </div>

    <?php if (empty($zones)) : ?>
        <p><?php _e('No shipping zones found.', 'dokan-delivery-companies'); ?></p>
    <?php else : ?>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th><?php _e('Zone Name', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Type', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Value', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Rate', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Free Threshold', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Delivery Days', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Status', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Actions', 'dokan-delivery-companies'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($zones as $zone) : ?>
                    <tr>
                        <td><?php echo esc_html($zone->zone_name); ?></td>
                        <td><?php echo esc_html($zone_type_options[$zone->zone_type]); ?></td>
                        <td><?php echo esc_html($zone->zone_value); ?></td>
                        <td><?php echo wc_price($zone->shipping_rate); ?></td>
                        <td><?php echo $zone->free_shipping_threshold ? wc_price($zone->free_shipping_threshold) : '-'; ?></td>
                        <td><?php echo esc_html($zone->estimated_delivery_days); ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($zone->status); ?>">
                                <?php echo esc_html(ucfirst($zone->status)); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-danger delete-zone-btn" data-zone-id="<?php echo esc_attr($zone->id); ?>">
                                <?php _e('Delete', 'dokan-delivery-companies'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif;
}


/**
 * Render earnings tab
 */
function render_earnings_tab($delivery_company_id)
{
    $earnings = Dokan_Delivery_Payout_Manager::get_earnings($delivery_company_id);
    /** @var object $summary */
    $summary = Dokan_Delivery_Payout_Manager::get_earnings_summary($delivery_company_id);
    $pending_earnings = Dokan_Delivery_Payout_Manager::get_pending_earnings($delivery_company_id);

    ?>
    <h2><?php _e('Earnings', 'dokan-delivery-companies'); ?></h2>

    <div class="earnings-summary" style="margin-bottom: 30px;">
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; min-width: 150px;">
                <h3><?php echo wc_price($summary->total_net); ?></h3>
                <p><?php _e('Total Earnings', 'dokan-delivery-companies'); ?></p>
            </div>
            <div style="background: #fff3cd; padding: 20px; border-radius: 8px; text-align: center; min-width: 150px;">
                <h3><?php echo wc_price($summary->pending_amount); ?></h3>
                <p><?php _e('Pending', 'dokan-delivery-companies'); ?></p>
            </div>
            <div style="background: #d4edda; padding: 20px; border-radius: 8px; text-align: center; min-width: 150px;">
                <h3><?php echo wc_price($summary->paid_amount); ?></h3>
                <p><?php _e('Paid', 'dokan-delivery-companies'); ?></p>
            </div>
        </div>
    </div>

    <?php if ($pending_earnings > 0) : ?>
        <div class="payout-section" style="margin-bottom: 30px;">
            <h3><?php _e('Request Payout', 'dokan-delivery-companies'); ?></h3>
            <p><?php printf(__('You have %s available for payout.', 'dokan-delivery-companies'), wc_price($pending_earnings)); ?></p>
            <button class="btn btn-primary" onclick="requestPayout()"><?php _e('Request Payout', 'dokan-delivery-companies'); ?></button>
        </div>
    <?php endif; ?>

    <?php if (empty($earnings)) : ?>
        <p><?php _e('No earnings found.', 'dokan-delivery-companies'); ?></p>
    <?php else : ?>
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th><?php _e('Order ID', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Amount', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Commission', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Net Amount', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Status', 'dokan-delivery-companies'); ?></th>
                    <th><?php _e('Created', 'dokan-delivery-companies'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($earnings as $earning) : ?>
                    <tr>
                        <td>#<?php echo esc_html($earning->order_id); ?></td>
                        <td><?php echo wc_price($earning->amount); ?></td>
                        <td><?php echo wc_price($earning->commission_amount); ?></td>
                        <td><?php echo wc_price($earning->net_amount); ?></td>
                        <td>
                            <span class="status-<?php echo esc_attr($earning->status); ?>">
                                <?php echo esc_html(ucfirst($earning->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($earning->created_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif;
}


/**
 * Render profile tab
 */
function render_profile_tab($delivery_company)
{
    ?>
    <h2><?php _e('Company Profile', 'dokan-delivery-companies'); ?></h2>

    <form method="post" action="">
        <div class="form-group">
            <label for="company_name"><?php _e('Company Name', 'dokan-delivery-companies'); ?></label>
            <input type="text" name="company_name" id="company_name" value="<?php echo esc_attr($delivery_company->get_data('company_name')); ?>" readonly>
        </div>

        <div class="form-group">
            <label for="contact_person"><?php _e('Contact Person', 'dokan-delivery-companies'); ?></label>
            <input type="text" name="contact_person" id="contact_person" value="<?php echo esc_attr($delivery_company->get_data('contact_person')); ?>">
        </div>

        <div class="form-group">
            <label for="email"><?php _e('Email', 'dokan-delivery-companies'); ?></label>
            <input type="email" name="email" id="email" value="<?php echo esc_attr($delivery_company->get_data('email')); ?>">
        </div>

        <div class="form-group">
            <label for="phone"><?php _e('Phone', 'dokan-delivery-companies'); ?></label>
            <input type="tel" name="phone" id="phone" value="<?php echo esc_attr($delivery_company->get_data('phone')); ?>">
        </div>

        <div class="form-group">
            <label for="address"><?php _e('Address', 'dokan-delivery-companies'); ?></label>
            <textarea name="address" id="address" rows="3"><?php echo esc_textarea($delivery_company->get_data('address')); ?></textarea>
        </div>

        <div class="form-group">
            <label for="city"><?php _e('City', 'dokan-delivery-companies'); ?></label>
            <input type="text" name="city" id="city" value="<?php echo esc_attr($delivery_company->get_data('city')); ?>">
        </div>

        <div class="form-group">
            <label for="state"><?php _e('State/Province', 'dokan-delivery-companies'); ?></label>
            <input type="text" name="state" id="state" value="<?php echo esc_attr($delivery_company->get_data('state')); ?>">
        </div>

        <div class="form-group">
            <label for="postal_code"><?php _e('Postal Code', 'dokan-delivery-companies'); ?></label>
            <input type="text" name="postal_code" id="postal_code" value="<?php echo esc_attr($delivery_company->get_data('postal_code')); ?>">
        </div>

        <div class="form-group">
            <label for="country"><?php _e('Country', 'dokan-delivery-companies'); ?></label>
            <input type="text" name="country" id="country" value="<?php echo esc_attr($delivery_company->get_data('country')); ?>" readonly>
        </div>

        <button type="submit" class="btn btn-primary"><?php _e('Update Profile', 'dokan-delivery-companies'); ?></button>
    </form>
<?php
}

?>