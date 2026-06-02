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
ddev wp eslov migrate status --dry-run
```

Future commands (add as migration work proceeds):

```bash
ddev wp eslov migrate meta-keys --dry-run
ddev wp eslov migrate modules --post-id=123
ddev wp eslov migrate options
```

## Adding a migration

1. Add transform logic in `source/php/Migration/` (pure PHP, no WP-CLI coupling).
2. Add `source/php/Cli/Migrate/YourCommand.php` extending `AbstractMigrateCommand`.
3. Register in `CliBootstrap::register()`.
4. Add a row to the migration registry in `StatusCommand` (or drive from a shared list).

## Adding a runtime shim

1. Create a class in `source/php/Customisations/`.
2. Register hooks in `__construct()`.
3. Add the class to `App::registerInstances()`.

## Blade view overrides

Place templates under `views/`. `Customisations\Templates` registers that path on `Municipio/viewPaths`.

## Assets (future)

When style overrides are needed, add Vite + `Helpers/CacheBust` (see Piteå `pitea-customisation`) and wire `enqueueAssets()` in `App.php`. Placeholders: `source/sass/`, `source/js/`.

## License

MIT
