<?php

/**
 * @package WP Encryption
 *
 * @author     Go Web Smarty
 * @copyright  Copyright (C) 2019-2020, Go Web Smarty
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3
 * @link       https://gowebsmarty.com
 * @since      Class available since Release 5.0.0
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

require_once WPLE_DIR . 'admin/le_admin_page_wrapper.php';

class WPLE_SubAdmin extends WPLE_Admin_Page
{

  public function __construct()
  {
    add_action('admin_menu', [$this, 'wple_register_admin_pages'], 11);
    add_action('admin_menu', [$this, 'wple_register_secondary_admin_pages'], 20);
    add_action('admin_init', [$this, 'wple_force_https_handler']);
  }

  /**
   * Register sub pages
   *
   * @since 5.0.0
   * @return void 
   */
  public function wple_register_admin_pages()
  {
    if (!wple_fs()->is_plan('firewall', true)) {
      add_submenu_page('wp_encryption', 'Download SSL Certificates', __('Download SSL Certificates', 'wp-letsencrypt-ssl'), 'manage_options', 'wp_encryption_download', [$this, 'wple_download_page']);
    }

    add_submenu_page('wp_encryption', 'Force HTTPS', __('Force HTTPS', 'wp-letsencrypt-ssl'), 'manage_options', 'wp_encryption_force_https', [$this, 'wple_force_https_page']);

    if (!wple_fs()->is_plan('firewall', true)) {
      add_submenu_page('wp_encryption', 'Debug log', __('Debug log', 'wp-letsencrypt-ssl'), 'manage_options', 'wp_encryption_log', [$this, 'wple_debug_log_page']);
    }
  }

  /**
   * Register sub pages
   *
   * @since 5.0.0
   * @return void
   */
  public function wple_register_secondary_admin_pages()
  {
    if (!wple_fs()->is_plan('firewall', true)) {
      add_submenu_page('wp_encryption', 'How-To Videos', __('How-To Videos', 'wp-letsencrypt-ssl'), 'manage_options', 'wp_encryption_howto_videos', [$this, 'wple_howto_page']);
      add_submenu_page('wp_encryption', 'FAQ', __('FAQ', 'wp-letsencrypt-ssl'), 'manage_options', 'wp_encryption_faq', [$this, 'wple_faq_page']);
      add_submenu_page('wp_encryption', 'Reset', __('Reset', 'wp-letsencrypt-ssl'), 'manage_options', 'wp_encryption_reset', [$this, 'wple_tools_block']);
    }
  }

  /**
   * Force HTTPS page
   *
   * @since 5.0.0
   * @source le_admin.php moved
   * @return void
   */
  public function wple_force_https_page()
  {
    $leopts = get_option('wple_opts');
    $checked = (isset($leopts['force_ssl']) && $leopts['force_ssl'] === 1) ? 'checked' : '';
    $htaccesschecked = (isset($leopts['force_ssl']) && $leopts['force_ssl'] === 2) ? 'checked' : '';
    $disablechecked = (!isset($leopts['force_ssl']) || ($checked == '' && $htaccesschecked == '')) ? 'checked' : '';


    $page = "<h2>" . __('Force HTTPS', 'wp-letsencrypt-ssl') . "</h2>
    <div class=\"wple-force\">
      <p>" . $this->wple_kses(__("If you still don't see a green padlock or notice <b>mixed content</b> warning in your browser console - please enable the below option to force HTTPS on all resources of site.", 'wp-letsencrypt-ssl')) . " " . $this->wple_kses(__("Along with this step, please have a look at <strong>'Images not loading on HTTPS site'</strong> question in FAQ tab to make sure 100% HTTPS is enforced.", "wp-letsencrypt-ssl")) . "</p>";

    $page .= '<form method="post">
      <label class="checkbox-label" style="float:left">
      <input type="radio" name="wple_forcessl" value="0" ' . $disablechecked . '>
        <span class="checkbox-custom rectangular"></span>
      </label>

      <label>' . esc_html__('Disable', 'wp-letsencrypt-ssl') . '</label><br /><br />

      <label class="checkbox-label" style="float:left">
      <input type="radio" name="wple_forcessl" value="2" ' . $htaccesschecked . '>
        <span class="checkbox-custom rectangular"></span>
      </label>

      <label>' . esc_html__('Force SSL via HTACCESS (Server level redirect - Faster)', 'wp-letsencrypt-ssl') . '</label><br /><br />

      <label class="checkbox-label" style="float:left">
      <input type="radio" name="wple_forcessl" value="1" ' . $checked . '>
        <span class="checkbox-custom rectangular"></span>
      </label>

      <label>' . esc_html__('Force SSL via WordPress (Alternate solution if htaccess redirect cause any issues)', 'wp-letsencrypt-ssl') . '</label><br /><br />

      ' . wp_nonce_field('wpleforcessl', 'site-force-ssl', false, false) . '
      <button type="submit" name="wple_ssl">' . esc_html__('Save', 'wp-letsencrypt-ssl') . '</button>
      </form>
    </div>';

    $this->generate_page($page);
  }

  /**
   * Force HTTPS Handler
   *
   * @since 5.0.0
   * @source le_admin.php moved
   * @return void
   */
  public function wple_force_https_handler()
  {

    //force ssl
    if (isset($_POST['site-force-ssl'])) {
      if (!wp_verify_nonce($_POST['site-force-ssl'], 'wpleforcessl')) {
        die('Unauthorized request');
      }

      $basedomain = str_ireplace(array('http://', 'https://'), array('', ''), site_url());

      //4.7
      if (FALSE != stripos($basedomain, '/')) {
        $basedomain = substr($basedomain, 0, stripos($basedomain, '/'));
      }

      //4.7.2
      $streamContext = stream_context_create([
        'ssl' => [
          'verify_peer' => true,
        ],
      ]);

      $errorDescription = $errorNumber = '';

      $client = @stream_socket_client(
        "ssl://$basedomain:443",
        $errorNumber,
        $errorDescription,
        30,
        STREAM_CLIENT_CONNECT,
        $streamContext
      );

      $reverter = uniqid('wple');

      $leopts = get_option('wple_opts');
      $prevforce = isset($leopts['force_ssl']) ? $leopts['force_ssl'] : 0;
      $leopts['force_ssl'] = (int) $_POST['wple_forcessl'];

      if (!$client && $leopts['force_ssl'] != 0) {
        $nossl = '<p>' . esc_html__('We could not detect valid SSL on your site!. Please double check SSL certificate is properly installed on your cPanel / Server.', 'wp-letsencrypt-ssl') . ' ' . esc_html($errorDescription) . '</p>';

        $nossl .= '<p>' . esc_html__('Switching to HTTPS without properly installing the SSL certificate might break your site.', 'wp-letsencrypt-ssl') . '</p>';

        $nossl .= '<a href="?page=wp_encryption&forceenablehttps=' . wp_create_nonce('hardforcessl') . '&forcetype=' . (int) $leopts['force_ssl'] . '" style="background: #f55656; color: #fff; padding: 10px; text-decoration: none; border-radius: 5px;        display: inline-block; margin:0 0 10px;"><strong>' . esc_html__('CLICK TO FORCE ENABLE HTTPS (Do it at your own risk)', 'wp-letsencrypt-ssl') . '</strong></a><br />
        <small>' . sprintf(esc_html__('In case you break the site, here is revert back to HTTP:// instructions - %s', 'wp-letsencrypt-ssl'), 'https://wordpress.org/support/topic/locked-out-unable-to-access-site-after-forcing-https-2/') . '</small>';

        wp_die($nossl);
        exit();
      }

      if ($leopts['force_ssl'] == 1) {
        $leopts['revertnonce'] = $reverter;
      }

      update_option('wple_opts', $leopts);

      if ($leopts['force_ssl'] != 0) {

        //since 5.0.0
        if (wple_fs()->is_plan('firewall', true)) {

          update_option('siteurl', str_ireplace('https:', 'http:', get_option('siteurl')));
          update_option('home', str_ireplace('https:', 'http:', get_option('home')));
        } else {
          update_option('siteurl', str_ireplace('http:', 'https:', get_option('siteurl')));
          update_option('home', str_ireplace('http:', 'https:', get_option('home')));

          if ($leopts['force_ssl'] == 1) {
            if ($prevforce == 2) {
              $this->wple_clean_htaccess();
            }
            $this->wple_send_reverter_secret($reverter);
          } elseif ($leopts['force_ssl'] == 2) {
            $this->wple_force_ssl_htaccess();
          }
        }
      } else {

        if ($prevforce == 2) { //previously htaccess forced so remove them  
          $this->wple_clean_htaccess();
        }

        update_option('siteurl', str_ireplace('https:', 'http:', get_option('siteurl')));
        update_option('home', str_ireplace('https:', 'http:', get_option('home')));
      }

      wp_redirect(admin_url('admin.php?page=wp_encryption_force_https&successnotice=1'));
      exit();
    }

    //HARD force ssl since 4.7.2
    if (isset($_GET['forceenablehttps'])) {
      if (!wp_verify_nonce($_GET['forceenablehttps'], 'hardforcessl')) {
        die('Unauthorized request');
      }

      if ($_GET['forcetype'] == 1) {

        $reverter = uniqid('wple');

        $leopts = get_option('wple_opts');
        $leopts['force_ssl'] = 1;
        $leopts['revertnonce'] = $reverter;

        update_option('wple_opts', $leopts);

        $this->wple_send_reverter_secret($reverter);
      } else {

        $leopts = get_option('wple_opts');
        $leopts['force_ssl'] = 2;
        update_option('wple_opts', $leopts);
        $this->wple_force_ssl_htaccess();
      }

      update_option('siteurl', str_ireplace('http:', 'https:', get_option('siteurl')));
      update_option('home', str_ireplace('http:', 'https:', get_option('home')));

      wp_redirect(admin_url('admin.php?page=wp_encryption_force_https&successnotice=1'));
      exit();
    }
  }

  /**
   * FAQ
   * 
   * @since 5.0.0   
   * @source le_admin.php moved
   * @return void
   */
  public function wple_faq_page()
  {
    $page = '<h2>' . esc_html__('FREQUENTLY ASKED QUESTIONS', 'wp-letsencrypt-ssl') . '</h2>
    <h4>' . esc_html__('Does installing the plugin will instantly turn my site https?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . esc_html__('Installing SSL certificate is a server side process and not as simple as installing a ready widget and using it instantly. You will have to follow some simple steps to install SSL for your WordPress site. Our plugin acts like a tool to generate and install SSL for your WordPress site. On FREE version of plugin - You should manually go through the SSL certificate installation process following the simple video tutorial. Whereas, the SSL certificates are easily generated by our plugin by running a simple SSL generation form.', 'wp-letsencrypt-ssl') . '</p>
      <hr>
      <h4>' . esc_html__('How to install SSL for both www & non-www version of my domain?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . $this->wple_kses('First of all, Please make sure you can access your site with and without www. Otherwise you will be not able to complete domain verification for both www & non-www together. Open <strong>WP Encryption</strong> page with <strong>&includewww=1</strong> appended to end of the URL (Ex: <strong>/wp-admin/admin.php?page=wp_encryption&includewww=1</strong>) and run the SSL form with <strong>"Generate SSL for both www & non-www"</strong> option checked.', 'wp-letsencrypt-ssl') . '</p>
      <hr>
      <h4>' . esc_html__('Images not loading on HTTPS site', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . esc_html__('Images on your site might be loading over http:// protocol, please enable "Force HTTPS" feature via WP Encryption page. If you have Elementor page builder installed, please go to Elementor > Tools > Replace URL and replace your http:// site url with https://. Make sure you have SSL certificates installed and browser padlock shows certificate(valid) before forcing these https measures.', 'wp-letsencrypt-ssl') . '</p>
      <p>' . esc_html__('If you are still not seeing padlock, We recommend testing your site at whynopadlock.com to determine the exact issue. If you have any image sliders, background images might be loading over http:// url instead of https:// and causing mixed content issues thus making padlock to not show.', 'wp-letsencrypt-ssl') . '</p>
      <hr>
      <h4>' . esc_html__('How do I renew my SSL certificate before expiry date?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . $this->wple_kses(__('Your SSL certificate will be auto renewed if you have <b>WP Encryption PRO</b> plugin purchased (SSL certs will be auto renewed in background just before the expiry date). If you have free version of plugin installed, You can use the same process of "Generate free SSL" to get new certs.', 'wp-letsencrypt-ssl')) . '</p>
      <hr>
      <h4>' . esc_html__('How do I install Wildcard SSL?', 'wp-letsencrypt-ssl') . '</h4>      
      <p>' . $this->wple_kses(__('If you have purchased the <b>WP Encryption PRO</b> version, You can notice a new tab for One click Wildcard SSL generation and installation.', 'wp-letsencrypt-ssl')) . '</p>
      <hr>      
      <h4>' . esc_html__('How to test if my SSL installation is good?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . $this->wple_kses(sprintf(
      __('You can run a SSL test by entering your website url in <a href="%s" rel="%s">SSL Labs</a> site.', 'wp-letsencrypt-ssl'),
      'https://www.ssllabs.com/ssltest/',
      'nofollow'
    ), 'a') . '</p>
      <hr>
      <h4>' . esc_html__('How to revert back to HTTP in case of force HTTPS failure?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . esc_html__('Please follow the revert back instructions given in [support forum](https://wordpress.org/support/topic/locked-out-unable-to-access-site-after-forcing-https-2/).', 'wp-letsencrypt-ssl') . '</p>
      <hr>
      <h4>' . esc_html__('Have a different question?', 'wp-letsencrypt-ssl') . '</h4>
      <p>' . $this->wple_kses(sprintf(
      __('Please use our <a href="%s" target="%s">Plugin support forum</a>. <b>PRO</b> users can register free account & use priority support at gowebsmarty.in. More info - https://wpencryption.com', 'wp-letsencrypt-ssl'),
      'https://wordpress.org/support/plugin/wp-letsencrypt-ssl/',
      '_blank'
    ), 'a') . '</p>';

    $this->generate_page($page);
  }

  /**
   * How-To Videos
   * 
   * @since 5.0.0
   * @source le_admin.php moved
   * @return void
   */
  public function wple_howto_page()
  {
    $page = '<h2>' . __('How-To Videos', 'wp-letsencrypt-ssl') . '</h2>
    <h3>' . esc_html__("How to complete domain verification via DNS challenge?", 'wp-letsencrypt-ssl') . '</h3>
    <iframe width="560" height="315" src="https://www.youtube.com/embed/BBQL69PDDrk" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    
    <h3 style="margin-top: 20px;">' . esc_html__("How to install SSL Certificate on cPanel?", 'wp-letsencrypt-ssl') . '</h3>
    <iframe width="560" height="315" src="https://www.youtube.com/embed/KQ2HYtplPEk" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>  
       
    <h3 style="margin-top: 20px;">' . esc_html__("How to install SSL Certificate on Non-cPanel site via SSH access?", 'wp-letsencrypt-ssl') . '</h3>
    <iframe width="560" height="315" src="https://www.youtube.com/embed/PANs_C2SI5Q" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>  
      
    <h3 style="margin-top: 20px;">' . esc_html__("PRO - Automate DNS verification for Godaddy", 'wp-letsencrypt-ssl') . '</h3>  
    <iframe width="560" height="315" src="https://www.youtube.com/embed/7Dztj-02Ebg" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>

    <div class="le-other-plugins">
      <a href="https://wordpress.org/plugins/modern-addons-elementor/" target="_blank">
        <img src="' . WPLE_URL . 'admin/assets/modern-addons.png"/>
      </a>
    </div>
      
    <h2 style="margin:30px 0 20px">Try our social sharing plugin with share analytics!</h2>  
    <a href="https://wordpress.org/plugins/go-viral/" target="_blank"><img src="' . WPLE_URL . 'admin/assets/go-viral.jpg"/></a>';

    $this->generate_page($page);
  }

  /**
   * Download SSL Certs
   *
   * @since 5.1.0
   * @return HTML
   */
  public function wple_download_page()
  {
    $cert = ABSPATH . 'keys/certificate.crt';

    $html = '<div class="download-certs">';
    if (file_exists($cert)) {
      $leopts = get_option('wple_opts');

      $html .= '<h3 style="margin:10px 13px 30px">' . esc_html__('Your current SSL certificate expires on', 'wp-letsencrypt-ssl') . ': <b>' . esc_html($leopts['expiry']) . '</b></h3>';
      $html .= '<ul>
      <li class="le-dwnld"><a href="?page=wp_encryption&le=1">' . esc_html__('Download cert file', 'wp-letsencrypt-ssl') . '</a></li>
      <li class="le-dwnld"><a href="?page=wp_encryption&le=2">' . esc_html__('Download key file',  'wp-letsencrypt-ssl') . '</a></li>
      <li class="le-dwnld"><a href="?page=wp_encryption&le=3">' . esc_html__('Download ca bundle', 'wp-letsencrypt-ssl') . '</a></li>
      </ul>';
    } else {
      $html .= '<b>' . esc_html__("You don't have any SSL certificates generated yet! Please generate your single/wildcard SSL first before you can download it here.", 'wp-letsencrypt-ssl') . '</b>';
    }
    $html .= '</div>';

    $this->generate_page($html);
  }

  public function wple_debug_log_page()
  {
    $file = WPLE_DEBUGGER . 'debug.log';

    $html = '<h3>' . esc_html__('Please share below debug log when requesting support', 'wp-letsencrypt-ssl') . '</h3>';

    if (file_exists($file)) {
      $log = file_get_contents($file);

      $hideh2 = '';
      if (isset($_GET['dnsverified']) || isset($_GET['dnsverify'])) {
        $hideh2 = 'hideheader';
      }

      $html .= '<div class="le-debugger running ' . $hideh2 . '"><h3>' . esc_html__('Debug Log', 'wp-letsencrypt-ssl') . ':</h3>' . wp_kses_post(nl2br($log)) . '</div>';
    } else {
      $html .= '<div class="le-debugger">' . esc_html__("Full response will be shown here", 'wp-letsencrypt-ssl') . '</div>';
    }

    $this->generate_page($html);
  }

  /**
   * Handy Tools
   *
   * @since 4.5.0
   * @source le_admin.php moved since 5.1.0
   * @return $html
   */
  public function wple_tools_block()
  {
    $html = '<h3>' . esc_html__('Reset / Delete Keys folder and restart the process', 'wp-letsencrypt-ssl') . '</h3>';

    $html .= '<p>' . esc_html__('Use this handy tool to reset the SSL process and start again in case you get some error like "no account exists with provided key". This reset action will delete your current certificate and keys folder.', 'wp-letsencrypt-ssl') . '</p>';

    $html .= '<a href="' . wp_nonce_url(admin_url('admin.php?page=wp_encryption'), 'restartwple', 'wplereset') . '" class="wple-reset-button">' . esc_html__('RESET KEYS AND CERTIFICATE', 'wp-letsencrypt-ssl') . '</a>';

    $this->generate_page($html);
  }

  public function wple_clean_htaccess()
  {
    if (is_writable(ABSPATH . '.htaccess')) {
      $htaccess = file_get_contents(ABSPATH . '.htaccess');
      $group = "/#\\s?BEGIN\\s?WP_Encryption_Force_SSL.*?#\\s?END\\s?WP_Encryption_Force_SSL/s";

      if (preg_match($group, $htaccess)) {
        $modhtaccess = preg_replace($group, "", $htaccess);
        insert_with_markers(ABSPATH . '.htaccess', '', $modhtaccess);
      }
    } else {
      wp_die(esc_html__('.htaccess file not writable. Please remove WP_Encryption_Force_SSL block from .htaccess file manually using FTP or File Manager.', 'wp-letsencrypt-ssl'));
      exit();
    }
  }
}
