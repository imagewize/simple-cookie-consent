# Simple Cookie Consent - Developer Documentation

This document provides technical details about how the Simple Cookie Consent plugin works, focusing on how it loads settings from the WordPress admin and blocks cookies until consent is given.

## Architecture Overview

The plugin consists of three main components:

1. **PHP Admin Interface**: Handles settings storage and retrieval in WordPress
2. **JavaScript Configuration**: Configures the vanilla-cookieconsent library
3. **Cookie Consent Banner**: User-facing interface for managing consent

## How Settings are Loaded from the Options Page

### Settings Storage

When a user saves settings in the WordPress admin:

1. Settings are stored in WordPress options table under the `scc_options` key
2. The plugin validates all inputs using `scc_validate_options()`
3. Default values are provided by `scc_get_default_options()` if needed

### Settings Transfer to JavaScript

When a page loads:

1. `scc_enqueue_scripts()` function loads the bundled JavaScript
2. The same function retrieves settings with `get_option('scc_options')`
3. `wp_localize_script()` passes these settings to JavaScript as `sccSettings.settings`

```php
wp_localize_script('scc-cookieconsent', 'sccSettings', array(
    'settings' => $options,
    'version' => time() 
));
```

### JavaScript Configuration

In the `src/index.js` file:

1. Default configuration is defined in the `config` object
2. WordPress settings are loaded from `window.sccSettings`
3. The configuration is updated with user preferences:

```javascript
// Example of how settings are merged
if (typeof window.sccSettings !== 'undefined') {
    const wpSettings = window.sccSettings.settings;
    
    // Update translations with WordPress settings
    if (config.language.translations[config.language.default]) {
        config.language.translations[config.language.default].consentModal.title = 
            wpSettings.title || config.language.translations[config.language.default].consentModal.title;
        
        // Additional settings mapping...
    }
}
```

## Cookie Blocking Mechanism

The plugin uses vanilla-cookieconsent's script blocking capabilities to prevent cookies from being set until consent is given.

### How Script Blocking Works

1. Scripts are marked with `data-cookiecategory` attributes to associate them with consent categories
2. The cookie consent library prevents these scripts from executing until the user gives consent for that category
3. The `page_scripts: true` setting enables this functionality

### Example Script Blocking

```html
<!-- This script won't run until 'analytics' consent is given -->
<script type="text/plain" data-cookiecategory="analytics">
    // Google Analytics or other tracking code
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
    
    ga('create', 'UA-XXXXX-Y', 'auto');
    ga('send', 'pageview');
</script>
```

### Cookie Auto-Clearing

When a user withdraws consent, related cookies can be automatically deleted:

1. The `autoclear_cookies` setting enables this feature
2. Specific cookies to clear are defined in the configuration:

```javascript
analytics: {
    enabled: false,
    readOnly: false,
    autoClear: {
        cookies: [
            {
                name: /^_ga/,  // Regular expression to match cookies starting with _ga
            },
            {
                name: '_gid',  // Exact match for _gid cookie
            }
        ]
    }
}
```

## Google Analytics Implementation Example

Here's how to implement Google Analytics with proper consent management:

1. Add your Google Analytics code to your site with the appropriate data attribute:

```html
<script type="text/plain" data-cookiecategory="analytics">
    // Google Analytics code (GA4)
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtag/js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','G-XXXXXXXXXX');
    
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-XXXXXXXXXX');
</script>
```

2. Ensure the analytics category is configured in the cookie consent setup:

```javascript
analytics: {
    enabled: false,  // Disabled by default, requiring explicit consent
    readOnly: false, // Can be toggled by the user
    autoClear: {
        cookies: [
            { name: /^_ga/ },
            { name: '_gid' },
            { name: '_gat' },
            { name: '_ga_.*' } // GA4 cookies
        ]
    }
}
```

## Advanced Usage

### Adding New Cookie Categories

To add a new cookie category (e.g., "marketing"):

1. Add the category to the configuration object in `src/index.js`:

```javascript
categories: {
    // ...existing categories...
    marketing: {
        enabled: false,
        readOnly: false,
        autoClear: {
            cookies: [
                { name: /^mk_/ },
                { name: 'marketing_session' }
            ]
        }
    }
}
```

2. Add the UI elements for this category:

```javascript
sections: [
    // ...existing sections...
    {
        title: 'Marketing Cookies',
        description: 'These cookies are used to track visitors across websites to display relevant advertisements.',
        linkedCategory: 'marketing'
    }
]
```

3. Mark scripts with the new category:

```html
<script type="text/plain" data-cookiecategory="marketing">
    // Marketing scripts here
</script>
```

### Event Handling

The cookie consent library provides several events you can hook into:

```javascript
onConsent: ({cookie}) => {
    console.log('Consent given:', cookie);
    // Perform actions based on consent
},

onChange: ({changedCategories, changedServices}) => {
    console.log('Consent changed for:', changedCategories);
    // React to consent changes
}
```

## Troubleshooting

Common issues and their solutions:

### Scripts Still Loading Despite No Consent

Check that:
- The script has the correct `type="text/plain"` attribute
- The `data-cookiecategory` value matches exactly with your category name
- The `page_scripts` setting is enabled

### Cookies Not Being Cleared

Check that:
- The `autoclear_cookies` setting is enabled
- Cookie names are correctly specified in the configuration
- The browser supports cookie deletion (some browsers restrict this)

### Configuration Not Updating

If your WordPress settings aren't reflected in the cookie banner:
- Check the browser console for JavaScript errors
- Verify that `sccSettings` is properly loaded by adding a debug statement
- Clear browser cache or add a version parameter to prevent caching

## Using with Tag Managers

If you're using Google Tag Manager or similar services:

1. Load the tag manager script with appropriate consent category:

```html
<script type="text/plain" data-cookiecategory="analytics">
    // Google Tag Manager
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-XXXXXXX');
</script>
```

2. Set up consent mode in your tag manager to respect the cookie consent choices
