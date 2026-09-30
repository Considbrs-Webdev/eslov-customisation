# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - 2026-09-30

### Fixed

- Tree navigation is more compact on mobile, with a small image beside the heading and tighter spacing. All child links remain visible.

## [1.0.2] - 2026-09-30

### Fixed

- Mediaflow requests are no longer blocked by CSP; shared rules for Mediaflow and Font Awesome apply to all sites where the plugin is active.

## [1.0.1] - 2026-09-29

### Changed

- Images are sharper thanks to earlier container query breakpoints.
- Downstream event review is disabled because events are now reviewed at the source.
- The Composer package is now named `considbrs-webdev/eslov-customisation` (previously `municipio-se/eslov-customisation`) following the repository ownership change. Update the name in `composer.local.json` if installing via Composer.

## [1.0.0] - 2026-09-29

### Added

- Initial production release for the standardized Municipio deployment.
- One-time WP-CLI migrations for legacy content, modules, options, fonts, design tokens, and section text.
- Runtime shims for site behaviour that stays after migration, including navigation, posts filtering, section layout, Matomo consent, SMTP, and subsite import review.
