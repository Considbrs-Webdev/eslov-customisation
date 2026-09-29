# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-09-29

### Changed

- Bilder blir skarpare tack vare tidigare brytpunkter för container queries.
- Den nedströms granskningen av event är avstängd, eftersom den nu hanteras vid källan.
- Composer-paketet heter nu `considbrs-webdev/eslov-customisation` (tidigare `municipio-se/eslov-customisation`) efter byte av repository-ägare. Uppdatera namnet i `composer.local.json` om du installerar via Composer.

## [1.0.0] - 2026-09-29

### Added

- Initial production release for the standardized Municipio deployment.
- One-time WP-CLI migrations for legacy content, modules, options, fonts, design tokens, and section text.
- Runtime shims for site behaviour that stays after migration, including navigation, posts filtering, section layout, Matomo consent, SMTP, and subsite import review.
