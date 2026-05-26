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
composer require imagewize/simple-cookie-consent
```

## Architecture

### Data Flow

```
WordPress DB (scc_options key)
  → scc_enqueue_scripts() fetches and localizes
  → window.sccSettings in browser
  → createConfigFromSettings() in src/index.js
  → CookieConsent.run(config)
```

### PHP Layer (`simple-cookie-consent.php`)

All plugin logic is in this single file (~552 lines):

- **`scc_get_default_options()`** — defines the canonical default settings structure
- **`scc_get_merged_options()`** — retrieves DB options and deep-merges with defaults; always returns a complete settings object
- **`scc_validate_options()`** — sanitizes/validates before saving to `scc_options` in `wp_options`
- **`scc_enqueue_scripts()`** — enqueues `dist/cookieconsent.bundle.js` and localizes it as `window.sccSettings`
- **`scc_settings_page()`** — renders the admin UI at Settings > Cookie Consent
- Settings are versioned via `scc_options_last_updated` timestamp for cache busting

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
scc_options = [
  'general'    => [ position, language, ... ],
  'texts'      => [ banner title/description, accept/reject button labels, ... ],
  'categories' => [
    'necessary' => [ enabled, readonly, cookies[] ],
    'analytics' => [ enabled, cookies[] ],
  ],
]
```

Each `cookies` entry supports `name` (exact string or `/regex/` pattern) and `domain`.

## Git Commits

Do not mention "Claude" or "Claude Code" in commit messages.
