# Laravel Easy Dev

Generate production-style Laravel feature structure from one Artisan command.

Laravel Easy Dev helps Laravel developers reduce repetitive setup around every model or feature by generating CRUD, APIs, Form Requests, Resources, optional Services and Repositories, Policies, DTOs, Tests, OpenAPI docs, Modules, frontend starter stubs, and AI-ready project context.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/anas/easy-dev.svg)](https://packagist.org/packages/anas/easy-dev)
[![Total Downloads](https://img.shields.io/packagist/dt/anas/easy-dev.svg)](https://packagist.org/packages/anas/easy-dev)
[![PHP Version](https://img.shields.io/packagist/php-v/anas/easy-dev.svg)](https://packagist.org/packages/anas/easy-dev)
[![License](https://img.shields.io/packagist/l/anas/easy-dev.svg)](LICENSE.md)
[![Tests](https://github.com/anasnashat/laravel-easy-dev/actions/workflows/tests.yml/badge.svg)](https://github.com/anasnashat/laravel-easy-dev/actions/workflows/tests.yml)
[![Telegram](https://img.shields.io/badge/Telegram-Community-blue?logo=telegram)](https://t.me/laraveleasydev)

```bash
composer require anas/easy-dev:^3.1 --dev

php artisan easy-dev:crud Product --api --with-repository --with-service --tests --swagger
```

> Stable release: v3.1.1

## Stop Repeating Laravel Boilerplate

Every time you start a new Laravel feature, you often repeat the same setup:

- Controller
- Form Requests
- API Resource
- Routes
- Service
- Repository
- Policy
- DTO
- Tests
- Swagger / OpenAPI docs

Laravel Easy Dev helps you generate that structure from one Artisan command, so you can focus on the actual business logic.

Instead of spending 30 to 90 minutes wiring the same structure for every model, run one Artisan command and start from a consistent, team-ready architecture.

## Demo

### CRUD in seconds

![Laravel Easy Dev quick CRUD demo](docs/assets/gifs/quick-crud.gif)

### Module and architecture scaffolding

![Laravel Easy Dev module architecture demo](docs/assets/gifs/architecture-module.gif)

### AI project analysis

![Laravel Easy Dev AI analysis demo](docs/assets/gifs/ai-analyze.gif)

## AI Guide

Laravel Easy Dev includes AI-friendly commands for coding agents:

```bash
php artisan easy-dev:ai-context --pretty
php artisan easy-dev:snapshot --ai
php artisan easy-dev:analyze --json
```

See [docs/AI_GUIDE.md](docs/AI_GUIDE.md) for token-saving workflows, safe generation rules, and prompt examples for Codex, Cursor, Claude, and ChatGPT.

## Quick Start

### 1. Install

```bash
composer require anas/easy-dev:^3.1 --dev
```

Laravel package discovery registers the service provider automatically.

### 2. Generate a Feature

```bash
php artisan easy-dev:crud Product --api --with-repository --with-service --tests --swagger
```

### 3. Check Routes

```bash
php artisan route:list --path=products
```

### 4. Run Tests

```bash
php artisan test
```

Publish the config when you want to customize paths, defaults, routes, validation rules, or module settings:

```bash
php artisan vendor:publish --tag=easy-dev-config
```

Publish stubs when you want generated code to match your team's exact style:

```bash
php artisan easy-dev:publish-stubs
```

Published stubs are placed in:

```text
resources/stubs/vendor/easy-dev/
```

## Before / After

### Before

For every model or feature, you may manually create:

- Controller
- Store request
- Update request
- API resource
- Routes
- Service class
- Repository class
- Policy
- DTO
- Tests
- Swagger docs

### After

```bash
php artisan easy-dev:crud Product --api --with-repository --with-service --tests --swagger
```

One command gives you a clean starting point that you can customize.

## Example Generated Structure

```text
app/
  Http/
    Controllers/
      Api/
        ProductApiController.php
    Requests/
      StoreProductRequest.php
      UpdateProductRequest.php
    Resources/
      ProductResource.php
  Services/
    ProductService.php
  Repositories/
    ProductRepository.php
  Policies/
    ProductPolicy.php
  DTOs/
    ProductData.php

routes/
  api.php

tests/
  Feature/
    ProductApiTest.php

storage/
  app/
    easy-dev/
      openapi.json
```

## What It Generates

Laravel Easy Dev can generate:

- Web CRUD controllers and routes
- API controllers, resources, requests, and routes
- Optional Service layer
- Optional Repository layer
- Policies
- DTOs
- Observers
- Filters
- Enums
- Feature and unit tests
- OpenAPI / Swagger docs
- Clean Architecture and DDD-style modules
- Vue, React, Inertia, and Livewire starter stubs
- AI-friendly JSON commands for project context and analysis

## Common Recipes

### API CRUD Only

```bash
php artisan easy-dev:crud Product --api
```

### API CRUD With Tests

```bash
php artisan easy-dev:crud Product --api --tests
```

### API CRUD With Service Layer

```bash
php artisan easy-dev:crud Product --api --with-service --tests
```

### Full Structure

```bash
php artisan easy-dev:crud Product --api --with-repository --with-service --with-policy --with-dto --tests --swagger
```

### Module Structure

```bash
php artisan easy-dev:crud Product --module=Catalog --architecture=clean --with-service --with-repository
```

### Generate OpenAPI Docs

```bash
php artisan easy-dev:swagger
```

### Export AI Context

```bash
php artisan easy-dev:ai-context --pretty
```

## Command Cheatsheet

| Command | Use It For |
| --- | --- |
| `easy-dev:crud` | Generate CRUD and optional layers |
| `easy-dev:make` | Interactive CRUD wizard |
| `easy-dev:test` | Generate tests |
| `easy-dev:swagger` | Generate OpenAPI docs |
| `easy-dev:analyze` | Analyze missing layers |
| `easy-dev:ai-context` | Export AI-ready project context |
| `easy-dev:snapshot` | Generate project snapshot |
| `easy-dev:publish-stubs` | Publish and customize stubs |

## When Should You Use This?

Laravel Easy Dev is useful when you are building:

- APIs
- Admin panels
- SaaS dashboards
- CRUD-heavy applications
- Modular Laravel applications
- Internal tools
- Teams that want consistent generated structure
- Projects where you want tests and documentation from the start

It is not meant to replace Laravel or hide the framework.

It gives you a clean starting point that you can customize, edit, or delete.

## Philosophy

Laravel Easy Dev does not force one architecture.

You can generate simple Laravel CRUD, or enable optional layers like:

- Services
- Repositories
- DTOs
- Policies
- Observers
- Tests
- Swagger docs
- Modules

Use only what fits your project.

The generated code is meant to be:

- Readable
- Editable
- Customizable
- Easy to delete
- Laravel-friendly

## Generated Files

Basic CRUD generation creates:

```text
app/
  Models/Product.php
  Http/Controllers/ProductController.php
  Http/Controllers/Api/ProductApiController.php
  Http/Requests/StoreProductRequest.php
  Http/Requests/UpdateProductRequest.php
  Http/Resources/ProductResource.php
  Http/Resources/ProductCollection.php
database/
  migrations/xxxx_xx_xx_xxxxxx_create_products_table.php
routes/
  web.php
  api.php
```

Optional flags add:

| Flag | Generated output |
| --- | --- |
| `--with-repository` | `app/Repositories/ProductRepository.php`, `app/Repositories/Contracts/ProductRepositoryInterface.php` |
| `--with-service` | `app/Services/ProductService.php`, `app/Services/Contracts/ProductServiceInterface.php` |
| `--with-policy` | `app/Policies/ProductPolicy.php` |
| `--with-dto` | `app/DTOs/ProductData.php` |
| `--with-observer` | `app/Observers/ProductObserver.php` |
| `--register-observer` | Adds `#[ObservedBy]` registration to the model |
| `--tests` | `tests/Feature/ProductControllerTest.php`, unit test shells |
| `--swagger` | `storage/app/easy-dev/openapi.json` |
| `--vue` | `resources/js/components/ProductsIndex.vue` |
| `--react` | `resources/js/components/ProductsIndex.jsx` |
| `--inertia` | `resources/js/Pages/Products/Index.vue` |
| `--livewire` | Livewire class and Blade view |

## Features

### API-First Mode

Use `--api` or `--api-only` to skip web controllers and focus on API resources.

```bash
php artisan easy-dev:crud Product --api --with-service --tests --swagger
```

### Optional Repository and Service Layers

Repository and Service layers are optional. Laravel Easy Dev does not force them. Enable them only when they match your project or team structure.

Generate clean separation between HTTP, business logic, and persistence:

```bash
php artisan easy-dev:crud Order --with-repository --with-service
```

### Architecture Presets

Use standard Laravel structure:

```bash
php artisan easy-dev:crud Invoice --architecture=laravel
```

Use Clean Architecture layout inside a module:

```bash
php artisan easy-dev:crud Invoice --module=Billing --architecture=clean --with-repository --with-service
```

Use DDD-style module layout:

```bash
php artisan easy-dev:crud Payment --module=Billing --architecture=ddd --with-repository --with-service
```

### Modules

Place generated files under a domain module:

```bash
php artisan easy-dev:crud Order --module=Sales --with-repository --with-service
```

Example module output:

```text
app/Modules/Sales/
  Models/Order.php
  Http/Controllers/OrderController.php
  Http/Controllers/Api/OrderApiController.php
  Http/Requests/StoreOrderRequest.php
  Http/Requests/UpdateOrderRequest.php
  Http/Resources/OrderResource.php
  Repositories/OrderRepository.php
  Repositories/Contracts/OrderRepositoryInterface.php
  Services/OrderService.php
  Services/Contracts/OrderServiceInterface.php
```

### Test Generation

Generate starter tests for a model:

```bash
php artisan easy-dev:test Product
```

Generate API, service, and repository test shells:

```bash
php artisan easy-dev:test Product --api --feature --unit --service --repository
```

### OpenAPI / Swagger

Generate a basic OpenAPI file:

```bash
php artisan easy-dev:swagger Product
```

Generate YAML:

```bash
php artisan easy-dev:swagger Product --format=yaml
```

### Project Analysis

Analyze project structure and missing layers:

```bash
php artisan easy-dev:analyze
```

Machine-readable output for AI tools:

```bash
php artisan easy-dev:analyze --json
```

Apply conservative safe fixes where available:

```bash
php artisan easy-dev:analyze --fix
```

### Relationship Management

Auto-detect relationships from database schema and migrations:

```bash
php artisan easy-dev:sync-relations --all
```

Add a relationship manually:

```bash
php artisan easy-dev:add-relation Post belongsTo User
```

### Individual Generators

Use each generator independently:

```bash
php artisan easy-dev:repository Product
php artisan easy-dev:api-resource Product
php artisan easy-dev:policy Product
php artisan easy-dev:dto Product
php artisan easy-dev:observer Product --register
php artisan easy-dev:filter Product
php artisan easy-dev:enum OrderStatus --values=pending,paid,cancelled
```

### Frontend Starters

Generate backend plus starter frontend files:

```bash
php artisan easy-dev:crud Product --inertia
php artisan easy-dev:crud Product --vue
php artisan easy-dev:crud Product --react
php artisan easy-dev:crud Product --livewire
```

These files are intentionally starter templates. They give you a consistent first screen to customize for your actual UI stack.

### AI Commands

Give AI coding agents structured project context:

```bash
php artisan easy-dev:ai-context --pretty
php artisan easy-dev:snapshot --ai
php artisan easy-dev:info Product --ai
```

All major generators support `--ai` for quiet JSON output:

```bash
php artisan easy-dev:crud Product --api --ai
```

## Customization

Publish stubs:

```bash
php artisan easy-dev:publish-stubs
```

List available stubs:

```bash
php artisan easy-dev:publish-stubs --list
```

Publish only selected stubs:

```bash
php artisan easy-dev:publish-stubs --only=controller.api,service.enhanced
```

Overwrite existing published stubs:

```bash
php artisan easy-dev:publish-stubs --force
```

Stub resolution order:

1. CLI `--stub=...`
2. published project stubs
3. `config/easy-dev.php` stub mapping
4. package default stubs

## Configuration

Publish config:

```bash
php artisan vendor:publish --tag=easy-dev-config
```

Common settings:

- output paths for models, controllers, resources, repositories, services, policies, DTOs, observers, enums, tests, factories, and migrations
- route middleware and route prefixes
- default generator options
- validation rule mapping
- relationship detection behavior
- module root path and module namespace structure

## Command Reference

Primary commands:

| Command | Purpose |
| --- | --- |
| `easy-dev:crud` | Generate CRUD and optional architecture layers |
| `easy-dev:make` | Interactive CRUD wizard |
| `easy-dev:dream` | Generate from natural language |
| `easy-dev:test` | Generate feature and unit test shells |
| `easy-dev:swagger` | Generate OpenAPI docs |
| `easy-dev:analyze` | Analyze missing layers and maintainability risks |
| `easy-dev:ai-context` | Export AI-ready project context |
| `easy-dev:snapshot` | Snapshot models, schemas, and relationships |
| `easy-dev:info` | Inspect one model |
| `easy-dev:publish-stubs` | Publish customizable stubs |

Pattern generators:

| Command | Purpose |
| --- | --- |
| `easy-dev:repository` | Repository and interface |
| `easy-dev:api-resource` | API resource and collection |
| `easy-dev:policy` | Authorization policy |
| `easy-dev:dto` | Data Transfer Object |
| `easy-dev:observer` | Model observer |
| `easy-dev:filter` | Query filter |
| `easy-dev:enum` | PHP enum |
| `easy-dev:sync-relations` | Detect and sync model relationships |
| `easy-dev:add-relation` | Add one relationship manually |

## Requirements

- PHP 8.1+
- Laravel 9, 10, 11, 12, or 13
- MySQL, PostgreSQL, or SQLite for schema analysis features

## Testing

Run the package tests:

```bash
cd packages/laravel-easy-dev
composer test
```

Current local verification:

```text
97 tests, 396 assertions
```

## Roadmap

Possible future improvements:

- Better OpenAPI schema generation from migrations
- More Pest testing support
- Filament resource generation
- Better frontend starter templates
- More module architecture presets
- More AI-friendly project analysis commands
- More customization options for generated stubs

## Community

Laravel Easy Dev is actively maintained and continuously improving.

Stay updated with:

- New releases
- Changelog updates
- Roadmap announcements
- Demo videos
- Laravel Easy Dev tips and examples

### Telegram Community

https://t.me/laraveleasydev

### GitHub Discussions

https://github.com/anasnashat/laravel-easy-dev/discussions

### GitHub Issues

https://github.com/anasnashat/laravel-easy-dev/issues

Feedback, ideas, bug reports, and feature requests are always welcome.

See [docs/COMMUNITY.md](docs/COMMUNITY.md) for the community welcome message.

## Feedback

Laravel Easy Dev is actively improving.

If you use it in a project, feedback is welcome:

- Are the generated defaults useful?
- What structure should be improved?
- What Laravel workflows should be supported next?

Open an issue or discussion on GitHub.

## Release Status

Laravel Easy Dev v3.1.1 is stable and ready for Laravel project use. Compatibility is verified across Laravel 9, 10, 11, 12, and 13, with GitHub Actions coverage for PHP 8.1, 8.2, 8.3, and 8.4 where supported.

## Contributing

Issues, discussions, pull requests, and real project feedback are welcome.

## License

The MIT License. See [LICENSE.md](LICENSE.md) for details.

## Credits

- [Anas Nashaat](https://github.com/anasnashat)
