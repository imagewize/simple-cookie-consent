# WordPress Plugin Review — Remediation Notes

**Status:** Addressed in v2.0.2

This document summarises the issues raised by the WordPress.org Plugin Review Team
and how each was resolved. Internal review/ticket identifiers are intentionally
omitted. All file references point to the current `inc/` structure.

---

## Overview

Four areas were flagged: source-code availability, nonce verification, input
sanitization, and output handling. The line numbers cited in the review matched
the **current** code (not a pre-refactor version), so each item was treated as a
real finding and verified directly against the source rather than assumed fixed.

---

## 1. Source Code Availability

**Concern:** A non-compiled version of the JavaScript/CSS source must be available,
either bundled in the plugin or linked from the readme. Obfuscated or
minified-only code is not permitted.

**Resolution:** Both routes are satisfied.

- The deployed plugin ships its source: `src/index.js` (webpack entry point) and
  `webpack.config.js` (build config). `.distignore` deliberately preserves these.
- `readme.txt` documents the build process and links to the public repository:
  `https://github.com/imagewize/warder-cookie-consent` (verified public).
- A dedicated "Source Code" section in `readme.txt` (placed before the changelog,
  the conventional location for developer notes) states explicitly that
  uncompressed source ships in the plugin *and* in the public repo.

The only compiled asset is `dist/cookieconsent.bundle.js`; nothing is obfuscated.

---

## 2. Nonce Verification Before Input Use

**Concern:** Request input must be origin-validated with a nonce before it drives
any action (`inc/ajax.php`, `warder_handle_admin_actions()`).

**Resolution:** The add-category and add-cookie handlers already used
`check_admin_referer()`. The two delete handlers were restructured so the nonce is
verified **before** any request data is used to mutate state, and now call
`wp_die()` on a failed check instead of silently continuing. The capability check
(`current_user_can( 'manage_options' )`) remains at function entry.

---

## 3. Input Sanitization

**Concern:** `$_POST`/`$_GET` input must be sanitized; the raw settings array was
passed to validation behind a `phpcs:ignore` suppression.

**Resolution:** A dedicated recursive sanitizer, `warder_sanitize_options_input()`,
now cleans the entire `$_POST['warder_options']` array at the boundary before
validation — the `phpcs:ignore` is gone. It is field-aware: `description` keys are
run through `wp_kses_post()` (the banner renders them as markup on the front end),
and every other scalar leaf through `sanitize_text_field()`. A blanket
`sanitize_text_field()` over the whole array was deliberately **not** used, because
it would strip legitimate links from category descriptions and break nested arrays.

`warder_validate_options()` was additionally hardened: every field access —
including nested per-category fields like each category's `description` — is now
`isset()`-guarded (no PHP warnings on partial submissions under `WP_DEBUG`), and
`current_lang` is constrained to the supported language whitelist. The sanitizer is
registered as a `customSanitizingFunction` in `phpcs.xml`.

---

## 4. Output Handling for Inline CSS

**Concern:** Echoed/output values must be handled appropriately
(`inc/frontend.php`, inline preferences-toggle CSS).

**Resolution:** The CSS returned by `warder_get_preferences_toggle_css()` is fully
static and contains no user input. The previous `wp_strip_all_tags()` wrapper was
removed — it is an HTML helper and the wrong tool for stylesheet content — and a
comment documents that the CSS is static. Dynamic values elsewhere in this file
(e.g. the toggle position and button markup) are escaped with `esc_attr()` /
`esc_attr__()`.

---

## Supporting Changes

- `phpcs.xml` now lints the entire `inc/` directory (previously only the main
  plugin file was scanned), so these files are covered going forward.
- The admin "thank you" notice was audited and is already correctly escaped
  (`esc_html__()` / `esc_url()` / `esc_html()`).

---

## Post-Review Verification (Branch: issues-30-05-26 vs main)

**Date:** 2025-06-04  
**Status:** All review issues confirmed resolved. No new `phpcs:ignore` suppressions added.

### Changes Applied in v2.0.2

| Issue | File | Change | Status |
|-------|------|--------|--------|
| Input Sanitization | `inc/ajax.php:20-22` | Removed `// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized`; replaced with `warder_sanitize_options_input()` call | ✅ Fixed |
| Nonce Verification | `inc/ajax.php:81-91` | Delete category handler: nonce verified **before** using `$category_id` to mutate state; calls `wp_die()` on failure | ✅ Fixed |
| Nonce Verification | `inc/ajax.php:99-109` | Delete cookie handler: nonce verified **before** using `$category_id` or `$cookie_index` to mutate state; calls `wp_die()` on failure | ✅ Fixed |
| Output Handling | `inc/frontend.php:44` | Removed `wp_strip_all_tags()` wrapper around static CSS; added comment explaining rationale | ✅ Fixed |
| Input Sanitization | `inc/settings.php:42-67` | Added `warder_sanitize_options_input()` recursive sanitizer function | ✅ Fixed |
| Input Validation | `inc/settings.php:71-120` | Hardened `warder_validate_options()`: all fields (incl. nested category `description`) `isset()`-guarded, `current_lang` whitelisted | ✅ Fixed |
| Tooling | `phpcs.xml` | Added `inc/` directory to linting; registered `warder_sanitize_options_input` as custom sanitizer | ✅ Fixed |
| Documentation | `readme.txt` | Added "Source Code" section before changelog | ✅ Fixed |

### Remaining `phpcs:ignore` Suppressions (Legitimate)

The following suppressions exist in the codebase but are **not** related to the review findings and are considered acceptable:

| File | Line | Sniff | Justification |
|------|------|-------|---------------|
| `inc/admin.php` | 63 | `WordPress.Security.NonceVerification.Recommended` | `$_GET['settings-updated']` — WordPress core settings API already verifies nonce before redirect |
| `inc/admin.php` | 69 | `WordPress.Security.NonceVerification.Recommended` | `$_GET['warder_notice']` — internal redirect parameter, sanitized with `sanitize_key()` |
| `inc/admin.php` | 435 | `WordPress.Security.NonceVerification.Recommended` | `$_GET['page']` — page slug check in `admin_url()` context |
| `inc/frontend.php` | 42 | `WordPress.WP.EnqueuedResourceParameters.MissingVersion` | Dynamic style handle registered with `$version` variable |

**No new `phpcs:ignore` suppressions were introduced** in the remediation commits. Two were removed: the `InputNotSanitized` ignore on the raw `$_POST['warder_options']` array, and the `UnusedFunctionParameter` ignore on `warder_update_options_timestamp()` (the callback now declares no parameters instead of accepting two it never used).

**`phpcs --standard=phpcs.xml` passes clean** across all plugin files (verified 2025-06-04).

---

## Verification Checklist

- [x] Source documented in readme and shipped in the plugin; repo confirmed public
- [x] Nonce verified before input use in all delete handlers
- [x] Raw input sanitized at the boundary; `phpcs:ignore` removed
- [x] `warder_validate_options()` `isset()`-guarded and whitelisted
- [x] Inappropriate `wp_strip_all_tags()` on CSS removed
- [x] `phpcs --standard=phpcs.xml` passes clean across all files
- [x] No new `phpcs:ignore` suppressions added for review-related issues
- [ ] Run the [Plugin Check](https://wordpress.org/plugins/plugin-check/) tool on a clean install
- [ ] Smoke-test admin save / add / delete flows with `WP_DEBUG=true`

---

## References

- [Plugin Security Handbook](https://developer.wordpress.org/plugins/security/)
- [Nonces](https://developer.wordpress.org/plugins/security/nonces/)
- [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/)
- [Escaping Data](https://developer.wordpress.org/apis/security/escaping/)
- [Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
