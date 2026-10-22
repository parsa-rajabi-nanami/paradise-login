<?php
/**
 * Description: this file is main template of shortcode [PLWN_login_form].
 */
defined('PLWN_PLUGIN_FILE') || exit;

// redirect back if user loged in
if (is_user_logged_in()) {
  wp_safe_redirect(home_url());
  exit();
}

// get setting data
$options = get_option('PLWN_plugin_sms_sending', array());
if (!is_array($options)) {
  $options = array();
}
$image_id = isset($options['logo']) ? absint($options['logo']) : 0;
$time_counter = isset($options['resend_time']) ? absint($options['resend_time']) : 30;
$sample_number = isset($options['sample_number']) ? sanitize_text_field($options['sample_number']) : '9120000000';


// enqueue styles this plugins
wp_enqueue_style('PLWN-login-form-style', plugins_url('assets/css/PLWN-login-form-style.css', PLWN_PLUGIN_FILE), array(), '1.1.0');

// enqueue script this plugins
wp_register_script('PLWN-login-form-script', plugins_url('assets/js/PLWN-login-form-script.js', PLWN_PLUGIN_FILE), array('jquery'), '1.1.0', true);
wp_enqueue_script('PLWN-login-form-script');
// localization script
wp_localize_script('PLWN-login-form-script', 'ajax_object', array(
  'ajax_url' => admin_url('admin-ajax.php'),
  'nonce' => wp_create_nonce('PLWN_login_form_script_nonce')
));
$output = '<div id="PLWN-login-form-box">';
// check if set logo show
$image_box_html = $image_id ? wp_get_attachment_image($image_id, 'medium', false, array('id' => 'logo_site')) : false;
if ($image_box_html) {
  $output .= $image_box_html;
} else {
  $output .= '<img id="PLWN-logo-preview-image" src="' . esc_url(plugins_url('assets/icons/logo.png', PLWN_PLUGIN_FILE)) . '" />';
}
$output .= '<h1>' . __('Login and membership form', 'PLWN_translate_pro') . '</h1>';
$output .= '<form method="post" id="PLWN-login-form">';
$output .= '<div id="container-inputs">';
$output .= '<input type="tel" dir="ltr" name="mobile_number" id="mobile_number" class="PLWN-login-form-tel" placeholder="' . esc_attr($sample_number) . '" required autofocus>';
$output .= '</div>';
$output .= '<button id="PLWN-login-form-submit">' . __('Send verification code', 'PLWN_translate_pro') . '</button>';
$output .= '<div class="resend_box">' . __('Resend Sms: ', 'PLWN_translate_pro') . ' <span id="time-counter">' . esc_html($time_counter) . '</span></div>';
$output .= '<div id="resend_box_btn">' . __('Resend Sms', 'PLWN_translate_pro') . '</div>';
$output .= '</form>';
$output .= '<div id="respone_box"></div>';
$output .= '</div>';
echo $output;
?>
