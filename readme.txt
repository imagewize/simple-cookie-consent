=== Warder Cookie Consent ===
Contributors: rhand
Donate link: https://imagewize.com
Tags: cookie, consent, gdpr, privacy, compliance
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 2.0.1
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight plugin that implements GDPR-compliant cookie consent functionality using the vanilla-cookieconsent library.

== Description ==

Warder Cookie Consent provides an easy way to add GDPR-compliant cookie consent banners to your WordPress website. The plugin uses the lightweight [CookieConsent v3](https://github.com/orestbida/cookieconsent) library and offers full customization through the WordPress admin interface.

= Features =

* Lightweight and fast performance
* Multi-language support (English, French, German, Spanish, Italian, Dutch)
* Customizable banner appearance and text
* Cookie category management (Necessary, Analytics, etc.)
* Automatic cookie blocking and clearing
* Floating preferences toggle button — lets users revisit consent choices at any time
* Fully responsive design
* No external dependencies

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin panel
3. Configure settings at **Settings > Cookie Consent**

== Source Code & Build Process ==

The bundled file `dist/cookieconsent.bundle.js` is compiled from source using webpack. The full source code is publicly available at:

https://github.com/imagewize/warder-cookie-consent

The entry point is `src/index.js`, which imports the [vanilla-cookieconsent v3](https://github.com/orestbida/cookieconsent) library. To build from source:

1. Install Node.js dependencies: `npm install`
2. Build the bundle: `npx webpack`
3. Watch for changes during development: `npx webpack --watch`

The webpack configuration is in `webpack.config.js`. Third-party library source: https://github.com/orestbida/cookieconsent

== Frequently Asked Questions ==

= How do I add custom cookie categories? =

Go to **Settings > Cookie Consent** and use the "Add New Category" section at the bottom of the page.

= Can I block specific scripts until consent is given? =

Yes, add a `data-category` attribute to your script tags (e.g. `data-category="analytics"`). Scripts with this attribute are managed by the cookie consent library based on user consent.

= Which cookies are blocked by default? =

The plugin pre-configures an analytics category covering Google Analytics cookies (`_ga`, `_gid`, `_gat`). You can add, remove, or modify cookie patterns in the admin settings.

= Is the plugin compatible with caching plugins? =

Yes. Settings are versioned via a timestamp that is appended to the script URL, so cached pages always load the correct configuration.

== Screenshots ==

1. Admin settings page
2. Cookie consent banner frontend view
3. Cookie category management interface

== Changelog ==

= 2.0.1 =
*2026-05-28*

* Fixed: necessary category enabled/readonly values were silently overwritten as false on every admin settings save because the disabled checkboxes were not submitted by form.serialize(). The necessary category is now always forced to enabled=true and readonly=true in validation, regardless of form input.
* Fixed: is_regex was always saved as true for every cookie. The hidden input used value="" for false, so PHP's isset() returned true for the empty string, silently marking non-regex cookies (_gid, _gat) as regex patterns and corrupting the autoClear cookie list. The hidden input now outputs '0' for false; validation uses !empty() with an explicit '0' check so only the string '1' is treated as true.
* Fixed: non-necessary categories (e.g. Analytics) appeared as locked and pre-selected in the frontend preferences modal. Validation now always saves enabled=false, readonly=false for non-necessary categories; src/index.js derives enabled/readOnly from the category id rather than DB values; admin UI replaces confusing enabled/readonly checkboxes with descriptive lock/unlock icons.
* Fixed: AJAX save no longer returns a misleading "No changes detected" message when a setting is toggled back to its current DB value — the response is always "Settings saved successfully."

= 2.0.0 =
*2026-05-28*

* Changed: plugin logic split from one monolithic file into five focused files under `inc/` — `defaults.php`, `settings.php`, `ajax.php`, `admin.php`, `frontend.php`. Main plugin file is now 26 lines (header, constants, requires). No behaviour changes.
* Fixed: admin page title renamed to "Warder Cookie Consent"; Settings sidebar label renamed to "Warder Consent"

= 1.5.2 =
*2026-05-28*

* Added: AJAX save for the Cookie Consent settings page — Save All Settings no longer reloads the page or scrolls back to the top; the success notice appears next to the form and the page scrolls it into view
* Fixed: setup welcome notice now self-suppresses once the plugin has been configured, instead of appearing on every admin page
* Fixed: Add Cookie forms are now rendered after the main settings form rather than nested inside it, so submitting an Add Cookie form no longer accidentally triggers the main settings save and no longer loses the regex checkbox state
* Fixed: success notice after adding/deleting categories or cookies (`?warder_notice=saved` after the redirect)
* Changed: removed leftover `:not([form])` selectors and JS DOM repositioning that were workarounds for the old nested-form layout

= 1.5.1 =
*2026-05-28*

* Fixed: Add Cookie form now appears directly below the "Add Cookie to this Category" button instead of at the bottom of the page (below Save All Settings and Add New Category)
* Fixed: Add Cookie submissions were silently dropped because the relocated form ended up nested inside the main settings form, which browsers reject. The actual form element now lives outside the main settings form and the visible inputs reference it via the HTML5 `form` attribute

= 1.5.0 =
*2026-05-28*

* Added: Matomo cookie patterns (`/^_pk_/` and `/^mtm_/`) to the default analytics category, so new installs manage Matomo cookies out of the box alongside Google Analytics. Existing sites can add the same patterns under Settings > Cookie Consent

= 1.4.2 =
*2026-05-28*

* Fixed: `register_setting()` updated to array format with explicit `sanitize_callback` key as required by WordPress.org guidelines
* Fixed: `privacy_policy_url` now sanitized with `esc_url_raw()` instead of `sanitize_text_field()` for proper URL sanitization
* Changed: `.distignore` updated to include `src/`, `webpack.config.js`, and `package.json` in the WordPress.org build so the human-readable source is available to reviewers (guideline §4)

= 1.4.1 =
*2026-05-27*

* Changed: Replaced the 10up/wpcs-action workflow with a local PHPCS workflow using phpcs.xml, adding enforcement of WordPress.WP.I18n (text domain: warder-cookie-consent) and WordPress.Security.EscapeOutput on pull requests

= 1.4.0 =
*2026-05-27*

* Fixed: the "Add New Category", "Add Cookie", and "Remove" cookie controls rendered but had no backend handler after an earlier refactor — they now work again
* Added: restored category/cookie management handlers (add category, add cookie, delete cookie, delete category) consolidated in `warder_handle_admin_actions()`, each with nonce verification and input sanitization
* Added: "Delete Category" link in each non-necessary category header
* Added: `Requires at least` and `Requires PHP` headers to the main plugin file
* Changed: wrapped all hardcoded admin settings-page strings in translation/escaping functions (`esc_html_e`, `esc_attr_e`, `esc_html__`, `esc_js`) so admin UI text is translatable and escaped on output

= 1.3.2 =
*2026-05-27*

* Fixed: moved inline admin `<script>` to `wp_add_inline_script()` via `admin_enqueue_scripts` hook
* Fixed: sanitize CSS output with `wp_strip_all_tags()` before passing to `wp_add_inline_style()`
* Added: `== Source Code & Build Process ==` section to readme.txt documenting webpack build and GitHub source link
* Changed: Contributors field updated to WordPress.org username `rhand`

= 1.3.1 =
*2026-05-26*

* Fixed .gitattributes so composer.json ships in Composer/Packagist dist archives (still excluded from WordPress.org builds via .distignore)

= 1.3.0 =
*2026-05-26*

* Added languages/ directory for translation files (WordPress 4.6+ auto-loads translations via the Text Domain header)
* Fixed Plugin Check workflow directory name to match text domain header (resolves textdomain_mismatch warnings)
* Renamed plugin to Warder Cookie Consent (Wheel of Time inspired, consistent with Elayne theme and Waygate pattern builder)
* Renamed main plugin file to warder-cookie-consent.php
* Updated text domain to warder-cookie-consent
* Updated all function prefixes from scc_ to warder_
* Updated Composer package name to imagewize/warder-cookie-consent
* GitHub repository renamed to imagewize/warder-cookie-consent

= 1.2.1 =
*2026-05-26*

* Fixed composer.json license field (MIT → GPL-2.0-or-later) to match plugin header
* Fixed composer.json support URLs pointing to wrong repository
* Added .gitattributes to exclude dev files from Composer installs and git archives

= 1.2.0 =
*2026-05-26*

* Added floating preferences toggle button — a cookie icon button rendered in the page footer that opens the preferences modal, letting users change their consent choices at any time
* Added "Preferences Toggle Button" setting in General Settings with a position dropdown (bottom-right, bottom-left, top-right, top-left)
* Toggle button can be enabled/disabled independently of the main banner

= 1.1.0 =
*2026-05-26*

* Added "Enable Plugin" toggle to General Settings (disable the banner without deactivating the plugin)
* Added plugin header fields required by WordPress.org (Author URI, Text Domain, License, License URI)
* Added direct file access protection (ABSPATH check)
* Removed debug error_log and console.log statements
* Fixed output escaping throughout admin UI (esc_url, esc_attr, esc_html)
* Fixed Plugin Check workflow directory name (resolved textdomain_mismatch and trademarked_term warnings)
* Added strict comparison to in_array calls
* Full WordPress Coding Standards compliance (PHPCS 0 errors)
* Added PHPDoc blocks to all functions
* Updated license from MIT to GPLv2 or later
* Added readme.txt, phpcs.xml, .distignore, and GitHub Actions workflows (WPCS, Plugin Check, release zip)

= 1.0.0 =
*2025-05-26*

* Initial release

== Upgrade Notice ==

= 2.0.1 =
Fixes three data-corruption bugs: (1) Strictly Necessary category losing its locked state after every save; (2) all cookies being silently saved as regex patterns due to a hidden-input value bug; (3) non-necessary categories appearing locked and pre-selected in the frontend consent modal.

= 2.0.0 =
Internal refactor only — plugin logic split into `inc/` files for maintainability. Admin page title and Settings sidebar label updated to "Warder Cookie Consent" / "Warder Consent". No settings migration required, no behaviour changes.

= 1.5.2 =
Save All Settings now uses AJAX, so the page no longer jumps back to the top after saving. Add Cookie forms are no longer nested inside the main settings form, so the regex checkbox and the rest of the cookie inputs submit reliably.

= 1.5.1 =
Fixes Add Cookie form positioning so it appears directly below the category button, and fixes a regression where submitting that form silently failed (nested form was being discarded by the browser).

= 1.5.0 =
Adds Matomo cookie patterns to the default analytics category so new installs manage Matomo cookies out of the box.

= 1.4.2 =
WordPress.org compliance fixes: register_setting() uses array format with sanitize_callback, privacy_policy_url uses esc_url_raw(), and src/ ships in the build for human-readable source access.

= 1.4.1 =
Replaces the 10up/wpcs-action CI workflow with local PHPCS, adding strict i18n (text domain) and output escaping checks on pull requests.

= 1.4.0 =
Restores the category/cookie management buttons (add/remove category and cookie) that were not working, with nonce-protected handlers. Adds minimum WordPress/PHP version headers.

= 1.3.2 =
WordPress.org compliance fixes: inline script moved to wp_add_inline_script, CSS output sanitized, source code documentation added to readme.

= 1.3.0 =
Plugin renamed to Warder Cookie Consent; added languages/ directory for translations; updated text domain, function prefixes, and Composer package name.

= 1.2.1 =
Fixes composer.json license and support URLs; adds .gitattributes for Composer installs.

= 1.2.0 =
Adds a floating preferences toggle button so visitors can revisit their cookie choices at any time.

= 1.1.0 =
Adds "Enable Plugin" toggle; WordPress.org compliance (PHPCS, Plugin Check, readme.txt, workflows).

= 1.0.0 =
Initial release.
