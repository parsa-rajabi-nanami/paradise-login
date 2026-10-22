# Changelog

All notable changes to this project are documented here.

## [1.1.0] - 2026-10-22

- **Behavior change — security (breaking):** Block phone login for privileged accounts; affected administrators and shop managers must use standard WordPress login or define `PARADISE_LOGIN_ALLOW_PRIVILEGED_LOGIN` as `true` in `wp-config.php`.
- **Behavior change — administrator settings:** Move the settings page under Settings while preserving its page slug and capability requirement.
- **Behavior change — security:** Use cryptographically generated OTPs, keyed digest storage, bounded lifetimes, single-use verification, and IP, phone, and code attempt limits.
- **Behavior change — security:** Prevent privileged roles from being assigned through self-registration and safely fall back for a previously saved privileged role.
- **Behavior change — compatibility:** Use portable include and asset paths, return shortcode markup at its content position, and set explicit RTL/LTR form direction.
- **No behavior change — security:** Serialize send cooldown checks and recover stale locks while retaining current cooldown behavior.
- **Behavior change — input:** Normalize Persian and Arabic-Indic digits and phone formatting, and match supported legacy numeral formats without rewriting stored values.
- **Behavior change — input:** Accept normalized phone formatting in the browser and stop using the legacy pattern setting.
- **No behavior change — security:** Reclaim stale lock options after 30 seconds while retaining current cooldowns and request behavior.
- **Behavior change — security and operations:** Add cache-compatible same-origin protection, verification throttles, safe login redirects, capability- and nonce-checked administrator operations, sanitized settings output, bounded SMS gateway requests, generic provider statuses, deferred translations, and plugin-scoped multisite cleanup.
- **No behavior change:** Keep `example.com` as the default generated email domain; allow administrators and integrations to choose another domain for new accounts.
