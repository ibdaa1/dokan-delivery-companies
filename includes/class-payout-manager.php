<?php

/**
 * Payout Management Class
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Dokan Delivery Payout Manager Class
 */
class Dokan_Delivery_Payout_Manager
{

    /**
     * Get earnings for delivery company
     *
     * @param int $delivery_company_id Delivery company ID
     * @param string $status Optional status filter
     * @return array
     */
    public static function get_earnings($delivery_company_id, $status = '')
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_earnings_table');

        $sql = "SELECT * FROM $table_name WHERE delivery_company_id = %d";
        $params = array($delivery_company_id);

        if ($status) {
            $sql .= " AND status = %s";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        $earnings = $wpdb->get_results($wpdb->prepare($sql, $params));

        return $earnings;
    }

    /**
     * Get total earnings for delivery company
     *
     * @param int $delivery_company_id Delivery company ID
     * @param string $status Optional status filter
     * @return float
     */
    public static function get_total_earnings($delivery_company_id, $status = '')
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_earnings_table');

        $sql = "SELECT SUM(net_amount) FROM $table_name WHERE delivery_company_id = %d";
        $params = array($delivery_company_id);

        if ($status) {
            $sql .= " AND status = %s";
            $params[] = $status;
        }

        $total = $wpdb->get_var($wpdb->prepare($sql, $params));

        return floatval($total);
    }

    /**
     * Get pending earnings for delivery company
     *
     * @param int $delivery_company_id Delivery company ID
     * @return float
     */
    public static function get_pending_earnings($delivery_company_id)
    {
        return self::get_total_earnings($delivery_company_id, 'pending');
    }

    /**
     * Get paid earnings for delivery company
     *
     * @param int $delivery_company_id Delivery company ID
     * @return float
     */
    public static function get_paid_earnings($delivery_company_id)
    {
        return self::get_total_earnings($delivery_company_id, 'paid');
    }

    /**
     * Process payout for delivery company
     *
     * @param int $delivery_company_id Delivery company ID
     * @param array $earnings_ids Array of earnings IDs to process
     * @param string $method Payout method
     * @param array $method_data Payout method data
     * @return bool
     */
    public static function process_payout($delivery_company_id, $earnings_ids, $method, $method_data)
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_earnings_table');

        // Validate earnings belong to delivery company
        $earnings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id IN (" . implode(',', array_fill(0, count($earnings_ids), '%d')) . ") AND delivery_company_id = %d AND status = 'pending'",
            array_merge($earnings_ids, array($delivery_company_id))
        ));

        if (empty($earnings)) {
            return false;
        }

        // Calculate total amount
        $total_amount = 0;
        foreach ($earnings as $earning) {
            $total_amount += $earning->net_amount;
        }

        // Process payout based on method
        $payout_success = false;

        switch ($method) {
            case 'bank_transfer':
                $payout_success = self::process_bank_transfer($delivery_company_id, $total_amount, $method_data);
                break;

            case 'paypal':
                $payout_success = self::process_paypal($delivery_company_id, $total_amount, $method_data);
                break;

            case 'manual':
                $payout_success = self::process_manual_payout($delivery_company_id, $total_amount, $method_data);
                break;
        }

        if ($payout_success) {
            // Update earnings status
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET status = 'paid', paid_at = %s WHERE id IN (" . implode(',', array_fill(0, count($earnings_ids), '%d')) . ")",
                array_merge(array(current_time('mysql')), $earnings_ids)
            ));

            // Send payout notification
            self::send_payout_notification($delivery_company_id, $total_amount, $method);

            return true;
        }

        return false;
    }

    /**
     * Process bank transfer payout
     *
     * @param int $delivery_company_id Delivery company ID
     * @param float $amount Amount
     * @param array $method_data Method data
     * @return bool
     */
    private static function process_bank_transfer($delivery_company_id, $amount, $method_data)
    {
        // This would integrate with your bank transfer API
        // For now, we'll just log it and return true

        $delivery_company = new Dokan_Delivery_Company($delivery_company_id);
        $company_name = $delivery_company->get_data('company_name');

        error_log(sprintf(
            'Bank transfer payout processed for %s: $%.2f to account %s',
            $company_name,
            $amount,
            $method_data['account_number']
        ));

        return true;
    }

    /**
     * Process PayPal payout
     *
     * @param int $delivery_company_id Delivery company ID
     * @param float $amount Amount
     * @param array $method_data Method data
     * @return bool
     */
    private static function process_paypal($delivery_company_id, $amount, $method_data)
    {
        // This would integrate with PayPal API
        // For now, we'll just log it and return true

        $delivery_company = new Dokan_Delivery_Company($delivery_company_id);
        $company_name = $delivery_company->get_data('company_name');

        error_log(sprintf(
            'PayPal payout processed for %s: $%.2f to %s',
            $company_name,
            $amount,
            $method_data['paypal_email']
        ));

        return true;
    }

    /**
     * Process manual payout
     *
     * @param int $delivery_company_id Delivery company ID
     * @param float $amount Amount
     * @param array $method_data Method data
     * @return bool
     */
    private static function process_manual_payout($delivery_company_id, $amount, $method_data)
    {
        // For manual payouts, we'll just mark them as processed
        // Admin will handle the actual payment

        $delivery_company = new Dokan_Delivery_Company($delivery_company_id);
        $company_name = $delivery_company->get_data('company_name');

        error_log(sprintf(
            'Manual payout marked for %s: $%.2f - %s',
            $company_name,
            $amount,
            $method_data['notes']
        ));

        return true;
    }

    /**
     * Send payout notification
     *
     * @param int $delivery_company_id Delivery company ID
     * @param float $amount Amount
     * @param string $method Payout method
     */
    private static function send_payout_notification($delivery_company_id, $amount, $method)
    {
        $delivery_company = new Dokan_Delivery_Company($delivery_company_id);
        $company_email = $delivery_company->get_data('email');

        if ($company_email) {
            $subject = __('Payout Processed', 'dokan-delivery-companies');
            $message = sprintf(
                __('Your payout of $%.2f has been processed via %s.', 'dokan-delivery-companies'),
                $amount,
                ucfirst(str_replace('_', ' ', $method))
            );

            wp_mail($company_email, $subject, $message);
        }
    }

    /**
     * Get payout methods
     *
     * @return array
     */
    public static function get_payout_methods()
    {
        return array(
            'bank_transfer' => __('Bank Transfer', 'dokan-delivery-companies'),
            'paypal' => __('PayPal', 'dokan-delivery-companies'),
            'manual' => __('Manual Payment', 'dokan-delivery-companies'),
        );
    }

    /**
     * Get earnings summary for delivery company
     *
     * @param int $delivery_company_id Delivery company ID
     * @return array
     */
    public static function get_earnings_summary($delivery_company_id)
    {
        global $wpdb;

        $table_name = get_option('dokan_delivery_earnings_table');

        $summary = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_orders,
                SUM(amount) as total_amount,
                SUM(commission_amount) as total_commission,
                SUM(net_amount) as total_net,
                SUM(CASE WHEN status = 'pending' THEN net_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'paid' THEN net_amount ELSE 0 END) as paid_amount
            FROM $table_name 
            WHERE delivery_company_id = %d",
            $delivery_company_id
        ));

        return $summary;
    }

    /**
     * Get monthly earnings for delivery company
     *
     * @param int $delivery_company_id Delivery company ID
     * @param int $year Year
     * @return array
     */
    public static function get_monthly_earnings($delivery_company_id, $year = null)
    {
        global $wpdb;

        if (! $year) {
            $year = date('Y');
        }

        $table_name = get_option('dokan_delivery_earnings_table');

        $earnings = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                MONTH(created_at) as month,
                COUNT(*) as orders,
                SUM(net_amount) as amount
            FROM $table_name 
            WHERE delivery_company_id = %d AND YEAR(created_at) = %d
            GROUP BY MONTH(created_at)
            ORDER BY month ASC",
            $delivery_company_id,
            $year
        ));

        return $earnings;
    }
}
