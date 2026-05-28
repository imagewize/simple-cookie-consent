# Warder Cookie Consent — Developer Documentation

Technical reference for how the plugin loads settings, blocks scripts, and manages cookies.

## Architecture Overview

```
WordPress DB (warder_options key)
  → warder_enqueue_scripts()   fetches and localizes
  → window.warderSettings      in browser
  → createConfigFromSettings() in src/index.js
  → CookieConsent.run(config)
```

### File Structure

```
warder-cookie-consent/
├── warder-cookie-consent.php       # Plugin header + WARDER_VERSION + WARDER_PLUGIN_FILE + require_once
├── inc/
│   ├── defaults.php                # warder_get_default_options(), warder_get_merged_options()
│   ├── settings.php                # warder_register_settings(), warder_validate_options(),
│   │                               #   warder_update_options_timestamp(), warder_plugin_activate()
│   ├── ajax.php                    # warder_ajax_save_settings(), warder_handle_admin_actions()
│   ├── admin.php                   # warder_add_options_page(), warder_enqueue_admin_scripts(),
│   │                               #   warder_render_options_page(), warder_admin_notices(),
│   │                               #   warder_render_category_title_field()
│   └── frontend.php                # warder_enqueue_scripts(), warder_get_preferences_toggle_css(),
│                                   #   warder_add_preferences_button()
├── assets/js/admin.js              # Admin UI: AJAX save, field highlight, add-cookie toggle
├── src/index.js                    # Frontend bundle entry (webpack input)
└── dist/cookieconsent.bundle.js    # Compiled bundle (webpack output)
```

## Settings Flow

### Storage

Settings are saved to `wp_options` under the key `warder_options`. All input passes through `warder_validate_options()` before writing.

### Transfer to JavaScript

`warder_enqueue_scripts()` (in `inc/frontend.php`) passes settings to the browser via `wp_localize_script`:

```php
wp_localize_script(
    'warder-cookieconsent',
    'warderSettings',
    array(
        'settings' => $options,
        'version'  => $version,
    )
);
```

### JavaScript Configuration

`createConfigFromSettings()` in `src/index.js` maps the flat `window.warderSettings.settings` object to vanilla-cookieconsent's nested config format:

```javascript
const wpSettings = window.warderSettings?.settings ?? {};

// Banner text
consentModal.title       = wpSettings.title;
consentModal.description = wpSettings.description;

// Categories — built dynamically from wpSettings.cookie_categories
```

## Script Blocking

Mark any script tag with `type="text/plain"` and `data-category` to gate it behind consent:

```html
<!-- Won't execute until the visitor accepts the "analytics" category -->
<script type="text/plain" data-category="analytics">
    // Google Analytics, Matomo, etc.
</script>
```

The `page_scripts: true` setting (enabled by default) activates this behaviour in vanilla-cookieconsent.

## Cookie Auto-Clearing

When consent is withdrawn, cookies matching patterns in that category are deleted automatically. Patterns are configured per-category in the admin UI (Settings > Warder Consent) and stored as:

```php
'cookies' => [
    [ 'name' => '/^_ga/', 'is_regex' => true  ],
    [ 'name' => '_gid',   'is_regex' => false ],
]
```

`is_regex` entries are converted to JavaScript `RegExp` objects in `createConfigFromSettings()`:

```javascript
if ( cookie.is_regex ) {
    const match = cookie.name.match( /^\/(.+)\/([gimsuy]*)$/ );
    name = match ? new RegExp( match[1], match[2] ) : cookie.name;
}
```

## Managing Cookie Categories

All category management is done through the admin UI at **Settings > Warder Consent**. No code changes needed.

- **Add a category** — enter a slug (e.g. `marketing`) in "Add New Category"
- **Add a cookie pattern** — click "Add Cookie to this Category" within any category
- **Delete a cookie or category** — use the Remove / Delete Category links

## Google Analytics Example

```html
<script type="text/plain" data-category="analytics">
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-XXXXXXXXXX');
</script>
<script type="text/plain" data-category="analytics"
    src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX" async>
</script>
```

Pre-configured analytics cookie patterns cover Google Analytics (`/^_ga/`, `_gid`, `_gat`) and Matomo (`/^_pk_/`, `/^mtm_/`) out of the box.

## Event Hooks

vanilla-cookieconsent fires callbacks you can use in `src/index.js`:

```javascript
CookieConsent.run({
    // ...config,
    onConsent: ({ cookie }) => {
        if ( CookieConsent.acceptedCategory('analytics') ) {
            // analytics accepted
        }
    },
    onChange: ({ changedCategories }) => {
        // react to consent changes
    },
});
```

## Admin AJAX Save

Settings save without a page reload via `wp_ajax_warder_save_settings`. The handler lives in `inc/ajax.php`; the JS intercept in `assets/js/admin.js`. The nonce used is `warder_options_group-options` (generated by `settings_fields()`).

## Cache Busting

`warder_update_options_timestamp()` writes `time()` to `warder_options_last_updated` on every option save. `warder_enqueue_scripts()` uses this value as the script version, so browser caches are invalidated automatically when settings change.

## Troubleshooting

**Scripts still loading despite no consent**
- Confirm `type="text/plain"` is present on the script tag
- Confirm `data-category` matches the category slug exactly (e.g. `analytics`, not `Analytics`)
- Confirm "Page Scripts" is enabled in Settings > Warder Consent

**Cookies not clearing**
- Confirm "Auto-clear Cookies" is enabled
- Confirm the cookie pattern is correct — test regex patterns at regex101.com
- Cookie deletion requires the cookie to share the same domain and path

**Settings not reflected in the banner**
- Open the browser console and inspect `window.warderSettings.settings`
- Hard-refresh (Cmd+Shift+R) to bypass the browser cache
