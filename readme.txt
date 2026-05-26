=== Simple Cookie Consent ===
Contributors: jasperfrumau
Donate link: https://imagewize.com
Tags: cookie, consent, gdpr, privacy, compliance
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 1.2.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight plugin that implements GDPR-compliant cookie consent functionality using the vanilla-cookieconsent library.

== Description ==

Simple Cookie Consent provides an easy way to add GDPR-compliant cookie consent banners to your WordPress website. The plugin uses the lightweight [CookieConsent v3](https://github.com/orestbida/cookieconsent) library and offers full customization through the WordPress admin interface.

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

= 1.2.0 =
Adds a floating preferences toggle button so visitors can revisit their cookie choices at any time.

= 1.1.0 =
Adds "Enable Plugin" toggle; WordPress.org compliance (PHPCS, Plugin Check, readme.txt, workflows).

= 1.0.0 =
Initial release.
