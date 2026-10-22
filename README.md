# sign in/up with number

Phone-number login and registration using SMS verification codes for WordPress.

## Requirements

- WordPress 4.6 or later.
- PHP 7.3 or later.
- An SMS gateway account and API URL.

## Installation

1. Download the plugin ZIP from [GitHub Releases](https://github.com/parsa-rajabi-nanami/paradise-login/releases). Do not use **Code → Download ZIP**; that archive has a different folder name.
2. In WordPress, open **Plugins → Add New → Upload Plugin** and upload the release ZIP.
3. The installed plugin folder must be named `paradise-login-with-numbers` under `wp-content/plugins/`.
4. Activate the plugin.

To update, upload the new release ZIP over the existing installation using the same method. Keep the folder name `paradise-login-with-numbers`.

## Configuration

Open **Settings → PLWN Dashboard** in the WordPress admin menu.

| Setting | Purpose | Default |
|---|---|---|
| SMS gateway API | Gateway request URL; use `%PLWN_otp%` and `%PLWN_mobile_number%` where values belong. | Empty; configure before sending |
| Login form logo | Select the image shown on the login form. | Built-in plugin logo |
| SMS resend interval | Minimum seconds between sends, per IP and phone. | 30 seconds |
| OTP lifetime | How long a code remains valid; maximum 900 seconds. | Same as the resend interval |
| Default email domain | Domain used for generated addresses of newly registered users. | `example.com` |
| Developer mode | Retained setting; currently has no runtime effect. | Off |
| Required-login URLs | URLs that require login before access. | Empty |
| Login page URL | Destination used for configured login redirects. | Site home URL |
| OTP digit count | Number of digits in new verification codes (3–9). | 6 |
| Registration role | Role assigned to newly registered users; privileged roles are rejected. | Subscriber |
| Sample phone number | Placeholder shown in the phone field. | `9120000000` |
| SMS country code | Prefix added to the phone number in the SMS gateway request. | Empty |
| Legacy browser pattern | No longer shown or used; the stored value is retained. | Not applicable |

## Usage

Add `[PLWN_login_form]` to a page or post. It has no shortcode attributes.

## Hooks

| Name | Args | Purpose |
|---|---|---|
| `paradise_login_normalized_phone_number` (filter) | `$normalized`, `$phone_number` | Adjust a normalized phone value before validation and use. |
| `paradise_login_otp_rate_limits` (filter) | `$limits` | Adjust the verify-IP, verify-phone, per-code attempt, and window limits. |
| `paradise_login_otp_lifetime` (filter) | `$lifetime`, `$options` | Adjust the OTP lifetime; the result remains bounded to 1–900 seconds. |
| `paradise_login_default_user_email` (filter) | `$email`, `$phone` | Override the generated email for a new account. |
| `paradise_login_block_privileged_login` (filter) | `$block`, `WP_User $user` | Allow or block phone login for an account with a privileged capability; defaults to blocking. |
| `paradise_login_privileged_login_blocked` (action) | `$user_id`, `$context` (`send` or `verify`) | Observe a blocked privileged phone-login attempt. |
| `wp_login` (action) | `$user_login`, `$user` | Fired after the plugin authenticates a user. |
| `wp_ajax_nopriv_PLWN_ajax_get_csrf_token` (action) | No callback arguments | Public endpoint used by the form to obtain a cache-compatible request token. |
| `wp_ajax_nopriv_PLWN_ajax_send_sms` (action) | No callback arguments | Public endpoint for sending a code and verifying an OTP. |
| `wp_ajax_PLWN_logo_get_image` (action) | No callback arguments | Authorized admin endpoint for the logo preview. |

## Constants

| Name | Purpose | Default |
|---|---|---|
| `PARADISE_LOGIN_VERSION` | Plugin version. | `1.1.0` |
| `PARADISE_LOGIN_ALLOW_PRIVILEGED_LOGIN` | Define as `true` in `wp-config.php` to restore phone login for privileged accounts. | Undefined; privileged phone login is blocked |

## Upgrade notes

- **Behavior change — security (breaking):** Accounts with any capability in the privileged-registration capability list, including administrators and WooCommerce shop managers by default, can no longer use phone login. They must use standard WordPress login. Define `PARADISE_LOGIN_ALLOW_PRIVILEGED_LOGIN` as `true` in `wp-config.php` to restore privileged phone login.
- **Behavior change — administrator settings:** The settings page moved from the top-level admin menu to **Settings → PLWN Dashboard**; its URL slug is unchanged.
- **Behavior change — security:** OTPs are cryptographically generated, stored as keyed digests, rate-limited, short-lived, and consumed after successful verification.
- **Behavior change — security:** Self-registration rejects privileged roles and uses a safe fallback when a privileged role is already stored.
- **Behavior change — input:** Phone input accepts Persian and Arabic-Indic digits and formatting separators; legacy numeral variants are matched without rewriting stored values.
- **Behavior change — compatibility:** Plugin includes and assets use portable paths, shortcode output is returned in place, and the form defines RTL layout with LTR phone and OTP fields.
- **Behavior change — security:** Cached login forms obtain a same-origin token; the legacy nonce path requires same-origin validation.
- **Behavior change — login:** Configured login destinations are validated; the existing non-remembered authentication cookie remains unchanged.
- **Behavior change — transport:** SMS provider response bodies are no longer returned to the browser; failed gateway responses use a generic failure response.
- **Behavior change — administrator settings:** Unsafe setting values are sanitized and escaped, privileged roles are flagged, the no-effect legacy browser pattern field is hidden, and OTP digit settings are capped at nine.
- **Behavior change — uninstall:** Cleanup is scoped to plugin data and iterates all sites on multisite instead of flushing the whole object cache.
- The configurable registration email domain defaults to the previous domain; existing user emails are unchanged.

## Security

Report vulnerabilities privately through [GitHub Security Advisories](https://github.com/parsa-rajabi-nanami/paradise-login/security/advisories/new). Do not report vulnerabilities in public issues.

## License

MIT. See [LICENSE](LICENSE).
