<?php
defined('ABSPATH') || exit;
require_once __DIR__ . '/send-sms-user.php';
class PLWN_Manage_User
{
    private $PLWN_send_sms;

    public function __construct()
    {
        $this->PLWN_send_sms = new Send_sms_user();
    }

    private function PLWN_Add_user($mobile, $user_role)
    {
        $mobile = PLWN_normalize_phone_number($mobile);
        if ($mobile === '') {
            return array(
                'status' => 'failed',
                'message' => __('Error! Try again in a few seconds', 'PLWN_translate_pro'),
                'text' => __('Try again', 'PLWN_translate_pro')
            );
        }

        $existing_user = $this->PLWN_Find_User_By_Phone($mobile);
        if ($existing_user) {
            return $this->PLWN_Login_user($existing_user, $user_role);
        }

        if (function_exists('PLWN_is_privileged_role') && PLWN_is_privileged_role($user_role)) {
            $fallback_role = get_option('default_role', 'subscriber');
            if (function_exists('PLWN_is_privileged_role') && PLWN_is_privileged_role($fallback_role)) {
                $fallback_role = 'subscriber';
            }
            $user_role = $fallback_role;
            error_log('Paradise Login: privileged self-registration role rejected; using a safe fallback.');
        }

        $fake_email = 'plwn_' . $mobile . '@' . PLWN_get_default_email_domain();
        $fake_email = apply_filters('paradise_login_default_user_email', $fake_email, $mobile);
        $pass_default = random_bytes(12);
        $pass_default = bin2hex($pass_default);
        $add_user = wp_insert_user(array(
            'user_login' => $mobile,
            'user_pass' => $pass_default,
            'user_email' => $fake_email,
            'role' => $user_role,
        ));
        if (!is_wp_error($add_user)) {
            $meta = array(
                'mobile_user' => $mobile,
            );
            foreach ($meta as $key => $val) {
                update_user_meta($add_user, $key, $val);
            }
            $user_obj = get_user_by('id', $add_user);
            return $this->PLWN_Login_user($user_obj, $user_role);
        } else {
            $response = array(
                'status' => 'failed',
                'message' => __('There was a problem logging in!', 'PLWN_translate_pro'),
                'text' => __('Try again', 'PLWN_translate_pro')
            );
            return $response;
        }
    }

    private function PLWN_Login_user($user, $user_role)
    {
        if ($user instanceof WP_User) {
            if (function_exists('PLWN_should_block_privileged_login') && PLWN_should_block_privileged_login($user, 'verify')) {
                return $this->PLWN_Wrong_Otp_Response();
            }
            wp_clear_auth_cookie();
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, false, is_ssl());
            do_action('wp_login', $user->user_login, $user);
            $response = array(
                'status' => 'success',
                'message' => __('You entered!', 'PLWN_translate_pro'),
                'url' => home_url()
            );
            return $response;
        } else {
            $response = array(
                'status' => 'failed',
                'message' => __('There was a problem logging in!', 'PLWN_translate_pro'),
                'text' => __('Try again', 'PLWN_translate_pro')
            );
            return $response;
        }
    }

    private function PLWN_Find_User_By_Phone($phone_number)
    {
        $mobile = PLWN_normalize_phone_number($phone_number);
        if ($mobile === '') {
            return null;
        }

        $exact_query = new WP_User_Query(array(
            'meta_key' => 'mobile_user',
            'meta_value' => $mobile,
            'meta_compare' => '=',
            'orderby' => 'ID',
            'order' => 'ASC',
            'number' => 2,
        ));
        $exact_users = $exact_query->get_results();
        if (!empty($exact_users)) {
            if (count($exact_users) > 1) {
                error_log('Paradise Login: duplicate exact phone metadata found; using the lowest user ID.');
            }
            return $exact_users[0];
        }

        $persian_digits = array('0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹');
        $arabic_digits = array('0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩');
        $variants = array_unique(array_filter(array(
            (string) $phone_number,
            strtr($mobile, $persian_digits),
            strtr($mobile, $arabic_digits),
        ), 'strlen'));

        $variant_query = new WP_User_Query(array(
            'meta_query' => array(
                array(
                    'key' => 'mobile_user',
                    'value' => $variants,
                    'compare' => 'IN',
                ),
            ),
            'orderby' => 'ID',
            'order' => 'ASC',
            'number' => 2,
        ));
        $variant_users = $variant_query->get_results();
        if (count($variant_users) > 1) {
            error_log('Paradise Login: multiple legacy phone metadata matches found; using the lowest user ID.');
        }

        return !empty($variant_users) ? $variant_users[0] : null;
    }

    private function User_manager_check($transient_key, $user_role)
    {
        $array_data_user = explode('_', $transient_key);
        $mobile = isset($array_data_user[3]) ? PLWN_normalize_phone_number($array_data_user[3]) : '';
        if ($mobile === '') {
            return $this->PLWN_Wrong_Otp_Response();
        }

        $user = $this->PLWN_Find_User_By_Phone($mobile);
        if ($user) {
            return $this->PLWN_Login_user($user, $user_role);
        }

        return $this->PLWN_Add_user($mobile, $user_role);
    }

    private function PLWN_Get_Rate_Limits()
    {
        $defaults = array(
            'verify_ip' => 120,
            'verify_phone' => 30,
            'attempts_per_code' => 5,
            'window' => 900,
        );
        $limits = apply_filters('paradise_login_otp_rate_limits', $defaults);

        return is_array($limits) ? array_merge($defaults, $limits) : $defaults;
    }

    private function PLWN_Acquire_Option_Lock($lock_key, $stale_after = 30)
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

    private function PLWN_Increment_Rate_Limit($type, $identity, $limit, $window, $bucketed = true)
    {
        $window = max(1, absint($window));
        if ($bucketed) {
            $window = min(86400, $window);
        }
        $limit = max(1, absint($limit));
        $identity_hash = hash_hmac('sha256', (string) $identity, wp_salt('auth'));
        $bucket = $bucketed ? '_' . (int) floor(time() / $window) : '';
        $rate_key = 'PLWN_otp_rate_' . $type . '_' . $identity_hash . $bucket;
        $lock_key = 'PLWN_otp_rate_lock_' . hash('sha256', $rate_key);

        if (!$this->PLWN_Acquire_Option_Lock($lock_key)) {
            return false;
        }

        try {
            $count = get_transient($rate_key);
            $count = ($count === false) ? 0 : absint($count);
            if ($count >= $limit) {
                return false;
            }

            $expiration = $bucketed ? $window * 2 : max(DAY_IN_SECONDS, $window);
            set_transient($rate_key, $count + 1, $expiration);
            return true;
        } finally {
            delete_option($lock_key);
        }
    }

    private function PLWN_Create_Otp_Code($options)
    {
        $digits = isset($options['otp_range_code']) ? absint($options['otp_range_code']) : 6;
        $digits = min(9, max(3, $digits));
        $minimum = (int) pow(10, $digits - 1);
        $maximum = (int) pow(10, $digits) - 1;

        return (string) random_int($minimum, $maximum);
    }

    private function PLWN_Encode_Otp($transient_key, $otp_code)
    {
        $message = $transient_key . "\0" . (string) $otp_code;
        return 'v2$' . hash_hmac('sha256', $message, wp_salt('auth'));
    }

    private function PLWN_Is_Valid_Otp($transient_key, $stored_value, $submitted_code)
    {
        if (!is_string($stored_value) && !is_numeric($stored_value)) {
            return false;
        }

        $stored_value = (string) $stored_value;
        if (strpos($stored_value, 'v2$') === 0) {
            if (!preg_match('/^v2\$[a-f0-9]{64}$/', $stored_value)) {
                return false;
            }

            return hash_equals($this->PLWN_Encode_Otp($transient_key, $submitted_code), $stored_value);
        }

        // Legacy plaintext transition: removable in a future major release.
        return hash_equals($stored_value, (string) $submitted_code);
    }

    private function PLWN_Claim_Otp($transient_key, $stored_value)
    {
        $claim_key = 'PLWN_otp_claim_' . hash_hmac(
            'sha256',
            $transient_key . "\0" . (string) $stored_value,
            wp_salt('auth')
        );

        return $this->PLWN_Acquire_Option_Lock($claim_key) ? $claim_key : false;
    }

    private function PLWN_Wrong_Otp_Response()
    {
        return array(
            'status' => 'failed',
            'message' => __('You entered the wrong code!', 'PLWN_translate_pro'),
            'text' => __('Try again', 'PLWN_translate_pro')
        );
    }

    public function PLWN_check_user($get_mobile_number, $get_otp_code)
    {
        $get_mobile_number = PLWN_normalize_phone_number($get_mobile_number);
        $options = get_option('PLWN_plugin_sms_sending', array());
        if (!is_array($options)) {
            $options = array();
        }
        if ($get_mobile_number === '') {
            return empty($get_otp_code)
                ? array('status' => 'failed', 'message' => __('Error! Try again in a few seconds', 'PLWN_translate_pro'), 'text' => __('Try again', 'PLWN_translate_pro'))
                : $this->PLWN_Wrong_Otp_Response();
        }

        if (empty($get_otp_code)) {
            $user_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            $transient_key = 'PLWN_' . $user_ip . '_otp_' . $get_mobile_number;
            if (!get_transient($transient_key)) {
                $create_otp_code = $this->PLWN_Create_Otp_Code($options);
                $otp_lifetime = function_exists('PLWN_get_otp_lifetime')
                    ? PLWN_get_otp_lifetime($options)
                    : min(900, max(1, isset($options['resend_time']) ? absint($options['resend_time']) : 30));
                set_transient($transient_key, $this->PLWN_Encode_Otp($transient_key, $create_otp_code), $otp_lifetime);
                $user = $this->PLWN_Find_User_By_Phone($get_mobile_number);
                return $this->PLWN_send_sms->Send_Sms_Otp($get_mobile_number, $create_otp_code, $user);
            } else {
                $response = array(
                    'status' => 'failed',
                    'message' => __('Error! Try again in a few seconds', 'PLWN_translate_pro'),
                    'text' => __('Try again', 'PLWN_translate_pro')
                );
                return $response;
            }
        } elseif (!empty($get_mobile_number) && !empty($get_otp_code)) {
            $user_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            $transient_key = 'PLWN_' . $user_ip . '_otp_' . $get_mobile_number;
            $limits = $this->PLWN_Get_Rate_Limits();
            $window = min(86400, max(1, absint($limits['window'])));
            if (!$this->PLWN_Increment_Rate_Limit('verify_ip', $user_ip, $limits['verify_ip'], $window)
                || !$this->PLWN_Increment_Rate_Limit('verify_phone', $get_mobile_number, $limits['verify_phone'], $window)) {
                return $this->PLWN_Wrong_Otp_Response();
            }

            $otp_value_true = get_transient($transient_key);
            if ($otp_value_true === false) {
                return $this->PLWN_Wrong_Otp_Response();
            }

            $claim_key = $this->PLWN_Claim_Otp($transient_key, $otp_value_true);
            if (!$claim_key) {
                return $this->PLWN_Wrong_Otp_Response();
            }

            $user_role = (!empty($options['user_role'])) ? $options['user_role'] : 'subscriber';
            try {
                $current_otp_value = get_transient($transient_key);
                if ($current_otp_value === false || !hash_equals((string) $otp_value_true, (string) $current_otp_value)) {
                    return $this->PLWN_Wrong_Otp_Response();
                }

                $attempt_window = max(
                    PLWN_get_otp_lifetime($options),
                    isset($options['resend_time']) ? absint($options['resend_time']) : 0,
                    DAY_IN_SECONDS
                );
                $attempt_identity = $transient_key . "\0" . (string) $otp_value_true;
                if (!$this->PLWN_Increment_Rate_Limit(
                    'attempt',
                    $attempt_identity,
                    $limits['attempts_per_code'],
                    $attempt_window,
                    false
                ) || !$this->PLWN_Is_Valid_Otp($transient_key, $otp_value_true, $get_otp_code)) {
                    return $this->PLWN_Wrong_Otp_Response();
                }

                $response = $this->User_manager_check($transient_key, $user_role);
                if (isset($response['status']) && $response['status'] === 'success') {
                    delete_transient($transient_key);
                }

                return $response;
            } finally {
                delete_option($claim_key);
            }
        } else {
            $response = array(
                'status' => 'failed',
                'message' => __('Error! An error has occurred', 'PLWN_translate_pro'),
                'text' => __('Try again', 'PLWN_translate_pro')
            );
            return $response;
        }
    }
}
