# Warder Cookie Consent - Architecture Plan

## Current State (v1.5.1)

### File Structure

```
warder-cookie-consent/
├── warder-cookie-consent.php   # All plugin logic (~851 lines)
├── src/index.js                # JS entry point (webpack input)
├── dist/cookieconsent.bundle.js# Compiled bundle (webpack output)
├── webpack.config.js
├── languages/
├── docs/
└── ...config files
```

### What Is Already Implemented

- Settings saved inline notice (`$_GET['settings-updated']`) inside `warder_render_options_page()`
- Yellow background highlight on changed fields (inline jQuery via `wp_add_inline_script`)
- Show/hide add-cookie form (inline jQuery)
- Activation-time merge of defaults (`warder_plugin_activate`)
- Cache busting via `warder_options_last_updated` timestamp on option update
- Welcome notice on other admin pages (`warder_admin_notices` via `admin_notices` hook)
- Cookie category/cookie add and delete via `warder_handle_admin_actions()`

### Known Issues

#### 1. Save UX — Page Jumps to Top
- **Cause**: form POSTs to `options.php`, which redirects back with `?settings-updated=true`, resetting scroll position
- **Impact**: disorienting on a long settings page; user loses context
- **Current partial fix**: inline success notice at top of page content — visible but requires scrolling back

#### 2. Monolithic File
- **Issue**: all logic in one 851-line file
- **Impact**: harder to navigate and maintain as the plugin grows
- **Not urgent** at current size, but worth planning for v2.0.0

---

## Phase 1: Fix Save UX (v1.6.0)

### Recommended Approach: AJAX Save

Replace the standard `options.php` form submission with an AJAX handler. This eliminates the page reload entirely, keeps the user at their scroll position, and gives immediate visual feedback.

**Why not the scroll-position-via-POST hack?**
Storing scroll offset in a hidden POST field and restoring it via JS after redirect is fragile, non-standard, and still flickers. AJAX solves the root cause.

**Why not stay with WordPress Settings API redirect?**
The Settings API redirect to `options.php` is what causes the jump. Intercepting the submit and using `wp_ajax_` directly is cleaner and widely used in modern WP plugins.

### PHP: AJAX Handler

Add to `warder-cookie-consent.php`:

```php
add_action( 'wp_ajax_warder_save_settings', 'warder_ajax_save_settings' );

function warder_ajax_save_settings() {
    check_ajax_referer( 'warder_options_group-options', '_wpnonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'warder-cookie-consent' ) ) );
    }

    $input = isset( $_POST['warder_options'] ) ? $_POST['warder_options'] : array();
    $valid = warder_validate_options( $input );

    if ( update_option( 'warder_options', $valid ) ) {
        delete_transient( 'warder_options_cache' );
        wp_send_json_success( array(
            'message' => __( 'Settings saved successfully.', 'warder-cookie-consent' ),
        ) );
    } else {
        wp_send_json_success( array(
            'message' => __( 'No changes detected.', 'warder-cookie-consent' ),
        ) );
    }
}
```

### JS: Intercept Submit

Replace the current inline `$admin_js` string with an enqueued file (see Phase 2), or extend it inline for now:

```javascript
jQuery( document ).ready( function( $ ) {
    $( '#warder-main-settings-form' ).on( 'submit', function( e ) {
        e.preventDefault();

        var form      = $( this );
        var submitBtn = form.find( 'input[type="submit"]' );
        var nonce     = $( '#_wpnonce' ).val();

        submitBtn.prop( 'disabled', true ).val( warderAdmin.saving );

        $.post( ajaxurl, form.serialize() + '&action=warder_save_settings', function( response ) {
            $( '.warder-ajax-notice' ).remove();
            var cls = response.success ? 'notice-success' : 'notice-error';
            form.before(
                '<div class="notice ' + cls + ' is-dismissible warder-ajax-notice">' +
                '<p><strong>' + response.data.message + '</strong></p></div>'
            );
            if ( response.success ) {
                form.find( 'input, textarea, select' ).css( 'background-color', '' );
            }
        } ).always( function() {
            submitBtn.prop( 'disabled', false ).val( warderAdmin.save );
        } );
    } );
} );
```

Use `wp_localize_script` to pass translated strings (`warderAdmin.save`, `warderAdmin.saving`) rather than hardcoding them in JS.

### Fallback (no-JS)
Keep the existing `options.php` form action and `settings_fields()` call intact. The AJAX handler intercepts via `e.preventDefault()` — if JS is unavailable, the form submits normally and the existing redirect notice still works.

### Changes Required in `warder_enqueue_admin_scripts()`
- Add `wp_localize_script()` call for `warderAdmin` strings after enqueueing jQuery
- Extend `$admin_js` to include the submit intercept, or extract to a file (preferred)

---

## Phase 2: Extract Admin JS to a File (v1.6.0 or v2.0.0)

Currently all admin JS is a PHP heredoc string passed to `wp_add_inline_script`. This is hard to edit, lint, or test.

### Change
1. Create `assets/js/admin.js`
2. In `warder_enqueue_admin_scripts()`, replace `wp_add_inline_script` with:
   ```php
   wp_enqueue_script(
       'warder-admin',
       plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
       array( 'jquery' ),
       WARDER_VERSION,
       true
   );
   wp_localize_script( 'warder-admin', 'warderAdmin', array(
       'save'   => __( 'Save Settings', 'warder-cookie-consent' ),
       'saving' => __( 'Saving…', 'warder-cookie-consent' ),
   ) );
   ```
3. Move all inline JS (show/hide form, field highlight, AJAX submit) into `assets/js/admin.js`

No webpack needed — this is plain jQuery, not a module.

---

## Phase 3: File Structure Refactoring (v2.0.0)

Only warranted if the main PHP file grows significantly beyond its current 851 lines or if the codebase gains contributors. Premature splitting adds navigation overhead.

### Proposed Structure (when needed)

```
warder-cookie-consent/
├── warder-cookie-consent.php       # Plugin header + require loader only
├── inc/
│   ├── settings.php                # warder_register_settings(), warder_validate_options()
│   ├── admin.php                   # warder_render_options_page(), warder_enqueue_admin_scripts()
│   ├── ajax.php                    # warder_ajax_save_settings(), warder_handle_admin_actions()
│   ├── frontend.php                # warder_enqueue_scripts(), warder_add_preferences_button()
│   └── defaults.php                # warder_get_default_options(), warder_get_merged_options()
├── assets/
│   └── js/
│       └── admin.js
├── src/
│   └── index.js                    # Frontend bundle entry (webpack)
├── dist/
│   └── cookieconsent.bundle.js
└── ...
```

### Migration Steps
1. Create `inc/` directory
2. Move function groups one file at a time, verifying nothing breaks after each move
3. Add `require_once` calls in the main file
4. No class-based refactor needed — procedural functions with `warder_` prefix work fine at this scale

---

## WordPress.org Plugin Guidelines Compliance

Relevant rules from the [Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) and how they apply:

### Guideline 4 — No Obfuscation
Webpack minification of `dist/cookieconsent.bundle.js` is fine. Variable name mangling (uglify `mangle` option) is **not** permitted. The webpack config must keep readable variable names in the bundle, and `src/index.js` must remain in the repo as the human-readable source.

### Guideline 11 — Admin Notices Must Be Dismissible
- The AJAX save notice (Phase 1) must include the `is-dismissible` CSS class — already in the proposed markup.
- The existing welcome/setup notice in `warder_admin_notices()` must be dismissible. Currently it is (`is-dismissible`), but it shows on **every** admin page until settings are configured. It should self-suppress once the plugin has been configured (e.g. check `get_option('warder_options_last_updated')`).
- Upgrade prompts or upsell messaging must never appear site-wide — only on the plugin's own settings page.

### Guideline 13 — Use WordPress Bundled Libraries
- jQuery is bundled with WordPress; always enqueue via `wp_enqueue_script( 'jquery' )`, never bundle or load a separate copy.
- `vanilla-cookieconsent` is not bundled with WordPress, so including it via webpack is correct. It uses the MIT licence, which is GPL-compatible (Guideline 1).
- Do not load any assets from third-party CDNs (Guideline 8). All JS and CSS must be self-hosted in the plugin.

### Guideline 7 — No User Tracking Without Consent
The AJAX save handler (`warder_ajax_save_settings`) must not transmit any data externally. Settings are saved only to `wp_options` — this is compliant. If any future feature sends data off-site (e.g. telemetry), it requires an explicit opt-in checkbox.

### Guideline 8 — No Third-Party Code Execution
The plugin must not make external HTTP requests during normal operation. The AJAX handler calls only `update_option()` and `wp_send_json_*()` — fully local. If external requests are ever added (e.g. licence checks), they require disclosure and must be genuinely functional (not solely for licence enforcement).

---

## Security Checklist (All Changes)

- Verify nonce on every AJAX handler: `check_ajax_referer()`
- Check capabilities: `current_user_can( 'manage_options' )`
- Sanitize all `$_POST` input via `warder_validate_options()` before saving
- Escape all output with `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`
- Never trust `$_GET['settings-updated']` for security decisions (it is UI-only)
- No external HTTP requests in AJAX handlers (Guideline 8)

---

## Testing Checklist

- [ ] Settings save without page jump (AJAX path)
- [ ] Success/error notice appears next to form, includes `is-dismissible` class (Guideline 11)
- [ ] Changed-field highlight resets on successful save
- [ ] Fallback: settings save normally when JS is disabled
- [ ] Welcome notice suppresses itself after plugin has been configured (Guideline 11)
- [ ] Add/delete category and cookie still work (not affected by AJAX save change)
- [ ] All existing options survive a round-trip through `warder_validate_options()`
- [ ] No external HTTP requests fired during save (Guideline 8)
- [ ] Works on WordPress multisite
- [ ] Compatible with PHP 8.0+

---

## WordPress Coding Standards

All changes must follow:
- [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/js/)
- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [WordPress.org Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

Lint PHP with `vendor/bin/phpcs --standard=phpcs.xml warder-cookie-consent.php` before committing.
