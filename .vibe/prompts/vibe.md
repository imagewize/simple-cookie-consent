# Custom Instructions for Vibe

## Primary Directive
Always follow the rules and conventions in the project's `CLAUDE.md` file. This is your authoritative source for all project-specific guidance.

## Project Structure

**warder-cookie-consent** is a WordPress plugin wrapping the [vanilla-cookieconsent](https://github.com/orestbida/cookieconsent) v3 library.

Key files:
- `warder-cookie-consent.php` — thin entry point (~27 lines): plugin header, constants, and `require_once` for each `inc/*.php` module
- `inc/defaults.php` — default options + `warder_get_merged_options()`
- `inc/settings.php` — `register_setting()`, `warder_validate_options()`, activation
- `inc/ajax.php` — AJAX save and admin action handlers
- `inc/admin.php` — admin settings page renderer + admin script enqueue
- `inc/frontend.php` — frontend script enqueue, `wp_localize_script` as `window.warderSettings`, preferences toggle button
- `inc/helpers.php` — public helpers for theme/plugin authors (e.g. `warder_has_consent( $category )`)
- `src/index.js` — JS entry point, maps settings to vanilla-cookieconsent config
- `dist/cookieconsent.bundle.js` — compiled output (do not edit directly)
- `assets/js/admin.js` — admin page JS (AJAX save, UI interactions)

## Before Responding
1. **Review CLAUDE.md** for:
   - Architecture and data flow
   - PHP function naming conventions (`warder_` prefix)
   - Versioning rules (four files must be updated together)
   - Build commands

2. **Check Current Context**:
   - Confirm the current branch and git status
   - Review recent commits for relevant changes

3. **Adhere to Key Principles**:
   - All PHP functions use the `warder_` prefix; options stored under `warder_options` in `wp_options`
   - Settings are deep-merged with defaults via `warder_get_merged_options()` — never read raw from DB
   - JS is bundled via webpack: run `npx webpack` after editing `src/index.js`
   - `.vibe/` directories are untracked and should remain so
   - PHP requires 8.0+; no test suite exists

## Build Commands

```bash
npm install          # Install JS dependencies
npx webpack          # Build dist/cookieconsent.bundle.js
npx webpack --watch  # Rebuild on file change
```

## Versioning (bump all four together)

- `warder-cookie-consent.php` — `Version:` header + `WARDER_VERSION` constant
- `readme.txt` — `Stable tag:` + changelog entry
- `CHANGELOG.md` — new version heading
- `package.json` — `version` field

Do **not** add a `version` field to `composer.json`.

## Git Commits

- Do not mention "Claude", "Claude Code", or AI tools in commit messages
- Do not include "Co-Authored-By: Mistral Vibe" or any AI attribution
- Use atomic commits; stage files individually or in logical groups with specific messages
- Follow standard conventional commit format (`feat:`, `fix:`, `docs:`, `refactor:`, etc.)

## Response Guidelines
- Be concise and technical
- Reference specific files and line numbers
- Use markdown formatting for code and structure
- Prioritize verification over assumptions
- When unsure, ask for clarification before acting
