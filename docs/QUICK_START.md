# Quick Start Guide

Get up and running with Laravel Easy Dev v2 in under 5 minutes!

## 🚀 Installation

### 1. Install the Package
```bash
composer require anas/easy-dev
```

### 2. Verify Installation
```bash
php artisan easy-dev:help
```

You should see a beautiful help interface!

## ⚡ Your First CRUD

### 1. Create a Model
```bash
php artisan make:model Product -m
```

### 2. Set Up Migration
```php
// database/migrations/xxxx_create_products_table.php
public function up()
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->decimal('price', 8, 2);
        $table->integer('stock')->default(0);
        $table->foreignId('category_id')->constrained();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}
```

### 3. Run Migration
```bash
php artisan migrate
```

### 4. Generate Complete CRUD
```bash
php artisan easy-dev:make Product --interactive
```

Follow the interactive prompts to configure your CRUD generation.

### 5. Auto-Detect Relationships
```bash
php artisan easy-dev:sync-relations --all
```

## 🎯 What You Get

After running the commands above, you'll have:

- ✅ **ProductController** with all CRUD methods
- ✅ **StoreProductRequest** and **UpdateProductRequest** for validation
- ✅ **ProductRepository** and **ProductRepositoryInterface** (if selected)
- ✅ **ProductService** and **ProductServiceInterface** (if selected)
- ✅ **ProductResource** and **ProductCollection** for API responses
- ✅ **Web and API routes** automatically registered
- ✅ **Feature and Unit tests** for your controller
- ✅ **Model relationships** automatically detected and added

## 🎮 Interactive Mode

For a guided experience, use interactive mode:

```bash
php artisan easy-dev:make Product --interactive
```

This will walk you through:
1. Selecting generation options
2. Configuring Repository and Service patterns
3. Setting up API endpoints
4. Customizing validation rules
5. Reviewing generated files

## 🔄 Next Steps

1. **Customize Validation**: Edit the generated Form Request classes
2. **Add Business Logic**: Implement your business rules in Service classes
3. **Customize Views**: Create Blade templates for your web routes
4. **Test Your API**: Use the generated API endpoints
5. **Add More Models**: Repeat the process for other entities

## 📚 Common Commands

```bash
# Generate with Repository pattern
php artisan easy-dev:make Product --with-repository

# Generate with Service layer
php artisan easy-dev:make Product --with-service

# Generate API-only CRUD
php artisan easy-dev:make Product --api-only

# Generate web-only CRUD
php artisan easy-dev:make Product --web-only

# Add a specific relationship
php artisan easy-dev:add-relation Post belongsTo User

# Generate repository for existing model
php artisan easy-dev:repository Product
```

## 🎨 Beautiful UI

Laravel Easy Dev v2 features a beautiful command-line interface with:
- 🌈 Colorful output
- 📊 Progress bars
- ✨ Interactive prompts
- 📋 Summary reports

Try the demo:
```bash
php artisan easy-dev:demo-ui
```

## 🛟 Need Help?

- Run `php artisan easy-dev:help` for detailed command information
- Check the [Complete Documentation](COMPLETE_DOCUMENTATION.md)
- Visit [GitHub Issues](https://github.com/anasnashat/laravel-easy-dev/issues) for support

**Happy Coding! 🚀**
