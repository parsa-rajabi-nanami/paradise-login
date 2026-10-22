# Paradise Login — rules for agents
WP plugin: login via phone + SMS OTP. Main file `paradise-login-with-numbers.php` (never rename or move it, nor the plugin dir, slug, or text domain).
Tag `baseline-legacy` = production code on live sites = frozen public API.

## Rules
- Backward compat first. Public = anything not provably internal: options, meta, transients, tables, hooks, public funcs/classes, shortcodes, AJAX/REST, HTML ids/classes/field names, JS globals.
- Renamed things: keep the old as a thin wrapper + `*_deprecated` helpers. Remove nothing.
- Migrations: additive, idempotent, never delete old data, run on `plugins_loaded` by stored-version compare (not on activation).
- New setting default == old behavior. Behavior changes are opt-in, except security fixes that can't lock out valid users; list them in CHANGELOG.
- Work on the current branch. Small focused commits. No push, tag, or release without my approval.
- Ambiguous → ask. Don't guess.

## Efficiency
- Use `rg`/`git grep` and read line ranges. Never cat whole files unless under 150 lines.
- Don't re-read files already summarized in `docs/CONTRACT.md`.
- Run only tests/lint for touched code. Full suite once before the final report.
- Output terse: tables over prose, no restating the task, no code dumps in chat.
