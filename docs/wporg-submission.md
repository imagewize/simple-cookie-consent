# WordPress.org Repository Submission Checklist

This document outlines all steps required to prepare the Simple Cookie Consent plugin for submission to the official WordPress Plugin Directory at [wordpress.org/plugins](https://wordpress.org/plugins/).

## Prerequisites

- [ ] WordPress.org account with plugin contributor access
- [x] Plugin is functional and tested
- [x] All dependencies are properly declared

## 1. Code & Structure Requirements

### Plugin Header
- [x] `Plugin Name` is unique and descriptive
- [x] `Description` is clear and concise (max 140 characters)
- [x] `Version` follows semantic versioning (currently `1.0.0`)
- [x] `Author` is present
- [x] `Author URI` is present (`https://imagewize.com`)
- [x] `Text Domain` is defined (`simple-cookie-consent`)
- [x] `License` and `License URI` are specified (GPLv2 or later)

### File Structure
- [x] Main plugin file is named correctly (`simple-cookie-consent.php`)
- [x] All plugin files are contained within a single directory
- [x] No development files included in distribution (`.distignore` created — see Section 7)
- [x] `readme.txt` exists (WordPress.org standard)

### Code Quality
- [x] No PHP errors, warnings, or notices
- [x] All functions are properly prefixed (`scc_`)
- [x] Nonces used for form submissions (`wp_nonce_field`, `wp_nonce_url` present)
- [x] All user input is sanitized and escaped
- [x] Direct file access is prevented (`defined('ABSPATH') || exit`)
- [x] No hardcoded paths — uses WordPress constants

## 2. Readme.txt File

- [x] `readme.txt` created at repo root with WordPress.org standard formatting
- [x] All required headers present (Contributors, Tags, Requires at least, Tested up to, Stable tag, License)
- [x] Description, Installation, FAQ, Screenshots, and Changelog sections included

## 3. Security Requirements

- [x] All form submissions use nonces
- [x] All user input is sanitized with WordPress sanitization functions
- [x] All output is escaped with `esc_*` functions
- [x] Direct file access is blocked
- [x] Capability checks are in place for admin functions (`current_user_can('manage_options')`)
- [x] No sensitive data is stored in insecure locations

## 4. PHP Coding Standards (PHPCS)

WordPress.org reviewers expect code to follow WordPress Coding Standards.

**Setup (already done — do not re-run):**
```bash
composer require --dev squizlabs/php_codesniffer wp-coding-standards/wpcs phpcsstandards/phpcsutils
```

Note: `vendor/` is `.gitignore`d and listed in `.distignore`. It is dev-only tooling and must not be committed or included in the distribution zip.

**Run check:**
```bash
./vendor/bin/phpcs
./vendor/bin/phpcbf   # auto-fix what it can
```

- [x] `phpcs.xml` created (uses `<element>` syntax compatible with PHPCS 3.3+)
- [x] PHPCS passes with **0 errors and 0 warnings**
- [x] Auto-fixable issues resolved with `phpcbf`

## 5. Documentation

- [x] `readme.txt` created (WordPress.org standard)
- [x] `README.md` exists for GitHub users
- [x] Inline code documentation added (PHPDoc blocks on all public functions)
- [ ] Usage examples and FAQ expanded (readme.txt FAQ is minimal — can improve before submission)

## 6. Build Process

- [x] `dist/cookieconsent.bundle.js` exists (production build ready)
- [ ] Verify source maps are excluded from production build
- [ ] Test the built plugin end-to-end

## 7. `.distignore` — Distribution Zip Exclusions

- [x] `.distignore` created at repo root
- [x] `vendor/*` added (dev-only PHPCS tooling excluded from zip)
- [ ] Distribution zip tested — only plugin files included (`simple-cookie-consent.php`, `dist/`, `readme.txt`, `LICENSE.md`)

**Build distribution zip:**
```bash
zip -r simple-cookie-consent.zip . -x@.distignore

# Verify contents
unzip -l simple-cookie-consent.zip
```

## 8. GitHub Actions Workflows

- [x] `.github/workflows/wpcs.yml` created — runs WPCS check on every PR
- [x] `.github/workflows/create-release.yml` created — builds and attaches plugin zip on GitHub releases (upload manually to wp.org)
- ~~`.github/workflows/deploy-wporg.yml`~~ — removed; uploading to wp.org manually via the zip from `create-release.yml`

## 9. Testing

- [ ] Test on clean WordPress installation (latest version)
- [ ] Test with default theme (Twenty Twenty-Four)
- [ ] Test with popular page builders (Elementor, Divi, etc.)
- [ ] Test with caching plugins
- [ ] Test in multiple browsers (Chrome, Firefox, Safari, Edge)
- [ ] Test on mobile devices
- [ ] Test cookie category blocking functionality
- [ ] Test consent withdrawal and cookie clearing

## 10. Submission Steps

1. **Prepare the plugin package:**
   ```bash
   # Build assets
   npm ci && npx webpack

   # Create distribution zip using .distignore
   zip -r simple-cookie-consent.zip . -x@.distignore

   # Verify contents
   unzip -l simple-cookie-consent.zip
   ```

2. **Submit to WordPress.org:**
   - Go to: https://wordpress.org/plugins/developers/add/
   - Upload the ZIP file
   - Fill in all required information
   - Wait for review (typically 1-2 weeks)

3. **SVN Repository Setup (after approval):**
   - Set up SVN repository as instructed by the wp.org team
   - Add `SVN_USERNAME` and `SVN_PASSWORD` as GitHub secrets
   - Enable the `deploy-wporg.yml` workflow
   - Future releases: push a git tag → workflow deploys to wp.org SVN automatically

## 11. Compliance Checks

- [ ] Verify GPLv2 compatibility of all included libraries
- [x] `vanilla-cookieconsent` is MIT licensed (GPL-compatible)
- [x] `LICENSE.md` updated to GPLv2 (matches plugin header `License: GPLv2 or later`)
- [ ] Ensure no proprietary code is included
- [ ] Confirm all third-party assets have proper attribution

## Timeline

| Task | Estimated Time | Status |
|------|---------------|--------|
| Plugin header fixes | 15 min | ✅ Done |
| Remove `error_log` calls | 15 min | ✅ Done |
| Add ABSPATH check | 5 min | ✅ Done |
| Update LICENSE.md to GPLv2 | 5 min | ✅ Done |
| Create `readme.txt` | 30 min | ✅ Done |
| Create `phpcs.xml` + run PHPCS | 1 hour | ✅ Done (0 errors) |
| Create `.distignore` | 15 min | ✅ Done |
| Create GitHub Actions workflows | 30 min | ✅ Done |
| Add PHPDoc to all functions | 30 min | ✅ Done |
| Fix all escaping / sanitization | 30 min | ✅ Done |
| Testing | 2-4 hours | Pending |
| Build + zip + verify contents | 30 min | Pending |
| wp.org submission | 30 min | Pending |
| Review wait | 1-2 weeks | Pending |
| SVN secrets + deploy workflow | 15 min | Pending (post-approval) |

## Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Plugin Submission Guidelines](https://wordpress.org/plugins/developers/)
- [Plugin Review Standards](https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/)
- [GPLv2 License](https://www.gnu.org/licenses/gpl-2.0.html)
- [10up WPCS Action](https://github.com/10up/wpcs-action)
- [10up WP Plugin Deploy Action](https://github.com/10up/action-wordpress-plugin-deploy)
- [softprops/action-gh-release](https://github.com/softprops/action-gh-release)
