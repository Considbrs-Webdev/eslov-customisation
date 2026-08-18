# Eslöv Customisation

Site-specific WordPress plugin for the Eslöv municipio-deployment migration. All Eslöv-specific DB transforms and runtime shims live here — not in the theme or LTS plugin ports.

## Fix types

| Type | Purpose | Lifecycle |
|------|---------|-----------|
| **One-time migration** | Rewrite DB: meta keys, module JSON, options | `wp eslov migrate …` — idempotent, `--dry-run` |
| **Runtime shim** | Bridge unmigrated rows or permanent site preference | Hook/filter in `Customisations/` |

Log every fix in `.cursor/plans/db-migration.md`.

## Installation

```bash
cd wp-content/plugins/eslov-customisation
composer install
ddev wp plugin activate eslov-customisation
```

## WP-CLI

```bash
ddev wp eslov migrate status
ddev wp eslov migrate all --dry-run
ddev wp eslov migrate all
```

Individual commands (also run by `migrate all` when status is `ready`):

```bash
ddev wp eslov migrate meta-keys --dry-run
ddev wp eslov migrate modules --post-id=123
ddev wp eslov migrate options
ddev wp eslov migrate fonts --dry-run
ddev wp eslov migrate fonts --network
ddev wp eslov migrate section-spacing --dry-run --network
```

## Adding a migration

1. Add transform logic in `source/php/Migration/` (pure PHP, no WP-CLI coupling).
2. Add `source/php/Cli/Migrate/YourCommand.php` extending `AbstractMigrateCommand`.
3. Register in `CliBootstrap::register()`.
4. Add an entry to `Migration/MigrationRegistry.php` (set `run_order` when status is `ready`).

## Adding a runtime shim

1. Create a class in `source/php/Customisations/`.
2. Register hooks in `__construct()`.
3. Add the class to `App::registerInstances()`.

## Plugin layout (Piteå-style)

```
source/
  sass/                # Site-wide CSS overrides (enqueued globally)
    site-overrides.scss
    components/        # Per-component override partials
  js/                  # Site-wide JS (enqueued globally)
    site.js            # Vite entry
    components/        # Per-feature modules imported by site.js
  php/
    AcfFields/         # ACF field groups (e.g. ModNavigationFields)
    Cli/               # WP-CLI migration commands
    Customisations/    # Runtime hooks and core module tweaks
    Migration/         # Pure transform logic for CLI
    Modules/           # Custom Modularity modules
      Navigation/      # mod-navigation (LTS fork)
        Navigation.php
        sass/          # Module SCSS source
        assets/dist/   # Module built CSS + manifest.json
        views/         # mod-navigation.blade.php + navigation/*
views/
  partials/            # Theme blade overrides (taglist, child buttons)
assets/dist/           # Site CSS + JS build output + manifest.json (gitignored)
```

Custom Modularity modules register in `eslov-customisation.php` (`init` priority 5), same pattern as Piteå `AccButtons`.

## Blade view overrides

- **Theme:** `views/partials/` — registered on `Municipio/viewPaths` via `Customisations\Templates`.
- **Modules:** `source/php/Modules/{Name}/views/` — registered on `/Modularity/externalViewPath`.
- **Components:** `views/components/` — registered on `ComponentLibrary/ViewPaths` via `Customisations\TimelineActiveStep` (dated `@timeline` uses sequential layout + card `meta` for dates).

## Assets (Vite)

Site CSS/JS and module styles use **separate Vite builds** and manifests.
Built files land in `assets/dist/` (gitignored) — run `npm run build` locally or via `build.php`.

```bash
cd wp-content/plugins/eslov-customisation
npm install
npm run build
```

| Build | Config | Output | Enqueued by |
|-------|--------|--------|-------------|
| Site CSS + JS | `vite.config.mjs` | `assets/dist/` | `SiteStyles` + `SiteScripts` (global) |
| mod-navigation | `vite.navigation.config.mjs` | `Modules/Navigation/assets/dist/` | `Navigation::style()` (on-page only) |

- Site CSS: `source/sass/site-overrides.scss` → imports `components/*`
- Site JS: `source/js/site.js` → imports `components/*` (depends on `js-styleguidejs`)
- Module SCSS: beside the module (`Modules/Navigation/sass/mod-navigation.scss`), scoped under `.modularity-mod-navigation`

## License

MIT
