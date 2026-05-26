# Changelog

All notable changes to Simple Cookie Consent are documented here.

## [1.0.0] - 2026-05-26

### Added
- `CLAUDE.md` with codebase architecture and build instructions for Claude Code
- `CHANGELOG.md` documenting full version history

### Security
- Updated webpack from 5.98.0 to 5.107.2, resolving two SSRF/allowedUris bypass vulnerabilities (moderate/low)
- Updated postcss (via `npm audit fix`), resolving XSS via unescaped `</style>` in CSS output (moderate)
- Transitive fixes via webpack update: serialize-javascript (RCE via RegExp/Date, CPU exhaustion DoS), fast-uri (host confusion + path traversal via percent-encoded segments), ajv (ReDoS with `$data` option)

## [1.0.0-beta.2] - 2025-05-14

### Fixed
- Corrected script blocking attribute from `data-cookiecategory` to `data-category` per vanilla-cookieconsent v3 documentation

## [1.0.0-beta.1] - 2025-04-04

### Added
- Cookie categories and sections management with configurable necessary and analytics categories
- Admin settings page (Settings > Cookie Consent) with full form submission and logging
- `scc_get_default_options()` and `scc_get_merged_options()` for reliable settings retrieval with defaults
- Guard for missing `cookie_categories` key in options array
- README cookie management documentation

### Changed
- Migrated to vanilla-cookieconsent v3 API (language structure, `gui_options`, `settings_modal`, categories format)
- Refactored admin form to save all settings correctly
- Restored missing default values returned from options functions

### Fixed
- Frontend script not enqueuing due to missing `wp_enqueue_scripts` action hook

## [0.0.4-alpha] - 2025-03-31

### Added
- Full vanilla-cookieconsent v3 configuration: `settings_modal`, `gui_options`, language structure, categories
- `CookieConsent.run()` initialization with DOM-ready guard
- Wildcard import of vanilla-cookieconsent functions; simplified to specific module imports
- Webpack container build

### Fixed
- Run import patch and initiation build fixes (Patch v4)

## [0.0.3-alpha] - 2025-03-31

### Added
- WordPress admin options page
- Cookie consent instance initiation
- Composer installation note in README

## [0.0.2-alpha] - 2025-03-31

### Added
- MIT License
- Composer package file (`imagewize/simple-cookie-consent`)

## [0.0.1-alpha] - 2025-03-31

### Added
- Initial plugin scaffolding with WordPress plugin header
- vanilla-cookieconsent v3 integration via webpack bundle (`src/index.js` → `dist/cookieconsent.bundle.js`)
- Webpack build system with CSS bundling via style-loader
