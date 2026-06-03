# Changelog

All notable changes to `anas/easy-dev` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [3.1.0-rc3] - 2026-06-03

### Added
- Added `easy-dev:test` for feature and unit test starter generation.
- Added `easy-dev:swagger` for dependency-light OpenAPI JSON/YAML generation.
- Added `easy-dev:analyze` for project structure and maintainability checks.
- Added GitHub Actions CI matrix for PHP and Laravel compatibility checks.
- Added CRUD flags for `--tests`, `--swagger`, `--inertia`, `--livewire`, `--vue`, and `--react`.
- Added `--architecture=laravel|clean|ddd` support for publish-facing architecture selection.
- Added observer auto-registration via `easy-dev:observer --register` and CRUD `--register-observer`.
- Added frontend starter stubs for Inertia Vue, Vue, React, and Livewire.
- Added test generation stubs for feature, model, service, and repository tests.
- Added README GIF demos for CRUD, module architecture, and AI analysis workflows.
- Added publish-readiness regression tests for the new command surface.

### Changed
- `easy-dev:crud --api` now behaves as an alias for API-only generation.
- CRUD generation now writes web/API resource routes through the route writer.
- Stub publishing now recursively includes nested stubs and supports `--force`.
- Updated README to answer the package value proposition, installation, quick example, generated files, and advanced features quickly.
- Updated Composer metadata and branch alias for v3 development.
- Refreshed development dependencies and lock file to remove security audit advisories.

### Fixed
- Fixed enhanced controller stubs leaving raw placeholders such as `{{ modelName }}` and `{{ pluralModelNameKebab }}` in generated controllers.
- Fixed default stub config so basic CRUD uses the normal controller stubs unless a service/enhanced controller is explicitly selected.
- Fixed GitHub Actions compatibility matrix so EOL Laravel majors can still be tested without failing dependency installation on framework security advisories.
- Fixed module test generation so tests stay in PSR-4-compatible `tests/Feature` and `tests/Unit` roots unless a custom path is explicitly provided.

### Verified
- `composer validate --strict` passes.
- `composer test` passes with 97 tests and 396 assertions.
- `composer audit` reports no security vulnerability advisories.
- Manual compatibility matrix passed for Laravel 9, 10, 11, 12, and 13 on PHP 8.4.11.

---

## [3.0.0] - 2026-05-23

### Added
- **Zero-Configuration Autodiscovery Engine**: Recursive model scanning engine that locates Eloquent models anywhere in the directory tree (e.g. standard `app/Models`, clean DDD `app/Modules/*/Domain/Models`, or custom nested paths) without requiring manual configuration.
- **Clean Architecture (DDD) Presets**: Added `--preset=clean` flag supporting full 4-layer Domain-Driven Design layout (`Domain`, `Application`, `Infrastructure`, `Presentation`) inside module contexts.
- **Advanced Modular Scaffolding**: Direct scaffolding in modular systems via the `--module={ModuleName}` flag.
- **AI-Native commands**:
  - `easy-dev:ai-context`: Generates a complete architecture-aware context map for LLM consumption.
  - `easy-dev:snapshot`: Produces a complete SQL/database schema snapshot to aid LLMs in context-building.
  - `easy-dev:info`: Detailed layout of current project architecture, modules, models, and service bindings.
- **Silent JSON Mode**: Added `--ai` option to every Artisan command for clean JSON stdout, eliminating interactive prompts for easy integration in IDE agents and AI workflows.
- **Natural Language Scaffolding (NLP Dream)**: Upgraded `easy-dev:dream` to support complex entity relationship descriptions, intelligent column mapping, and interactive model generation.
- **Total Signature Harmony**: Refactored controller, service, and repository stubs so methods, types, and DTOs match flawlessly from controller down to storage layers.
- **Automated Service Provider Wiring**: Automatically registers new modules and handles interface-to-implementation binding in Laravel service providers during CRUD generation.
- **Beautiful Interactive Help**: Fully redesigned `easy-dev:help` and `easy-dev:info` output using rich ANSI-styled tables, category boxes, and colors.
- **New stub files**: Expanded stubs count to 34 comprehensive templates, covering API/Web/Service controllers, DTOs, Observers, Filters, Policies, and Eloquent relation definitions.

### Changed
- Refactored `src/Services/FileGenerator.php` for dynamic namespace rewriting and PSR-4 resolution.
- Updated `composer.json` to fully support Laravel `^9.0`, `^10.0`, `^11.0`, `^12.0`, and PHP `^8.1` up to PHP `^8.4`.
- Reorganized `config/easy-dev.php` configuration blocks with comprehensive comments.

### Security
- Verified zero external HTTP requests, zero tracking, and zero hardcoded secrets.

---

## [2.1.0] - 2025-11-15

### Added
- Interactive prompts for CRUD generation in `easy-dev:crud`.
- Flexible Repository & Service layers optional generation.
- Integrated standard Laravel policy generation inside scaffolding.

---

## [1.0.0] - 2025-06-01

### Added
- Initial release of Easy Dev package.
- Basic CRUD generator commands for standard MVC layout.
- Configuration for stub customization.
