# Changelog

All notable changes to `anas/easy-dev` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
