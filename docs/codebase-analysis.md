# Warder Cookie Consent - Codebase Analysis & Recommendations

*Generated: 2026-05-28*  
*Status: Comprehensive Technical Review*  

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Architecture Overview](#architecture-overview)
3. [Cookie Consent Flow Analysis](#cookie-consent-flow-analysis)
4. [Critical Issues Found](#critical-issues-found)
5. [Medium Priority Issues](#medium-priority-issues)
6. [Low Priority Issues](#low-priority-issues)
7. [Security Analysis](#security-analysis)
8. [Performance Analysis](#performance-analysis)
9. [Code Quality Analysis](#code-quality-analysis)
10. [Testing Recommendations](#testing-recommendations)
11. [PHP Unit Test Suggestions](#php-unit-test-suggestions)
12. [JavaScript Test Suggestions](#javascript-test-suggestions)
13. [Feature Enhancement Suggestions](#feature-enhancement-suggestions)
14. [Implementation Roadmap](#implementation-roadmap)

---

## Executive Summary

The Warder Cookie Consent plugin is a well-structured WordPress plugin that integrates the vanilla-cookieconsent library to provide GDPR-compliant cookie management. The codebase has recently undergone significant refactoring (v2.0.0) with improved modularization and bug fixes.

**Overall Assessment: B+ (Good, with room for improvement)**

- ✅ **Strengths**: Clean architecture, proper WordPress standards, good separation of concerns, active development
- ⚠️ **Concerns**: Missing test coverage, potential cookie blocking edge cases, configuration validation gaps
- 🔧 **Critical**: Issues with cookie blocking for scripts without proper attributes

---

## Architecture Overview

```
Warder Cookie Consent Architecture
├── Plugin Entry Point
│   └── warder-cookie-consent.php (26 lines - excellent!)
│       ├── Defines constants (WARDER_VERSION, WARDER_PLUGIN_FILE)
│       └── Requires 5 inc/* files
├── inc/
│   ├── defaults.php      - Default options & merged options retrieval
│   ├── settings.php      - Settings registration, validation, activation
│   ├── ajax.php          - AJAX handlers for admin operations
│   ├── admin.php         - Admin page rendering & UI
│   └── frontend.php      - Frontend script enqueueing & toggle button
├── src/
│   └── index.js          - Frontend bundle entry (Webpack input)
├── dist/
│   └── cookieconsent.bundle.js - Compiled bundle (Webpack output)
├── assets/js/
│   └── admin.js          - Admin UI JavaScript
└── vendor/              - Composer dependencies
```

### Data Flow

```
WordPress DB (wp_options:warder_options)
    ↓ warder_get_merged_options()
    ↓ warder_enqueue_scripts()
    ↓ wp_localize_script('warderSettings')
    ↓ Browser: window.warderSettings
    ↓ createConfigFromSettings() in src/index.js
    ↓ CookieConsent.run(config)
    ↓ User interacts with consent modal
    ↓ Cookies set/cleared based on consent
```

---

## Cookie Consent Flow Analysis

### How Cookies Are Currently Handled

#### 1. **Necessary Cookies (✅ Working Correctly)**
- **Configuration**: `inc/defaults.php:40-46` defines `necessary` category with `enabled: true` and `readonly: true`
- **Validation**: `inc/settings.php:56-57` enforces `enabled: true, readonly: true` regardless of form input
- **Frontend**: `src/index.js:139-140` sets `config.categories[categoryId].enabled = (categoryId === 'necessary')`
- **Result**: Necessary cookies are **always allowed** and cannot be disabled by users ✓

#### 2. **Other Categories (⚠️ Needs Verification)**
- **Configuration**: Non-necessary categories have `enabled: false` by default
- **Validation**: `inc/settings.php:64` enforces `enabled: false` for non-necessary categories
- **Frontend**: Categories start disabled, users must opt-in
- **Issue**: No server-side validation that non-necessary categories stay disabled by default

#### 3. **Cookie Blocking Mechanism**

**Current Implementation:**
```javascript
// Scripts with type="text/plain" and data-category attribute are blocked
<script type="text/plain" data-category="analytics">
    // This won't execute until analytics category is accepted
</script>
```

**How it works:**
1. vanilla-cookieconsent library scans for scripts with `type="text/plain"` and `data-category`
2. When consent is given for a category, it changes `type="text/plain"` to `type="text/javascript"`
3. Browser then executes the script

**✅ Verified Working**: The `page_scripts: true` option (default) activates this behavior

#### 4. **Cookie Auto-Clearing**

**Current Implementation:**
```javascript
// src/index.js:143-157
if (category.cookies && category.cookies.length > 0) {
    config.categories[categoryId].autoClear = {
        cookies: category.cookies.map(cookie => {
            if (cookie.is_regex && cookie.name.startsWith('/') && cookie.name.includes('/')) {
                const pattern = cookie.name.slice(1, cookie.name.lastIndexOf('/'));
                return { name: new RegExp(pattern) };
            } else {
                return { name: cookie.name };
            }
        })
    };
}
```

**✅ Working**: When consent is withdrawn, cookies matching patterns are automatically cleared

---

## Critical Issues Found

### 🔴 CRITICAL: Cookie Blocking Bypass Vulnerabilities

#### Issue 1: Scripts Without Proper Attributes Are Not Blocked

**Problem**: Scripts that don't use `type="text/plain" data-category="..."` will execute regardless of consent state.

**Impact**:
- Google Analytics, Matomo, or other tracking scripts added via WordPress plugins/themes without the proper attributes will **always load**
- This defeats the entire purpose of the cookie consent system

**Example of BROKEN usage:**
```html
<!-- This WILL execute even if analytics is rejected -->
<script src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXX"></script>
```

**Example of CORRECT usage:**
```html
<!-- This will be blocked until analytics is accepted -->
<script type="text/plain" data-category="analytics"
    src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXX"></script>
```

**Recommendation:**
1. Add server-side script filtering to wrap/block scripts based on consent
2. Create a WordPress filter to automatically add `data-category` attributes to known tracking scripts
3. Document this limitation prominently in admin UI

#### Issue 2: Inline Scripts Without Type Attribute

**Problem**: Inline scripts without `type="text/plain"` cannot be blocked by vanilla-cookieconsent.

**Example:**
```html
<script>
    // This executes immediately, cannot be blocked
    gtag('config', 'G-XXXXXX');
</script>
```

**Recommendation:**
- Add documentation warning about inline scripts
- Consider implementing a server-side output buffer filter to catch and modify inline scripts

### 🔴 CRITICAL: Cookie Pattern Matching Issues

#### Issue 3: Regex Pattern Validation Missing

**Problem**: Invalid regex patterns in cookie configurations can cause JavaScript errors.

**Location**: `src/index.js:148-151`
```javascript
try {
    const pattern = cookie.name.slice(1, cookie.name.lastIndexOf('/'));
    return { name: new RegExp(pattern) };
} catch (e) {
    console.error('Invalid regex pattern:', cookie.name);
    return { name: cookie.name }; // Falls back to literal match
}
```

**Current behavior**: Invalid regex silently falls back to literal string matching (good for not breaking the site, but bad for debugging)

**Recommendation**:
- Add regex validation in PHP (`inc/settings.php`) before saving
- Display validation errors in admin UI
- Log invalid patterns for administrator review

#### Issue 4: Path and Domain Mismatch for Cookie Clearing

**Problem**: Cookies can only be cleared if they share the same domain and path as when they were set.

**Current Implementation**: vanilla-cookieconsent clears cookies with default path `/` and current domain.

**Impact**: Cookies set with custom paths or domains (e.g., `.example.com` vs `www.example.com`) may not be cleared properly.

**Recommendation**:
- Add path and domain configuration options for each cookie pattern
- Document this limitation
- Consider using JavaScript's `document.cookie` with explicit path/domain when clearing

---

## Medium Priority Issues

### ⚠️ Issue 5: Missing Default Category for Marketing Cookies

**Current State**: Only `necessary` and `analytics` categories exist by default.

**Problem**: Common tracking cookies (Facebook, Google Ads, etc.) don't have a default category.

**Recommendation**: Add a `marketing` category by default with common patterns:
```php
'marketing' => array(
    'title' => 'Marketing',
    'description' => 'These cookies help us improve the relevancy of advertising campaigns.',
    'enabled' => false,
    'readonly' => false,
    'cookies' => array(
        array('name' => '/^_fb_/', 'is_regex' => true),
        array('name' => '_fbp', 'is_regex' => false),
        array('name' => '/^_gcl_/', 'is_regex' => true),
    ),
),
```

### ⚠️ Issue 6: Cookie Consent State Not Accessible to PHP

**Problem**: The consent state (which categories user accepted) is stored in a browser cookie (`cc_cookie`), but PHP cannot easily read this to conditionally render server-side content.

**Impact**:
- Server-side PHP code cannot check if user accepted analytics before rendering tracking code
- Theme/plugins adding tracking via PHP `echo` or direct output cannot be blocked

**Recommendation**:
- Add a PHP function `warder_has_consent($category)` that reads the cookie
- Create a WordPress conditional tag `if (warder_user_accepted('analytics'))`
- Document server-side usage patterns

### ⚠️ Issue 7: No Granular Cookie Consent per Page/Post

**Problem**: Consent is global across the entire site.

**Use Case**: Some pages may need different cookie categories than others.

**Recommendation**:
- Consider adding per-page/post cookie category overrides
- Or document that consent is site-wide

### ⚠️ Issue 8: Missing Cookie Expiry Configuration

**Current State**: Consent cookie expires after 182 days (hardcoded in `src/index.js:9`)

**Problem**: Site administrators cannot configure the consent cookie duration.

**Recommendation**:
- Add `consent_cookie_expires` option to settings
- Allow configuration via admin UI

### ⚠️ Issue 9: No Version Migration System

**Problem**: When defaults change (e.g., new cookie patterns added), existing installations don't get the updates.

**Example**: Matomo patterns were added in v1.5.0, but existing installs don't have them.

**Recommendation**:
- Implement a version-based migration system
- On plugin update, merge new defaults with existing settings
- Preserve user customizations while adding new features

### ⚠️ Issue 10: JavaScript Error Handling Could Be Improved

**Current State**: Errors are logged to console but don't fail gracefully.

**Problem**: If CookieConsent initialization fails, the entire consent system may be broken without user feedback.

**Recommendation**:
- Add user-visible error messages (opt-in, admin-only by default)
- Implement fallback behavior (e.g., block all non-necessary cookies on error)
- Add error reporting option for administrators

---

## Low Priority Issues

### 🟡 Issue 11: Hardcoded Language Strings in JavaScript

**Location**: `src/index.js` has hardcoded English strings that don't go through translation.

**Recommendation**: Pass translations from PHP via `wp_localize_script`

### 🟡 Issue 12: No Loading State for Consent Modal

**Problem**: Large sites may have a delay before consent modal appears.

**Recommendation**: Add a loading indicator or ensure modal appears immediately.

### 🟡 Issue 13: Preferences Toggle Button Accessibility

**Problem**: Button has `aria-label` but could have better keyboard navigation.

**Recommendation**:
- Add `tabindex` to ensure focus order
- Add keyboard shortcuts documentation
- Test with screen readers

### 🟡 Issue 14: Missing Browser Compatibility Notes

**Problem**: No documentation about which browsers are supported.

**Recommendation**: Document browser support (vanilla-cookieconsent v3 supports IE11+ but recommend modern browsers)

### 🟡 Issue 15: Cache Busting Could Be More Efficient

**Current State**: Uses `time()` as version, which changes on every save.

**Problem**: This forces all users to re-download assets even for minor changes.

**Recommendation**: Use semantic versioning or content hashing for cache busting.

---

## Security Analysis

### ✅ Security Strengths

1. **Nonce Protection**: All admin actions use proper nonces
   - `warder_options_group-options` for settings save
   - Dedicated nonces for add/delete category/cookie actions
   
2. **Capability Checks**: All admin functions check `current_user_can('manage_options')`

3. **Input Sanitization**: 
   - `sanitize_text_field()` for text inputs
   - `sanitize_key()` for category IDs
   - `esc_url_raw()` for URLs
   - `wp_kses_post()` for HTML content

4. **Output Escaping**:
   - `esc_html_e()`, `esc_attr()`, `esc_url()` used throughout
   - `wp_strip_all_tags()` for inline styles

5. **Direct File Access Prevention**: `defined('ABSPATH') || exit` in all PHP files

### ⚠️ Security Concerns

1. **XSS via Cookie Names**: Cookie names are output in admin UI without escaping (though they are sanitized on input)
   - **Location**: Admin cookie list table
   - **Risk**: Low (sanitized on input, but defense in depth is better)

2. **CSRF in AJAX Save**: AJAX save uses nonce but could benefit from additional validation
   - **Recommendation**: Add referer check, rate limiting

3. **Information Disclosure**: Error messages in console could expose implementation details
   - **Recommendation**: Sanitize error messages in production

### 🔒 Security Recommendations

1. **Add Content Security Policy (CSP) Headers**
   - Prevent inline script execution (except for cookieconsent which needs it)
   - Use nonce-based CSP for inline scripts

2. **Implement Rate Limiting for AJAX Save**
   - Prevent brute force attacks on settings

3. **Add Security Logging**
   - Log failed consent attempts
   - Log settings changes for audit trail

4. **Regular Dependency Updates**
   - Monitor vanilla-cookieconsent for security updates
   - Update webpack and other dev dependencies regularly

---

## Performance Analysis

### ✅ Performance Strengths

1. **Lightweight**: Only loads scripts when plugin is enabled
2. **Deferred Loading**: Scripts loaded with `defer` strategy
3. **Cache Busting**: Version-based cache invalidation
4. **No Database Queries on Frontend**: Settings cached in option, passed via wp_localize_script

### ⚠️ Performance Concerns

1. **Bundle Size**: `dist/cookieconsent.bundle.js` includes all of vanilla-cookieconsent
   - **Impact**: ~50KB minified (acceptable but could be optimized)
   - **Recommendation**: Consider tree-shaking unused features

2. **No Lazy Loading**: Consent modal JavaScript loads on all pages
   - **Impact**: Slight performance overhead on pages that don't need consent
   - **Recommendation**: Consider conditional loading based on page content

3. **Inline Styles**: Preferences toggle CSS is inlined
   - **Impact**: Cannot be cached separately, increases HTML size
   - **Recommendation**: Move to external CSS file or use CSS-in-JS

### 🚀 Performance Recommendations

1. **Implement Code Splitting**
   - Separate admin and frontend bundles
   - Lazy load consent modal

2. **Optimize Asset Loading**
   - Load CSS separately for better caching
   - Use `async` or `defer` appropriately

3. **Add Performance Metrics**
   - Track consent modal load time
   - Monitor impact on page speed

---

## Code Quality Analysis

### ✅ Code Quality Strengths

1. **Modular Architecture**: Well-separated concerns (inc/*.php files)
2. **WordPress Standards**: Follows WPCS, proper hooks, nonces
3. **Documentation**: Good PHPDoc blocks, inline comments
4. **Error Handling**: Try/catch blocks in critical areas
5. **Version Control**: Comprehensive CHANGELOG.md

### ⚠️ Code Quality Issues

1. **Magic Numbers**: Hardcoded values without constants
   - Example: `182` days for cookie expiry
   - Example: Z-index values in CSS

2. **Deep Nesting**: Some functions have deep nesting levels
   - Example: `warder_render_options_page()` has multiple nested loops

3. **Mixed Concerns**: `src/index.js` handles both configuration and initialization
   - **Recommendation**: Split into separate modules

4. **No Type Checking**: PHP 8.0+ but no type declarations
   - **Recommendation**: Add parameter and return types

5. **Missing Unit Tests**: No automated testing
   - **Impact**: Regression risk when making changes

### 📊 Code Quality Recommendations

1. **Add Type Declarations**
   ```php
   function warder_get_merged_options(): array {
       // ...
   }
   ```

2. **Extract Constants**
   ```php
   define('WARDER_CONSENT_COOKIE_EXPIRY_DAYS', 182);
   ```

3. **Implement Design Patterns**
   - Use Singleton for plugin class
   - Use Factory for configuration building
   - Use Strategy for different cookie matching strategies

4. **Add Code Style Enforcement**
   - Configure PHPCS more strictly
   - Add Prettier/ESLint for JavaScript

---

## Testing Recommendations

### Test Coverage Goal: 80%+

Currently: **0% test coverage** (no tests exist)

---

## PHP Unit Test Suggestions

### Test Framework Setup

```bash
# Install PHPUnit
composer require --dev phpunit/phpunit

# Create phpunit.xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="Warder Cookie Consent">
            <directory>tests/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Test Directory Structure

```
tests/
├── bootstrap.php          # Test bootstrap
├── phpunit.xml           # PHPUnit configuration
├── TestCase.php          # Base test case
├── Unit/
│   ├── DefaultsTest.php  # Test defaults.php
│   ├── SettingsTest.php  # Test settings.php
│   ├── AjaxTest.php       # Test ajax.php
│   └── FrontendTest.php   # Test frontend.php
└── Integration/
    ├── PluginActivationTest.php
    └── AdminUITest.php
```

### Test Cases to Implement

#### 1. DefaultsTest.php

```php
<?php
class DefaultsTest extends TestCase {
    
    public function test_get_default_options_structure() {
        $options = warder_get_default_options();
        
        $this->assertArrayHasKey('enabled', $options);
        $this->assertArrayHasKey('cookie_categories', $options);
        $this->assertArrayHasKey('necessary', $options['cookie_categories']);
        $this->assertArrayHasKey('analytics', $options['cookie_categories']);
    }
    
    public function test_necessary_category_is_always_enabled() {
        $options = warder_get_default_options();
        
        $this->assertTrue($options['cookie_categories']['necessary']['enabled']);
        $this->assertTrue($options['cookie_categories']['necessary']['readonly']);
    }
    
    public function test_analytics_category_is_disabled_by_default() {
        $options = warder_get_default_options();
        
        $this->assertFalse($options['cookie_categories']['analytics']['enabled']);
        $this->assertFalse($options['cookie_categories']['analytics']['readonly']);
    }
    
    public function test_get_merged_options_returns_defaults_when_empty() {
        // Mock get_option to return empty array
        $this->mock_get_option('warder_options', []);
        
        $options = warder_get_merged_options();
        $defaults = warder_get_default_options();
        
        $this->assertEquals($defaults, $options);
    }
    
    public function test_get_merged_options_merges_with_db_values() {
        $db_options = ['title' => 'Custom Title'];
        $this->mock_get_option('warder_options', $db_options);
        
        $options = warder_get_merged_options();
        
        $this->assertEquals('Custom Title', $options['title']);
        $this->assertEquals(warder_get_default_options()['description'], $options['description']);
    }
}
```

#### 2. SettingsTest.php

```php
<?php
class SettingsTest extends TestCase {
    
    public function test_validate_options_preserves_necessary_category() {
        $input = [
            'cookie_categories' => [
                'necessary' => [
                    'enabled' => false, // User tried to disable
                    'readonly' => false,
                ],
            ],
        ];
        
        $valid = warder_validate_options($input);
        
        // Necessary should always be enabled and readonly
        $this->assertTrue($valid['cookie_categories']['necessary']['enabled']);
        $this->assertTrue($valid['cookie_categories']['necessary']['readonly']);
    }
    
    public function test_validate_options_sanitizes_text_fields() {
        $input = [
            'title' => '<script>alert("xss")</script>',
            'description' => '<p>Test</p><script>bad()</script>',
        ];
        
        $valid = warder_validate_options($input);
        
        $this->assertEquals('&lt;script&gt;alert("xss")&lt;/script&gt;', $valid['title']);
        $this->assertStringNotContainsString('<script>', $valid['description']);
    }
    
    public function test_validate_options_handles_missing_fields() {
        $input = []; // Empty input
        
        $valid = warder_validate_options($input);
        
        $this->assertFalse($valid['enabled']);
        $this->assertEquals('en', $valid['current_lang']);
    }
    
    public function test_validate_options_validates_cookie_patterns() {
        $input = [
            'cookie_categories' => [
                'analytics' => [
                    'cookies' => [
                        ['name' => '/^_ga/', 'is_regex' => true],
                        ['name' => '_gid', 'is_regex' => false],
                        ['name' => '', 'is_regex' => false], // Empty - should be filtered
                    ],
                ],
            ],
        ];
        
        $valid = warder_validate_options($input);
        
        $this->assertCount(2, $valid['cookie_categories']['analytics']['cookies']);
    }
}
```

#### 3. CookieConsentTest.php

```php
<?php
class CookieConsentTest extends TestCase {
    
    public function test_necessary_cookies_are_always_allowed() {
        // Test that necessary category cannot be disabled
        $options = warder_get_default_options();
        
        // Even if user tries to disable via filter
        add_filter('warder_cookie_categories', function($categories) {
            $categories['necessary']['enabled'] = false;
            return $categories;
        });
        
        $merged = warder_get_merged_options();
        
        $this->assertTrue($merged['cookie_categories']['necessary']['enabled']);
    }
    
    public function test_cookie_pattern_matching() {
        $patterns = [
            ['name' => '/^_ga/', 'is_regex' => true],
            ['name' => '_gid', 'is_regex' => false],
            ['name' => '/^_pk_/', 'is_regex' => true],
        ];
        
        $test_cookies = [
            '_ga' => true,
            '_ga_123' => true,
            '_gat' => false,
            '_gid' => true,
            '_pk_id' => true,
            '_pk_ses' => true,
            'wordpress_cookie' => false,
        ];
        
        foreach ($test_cookies as $cookie => $should_match) {
            $matched = false;
            foreach ($patterns as $pattern) {
                if ($pattern['is_regex']) {
                    $regex = '/' . trim($pattern['name'], '/') . '/';
                    if (preg_match($regex, $cookie)) {
                        $matched = true;
                        break;
                    }
                } else {
                    if ($pattern['name'] === $cookie) {
                        $matched = true;
                        break;
                    }
                }
            }
            
            $this->assertEquals($should_match, $matched, "Cookie $cookie matching failed");
        }
    }
}
```

#### 4. FrontendTest.php

```php
<?php
class FrontendTest extends TestCase {
    
    public function test_enqueue_scripts_when_enabled() {
        $this->mock_get_option('warder_options', ['enabled' => true]);
        
        // Capture wp_enqueue_script calls
        $this->expect_script_enqueued('warder-cookieconsent');
        
        warder_enqueue_scripts();
    }
    
    public function test_enqueue_scripts_when_disabled() {
        $this->mock_get_option('warder_options', ['enabled' => false]);
        
        // Should not enqueue when disabled
        $this->expect_script_not_enqueued('warder-cookieconsent');
        
        warder_enqueue_scripts();
    }
    
    public function test_preferences_toggle_css_generation() {
        $css = warder_get_preferences_toggle_css();
        
        $this->assertStringContainsString('.warder-preferences-toggle', $css);
        $this->assertStringContainsString('bottom-right', $css);
        $this->assertStringContainsString('top-left', $css);
    }
}
```

### Mock Helper (tests/TestCase.php)

```php
<?php
abstract class TestCase extends WP_UnitTestCase {
    
    protected $original_options = [];
    
    public function setUp(): void {
        parent::setUp();
        
        // Backup original options
        $this->original_options = get_option('warder_options', []);
    }
    
    public function tearDown(): void {
        // Restore original options
        if ($this->original_options) {
            update_option('warder_options', $this->original_options);
        } else {
            delete_option('warder_options');
        }
        
        parent::tearDown();
    }
    
    protected function mock_get_option($key, $value) {
        add_filter('pre_option_' . $key, function() use ($value) {
            return $value;
        });
    }
    
    protected function expect_script_enqueued($handle) {
        $this->expectHookCalled('wp_enqueue_scripts', function($scripts) use ($handle) {
            return isset($scripts[$handle]);
        });
    }
    
    protected function expect_script_not_enqueued($handle) {
        $this->expectHookNotCalled('wp_enqueue_scripts', function($scripts) use ($handle) {
            return isset($scripts[$handle]);
        });
    }
}
```

---

## JavaScript Test Suggestions

### Test Framework Setup

```bash
# Install testing libraries
npm install --save-dev jest @testing-library/jest-dom @testing-library/user-event
npm install --save-dev babel-jest @babel/preset-env
```

### Jest Configuration (jest.config.js)

```javascript
module.exports = {
    testEnvironment: 'jsdom',
    setupFilesAfterEnv: ['<rootDir>/tests/setup.js'],
    moduleNameMapper: {
        '^vanilla-cookieconsent$': '<rootDir>/node_modules/vanilla-cookieconsent/dist/cookieconsent.js',
    },
    transform: {
        '^.+\.js$': 'babel-jest',
    },
};
```

### Test Directory Structure

```
tests/js/
├── setup.js              # Jest setup
├── __mocks__/
│   └── cookieconsent.js  # Mock vanilla-cookieconsent
├── index.test.js         # Main index.js tests
└── utils/
    └── configBuilder.test.js
```

### Mock vanilla-cookieconsent (tests/js/__mocks__/cookieconsent.js)

```javascript
// Mock implementation of vanilla-cookieconsent
const CookieConsent = {
    run: jest.fn((config) => {
        // Store config for assertions
        CookieConsent.lastConfig = config;
        return {
            showPreferences: jest.fn(),
            acceptedCategory: jest.fn((category) => {
                return config.categories?.[category]?.enabled || false;
            }),
        };
    }),
    lastConfig: null,
};

export default CookieConsent;
export { CookieConsent };
```

### Main Tests (tests/js/index.test.js)

```javascript
import { createConfigFromSettings } from '../../src/index';

describe('createConfigFromSettings', () => {
    const defaultConfig = {
        cookie: {
            name: 'cc_cookie',
            expiresAfterDays: 182,
        },
        categories: {
            necessary: { enabled: true, readOnly: true },
            analytics: { enabled: false, readOnly: false },
        },
        language: {
            default: 'en',
            translations: {
                en: {
                    consentModal: {
                        title: 'We use cookies',
                        description: 'This website uses cookies...',
                    },
                    preferencesModal: {
                        sections: [],
                    },
                },
            },
        },
    };

    describe('with empty settings', () => {
        it('should return default config when wpSettings is empty', () => {
            const result = createConfigFromSettings(defaultConfig, {});
            expect(result).toEqual(defaultConfig);
        });

        it('should return default config when settings is null', () => {
            const result = createConfigFromSettings(defaultConfig, null);
            expect(result).toEqual(defaultConfig);
        });
    });

    describe('with valid settings', () => {
        const wpSettings = {
            settings: {
                current_lang: 'fr',
                title: 'Nous utilisons des cookies',
                description: 'Ce site utilise des cookies',
                cookie_categories: {
                    necessary: {
                        title: 'Nécéssaires',
                        description: 'Cookies nécessaires',
                        enabled: true,
                        readonly: true,
                        cookies: [],
                    },
                    analytics: {
                        title: 'Analytique',
                        description: 'Cookies analytiques',
                        enabled: false,
                        readonly: false,
                        cookies: [
                            { name: '/^_ga/', is_regex: true },
                            { name: '_gid', is_regex: false },
                        ],
                    },
                },
            },
        };

        it('should update language default', () => {
            const result = createConfigFromSettings(defaultConfig, wpSettings);
            expect(result.language.default).toBe('fr');
        });

        it('should update consent modal text', () => {
            const result = createConfigFromSettings(defaultConfig, wpSettings);
            expect(result.language.translations.fr.consentModal.title).toBe('Nous utilisons des cookies');
            expect(result.language.translations.fr.consentModal.description).toBe('Ce site utilise des cookies');
        });

        it('should preserve necessary category as enabled and readonly', () => {
            const result = createConfigFromSettings(defaultConfig, wpSettings);
            expect(result.categories.necessary.enabled).toBe(true);
            expect(result.categories.necessary.readOnly).toBe(true);
        });

        it('should set analytics category as disabled', () => {
            const result = createConfigFromSettings(defaultConfig, wpSettings);
            expect(result.categories.analytics.enabled).toBe(false);
            expect(result.categories.analytics.readOnly).toBe(false);
        });

        it('should convert regex patterns correctly', () => {
            const result = createConfigFromSettings(defaultConfig, wpSettings);
            
            const analyticsCookies = result.categories.analytics.autoClear.cookies;
            
            // First cookie should be a RegExp
            expect(analyticsCookies[0].name).toBeInstanceOf(RegExp);
            expect(analyticsCookies[0].name.source).toBe('^_ga');
            
            // Second cookie should be a string
            expect(analyticsCookies[1].name).toBe('_gid');
        });

        it('should add sections for categories', () => {
            const result = createConfigFromSettings(defaultConfig, wpSettings);
            
            const sections = result.language.translations.fr.preferencesModal.sections;
            
            // Should have intro section plus category sections
            expect(sections.length).toBeGreaterThan(0);
            
            // Find analytics section
            const analyticsSection = sections.find(s => s.linkedCategory === 'analytics');
            expect(analyticsSection).toBeDefined();
            expect(analyticsSection.title).toBe('Analytique');
            expect(analyticsSection.description).toBe('Cookies analytiques');
        });
    });

    describe('with invalid regex patterns', () => {
        it('should handle invalid regex gracefully', () => {
            const invalidSettings = {
                settings: {
                    cookie_categories: {
                        analytics: {
                            cookies: [
                                { name: '/[invalid/', is_regex: true }, // Invalid regex
                            ],
                        },
                    },
                },
            };

            // Should not throw
            expect(() => {
                createConfigFromSettings(defaultConfig, invalidSettings);
            }).not.toThrow();
        });

        it('should fall back to literal string for invalid regex', () => {
            const invalidSettings = {
                settings: {
                    cookie_categories: {
                        analytics: {
                            cookies: [
                                { name: '/[invalid/', is_regex: true },
                            ],
                        },
                    },
                },
            };

            const result = createConfigFromSettings(defaultConfig, invalidSettings);
            const analyticsCookies = result.categories.analytics.autoClear.cookies;
            
            // Should fall back to literal string
            expect(analyticsCookies[0].name).toBe('/[invalid/');
        });
    });
});
```

### Cookie Pattern Matching Tests

```javascript
// tests/js/utils/cookieMatcher.test.js

describe('Cookie Pattern Matching', () => {
    const patterns = [
        { name: '/^_ga/', is_regex: true },
        { name: '_gid', is_regex: false },
        { name: '/^_pk_/', is_regex: true },
        { name: '/^mtm_/', is_regex: true },
    ];

    const convertPatterns = (cookiePatterns) => {
        return cookiePatterns.map(cookie => {
            if (cookie.is_regex && cookie.name.startsWith('/') && cookie.name.includes('/')) {
                try {
                    const pattern = cookie.name.slice(1, cookie.name.lastIndexOf('/'));
                    return { name: new RegExp(pattern) };
                } catch (e) {
                    return { name: cookie.name };
                }
            } else {
                return { name: cookie.name };
            }
        });
    };

    it('should match Google Analytics cookies', () => {
        const compiled = convertPatterns(patterns);
        
        const testCases = [
            { cookie: '_ga', shouldMatch: true },
            { cookie: '_ga_123456', shouldMatch: true },
            { cookie: '_gat', shouldMatch: false },
            { cookie: '_gid', shouldMatch: true },
        ];

        testCases.forEach(({ cookie, shouldMatch }) => {
            const matched = compiled.some(p => {
                if (p.name instanceof RegExp) {
                    return p.name.test(cookie);
                }
                return p.name === cookie;
            });
            expect(matched).toBe(shouldMatch);
        });
    });

    it('should match Matomo cookies', () => {
        const compiled = convertPatterns(patterns);
        
        const matomoCookies = [
            '_pk_id', '_pk_ses', '_pk_ref', '_pk_cvar', '_pk_hsr',
            '_pk_testcookie', 'mtm_cookie_consent', 'mtm_goal'
        ];

        matomoCookies.forEach(cookie => {
            const matched = compiled.some(p => {
                if (p.name instanceof RegExp) {
                    return p.name.test(cookie);
                }
                return p.name === cookie;
            });
            expect(matched).toBe(true);
        });
    });
});
```

---

## Feature Enhancement Suggestions

### 1. Server-Side Cookie Consent Checking

**Status: ✅ Implemented in `inc/helpers.php`** (branch `feat/warder-has-consent-helper`)

**Feature**: PHP function to check if visitor has accepted a specific category.

**Correction to original proposal**: vanilla-cookieconsent v3 persists accepted
categories as a **string array** (`categories: string[]` per its TypeScript
definition), not a keyed `{ category: bool }` map. The earlier draft using
`$consent_data['categories'][$category]` would have always returned `false`.
The correct lookup is `in_array( $category, $data['categories'], true )`.

```php
function warder_has_consent( $category ) {
    $category = sanitize_key( $category );

    if ( '' === $category ) {
        return false;
    }

    // Necessary is always granted — no cookie required.
    if ( 'necessary' === $category ) {
        return true;
    }

    if ( empty( $_COOKIE['cc_cookie'] ) ) {
        return false;
    }

    $raw  = wp_unslash( $_COOKIE['cc_cookie'] );
    $data = json_decode( $raw, true );

    if ( ! is_array( $data ) || empty( $data['categories'] ) || ! is_array( $data['categories'] ) ) {
        return false;
    }

    return in_array( $category, $data['categories'], true );
}
```

**Usage:**
```php
// In theme or plugin — works for any cookie-using analytics provider in
// the category (Google Analytics, Matomo, ...) and for custom categories.
if ( warder_has_consent( 'analytics' ) ) {
    echo '<script>/* your analytics snippet */</script>';
}
```

### 2. Automatic Script Wrapping

**Feature**: Automatically wrap known tracking scripts with proper attributes.

```php
/**
 * Filter to automatically add data-category to known tracking scripts
 */
function warder_filter_script_tags($tag, $handle, $src) {
    $tracking_scripts = [
        'google-analytics' => 'analytics',
        'gtag' => 'analytics',
        'matomo' => 'analytics',
        'facebook-pixel' => 'marketing',
    ];
    
    foreach ($tracking_scripts as $script_handle => $category) {
        if (strpos($handle, $script_handle) !== false) {
            $tag = preg_replace('/<script /', '<script type="text/plain" data-category="' . $category . '" ', $tag);
            break;
        }
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'warder_filter_script_tags', 10, 3);
```

### 3. Consent Logging

**Feature**: Log consent events for audit/compliance purposes.

```php
/**
 * Log consent events
 */
function warder_log_consent_event($action, $category = null, $user_id = null) {
    if (!get_option('warder_enable_consent_logging', false)) {
        return;
    }
    
    $log_data = [
        'timestamp' => current_time('mysql'),
        'action' => $action,
        'category' => $category,
        'user_id' => $user_id,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];
    
    // Store in custom table or option
    $logs = get_option('warder_consent_logs', []);
    $logs[] = $log_data;
    
    // Keep only last 1000 logs
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }
    
    update_option('warder_consent_logs', $logs);
}
```

### 4. Consent Export/Import

**Feature**: Allow exporting and importing consent configurations.

```php
/**
 * Export consent settings as JSON
 */
function warder_export_settings() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized.', 'warder-cookie-consent'));
    }
    
    $options = get_option('warder_options', []);
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="warder-consent-settings-' . date('Y-m-d') . '.json"');
    
    echo json_encode($options, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Import consent settings from JSON
 */
function warder_import_settings() {
    if (!current_user_can('manage_options') || !isset($_FILES['warder_import_file'])) {
        wp_die(__('Unauthorized or no file uploaded.', 'warder-cookie-consent'));
    }
    
    $file = $_FILES['warder_import_file'];
    
    if ($file['type'] !== 'application/json') {
        wp_die(__('Please upload a valid JSON file.', 'warder-cookie-consent'));
    }
    
    $content = file_get_contents($file['tmp_name']);
    $options = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_die(__('Invalid JSON file.', 'warder-cookie-consent'));
    }
    
    // Validate and merge with defaults
    $valid_options = warder_validate_options($options);
    
    update_option('warder_options', $valid_options);
    
    wp_redirect(admin_url('options-general.php?page=warder-cookie-consent&imported=1'));
    exit;
}
```

### 5. Consent Statistics Dashboard

**Feature**: Show consent statistics in admin dashboard.

```php
/**
 * Display consent statistics
 */
function warder_consent_statistics() {
    $stats = get_transient('warder_consent_stats');
    
    if ($stats === false) {
        // Calculate stats
        $logs = get_option('warder_consent_logs', []);
        
        $stats = [
            'total_consents' => 0,
            'accept_all' => 0,
            'accept_necessary' => 0,
            'accept_selected' => 0,
            'categories' => [],
        ];
        
        foreach ($logs as $log) {
            if ($log['action'] === 'accept_all') {
                $stats['accept_all']++;
                $stats['total_consents']++;
            } elseif ($log['action'] === 'accept_necessary') {
                $stats['accept_necessary']++;
                $stats['total_consents']++;
            } elseif ($log['action'] === 'accept_selected') {
                $stats['accept_selected']++;
                $stats['total_consents']++;
            }
            
            if ($log['category']) {
                $stats['categories'][$log['category']] = ($stats['categories'][$log['category']] ?? 0) + 1;
            }
        }
        
        set_transient('warder_consent_stats', $stats, DAY_IN_SECONDS);
    }
    
    // Display stats in admin
    return $stats;
}
```

### 6. Geo-Based Consent Rules

**Feature**: Different consent rules for different regions.

```php
/**
 * Get consent configuration based on user location
 */
function warder_get_geo_based_config() {
    $geo_rules = get_option('warder_geo_rules', []);
    
    if (empty($geo_rules)) {
        return null; // Use default config
    }
    
    $user_country = $this->get_user_country(); // Implement based on IP
    
    foreach ($geo_rules as $rule) {
        if (in_array($user_country, $rule['countries'])) {
            return $rule['config'];
        }
    }
    
    return null; // Use default config
}
```

---

## Implementation Roadmap

### Phase 1: Critical Fixes (Priority: HIGH - Complete within 2 weeks)

- [ ] **Fix script blocking bypass** - Document the requirement for `type="text/plain" data-category="..."`
- [ ] **Add regex validation** in PHP before saving cookie patterns
- [x] **Add server-side consent checking** function `warder_has_consent()` — landed in `inc/helpers.php`
- [ ] **Create admin notice** warning about script attribute requirements

### Phase 2: Testing Infrastructure (Priority: HIGH - Complete within 3 weeks)

- [ ] **Set up PHPUnit** with bootstrap and configuration
- [ ] **Create base test case** with mock helpers
- [ ] **Implement DefaultsTest** for default options
- [ ] **Implement SettingsTest** for validation logic
- [ ] **Implement CookieConsentTest** for pattern matching
- [ ] **Set up Jest** for JavaScript testing
- [ ] **Create mock for vanilla-cookieconsent**
- [ ] **Implement index.test.js** for config building
- [ ] **Implement cookie pattern matching tests**

### Phase 3: Feature Enhancements (Priority: MEDIUM - Complete within 6 weeks)

- [ ] **Add server-side script wrapping** filter
- [ ] **Implement consent logging** functionality
- [ ] **Add consent export/import** feature
- [ ] **Create consent statistics dashboard**
- [ ] **Add version migration system** for defaults

### Phase 4: Documentation & Polish (Priority: MEDIUM - Ongoing)

- [ ] **Update README.md** with usage examples and limitations
- [ ] **Add developer documentation** for custom implementations
- [ ] **Create user guide** for configuration
- [ ] **Add troubleshooting section** to docs
- [ ] **Improve admin UI** with better error messages

### Phase 5: Advanced Features (Priority: LOW - Future)

- [ ] **Geo-based consent rules**
- [ ] **Per-page cookie category overrides**
- [ ] **Consent cookie duration configuration**
- [ ] **Advanced cookie path/domain configuration**
- [ ] **Integration with popular plugins** (GA, Matomo, etc.)

---

## Conclusion

The Warder Cookie Consent plugin has a solid foundation with good architecture and WordPress standards compliance. The recent v2.0.0 refactoring addressed several critical issues with the necessary category handling.

**Key Areas for Improvement:**

1. **Critical**: Address the script blocking bypass vulnerability by documenting requirements and potentially adding server-side wrapping
2. **High Priority**: Implement comprehensive test coverage to prevent regressions
3. **Medium Priority**: Add server-side consent checking and logging features
4. **Low Priority**: Implement advanced features like geo-based rules and per-page overrides

With these improvements, the plugin can achieve A+ status and be ready for WordPress.org submission with confidence.

---

*Analysis completed by: Claude Code Assistant*  
*For: Jasper Frumau / Imagewize*  
*Plugin: Warder Cookie Consent v2.0.1*
