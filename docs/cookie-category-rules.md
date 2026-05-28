# Cookie Category Rules

This document defines the hard rules for how cookie categories behave across the admin, PHP layer, and JavaScript frontend. These rules are enforced in code and must not be changed without reviewing all three layers.

---

## Necessary category

| Property | Value | Where enforced |
|---|---|---|
| Enabled | Always `true` | `settings.php` validation, `index.js` config mapping |
| Read-only | Always `true` | `settings.php` validation, `index.js` config mapping |
| User can toggle | No | vanilla-cookieconsent `readOnly: true` |
| Admin UI | Locked — info text only, no checkboxes | `admin.php` |

**Rationale:** Strictly necessary cookies are required for the site to function. They must always be active regardless of user preference.

---

## All other categories (analytics, marketing, custom, …)

| Property | Value | Where enforced |
|---|---|---|
| Enabled | Always `false` | `settings.php` validation, `index.js` config mapping |
| Read-only | Always `false` | `settings.php` validation, `index.js` config mapping |
| User can toggle | Yes | vanilla-cookieconsent `readOnly: false` |
| Admin UI | Info text only, no checkboxes | `admin.php` |

**Rationale:** GDPR requires that non-necessary cookies are opt-in. Pre-selecting or locking optional categories is not permitted. Users must actively choose to enable them.

---

## Where each rule is enforced

### `inc/settings.php` — `warder_validate_options()`

```php
'enabled'  => $is_necessary,   // true for necessary, false for everything else
'readonly' => $is_necessary,   // true for necessary, false for everything else
```

This ensures the database never stores `enabled: true` or `readonly: true` for non-necessary categories, even if a future UI change were to expose those fields again.

### `src/index.js` — `createConfigFromSettings()`

```js
config.categories[categoryId].enabled   = (categoryId === 'necessary');
config.categories[categoryId].readOnly  = (categoryId === 'necessary');
```

This is the final safety layer. Even if the DB somehow contains stale values, the JavaScript runtime always applies the correct policy before passing the config to vanilla-cookieconsent.

### `inc/admin.php` — category settings row

The "Enabled by default" and "Read-only" checkboxes have been removed. The admin now shows:

- **Necessary:** lock icon + "Always enabled — users cannot turn this off"
- **Other:** unlock icon + "Always off by default — users can opt in"

This eliminates admin-side confusion that previously caused the bug where analytics appeared locked on the frontend.

---

## What to do if a new category is added

1. Add the category via the **Add Category** form in the admin.
2. Give it a title, description, and list the cookies it controls.
3. Do **not** try to make it enabled by default or read-only — the code enforces these as `false` automatically.
4. The category will appear in the preferences modal as an optional, off-by-default toggle.

---

## Browser cookie cache note

vanilla-cookieconsent stores accepted consent in a `cc_cookie` browser cookie. If a visitor previously accepted all cookies while a bug was present (all categories locked/on), their browser still holds the old consent state.

To reset your own browser for testing: open DevTools → Application → Cookies → delete `cc_cookie`, then reload.

New visitors who have never consented will always see the correct default state (necessary on and locked, everything else off and optional).
