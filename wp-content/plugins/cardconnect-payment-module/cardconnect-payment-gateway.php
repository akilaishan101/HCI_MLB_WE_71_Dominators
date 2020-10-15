<?php
    /**
     * Plugin Name: CardConnect Payment Gateway
     * Plugin URI: https://wordpress.org/plugins/cardconnect-payment-module
     * Description: Accept credit card payments in your WooCommerce store!
     * Version: 3.2.9
     * Author: CardConnect <jle@cardconnect.com>
     * Author URI: https://cardconnect.com
     * License: GNU General Public License v2
     * License URI: http://www.gnu.org/licenses/gpl-2.0.html
     *
     * WC requires at least: 3.2
     * WC tested up to: 4.0.1
     *
     * @version 3.2.9
     * @author  CardConnect
     */
    
    /*
        Copyright: © 2019 CardConnect <jle@cardconnect.com>
    */
    
    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }
    
    define('WC_CARDCONNECT_VER', '3.2.9');
    define('WC_CARDCONNECT_PLUGIN_PATH', untrailingslashit(plugin_basename(__DIR__)));
    define('WC_CARDCONNECT_TEMPLATE_PATH', untrailingslashit(plugin_dir_path(__FILE__)) . '/templates/');
    define('WC_CARDCONNECT_PLUGIN_URL', untrailingslashit(plugins_url('', __FILE__)));
    
    add_action('plugins_loaded', 'CardConnectPaymentGateway_init', 0);
    
    /**
     * Initializes Card Connect Gateway
     *
     * @return void
     * @since 0.5.0
     */
    function CardConnectPaymentGateway_init() {
        
        // Append local includes dir to include path
        set_include_path(get_include_path() . PATH_SEPARATOR . plugin_dir_path(__FILE__) . 'includes');
        
        if (class_exists('CardConnectPaymentGateway') || !class_exists('WC_Payment_Gateway')) {
            return;
        }
        
        // Include Classes
        include_once 'classes/class-wc-gateway-cardconnect.php';
        include_once 'classes/class-wc-gateway-cardconnect-saved-cards.php';
        
        // Include Class for WooCommerce Subscriptions extension
        if (class_exists('WC_Subscriptions_Order')) {
            
            if (!function_exists('wcs_create_renewal_order')) {
                // Subscriptions 1.x
                include_once 'classes/class-wc-gateway-cardconnect-addons-deprecated.php';
            } else {
                // Subscriptions 2.x
                include_once 'classes/class-wc-gateway-cardconnect-addons.php';
            }
        }
        
        // Include Class for WooCommerce Pre-Orders extension
        if (class_exists('WC_Pre_Orders')) {
            include_once 'classes/class-wc-gateway-cardconnect-addons.php';
        }
        
        
        /**
         * Add the Gateway to WooCommerce
         **/
        add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_CardConnectPaymentGateway');
        
        function woocommerce_add_gateway_CardConnectPaymentGateway($methods) {
            
            
            if (class_exists('WC_Subscriptions_Order')) {
                // handling for WooCommerce Subscriptions extension
                
                if (!function_exists('wcs_create_renewal_order')) {
                    // Subscriptions 1.x
                    $methods[] = 'CardConnectPaymentGatewayAddonsDeprecated';
                } else {
                    // Subscriptions 2.x
                    $methods[] = 'CardConnectPaymentGatewayAddons';
                }
                
            } elseif (class_exists('WC_Pre_Orders')) {
                // handling for WooCommerce Pre-Orders extension
                $methods[] = 'CardConnectPaymentGatewayAddons';
            } else {
                // handling for plain-ole "simple product" type orders
                $methods[] = 'CardConnectPaymentGateway';
            }
            
            return $methods;
        }
        
    }
    
    //add_action('in_plugin_update_message-cardconnect-payment-module/cardconnect-payment-gateway.php', 'card_connect_update_message', 10, 2);
    
    function card_connect_update_message($data, $response) {
        if (version_compare(WC_CARDCONNECT_VER, '3.0.0', '>=')) {
            ob_start(); ?>
            <br>Please be advised that CardConnect is in the process of migrating its hosted applications and services.
            <br>
            <li><span style="color:red;">69.164.93.9/32</span></li>
            <li><span style="color:red;">198.62.138.0/24</span></li>
            <li><span style="color:red;">67.217.245.179/32</span></li>
            <li><span style="color:red;">206.201.63.0/24</span></li>
            Contact CardConnect Support at <a
                    href="tel:+18778280720">877-828-0720</a> if you have any further questions.
            <?php
            $message = ob_get_clean();
            printf('<br><strong style="">%s</strong>', __($message, 'text-domain'));
        }
    }
    
    // display CC admin notice
    
    
    function wc_cc_notice() {
        $cc_settings_page = admin_url('admin.php?page=wc-settings&tab=checkout&section=card_connect');
        ?>

        <div class="notice notice-warning is-dismissible wc-cc-notice">
            <p><?php _e('Please check the <a href="' . $cc_settings_page . '" >WooCommerce CardConnect settings page</a> to set up your CardConnect merchant account.', 'woocommerce'); ?></p>
        </div>
    
    <?php }
    
    
    function cc_add_notice_script() {
        wp_register_script('cc-notice-update', plugins_url('javascript/cc-admin-notice.js', __FILE__), 'jquery', '1.0', false);
        
        wp_localize_script('cc-notice-update', 'notice_params', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        ));
        
        wp_enqueue_script('cc-notice-update');
    }
    
    if (get_option('cc_dismiss_admin_notice') !== 'dismissed') {
        add_action('admin_notices', 'wc_cc_notice');
        add_action('admin_enqueue_scripts', 'cc_add_notice_script');
    }
    add_action('wp_ajax_cc_dismiss_admin_notice', 'cc_dismiss_admin_notice');
    
    function cc_dismiss_admin_notice() {
        update_option('cc_dismiss_admin_notice', 'dismissed');
    }
    
    
