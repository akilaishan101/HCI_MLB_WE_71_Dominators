<?php

/**
 * @package WP Encryption
 *
 * @author     Go Web Smarty
 * @copyright  Copyright (C) 2019-2020, Go Web Smarty
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://gowebsmarty.com
 * @since      Class available since Release 1.0.0
 *
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */
/**
 * Autoloader
 * 
 * @since 5.1.1
 */
require_once plugin_dir_path( __DIR__ ) . 'vendor/autoload.php';
use  LEClient\LEFunctions ;
require_once WPLE_DIR . 'classes/le-core.php';
require_once WPLE_DIR . 'classes/le-subdir-challenge.php';
require_once WPLE_DIR . 'classes/le-trait.php';
/**
 * WPLE_Admin class
 * 
 * Handles all the aspects of plugin page & cert generation form
 * @since 1.0.0
 */
class WPLE_Admin
{
    private  $FIREWALL ;
    public function __construct()
    {
        add_action( 'admin_enqueue_scripts', array( $this, 'wple_admin_styles' ) );
        add_action( 'admin_menu', array( $this, 'wple_admin_menu_page' ) );
        add_action(
            'before_wple_admin_form',
            array( $this, 'wple_debug_log' ),
            20,
            1
        );
        add_action( 'admin_init', array( $this, 'wple_save_email_generate_certs' ) );
        add_action( 'admin_init', array( $this, 'wple_download_files' ) );
        add_action( 'plugins_loaded', array( $this, 'wple_load_plugin_textdomain' ) );
        $show_rev = get_option( 'wple_show_review' );
        if ( $show_rev != FALSE && $show_rev == 1 ) {
            add_action( 'admin_notices', array( $this, 'wple_rateus' ) );
        }
        add_action( 'admin_init', array( $this, 'wple_review_handler' ) );
        add_action( 'admin_init', array( $this, 'wple_reset_handler' ) );
        add_action( 'wple_show_reviewrequest', array( $this, 'wple_set_review_flag' ) );
        add_action( 'wp_ajax_wple_dismiss', array( $this, 'wple_dismiss_notice' ) );
        add_action( 'wp_ajax_wple_admin_dnsverify', [ $this, 'wple_ajx_verify_dns' ] );
        add_action( 'wple_ssl_reminder_notice', [ $this, 'wple_start_show_reminder' ] );
        if ( FALSE !== get_option( 'wple_show_reminder' ) ) {
            add_action( 'admin_notices', [ $this, 'wple_reminder_notice' ] );
        }
        add_action( 'admin_init', 'WPLE_Subdir_Challenge_Helper::download_challenge_files' );
        add_action( 'wp_ajax_wple_admin_httpverify', [ $this, 'wple_ajx_verify_http' ] );
        add_action( 'admin_init', [ $this, 'wple_continue_certification' ] );
        if ( isset( $_GET['successnotice'] ) ) {
            add_action( 'admin_notices', array( $this, 'wple_success_notice' ) );
        }
        add_action( 'admin_init', array( $this, 'wple_domain_verification' ) );
    }
    
    /**
     * Enqueue admin styles
     * 
     * @since 1.0.0
     * @return void
     */
    public function wple_admin_styles()
    {
        wp_enqueue_style(
            WPLE_NAME,
            WPLE_URL . 'admin/css/le-admin.min.css',
            FALSE,
            WPLE_VERSION,
            'all'
        );
        wp_enqueue_script(
            WPLE_NAME . '-popper',
            WPLE_URL . 'admin/js/popper.min.js',
            array( 'jquery' ),
            WPLE_VERSION,
            true
        );
        wp_enqueue_script(
            WPLE_NAME . '-tippy',
            WPLE_URL . 'admin/js/tippy-bundle.iife.min.js',
            array( 'jquery' ),
            WPLE_VERSION,
            true
        );
        wp_enqueue_script(
            WPLE_NAME,
            WPLE_URL . 'admin/js/le-admin.js',
            array( 'jquery' ),
            WPLE_VERSION,
            true
        );
    }
    
    /**
     * Register plugin page
     *
     * @since 1.0.0
     * @return void
     */
    public function wple_admin_menu_page()
    {
        add_menu_page(
            WPLE_NAME,
            WPLE_NAME,
            'manage_options',
            WPLE_SLUG,
            array( $this, 'wple_menu_page' ),
            plugin_dir_url( __DIR__ ) . 'admin/assets/icon.png',
            100
        );
    }
    
    public function wple_load_plugin_textdomain()
    {
        load_plugin_textdomain( 'wp-letsencrypt-ssl', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
    }
    
    /**
     * Plugin page HTML
     *
     * @since 1.0.0
     * @return void
     */
    public function wple_menu_page()
    {
        $this->wple_subdir_ipaddress();
        $eml = '';
        $leopts = get_option( 'wple_opts' );
        if ( $opts = get_option( 'wple_opts' ) ) {
            $eml = ( isset( $opts['email'] ) ? $opts['email'] : '' );
        }
        $html = '
    <div class="wple-header">
      <img src="' . WPLE_URL . 'admin/assets/logo.png" class="wple-logo"/> <span class="wple-version">v' . WPLE_VERSION . '</span>
    </div>';
        
        if ( FALSE === get_option( 'wple_plan_choosen' ) ) {
            $this->wple_initial_quick_pricing( $html );
            return;
        }
        
        //5.1.0
        $complete = ( FALSE !== get_option( 'wple_complete' ) ? 1 : 0 );
        
        if ( $complete ) {
            $html .= '<div id="wple-sslgen">';
            $this->wple_completed_block( $html );
            $html .= '</div>';
            if ( !wple_fs()->is__premium_only() || !wple_fs()->can_use_premium_code() ) {
                $this->wple_upgrade_block( $html );
            }
            echo  $html ;
            return;
        }
        
        $this->wple_success_block( $html );
        $this->wple_error_block( $html );
        if ( !isset( $_GET['wpleauto'] ) && isset( $_GET['subdir'] ) ) {
            $this->wple_subdir_challenges( $html, $leopts );
        }
        
        if ( !wple_fs()->is__premium_only() || !wple_fs()->can_use_premium_code() ) {
            
            if ( isset( $_GET['subdir'] ) ) {
                echo  $html ;
                return;
            }
            
            
            if ( isset( $_GET['success'] ) ) {
                $this->wple_upgrade_block( $html );
                echo  $html ;
                return;
            }
        
        }
        
        $mappeddomain = '';
        $currentdomain = esc_html( str_ireplace( array( 'http://', 'https://' ), array( '', '' ), site_url() ) );
        $maindomain = $currentdomain;
        $slashpos = stripos( $currentdomain, '/' );
        
        if ( FALSE !== $slashpos ) {
            //subdir installation
            $maindomain = substr( $currentdomain, 0, $slashpos );
            $mappeddomain = '<label style="display: block; padding: 10px 5px; color: #aaa;font-size:15px;">' . esc_html__( 'PRIMARY DOMAIN', 'wp-letsencrypt-ssl' ) . '</label>
      <p style="width: 800px; max-width:100%; margin: 5px auto 20px;">' . $this->wple_kses( sprintf( __( '<strong>NOTE:</strong> Since you are willing to install SSL certificate for sub-directory site, SSL certificate will be generated for your primary domain <strong>%s</strong> which will cover your primary domain + ALL sub-directory sites.', 'wp-letsencrypt-ssl' ), $maindomain ) ) . '</p>
    <input type="text" name="wple_domain" class="wple-domain-input" value="' . esc_attr( $maindomain ) . '" readonly><br />';
        }
        
        if ( $leopts['type'] == 'wildcard' ) {
            $html .= '<script>
      jQuery(document).ready(function(){
        jQuery(".single-wildcard-switch").trigger("click");
      });
      </script>';
        }
        $html .= '<div id="wple-sslgen">
    <h2>' . esc_html__( 'ENTER YOUR EMAIL BELOW AND GENERATE SSL IN ONE CLICK', 'wp-letsencrypt-ssl' ) . '</h2>';
        if ( is_multisite() ) {
            $html .= '<p class="wple-multisite">' . $this->wple_kses( __( 'Upgrade to <strong>PRO</strong> version to avail Wildcard SSL support for multisite and ability to install SSL for mapped domains (different domain names).', 'wp-letsencrypt-ssl' ) ) . '</p>';
        }
        $html .= WPLE_Trait::wple_progress_bar();
        //$cname = '';
        //if (FALSE === stripos($currentdomain, '/')) {
        // if (stripos($currentdomain, 'www') === FALSE) {
        //   $cname = '<span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="' . esc_attr__("Add a CNAME with name 'www' pointing to your non-www domain", 'wp-letsencrypt-ssl') . '. ' . esc_attr__("Refer FAQ if you want to generate SSL for both www & non-www domain.", 'wp-letsencrypt-ssl') . '"></span>';
        // } else {
        //$cname = '<span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="' . esc_attr__("Refer FAQ if you want to generate SSL for both www & non-www domain.", 'wp-letsencrypt-ssl') . '"></span>';
        //}
        //}
        $bothchecked = '';
        $leadminform = '<form method="post" class="le-genform single-genform">' . $mappeddomain . '
    <input type="email" name="wple_email" class="wple_email" value="' . esc_attr( $eml ) . '" placeholder="' . esc_attr__( 'Enter your email address', 'wp-letsencrypt-ssl' ) . '" ><br />';
        
        if ( FALSE === stripos( 'www', $maindomain ) ) {
            $altdomain = 'www.' . $maindomain;
        } else {
            $altdomain = str_ireplace( 'www.', '', $maindomain );
        }
        
        $altdomaintest = wp_remote_head( 'http://' . $altdomain );
        
        if ( !is_wp_error( $altdomaintest ) ) {
            //if (isset($_GET['includewww'])) {
            $leadminform .= '<span class="lecheck">
      <label class="checkbox-label">
      <input type="checkbox" name="wple_include_www" class="wple_include_www" value="1" ' . $bothchecked . '>
        <span class="checkbox-custom rectangular"></span>
      </label>
    ' . esc_html__( 'Generate SSL Certificate for both www & non-www version of domain', 'wp-letsencrypt-ssl' ) . '&nbsp; <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="' . esc_attr__( "Before enabling this - please make sure both www & non-www version of your domain works!. Add a CNAME with name 'www' pointing to your non-www domain in your domain DNS zone editor", 'wp-letsencrypt-ssl' ) . '"></span></label>
    </span><br />';
            //}
        }
        
        $leadminform .= '<span class="lecheck">
      <label class="checkbox-label">
      <input type="checkbox" name="wple_send_usage" value="1" checked>
        <span class="checkbox-custom rectangular"></span>
      </label>
    ' . esc_html__( 'Anonymously send response data to get better support', 'wp-letsencrypt-ssl' ) . '</label>
    </span><br />';
        $leadminform .= '<span class="lecheck">
    <label class="checkbox-label">
      <input type="checkbox" name="wple_agree_le_tos" class="wple_agree_le" value="1">
      <span class="checkbox-custom rectangular"></span>
    </label>
    ' . $this->wple_kses( sprintf( __( "I agree to <b>Let's Encrypt</b> %sTerms of service%s", "wp-letsencrypt-ssl" ), '<a href="' . esc_attr__( 'https://letsencrypt.org/repository/', 'wp-letsencrypt-ssl' ) . '" rel="nofollow" target="_blank" style="margin-left:5px">', '</a>' ), 'a' ) . '
    </span> 
    <span class="lecheck">
    <label class="checkbox-label">
      <input type="checkbox" name="wple_agree_gws_tos" class="wple_agree_gws" value="1">
      <span class="checkbox-custom rectangular"></span>
    </label>
    ' . $this->wple_kses( sprintf( __( "I agree to <b>WP Encryption</b> %sTerms of service%s", "wp-letsencrypt-ssl" ), '<a href="https://gowebsmarty.com/terms-and-conditions/" rel="nofollow" target="_blank" style="margin-left:5px">', '</a>' ), 'a' ) . '
    </span>        
    ' . wp_nonce_field(
            'legenerate',
            'letsencrypt',
            false,
            false
        ) . '
    <button type="submit" name="generate-certs" id="singledvssl">' . esc_html__( 'Generate Free SSL Certificate', 'wp-letsencrypt-ssl' ) . '</button>
    </form>
    
    <div id="wple-error-popper">    
      <div class="wple-flex">
        <img src="' . WPLE_URL . 'admin/assets/loader.png" class="wple-loader"/>
        <div class="wple-error">Error</div>
      </div>
    </div>';
        $nonwww = str_ireplace( 'www.', '', $currentdomain );
        if ( FALSE !== ($ps = stripos( $nonwww, '/' )) ) {
            $nonwww = substr( $nonwww, 0, $ps );
        }
        $html .= '<div class="wple-single-dv-ssl">
    <div class="wple-info-box">
      <h3>' . esc_html__( 'Domains Covered', 'wp-letsencrypt-ssl' ) . '</h3>
      <strong>' . $nonwww . '</strong>
      <div class="wple-www"><strong>www.' . $nonwww . '</strong></div>
      <div class="wple-wc"><strong>*.' . $nonwww . '</strong></div>
    </div>';
        ob_start();
        do_action( 'before_wple_admin_form', $html );
        $html .= ob_get_contents();
        ob_end_clean();
        $html .= apply_filters( 'wple_admin_form', $leadminform );
        ob_start();
        do_action( 'after_wple_admin_form', $html );
        $html .= ob_get_contents();
        ob_end_clean();
        $html .= '</div>';
        $prosupport = $this->wple_kses( sprintf( __( 'Help your locale users by translating this page to your language!. %sSign-Up / Login and start translating right away%s.' ), '<a href="https://translate.wordpress.org/projects/wp-plugins/wp-letsencrypt-ssl/" target="_blank">', '</a>' ), 'a' );
        $html .= '
    <div class="le-powered">
		  <span>' . $prosupport . ' ' . $this->wple_kses( sprintf( 'SSL Certificate will be generated by %s (An open certificate authority).', "<b>Let's Encrypt</b>" ) ) . '</span>
	  </div>';
        $html .= '    
    </div><!--wple-sslgen-->';
        if ( !wple_fs()->is__premium_only() || !wple_fs()->can_use_premium_code() ) {
            $this->wple_upgrade_block( $html );
        }
        echo  $html ;
    }
    
    /**
     * log process & error in debug.log file
     *
     * @since 1.0.0
     * @param string $html
     * @return void
     */
    public function wple_debug_log( $html )
    {
        
        if ( !file_exists( WPLE_DEBUGGER ) ) {
            wp_mkdir_p( WPLE_DEBUGGER );
            $htacs = '<Files debug.log>' . "\n" . 'Order allow,deny' . "\n" . 'Deny from all' . "\n" . '</Files>';
            file_put_contents( WPLE_DEBUGGER . '.htaccess', $htacs );
        }
        
        //show only upon error since 4.6.0
        
        if ( isset( $_GET['error'] ) ) {
            $html = '<div class="toggle-debugger"><span class="dashicons dashicons-arrow-down-alt2"></span> ' . esc_html__( 'Show/hide full response', 'wp-letsencrypt-ssl' ) . '</div>';
            $file = WPLE_DEBUGGER . 'debug.log';
            
            if ( file_exists( $file ) ) {
                $log = file_get_contents( $file );
                $hideh2 = '';
                if ( isset( $_GET['dnsverified'] ) || isset( $_GET['dnsverify'] ) ) {
                    $hideh2 = 'hideheader';
                }
                $html .= '<div class="le-debugger running ' . $hideh2 . '"><h3>' . esc_html__( 'Response Log', 'wp-letsencrypt-ssl' ) . ':</h3>' . wp_kses_post( nl2br( $log ) ) . '</div>';
            } else {
                $html .= '<div class="le-debugger">' . esc_html__( "Full response will be shown here", 'wp-letsencrypt-ssl' ) . '</div>';
            }
            
            echo  $html ;
        }
    
    }
    
    /**
     * Save email & proceed upon clicking install SSL
     *
     * @since 1.0.0
     * @return void
     */
    public function wple_save_email_generate_certs()
    {
        //since 2.4.0
        //force https upon success
        
        if ( isset( $_POST['wple-https'] ) ) {
            if ( !wp_verify_nonce( $_POST['sslready'], 'wplehttps' ) ) {
                exit( 'Unauthorized access' );
            }
            $basedomain = str_ireplace( array( 'http://', 'https://' ), array( '', '' ), addslashes( site_url() ) );
            //4.7
            if ( FALSE != stripos( $basedomain, '/' ) ) {
                $basedomain = substr( $basedomain, 0, stripos( $basedomain, '/' ) );
            }
            $streamContext = stream_context_create( [
                'ssl' => [
                'capture_peer_cert' => true,
            ],
            ] );
            $errorNumber = $errorDescription = '';
            $client = @stream_socket_client(
                "ssl://{$basedomain}:443",
                $errorNumber,
                $errorDescription,
                30,
                STREAM_CLIENT_CONNECT,
                $streamContext
            );
            
            if ( !$client ) {
                wp_redirect( admin_url( '/admin.php?page=wp_encryption&success=1&nossl=1', 'http' ) );
                exit;
            }
            
            // $SSLCheck = @fsockopen("ssl://" . $basedomain, 443, $errno, $errstr, 30);
            // if (!$SSLCheck) {
            //   wp_redirect(admin_url('/admin.php?page=wp_encryption&success=1&nossl=1', 'http'));
            //   exit();
            // }
            $reverter = uniqid( 'wple' );
            $savedopts = get_option( 'wple_opts' );
            $savedopts['force_ssl'] = 1;
            $savedopts['revertnonce'] = $reverter;
            $this->wple_send_reverter_secret( $reverter );
            update_option( 'wple_opts', $savedopts );
            delete_option( 'wple_error' );
            //complete
            update_option( 'wple_complete', 1 );
            update_option( 'siteurl', str_ireplace( 'http:', 'https:', get_option( 'siteurl' ) ) );
            update_option( 'home', str_ireplace( 'http:', 'https:', get_option( 'home' ) ) );
            wp_redirect( admin_url( '/admin.php?page=wp_encryption', 'https' ) );
            exit;
        }
        
        //single domain ssl
        
        if ( isset( $_POST['generate-certs'] ) ) {
            if ( !wp_verify_nonce( $_POST['letsencrypt'], 'legenerate' ) ) {
                die( 'Unauthorized request' );
            }
            if ( empty($_POST['wple_email']) ) {
                wp_die( esc_html__( 'Please input valid email address', 'wp-letsencrypt-ssl' ) );
            }
            $leopts = array(
                'email'         => sanitize_email( $_POST['wple_email'] ),
                'date'          => date( 'd-m-Y' ),
                'expiry'        => '',
                'type'          => 'single',
                'send_usage'    => ( isset( $_POST['wple_send_usage'] ) ? 1 : 0 ),
                'include_www'   => ( isset( $_POST['wple_include_www'] ) ? 1 : 0 ),
                'agree_gws_tos' => ( isset( $_POST['wple_agree_gws_tos'] ) ? 1 : 0 ),
                'agree_le_tos'  => ( isset( $_POST['wple_agree_le_tos'] ) ? 1 : 0 ),
            );
            
            if ( isset( $_POST['wple_domain'] ) && !is_multisite() ) {
                $leopts['subdir'] = 1;
                $leopts['domain'] = sanitize_text_field( $_POST['wple_domain'] );
            }
            
            update_option( 'wple_opts', $leopts );
            new WPLE_Core( $leopts );
        }
    
    }
    
    /**
     * Download cert files based on clicked link
     *
     * certs for multisite mapped domains cannot be downloaded yet
     * @since 1.0.0
     * @return void
     */
    public function wple_download_files()
    {
        
        if ( isset( $_GET['le'] ) && current_user_can( 'manage_options' ) ) {
            switch ( $_GET['le'] ) {
                case '1':
                    $file = uniqid() . '-cert.crt';
                    file_put_contents( $file, file_get_contents( ABSPATH . 'keys/certificate.crt' ) );
                    break;
                case '2':
                    $file = uniqid() . '-key.pem';
                    file_put_contents( $file, file_get_contents( ABSPATH . 'keys/private.pem' ) );
                    break;
                case '3':
                    $file = uniqid() . '-cabundle.crt';
                    file_put_contents( $file, file_get_contents( WPLE_DIR . 'cabundle/ca.crt' ) );
                    break;
            }
            header( 'Content-Description: File Transfer' );
            header( 'Content-Type: text/plain' );
            header( 'Content-Length: ' . filesize( $file ) );
            header( 'Content-Disposition: attachment; filename=' . basename( $file ) );
            readfile( $file );
            exit;
        }
    
    }
    
    /**
     * Rate us admin notice
     *
     * @since 2.0.0 
     * @return void
     */
    public function wple_rateus()
    {
        $cert = ABSPATH . 'keys/certificate.crt';
        
        if ( file_exists( $cert ) ) {
            if ( isset( $_GET['page'] ) && $_GET['page'] == 'wp_encryption' ) {
                return;
            }
            $already_did = wp_nonce_url( admin_url( 'admin.php?page=wp_encryption' ), 'wple_reviewed', 'wplerated' );
            $remind_later = wp_nonce_url( admin_url( 'admin.php?page=wp_encryption' ), 'wple_review_later', 'wplelater' );
            $html = '<div class="notice notice-info wple-admin-review">
        <div class="wple-review-box">
          <img src="' . WPLE_URL . 'admin/assets/symbol.png"/>
          <span><strong>' . esc_html__( 'Congratulations!', 'wp-letsencrypt-ssl' ) . '</strong><p>' . $this->wple_kses( __( 'SSL certificate generated successfully!. <b>WP Encryption</b> just saved you several $$$ by generating free SSL certificate in record time!. Could you please do us a BIG favor & rate us with 5 star review to support further development of this plugin.', 'wp-letsencrypt-ssl' ) ) . '</p></span>
        </div>
        <a class="wple-lets-review wplerevbtn" href="https://wordpress.org/support/plugin/wp-letsencrypt-ssl/reviews/#new-post" rel="nofollow" target="_blank">' . esc_html__( 'Rate plugin', 'wp-letsencrypt-ssl' ) . '</a>
        <a class="wple-did-review wplerevbtn" href="' . $already_did . '" rel="nofollow">' . esc_html__( 'I already did', 'wp-letsencrypt-ssl' ) . '&nbsp;<span class="dashicons dashicons-smiley"></span></a>
        <a class="wple-later-review wplerevbtn" href="' . $remind_later . '" rel="nofollow">' . esc_html__( 'Remind me later', 'wp-letsencrypt-ssl' ) . '&nbsp;<span class="dashicons dashicons-clock"></span></a>
      </div>';
            echo  $html ;
        }
    
    }
    
    /**
     * Check if wp install is IP or subdir based
     *
     * @since 2.4.0
     * @return void
     */
    public function wple_subdir_ipaddress()
    {
        $siteURL = str_ireplace( array( 'http://', 'https://', 'www.' ), array( '', '', '' ), site_url() );
        $flg = 0;
        if ( filter_var( $siteURL, FILTER_VALIDATE_IP ) ) {
            $flg = 1;
        }
        if ( FALSE !== stripos( $siteURL, 'localhost' ) ) {
            $flg = 1;
        }
        
        if ( FALSE != stripos( $siteURL, '/' ) && is_multisite() ) {
            $html = '<div class="wrap" id="le-wrap">
      <div class="le-inner">
        <div class="wple-header">
          <img src="' . WPLE_URL . 'admin/assets/logo.png" class="wple-logo"/> <span class="wple-version">v' . esc_html( WPLE_VERSION ) . '</span>
        </div>
        <div class="wple-warning-notice">
        <h2>' . esc_html__( 'You do not need to install SSL for each sub-directory site in multisite, Please install SSL for your primary domain and it will cover ALL sub directory sites too.', 'wp-letsencrypt-ssl' ) . '</h2>
        </div>
      </div>
      </div>';
            echo  $html ;
            wp_die();
        }
        
        
        if ( $flg ) {
            $html = '<div class="wrap" id="le-wrap">
      <div class="le-inner">
        <div class="wple-header">
          <img src="' . WPLE_URL . 'admin/assets/logo.png" class="wple-logo"/> <span class="wple-version">v' . esc_html( WPLE_VERSION ) . '</span>
        </div>
        <div class="wple-warning-notice">
        <h2>' . esc_html__( 'SSL Certificates cannot be issued for localhost and IP address based WordPress site. Please use this on your real domain based WordPress site.', 'wp-letsencrypt-ssl' ) . ' ' . esc_html__( 'This restriction is not implemented by WP Encryption but its how SSL certificates work.', 'wp-letsencrypt-ssl' ) . '</h2>
        </div>
      </div>
      </div>';
            echo  $html ;
            wp_die();
        }
    
    }
    
    /**
     * Upgrade to PRO
     *
     * @param string $html
     * @since 2.5.0
     * @return void
     */
    public function wple_upgrade_block( &$html )
    {
        $upgradeurl = admin_url( '/admin.php?page=wp_encryption-pricing' );
        $automatic = esc_html__( 'Automatic', 'wp-letsencrypt-ssl' );
        $manual = esc_html__( 'Manual', 'wp-letsencrypt-ssl' );
        $domain = str_ireplace( array( 'https://', 'http://', 'www.' ), '', site_url() );
        $dverify = $automatic;
        if ( stripos( $domain, '/' ) != FALSE ) {
            //subdir site
            $dverify = $manual;
        }
        $html .= ' 
      <div id="wple-upgradepro">';
        if ( FALSE !== ($cp = get_option( 'wple_have_cpanel' )) && $cp ) {
            $html .= '<strong style="display: block; text-align: center; color: #666;">Tired of manual SSL renewal every 90 days? WP Encryption Pro is 100% guaranteed to work on your cPanel hosted site! 7 Days Refund Policy - 100% Money Back Guarantee</strong>';
        }
        $html .= '<div class="wple-plans">
            <span class="free">* ' . esc_html__( 'FREE', 'wp-letsencrypt-ssl' ) . '</span>
            <span class="pro">* ' . esc_html__( 'PRO', 'wp-letsencrypt-ssl' ) . '</span>
          </div>
          <div class="wple-plan-compare">
            <div class="wple-compare-item">
              <img src="' . WPLE_URL . 'admin/assets/verified.png"/>
              <h4>' . esc_html__( 'HTTP Verification', 'wp-letsencrypt-ssl' ) . '</h4>
              <span class="wple-free">' . $manual . '</span>
              <span class="wple-pro">' . $automatic . '</span>
            </div>
            <div class="wple-compare-item">
              <img src="' . WPLE_URL . 'admin/assets/DNS.png"/>
              <h4>' . esc_html__( 'DNS Verification', 'wp-letsencrypt-ssl' ) . ' <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="' . esc_attr__( 'In case of HTTP verification fail / not possible', 'wp-letsencrypt-ssl' ) . '"></span></h4>
              <span class="wple-free">' . $manual . '</span>
              <span class="wple-pro">' . $automatic . '</span>
            </div>
            <div class="wple-compare-item">
              <img src="' . WPLE_URL . 'admin/assets/Certificate.png"/>
              <h4>' . esc_html__( 'Certificate Issuance', 'wp-letsencrypt-ssl' ) . '</h4>
              <span class="wple-free">' . $automatic . '</span>
              <span class="wple-pro">' . $automatic . '</span>
            </div>
            <div class="wple-compare-item">
              <img src="' . WPLE_URL . 'admin/assets/Install.png"/>
              <h4>' . esc_html__( 'Certificate Installation', 'wp-letsencrypt-ssl' ) . ' <!--<span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="' . esc_attr__( 'PRO - We offer one time free manual support for non-cPanel based sites', 'wp-letsencrypt-ssl' ) . '"></span>--></h4>
              <span class="wple-free">' . $manual . '</span>
              <span class="wple-pro">' . $automatic . '</span>
            </div>
            <div class="wple-compare-item">
              <img src="' . WPLE_URL . 'admin/assets/renewal.png"/>
              <h4>' . esc_html__( 'Auto Renewal', 'wp-letsencrypt-ssl' ) . ' <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="' . esc_attr__( 'Expires in 90 days', 'wp-letsencrypt-ssl' ) . '"></span></h4>
              <span class="wple-free">' . $manual . '</span>
              <span class="wple-pro">' . $automatic . '</span>
            </div>
            <div class="wple-compare-item">
              <img src="' . WPLE_URL . 'admin/assets/wildcard.png"/>
              <h4>' . esc_html__( 'Wildcard SSL', 'wp-letsencrypt-ssl' ) . ' <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="' . esc_attr__( 'PRO - Your domain DNS must be managed by cPanel or Godaddy for full automation', 'wp-letsencrypt-ssl' ) . '"></span></h4>
              <span class="wple-free">' . esc_html__( 'Not Available', 'wp-letsencrypt-ssl' ) . '</span>
              <span class="wple-pro">' . esc_html__( 'Available', 'wp-letsencrypt-ssl' ) . '</span>
            </div>
            <div class="wple-compare-item">
              <img src="' . WPLE_URL . 'admin/assets/multisite.png"/>
              <h4>' . esc_html__( 'Multisite Support', 'wp-letsencrypt-ssl' ) . ' <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="' . esc_attr__( 'PRO - Support for Multisite + Mapped domains', 'wp-letsencrypt-ssl' ) . '"></span></h4>
              <span class="wple-free">' . esc_html__( 'Not Available', 'wp-letsencrypt-ssl' ) . '</span>
              <span class="wple-pro">' . esc_html__( 'Available', 'wp-letsencrypt-ssl' ) . '</span>
            </div>            
          </div>

          <div class="wple-upgrade-pro">
              <a href="https://wpencryption.com?utm_source=wordpress&utm_medium=comparison&utm_campaign=wpencryption" target="_blank" class="wplecompare">' . esc_html__( 'COMPARE FREE & PRO VERSION', 'wp-letsencrypt-ssl' ) . ' <span class="dashicons dashicons-external"></span></a>';
        
        if ( isset( $_GET['success'] ) ) {
            $html .= '<a href="' . $upgradeurl . '">' . esc_html__( 'UPGRADE TO PRO', 'wp-letsencrypt-ssl' ) . '<span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Requires cPanel or root SSH access"></span></a>
                <a href="https://wpencryption.com/#firewall" target="_blank">' . esc_html__( 'UPGRADE TO FIREWALL', 'wp-letsencrypt-ssl' ) . '<span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Why buy an SSL alone when you can get Premium SSL + CDN + Firewall Security for even lower cost."></span></a>';
        } else {
            $html .= '<a href="' . $upgradeurl . '">' . esc_html__( 'UPGRADE TO PRO', 'wp-letsencrypt-ssl' ) . '</a>';
        }
        
        $html .= '</div>
      </div><!--wple-upgradepro-->
      ';
    }
    
    /**
     * Success Message block
     *
     * @param string $html
     * @since 2.5.0
     * @return void
     */
    public function wple_success_block( &$html )
    {
        //since 2.4.0
        
        if ( isset( $_GET['success'] ) ) {
            $this->wple_wellknown_htaccess();
            update_option( 'wple_error', 5 );
            //all success
            $html .= '
      <div id="wple-sslgenerator">
      <div class="wple-success-form">';
            if ( !isset( $_GET['resume'] ) && !isset( $_GET['nossl'] ) ) {
                $this->wple_send_success_mail();
            }
            $html .= '<h2><span class="dashicons dashicons-yes"></span>&nbsp;' . $this->wple_kses( __( '<b>Congrats! SSL Certificate have been successfully generated.</b>', 'wp-letsencrypt-ssl' ) ) . '</h2>
        <h3 style="width: 87%; margin: 0px auto; color: #7b8279; font-weight:400;">' . $this->wple_kses( __( 'We just completed major task of generating SSL certificate! Now we have ONE final step to complete.', 'wp-letsencrypt-ssl' ) ) . '</h3>';
            $html .= WPLE_Trait::wple_progress_bar();
            $html .= '   

        <div class="wple-success-flex">
        <div class="wple-success-flex-video">
        <iframe width="560" height="315" src="https://www.youtube.com/embed/aKvvVlAlZ14" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
        <div class="wple-success-flex-final">        
        <ul class="download-ssl-certs">
          <li>
            <span>1. ' . esc_html__( 'Download SSL certificates from below', 'wp-letsencrypt-ssl' ) . '</span>
            <ul>
              <li class="le-dwnld"><a href="?page=wp_encryption&le=1">' . esc_html__( 'Download cert file', 'wp-letsencrypt-ssl' ) . '</a></li>
              <li class="le-dwnld"><a href="?page=wp_encryption&le=2">' . esc_html__( 'Download key file', 'wp-letsencrypt-ssl' ) . '</a></li>
              <li class="le-dwnld"><a href="?page=wp_encryption&le=3">' . esc_html__( 'Download ca bundle', 'wp-letsencrypt-ssl' ) . '</a></li>
            </ul>
          </li>
          <li>2. ' . sprintf( __( 'Open %sSSL/TLS%s option on your cPanel', 'wp-letsencrypt-ssl' ), '<strong>', '</strong>' ) . '</li>
          <li>3. ' . esc_html__( 'Copy/Paste cert files into appropriate fields', 'wp-letsencrypt-ssl' ) . '</li>
          <li>4. ' . sprintf( __( 'Click on %sInstall certificate%s', 'wp-letsencrypt-ssl' ), '<strong>', '</strong>' ) . '</li>
          <li>5. ' . sprintf( __( 'Please wait few minutes and click on %sEnable HTTPS Now%s button', 'wp-letsencrypt-ssl' ), '<strong>', '</strong>' ) . '</li>
        </ul>
        </div>
        </div>  

            <div class="wple-success-cols">
              <div>
                <h3>' . esc_html__( "Don't have cPanel?", 'wp-letsencrypt-ssl' ) . '</h3>
                <p>' . sprintf( __( 'Download the certs and send it to your hosting support to install it for you. If you have root SSH access, you can refer our %sDOCS%s for server specific instructions.', 'wp-letsencrypt-ssl' ), '<a href="https://wpencryption.com/docs/?cp=' . esc_attr( get_option( 'wple_have_cpanel' ) ) . '" target="_blank">', '</a>' ) . '</p>
              </div>
              <div>
                <h3>' . esc_html__( "Test SSL Installation", 'wp-letsencrypt-ssl' ) . '</h3>
                <p>' . esc_html__( "After installing SSL certs on your cPanel, open your site in https:// and click on padlock to see if valid certificate exists. You can also test your site's SSL on SSLLabs.com", "wp-letsencrypt-ssl" ) . '</p>
              </div>
              <div>
                <h3>' . esc_html__( "By Clicking Enable HTTPS", 'wp-letsencrypt-ssl' ) . '</h3>
                <p>' . esc_html__( 'Your site & admin url will be changed to https:// and all assets, js, css, images will strictly load over https:// to avoid mixed content errors.', 'wp-letsencrypt-ssl' ) . '</p>
              </div>
              <div>
                <h3>' . esc_html__( "Looking for instant SSL solution?", 'wp-letsencrypt-ssl' ) . '</h3>
                <p>' . sprintf(
                __( 'Why pay for an SSL certificate alone when you can get %sPremium Sectigo SSL%s + %sCDN Performance%s + %sSecurity Firewall%s with our %sFIREWALL%s Service. Get started with %s7 days free trial%s', 'wp-letsencrypt-ssl' ),
                '<strong>',
                '</strong>',
                '<strong>',
                '</strong>',
                '<strong>',
                '</strong>',
                '<a href="https://wpencryption.com/#firewall" target="_blank">',
                '</a>',
                '<strong>',
                '</strong>'
            ) . '!.</p>
              </div>              
            </div>

          <ul>          
          <!--<li>' . $this->wple_kses( __( '<b>Note:</b> Use below "Enable HTTPS" button ONLY after SSL certificate is successfully installed on your cPanel', 'wp-letsencrypt-ssl' ) ) . '</li>-->
          </ul>';
            if ( isset( $_GET['nossl'] ) ) {
                $html .= '<h3 style="color:#ff4343;margin-bottom:10px;">' . esc_html__( 'We could not detect valid SSL certificate on your site!. Please wait as it may take few minutes if you installed SSL certificate on your cPanel / Server just now.', 'wp-letsencrypt-ssl' ) . '</h3>
        <p>' . esc_html__( 'Switching to HTTPS without properly installing the SSL certificate might break your site.', 'wp-letsencrypt-ssl' ) . '</p>';
            }
            $html .= '<form method="post">
        ' . wp_nonce_field(
                'wplehttps',
                'sslready',
                false,
                false
            ) . '
        <button type="submit" name="wple-https">' . esc_html__( 'ENABLE HTTPS NOW', 'wp-letsencrypt-ssl' ) . '</button>
        </form>
        </div>
        </div><!--wple-sslgenerator-->';
        }
    
    }
    
    /**
     * Show pending challenges
     *
     * @return void
     */
    public function wple_domain_verification()
    {
        //since 5.1.0
        
        if ( isset( $_GET['restart'] ) ) {
            delete_option( 'wple_error' );
            delete_option( 'wple_complete' );
            wp_redirect( admin_url( '/admin.php?page=wp_encryption' ), 302 );
            exit;
        }
        
        $estage = get_option( 'wple_error' );
        
        if ( FALSE !== $estage && $estage == 2 && !isset( $_GET['subdir'] ) && !isset( $_GET['error'] ) && !isset( $_GET['includewww'] ) && !isset( $_GET['wpleauto'] ) && isset( $_GET['page'] ) && $_GET['page'] == 'wp_encryption' && !isset( $_GET['success'] ) && !isset( $_GET['wplereset'] ) ) {
            wp_redirect( admin_url( '/admin.php?page=wp_encryption&subdir=1' ), 302 );
            exit;
        }
        
        
        if ( FALSE !== $estage && $estage == 5 && !isset( $_GET['subdir'] ) && !isset( $_GET['error'] ) && !isset( $_GET['includewww'] ) && !isset( $_GET['wpleauto'] ) && isset( $_GET['page'] ) && $_GET['page'] == 'wp_encryption' && !isset( $_GET['resume'] ) && !isset( $_GET['nossl'] ) && !isset( $_GET['wplereset'] ) ) {
            wp_redirect( admin_url( '/admin.php?page=wp_encryption&success=1&resume=1' ), 302 );
            exit;
        }
    
    }
    
    /**
     * Error Message block
     *
     * @param string $html
     * @since 2.5.0
     * @return void
     */
    public function wple_error_block( &$html )
    {
        if ( !isset( $_GET['subdir'] ) && !isset( $_GET['success'] ) ) {
            
            if ( isset( $_GET['error'] ) || FALSE != ($error_code = get_option( 'wple_error' )) ) {
                $error_code = get_option( 'wple_error' );
                $generic = esc_html__( 'There was some issue while generating SSL for your site. Please check debug log or try Reset option once.', 'wp-letsencrypt-ssl' );
                $generic .= '<p style="font-size:16px;color:#888">' . sprintf( esc_html__( 'Feel free to open support ticket at %s for any help.', 'wp-letsencrypt-ssl' ), 'https://wordpress.org/support/plugin/wp-letsencrypt-ssl/#new-topic-0' ) . '</p>';
                $firerec = sprintf(
                    esc_html__( "We highly recommend switching to our %sFIREWALL%s service for %sPremium SSL%s + %sCDN%s + %sFirewall Security%s.", 'wp-letsencrypt-ssl' ),
                    '<a href="https://wpencryption.com/#firewall" target="_blank">',
                    '</a>',
                    '<strong>',
                    '</strong>',
                    '<strong>',
                    '</strong>',
                    '<strong>',
                    '</strong>'
                );
                $thirdparty = esc_html__( "Your hosting server don't seem to support third party SSL.", "wp-letsencrypt-ssl" );
                
                if ( FALSE !== $error_code && ($error_code == 1 || $error_code == 400 || $error_code == 2) ) {
                    $generic .= '<p class="firepro">' . $thirdparty . ' ' . sprintf(
                        esc_html__( "We highly recommend switching to our %sFIREWALL%s service for %sPremium SSL%s + %sCDN%s + %sFirewall Security%s.", 'wp-letsencrypt-ssl' ),
                        '<a href="https://wpencryption.com/#firewall" target="_blank">',
                        '</a>',
                        '<strong>',
                        '</strong>',
                        '<strong>',
                        '</strong>',
                        '<strong>',
                        '</strong>'
                    ) . '</p>';
                } else {
                    if ( file_exists( ABSPATH . 'keys/certificate.crt' ) ) {
                        $generic .= '<br><br>' . $this->wple_kses( __( 'You already seem to have certificate generated and stored. Please try downloading certs from <strong>Download SSL Certificates</strong> page and open in a text editor like notepad to check if certificate is not empty.', 'wp-letsencrypt-ssl' ) );
                    }
                }
                
                
                if ( FALSE !== $error_code && $error_code == 429 ) {
                    $generic = sprintf( esc_html__( 'Too many registration attempts from your IP address (%s). Please try after 2-3 hours.', 'wp-letsencrypt-ssl' ), 'https://letsencrypt.org/docs/rate-limits/' );
                    $generic .= '<p class="firepro">' . $firerec . '</p>';
                    $generic .= '<p style="font-size:17px;color:#888">' . sprintf( esc_html__( 'Feel free to open support ticket at %s for any help.', 'wp-letsencrypt-ssl' ), 'https://wordpress.org/support/plugin/wp-letsencrypt-ssl/#new-topic-0' ) . '</p>';
                }
                
                if ( $error_code != 5 ) {
                    $html .= '
          <div id="wple-sslgenerator" class="error">
            <div class="wple-error-message">
              ' . $generic . '
            </div>
          </div><!--wple-sslgenerator-->';
                }
            }
        
        }
    }
    
    /**
     * Send email to user on success
     * 
     * @since 3.0.0
     */
    private function wple_send_success_mail()
    {
        $opts = get_option( 'wple_opts' );
        $to = sanitize_email( $opts['email'] );
        $subject = esc_html__( 'Congratulations! Your SSL certificates have been generated using WP Encryption Plugin', 'wp-letsencrypt-ssl' );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        $body = '<h2>' . esc_html__( 'You are just ONE step behind enabling HTTPS for your WordPress site', 'wp-letsencrypt-ssl' ) . '</h2>';
        $body .= '<p>' . esc_html__( 'Download the generated SSL certificates from below given links and install it on your cPanel following the video tutorial', 'wp-letsencrypt-ssl' ) . ' (https://youtu.be/KQ2HYtplPEk). ' . esc_html__( 'These certificates expires on', 'wp-letsencrypt-ssl' ) . ' <b>' . esc_html( $opts['expiry'] ) . '</b></p>
        <br/>
        <a href="' . admin_url( '/admin.php?page=wp_encryption&le=1', 'http' ) . '" style="background: #0073aa; text-decoration: none; color: #fff; padding: 12px 20px; display: inline-block; margin: 10px 10px 10px 0; font-weight: bold;">' . esc_html__( 'Download Cert File', 'wp-letsencrypt-ssl' ) . '</a>
      <a href="' . admin_url( '/admin.php?page=wp_encryption&le=2', 'http' ) . '" style="background: #0073aa; text-decoration: none; color: #fff; padding: 12px 20px; display: inline-block; margin: 10px; font-weight: bold;">' . esc_html__( 'Download Key File', 'wp-letsencrypt-ssl' ) . '</a>
      <a href="' . admin_url( '/admin.php?page=wp_encryption&le=3', 'http' ) . '" style="background: #0073aa; text-decoration: none; color: #fff; padding: 12px 20px; display: inline-block; margin: 10px; font-weight: bold;">' . esc_html__( 'Download CA File', 'wp-letsencrypt-ssl' ) . '</a>
      <br/>
        <img src="' . site_url( '/wp-content/plugins/wp-letsencrypt-ssl/admin/assets/free-vs-pro.png', 'http' ) . '"/><br /><br />';
        $body .= '<b>' . esc_html__( 'WP Encryption PRO can automate this entire process in one click including SSL installation on cPanel hosting and auto renewal of certificates every 90 days', 'wp-letsencrypt-ssl' ) . '!. <br><a href="' . admin_url( '/admin.php?page=wp_encryption-pricing', 'http' ) . '" style="background: #0073aa; text-decoration: none; color: #fff; padding: 12px 20px; display: inline-block; margin: 10px 0; font-weight: bold;">' . esc_html__( 'UPGRADE TO PREMIUM', 'wp-letsencrypt-ssl' ) . '</a></b><br /><br />';
        $body .= "<h3>" . esc_html__( "Don't have cPanel hosting?", 'wp-letsencrypt-ssl' ) . "</h3>";
        $body .= '<p>' . $this->wple_kses( __( 'We offer one time free manual support for Premium users for installing the generated SSL certificates via <b>SSH</b>. With free version, You can download and send these SSL certificates to your hosting support asking them to install these SSL certificates.', 'wp-letsencrypt-ssl' ) ) . '</p><br /><br />';
        wp_mail(
            $to,
            $subject,
            $body,
            $headers
        );
    }
    
    /**
     * Ability to revert back to HTTP
     *
     * @since 3.3.0
     * @param string $revertcode
     * @return void
     */
    private function wple_send_reverter_secret( $revertcode )
    {
        $to = get_bloginfo( 'admin_email' );
        $sub = esc_html__( 'You have successfully forced HTTPS on your site', 'wp-letsencrypt-ssl' );
        $header = array( 'Content-Type: text/html; charset=UTF-8' );
        $rcode = sanitize_text_field( $revertcode );
        $body = $this->wple_kses( __( "HTTPS have been strictly forced on your site now!. In rare cases, this may cause issue / make the site un-accessible <b>IF</b> you dont have valid SSL certificate installed for your WordPress site. Kindly save the below <b>Secret code</b> to revert back to HTTP in such a case.", 'wp-letsencrypt-ssl' ) ) . "\r\n      <br><br>\r\n      <strong>{$rcode}</strong><br><br>" . $this->wple_kses( __( "Opening the revert url will <b>IMMEDIATELY</b> turn back your site to HTTP protocol & revert back all the force SSL changes made by WP Encryption in one go!. Please follow instructions given at https://wordpress.org/support/topic/locked-out-unable-to-access-site-after-forcing-https-2/", 'wp-letsencrypt-ssl' ) ) . "<br>\r\n      <br>\r\n      " . esc_html__( "Revert url format", 'wp-letsencrypt-ssl' ) . ": http://yourdomainname.com/?reverthttps=SECRETCODE<br>\r\n      " . esc_html__( "Example:", 'wp-letsencrypt-ssl' ) . " http://gowebsmarty.in/?reverthttps=wple43643sg5qaw<br>\r\n      <br>\r\n      " . esc_html__( "We have spent several hours to craft this plugin to perfectness. Please take a moment to rate us with 5 stars", 'wp-letsencrypt-ssl' ) . " - https://wordpress.org/support/plugin/wp-letsencrypt-ssl/reviews/#new-post\r\n      <br />";
        wp_mail(
            $to,
            $sub,
            $body,
            $header
        );
    }
    
    /**
     * Escape html but retain bold
     *
     * @since 3.3.3
     * @param string $translated
     * @param string $additional Additional allowed html tags
     * @return void
     */
    private function wple_kses( $translated, $additional = '' )
    {
        $allowed = array(
            'strong' => array(),
            'b'      => array(),
        );
        if ( $additional == 'a' ) {
            $allowed['a'] = array(
                'href'   => array(),
                'rel'    => array(),
                'target' => array(),
                'title'  => array(),
            );
        }
        return wp_kses( $translated, $allowed );
    }
    
    /**
     * Handles review box actions
     *
     * @since 4.4.0
     * @return void
     */
    public function wple_review_handler()
    {
        
        if ( isset( $_GET['wplerated'] ) ) {
            if ( !wp_verify_nonce( $_GET['wplerated'], 'wple_reviewed' ) ) {
                wp_die( 'Unauthorized request' );
            }
            delete_option( 'wple_show_review' );
            wp_redirect( admin_url( '/admin.php?page=wp_encryption' ), 302 );
        } else {
            
            if ( isset( $_GET['wplelater'] ) ) {
                if ( !wp_verify_nonce( $_GET['wplelater'], 'wple_review_later' ) ) {
                    wp_die( 'Unauthorized request' );
                }
                delete_option( 'wple_show_review' );
                wp_schedule_single_event( strtotime( '+3 day', time() ), 'wple_show_reviewrequest' );
                wp_redirect( admin_url( '/admin.php?page=wp_encryption' ), 302 );
            }
        
        }
        
        //since 5.0.0
        $this->wple_intro_pricing_handler();
    }
    
    /**
     * Sets review flag to show review request
     * 
     * @since 4.4.0
     */
    public function wple_set_review_flag()
    {
        update_option( 'wple_show_review', 1 );
    }
    
    /**
     * Handle the reset keys action
     *
     * @since 4.5.0
     * @return void
     */
    public function wple_reset_handler()
    {
        
        if ( isset( $_GET['wplereset'] ) ) {
            if ( !current_user_can( 'manage_options' ) ) {
                exit( 'No Trespassing Allowed' );
            }
            if ( !wp_verify_nonce( $_GET['wplereset'], 'restartwple' ) ) {
                exit( 'No Trespassing Allowed' );
            }
            $keys = ABSPATH . 'keys/';
            $files = array(
                $keys . 'public.pem',
                $keys . 'private.pem',
                $keys . 'order',
                $keys . 'fullchain.crt',
                $keys . 'certificate.crt',
                $keys . '__account/private.pem',
                $keys . '__account/public.pem'
            );
            foreach ( $files as $file ) {
                if ( file_exists( $file ) ) {
                    unlink( $file );
                }
            }
            delete_option( 'wple_error' );
            delete_option( 'wple_complete' );
            add_action( 'admin_notices', array( $this, 'wple_reset_success' ) );
        }
        
        //since 4.6.0
        
        if ( isset( $_GET['wplesslrenew'] ) ) {
            if ( !wp_verify_nonce( $_GET['wplesslrenew'], 'wple_renewed' ) ) {
                exit( 'Unauthorized' );
            }
            delete_option( 'wple_show_reminder' );
            wp_redirect( admin_url( '/admin.php?page=wp_encryption' ), 302 );
        }
    
    }
    
    /**
     * Reset success notice
     * 
     * @since 4.5.0
     */
    public function wple_reset_success()
    {
        echo  '<div class="notice notice-success is-dismissable">
    <p>' . esc_html( 'Reset successful!. You can start with the SSL install process again.', 'wp-letsencrypt-ssl' ) . '</p>
    </div>' ;
    }
    
    /**
     * Local check DNS records via Ajax
     * 
     * @since 4.6.0
     * @return void
     */
    public function wple_ajx_verify_dns()
    {
        
        if ( isset( $_POST['nc'] ) ) {
            if ( !wp_verify_nonce( $_POST['nc'], 'verifydnsrecords' ) ) {
                exit( 'Unauthorized' );
            }
            $toVerify = get_option( 'wple_opts' );
            
            if ( array_key_exists( 'dns_challenges', $toVerify ) && !empty($toVerify['dns_challenges']) ) {
                $toVerify = array_map( 'sanitize_text_field', $toVerify['dns_challenges'] );
                //array
                $failflag = 0;
                foreach ( $toVerify as $index => $item ) {
                    $domain_code = explode( '||', $item );
                    $acme = '_acme-challenge.' . esc_html( $domain_code[0] );
                    $requestURL = 'https://dns.google.com/resolve?name=' . addslashes( $acme ) . '&type=TXT';
                    $fh = fopen( WPLE_DEBUGGER . 'debug.log', 'a' );
                    fwrite( $fh, "\n" . wp_kses_post( $requestURL ) . "\n" );
                    fclose( $fh );
                    $handle = curl_init();
                    curl_setopt( $handle, CURLOPT_URL, $requestURL );
                    curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
                    curl_setopt( $handle, CURLOPT_FOLLOWLOCATION, true );
                    $response = json_decode( trim( curl_exec( $handle ) ) );
                    
                    if ( $response->Status === 0 && isset( $response->Answer ) ) {
                        foreach ( $response->Answer as $answer ) {
                            
                            if ( $answer->type === 16 ) {
                                $livecode = str_ireplace( '"', '', $answer->data );
                                if ( $livecode !== $domain_code[1] ) {
                                    $failflag = 1;
                                }
                                // $searchDNS = array_search(str_ireplace('"', '', $answer->data), $toVerify);
                                // if (FALSE !== $searchDNS) {
                                //   unset($toVerify[$searchDNS]);
                                // }
                            }
                        
                        }
                        // if (count($toVerify) == 0) { //all verified
                        //   echo '1';
                        //   exit();
                        //   // } else if (count($toVerify) == 1) {
                        //   //   $pending = array_values($toVerify);
                        //   //   echo esc_html($pending[0]);
                        //   //   exit();
                        // }
                    } else {
                        echo  'fail' ;
                    }
                
                }
            } else {
                
                if ( empty($toVerify['dns_challenges']) ) {
                    echo  1 ;
                    exit;
                }
                
                echo  'fail' ;
                exit;
            }
            
            
            if ( !$failflag ) {
                echo  1 ;
                exit;
            }
        
        }
        
        echo  'fail' ;
        exit;
    }
    
    /**
     * Show expiry reminder in admin notice
     *
     * @see 4.6.0
     * @return void
     */
    public function wple_start_show_reminder()
    {
        update_option( 'wple_show_reminder', 1 );
    }
    
    public function wple_reminder_notice()
    {
        $already_did = wp_nonce_url( admin_url( 'admin.php?page=wp_encryption' ), 'wple_renewed', 'wplesslrenew' );
        $html = '<div class="notice notice-info wple-admin-review">
        <div class="wple-review-box wple-reminder-notice">
          <img src="' . WPLE_URL . 'admin/assets/symbol.png"/>
          <span><strong>WP ENCRYPTION: ' . esc_html__( 'Your SSL certificate expires in less than 10 days', 'wp-letsencrypt-ssl' ) . '</strong><p>' . $this->wple_kses( __( 'Renew your SSL certificate today to avoid your site from showing as insecure. Please support our contribution by upgrading to <strong>Pro</strong> and avail automatic renewal with automatic installation.', 'wp-letsencrypt-ssl' ) ) . '</p></span>
        </div>
        <a class="wple-lets-review wplerevbtn" href="' . admin_url( '/admin.php?page=wp_encryption-pricing' ) . '">' . esc_html__( 'Upgrade to Pro', 'wp-letsencrypt-ssl' ) . '</a>
        <a class="wple-did-review wplerevbtn" href="' . $already_did . '">' . esc_html__( 'I already renewed', 'wp-letsencrypt-ssl' ) . '&nbsp;<span class="dashicons dashicons-smiley"></span></a>
      </div>';
        echo  $html ;
    }
    
    /**
     * Manual HTTP challenges for subdir sites
     *
     * @since 4.7.0
     * @param string $html
     * @param array $opts
     * @return string
     */
    public function wple_subdir_challenges( &$html, $opts )
    {
        if ( isset( $_GET['subdir'] ) ) {
            $html .= '
      <div id="wple-sslgenerator">
      <div class="wple-success-form">
          ' . WPLE_Subdir_Challenge_Helper::show_challenges( $opts ) . '
      </div>
      </div><!--wple-sslgenerator-->';
        }
    }
    
    /**
     * Local check HTTP records via Ajax for subdir sites
     * 
     * @since 4.7.0
     * @return void
     */
    public function wple_ajx_verify_http()
    {
        
        if ( isset( $_POST['nc'] ) ) {
            if ( !wp_verify_nonce( $_POST['nc'], 'verifyhttprecords' ) ) {
                exit( 'Unauthorized' );
            }
            $domain = str_ireplace( array( 'https://', 'http://' ), '', site_url() );
            if ( stripos( $domain, '/' ) != FALSE ) {
                //subdir site
                $domain = substr( $domain, 0, stripos( $domain, '/' ) );
            }
            $opts = get_option( 'wple_opts' );
            $httpch = $opts['challenge_files'];
            
            if ( empty($httpch) ) {
                echo  1 ;
                exit;
            }
            
            foreach ( $httpch as $index => $ch ) {
                $check = LEFunctions::checkHTTPChallenge( $domain, $ch['file'], $ch['value'] );
                
                if ( !$check ) {
                    echo  'fail' ;
                    exit;
                }
            
            }
            echo  1 ;
            exit;
        }
    
    }
    
    /**
     * Continue process on wpleauto param
     *
     * @return void
     */
    public function wple_continue_certification()
    {
        
        if ( isset( $_GET['wpleauto'] ) ) {
            $leopts = get_option( 'wple_opts' );
            
            if ( $_GET['wpleauto'] == 'http' ) {
                new WPLE_Core( $leopts );
            } else {
                //DNS
                new WPLE_Core(
                    $leopts,
                    true,
                    false,
                    true
                );
            }
        
        }
    
    }
    
    /**
     * Simple success notice for admin
     *
     * @since 4.7.2
     * @return void
     */
    public function wple_success_notice()
    {
        $html = '<div class="notice notice-success">
        <p>' . esc_html__( 'Success', 'wp-letsencrypt-ssl' ) . '!</p>
      </div>';
        echo  $html ;
    }
    
    /**
     * Show Pricing table once on activation
     *
     * @since 5.0.0
     * @param string $html
     * @return $html
     */
    public function wple_initial_quick_pricing( &$html )
    {
        $host = str_ireplace( array( 'https://', 'http://' ), array( '', '' ), site_url() );
        if ( FALSE != ($slashpos = stripos( $host, '/' )) ) {
            $host = substr( $host, 0, $slashpos );
        }
        $cp = ( is_ssl() ? 'https://' . $host . ':2083' : 'http://' . $host . ':2082' );
        $response = wp_remote_get( $cp, [
            'headers'   => [
            'Connection' => 'close',
        ],
            'sslverify' => false,
        ] );
        $cpanel = true;
        if ( is_wp_error( $response ) ) {
            $cpanel = false;
        }
        $html .= '<div id="wple-sslgen">';
        
        if ( $cpanel ) {
            update_option( 'wple_have_cpanel', 1 );
            $html .= $this->wple_cpanel_pricing_table( 'cPanel' );
        } else {
            update_option( 'wple_have_cpanel', 0 );
            $html .= $this->wple_cpanel_pricing_table( '' );
        }
        
        $html .= '</div>';
        echo  $html ;
    }
    
    /**
     * Pricing table html
     *
     * @since 5.0.0
     * @return $table
     */
    public function wple_cpanel_pricing_table( $cpanel = '' )
    {
        ob_start();
        ?>

      <h2 class="pricing-intro-head"><?php 
        esc_html_e( 'SAVE UPTO $60 EVERY YEAR IN SSL CERTIFICATE FEE', 'wp-letsencrypt-ssl' );
        ?></h2>

      <h4 class="pricing-intro-subhead">One time purchase! Hassle free Lifetime SSL with our PRO plugin - Trusted Globally by <b>40,000+</b> WordPress Sites (<a href="<?php 
        echo  admin_url( '/admin.php?page=wp_encryption&gopro=1' ) ;
        ?>">Looking for Unlimited sites license?</a>)</h4>

      <div id="quick-pricing-table">
        <div class="free-pricing-col wplepricingcol">
          <div class="quick-pricing-head free">
            <h3>FREE</h3>
            <large>$0</large>
          </div>
          <ul>
            <li><strong>Manual</strong> domain verification</li>
            <li><strong>Manual</strong> SSL installation</li>
            <li><strong>Manual</strong> SSL renewal</li>
            <li><strong>Expires</strong> in 90 days</li>
            <li><strong>Basic</strong> support</li>
          </ul>
          <div class="pricing-btn-block">
            <a href="<?php 
        echo  admin_url( '/admin.php?page=wp_encryption&gofree=1' ) ;
        ?>" class="pricingbtn free">Select Plan</a>
          </div>
        </div>

        <div class="pro-pricing-col wplepricingcol">
          <div class="quick-pricing-head pro">
            <span class="wple-trending">Popular</span>
            <h3>PRO</h3>
            <div class="quick-price-row">
              <large>$39<sup>.99</sup></large>
              <small>/lifetime</small>
            </div>
          </div>
          <ul>
            <li><strong>Automatic</strong> domain verification</li>
            <li><strong>Automatic</strong> SSL installation</li>
            <li><strong>Automatic</strong> SSL renewal</li>
            <li><strong>Wildcard</strong> SSL support <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="One SSL certificate to cover all your sub-domains"></span></li>
            <li><strong>Multisite</strong> mapped domains <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Install SSL for different domains mapped to your multisite network with MU domain mapping plugin"></span></li>
            <li><strong>DNS</strong> Automation <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Automatic Domain verification with DNS if HTTP domain verification fails"></span></li>
            <li><strong>Never</strong> expires <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Never worry about SSL again"></span></li>
            <li><strong>Priority</strong> support <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="gowebsmarty.in"></span></li>
          </ul>
          <div class="pricing-btn-block">
            <a href="<?php 
        echo  admin_url( '/admin.php?page=wp_encryption&gopro=1' ) ;
        ?>" class="pricingbtn free">Select Plan</a>
          </div>
        </div>

      </div>

      <br />
      <?php 
        if ( $cpanel != '' ) {
            ?>
        <div class="quick-refund-policy">
          <strong>7 Days Refund Policy - 100% Money back guarantee!</strong>
          <p>We are showing this recommendation because you have cPanel hosting where our PRO plugin is 100% guaranteed to work. Your purchase will be completely refunded if our plugin failed to work on your site.</p>
        </div>
      <?php 
        }
        ?>

    <?php 
        $table = ob_get_clean();
        return $table;
    }
    
    public function wple_firewall_pricing_table()
    {
        ob_start();
        ?>

      <h2 class="pricing-intro-head">PERFECT SOLUTION FOR YOUR NON-CPANEL SITE</h2>
      <h4 class="pricing-intro-subhead">Services worth whopping <strong>$360 ($30/m x 12 months)</strong> for as low as <strong>$3 per month</strong> <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Buying an SSL certificate alone would cost you atleast $60+ per year!"></span> (Service Partner "<strong>StackPath EDGE Services</strong>").</h4>

      <div id="quick-pricing-table" class="non-cpanel-plans">
        <div class="free-pricing-col wplepricingcol">
          <div class="quick-pricing-head free">
            <h3>FREE</h3>
            <large>$0</large>
          </div>
          <ul>
            <li><strong>Manual</strong> domain verification</li>
            <li><strong>Manual</strong> SSL installation</li>
            <li><strong>Manual</strong> SSL renewal</li>
            <li><strong>Expires</strong> in 90 days</li>
            <li><strong>Basic</strong> support</li>
          </ul>
          <div class="pricing-btn-block">
            <a href="<?php 
        echo  admin_url( '/admin.php?page=wp_encryption&gofree=1' ) ;
        ?>" class="pricingbtn free">Select Plan</a>
          </div>
        </div>

        <div class="pro-pricing-col wplepricingcol firewallplan">
          <div class="quick-pricing-head pro">
            <span class="wple-trending">Trending</span>
            <h3>FIREWALL</h3>
            <div class="quick-price-row">
              <large>$36<sup>.99</sup></large>
              <small>/year</small>
            </div>
          </div>
          <ul>
            <li><strong>Automatic</strong> domain verification</li>
            <li><strong>Automatic</strong> SSL installation</li>
            <li><strong>Automatic</strong> SSL renewal</li>
            <li><strong>Dashboard</strong> <a href="https://wpencryption.com/wp-content/uploads/2020/07/WP-Encryption-Metrics.jpg" target="_blank">metrics</a> <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Don't be blind! Keep an eye on your website traffic. Easily Monitor attack rate vs legitimate traffic, Search engine crawl rate, DDoS metrics right from your WordPress dashboard"></span></li>
            <li><strong>Instant</strong> <a href="https://wpencryption.com/#firewall" target="_blank">setup</a> <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Get! Set! Go! right from your WordPress dashboard using our easy to setup wizard"></span></li>
            <li><strong>Fastest</strong> CDN <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Your site is cached and served from 45 full-scale edge locations worldwide for fastest delivery and low TTFB thus improving Google pagespeed score"></span></li>
            <li><strong>Most Secure</strong> Firewall <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="All your site traffic routed through secure StackPath firewall offering protection against DDOS attacks, XSS, SQL injection, File inclusion, Common WordPress exploits, CSRF, etc.,"></span></li>
            <li><strong>Advanced</strong> caching <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Fastest Performance with advanced caching & Gzip compression"></span></li>
            <li><strong>Brute Force</strong> protection <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Block suspicious users and bots trying to guess/break your login"></span></li>
            <li><strong>Spam</strong> protection <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Protection against aggressive form submissions and bots"></span></li>
            <!-- <li><strong>No</strong> plugin required <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="Just need a minor change in your domain DNS to start working instantly"></span></li> -->
            <li><strong>Priority</strong> support</li>
          </ul>
          <div class="pricing-btn-block">
            <a href="<?php 
        echo  admin_url( '/admin.php?page=wp_encryption&gofirewall=1' ) ;
        ?>" class="pricingbtn free">Select Plan</a>
          </div>
        </div>

      </div>
      <div class="inro-pricing-refund">
        7 Days money back guarantee <span class="dashicons dashicons-editor-help wple-tooltip" data-tippy="If you are not satisfied with the service within 7 days of purchase, We will refund your purchase no questions asked"></span>
      </div>

  <?php 
        $table = ob_get_clean();
        return $table;
    }
    
    /**
     * Intro pricing table handler
     * 
     * @since 5.0.0     
     * @return void
     */
    public function wple_intro_pricing_handler()
    {
        $goplan = '';
        
        if ( isset( $_GET['gofree'] ) ) {
            update_option( 'wple_plan_choosen', 1 );
            wp_redirect( admin_url( '/admin.php?page=wp_encryption' ), 302 );
            exit;
        } else {
            
            if ( isset( $_GET['gopro'] ) ) {
                update_option( 'wple_plan_choosen', 1 );
                wp_redirect( admin_url( '/admin.php?page=wp_encryption-pricing' ), 302 );
                exit;
            } else {
                
                if ( isset( $_GET['gofirewall'] ) ) {
                    update_option( 'wple_plan_choosen', 1 );
                    wp_redirect( 'https://checkout.freemius.com/mode/dialog/plugin/5090/plan/10643/', 302 );
                    exit;
                }
            
            }
        
        }
    
    }
    
    /**
     * After all stages completion
     *
     * @param string $html
     * @return void
     */
    public function wple_completed_block( &$html )
    {
        $html .= WPLE_Trait::wple_progress_bar();
        $cert = ABSPATH . 'keys/certificate.crt';
        $leopts = get_option( 'wple_opts' );
        $future = strtotime( $leopts['expiry'] );
        //Future date.
        $timefromdb = time();
        $timeleft = $future - $timefromdb;
        $daysleft = round( $timeleft / 24 / 60 / 60 );
        $renewtext = esc_html__( 'Renew SSL Certificate', 'wp-letsencrypt-ssl' );
        $renewlink = '<a href="#" class="letsrenew wple-tooltip disabled" data-tippy="' . esc_html__( 'This renew button will get enabled during last 30 days of current SSL certificate expiry', 'wp-letsencrypt-ssl' ) . '">' . $renewtext . '</a>';
        if ( $daysleft <= 30 ) {
            $renewlink = '<a href="' . admin_url( '/admin.php?page=wp_encryption&restart=1' ) . '" class="letsrenew">' . $renewtext . '</a>';
        }
        $headline = esc_html__( 'Woohoo! WP Encryption just saved you $60+ in SSL Certificate Fee.', 'wp-letsencrypt-ssl' );
        $html .= '<div id="wple-completed">
        <div class="wple-completed-review">
          <h2>' . $headline . '</h2>
          <p>' . sprintf(
            __( 'Can you please do us a BIG favor by leaving a %s%s%s%s%s rating on WordPress.org', 'wp-letsencrypt-ssl' ),
            '<span class="dashicons dashicons-star-filled"></span>',
            '<span class="dashicons dashicons-star-filled"></span>',
            '<span class="dashicons dashicons-star-filled"></span>',
            '<span class="dashicons dashicons-star-filled"></span>',
            '<span class="dashicons dashicons-star-filled"></span>'
        ) . '</p>
          <a href="https://wordpress.org/support/plugin/wp-letsencrypt-ssl/reviews/#new-post" target="_blank" class="letsrate">' . esc_html__( 'LEAVE A RATING', 'wp-letsencrypt-ssl' ) . ' <span class="dashicons dashicons-external"></span></a>
          ' . $renewlink . '
          <small>' . esc_html__( 'Just takes a moment', 'wp-letsencrypt-ssl' ) . '</small>
        </div>';
        if ( file_exists( $cert ) && isset( $leopts['expiry'] ) ) {
            $html .= '<div class="wple-completed-remaining">
          <div class="progress--circle progress--' . esc_attr( $daysleft ) . '">
            <div class="progress__number"><strong>' . esc_html( $daysleft ) . '</strong><br><small>' . esc_html__( 'Days', 'wp-letsencrypt-ssl' ) . '</small></div>
          </div>  
          <div class="wple-circle-expires">  
          <strong>' . esc_html__( 'Your current SSL certificate expires on', 'wp-letsencrypt-ssl' ) . ': <b>' . esc_html( $leopts['expiry'] ) . '</b></strong>
          <p>' . esc_html__( 'Major browsers like Chrome will start showing insecure site warning IF you fail to renew / re-generate certs before this expiry date. If you are using PRO version - SSL certificates will be auto renewed in background a week prior to expiry date.', 'wp-letsencrypt-ssl' ) . '</p>           
          </div>
        </div>';
        }
        $html .= '</div>';
    }
    
    /**
     * Make verificiation possible with broken cert
     *
     * @return void
     */
    public function wple_wellknown_htaccess()
    {
        $dir = ABSPATH . '.well-known/acme-challenge/.htaccess';
        
        if ( !file_exists( $dir ) ) {
            $file = fopen( $dir, "w" );
        } else {
            $file = true;
        }
        
        
        if ( is_writable( $dir ) && $file !== false ) {
            $ruleset = "<IfModule mod_rewrite.c>" . "\n";
            $ruleset .= "RewriteEngine on" . "\n";
            $ruleset .= "RewriteCond %{HTTPS} =on [NC]" . "\n";
            $ruleset .= "RewriteRule ^(.*)\$ http://%{HTTP_HOST}%{REQUEST_FILENAME} [R=301,L]" . "\n";
            $ruleset .= "</IfModule>" . "\n";
            insert_with_markers( $dir, 'WP_Encryption', $ruleset );
        }
    
    }

}