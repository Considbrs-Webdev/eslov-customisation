# Upstream shims (temporary fixes)

Workarounds in **eslov-customisation** that exist because upstream Municipio, Modularity, or another package is missing a fix. They are not permanent site policy.

Same tracking as Piteå's `pitea-customisation/docs/upstream-shims.md`.

When you add a shim:

1. Pick a stable **`id`** (kebab-case, e.g. `municipio-custom-css-cap-null`).
2. Add **`@upstream-shim id=…`** on the class or method (see [Comment format](#comment-format)).
3. Add a row to the [registry](#registry) below.
4. Run `./scripts/list-upstream-shims.sh` and confirm the id appears in code and in this file.

When upstream ships the fix:

1. Follow **Verify removal** for that id.
2. Delete the shim code and its `App.php` registration if the whole class goes away.
3. Remove the registry row and any `@upstream-shim` tags.
4. Re-run `./scripts/list-upstream-shims.sh`.

---

## Comment format

Use a **class or method docblock**. These tags are for humans and `./scripts/list-upstream-shims.sh`.

```php
/**
 * Short summary of what this shim does.
 *
 * @upstream-shim id=municipio-custom-css-cap-null
 * @upstream-repo municipio (library/Customizer/CustomCssCapabilities.php)
 * @upstream-broken What the upstream code does wrong, and the call that hits it.
 * @upstream-fix-needed What upstream should change.
 * @remove-when Version or grep check that means this class can be deleted.
 * @verify-removal How to confirm the site still works after deletion.
 */
```

Add **`@upstream-fixed-in`** (`municipio@7.71.0` or a commit) once a release actually contains the fix.

---

## Registry

Status: **`open`** = still needed · **`watch`** = upstream fix exists; confirm the deployed version · **`pr-open`** = waiting on merge

| id | Status | Location | Upstream | What is broken | Fix needed in core | Remove when | Verify removal |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `municipio-custom-css-cap-null` | open | `source/php/Customisations/CustomCssCapability.php` | Municipio `CustomCssCapabilities::allowCustomCssEditing()` | Argument 2 is type-hinted `string`. `wp-admin/menu-header.php` calls `current_user_can()` on each submenu capability. A null capability reaches `map_meta_cap` and the TypeError blanks wp-admin after the menu. Introduced in `e6e1476bb6` (2026-08-31). Still `string` in municipio **7.70.0**. | Accept null (`mixed` or `?string`) and return the original capabilities unless the value is `edit_css`. | Deployed theme method no longer type-hints `$capability` as `string`. Grep `library/Customizer/CustomCssCapabilities.php`. Delete this class and its `App.php` line. | Main site `plugins.php` renders its body. PHP log has no `CustomCssCapabilities` TypeError. |

---

## Maintenance

```bash
cd wp-content/plugins/eslov-customisation
./scripts/list-upstream-shims.sh
```

When upgrading Municipio, grep this file for **`watch`** and **`pr-open`** rows and run **Verify removal** before deleting shims.
