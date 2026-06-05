# Command Cheatsheet

This file is a short command cheatsheet. For full details, see [COMMAND_REFERENCE.md](COMMAND_REFERENCE.md).

## Primary Commands

| Command | Use It For |
| --- | --- |
| `easy-dev:crud` | Generate CRUD and optional architecture layers |
| `easy-dev:make` | Interactive CRUD wizard |
| `easy-dev:dream` | Generate from a natural language prompt |
| `easy-dev:test` | Generate feature and unit test starter files |
| `easy-dev:swagger` | Generate OpenAPI JSON or YAML |
| `easy-dev:analyze` | Analyze missing layers and maintainability risks |
| `easy-dev:ai-context` | Export AI-ready project context |
| `easy-dev:snapshot` | Snapshot models, schemas, and relationships |
| `easy-dev:publish-stubs` | Publish and customize package stubs |

## Pattern Generators

```bash
php artisan easy-dev:repository Product
php artisan easy-dev:api-resource Product
php artisan easy-dev:policy Product
php artisan easy-dev:dto Product
php artisan easy-dev:observer Product --register
php artisan easy-dev:filter Product
php artisan easy-dev:enum OrderStatus --values=pending,paid,cancelled
```

## Relationships

```bash
php artisan easy-dev:sync-relations Product
php artisan easy-dev:sync-relations --all
php artisan easy-dev:add-relation Post belongsTo User
php artisan easy-dev:add-relation User hasMany Post --method=articles
```

## AI-Friendly Output

```bash
php artisan easy-dev:crud Product --api --ai
php artisan easy-dev:analyze --json
php artisan easy-dev:ai-context --pretty
php artisan easy-dev:snapshot --ai
```
