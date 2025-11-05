<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Delivery Companies Shipping Method
 */
class Dokan_Delivery_Companies_Shipping_Method extends WC_Shipping_Method
{

    public function __construct($instance_id = 0)
    {
        $this->id                 = 'dokan_delivery_company_shipping';
        $this->instance_id        = absint($instance_id);
        $this->method_title       = __('Delivery Company', 'dokan-delivery-companies');
        $this->method_description = __('Shipping method provided by delivery companies registered in the marketplace.', 'dokan-delivery-companies');

        $this->enabled            = 'yes';
        $this->title              = __('Delivery Company', 'dokan-delivery-companies');

        $this->init();
    }

    public function init()
    {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', $this->title);

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'title' => array(
                'title'       => __('Method Title', 'dokan-delivery-companies'),
                'type'        => 'text',
                'description' => __('Title to be displayed during checkout.', 'dokan-delivery-companies'),
                'default'     => $this->title,
            ),
        );
    }

    /**
     * Calculate shipping rates for the given package
     *
     * @param array $package
     */
    public function calculate_shipping($package = array())
    {
        if (! get_option('dokan_delivery_companies_enabled', 'yes')) {
            return;
        }

        // Build address array compatible with Dokan_Delivery_Company::serves_address
        $address = array(
            'country'     => isset($package['destination']['country']) ? $package['destination']['country'] : '',
            'state'       => isset($package['destination']['state']) ? $package['destination']['state'] : '',
            'city'        => isset($package['destination']['city']) ? $package['destination']['city'] : '',
            'postal_code' => isset($package['destination']['postcode']) ? $package['destination']['postcode'] : '',
        );

        // Get cart total for free shipping threshold check
        $cart_total = isset($package['contents_cost']) ? floatval($package['contents_cost']) : 0;

        // Use the plugin model to find a company for this address
        if (class_exists('Dokan_Delivery_Company')) {
            $companies = Dokan_Delivery_Company::get_active_companies();

            foreach ($companies as $company_data) {
                $company = new Dokan_Delivery_Company($company_data->id);

                if ($company->serves_address($address)) {

                    // Get the matching zone directly
                    $matched_zone = $this->get_matching_zone($company, $address);

                    if (!$matched_zone) {
                        continue;
                    }

                    // Calculate cost with free shipping threshold
                    $cost = 0;
                    if (!empty($matched_zone->free_shipping_threshold) && $cart_total >= floatval($matched_zone->free_shipping_threshold)) {
                        $cost = 0; // Free shipping
                    } else {
                        $cost = floatval($matched_zone->shipping_rate);
                    }

                    // Get company name
                    $company_name = $company->get_data('company_name');

                    // Build descriptive label
                    $label_parts = array();

                    // Add company name
                    if (!empty($company_name)) {
                        $label_parts[] = $company_name;
                    } else {
                        $label_parts[] = $this->title;
                    }

                    // Add zone name if available
                    if (!empty($matched_zone->zone_name)) {
                        $label_parts[] = sprintf(__('to %s', 'dokan-delivery-companies'), $matched_zone->zone_name);
                    }

                    // Add delivery time if available
                    if (!empty($matched_zone->estimated_delivery_days)) {
                        $days = intval($matched_zone->estimated_delivery_days);
                        $label_parts[] = sprintf(
                            '(%s)',
                            sprintf(
                                _n('%d day', '%d days', $days, 'dokan-delivery-companies'),
                                $days
                            )
                        );
                    }

                    $label = implode(' ', $label_parts);

                    // Debug logging
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf(
                            'Dokan Delivery Shipping: Company=%s, Zone=%s, Rate=%s, Label=%s',
                            $company_name,
                            $matched_zone->zone_name,
                            $cost,
                            $label
                        ));
                    }

                    // Add rate
                    $rate = array(
                        'id'    => $this->id . ':' . $company->get_data('id'),
                        'label' => $label,
                        'cost'  => $cost,
                        'meta_data' => array(
                            'delivery_company_id' => $company->get_data('id'),
                            'delivery_company_name' => $company_name,
                            'zone_name' => $matched_zone->zone_name,
                            'zone_id' => $matched_zone->id,
                            'estimated_delivery_days' => isset($matched_zone->estimated_delivery_days) ? $matched_zone->estimated_delivery_days : '',
                        ),
                    );

                    $this->add_rate($rate);
                }
            }
        }
    }

    /**
     * Get matching zone for address (case-insensitive)
     *
     * @param Dokan_Delivery_Company $company
     * @param array $address
     * @return object|null
     */
    private function get_matching_zone($company, $address)
    {
        if (!class_exists('Dokan_Delivery_Shipping_Zone')) {
            return null;
        }

        $zones = Dokan_Delivery_Shipping_Zone::get_active_by_delivery_company_id($company->get_data('id'));

        if (empty($zones)) {
            return null;
        }

        foreach ($zones as $zone) {
            $zone_type = isset($zone->zone_type) ? $zone->zone_type : '';
            $zone_values = isset($zone->zone_value) ? explode(',', $zone->zone_value) : array();

            // Trim and normalize zone values
            $zone_values = array_map('trim', $zone_values);
            $zone_values = array_map('strtolower', $zone_values);

            $matches = false;

            switch ($zone_type) {
                case 'country':
                    if (!empty($address['country'])) {
                        $matches = in_array(strtolower(trim($address['country'])), $zone_values);
                    }
                    break;

                case 'state':
                    if (!empty($address['state'])) {
                        $matches = in_array(strtolower(trim($address['state'])), $zone_values);
                    }
                    break;

                case 'city':
                    if (!empty($address['city'])) {
                        $matches = in_array(strtolower(trim($address['city'])), $zone_values);
                    }
                    break;

                case 'postal':
                    if (!empty($address['postal_code'])) {
                        $matches = in_array(strtolower(trim($address['postal_code'])), $zone_values);
                    }
                    break;
            }

            if ($matches) {
                return $zone;
            }
        }

        return null;
    }
}

/**
 * Register the shipping method with WooCommerce
 */
function dokan_delivery_companies_register_shipping_method($methods)
{
    if (! class_exists('WC_Shipping_Method')) {
        return $methods;
    }

    $methods['dokan_delivery_company_shipping'] = 'Dokan_Delivery_Companies_Shipping_Method';

    return $methods;
}

add_filter('woocommerce_shipping_methods', 'dokan_delivery_companies_register_shipping_method');
