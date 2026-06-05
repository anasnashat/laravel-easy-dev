# Configuration

Laravel Easy Dev works without configuration. Publish the config only when you want to change default paths, routes, validation rules, module settings, or stub mappings.

## Publish Config

```bash
php artisan vendor:publish --tag=easy-dev-config
```

This creates:

```text
config/easy-dev.php
```

## Publish Stubs

```bash
php artisan easy-dev:publish-stubs
```

Published stubs are placed in:

```text
resources/stubs/vendor/easy-dev/
```

## Stub Commands

```bash
# List package stubs
php artisan easy-dev:publish-stubs --list

# Publish selected stubs
php artisan easy-dev:publish-stubs --only=controller.api,service.enhanced

# Overwrite existing published stubs
php artisan easy-dev:publish-stubs --force
```

## Stub Resolution Order

1. CLI `--stub=...`
2. Published project stubs
3. `config/easy-dev.php` stub mapping
4. Package default stubs

## Common Settings

You can configure:

- output paths for models, controllers, resources, repositories, services, policies, DTOs, observers, enums, tests, factories, and migrations
- route middleware and prefixes
- default generator options
- validation rule mapping
- relationship detection behavior
- module root path and module namespace structure

## Custom Paths

Most generator commands support `--path` for custom output:

```bash
php artisan easy-dev:repository Product --path=app/Domains/Catalog/Repositories
```

## Modules

Use `--module` to place generated files in a domain module:

```bash
php artisan easy-dev:crud Product --module=Catalog --with-service --with-repository
```

The default module root is:

```text
app/Modules/
```
