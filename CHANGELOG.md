# Changelog

All notable changes to Warder Cookie Consent are documented here.

## [2.1.0] - 2026-05-28

### Added
- `inc/helpers.php` — public helper module loaded from the main plugin file
- `warder_has_consent( $category )` — PHP function for theme and plugin authors to check whether the current visitor accepted a given cookie category. Reads the `cc_cookie` payload set by vanilla-cookieconsent v3, JSON-decodes it, and checks `in_array( $category, $data['categories'], true )` (the v3 cookie stores accepted categories as a string array, not a keyed map). The `necessary` category short-circuits to `true`. Wrapped in `function_exists()` for safety; inputs sanitized via `sanitize_key()` and unslashed via `wp_unslash()`. Usage (works for any cookie-using analytics provider — Google Analytics, Matomo, etc. — or custom categories like `marketing`):
  ```php
  if ( warder_has_consent( 'analytics' ) ) {
      // emit your analytics snippet server-side (GA, Matomo, ...)
  }
  ```

## [2.0.1] - 2026-05-28

### Fixed
- `necessary` category `enabled` and `readonly` values were silently overwritten as `false` on every admin settings save. The admin form is submitted via AJAX with `form.serialize()`, which drops `disabled` fields — the readonly checkbox for `necessary` is `disabled`, so it was never submitted. `warder_validate_options` now forces `enabled = true` and `readonly = true` for the `necessary` category regardless of form input. The `enabled` checkbox in the admin UI is also now `disabled` for `necessary`, consistent with `readonly`.
- `is_regex` was always saved as `true` for every cookie. The hidden input used `value=""` for false, so PHP's `isset()` returned `true` for the empty string, silently marking non-regex cookies (`_gid`, `_gat`) as regex patterns and corrupting the autoClear list. The hidden input now outputs `'0'` for false; validation uses `!empty()` with a literal `'0'` check so only the string `'1'` is treated as true.
- Non-necessary categories (e.g. Analytics) were appearing as locked and pre-selected in the frontend preferences modal. `warder_validate_options` was using `isset()` to read `enabled`/`readonly` from DB values rather than enforcing them by policy. PHP now always saves `enabled = false, readonly = false` for non-necessary categories; `src/index.js` derives `enabled`/`readOnly` from the category id rather than trusting DB values; the admin UI replaces the confusing enabled/readonly checkboxes with descriptive lock/unlock icons.
- AJAX save handler no longer returns a misleading "No changes detected" message. `update_option()` returns `false` when the value is unchanged, but the save did succeed — the response now always says "Settings saved successfully."

## [2.0.0] - 2026-05-28

### Changed
- Split plugin logic out of the monolithic `warder-cookie-consent.php` into five focused files under `inc/`:
  - `inc/defaults.php` — `warder_get_default_options()`, `warder_get_merged_options()`
  - `inc/settings.php` — `warder_register_settings()`, `warder_validate_options()`, `warder_update_options_timestamp()`, `warder_plugin_activate()`
  - `inc/ajax.php` — `warder_ajax_save_settings()`, `warder_handle_admin_actions()`
  - `inc/admin.php` — `warder_add_options_page()`, `warder_enqueue_admin_scripts()`, `warder_render_options_page()`, `warder_admin_notices()`, `warder_render_category_title_field()`
  - `inc/frontend.php` — `warder_enqueue_scripts()`, `warder_get_preferences_toggle_css()`, `warder_add_preferences_button()`
- Main plugin file is now 26 lines: plugin header, `WARDER_VERSION`, `WARDER_PLUGIN_FILE`, and five `require_once` calls
- Added `WARDER_PLUGIN_FILE` constant (`__FILE__` captured at plugin root) so `plugin_dir_url()`, `get_plugin_data()`, and `register_activation_hook()` resolve correctly from within `inc/` files

No behaviour changes; all existing settings, hooks, and nonces are unaffected.

### Fixed
- Admin page title changed from "Cookie Consent Settings" to "Warder Cookie Consent"
- Settings sidebar label changed from "Cookie Consent" to "Warder Consent" to avoid ambiguity when other consent plugins are active

## [1.5.2] - 2026-05-28

### Added
- AJAX save for the settings page via `wp_ajax_warder_save_settings` (`warder_ajax_save_settings()`). Submit is intercepted in `assets/js/admin.js`, the form serializes through `$.post( ajaxurl, ... )`, and the response is shown in a dismissible `.warder-ajax-notice` injected before the form — no `options.php` redirect, no scroll-to-top
- Admin JS extracted from the inline `wp_add_inline_script()` heredoc into `assets/js/admin.js`, enqueued via `wp_enqueue_script( 'warder-admin' )` with `wp_localize_script` providing `warderAdmin.ajaxurl`, `warderAdmin.save`, and `warderAdmin.saving`
- `?warder_notice=saved` redirect-after-POST in `warder_handle_admin_actions()` so add/delete-category and add/delete-cookie actions show a success notice on the resulting page

### Fixed
- Setup welcome notice in `warder_admin_notices()` now self-suppresses once `warder_options_last_updated` is set, so it stops appearing on every admin page after configuration (WordPress.org guideline 11)
- Add Cookie form was nested inside `#warder-main-settings-form` and used the HTML5 `form="..."` attribute to target a hidden form elsewhere in the DOM. Browsers handle `display:none` target forms inconsistently, which dropped the `is_regex` checkbox state and caused the AJAX save handler to intercept Add Cookie submissions. Each Add Cookie form is now rendered as a self-contained `<form>` after `</form>` of the main settings form
- AJAX save notice is now scrolled into view via `$( 'html, body' ).animate( { scrollTop: $notice.offset().top - 50 }, 300 )` so it is visible regardless of the scroll position at submit time
- Show-add-cookie button scrolls the now-revealed container into view on open

### Changed
- Dropped the `:not([form])` filters on the change-highlight selector and submit-button lookup in `assets/js/admin.js`, and removed the JS `.after()` reposition of add-cookie containers. These were workarounds for the old nested-form layout and are no longer needed now that PHP renders the containers outside the main form

## [1.5.1] - 2026-05-28

### Fixed
- Add Cookie form now appears directly below the "Add Cookie to this Category" button instead of at the bottom of the page (below Save All Settings and Add New Category). Moved the `warder-add-cookie-form-container` divs from the end of `warder_render_options_page()` into each category section, immediately after the show-add-cookie-form button
- Add Cookie submission was silently being dropped because moving the form into the category section nested it inside the main `<form action="options.php">` — browsers discard nested `<form>` tags, so the click submitted the outer settings form instead. The actual `<form id="warder-add-cookie-form-<id>">` element now lives outside the main settings form; the visible cookie name / regex / submit inputs reference it via the HTML5 `form="..."` attribute

## [1.5.0] - 2026-05-28

### Added
- Matomo cookie patterns to the default analytics category in `warder_get_default_options()`: `/^_pk_/` (matches `_pk_id.*`, `_pk_ses.*`, `_pk_ref.*`, `_pk_cvar.*`, `_pk_hsr.*`) and `/^mtm_/` (Matomo Tag Manager consent cookies) — new installs now manage Matomo cookies out of the box alongside the existing Google Analytics patterns. Existing installs can add the same patterns via Settings → Cookie Consent

## [1.4.2] - 2026-05-28

### Fixed
- `register_setting()` updated to array format with explicit `sanitize_callback` key — satisfies WordPress.org guideline requiring formal sanitize_callback declaration
- `privacy_policy_url` now sanitized with `esc_url_raw()` instead of `sanitize_text_field()` — correct sanitizer for URL values

### Changed
- `.distignore` updated to ship `src/`, `webpack.config.js`, and `package.json` in the WordPress.org build — satisfies the human-readable source code requirement (guideline §4); `node_modules/` and `vendor/` remain excluded

## [1.4.1] - 2026-05-27

### Changed
- Replaced the `10up/wpcs-action`-based WPCS workflow with a local PHPCS workflow that runs against `phpcs.xml` — enforces `WordPress.WP.I18n` with the `warder-cookie-consent` text domain and `WordPress.Security.EscapeOutput`, catching i18n and escaping issues the old workflow missed

## [1.4.0] - 2026-05-27

### Added
- Restored the cookie category and cookie management actions in the settings page, now consolidated in `warder_handle_admin_actions()`: add a new category, add a cookie to a category, delete a cookie, and delete a category — each guarded by a dedicated nonce (`check_admin_referer()` / `wp_verify_nonce()`) and sanitized with `sanitize_key()` / `sanitize_text_field()` / `absint()`
- "Delete Category" link in each non-necessary category header so the delete-category action is reachable from the UI
- `Requires at least` (5.0) and `Requires PHP` (8.0) headers in the main plugin file so WordPress can enforce minimum-version compatibility at activation

### Changed
- Wrapped all hardcoded admin settings-page strings (section headings, field labels, descriptions, select options, buttons, placeholders, and confirm dialogs) in `esc_html_e()` / `esc_attr_e()` / `esc_html__()` / `esc_js()` with the `warder-cookie-consent` text domain — they are now translatable and escaped on output, and pass the `WordPress.WP.I18n` sniff

### Fixed
- The "Add New Category", "Add Cookie", and "Remove" cookie controls were rendering but had no backend handler after the earlier form refactor — clicking them did nothing. They now work again

## [1.3.2] - 2026-05-27

### Fixed
- Moved inline `<script>` block out of `warder_render_options_page()` and into a dedicated `warder_enqueue_admin_scripts()` function hooked to `admin_enqueue_scripts`, using `wp_add_inline_script()` — resolves WP.org guideline violation for direct `<script>` output
- Wrapped `warder_get_preferences_toggle_css()` output with `wp_strip_all_tags()` before passing to `wp_add_inline_style()` — satisfies WP.org late-escaping requirement

### Added
- `== Source Code & Build Process ==` section in `readme.txt` documenting the GitHub repository, `src/index.js` entry point, and `npm install` / `npx webpack` build steps — satisfies WP.org requirement for human-readable source documentation of compiled assets

### Changed
- `Contributors` field in `readme.txt` updated to WordPress.org username `rhand`

## [1.3.1] - 2026-05-26

### Fixed
- `composer.json` is no longer `export-ignore`d in `.gitattributes`, so the package manifest ships in Composer/Packagist dist archives (it remains excluded from WordPress.org builds via `.distignore`, which is correct since WP.org does not use Composer)

## [1.3.0] - 2026-05-26

### Added
- `languages/` directory for shipping and community-contributed translation files (WordPress 4.6+ auto-loads translations via the `Text Domain` header)

### Changed
- Plugin renamed from Simple Cookie Consent to **Warder Cookie Consent** (Wheel of Time inspired, consistent with Elayne FSE theme and Waygate pattern builder)
- Main plugin file renamed to `warder-cookie-consent.php`
- Text domain changed from `simple-cookie-consent` to `warder-cookie-consent`
- All function and option prefixes changed from `scc_` to `warder_`
- Composer package renamed to `imagewize/warder-cookie-consent`
- GitHub repository renamed to `imagewize/warder-cookie-consent`

### Fixed
- Plugin Check workflow build directory renamed to `warder-cookie-consent` to match the text domain header, resolving `textdomain_mismatch` warnings

## [1.2.1] - 2026-05-26

### Fixed
- `composer.json` license corrected from MIT to GPL-2.0-or-later to match plugin header and LICENSE.md
- `composer.json` support URLs were pointing to wrong repository (carousel-block → simple-cookie-consent)

### Added
- `.gitattributes` with `export-ignore` directives to exclude dev files from Composer installs and git archives — resolves Plugin Check false-positive warnings when installing via Composer

## [1.2.0] - 2026-05-26

### Added
- Floating preferences toggle button rendered in the page footer — a cookie icon that opens the preferences modal so users can revisit consent choices at any time
- `show_preferences_toggle` option (enabled by default) to turn the button on or off without touching code
- `preferences_toggle_position` option with four choices: `bottom-right` (default), `bottom-left`, `top-right`, `top-left`
- "Preferences Toggle Button" row in General Settings admin UI with checkbox and position dropdown
- `scc_add_preferences_button()` hooked to `wp_footer` — only outputs when both `enabled` and `show_preferences_toggle` are true
- `scc_get_preferences_toggle_css()` — button styles registered via `wp_add_inline_style` (no render-blocking CSS file)

### Changed
- `scc_get_default_options()` extended with the two new option keys
- `scc_validate_options()` sanitizes the new checkbox and allowlisted position value
- `scc_enqueue_scripts()` conditionally registers the inline style handle when the toggle is enabled
- `src/index.js` wires the button's click event to `CookieConsent.showPreferences()` after `CookieConsent.run()`

## [1.1.0] - 2026-05-26

### Added
- "Enable Plugin" toggle in General Settings — allows disabling the consent banner without deactivating the plugin
- Plugin header fields required by WordPress.org: `Author URI`, `Text Domain`, `License`, `License URI`
- Direct file access protection via `defined('ABSPATH') || exit`
- `readme.txt` in WordPress.org standard format
- `phpcs.xml` with WordPress Coding Standards configuration
- `.distignore` to exclude dev files from distribution zip
- `.github/workflows/wpcs.yml` — WPCS check on pull requests
- `.github/workflows/plugin-check.yml` — WordPress Plugin Check on pull requests
- `.github/workflows/create-release.yml` — builds and attaches plugin zip on GitHub releases
- PHPDoc blocks on all public functions

### Changed
- License updated from MIT to GPLv2 or later (required for WordPress.org)
- `in_array()` calls now use strict comparison (`true` as third argument)
- Yoda conditions applied throughout per WordPress Coding Standards

### Fixed
- Output escaping throughout admin UI (`esc_url` on `wp_nonce_url`, `esc_attr` on hidden inputs, `esc_html__` on translated strings)
- Removed all `error_log()` debug calls from `scc_validate_options()`
- Removed debug `console.log` statements from inline admin JavaScript
- Full WordPress Coding Standards compliance — PHPCS passes with 0 errors
- Plugin Check workflow now uses correct plugin directory name, resolving false `textdomain_mismatch` and `trademarked_term` warnings
- `EnqueuedScriptsScope` Plugin Check warning resolved — frontend script only enqueued when plugin is enabled

### Security
- All `in_array()` calls hardened with strict mode to prevent type coercion bypass
- All admin page output audited and escaped with appropriate `esc_*` functions

## [1.0.0] - 2026-05-26

### Added
- `CLAUDE.md` with codebase architecture and build instructions for Claude Code
- `CHANGELOG.md` documenting full version history

### Security
- Updated webpack from 5.98.0 to 5.107.2, resolving two SSRF/allowedUris bypass vulnerabilities (moderate/low)
- Updated postcss (via `npm audit fix`), resolving XSS via unescaped `</style>` in CSS output (moderate)
- Transitive fixes via webpack update: serialize-javascript (RCE via RegExp/Date, CPU exhaustion DoS), fast-uri (host confusion + path traversal via percent-encoded segments), ajv (ReDoS with `$data` option)

## [1.0.0-beta.2] - 2025-05-14

### Fixed
- Corrected script blocking attribute from `data-cookiecategory` to `data-category` per vanilla-cookieconsent v3 documentation

## [1.0.0-beta.1] - 2025-04-04

### Added
- Cookie categories and sections management with configurable necessary and analytics categories
- Admin settings page (Settings > Cookie Consent) with full form submission and logging
- `scc_get_default_options()` and `scc_get_merged_options()` for reliable settings retrieval with defaults
- Guard for missing `cookie_categories` key in options array
- README cookie management documentation

### Changed
- Migrated to vanilla-cookieconsent v3 API (language structure, `gui_options`, `settings_modal`, categories format)
- Refactored admin form to save all settings correctly
- Restored missing default values returned from options functions

### Fixed
- Frontend script not enqueuing due to missing `wp_enqueue_scripts` action hook

## [0.0.4-alpha] - 2025-03-31

### Added
- Full vanilla-cookieconsent v3 configuration: `settings_modal`, `gui_options`, language structure, categories
- `CookieConsent.run()` initialization with DOM-ready guard
- Wildcard import of vanilla-cookieconsent functions; simplified to specific module imports
- Webpack container build

### Fixed
- Run import patch and initiation build fixes (Patch v4)

## [0.0.3-alpha] - 2025-03-31

### Added
- WordPress admin options page
- Cookie consent instance initiation
- Composer installation note in README

## [0.0.2-alpha] - 2025-03-31

### Added
- MIT License
- Composer package file (`imagewize/simple-cookie-consent`)

## [0.0.1-alpha] - 2025-03-31

### Added
- Initial plugin scaffolding with WordPress plugin header
- vanilla-cookieconsent v3 integration via webpack bundle (`src/index.js` → `dist/cookieconsent.bundle.js`)
- Webpack build system with CSS bundling via style-loader
