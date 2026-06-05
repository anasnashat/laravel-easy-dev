# Advanced Usage

Use these features when a project needs more structure than basic Laravel CRUD.

## Optional Repository And Service Layers

Repository and Service layers are optional. Laravel Easy Dev does not force them. Enable them only when they match your project or team structure.

```bash
php artisan easy-dev:crud Order --with-repository --with-service
```

## Architecture Presets

```bash
# Standard Laravel layout
php artisan easy-dev:crud Invoice --architecture=laravel

# Clean Architecture layout inside a module
php artisan easy-dev:crud Invoice --module=Billing --architecture=clean --with-repository --with-service

# DDD-style module layout
php artisan easy-dev:crud Payment --module=Billing --architecture=ddd --with-repository --with-service
```

## Modules

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
  Services/OrderService.php
```

## Frontend Starters

```bash
php artisan easy-dev:crud Product --inertia
php artisan easy-dev:crud Product --vue
php artisan easy-dev:crud Product --react
php artisan easy-dev:crud Product --livewire
```

These files are starter templates. Customize them for your UI stack.

## AI Workflows

```bash
php artisan easy-dev:ai-context --pretty
php artisan easy-dev:snapshot --ai
php artisan easy-dev:info Product --ai
php artisan easy-dev:crud Product --api --ai
```

## Dry Run

Preview a generation without writing files:

```bash
php artisan easy-dev:crud Product --with-service --dry-run
```

## Natural Language Scaffolding

```bash
php artisan easy-dev:dream "Create product catalog with name:string price:decimal stock:integer connected to categories" --dry-run
```
