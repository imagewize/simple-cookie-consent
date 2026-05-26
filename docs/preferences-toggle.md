# Adding a Preferences Toggle Button

## Current State

The Simple Cookie Consent plugin currently displays a cookie consent banner on first visit, but does not provide a persistent way for users to change their preferences after making an initial selection. Many GDPR compliance guidelines recommend providing users with an easy way to revisit and modify their cookie preferences.

## Solution Overview

The vanilla-cookieconsent library (v3) that powers this plugin includes built-in functionality to show a preferences modal at any time. There are two approaches to implement this:

### Approach 1: Via WordPress Settings (Recommended)

> **Note:** vanilla-cookieconsent v3 does **not** have a built-in floating toggle button via `guiOptions`. There is no `guiOptions.preferencesToggle` property in the library — use Approach 2 for a floating button instead.

The recommended path for a toggle tied to the plugin's settings system is to expose it as an admin option and render it server-side (see Approach 2 for the actual rendering), wired through the existing settings infrastructure:

```php
// In simple-cookie-consent.php, add to default options:
function scc_get_default_options() {
    return array(
        // ... existing options
        'show_preferences_toggle' => true,
        'preferences_toggle_position' => 'bottom right',
    );
}
```

Then pass `show_preferences_toggle` to the frontend via `scc_enqueue_scripts()` and conditionally render the button (see Approach 2).

### Approach 2: Custom Footer Button with SVG Icon

For more control over styling and positioning, create a custom HTML element that triggers the preferences modal.

#### Implementation Steps

1. **Add HTML to footer** - In your theme's `footer.php` or via a WordPress hook:

```php
// In simple-cookie-consent.php, add a new function:
function scc_add_preferences_button() {
    $options = scc_get_merged_options();
    if ( empty( $options['enabled'] ) ) {
        return;
    }
    
    echo '<button id="scc-preferences-toggle" class="scc-preferences-toggle" aria-label="Cookie Preferences">';
    echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
    echo '<circle cx="12" cy="12" r="10"/>';
    echo '<circle cx="9" cy="9" r="1.5" fill="currentColor"/>';
    echo '<circle cx="15" cy="8" r="1" fill="currentColor"/>';
    echo '<circle cx="14" cy="14" r="1.5" fill="currentColor"/>';
    echo '<circle cx="9" cy="15" r="1" fill="currentColor"/>';
    echo '</svg>';
    echo '</button>';
}
add_action( 'wp_footer', 'scc_add_preferences_button' );
```

2. **Add CSS** - In your theme or via `wp_add_inline_style`:

```css
.scc-preferences-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #333;
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    z-index: 9999;
    transition: all 0.3s ease;
}

.scc-preferences-toggle:hover {
    background: #555;
    transform: scale(1.1);
}

.scc-preferences-toggle svg {
    width: 24px;
    height: 24px;
}
```

3. **Add JavaScript** - Update `src/index.js` to handle the button click:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    try {
        const config = typeof window.sccSettings !== 'undefined'
            ? createConfigFromSettings(defaultConfig, window.sccSettings)
            : defaultConfig;
        
        // Initialize cookie consent (run() does not return a usable instance; use the static API)
        CookieConsent.run(config);
        
        // Add click handler for custom preferences button
        const prefButton = document.getElementById('scc-preferences-toggle');
        if (prefButton) {
            prefButton.addEventListener('click', function() {
                CookieConsent.showPreferences();
            });
        }
        
    } catch (error) {
        console.error('Error initializing cookie consent:', error);
    }
});
```

### Approach 3: Using the Library's API Directly

The vanilla-cookieconsent library exposes these API methods:

- `CookieConsent.show()` - Shows the consent modal
- `CookieConsent.showPreferences()` - Shows the preferences modal
- `CookieConsent.hide()` - Hides all modals
- `CookieConsent.acceptAll()` - Accepts all cookies
- `CookieConsent.acceptNecessary()` - Accepts only necessary cookies

You can call these from any JavaScript code on your site.

## Styling Options

### Positioning the Toggle

The floating button can be positioned in any corner:
- `bottom right` (recommended - standard location)
- `bottom left`
- `top right`
- `top left`

### Icon Options

Use any SVG for the cookie icon. Recommended options:

**Cookie Bite Icon:**
```svg
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
    <path d="M12 2v10M12 12v10M2 12h10M12 12h10"/>
    <circle cx="12" cy="12" r="3"/>
</svg>
```

**Cookie Icon (Simple):**
```svg
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <circle cx="12" cy="12" r="10"/>
    <circle cx="12" cy="12" r="3"/>
</svg>
```

**Settings Gear Icon:**
```svg
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <circle cx="12" cy="12" r="3"/>
    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
</svg>
```

## Accessibility Considerations

- Add `aria-label` attribute to the button
- Ensure proper color contrast
- Button should be keyboard navigable
- Consider adding a text label for screen readers

## Testing

After implementation, test:
1. Initial banner appears correctly
2. After accepting/rejecting, the preferences button is visible
3. Clicking the button opens the preferences modal
4. Changes made in preferences are saved correctly
5. Button works on all page types (posts, pages, custom post types)
6. Button is responsive on mobile devices

## References

- [vanilla-cookieconsent v3 Documentation](https://cookieconsent.orestbida.com/)
- [GitHub Repository](https://github.com/orestbida/cookieconsent)
- [Playground Demo](https://playground.cookieconsent.orestbida.com/)
