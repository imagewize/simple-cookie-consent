# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

A WordPress plugin wrapping the [vanilla-cookieconsent](https://github.com/orestbida/cookieconsent) v3 library. It exposes a settings page in the WordPress admin and passes those settings via `wp_localize_script` into JavaScript, which then configures and runs the cookie consent banner.

## Build Commands

```bash
npm install          # Install JS dependencies
npx webpack          # Build dist/cookieconsent.bundle.js from src/index.js
npx webpack --watch  # Rebuild on file change
```

There are no tests. PHP requires 8.0+.

To install as a Composer dependency:
```bash
composer require imagewize/warder-cookie-consent
```

## Architecture

### Data Flow

```
WordPress DB (warder_options key)
  → warder_enqueue_scripts() fetches and localizes
  → window.sccSettings in browser
  → createConfigFromSettings() in src/index.js
  → CookieConsent.run(config)
```

### PHP Layer (`warder-cookie-consent.php`)

All plugin logic is in this single file (~851 lines):

- **`warder_get_default_options()`** — defines the canonical default settings structure
- **`warder_get_merged_options()`** — retrieves DB options and deep-merges with defaults; always returns a complete settings object
- **`warder_validate_options()`** — sanitizes/validates before saving to `warder_options` in `wp_options`
- **`warder_enqueue_scripts()`** — enqueues `dist/cookieconsent.bundle.js` and localizes it as `window.sccSettings`
- **`warder_render_options_page()`** — renders the admin UI at Settings > Cookie Consent
- Settings are versioned via `warder_options_last_updated` timestamp for cache busting

### JS Layer (`src/index.js` → `dist/cookieconsent.bundle.js`)

- Imports vanilla-cookieconsent and its CSS (bundled via webpack style-loader)
- `createConfigFromSettings()` maps the flat `window.sccSettings` structure to vanilla-cookieconsent's nested config format
- Handles regex pattern conversion for cookie matching rules
- Two default categories: `necessary` (always on) and `analytics` (optional)
- Supports six languages: en, fr, de, es, it, nl

### Cookie Blocking

Scripts are blocked until consent is given via `data-cookiecategory` HTML attributes on `<script>` tags. The plugin does not inject any blocking logic itself — that is handled by vanilla-cookieconsent based on the category configuration.

### Settings Structure

```php
warder_options = [
  'enabled'                    => bool,
  'current_lang'               => string,
  'autoclear_cookies'          => bool,
  'page_scripts'               => bool,
  'show_preferences_toggle'    => bool,
  'preferences_toggle_position'=> string,
  'title'                      => string,
  'description'                => string,
  'primary_btn_text'           => string,
  'primary_btn_role'           => string,
  'secondary_btn_text'         => string,
  'secondary_btn_role'         => string,
  'privacy_policy_url'         => string,
  'cookie_categories'          => [
    'necessary' => [ title, description, enabled, readonly, cookies[] ],
    'analytics' => [ title, description, enabled, cookies[] ],
    ...  // user-defined categories
  ],
]
```

Each `cookies` entry supports `name` (exact string or `/regex/` pattern) and `domain`.

## Versioning

The `Version:` header in `warder-cookie-consent.php` is the canonical version (this is what WordPress.org reads). When bumping the version, update all of these together:

- `warder-cookie-consent.php` — `Version:` header and `WARDER_VERSION` constant
- `readme.txt` — `Stable tag:` plus new `== Changelog ==` and `== Upgrade Notice ==` entries
- `CHANGELOG.md` — new version heading
- `package.json` — `version` field (kept in sync even though this package is not published to npm)

`composer.json` intentionally has **no** `version` field — Packagist derives versions from git tags, so do not add one.

## Git Commits

Do not mention "Claude" or "Claude Code" in commit messages.
