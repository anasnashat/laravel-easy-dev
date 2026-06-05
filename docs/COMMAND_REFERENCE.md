# Laravel Easy Dev Command Reference

Complete reference for Laravel Easy Dev v3.1.1 commands, options, generated files, and AI-friendly workflows.

## Primary Commands

### `easy-dev:crud`

Generate CRUD and optional architecture layers from one non-interactive command.

```bash
php artisan easy-dev:crud Product
```

Common options:

| Option | Purpose |
| --- | --- |
| `--api` | Alias for API-only generation |
| `--api-only` | Generate API controller/resources only |
| `--web-only` | Generate web controller/routes only |
| `--with-repository` | Generate optional Repository layer |
| `--with-service` | Generate optional Service layer |
| `--with-policy` | Generate authorization policy |
| `--with-dto` | Generate Data Transfer Object |
| `--with-observer` | Generate model observer |
| `--register-observer` | Register the generated observer on the model |
| `--tests` | Generate starter tests |
| `--swagger` | Generate/update OpenAPI docs |
| `--architecture=laravel|clean|ddd` | Select architecture layout |
| `--module=NAME` | Generate inside `app/Modules/NAME` |
| `--inertia`, `--livewire`, `--vue`, `--react` | Generate frontend starter files |
| `--dry-run` | Preview files without writing |
| `--force` | Overwrite supported generated files |
| `--stub=NAME_OR_PATH` | Use a custom stub |
| `--path=DIR` | Use a custom output path |
| `--ai` | Output machine-friendly JSON where supported |

Examples:

```bash
php artisan easy-dev:crud Product --api --tests --swagger
php artisan easy-dev:crud Order --with-repository --with-service
php artisan easy-dev:crud Invoice --module=Billing --architecture=clean --with-service --with-repository
php artisan easy-dev:crud Product --api --with-service --inertia
```

### `easy-dev:make`

Interactive CRUD wizard.

```bash
php artisan easy-dev:make Product --interactive
```

Use this when you want guided prompts. Use `easy-dev:crud` when you want repeatable commands for scripts, docs, or AI agents.

### `easy-dev:dream`

Generate from a natural language prompt.

```bash
php artisan easy-dev:dream "Create product with name:string price:decimal stock:integer" --dry-run
```

### `easy-dev:help`

Show available commands and examples.

```bash
php artisan easy-dev:help
php artisan easy-dev:help --examples
```

## Pattern Generators

### `easy-dev:repository`

```bash
php artisan easy-dev:repository Product
php artisan easy-dev:repository Product --without-interface
```

### `easy-dev:api-resource`

```bash
php artisan easy-dev:api-resource Product
php artisan easy-dev:api-resource Product --collection
```

### `easy-dev:policy`

```bash
php artisan easy-dev:policy Product
```

### `easy-dev:dto`

```bash
php artisan easy-dev:dto Product
```

### `easy-dev:observer`

```bash
php artisan easy-dev:observer Product
php artisan easy-dev:observer Product --register
```

### `easy-dev:filter`

```bash
php artisan easy-dev:filter Product
```

### `easy-dev:enum`

```bash
php artisan easy-dev:enum OrderStatus --values=pending,paid,cancelled
```

### `easy-dev:test`

```bash
php artisan easy-dev:test Product
php artisan easy-dev:test Product --api --feature --unit --service --repository
```

### `easy-dev:swagger`

```bash
php artisan easy-dev:swagger Product
php artisan easy-dev:swagger Product --format=yaml
```

## Relationship Commands

### `easy-dev:sync-relations`

```bash
php artisan easy-dev:sync-relations Product
php artisan easy-dev:sync-relations --all
php artisan easy-dev:sync-relations Comment --morph-targets=Post,Video
```

### `easy-dev:add-relation`

```bash
php artisan easy-dev:add-relation Post belongsTo User
php artisan easy-dev:add-relation User hasMany Post --method=articles --foreign-key=author_id
php artisan easy-dev:add-relation User belongsToMany Role --pivot-table=role_user
```

## AI Commands

### `easy-dev:ai-context`

```bash
php artisan easy-dev:ai-context --pretty
```

### `easy-dev:snapshot`

```bash
php artisan easy-dev:snapshot
php artisan easy-dev:snapshot --ai
```

### `easy-dev:analyze`

```bash
php artisan easy-dev:analyze
php artisan easy-dev:analyze --json
php artisan easy-dev:analyze --fix
```

### `easy-dev:info`

```bash
php artisan easy-dev:info Product
php artisan easy-dev:info Product --ai
```

## Stub Publishing

```bash
php artisan easy-dev:publish-stubs
php artisan easy-dev:publish-stubs --list
php artisan easy-dev:publish-stubs --only=controller.api,service.enhanced
php artisan easy-dev:publish-stubs --force
php artisan easy-dev:publish-stubs --list --ai
```

Published stubs are placed in:

```text
resources/stubs/vendor/easy-dev/
```

## Default Output Paths

| Component | Default Path |
| --- | --- |
| Model | `app/Models/` |
| Web Controller | `app/Http/Controllers/` |
| API Controller | `app/Http/Controllers/Api/` |
| Form Requests | `app/Http/Requests/` |
| API Resources | `app/Http/Resources/` |
| Repository | `app/Repositories/` |
| Repository Contract | `app/Repositories/Contracts/` |
| Service | `app/Services/` |
| Service Contract | `app/Services/Contracts/` |
| Policy | `app/Policies/` |
| DTO | `app/DTOs/` |
| Observer | `app/Observers/` |
| Filter | `app/Filters/` |
| Enum | `app/Enums/` |
| Feature Test | `tests/Feature/` |
| Unit Test | `tests/Unit/` |
| OpenAPI | `storage/app/easy-dev/` |

When `--module=Billing` is used, generated application files are placed under `app/Modules/Billing/` with matching namespaces.
