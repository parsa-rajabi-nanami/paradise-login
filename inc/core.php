<?php
/**
 * Description: this file create shortcodes and define class's
 */
defined('PLWN_PLUGIN_FILE') || exit;


/**
 * create main shortcode
 *
 * [PLWN_login_form]
 */
function PLWN_login_form()
{
  ob_start();
  require __DIR__ . '/templates/plwn-login-form-template.php';
  return ob_get_clean();
}
add_shortcode('PLWN_login_form', 'PLWN_login_form');

/**
 * Issue a cache-compatible, same-origin token for the public OTP endpoint.
 */
function PLWN_ajax_get_csrf_token()
{
  $cookie_name = 'plwn_csrf_secret';
  $secret = isset($_COOKIE[$cookie_name]) ? sanitize_text_field(wp_unslash($_COOKIE[$cookie_name])) : '';
  if (!preg_match('/^[a-f0-9]{64}$/', $secret)) {
    $secret = bin2hex(random_bytes(32));
    $cookie_set = setcookie($cookie_name, $secret, array(
      'expires' => time() + 2 * HOUR_IN_SECONDS,
      'path' => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
      'domain' => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
      'secure' => is_ssl(),
      'httponly' => true,
      'samesite' => 'Strict',
    ));
    if (!$cookie_set) {
      wp_send_json_error(array('message' => __('Please reload and try again.', 'PLWN_translate_pro')), 500);
    }
    $_COOKIE[$cookie_name] = $secret;
  }

  nocache_headers();
  wp_send_json_success(array(
    'csrf_token' => hash_hmac('sha256', 'PLWN_ajax_send_sms', $secret),
  ));
}
add_action('wp_ajax_nopriv_PLWN_ajax_get_csrf_token', 'PLWN_ajax_get_csrf_token');

function PLWN_is_same_origin_request()
{
  $origin = isset($_SERVER['HTTP_ORIGIN']) ? wp_unslash($_SERVER['HTTP_ORIGIN']) : '';
  if ($origin === '' && isset($_SERVER['HTTP_REFERER'])) {
    $origin = wp_unslash($_SERVER['HTTP_REFERER']);
  }
  if ($origin === '') {
    return false;
  }

  $request_origin = wp_parse_url($origin);
  $site_origin = wp_parse_url(home_url('/'));
  if (empty($request_origin['host']) || empty($site_origin['host'])) {
    return false;
  }

  $request_scheme = isset($request_origin['scheme']) ? strtolower($request_origin['scheme']) : '';
  $site_scheme = isset($site_origin['scheme']) ? strtolower($site_origin['scheme']) : '';
  $request_port = isset($request_origin['port']) ? (int) $request_origin['port'] : ('https' === $request_scheme ? 443 : 80);
  $site_port = isset($site_origin['port']) ? (int) $site_origin['port'] : ('https' === $site_scheme ? 443 : 80);

  return strtolower($request_origin['host']) === strtolower($site_origin['host'])
    && $request_scheme === $site_scheme
    && $request_port === $site_port;
}

/**
 * add action for ajax send sms
 */
function PLWN_ajax_send_sms_function()
{
  $csrf_valid = false;
  $csrf_token = isset($_POST['csrf_token']) ? sanitize_text_field(wp_unslash($_POST['csrf_token'])) : '';
  $csrf_secret = isset($_COOKIE['plwn_csrf_secret']) ? sanitize_text_field(wp_unslash($_COOKIE['plwn_csrf_secret'])) : '';
  if (preg_match('/^[a-f0-9]{64}$/', $csrf_secret) && preg_match('/^[a-f0-9]{64}$/', $csrf_token)) {
    $csrf_valid = hash_equals(hash_hmac('sha256', 'PLWN_ajax_send_sms', $csrf_secret), $csrf_token);
  }

  // The legacy nonce remains accepted only for same-origin requests.
  $legacy_nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
  $legacy_valid = $legacy_nonce !== ''
    && wp_verify_nonce($legacy_nonce, 'PLWN_login_form_script_nonce')
    && PLWN_is_same_origin_request();
  if (!$csrf_valid && !$legacy_valid) {
    wp_send_json_error(array('message' => __('Invalid request. Please reload and try again.', 'PLWN_translate_pro')));
    wp_die();
  }
  $get_mobile_number = isset($_POST['mobile_number'])
    ? PLWN_normalize_phone_number(sanitize_text_field(wp_unslash($_POST['mobile_number'])))
    : '';
  $get_otp_code = isset($_POST['otp_code']) ? sanitize_text_field(wp_unslash($_POST['otp_code'])) : '';
  include __DIR__ . '/class/user-login-sing-up.php';
  $managed_user = new PLWN_Manage_User();
  $managed_user_check = $managed_user->PLWN_check_user($get_mobile_number, $get_otp_code);
  wp_send_json($managed_user_check);
  wp_die();
}
add_action('wp_ajax_nopriv_PLWN_ajax_send_sms', 'PLWN_ajax_send_sms_function');


/**
 * redirect user not login to login form from checkout
 */
add_action('init', 'redirect_to_login');
function redirect_to_login()
{
  if (!is_user_logged_in()) {
    // get urls must login
    $options = get_option('PLWN_plugin_sms_sending', array());
    if (!is_array($options)) {
      $options = array();
    }
    $options_redirect_login = !empty($options) && isset($options['redirect_login']) ? $options['redirect_login'] : null;
    if ($options_redirect_login != null) {
      $urls = explode("\n", $options_redirect_login);
      // create actual url
      $actual_link_protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
      $actual_link_serverName = $_SERVER['SERVER_NAME'];
      $actual_link_requests = $_SERVER['REQUEST_URI'];
      $actual_link = $actual_link_protocol . "://" . $actual_link_serverName . $actual_link_requests;
      // lower case targets
      $urls = array_map('strtolower', $urls);
      $actual_link = strtolower($actual_link);
      // check actions
      if (in_array($actual_link, $urls)) {
        if (!empty($options['url_login_page'])) {
          $login_page = $options['url_login_page'];
        } else {
          $login_page = home_url();
        }
        $login_page = wp_validate_redirect($login_page, home_url('/'));
        wp_safe_redirect($login_page);
        exit();
      } else {
        return;
      }
    } else {
      return;
    }
  }
}