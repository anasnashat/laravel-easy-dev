# Relationship Detection Guide

Laravel Easy Dev v2 features intelligent relationship detection that analyzes your database schema and automatically generates appropriate Eloquent relationships.

## 🔍 How It Works

The relationship detection system works by:

1. **Analyzing Database Schema**: Examining foreign key constraints and column naming patterns
2. **Parsing Migration Files**: Reading your migration files for relationship clues
3. **Pattern Recognition**: Using Laravel conventions to identify relationship types
4. **Code Generation**: Creating properly formatted relationship methods

## 🔄 Supported Relationships

### 1. BelongsTo Relationships

**Detected From:**
- Foreign key columns ending in `_id`
- Explicit foreign key constraints
- Laravel `foreignId()` calls in migrations

**Examples:**
```sql
-- Database schema
CREATE TABLE posts (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255),
    user_id BIGINT,
    category_id BIGINT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

**Generated Code:**
```php
// In Post model
public function user()
{
    return $this->belongsTo(User::class);
}

public function category()
{
    return $this->belongsTo(Category::class);
}
```

**Migration Patterns Detected:**
```php
// Pattern 1: foreignId with constrained
$table->foreignId('user_id')->constrained();

// Pattern 2: foreignId with explicit table
$table->foreignId('category_id')->constrained('categories');

// Pattern 3: Manual foreign key
$table->unsignedBigInteger('user_id');
$table->foreign('user_id')->references('id')->on('users');
```

### 2. HasMany Relationships

**Detected From:**
- Reverse analysis of foreign key relationships
- Finding tables that reference the current model

**Examples:**
```sql
-- Users table
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255)
);

-- Posts table references users
CREATE TABLE posts (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Generated Code:**
```php
// In User model (reverse relationship)
public function posts()
{
    return $this->hasMany(Post::class);
}
```

### 3. BelongsToMany Relationships

**Detected From:**
- Pivot tables with exactly two foreign keys
- Table naming following Laravel conventions (`table1_table2`)

**Examples:**
```sql
-- Pivot table
CREATE TABLE post_tag (
    id BIGINT PRIMARY KEY,
    post_id BIGINT,
    tag_id BIGINT,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (tag_id) REFERENCES tags(id)
);
```

**Generated Code:**
```php
// In Post model
public function tags()
{
    return $this->belongsToMany(Tag::class);
}

// In Tag model
public function posts()
{
    return $this->belongsToMany(Post::class);
}
```

**Pivot Table Patterns:**
```php
// Standard pivot table migration
Schema::create('post_tag', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained();
    $table->foreignId('tag_id')->constrained();
    $table->timestamps(); // Optional
});
```

### 4. Polymorphic Relationships

#### MorphTo Relationships

**Detected From:**
- Columns ending in `_type` and `_id`
- Laravel `morphs()` method in migrations

**Examples:**
```sql
-- Comments table with polymorphic relationship
CREATE TABLE comments (
    id BIGINT PRIMARY KEY,
    content TEXT,
    commentable_type VARCHAR(255),
    commentable_id BIGINT,
    user_id BIGINT
);
```

**Generated Code:**
```php
// In Comment model
public function commentable()
{
    return $this->morphTo();
}
```

**Migration Patterns:**
```php
// Using morphs helper
$table->morphs('commentable'); // Creates commentable_type and commentable_id

// Manual columns
$table->string('commentable_type');
$table->unsignedBigInteger('commentable_id');
```

#### MorphMany Relationships

**Detected From:**
- Reverse analysis of morphTo relationships
- Identifying models that can be polymorphic targets

**Generated Code:**
```php
// In Post model
public function comments()
{
    return $this->morphMany(Comment::class, 'commentable');
}

// In Video model  
public function comments()
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

### 5. Self-Referencing Relationships

**Detected From:**
- Columns named `parent_id`
- Foreign keys referencing the same table

**Examples:**
```sql
-- Categories with parent/child structure
CREATE TABLE categories (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    parent_id BIGINT NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);
```

**Generated Code:**
```php
// In Category model
public function parent()
{
    return $this->belongsTo(Category::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Category::class, 'parent_id');
}
```

## 🎯 Detection Algorithms

### Foreign Key Detection

The system uses multiple detection methods:

1. **Database Schema Analysis** (Primary)
   ```php
   // For SQLite
   $foreignKeys = DB::select("PRAGMA foreign_key_list({$tableName})");
   
   // For MySQL
   $foreignKeys = DB::select("
       SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
       FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
       WHERE TABLE_NAME = ? AND CONSTRAINT_NAME != 'PRIMARY'
   ", [$tableName]);
   ```

2. **Migration File Analysis** (Fallback)
   ```php
   // Detect patterns in migration files
   preg_match_all('/\$table->foreignId\([\'"]([^\'"]+)[\'"]\)/', $content, $matches);
   ```

### Pivot Table Detection

Identifies pivot tables by:

1. **Naming Convention**: `table1_table2` format
2. **Foreign Key Count**: Exactly two foreign key columns
3. **Primary Key Structure**: Composite or separate ID

```php
// Algorithm pseudocode
function isPivotTable($tableName, $foreignKeys) {
    return count($foreignKeys) === 2 
        && preg_match('/^(\w+)_(\w+)$/', $tableName)
        && !hasTimestamps($tableName);
}
```

### Polymorphic Detection

Looks for polymorphic patterns:

1. **Type Column**: Ending in `_type`
2. **ID Column**: Corresponding column ending in `_id`
3. **Naming Convention**: `{name}_type` and `{name}_id`

```php
// Detection pattern
function isPolymorphic($columns) {
    foreach ($columns as $column) {
        if (str_ends_with($column, '_type')) {
            $baseName = str_replace('_type', '', $column);
            $idColumn = $baseName . '_id';
            
            if (in_array($idColumn, $columns)) {
                return $baseName;
            }
        }
    }
    return false;
}
```

## 🛠️ Usage Examples

### Basic Relationship Detection

```bash
# Detect relationships for a specific model
php artisan easy-dev:sync-relations Product

# Detect relationships for all models
php artisan easy-dev:sync-relations --all
```

### Complex Example: E-commerce Schema

```sql
-- Users table
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255)
);

-- Categories table (self-referencing)
CREATE TABLE categories (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    parent_id BIGINT NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);

-- Products table
CREATE TABLE products (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    price DECIMAL(10,2),
    category_id BIGINT,
    user_id BIGINT,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Orders table
CREATE TABLE orders (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    total DECIMAL(10,2),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE order_items (
    id BIGINT PRIMARY KEY,
    order_id BIGINT,
    product_id BIGINT,
    quantity INTEGER,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Product tags (many-to-many)
CREATE TABLE product_tag (
    product_id BIGINT,
    tag_id BIGINT,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (tag_id) REFERENCES tags(id)
);

-- Reviews (polymorphic)
CREATE TABLE reviews (
    id BIGINT PRIMARY KEY,
    content TEXT,
    rating INTEGER,
    reviewable_type VARCHAR(255),
    reviewable_id BIGINT,
    user_id BIGINT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Running Detection:**
```bash
php artisan easy-dev:sync-relations --all
```

**Generated Relationships:**

**User Model:**
```php
public function products()
{
    return $this->hasMany(Product::class);
}

public function orders()
{
    return $this->hasMany(Order::class);
}

public function reviews()
{
    return $this->hasMany(Review::class);
}
```

**Category Model:**
```php
public function parent()
{
    return $this->belongsTo(Category::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Category::class, 'parent_id');
}

public function products()
{
    return $this->hasMany(Product::class);
}
```

**Product Model:**
```php
public function category()
{
    return $this->belongsTo(Category::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}

public function orderItems()
{
    return $this->hasMany(OrderItem::class);
}

public function tags()
{
    return $this->belongsToMany(Tag::class);
}

public function reviews()
{
    return $this->morphMany(Review::class, 'reviewable');
}
```

**Order Model:**
```php
public function user()
{
    return $this->belongsTo(User::class);
}

public function orderItems()
{
    return $this->hasMany(OrderItem::class);
}
```

**Review Model:**
```php
public function reviewable()
{
    return $this->morphTo();
}

public function user()
{
    return $this->belongsTo(User::class);
}
```

## ⚙️ Advanced Configuration

### Polymorphic Targets

Specify which models can be polymorphic targets:

```bash
php artisan easy-dev:sync-relations Comment --morph-targets=Post,Video,Product
```

### Custom Detection Rules

You can customize detection in the config file:

```php
// config/easy-dev.php
'database' => [
    'relationship_detection' => true,
    'foreign_key_detection' => true,
    'polymorphic_detection' => true,
    'pivot_table_detection' => true,
    'self_referencing_detection' => true,
    
    // Custom patterns
    'foreign_key_patterns' => [
        '/(\w+)_id$/',
        '/(\w+)_uuid$/',
    ],
    
    'polymorphic_patterns' => [
        '/(\w+)able_type$/',
        '/(\w+)_type$/',
    ],
],
```

## 🔧 Troubleshooting

### Common Issues

1. **Relationships Not Detected**
   - Verify foreign key constraints exist
   - Check column naming follows Laravel conventions
   - Ensure migrations are properly structured

2. **Duplicate Relationships**
   - The system automatically prevents duplicates
   - Existing relationships are skipped

3. **Wrong Relationship Types**
   - Verify database schema matches intended relationships
   - Check foreign key directions

### Debug Mode

Run with verbose output to see detection process:

```bash
php artisan easy-dev:sync-relations Product --verbose
```

### Manual Override

If auto-detection doesn't work, add relationships manually:

```bash
php artisan easy-dev:add-relation Post belongsTo User
php artisan easy-dev:add-relation User hasMany Post --method=articles
```

## 🎯 Best Practices

1. **Follow Laravel Conventions**
   - Use `foreignId()` in migrations
   - Name foreign keys as `{model}_id`
   - Use Laravel naming for pivot tables

2. **Proper Migration Structure**
   ```php
   // Good
   $table->foreignId('user_id')->constrained();
   
   // Also good
   $table->foreignId('category_id')->constrained('categories');
   
   // Avoid
   $table->integer('userid'); // Non-standard naming
   ```

3. **Run Detection After Schema Changes**
   ```bash
   # After adding new migrations
   php artisan migrate
   php artisan easy-dev:sync-relations --all
   ```

4. **Verify Generated Relationships**
   - Test relationships in Tinker
   - Check for N+1 query issues
   - Ensure proper eager loading

The relationship detection system makes it easy to maintain consistent, well-structured Eloquent relationships across your entire application!
