<?php

/**
 * Shipping Zone Management Class
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Dokan Delivery Shipping Zone Class
 */
class Dokan_Delivery_Shipping_Zone
{

    /**
     * Zone data
     *
     * @var array
     */
    private $data = array();

    /**
     * Zone ID
     *
     * @var int
     */
    private $id = 0;

    /**
     * Constructor
     *
     * @param int $id Zone ID
     */
    public function __construct($id = 0)
    {
        if ($id > 0) {
            $this->id = $id;
            $this->load_data();
        }
    }

    /**
     * Load zone data
     */
    private function load_data()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_shipping_zones_table');
        $zone = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $this->id));

        if ($zone) {
            $this->data = (array) $zone;
        }
    }

    /**
     * Get zone data
     *
     * @param string $key Data key
     * @return mixed
     */
    public function get_data($key = '')
    {
        if (empty($key)) {
            return $this->data;
        }

        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Set zone data
     *
     * @param string $key Data key
     * @param mixed $value Data value
     */
    public function set_data($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Save zone data
     *
     * @return bool|int
     */
    public function save()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_shipping_zones_table');

        if ($this->id > 0) {
            // Update existing zone
            $result = $wpdb->update(
                $table_name,
                $this->data,
                array('id' => $this->id),
                array('%d', '%s', '%s', '%s', '%f', '%f', '%d', '%s'),
                array('%d')
            );

            return $result !== false;
        } else {
            // Insert new zone
            $result = $wpdb->insert(
                $table_name,
                $this->data,
                array('%d', '%s', '%s', '%s', '%f', '%f', '%d', '%s')
            );

            if ($result) {
                $this->id = $wpdb->insert_id;
                return $this->id;
            }

            return false;
        }
    }

    /**
     * Create new shipping zone
     *
     * @param array $data Zone data
     * @return bool|int
     */
    public static function create($data)
    {
        $zone = new self();

        $zone->set_data('delivery_company_id', intval($data['delivery_company_id']));
        $zone->set_data('zone_name', sanitize_text_field($data['zone_name']));
        $zone->set_data('zone_type', sanitize_text_field($data['zone_type']));
        $zone->set_data('zone_value', sanitize_text_field($data['zone_value']));
        $zone->set_data('shipping_rate', floatval($data['shipping_rate']));
        $zone->set_data('free_shipping_threshold', isset($data['free_shipping_threshold']) ? floatval($data['free_shipping_threshold']) : null);
        $zone->set_data('estimated_delivery_days', intval($data['estimated_delivery_days']));
        $zone->set_data('status', 'active');

        return $zone->save();
    }

    /**
     * Get zones by delivery company ID
     *
     * @param int $delivery_company_id Delivery company ID
     * @return array
     */
    public static function get_by_delivery_company_id($delivery_company_id)
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_shipping_zones_table');
        $zones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE delivery_company_id = %d ORDER BY zone_name ASC",
            $delivery_company_id
        ));

        return $zones;
    }

    /**
     * Get active zones by delivery company ID
     *
     * @param int $delivery_company_id Delivery company ID
     * @return array
     */
    public static function get_active_by_delivery_company_id($delivery_company_id)
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_shipping_zones_table');
        $zones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE delivery_company_id = %d AND status = 'active' ORDER BY zone_name ASC",
            $delivery_company_id
        ));

        return $zones;
    }

    /**
     * Update zone status
     *
     * @param string $status New status
     * @return bool
     */
    public function update_status($status)
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_shipping_zones_table');
        $result = $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $this->id),
            array('%s'),
            array('%d')
        );

        if ($result) {
            $this->set_data('status', $status);
        }

        return $result !== false;
    }

    /**
     * Delete zone
     *
     * @return bool
     */
    public function delete()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_shipping_zones_table');
        $result = $wpdb->delete($table_name, array('id' => $this->id), array('%d'));

        return $result !== false;
    }

    /**
     * Get zone options for form
     *
     * @return array
     */
    public static function get_zone_type_options()
    {
        return array(
            'country' => __('Country', 'dokan-delivery-companies'),
            'state' => __('State/Province', 'dokan-delivery-companies'),
            'city' => __('City', 'dokan-delivery-companies'),
            'postal' => __('Postal Code', 'dokan-delivery-companies'),
        );
    }

    /**
     * Get countries list
     *
     * @return array
     */
    public static function get_countries()
    {
        $countries = array();

        if (function_exists('WC') && WC()->countries) {
            $countries = WC()->countries->get_countries();
        } else {
            // Fallback countries list
            $countries = array(
                'US' => __('United States', 'dokan-delivery-companies'),
                'CA' => __('Canada', 'dokan-delivery-companies'),
                'GB' => __('United Kingdom', 'dokan-delivery-companies'),
                'AU' => __('Australia', 'dokan-delivery-companies'),
                'DE' => __('Germany', 'dokan-delivery-companies'),
                'FR' => __('France', 'dokan-delivery-companies'),
                'IT' => __('Italy', 'dokan-delivery-companies'),
                'ES' => __('Spain', 'dokan-delivery-companies'),
                'NL' => __('Netherlands', 'dokan-delivery-companies'),
                'BE' => __('Belgium', 'dokan-delivery-companies'),
            );
        }

        return $countries;
    }

    /**
     * Get states for country
     *
     * @param string $country_code Country code
     * @return array
     */
    public static function get_states($country_code)
    {
        $states = array();

        if (function_exists('WC') && WC()->countries) {
            $states = WC()->countries->get_states($country_code);
        }

        return $states;
    }

    /**
     * Validate zone data
     *
     * @param array $data Zone data
     * @return array|bool Array of errors or true if valid
     */
    public static function validate_zone_data($data)
    {
        $errors = array();

        if (empty($data['zone_name'])) {
            $errors[] = __('Zone name is required.', 'dokan-delivery-companies');
        }

        if (empty($data['zone_type'])) {
            $errors[] = __('Zone type is required.', 'dokan-delivery-companies');
        }

        if (empty($data['zone_value'])) {
            $errors[] = __('Zone value is required.', 'dokan-delivery-companies');
        }

        if (! isset($data['shipping_rate']) || $data['shipping_rate'] < 0) {
            $errors[] = __('Shipping rate must be a positive number.', 'dokan-delivery-companies');
        }

        if (isset($data['free_shipping_threshold']) && $data['free_shipping_threshold'] < 0) {
            $errors[] = __('Free shipping threshold must be a positive number.', 'dokan-delivery-companies');
        }

        if (isset($data['estimated_delivery_days']) && $data['estimated_delivery_days'] < 1) {
            $errors[] = __('Estimated delivery days must be at least 1.', 'dokan-delivery-companies');
        }

        return empty($errors) ? true : $errors;
    }
}
