<?php

/**
 * Plugin Name: Dokan Delivery Companies
 * Plugin URI: https://wedevs.com/dokan/
 * Description: Extends Dokan marketplace with delivery company functionality for automated shipping management.
 * Version: 1.0.0
 * Author: WeDevs
 * Author URI: https://wedevs.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dokan-delivery-companies
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DOKAN_DELIVERY_COMPANIES_VERSION', '1.0.0');
define('DOKAN_DELIVERY_COMPANIES_FILE', __FILE__);
define('DOKAN_DELIVERY_COMPANIES_PATH', dirname(__FILE__));
define('DOKAN_DELIVERY_COMPANIES_URL', plugins_url('', __FILE__));
define('DOKAN_DELIVERY_COMPANIES_ASSETS', DOKAN_DELIVERY_COMPANIES_URL . '/assets');

/**
 * Main Dokan Delivery Companies Class
 */
class Dokan_Delivery_Companies
{

    /**
     * Plugin instance
     *
     * @var Dokan_Delivery_Companies
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return Dokan_Delivery_Companies
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init_hooks();
        $this->includes();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        add_action('plugins_loaded', array($this, 'init'), 20);
        add_action('plugins_loaded', array($this, 'load_textdomain'), 20);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Include required files
     */
    private function includes()
    {
        require_once DOKAN_DELIVERY_COMPANIES_PATH . '/includes/class-install.php';
        require_once DOKAN_DELIVERY_COMPANIES_PATH . '/includes/class-delivery-company.php';
        require_once DOKAN_DELIVERY_COMPANIES_PATH . '/includes/class-shipping-zone.php';
        require_once DOKAN_DELIVERY_COMPANIES_PATH . '/includes/class-order-manager.php';
        require_once DOKAN_DELIVERY_COMPANIES_PATH . '/includes/class-payout-manager.php';
        require_once DOKAN_DELIVERY_COMPANIES_PATH . '/includes/class-admin.php';
        require_once DOKAN_DELIVERY_COMPANIES_PATH . '/includes/class-frontend.php';
        require_once DOKAN_DELIVERY_COMPANIES_PATH . '/includes/class-ajax.php';
        require_once DOKAN_DELIVERY_COMPANIES_PATH . '/includes/class-hooks.php';
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Check if Dokan is active
        if (! $this->is_dokan_active()) {
            add_action('admin_notices', array($this, 'dokan_missing_notice'));
            return;
        }

        // Initialize classes
        new Dokan_Delivery_Companies_Install();
        new Dokan_Delivery_Companies_Admin();
        new Dokan_Delivery_Companies_Frontend();
        new Dokan_Delivery_Companies_Ajax();
        new Dokan_Delivery_Companies_Hooks();
    }

    /**
     * Check if Dokan is active
     */
    private function is_dokan_active()
    {
        // Check if Dokan class exists
        if (! class_exists('WeDevs_Dokan')) {
            return false;
        }

        // Check if dokan function exists
        if (! function_exists('dokan')) {
            return false;
        }

        // Check if WooCommerce is active (Dokan dependency)
        if (! class_exists('WooCommerce')) {
            return false;
        }

        return true;
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('dokan-delivery-companies', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        $installer = new Dokan_Delivery_Companies_Install();
        $installer->install();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Cleanup if needed
    }

    /**
     * Dokan missing notice
     */
    public function dokan_missing_notice()
    {
        $message = __('Dokan Delivery Companies requires Dokan plugin to be installed and activated.', 'dokan-delivery-companies');

        if (! class_exists('WooCommerce')) {
            $message .= ' ' . __('WooCommerce is also required.', 'dokan-delivery-companies');
        }

?>
        <div class="notice notice-error">
            <p><?php echo esc_html($message); ?></p>
            <p>
                <a href="<?php echo admin_url('plugin-install.php?s=dokan&tab=search&type=term'); ?>" class="button button-primary">
                    <?php _e('Install Dokan', 'dokan-delivery-companies'); ?>
                </a>
                <?php if (! class_exists('WooCommerce')) : ?>
                    <a href="<?php echo admin_url('plugin-install.php?s=woocommerce&tab=search&type=term'); ?>" class="button">
                        <?php _e('Install WooCommerce', 'dokan-delivery-companies'); ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
<?php
    }
}

/**
 * Initialize the plugin
 */
function dokan_delivery_companies()
{
    return Dokan_Delivery_Companies::instance();
}

// Start the plugin
dokan_delivery_companies();
