# Relationship Detection

Laravel Easy Dev can detect and add Eloquent relationships from your database schema and migrations.

## Sync One Model

```bash
php artisan easy-dev:sync-relations Product
```

## Sync All Models

```bash
php artisan easy-dev:sync-relations --all
```

The command scans models, migrations, and database schema where available.

## Add One Relationship Manually

```bash
php artisan easy-dev:add-relation Post belongsTo User
php artisan easy-dev:add-relation User hasMany Post --method=articles
```

## Common Relationship Types

- `belongsTo`
- `hasOne`
- `hasMany`
- `belongsToMany`
- `morphTo`
- `morphMany`

## Polymorphic Targets

When polymorphic targets should be explicit:

```bash
php artisan easy-dev:sync-relations Comment --morph-targets=Post,Video
```

## Workflow

1. Create or update migrations.
2. Run migrations when possible.
3. Run `php artisan easy-dev:sync-relations --all`.
4. Review the generated model methods.
5. Commit only the relationships you want to keep.

## Safety

Laravel Easy Dev skips existing relationship methods to avoid duplicate methods. Always review generated model changes before publishing them in an application.
