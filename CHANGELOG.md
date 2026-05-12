# Changelog

All notable changes to this project are documented here.

The project follows Semantic Versioning. Versions before `1.0.0` may still adjust public APIs while the framework is tested in real plugins.

## [Unreleased]

### Added

- CI definitions for GitHub Actions and GitLab CI.
- PHPStan configuration and Composer scripts.
- PHP CS Fixer configuration and Composer scripts.
- Release checklist in `RELEASE.md`.

## [0.9.0] - 2026-05-12

### Added

- Multi-plugin bootstrap through `Wpp::boot()`, `Wpp::activate()`, `Wpp::deactivate()` and `Wpp::uninstall()`.
- Declarative providers for post types, taxonomies, routes, REST, admin pages, admin forms, admin CRUD, metaboxes, settings, assets, cron, AJAX, capabilities, lifecycle, logger and uninstall.
- REST router with route files, middleware, resource routing and validation handling.
- `FormRequest`, `Validator`, custom validation rules and WordPress `exists` rules for posts, terms and users.
- `BaseRepository` for CPT repositories.
- `ResourcePolicy` and policy helpers for REST/admin CRUD authorization.
- `ViewRenderer` with PHP views, layouts, sections, includes and stacks.
- `FieldSanitizer` shared by metaboxes and admin CRUD fields.
- CLI generator commands including `make:resource`, `make:crud`, `make:admin-crud` and `make:provider`.
- Product catalog example plugin.
- Docker test runner.
- MIT license.

### Changed

- Admin CRUD validation failures redirect back to the form with transient-backed errors and old input.
- `ResourcePolicy` reads use the WordPress `read` capability by default; returning `null` from capability methods is an explicit public-access choice.
- `ConfigValidator` validates `admin_crud` resources, fields, filters, rules and messages more aggressively.

### Fixed

- Cron cleanup on deactivation.
- Template scope isolation and view data extraction consistency.
- Route file existence validation.
- Shortcode tag validation.
- Container factory return validation.
- `MakeCommand` namespace detection now prefers the PSR-4 namespace mapped to `app/`.
