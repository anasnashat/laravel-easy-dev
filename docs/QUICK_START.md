# Quick Start

Get Laravel Easy Dev running in a Laravel project in a few minutes.

## Install

```bash
composer require anas/easy-dev:^3.1 --dev
```

Laravel package discovery registers the service provider automatically.

## Generate Your First Feature

```bash
php artisan easy-dev:crud Product --api --with-repository --with-service --tests --swagger
```

This creates a production-style starting point for a `Product` feature.

## Check The Generated Routes

```bash
php artisan route:list --path=products
```

For Laravel 11, 12, and 13 API routes, make sure your application loads `routes/api.php` in `bootstrap/app.php`:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    );
```

## Run Tests

```bash
php artisan test
```

## Common Commands

```bash
# API CRUD only
php artisan easy-dev:crud Product --api

# API CRUD with tests
php artisan easy-dev:crud Product --api --tests

# Full structure
php artisan easy-dev:crud Product --api --with-repository --with-service --with-policy --with-dto --tests --swagger

# Module structure
php artisan easy-dev:crud Product --module=Catalog --architecture=clean --with-service --with-repository

# Generate tests for an existing model
php artisan easy-dev:test Product --api --feature --unit --service --repository

# Generate OpenAPI docs
php artisan easy-dev:swagger Product

# Export AI-ready context
php artisan easy-dev:ai-context --pretty
```

## Customize Stubs

```bash
php artisan easy-dev:publish-stubs
```

Published stubs are placed in:

```text
resources/stubs/vendor/easy-dev/
```

## Next Steps

- Read the [Command Reference](COMMAND_REFERENCE.md).
- Review [API Development](API_DEVELOPMENT.md).
- Customize defaults in [Configuration](CONFIGURATION.md).
