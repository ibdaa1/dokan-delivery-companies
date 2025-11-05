<?php

/**
 * Admin Interface Class
 *
 * @package Dokan_Delivery_Companies
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Dokan Delivery Companies Admin Class
 */
class Dokan_Delivery_Companies_Admin
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Delivery Companies', 'dokan-delivery-companies'),
            __('Delivery Companies', 'dokan-delivery-companies'),
            'manage_options',
            'dokan-delivery-companies',
            array($this, 'admin_page'),
            'dashicons-truck',
            30
        );

        add_submenu_page(
            'dokan-delivery-companies',
            __('All Companies', 'dokan-delivery-companies'),
            __('All Companies', 'dokan-delivery-companies'),
            'manage_options',
            'dokan-delivery-companies',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'dokan-delivery-companies',
            __('Add Company', 'dokan-delivery-companies'),
            __('Add Company', 'dokan-delivery-companies'),
            'manage_options',
            'dokan-delivery-companies-add',
            array($this, 'add_company_page')
        );

        add_submenu_page(
            'dokan-delivery-companies',
            __('Settings', 'dokan-delivery-companies'),
            __('Settings', 'dokan-delivery-companies'),
            'manage_options',
            'dokan-delivery-companies-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'dokan-delivery-companies') === false) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
    }

    /**
     * Handle admin actions
     */
    public function handle_admin_actions()
    {
        if (! isset($_POST['dokan_delivery_action'])) {
            return;
        }

        if (! wp_verify_nonce($_POST['_wpnonce'], 'dokan_delivery_admin')) {
            wp_die(__('Security check failed', 'dokan-delivery-companies'));
        }

        $action = sanitize_text_field($_POST['dokan_delivery_action']);

        switch ($action) {
            case 'add_company':
                $this->handle_add_company();
                break;

            case 'update_status':
                $this->handle_update_status();
                break;

            case 'save_settings':
                $this->handle_save_settings();
                break;
        }
    }

    /**
     * Handle add company
     */
    private function handle_add_company()
    {
        $data = array(
            'user_id' => intval($_POST['user_id']),
            'company_name' => sanitize_text_field($_POST['company_name']),
            'contact_person' => sanitize_text_field($_POST['contact_person']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'postal_code' => sanitize_text_field($_POST['postal_code']),
            'country' => sanitize_text_field($_POST['country']),
        );

        $company_id = Dokan_Delivery_Company::create($data);

        if ($company_id) {
            wp_redirect(admin_url('admin.php?page=dokan-delivery-companies&message=company_added'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=dokan-delivery-companies&message=error'));
            exit;
        }
    }

    /**
     * Handle update status
     */
    private function handle_update_status()
    {
        $company_id = intval($_POST['company_id']);
        $status = sanitize_text_field($_POST['status']);

        $company = new Dokan_Delivery_Company($company_id);
        $company->update_status($status);

        wp_redirect(admin_url('admin.php?page=dokan-delivery-companies&message=status_updated'));
        exit;
    }

    /**
     * Handle save settings
     */
    private function handle_save_settings()
    {
        $settings = array(
            'dokan_delivery_companies_enabled' => isset($_POST['enabled']) ? 'yes' : 'no',
            'dokan_delivery_companies_commission_rate' => floatval($_POST['commission_rate']),
            'dokan_delivery_companies_auto_assign' => isset($_POST['auto_assign']) ? 'yes' : 'no',
            'dokan_delivery_companies_notification_email' => sanitize_email($_POST['notification_email']),
        );

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }

        wp_redirect(admin_url('admin.php?page=dokan-delivery-companies-settings&message=settings_saved'));
        exit;
    }

    /**
     * Admin page
     */
    public function admin_page()
    {
        // Get all companies (not just active ones)
        global $wpdb;
        $table_name = get_option('dokan_delivery_companies_table');

        $where_clause = '';
        if (isset($_GET['status_filter']) && !empty($_GET['status_filter'])) {
            $status = sanitize_text_field($_GET['status_filter']);
            $where_clause = $wpdb->prepare(" WHERE status = %s", $status);
        }

        $companies = $wpdb->get_results("SELECT * FROM $table_name" . $where_clause . " ORDER BY created_at DESC");

        // Get counts for display
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        $active_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'");
        $inactive_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'inactive'");

?>
        <div class="wrap">
            <h1><?php _e('Delivery Companies', 'dokan-delivery-companies'); ?></h1>

            <?php $this->display_admin_messages(); ?>

            <?php if ($pending_count > 0) : ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php _e('Action Required:', 'dokan-delivery-companies'); ?></strong>
                        <?php printf(_n('You have %d delivery company pending approval.', 'You have %d delivery companies pending approval.', $pending_count, 'dokan-delivery-companies'), $pending_count); ?>
                        <a href="<?php echo admin_url('admin.php?page=dokan-delivery-companies&status_filter=pending'); ?>" class="button button-primary" style="margin-left: 10px;">
                            <?php _e('Review Pending Companies', 'dokan-delivery-companies'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" style="display: inline-block;">
                        <input type="hidden" name="page" value="dokan-delivery-companies">
                        <select name="status_filter" onchange="this.form.submit()">
                            <option value=""><?php _e('All Statuses', 'dokan-delivery-companies'); ?></option>
                            <option value="pending" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'pending'); ?>><?php _e('Pending', 'dokan-delivery-companies'); ?></option>
                            <option value="active" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'active'); ?>><?php _e('Active', 'dokan-delivery-companies'); ?></option>
                            <option value="inactive" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'inactive'); ?>><?php _e('Inactive', 'dokan-delivery-companies'); ?></option>
                        </select>
                    </form>
                </div>
                <div class="alignright">
                    <span class="displaying-num">
                        <?php printf(__('%d companies total (%d pending, %d active, %d inactive)', 'dokan-delivery-companies'), $total_count, $pending_count, $active_count, $inactive_count); ?>
                    </span>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Company Name', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Contact Person', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Email', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Phone', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Status', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Created', 'dokan-delivery-companies'); ?></th>
                        <th><?php _e('Actions', 'dokan-delivery-companies'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($companies)) : ?>
                        <tr>
                            <td colspan="7"><?php _e('No delivery companies found.', 'dokan-delivery-companies'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($companies as $company) : ?>
                            <tr>
                                <td><?php echo esc_html($company->company_name); ?></td>
                                <td><?php echo esc_html($company->contact_person); ?></td>
                                <td><?php echo esc_html($company->email); ?></td>
                                <td><?php echo esc_html($company->phone); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($company->status); ?>">
                                        <?php echo esc_html(ucfirst($company->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($company->created_at))); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('dokan_delivery_admin'); ?>
                                        <input type="hidden" name="dokan_delivery_action" value="update_status">
                                        <input type="hidden" name="company_id" value="<?php echo esc_attr($company->id); ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="pending" <?php selected($company->status, 'pending'); ?>><?php _e('Pending', 'dokan-delivery-companies'); ?></option>
                                            <option value="active" <?php selected($company->status, 'active'); ?>><?php _e('Active', 'dokan-delivery-companies'); ?></option>
                                            <option value="inactive" <?php selected($company->status, 'inactive'); ?>><?php _e('Inactive', 'dokan-delivery-companies'); ?></option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php
    }

    /**
     * Add company page
     */
    public function add_company_page()
    {
    ?>
        <div class="wrap">
            <h1><?php _e('Add Delivery Company', 'dokan-delivery-companies'); ?></h1>

            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Instructions:', 'dokan-delivery-companies'); ?></strong><br>
                    <?php _e('1. First, create a user account and assign them the "Delivery Company" role', 'dokan-delivery-companies'); ?><br>
                    <?php _e('2. Then select that user from the dropdown below to create their company profile', 'dokan-delivery-companies'); ?><br>
                    <?php _e('3. Users who already have a delivery company profile will not appear in the dropdown', 'dokan-delivery-companies'); ?>
                </p>
                <p>
                    <a href="<?php echo admin_url('user-new.php'); ?>" class="button button-secondary" target="_blank">
                        <?php _e('Create New User', 'dokan-delivery-companies'); ?>
                    </a>
                    <a href="<?php echo admin_url('users.php'); ?>" class="button button-secondary" target="_blank">
                        <?php _e('Manage Users', 'dokan-delivery-companies'); ?>
                    </a>
                </p>
            </div>

            <form method="post">
                <?php wp_nonce_field('dokan_delivery_admin'); ?>
                <input type="hidden" name="dokan_delivery_action" value="add_company">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_id"><?php _e('User Account', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <select name="user_id" id="user_id" required>
                                <option value=""><?php _e('Select User', 'dokan-delivery-companies'); ?></option>
                                <?php
                                // Get users with customer or delivery_company role
                                $users = get_users(array(
                                    'role__in' => array('customer', 'delivery_company'),
                                    'orderby' => 'display_name',
                                    'order' => 'ASC'
                                ));

                                // Get existing delivery company user IDs to exclude them
                                global $wpdb;
                                $table_name = get_option('dokan_delivery_companies_table');
                                $existing_user_ids = array();
                                if ($table_name) {
                                    $existing_user_ids = $wpdb->get_col("SELECT user_id FROM $table_name");
                                }

                                foreach ($users as $user) {
                                    // Skip users who already have a delivery company profile
                                    if (in_array($user->ID, $existing_user_ids)) {
                                        continue;
                                    }

                                    $role_display = '';
                                    if (in_array('delivery_company', $user->roles)) {
                                        $role_display = ' [Delivery Company]';
                                    }

                                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name . ' (' . $user->user_email . ')' . $role_display) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">
                                <?php _e('Select a user account. Users with [Delivery Company] role are already assigned the delivery company role.', 'dokan-delivery-companies'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="company_name"><?php _e('Company Name', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="company_name" id="company_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contact_person"><?php _e('Contact Person', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="contact_person" id="contact_person" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email"><?php _e('Email', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="email" name="email" id="email" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="phone"><?php _e('Phone', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="tel" name="phone" id="phone" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="address"><?php _e('Address', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <textarea name="address" id="address" rows="3" cols="50" required></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="city"><?php _e('City', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="city" id="city" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="state"><?php _e('State/Province', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="state" id="state" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="postal_code"><?php _e('Postal Code', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="postal_code" id="postal_code" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="country"><?php _e('Country', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <select name="country" id="country" required>
                                <option value=""><?php _e('Select Country', 'dokan-delivery-companies'); ?></option>
                                <?php
                                $countries = Dokan_Delivery_Shipping_Zone::get_countries();
                                foreach ($countries as $code => $name) {
                                    echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Add Company', 'dokan-delivery-companies')); ?>
            </form>
        </div>
    <?php
    }

    /**
     * Settings page
     */
    public function settings_page()
    {
        $enabled = get_option('dokan_delivery_companies_enabled', 'yes');
        $commission_rate = get_option('dokan_delivery_companies_commission_rate', 5.00);
        $auto_assign = get_option('dokan_delivery_companies_auto_assign', 'yes');
        $notification_email = get_option('dokan_delivery_companies_notification_email', get_option('admin_email'));

    ?>
        <div class="wrap">
            <h1><?php _e('Delivery Companies Settings', 'dokan-delivery-companies'); ?></h1>

            <?php $this->display_admin_messages(); ?>

            <form method="post">
                <?php wp_nonce_field('dokan_delivery_admin'); ?>
                <input type="hidden" name="dokan_delivery_action" value="save_settings">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enabled"><?php _e('Enable Delivery Companies', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="enabled" id="enabled" value="1" <?php checked($enabled, 'yes'); ?>>
                            <p class="description"><?php _e('Enable delivery company functionality', 'dokan-delivery-companies'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="commission_rate"><?php _e('Commission Rate (%)', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="commission_rate" id="commission_rate" value="<?php echo esc_attr($commission_rate); ?>" step="0.01" min="0" max="100">
                            <p class="description"><?php _e('Commission rate for delivery companies', 'dokan-delivery-companies'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="auto_assign"><?php _e('Auto Assign Orders', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="auto_assign" id="auto_assign" value="1" <?php checked($auto_assign, 'yes'); ?>>
                            <p class="description"><?php _e('Automatically assign orders to delivery companies', 'dokan-delivery-companies'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="notification_email"><?php _e('Notification Email', 'dokan-delivery-companies'); ?></label>
                        </th>
                        <td>
                            <input type="email" name="notification_email" id="notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
                            <p class="description"><?php _e('Email address for delivery company notifications', 'dokan-delivery-companies'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save Settings', 'dokan-delivery-companies')); ?>
            </form>
        </div>
<?php
    }

    /**
     * Create user with delivery company role
     *
     * @param array $user_data User data
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    public static function create_delivery_company_user($user_data)
    {
        $user_id = wp_create_user(
            $user_data['username'],
            $user_data['password'],
            $user_data['email']
        );

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Set additional user data
        if (isset($user_data['first_name'])) {
            update_user_meta($user_id, 'first_name', $user_data['first_name']);
        }

        if (isset($user_data['last_name'])) {
            update_user_meta($user_id, 'last_name', $user_data['last_name']);
        }

        if (isset($user_data['display_name'])) {
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $user_data['display_name']
            ));
        }

        // Assign delivery company role
        $user = new WP_User($user_id);
        $user->set_role('delivery_company');

        return $user_id;
    }

    /**
     * Display admin messages
     */
    private function display_admin_messages()
    {
        if (! isset($_GET['message'])) {
            return;
        }

        $message = sanitize_text_field($_GET['message']);

        switch ($message) {
            case 'company_added':
                echo '<div class="notice notice-success"><p>' . __('Delivery company added successfully.', 'dokan-delivery-companies') . '</p></div>';
                break;

            case 'status_updated':
                echo '<div class="notice notice-success"><p>' . __('Company status updated successfully.', 'dokan-delivery-companies') . '</p></div>';
                break;

            case 'settings_saved':
                echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'dokan-delivery-companies') . '</p></div>';
                break;

            case 'error':
                echo '<div class="notice notice-error"><p>' . __('An error occurred. Please try again.', 'dokan-delivery-companies') . '</p></div>';
                break;
        }
    }
}
