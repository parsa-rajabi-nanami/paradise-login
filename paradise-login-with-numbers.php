<?php
/**
 * Plugin Name: sign in/up with number
 * Plugin URI: https://github.com/parsa-rajabi-nanami/paradise-login
 * Update URI: https://github.com/parsa-rajabi-nanami/paradise-login
 * Description: Adds phone-number login and registration using SMS one-time codes.
 * Version: 1.1.0
 * Author: Parsa Rajabi
 * Author URI: https://www.linkedin.com/in/parsa-rajabi-nanami/
 * Requires at least: 4.6
 * Text Domain: PLWN_translate_pro
 * Domain Path: /languages
 * Requires PHP: 7.3
 * License: MIT
 * License URI: https://opensource.org/license/mit/
 */
defined('ABSPATH') || exit;

if (!defined('PARADISE_LOGIN_VERSION')) {
    define('PARADISE_LOGIN_VERSION', '1.1.0');
}

/**
 * Normalize a phone number to the plugin's country-agnostic ASCII digit format.
 *
 * @param string $phone_number Raw phone number.
 * @return string Normalized phone number, or an empty string when invalid.
 */
function PLWN_normalize_phone_number($phone_number)
{
    if (!is_scalar($phone_number)) {
        return '';
    }
    $phone_number = (string) $phone_number;
    $digits = array(
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    );
    $phone_number = strtr($phone_number, $digits);
    $normalized = preg_replace('/[\s\p{Z}\p{Cf}\p{Pd}()]+/u', '', $phone_number);
    if (!is_string($normalized)) {
        return '';
    }

    $normalized = apply_filters('paradise_login_normalized_phone_number', $normalized, $phone_number);
    if (!is_string($normalized) || !preg_match('/^[0-9]{1,15}$/', $normalized)) {
        return '';
    }

    return $normalized;
}

if (!defined('PLWN_PLUGIN_FILE')) {
    define('PLWN_PLUGIN_FILE', __FILE__);
}

function paradise_login_with_numbers_update_version()
{
    $stored_version = get_option('paradise_login_with_numbers_version', '');
    if ($stored_version !== PARADISE_LOGIN_VERSION) {
        update_option('paradise_login_with_numbers_version', PARADISE_LOGIN_VERSION);
    }
}
add_action('plugins_loaded', 'paradise_login_with_numbers_update_version', 1);

// load translate plugins
function PLWN_translate()
{
    add_action('init', 'PLWN_load_textdomain', 0);
}
add_action('plugins_loaded', 'PLWN_translate');

function PLWN_load_textdomain()
{
    load_plugin_textdomain('PLWN_translate_pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// require admin settings
require __DIR__ . '/inc/admin/core-admin.php';

// include core plugin
require __DIR__ . '/inc/core.php';

// activation hook for this plugin
function paradise_login_with_numbers_activate()
{
    add_option('paradise_login_with_numbers_activated', true);
}
register_activation_hook(__FILE__, 'paradise_login_with_numbers_activate');

// ridirect after activated plugin
function paradise_login_with_numbers_redirect_to_settings()
{
    if (get_option('paradise_login_with_numbers_activated', false)) {
        delete_option('paradise_login_with_numbers_activated');
        if (!is_network_admin() && !isset($_GET['activate-multi'])) {
            wp_safe_redirect(admin_url('options-general.php?page=PLWN-plugin-settings'));
            exit;
        }
    }
}
add_action('admin_init', 'paradise_login_with_numbers_redirect_to_settings');
