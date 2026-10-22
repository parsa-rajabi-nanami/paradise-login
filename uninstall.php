<?php
/**
 * PLWN Uninstall
 *
 * Uninstalling PLWN deletes options and transients created by this plugin.
 */
defined('WP_UNINSTALL_PLUGIN') || exit;

function plwn_uninstall_current_site()
{
    global $wpdb;

    // Preserve the plugin-owned setting and transient cleanup from earlier releases.
    // This option array owns default_email_domain as well as the existing settings.
    delete_option('PLWN_plugin_sms_sending');
    $transient_options = $wpdb->get_col($wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_PLWN_') . '%',
        $wpdb->esc_like('_transient_timeout_PLWN_') . '%'
    ));
    foreach ($transient_options as $transient_option) {
        if (strpos($transient_option, '_transient_timeout_PLWN_') === 0) {
            delete_transient(substr($transient_option, strlen('_transient_timeout_')));
            continue;
        }
        delete_transient(substr($transient_option, strlen('_transient_PLWN_')));
    }

    // Remove this release's stored version and non-autoloaded inter-request locks.
    delete_option('paradise_login_with_numbers_version');
    foreach (array('PLWN_send_rate_lock_', 'PLWN_otp_rate_lock_', 'PLWN_otp_claim_') as $prefix) {
        $lock_options = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like($prefix) . '%'
        ));
        foreach ($lock_options as $lock_option) {
            delete_option($lock_option);
        }
    }
}

if (is_multisite()) {
    $site_ids = get_sites(array('fields' => 'ids', 'number' => 0));
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        plwn_uninstall_current_site();
        restore_current_blog();
    }
} else {
    plwn_uninstall_current_site();
}
