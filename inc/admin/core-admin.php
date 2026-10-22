<?php
/**
 * Description: this file create admin setting
 */
defined('PLWN_PLUGIN_FILE') || exit;

// Add PLWN under Settings.
function PLWN_plugin_menu()
{
    add_options_page(
        __('PLWN Manager', 'PLWN_translate_pro'),
        __('PLWN Dashboard', 'PLWN_translate_pro'),
        'manage_options',
        'PLWN-plugin-settings',
        'PLWN_plugin_settings_callback'
    );
}
add_action('admin_menu', 'PLWN_plugin_menu');

function PLWN_redirect_legacy_settings_url()
{
    global $pagenow;
    if ('admin.php' !== $pagenow || !isset($_GET['page']) || 'PLWN-plugin-settings' !== sanitize_text_field(wp_unslash($_GET['page']))) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    wp_safe_redirect(add_query_arg('page', 'PLWN-plugin-settings', admin_url('options-general.php')));
    exit;
}
add_action('admin_init', 'PLWN_redirect_legacy_settings_url', 0);
function PLWN_plugin_settings_callback()
{
    settings_errors();
    ?>
    <div class="wrap">
        <h1><?php _e('PLWN settings', 'PLWN_translate_pro') ?></h1>
        <form method="post" action="options.php" id="form_settings_PLWN">
            <?php
            settings_fields('PLWN_plugin_settings_group');
            do_settings_sections('PLWN-plugin-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// add settings section and boxes
function PLWN_plugin_register_settings()
{
    register_setting('PLWN_plugin_settings_group', 'PLWN_plugin_sms_sending', 'PLWN_plugin_sms_sending_sanitize_callback');

    add_settings_section(
        'PLWN_plugin_section',
        __('Settings section', 'PLWN_translate_pro'),
        'PLWN_plugin_section_callback',
        'PLWN-plugin-settings'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_API',
        __('API received from SMS gateway', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_API_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_logo',
        __('set logo for login form', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_logo_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_resend_time',
        __('The duration between sending SMS', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_resend_time_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_otp_lifetime',
        __('OTP code lifetime', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_otp_lifetime_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_default_email_domain',
        __('Default email domain', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_default_email_domain_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_developer_mode',
        __('Star developer mode', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_developer_mode_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_url_redirect_login',
        __('Links with mandatory entry', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_url_redirect_login_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_url_login_page',
        __('Links your login page', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_url_login_page_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_otp_range_code',
        __('number of digits of Otp code', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_otp_range_code_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_user_role',
        __('User role', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_user_role_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_sample_number',
        __('sample of number', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_sample_number_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );

    add_settings_field(
        'PLWN_plugin_sms_sending_country_code',
        __('country code', 'PLWN_translate_pro'),
        'PLWN_plugin_sms_sending_country_code_callback',
        'PLWN-plugin-settings',
        'PLWN_plugin_section'
    );
}
add_action('admin_init', 'PLWN_plugin_register_settings');

// add description for this section
function PLWN_plugin_section_callback()
{
    ?>
    <p class="introduce">Fill the required values ​​carefully so as not to encounter problems.<br>ShortCode :
        [PLWN_login_form]</p>
    <?php
}

function PLWN_get_privileged_role_capabilities()
{
    $capabilities = array(
        'manage_options',
        'list_users',
        'edit_users',
        'create_users',
        'delete_users',
        'promote_users',
        'install_plugins',
        'activate_plugins',
        'edit_plugins',
        'edit_themes',
        'switch_themes',
        'unfiltered_html',
        'edit_others_posts',
        'manage_woocommerce',
    );

    $capabilities = apply_filters('paradise_login_privileged_role_capabilities', $capabilities);
    return is_array($capabilities) ? array_values(array_filter($capabilities, 'is_string')) : array();
}

function PLWN_is_privileged_role($role_name)
{
    $role = get_role($role_name);
    if (!$role) {
        return false;
    }

    foreach (PLWN_get_privileged_role_capabilities() as $capability) {
        if (!empty($role->capabilities[$capability])) {
            return true;
        }
    }

    return false;
}

function PLWN_should_block_privileged_login($user, $context)
{
    if (!$user instanceof WP_User || (defined('PARADISE_LOGIN_ALLOW_PRIVILEGED_LOGIN') && true === PARADISE_LOGIN_ALLOW_PRIVILEGED_LOGIN)) {
        return false;
    }

    $privileged = is_super_admin($user->ID);
    if (!$privileged) {
        foreach (PLWN_get_privileged_role_capabilities() as $capability) {
            if (user_can($user, $capability)) {
                $privileged = true;
                break;
            }
        }
    }

    if (!$privileged) {
        return false;
    }

    $block = (bool) apply_filters('paradise_login_block_privileged_login', true, $user);
    if ($block) {
        do_action('paradise_login_privileged_login_blocked', (int) $user->ID, $context);
    }

    return $block;
}

// checked input variables
function PLWN_plugin_sms_sending_sanitize_callback($input)
{
    if (!is_array($input)) {
        $current_options = get_option('PLWN_plugin_sms_sending', array());
        return is_array($current_options) ? $current_options : array();
    }

    $current_options = get_option('PLWN_plugin_sms_sending', array());
    if (!is_array($current_options)) {
        $current_options = array();
    }
    $input = array_merge($current_options, $input);
    foreach ($input as $key => $value) {
        if (!is_scalar($value)) {
            $input[$key] = isset($current_options[$key]) ? $current_options[$key] : '';
        }
    }

    if (isset($input['API'])) {
        $input['API'] = sanitize_text_field($input['API']);
    }
    if (isset($input['logo'])) {
        $input['logo'] = absint($input['logo']);
    }
    if (isset($input['resend_time'])) {
        $input['resend_time'] = max(1, absint($input['resend_time']));
    }
    if (isset($input['otp_lifetime']) && $input['otp_lifetime'] !== '') {
        $input['otp_lifetime'] = min(900, max(1, absint($input['otp_lifetime'])));
    }
    if (isset($input['default_email_domain'])) {
        $email_domain = PLWN_sanitize_email_domain($input['default_email_domain']);
        if ($email_domain === false) {
            add_settings_error(
                'PLWN_plugin_sms_sending',
                'invalid_default_email_domain',
                __('Enter a valid email domain with at least one dot. The previous value was kept.', 'PLWN_translate_pro'),
                'error'
            );
            if (isset($current_options['default_email_domain'])) {
                $input['default_email_domain'] = $current_options['default_email_domain'];
            } else {
                unset($input['default_email_domain']);
            }
        } else {
            $input['default_email_domain'] = $email_domain;
        }
    }
    if (isset($input['developer_mode'])) {
        $input['developer_mode'] = (string) $input['developer_mode'] === '1' ? '1' : '0';
    }
    if (isset($input['redirect_login'])) {
        $input['redirect_login'] = sanitize_textarea_field($input['redirect_login']);
    }
    if (!empty($input['url_login_page'])) {
        $input['url_login_page'] = esc_url_raw($input['url_login_page']);
    } elseif (isset($input['url_login_page'])) {
        $input['url_login_page'] = '';
    }
    if (isset($input['otp_range_code'])) {
        $digits = absint($input['otp_range_code']);
        if ($digits > 9) {
            add_settings_error(
                'PLWN_plugin_sms_sending',
                'otp_range_code_capped',
                __('The OTP digit length was capped at 9.', 'PLWN_translate_pro'),
                'error'
            );
        }
        $input['otp_range_code'] = min(9, max(3, $digits));
    }
    if (isset($input['user_role'])) {
        $input['user_role'] = sanitize_key($input['user_role']);
        if (PLWN_is_privileged_role($input['user_role'])) {
            add_settings_error(
                'PLWN_plugin_sms_sending',
                'privileged_registration_role',
                __('Phone registration cannot assign a privileged role. Choose a role without privileged capabilities.', 'PLWN_translate_pro'),
                'error'
            );
            if (isset($current_options['user_role'])) {
                $input['user_role'] = $current_options['user_role'];
            } else {
                unset($input['user_role']);
            }
        }
    }
    if (isset($input['sample_number'])) {
        $input['sample_number'] = sanitize_text_field($input['sample_number']);
    }
    if (isset($input['country_code'])) {
        $input['country_code'] = sanitize_text_field($input['country_code']);
    }

    return $input;
}

function PLWN_get_otp_lifetime($options = null)
{
    if (!is_array($options)) {
        $options = get_option('PLWN_plugin_sms_sending', array());
    }

    $default = isset($options['resend_time']) ? absint($options['resend_time']) : 30;
    $lifetime = isset($options['otp_lifetime']) && $options['otp_lifetime'] !== ''
        ? absint($options['otp_lifetime'])
        : $default;
    $lifetime = min(900, max(1, $lifetime));
    $lifetime = apply_filters('paradise_login_otp_lifetime', $lifetime, $options);

    return min(900, max(1, absint($lifetime)));
}

function PLWN_sanitize_email_domain($value)
{
    if (!is_scalar($value)) {
        return false;
    }

    $domain = strtolower(trim((string) $value));
    $domain = preg_replace('~^[a-z][a-z0-9+.-]*://~i', '', $domain);
    $domain = preg_replace('~[/?#].*$~', '', $domain);
    $domain = preg_replace('/\s+/u', '', $domain);
    $domain = ltrim($domain, '@');

    if (!is_string($domain) || strpos($domain, '.') === false
        || filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
        return false;
    }

    return $domain;
}

function PLWN_get_default_email_domain($options = null)
{
    if (!is_array($options)) {
        $options = get_option('PLWN_plugin_sms_sending', array());
    }
    if (!is_array($options)) {
        $options = array();
    }

    $domain = isset($options['default_email_domain'])
        ? PLWN_sanitize_email_domain($options['default_email_domain'])
        : false;

    return $domain !== false ? $domain : 'example.com';
}

// add fields for this section
function PLWN_plugin_sms_sending_API_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = is_array($options) && isset($options['API']) ? $options['API'] : '';
    ?>
    <input type="text" name="PLWN_plugin_sms_sending[API]" value="<?php echo esc_attr($value); ?>" id="sms_sending_API" required>
    <p class="PLWN_info">
        Enter the API received from your SMS panel, of course you can use the samples below and create your own API. Enter
        your panel information instead of expressions inside {} and do not touch the expressions between %%.<br>
        https://ippanel.com/patterns/pattern?username={Username}&password={Password}&from={SEnderNumber}&to=%PLWN_mobile_number%&input_data={"{VariableName}":%PLWN_otp%}&pattern_code={YourPatternCode}<br>
        https://api.kavenegar.com/v1/{api_key}/verify/lookup.json?receptor=%PLWN_mobile_number%&token=%PLWN_otp%&template={template_code}
    </p>
    <?php
}

function PLWN_plugin_sms_sending_logo_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = is_array($options) && isset($options['logo']) ? absint($options['logo']) : 0;
    if ($value) {
        $image = wp_get_attachment_image($value, 'medium', false, array('id' => 'PLWN-logo-preview-image'));
    } else {
        $image = '<img id="PLWN-logo-preview-image" src="' . esc_url(plugins_url('assets/icons/logo.png', PLWN_PLUGIN_FILE)) . '" />';
    }

    echo wp_kses_post($image);
    ?>
    <input type="hidden" name="PLWN_plugin_sms_sending[logo]" id="PLWN-logo_image_id"
        value="<?php echo esc_attr($value); ?>" class="regular-text" required />
    <input type="button" class="button-primary" value="<?php echo esc_attr__('Select a image', 'PLWN_translate_pro'); ?>"
        id="PLWN_logo_media_manager" />
    <?php
}

// Ajax action to refresh the user image
add_action('wp_ajax_PLWN_logo_get_image', 'PLWN_logo_get_image');
function PLWN_logo_get_image()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Not allowed.', 'PLWN_translate_pro')), 403);
    }
    check_ajax_referer('PLWN_admin_plugin_nonce', 'nonce');

    $image_id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
    if ($image_id) {
        $image = wp_get_attachment_image($image_id, 'medium', false, array('id' => 'PLWN-logo-preview-image'));
        wp_send_json_success(array('image' => $image));
    }

    wp_send_json_error();
}

function PLWN_plugin_sms_sending_resend_time_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = is_array($options) && isset($options['resend_time']) ? absint($options['resend_time']) : 30;
    ?>
    <input type="number" id="sms_sending_resend_time" name="PLWN_plugin_sms_sending[resend_time]" min="30"
        value="<?php echo esc_attr($value); ?>" required>
    <p class="PLWN_info">
        Enter the amount of time required between each SMS sending in seconds.
    </p>
    <?php
}

function PLWN_plugin_sms_sending_otp_lifetime_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = PLWN_get_otp_lifetime($options);
    ?>
    <input type="number" id="sms_sending_otp_lifetime" name="PLWN_plugin_sms_sending[otp_lifetime]"
        min="1" max="900" value="<?php echo esc_attr($value); ?>" required>
    <p class="PLWN_info">
        Enter how long a verification code remains valid, in seconds (maximum 900).
    </p>
    <?php
}

function PLWN_plugin_sms_sending_default_email_domain_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = PLWN_get_default_email_domain($options);
    ?>
    <input type="text" id="sms_sending_default_email_domain"
        name="PLWN_plugin_sms_sending[default_email_domain]" value="<?php echo esc_attr($value); ?>"
        placeholder="example.com" required>
    <p class="PLWN_info">
        The domain used for email addresses created during phone registration.
    </p>
    <?php
}

function PLWN_plugin_sms_sending_developer_mode_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = is_array($options) && isset($options['developer_mode']) ? (string) $options['developer_mode'] : '0';
    ?>
    <select name="PLWN_plugin_sms_sending[developer_mode]" id="sms_sending_developer_mode" required>
        <option value="1" <?php selected($value, '1'); ?>><?php _e('ON', 'PLWN_translate_pro'); ?>
        </option>
        <option value="0" <?php selected($value, '0'); ?>><?php _e('OFF', 'PLWN_translate_pro'); ?>
        </option>
    </select>
    <?php
}

function PLWN_plugin_sms_sending_url_redirect_login_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = is_array($options) && isset($options['redirect_login']) ? $options['redirect_login'] : '';
    ?>
    <textarea id="sms_sending_redirect_login" name="PLWN_plugin_sms_sending[redirect_login]"
        rows="6"><?php echo esc_textarea($value); ?></textarea>
    <p class="PLWN_info">
        Enter the links in which you want to make entry mandatory in each line.
    </p>
    <?php
}

function PLWN_plugin_sms_sending_url_login_page_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = is_array($options) && isset($options['url_login_page']) ? $options['url_login_page'] : '';
    ?>
    <input type="text" name="PLWN_plugin_sms_sending[url_login_page]" value="<?php echo esc_url($value); ?>"
        id="sms_sending_url_login_page" required>
    <p class="PLWN_info">
        Enter your login page link.
    </p>
    <?php
}

function PLWN_plugin_sms_sending_otp_range_code_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = is_array($options) && isset($options['otp_range_code']) ? absint($options['otp_range_code']) : 6;
    ?>
    <input type="number" id="sms_sending_otp_range_code" name="PLWN_plugin_sms_sending[otp_range_code]" min="3"
        value="<?php echo esc_attr($value); ?>" required>
    <?php
}


function PLWN_privileged_registration_role_admin_notice()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = get_option('PLWN_plugin_sms_sending', array());
    $role = is_array($options) && isset($options['user_role']) ? $options['user_role'] : '';
    if (!$role || !PLWN_is_privileged_role($role)) {
        return;
    }

    $settings_url = admin_url('options-general.php?page=PLWN-plugin-settings');
    echo '<div class="notice notice-warning"><p>' . esc_html__('Phone self-registration is using a safe fallback because its saved role is privileged.', 'PLWN_translate_pro') . ' '
        . '<a href="' . esc_url($settings_url) . '">' . esc_html__('Update the registration role.', 'PLWN_translate_pro') . '</a></p></div>';
}
add_action('admin_notices', 'PLWN_privileged_registration_role_admin_notice');

function PLWN_plugin_sms_sending_user_role_callback()
{
    $roles = get_editable_roles();
    $options = get_option('PLWN_plugin_sms_sending', array());
    $selected_role = is_array($options) && isset($options['user_role']) ? $options['user_role'] : 'subscriber';
    $selected_is_privileged = PLWN_is_privileged_role($selected_role);

    if ($selected_is_privileged && !isset($roles[$selected_role])) {
        $all_roles = wp_roles()->roles;
        if (isset($all_roles[$selected_role])) {
            $roles[$selected_role] = $all_roles[$selected_role];
        }
    }

    echo '<select name="PLWN_plugin_sms_sending[user_role]" id="sms_sending_user_role" required>';
    foreach ($roles as $role_key => $role_info) {
        $is_privileged = PLWN_is_privileged_role($role_key);
        if ($is_privileged && $role_key !== $selected_role) {
            continue;
        }
        $selected = ($selected_role === $role_key) ? 'selected="selected"' : '';
        $role_label = $role_info['name'];
        if ($is_privileged) {
            $role_label .= ' — ' . __('privileged; choose a safer role', 'PLWN_translate_pro');
        }
        echo '<option value="' . esc_attr($role_key) . '" ' . $selected . '>' . esc_html($role_label) . '</option>';
    }
    echo '</select>';
    if ($selected_is_privileged) {
        echo '<p class="notice notice-error"><strong>' . esc_html__('The saved registration role is privileged. Select a non-privileged role and save settings.', 'PLWN_translate_pro') . '</strong></p>';
    }
}

function PLWN_plugin_sms_sending_pattern_number_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $pattern_number = is_array($options) && isset($options['pattern_number']) ? sanitize_text_field($options['pattern_number']) : '[0-9]{10}';
    ?>
    <input type="text" id="sms_sending_pattern_number" name="PLWN_plugin_sms_sending[pattern_number]"
        value="<?php echo esc_attr($pattern_number); ?>" required>
    <p class="PLWN_info">
        Check and filter the type of input numbers.
    </p>
    <?php
}

function PLWN_plugin_sms_sending_sample_number_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = is_array($options) && isset($options['sample_number']) ? $options['sample_number'] : '';
    ?>
    <input type="number" id="sms_sending_sample_number" name="PLWN_plugin_sms_sending[sample_number]"
        value="<?php echo esc_attr($value); ?>" placeholder="9120000000" required>
    <p class="PLWN_info">
        Change the default input text of the contact number input.
    </p>
    <?php
}

function PLWN_plugin_sms_sending_country_code_callback()
{
    $options = get_option('PLWN_plugin_sms_sending', array());
    $value = is_array($options) && isset($options['country_code']) ? $options['country_code'] : '';
    ?>
    <input type="text" id="sms_sending_country_code" name="PLWN_plugin_sms_sending[country_code]"
        value="<?php echo esc_attr($value); ?>" placeholder="+98" pattern="[+]{1}[0-9]{,10}" required>
    <?php
}

// add style and script to dashboard
function PLWN_plugin_admin_scripts($page)
{
    if ($page != 'settings_page_PLWN-plugin-settings') {
        return;
    }
    wp_enqueue_style('admin-panle-style', plugin_dir_url(__FILE__) . 'assets/admin-panle-style.css');
    wp_enqueue_media();
    wp_enqueue_script('admin-panle-script', plugin_dir_url(__FILE__) . 'assets/admin-panle-script.js', array('jquery'), null, true);
    wp_localize_script('admin-panle-script', 'PLWN_admin_plugin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('PLWN_admin_plugin_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'PLWN_plugin_admin_scripts');


// Add custom user meta fields admin edit users
function PLWN_plugin_user_meta_fields($user)
{
    ?>
    <h3><?php _e('PLWN meta box', 'PLWN_translate_pro') ?></h3>
    <table class="form-table" id="PLWN_data">
        <tr>
            <th><label for="phone_number"><?php _e('Phone number', 'PLWN_translate_pro') ?></label></th>
            <td>
                <input type="text" name="mobile_user" id="mobile_user"
                    value="<?php echo esc_attr(get_the_author_meta('mobile_user', $user->ID)); ?>"
                    class="regular-text" /><br />
                <span class="description"><?php _e('Please enter your phone number.', 'PLWN_translate_pro') ?></span>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'PLWN_plugin_user_meta_fields');
add_action('edit_user_profile', 'PLWN_plugin_user_meta_fields');

// Save custom user meta fields
function PLWN_plugin_save_user_meta_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    if (isset($_POST['mobile_user'])) {
        update_user_meta($user_id, 'mobile_user', sanitize_text_field(wp_unslash($_POST['mobile_user'])));
    }
}
add_action('personal_options_update', 'PLWN_plugin_save_user_meta_fields');
add_action('edit_user_profile_update', 'PLWN_plugin_save_user_meta_fields');
