# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A site-specific WordPress plugin (`EslovCustomisation\` PSR-4 namespace, `source/php/`) that
customises Eslöv's [municipio-deployment](https://github.com/municipio-se/municipio-deployment)
installation (the current Municipio platform). It holds two distinct kinds of code — keep them
separate:

- **One-time migrations** (`source/php/Migration/` + `source/php/Cli/Migrate/`) — idempotent DB
  transforms, run via WP-CLI, that move data from the site's old
  [municipio-lts-deployment](https://github.com/municipio-se/municipio-lts-deployment) (LTS) setup
  into shapes the current Municipio platform expects — meta keys, module JSON, Kirki customizer
  mods → design tokens, classic widgets → blocks, etc. Meant to run once per environment and then
  be retired; "LTS" throughout this codebase means "the old municipio-lts-deployment source data",
  not a version-support policy.
- **Runtime shims** (`source/php/Customisations/`) — permanent hooks/filters bridging unmigrated
  LTS rows or encoding a permanent site preference, registered in `App::registerInstances()`.

Log every fix (migration or shim) in `.cursor/plans/db-migration.md`.

## Commands

```bash
composer install                          # PHP deps (PSR-4 autoload → source/php/)
npm install && npm run build               # Vite build (site + module CSS/JS)
npm run dev                                 # Vite dev server
npm run watch                               # Vite build --watch
php build.php                               # composer dump-autoload + npm install/build (used by CI/deploy)
php build.php --cleanup                    # also strips dev-only files (see build.php $removables)
```

WP-CLI migration commands (run from inside `ddev`, in the plugin dir):

```bash
ddev wp eslov migrate status
ddev wp eslov migrate all --dry-run
ddev wp eslov migrate all                  # runs all `ready` migrations, in MigrationRegistry run_order
ddev wp eslov migrate <name> --dry-run     # every migrate command supports --dry-run
ddev wp eslov migrate <name> --network     # multisite: loop every blog via switch_to_blog
```

There is no test suite, linter, or CI config in this repo — do not invent commands for these.

## Architecture

### Bootstrap (`eslov-customisation.php`)

Registers a PSR-4 autoloader for `EslovCustomisation\`, requires `vendor/autoload.php` if present,
runs `Shim\MunicipioTermCacheFix::register()` immediately, instantiates `App` (which wires all
runtime shims), registers the `mod-navigation` Modularity module on `init` priority 5, and — only
when `WP_CLI` is defined — calls `CliBootstrap::register()`.

### Runtime shims: `App` + `Customisations/`

`App::registerInstances()` is the single source of truth for which shims are active — it holds a
flat array of class names, `new`s each one (guarded by `class_exists`), and every class wires its
own hooks in `__construct()`. To add a shim: create the class under `source/php/Customisations/`,
wire hooks in its constructor, then add it to the array in `App.php`. There's no autodiscovery.

### Migrations: `Cli/Migrate/*Command` → `Migration/*Migrator`

Each WP-CLI command (`source/php/Cli/Migrate/`) is a thin `AbstractMigrateCommand` subclass that
parses flags (`--dry-run`, `--post-id`, `--network`) and delegates to a pure-PHP migrator class in
`source/php/Migration/` (no WP-CLI coupling — testable/reusable independent of the CLI layer).
Commands are registered by hand in `CliBootstrap::register()` (`WP_CLI::add_command`) and must
also get an entry in `Migration/MigrationRegistry::all()` (status `scaffold`/`planned`/`ready`,
plus `run_order` once `ready` — `AllCommand` runs everything `ready` in `run_order` sequence).
Adding a migration means touching all three: the migrator class, the CLI command, and the registry
entry.

`AbstractMigrateCommand::executeAcrossSites()` is the multisite loop primitive (`--network` flag →
`switch_to_blog`/`restore_current_blog` per site); it also network-activates the plugin itself when
needed so shims/CSS apply on every subsite.

### Design tokens (`Migration/DesignTokenCorrections/`)

The most elaborate migration path. Design tokens live at runtime *only* in the `tokens` theme mod
(`theme_mods_municipio`) — files under `config/design-tokens/` and
`config/styleguide-token-patches.json` are git-tracked inputs/snapshots, never read at runtime.
`DesignTokensMigrator` runs an ordered list of `DesignTokenCorrectionInterface` classes against a
shared `DesignTokenState`, then applies `config/styleguide-token-patches.json` as a final sparse
patch layer, then (unless `--dry-run`) writes the result back with `set_theme_mod('tokens', ...)`.
Order matters: several corrections read state set by an earlier one (e.g. `HeaderTextColorCorrection`
must run after `PrimaryPaletteCorrection`). See `config/README.md` for the full apply-layer table,
known CSS-cascade gotchas that corrections alone can't fix (header buttons, search form radius,
footer link contrast), and the multisite export/snapshot workflow (`--export`).

To add a new correction: implement `DesignTokenCorrectionInterface` in
`Migration/DesignTokenCorrections/`, register it in `DesignTokensMigrator::$corrections`, and log
the fix per the README's convention.

### Custom Modularity module: `Modules/Navigation/`

A full LTS-fork Modularity module living inside this plugin rather than as its own plugin
(`mod-navigation`), registered via `modularity_register_module()` in `eslov-customisation.php` and
`/Modularity/externalViewPath`. It has its own Vite build (`vite.navigation.config.mjs` →
`source/php/Modules/Navigation/assets/dist/`) and SCSS scoped under `.modularity-mod-navigation`,
separate from the site-wide build. `Navigation.php` enqueues its compiled CSS on-page only (not
globally, unlike `SiteStyles`/`SiteScripts`).

### Blade view overrides

Three separate registration points, each with its own hook and its own directory — don't mix them
up:

| Scope | Directory | Registered via |
|-------|-----------|-----------------|
| Theme partials | `views/partials/` | `Customisations\Templates` on `Municipio/viewPaths` |
| Modularity module views | `source/php/Modules/{Name}/views/` | `/Modularity/externalViewPath` |
| ComponentLibrary components | `views/components/` | `Customisations\TimelineActiveStep` on `ComponentLibrary/ViewPaths` |

### Assets (Vite)

Two independent Vite builds/manifests, both gitignored under `assets/dist/`:

| Build | Config | Entry | Output | Enqueued by |
|-------|--------|-------|--------|-------------|
| Site CSS+JS | `vite.config.mjs` | `source/sass/site-overrides.scss`, `source/js/site.js` | `assets/dist/` | `SiteStyles`/`SiteScripts` (global, every page) |
| mod-navigation | `vite.navigation.config.mjs` | `Modules/Navigation/sass/mod-navigation.scss` | `Modules/Navigation/assets/dist/` | `Navigation::style()` (on-page only) |

Site JS (`source/js/site.js`) depends on `js-styleguidejs`. Both configs use `vite-config-factory`
for shared Vite setup.

### SMTP

`Customisations\Smtp` hooks `phpmailer_init` and is a no-op until all six `SMTP_*` constants are
defined. Configuration lives outside this repo, in `config/smtp.php` (not committed here — follow
the `config-example/*.php` pattern in the sibling `municipio-deployment` repo) and is registered in
that project's `wp-config.php` `$configFiles` list. See README.md for the full constant list.
