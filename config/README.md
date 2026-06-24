# Styleguide token configuration

Site-specific design token overrides for the Municipio design builder.

## Storage

Corrected values are written to the WordPress theme mod `tokens` inside `theme_mods_municipio`. Shape matches the design-builder export:

```json
{
  "token": { "--color--primary": "#5b2c82" },
  "component": {
    "__general__": { "button": { "--c-button--font-weight-medium": "700" } },
    "scope:s-header": { "button": { "--c-button--color--surface-contrast": "#ffffff" } }
  }
}
```

## Three layers

| Layer | Tool | When |
|-------|------|------|
| 1 | Municipio v4.1 upgrade | Runs on theme upgrade; maps Kirki mods → tokens (incomplete) |
| 2 | `wp eslov migrate design-tokens` | Rule-based corrections from legacy Kirki mods |
| 3 | `config/styleguide-token-patches.json` | QA tweaks with no Kirki source |
| 4 | CSS in `site-overrides.scss` | Token exists but upstream SCSS ignores it, or scoped tokens cannot win cascade (see below) |

### Scoped component tokens limitation

Municipio's `DesignTokensToCssConverter` emits scoped overrides on `[data-scope*="s-header;"]`, not on `.c-button` inside the scope. Global `.c-button { --c-button--* }` from `__general__` is set **directly on the element**, which beats inherited scoped values. Header basic buttons therefore need CSS in `components/header-buttons.scss` wired to `var(--c-header--color)` — after `PrimaryPaletteCorrection` sets primary contrast tokens.

### `--c-search-form-border-radius`

Kirki `search_form_shape=100` maps to token `--c-search-form-border-radius` via `SearchFormShapeCorrection`. Municipio styleguide **does not consume** this token in component SCSS (only Kirki used to output it on `.search-form`). Site CSS in `components/search-forms.scss` applies pill radii from the token. Pair with `c-group--skip-child-normalization` on search form `@group` blades so `@group` child-normalization does not zero the submit button corners.

Search field radius cascade in `search-forms.scss` (field left + submit right use the same token):

1. `--c-search-form-border-radius` when `search_form_shape=100` (pill sites)
2. Else `calc(var(--c-field--border-radius, var(--border-radius)) * var(--base))`

`FieldBorderRadiusCorrection` writes `--c-field--border-radius` at `__general__` from legacy Kirki `field_border_radius` when `field_appearance_type=custom` (unset mod → `0`, matching LTS `calc(0/4)`). Non-pill subsites with custom fields (e.g. plus) therefore get square search corners; pill sites still use the 100px token.

### Footer link contrast

Footer surface tokens come from v4.1 `footer_background` / `footer_color_text`. Plain widget `<a>` tags use `color: var(--c-link-link-color-mix)`, which reads `--c-link--color--background-contrast` inherited from `:root` (`var(--color--background-contrast)` → `#000`) **before** the inherit fallback runs. Setting only `--inherit-color-contrast` on `.c-footer` is not enough.

Fix: `FooterLinkContrastCorrection` sets `component.__general__.footer.--c-link--color--background-contrast` from legacy `footer_color_text` (`#ffffff`). That token inherits to footer descendants and drives the link color-mix to white.

## Adding a correction (preferred)

When a value can be derived from legacy customizer data:

1. Add a class in `source/php/Migration/DesignTokenCorrections/`
2. Implement `DesignTokenCorrectionInterface`
3. Register it in `DesignTokensMigrator::$corrections`
4. Log the fix in `.cursor/plans/db-migration.md`

## Adding a patch entry

Use `styleguide-token-patches.json` only when:

- No legacy Kirki theme mod exists
- The value was confirmed in the design-builder UI after visual QA
- A correction rule would be too brittle

Document each entry in a commit message or breakage matrix row.

Run:

```bash
ddev wp eslov migrate design-tokens --dry-run
ddev wp eslov migrate design-tokens
ddev wp eslov migrate design-tokens --force
ddev wp eslov migrate design-tokens --network   # all multisite blogs
ddev wp eslov migrate all --network             # every ready migration, every blog
```

Use `--patches=/path/to/custom.json` to test alternate patch files.

**Multisite:** Theme mods and `tokens` are stored per blog (`wp_{id}_options`). Always pass `--network` after a fresh DB import so subsites get the same corrections as the main site. The command network-activates `eslov-customisation` when needed (runtime shims/CSS on subsites).
