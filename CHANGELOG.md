# Changelog

All notable changes to Warder Cookie Consent are documented here.

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
