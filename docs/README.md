# Laravel Easy Dev Documentation

Laravel Easy Dev is a Laravel feature scaffolding toolkit that generates production-style CRUD, APIs, architecture layers, tests, OpenAPI docs, modules, and AI-ready project context from one Artisan command.

## Start Here

- [Quick Start](QUICK_START.md)
- [Command Reference](COMMAND_REFERENCE.md)
- [AI Guide](AI_GUIDE.md)
- [API Development](API_DEVELOPMENT.md)
- [Configuration](CONFIGURATION.md)
- [Advanced Usage](ADVANCED.md)
- [Examples and Use Cases](EXAMPLES_USE_CASES.md)
- [Relationship Detection](RELATIONSHIP_DETECTION.md)
- [Community](COMMUNITY.md)
- [Publishing Copy](PUBLISHING_COPY.md)

## Install

```bash
composer require anas/easy-dev:^3.1 --dev
```

## First Command

```bash
php artisan easy-dev:crud Product --api --with-repository --with-service --tests --swagger
```

## Stable Release

Laravel Easy Dev v3.1.1 is stable and ready for Laravel project use.

## Requirements

- PHP 8.1+
- Laravel 9, 10, 11, 12, or 13
- MySQL, PostgreSQL, or SQLite for schema analysis features
