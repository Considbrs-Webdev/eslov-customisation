# Eslöv Customisation

<<<<<<< Updated upstream
Site-specific WordPress plugin that customises Eslöv's [municipio-deployment](https://github.com/municipio-se/municipio-deployment)
installation. Runtime hooks, ACF fields, Blade overrides, styles and a custom Modularity module live
here — not in the theme or in forked plugins.
=======
Site-specific WordPress plugin for Eslöv's live standard Municipio site.

**`wp eslov migrate` is frozen.** It is the record of the completed LTS cutover and already ran in production. Keep the commands. Do not add new ones, and do not re-run `migrate all` to fix bugs found after go-live.

New breakage goes in `Customisations/` (hooks, views, CSS) or as a narrow data repair that is not registered in `MigrationRegistry`. Log those fixes under Post-cutover breakage in the site repo's `.cursor/plans/db-migration.md`.
>>>>>>> Stashed changes

## Installation

```bash
cd wp-content/plugins/eslov-customisation
composer install
ddev wp plugin activate eslov-customisation
```

<<<<<<< Updated upstream
## Adding a customisation

`source/php/Customisations/` holds permanent hooks and filters that adapt Municipio and its plugins
to Eslöv's needs. A subset are legacy-data shims that display unmigrated LTS rows the new way.
=======
## WP-CLI (frozen — do not run against production to fix new bugs)

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
ddev wp eslov migrate design-tokens --export --network
ddev wp eslov migrate section-spacing --dry-run --network
ddev wp eslov migrate section-text-autop --dry-run --network
```

## The frozen migrate CLI

`source/php/Cli/Migrate/` and `source/php/Migration/` stay in the repo so the cutover can be read and, if someone explicitly asks, re-run. Running a command prints a FROZEN warning. Do not add commands for newly found bugs.

## Adding a runtime shim
>>>>>>> Stashed changes

1. Create a class in `source/php/Customisations/`.
2. Register hooks in `__construct()`.
3. Add the class to `App::registerInstances()`.

## SMTP

`Customisations\Smtp` routes outgoing mail through SMTP (via `phpmailer_init`). It is a no-op
until all six constants below are defined.

Add a `config/smtp.php` (there's no built-in one), following the pattern of
[`config-example/sentry-example.php`](https://github.com/municipio-se/municipio-deployment/blob/master/config-example/sentry-example.php)
in [municipio-deployment](https://github.com/municipio-se/municipio-deployment):

```php
<?php
// config/smtp.php

define('SMTP_HOST', '(#smtp_host#)');
define('SMTP_PORT', '(#smtp_port#)');
define('SMTP_USERNAME', '(#smtp_username#)');
define('SMTP_PASSWORD', '(#smtp_password#)');
define('SMTP_FROM', '(#smtp_from#)');
define('SMTP_FROM_NAME', '(#smtp_from_name#)');
```

...and register it in `wp-config.php`'s `$configFiles` list, alongside `sentry.php` etc. The
`(#token#)` placeholders are filled in per environment by the deploy pipeline, same as
`DB_PASSWORD` and the other secrets in `config-example/`.

This replaces the old `web/app/mu-plugins/smtp.php` mu-plugin — no mu-plugin changes are needed.

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
    Customisations/    # Runtime hooks and core module tweaks
    Modules/           # Custom Modularity modules
      Navigation/      # mod-navigation
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

## Imported-event review is disabled

Events are reviewed at the source site. The registrations for
`SubsiteImportReview`, `SubsiteImportReviewList` and `SubsiteImportReviewChanges`
are commented out in `source/php/App.php`. External Content follows its normal
publication behavior, with no additional review buttons or change-review metabox.

The handler code remains available for future reuse. There is no site option to
switch these handlers on or off; restoring them requires a code change. This
applies wherever this plugin runs. Existing pending posts are not automatically
published, and checksum-based synchronization may skip unchanged source events.

## Legacy: migration from Municipio LTS

The site was originally built on [municipio-lts-deployment](https://github.com/municipio-se/municipio-lts-deployment)
(LTS). A one-off set of WP-CLI migrations moved its data into the shape the current Municipio
platform expects (meta keys, module JSON, Kirki customizer mods → design tokens, classic widgets →
blocks). They have already been run and are kept only for reference or re-importing old databases.
"LTS" in this codebase means that old source data, not a version-support policy.

Code lives in `source/php/Migration/` (pure transform logic) and `source/php/Cli/Migrate/` (thin
WP-CLI commands). Design-token details are in [`config/README.md`](config/README.md).

### WP-CLI

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
ddev wp eslov migrate design-tokens --export --network
ddev wp eslov migrate section-spacing --dry-run --network
ddev wp eslov migrate section-text-autop --dry-run --network
```

## Adding a migration

1. Add transform logic in `source/php/Migration/` (pure PHP, no WP-CLI coupling).
2. Add `source/php/Cli/Migrate/YourCommand.php` extending `AbstractMigrateCommand`.
3. Register in `CliBootstrap::register()`.
4. Add an entry to `Migration/MigrationRegistry.php` (set `run_order` when status is `ready`).

### Adding a migration

1. Add transform logic in `source/php/Migration/` (pure PHP, no WP-CLI coupling).
2. Add `source/php/Cli/Migrate/YourCommand.php` extending `AbstractMigrateCommand`.
3. Register in `CliBootstrap::register()`.
4. Add an entry to `Migration/MigrationRegistry.php` (set `run_order` when status is `ready`).
