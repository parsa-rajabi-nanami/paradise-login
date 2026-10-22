<?php
defined('ABSPATH') || exit;
class Send_sms_user
{

    public function __construct()
    {
    }
    public function Send_Sms_Otp($mobile_number, $otp_code, $user = null)
    {
        $mobile_number = PLWN_normalize_phone_number($mobile_number);
        $options = get_option('PLWN_plugin_sms_sending', array());
        if (!is_array($options)) {
            $options = array();
        }
        $resend_time = isset($options['resend_time']) ? absint($options['resend_time']) : 30;
        $failure = array(
            'status' => 'failed',
            'message' => __('Failure to communicate with the operator!', 'PLWN_translate_pro'),
            'text' => __('Try again', 'PLWN_translate_pro')
        );

        if (!$this->Can_Get_Sms($resend_time, $mobile_number)) {
            return array(
                'status' => 'failed',
                'message' => __('Error! Try again in a few seconds', 'PLWN_translate_pro'),
                'text' => __('Try again', 'PLWN_translate_pro')
            );
        }
        if (function_exists('PLWN_should_block_privileged_login') && PLWN_should_block_privileged_login($user, 'send')) {
            return array(
                'status' => 'success',
                'message' => __('Sms sent successfully', 'PLWN_translate_pro'),
                'text' => __('Submit Code', 'PLWN_translate_pro'),
                'new_input' => '<input id="otp_code" dir="ltr" type="text" name="otp_code" placeholder="' . esc_attr__('Inter Otp Code!', 'PLWN_translate_pro') . '">',
                'port' => 'sent'
            );
        }
        if (empty($options['API']) || !is_string($options['API'])) {
            return $failure;
        }

        $recipient = (isset($options['country_code']) ? $options['country_code'] : '') . $mobile_number;
        $url = str_replace(array('%PLWN_otp%', '%PLWN_mobile_number%'), array($otp_code, $recipient), $options['API']);
        $handler = curl_init($url);
        if ($handler === false) {
            return $failure;
        }

        try {
            curl_setopt($handler, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($handler, CURLOPT_TIMEOUT, 15);
            $gateway_response = curl_exec($handler);
            $http_status = (int) curl_getinfo($handler, CURLINFO_HTTP_CODE);
        } finally {
            curl_close($handler);
        }

        if ($gateway_response === false || $http_status < 200 || $http_status >= 300) {
            return $failure;
        }

        return array(
            'status' => 'success',
            'message' => __('Sms sent successfully', 'PLWN_translate_pro'),
            'text' => __('Submit Code', 'PLWN_translate_pro'),
            'new_input' => '<input id="otp_code" dir="ltr" type="text" name="otp_code" placeholder="' . esc_attr__('Inter Otp Code!', 'PLWN_translate_pro') . '">',
            'port' => 'sent'
        );
    }

    private function Acquire_Option_Lock($lock_key, $stale_after = 30)
    {
        if (add_option($lock_key, time(), '', false)) {
            return true;
        }

        $existing_value = get_option($lock_key, false);
        if (!is_numeric($existing_value) || (time() - (int) $existing_value) <= $stale_after) {
            return false;
        }

        global $wpdb;
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
            $lock_key,
            (string) $existing_value
        ));
        if (1 !== $deleted) {
            return false;
        }

        wp_cache_delete($lock_key, 'options');
        wp_cache_delete('notoptions', 'options');
        return add_option($lock_key, time(), '', false);
    }

    private function Can_Get_Sms($time = 30, $mobile_number)
    {
        $mobile_number = PLWN_normalize_phone_number($mobile_number);
        $user_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if (!preg_match('/^[0-9]{1,15}$/', (string) $mobile_number)) {
            return false;
        }

        $transient_key = 'PLWN_form_submit_' . $user_ip;
        $phone_transient_key = 'PLWN_form_submit_phone_' . hash_hmac('sha256', (string) $mobile_number, wp_salt('auth'));
        $lock_keys = array(
            'PLWN_send_rate_lock_' . hash('sha256', $transient_key),
            'PLWN_send_rate_lock_' . hash('sha256', $phone_transient_key),
        );
        sort($lock_keys, SORT_STRING);
        $acquired_locks = array();
        foreach ($lock_keys as $lock_key) {
            if (!$this->Acquire_Option_Lock($lock_key)) {
                foreach ($acquired_locks as $acquired_lock) {
                    delete_option($acquired_lock);
                }
                return false;
            }
            $acquired_locks[] = $lock_key;
        }

        try {
            $current_time = time();
            $time = max(1, absint($time));
            $last_submit_time = get_transient($transient_key);
            $last_phone_submit_time = get_transient($phone_transient_key);
            if (($last_submit_time && ($current_time - $last_submit_time) < $time)
                || ($last_phone_submit_time && ($current_time - $last_phone_submit_time) < $time)) {
                return false;
            }

            set_transient($transient_key, $current_time, $time);
            set_transient($phone_transient_key, $current_time, $time);
            return true;
        } finally {
            foreach ($acquired_locks as $acquired_lock) {
                delete_option($acquired_lock);
            }
        }
    }
}
