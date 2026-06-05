# API Development

Laravel Easy Dev can scaffold API-first Laravel features with controllers, requests, resources, routes, tests, and OpenAPI docs.

## API CRUD

```bash
php artisan easy-dev:crud Product --api
```

Add optional layers only when your project needs them:

```bash
php artisan easy-dev:crud Product --api --with-service --with-repository --tests --swagger
```

## Generated API Files

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
      ProductCollection.php

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

## API Routes

After generation, inspect the routes:

```bash
php artisan route:list --path=products
```

For Laravel 11, 12, and 13, confirm API routing is enabled in `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

## OpenAPI / Swagger

Generate or refresh OpenAPI docs:

```bash
php artisan easy-dev:swagger Product
php artisan easy-dev:swagger Product --format=yaml
```

By default, generated OpenAPI files are written under:

```text
storage/app/easy-dev/
```

## Tests

Generate API test starter files:

```bash
php artisan easy-dev:test Product --api --feature
```

Run your application tests:

```bash
php artisan test
```

## Recommended API Recipe

```bash
php artisan easy-dev:crud Product --api --with-service --tests --swagger
```

Use `--with-repository` only when your team intentionally uses repositories.
