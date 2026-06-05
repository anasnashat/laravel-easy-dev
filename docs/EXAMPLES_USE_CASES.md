# Examples And Use Cases

Laravel Easy Dev is useful for APIs, admin panels, SaaS dashboards, internal tools, and CRUD-heavy Laravel applications.

## Product Catalog API

```bash
php artisan easy-dev:crud Product --api --with-service --tests --swagger
php artisan easy-dev:crud Category --api --with-service --tests
php artisan easy-dev:sync-relations --all
```

Use this for product catalogs, inventory tools, and admin dashboards.

## SaaS Billing Module

```bash
php artisan easy-dev:crud Invoice --module=Billing --architecture=clean --with-service --with-repository --tests
php artisan easy-dev:crud Payment --module=Billing --architecture=clean --with-service --tests
php artisan easy-dev:crud Subscription --module=Billing --architecture=clean --with-service --tests
```

Use this when billing code should be grouped under one module.

## Internal Admin Tool

```bash
php artisan easy-dev:crud Employee --with-service --with-policy --tests
php artisan easy-dev:crud Department --with-service --tests
php artisan easy-dev:crud Role --with-policy
```

Use policies when the admin workflow needs authorization boundaries.

## AI-Assisted Refactor

```bash
php artisan easy-dev:analyze --json
php artisan easy-dev:ai-context --pretty
php artisan easy-dev:snapshot --ai
```

Use these commands to give an AI coding agent compact project context.

## Frontend Starter

```bash
php artisan easy-dev:crud Product --api --inertia
php artisan easy-dev:crud Product --api --vue
php artisan easy-dev:crud Product --api --react
php artisan easy-dev:crud Product --livewire
```

These starter files are intentionally simple so your team can adapt them.

## Full Feature Example

```bash
php artisan easy-dev:crud Order --api --with-repository --with-service --with-policy --with-dto --with-observer --register-observer --tests --swagger
```

This is useful when you want a complete starting structure for a larger feature.
