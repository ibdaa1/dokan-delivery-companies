<?php

/**
 * Delivery Company Management Class
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Dokan Delivery Company Class
 */
class Dokan_Delivery_Company
{

    /**
     * Company data
     *
     * @var array
     */
    private $data = array();

    /**
     * Company ID
     *
     * @var int
     */
    private $id = 0;

    /**
     * Constructor
     *
     * @param int $id Company ID
     */
    public function __construct($id = 0)
    {
        if ($id > 0) {
            $this->id = $id;
            $this->load_data();
        }
    }

    /**
     * Load company data
     */
    private function load_data()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_companies_table');
        $company = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $this->id));

        if ($company) {
            $this->data = (array) $company;
        }
    }

    /**
     * Get company data
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
     * Set company data
     *
     * @param string $key Data key
     * @param mixed $value Data value
     */
    public function set_data($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Save company data
     *
     * @return bool|int
     */
    public function save()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_companies_table');

        if ($this->id > 0) {
            // Update existing company
            $result = $wpdb->update(
                $table_name,
                $this->data,
                array('id' => $this->id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );

            return $result !== false;
        } else {
            // Insert new company
            $result = $wpdb->insert(
                $table_name,
                $this->data,
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );

            if ($result) {
                $this->id = $wpdb->insert_id;
                return $this->id;
            }

            return false;
        }
    }

    /**
     * Create new delivery company
     *
     * @param array $data Company data
     * @return bool|int
     */
    public static function create($data)
    {
        $company = new self();

        $company->set_data('user_id', $data['user_id']);
        $company->set_data('company_name', sanitize_text_field($data['company_name']));
        $company->set_data('contact_person', sanitize_text_field($data['contact_person']));
        $company->set_data('email', sanitize_email($data['email']));
        $company->set_data('phone', sanitize_text_field($data['phone']));
        $company->set_data('address', sanitize_textarea_field($data['address']));
        $company->set_data('city', sanitize_text_field($data['city']));
        $company->set_data('state', sanitize_text_field($data['state']));
        $company->set_data('postal_code', sanitize_text_field($data['postal_code']));
        $company->set_data('country', sanitize_text_field($data['country']));
        $company->set_data('status', 'pending');

        return $company->save();
    }

    /**
     * Get company by user ID
     *
     * @param int $user_id User ID
     * @return Dokan_Delivery_Company|null
     */
    public static function get_by_user_id($user_id)
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_companies_table');
        $company_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE user_id = %d", $user_id));

        if ($company_id) {
            return new self($company_id);
        }

        return null;
    }

    /**
     * Get all active companies
     *
     * @return array
     */
    public static function get_active_companies()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_companies_table');
        $companies = $wpdb->get_results("SELECT * FROM $table_name WHERE status = 'active'");

        return $companies;
    }

    /**
     * Check if company serves address
     *
     * @param array $address Customer address
     * @return bool
     */
    public function serves_address($address)
    {
        $shipping_zones = $this->get_shipping_zones();

        foreach ($shipping_zones as $zone) {
            if ($this->address_matches_zone($address, $zone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get shipping zones for company
     *
     * @return array
     */
    public function get_shipping_zones()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_shipping_zones_table');
        $zones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE delivery_company_id = %d AND status = 'active'",
            $this->id
        ));

        return $zones;
    }

    /**
     * Check if address matches shipping zone
     *
     * @param array $address Customer address
     * @param object $zone Shipping zone
     * @return bool
     */
    private function address_matches_zone($address, $zone)
    {
        $zone_values = explode(',', $zone->zone_value);

        switch ($zone->zone_type) {
            case 'country':
                return in_array($address['country'], $zone_values);

            case 'state':
                return in_array($address['state'], $zone_values);

            case 'city':
                return in_array($address['city'], $zone_values);

            case 'postal':
                return in_array($address['postal_code'], $zone_values);

            default:
                return false;
        }
    }

    /**
     * Get shipping cost for address
     *
     * @param array $address Customer address
     * @param float $order_total Order total
     * @return float
     */
    public function get_shipping_cost($address, $order_total = 0)
    {
        $shipping_zones = $this->get_shipping_zones();

        foreach ($shipping_zones as $zone) {
            if ($this->address_matches_zone($address, $zone)) {
                // Check if free shipping threshold is met
                if ($zone->free_shipping_threshold && $order_total >= $zone->free_shipping_threshold) {
                    return 0;
                }

                return floatval($zone->shipping_rate);
            }
        }

        return 0;
    }

    /**
     * Update company status
     *
     * @param string $status New status
     * @return bool
     */
    public function update_status($status)
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_companies_table');
        $result = $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $this->id),
            array('%s'),
            array('%d')
        );

        if ($result) {
            $this->set_data('status', $status);

            // Update user role if status changed to active
            if ($status === 'active') {
                $user = get_user_by('id', $this->get_data('user_id'));
                if ($user) {
                    $user->set_role('delivery_company');
                }
            }
        }

        return $result !== false;
    }

    /**
     * Delete company
     *
     * @return bool
     */
    public function delete()
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_companies_table');
        $result = $wpdb->delete($table_name, array('id' => $this->id), array('%d'));

        return $result !== false;
    }
}
