---
name: release
description: >-
  Cut a new version of the eslov-customisation plugin — bump the version,
  update CHANGELOG.md, commit, tag, and publish a GitHub release. Use when
  the user asks to release, cut a version, bump the version, or tag a release.
---

# Cutting a release

A release bumps the version, writes a changelog entry, tags `main`, and publishes a GitHub release. It does not deploy the site. Production keeps running the previous tag until `eslov-se-new` pins the new version in `composer.local.json`.

Version lives in **three** places and must move together:

- `eslov-customisation.php` — `* Version: X.Y.Z` header and `ESLOV_CUSTOMISATION_VERSION`
- `package.json` — `"version": "X.Y.Z"`
- `package-lock.json` — root `"version"` and `packages[""].version`

Leave `composer.json` without a `version` field. Composer resolves the version from the git tag.

Built assets stay gitignored (`**/assets/dist/`). The municipio-deployment build runs this plugin's `build.php` and compiles them. Do not commit `assets/dist/`.

There is no test suite. Do not invent `composer test` or CI steps.

## 1. Figure out the next version

```bash
git fetch origin
git checkout main
git pull --ff-only origin main
git fetch --tags
git describe --tags --abbrev=0
git log --oneline <last-tag>..main
```

Semver: bug fixes → patch, backward-compatible features → minor, breaking change (renamed hook, option, REST route, or CLI command, or a migration production must run before the new code is safe) → major. When in doubt, ask.

## 2. Draft the changelog entry — confirm before writing

Summarize unreleased commits into user-facing bullets. Match existing `CHANGELOG.md` tone: "Fixed X happening when Y", not class or method names. Group under `### Added` / `### Changed` / `### Fixed`. Show the draft and the version number before writing files.

Skip this pause only when the user already named the version and asked for the full release in the same request.

## 3. Apply the version bump

Set both version strings in `eslov-customisation.php`.

```bash
npm version <X.Y.Z> --no-git-tag-version --allow-same-version
```

Insert the confirmed entry at the top of `CHANGELOG.md`, after the header, before the previous release:

```markdown
## [X.Y.Z] - YYYY-MM-DD

### Fixed
- ...
```

## 4. Verify

```bash
php -l eslov-customisation.php
```

Confirm the three version locations and `ESLOV_CUSTOMISATION_VERSION` all equal `X.Y.Z`, and that `composer.json` still has no `version` field.

## 5. Commit

Behavior changes belong in their own commits before the release starts. The release commit touches only the version files, `CHANGELOG.md`, and this skill when the skill itself changed:

```bash
git add eslov-customisation.php package.json package-lock.json CHANGELOG.md
git commit -m "Release X.Y.Z"
```

## 6. Push and tag — confirm before this step

This publishes a release on the shared remote. Confirm before running it, unless the user already asked for the full release including tag and publish.

`main` is the integration branch. Tag that commit. Do not create a `dev` branch.

```bash
git push origin main

git tag -a X.Y.Z -m "Release X.Y.Z"
git push origin X.Y.Z
```

If `main` has diverged from `origin/main`, stop. Do not force-push.

## 7. Publish the GitHub release

Title `vX.Y.Z`. Body is the changelog entry, starting with `## [X.Y.Z] - YYYY-MM-DD`.

```bash
gh release create X.Y.Z --repo Considbrs-Webdev/eslov-customisation \
  --title "vX.Y.Z" \
  --notes "$(cat <<'EOF'
## [X.Y.Z] - YYYY-MM-DD

### Fixed
- ...
EOF
)"
```

## 8. Pin the deployment

Tagging does not change production. `eslov-se-new` installs this plugin from `composer.local.json`.

When the user wants the site to run this release, set:

```json
"considbrs-webdev/eslov-customisation": "X.Y.Z"
```

Do that only when asked. Until then, say which constraint is still installed (`dev-main` or the previous tag) and what to change.
