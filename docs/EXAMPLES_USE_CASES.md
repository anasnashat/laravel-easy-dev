# Examples & Use Cases

Real-world examples and use cases demonstrating Laravel Easy Dev v2 capabilities.

## 🛍️ E-commerce Platform

Building a complete e-commerce platform with product catalog, order management, and customer reviews.

### Database Schema

```sql
-- Users table
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Categories table (hierarchical)
CREATE TABLE categories (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    image VARCHAR(255),
    parent_id BIGINT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);

-- Brands table
CREATE TABLE brands (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    logo VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    short_description TEXT,
    sku VARCHAR(100) UNIQUE,
    price DECIMAL(10,2),
    sale_price DECIMAL(10,2) NULL,
    stock INTEGER DEFAULT 0,
    low_stock_threshold INTEGER DEFAULT 10,
    weight DECIMAL(8,2),
    dimensions JSON,
    images JSON,
    category_id BIGINT,
    brand_id BIGINT,
    user_id BIGINT, -- Created by
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Product attributes (for variants like size, color)
CREATE TABLE attributes (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    type ENUM('text', 'number', 'select', 'multiselect'),
    is_required BOOLEAN DEFAULT FALSE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Product attribute values
CREATE TABLE product_attributes (
    id BIGINT PRIMARY KEY,
    product_id BIGINT,
    attribute_id BIGINT,
    value TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES attributes(id)
);

-- Tags for products
CREATE TABLE tags (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    color VARCHAR(7) DEFAULT '#007bff',
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Product tags pivot table
CREATE TABLE product_tag (
    product_id BIGINT,
    tag_id BIGINT,
    PRIMARY KEY (product_id, tag_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id BIGINT PRIMARY KEY,
    order_number VARCHAR(100) UNIQUE,
    user_id BIGINT,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2),
    tax_amount DECIMAL(10,2),
    shipping_amount DECIMAL(10,2),
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'USD',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(100),
    notes TEXT,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE order_items (
    id BIGINT PRIMARY KEY,
    order_id BIGINT,
    product_id BIGINT,
    quantity INTEGER,
    price DECIMAL(10,2),
    total DECIMAL(10,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Reviews table (polymorphic)
CREATE TABLE reviews (
    id BIGINT PRIMARY KEY,
    content TEXT,
    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
    reviewable_type VARCHAR(255),
    reviewable_id BIGINT,
    user_id BIGINT,
    is_approved BOOLEAN DEFAULT FALSE,
    helpful_count INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Wishlists table
CREATE TABLE wishlists (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    product_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- Coupons table
CREATE TABLE coupons (
    id BIGINT PRIMARY KEY,
    code VARCHAR(100) UNIQUE,
    type ENUM('fixed', 'percentage'),
    value DECIMAL(10,2),
    minimum_amount DECIMAL(10,2) DEFAULT 0,
    usage_limit INTEGER NULL,
    used_count INTEGER DEFAULT 0,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Step-by-Step Implementation

#### 1. Create Models and Migrations

```bash
# Create all models with migrations
php artisan make:model Category -m
php artisan make:model Brand -m
php artisan make:model Product -m
php artisan make:model Attribute -m
php artisan make:model ProductAttribute -m
php artisan make:model Tag -m
php artisan make:model Order -m
php artisan make:model OrderItem -m
php artisan make:model Review -m
php artisan make:model Wishlist -m
php artisan make:model Coupon -m

# Run migrations
php artisan migrate
```

#### 2. Generate Complete CRUD Operations

```bash
# Generate CRUD with all patterns for main entities
php artisan easy-dev:make Category --with-repository --with-service --interactive
php artisan easy-dev:make Brand --with-repository --with-service --interactive
php artisan easy-dev:make Product --with-repository --with-service --interactive

# Generate API-only CRUD for order management
php artisan easy-dev:make Order --api-only --with-service --interactive
php artisan easy-dev:make OrderItem --api-only --with-repository

# Generate simple CRUD for supporting entities
php artisan easy-dev:make Attribute --with-repository
php artisan easy-dev:make Tag --interactive
php artisan easy-dev:make Review --with-service
php artisan easy-dev:make Coupon --with-repository --with-service
```

#### 3. Auto-Detect All Relationships

```bash
# Automatically detect and generate all relationships
php artisan easy-dev:sync-relations --all
```

#### 4. Add Custom Relationships

```bash
# Add polymorphic relationships for reviews
php artisan easy-dev:add-relation Review morphTo reviewable

# Add many-to-many for product tags
php artisan easy-dev:add-relation Product belongsToMany Tag
php artisan easy-dev:add-relation Tag belongsToMany Product

# Add wishlist relationships
php artisan easy-dev:add-relation User belongsToMany Product --method=wishlist --pivot-table=wishlists
```

### Generated File Structure

After running the commands, you'll have:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── CategoryController.php
│   │   ├── ProductController.php
│   │   ├── BrandController.php
│   │   └── Api/
│   │       ├── OrderController.php
│   │       ├── OrderItemController.php
│   │       └── ReviewController.php
│   ├── Requests/
│   │   ├── StoreCategoryRequest.php
│   │   ├── UpdateCategoryRequest.php
│   │   ├── StoreProductRequest.php
│   │   ├── UpdateProductRequest.php
│   │   └── ...
│   └── Resources/
│       ├── CategoryResource.php
│       ├── CategoryCollection.php
│       ├── ProductResource.php
│       ├── ProductCollection.php
│       └── ...
├── Models/
│   ├── Category.php (with relationships)
│   ├── Product.php (with relationships)
│   ├── Order.php (with relationships)
│   └── ...
├── Repositories/
│   ├── Contracts/
│   │   ├── CategoryRepositoryInterface.php
│   │   ├── ProductRepositoryInterface.php
│   │   └── ...
│   ├── CategoryRepository.php
│   ├── ProductRepository.php
│   └── ...
└── Services/
    ├── Contracts/
    │   ├── CategoryServiceInterface.php
    │   ├── ProductServiceInterface.php
    │   └── ...
    ├── CategoryService.php
    ├── ProductService.php
    └── ...
```

### Example Generated Models with Relationships

#### Product Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'sku',
        'price', 'sale_price', 'stock', 'low_stock_threshold',
        'weight', 'dimensions', 'images', 'category_id', 'brand_id',
        'user_id', 'is_active', 'is_featured', 'meta_title', 'meta_description'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // Auto-generated relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
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

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    // Custom methods (add manually)
    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 2);
    }

    public function getIsOnSaleAttribute()
    {
        return $this->sale_price && $this->sale_price < $this->price;
    }

    public function getEffectivePriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getInStockAttribute()
    {
        return $this->stock > 0;
    }

    public function getLowStockAttribute()
    {
        return $this->stock <= $this->low_stock_threshold;
    }
}
```

### Example API Endpoints

The generated API will provide these endpoints:

```bash
# Categories API
GET    /api/categories              # List all categories
POST   /api/categories              # Create category
GET    /api/categories/{id}         # Show category
PUT    /api/categories/{id}         # Update category
DELETE /api/categories/{id}         # Delete category

# Products API
GET    /api/products                # List products with filtering
POST   /api/products                # Create product
GET    /api/products/{id}           # Show product with relationships
PUT    /api/products/{id}           # Update product
DELETE /api/products/{id}           # Delete product

# Orders API
GET    /api/orders                  # List orders
POST   /api/orders                  # Create order
GET    /api/orders/{id}             # Show order
PUT    /api/orders/{id}             # Update order
DELETE /api/orders/{id}             # Cancel order

# Reviews API
GET    /api/reviews                 # List reviews
POST   /api/reviews                 # Create review
GET    /api/reviews/{id}            # Show review
PUT    /api/reviews/{id}            # Update review
DELETE /api/reviews/{id}            # Delete review
```

---

## 📝 Blog Platform

Creating a modern blog platform with posts, comments, tags, and user management.

### Quick Setup

```bash
# Create models
php artisan make:model Post -m
php artisan make:model Comment -m
php artisan make:model Tag -m
php artisan make:model Category -m

# Generate CRUD operations
php artisan easy-dev:make Post --with-repository --with-service --interactive
php artisan easy-dev:make Comment --with-service --api-only
php artisan easy-dev:make Tag --with-repository
php artisan easy-dev:make Category --with-repository --with-service

# Auto-detect relationships
php artisan easy-dev:sync-relations --all

# Add custom relationships
php artisan easy-dev:add-relation Post belongsToMany Tag
php artisan easy-dev:add-relation Comment morphTo commentable
```

### Database Schema

```sql
-- Posts table
CREATE TABLE posts (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    excerpt TEXT,
    content LONGTEXT,
    featured_image VARCHAR(255),
    user_id BIGINT,
    category_id BIGINT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    meta_title VARCHAR(255),
    meta_description TEXT,
    view_count INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Comments table (polymorphic)
CREATE TABLE comments (
    id BIGINT PRIMARY KEY,
    content TEXT,
    commentable_type VARCHAR(255),
    commentable_id BIGINT,
    user_id BIGINT,
    parent_id BIGINT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parent_id) REFERENCES comments(id)
);

-- Post-Tag pivot table
CREATE TABLE post_tag (
    post_id BIGINT,
    tag_id BIGINT,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);
```

### Generated Features

- ✅ Complete blog post CRUD with rich text editor support
- ✅ Hierarchical comment system with approval workflow
- ✅ Tag management with many-to-many relationships
- ✅ Category hierarchy with parent/child relationships
- ✅ SEO-friendly URLs with slug generation
- ✅ Post status management (draft/published/archived)
- ✅ API endpoints for headless CMS usage
- ✅ Comprehensive test coverage

---

## 🏢 Project Management System

Building a comprehensive project management system with teams, projects, tasks, and time tracking.

### Setup Commands

```bash
# Create core models
php artisan make:model Team -m
php artisan make:model Project -m
php artisan make:model Task -m
php artisan make:model TimeLog -m
php artisan make:model Comment -m

# Generate with different patterns based on complexity
php artisan easy-dev:make Team --with-repository --with-service --interactive
php artisan easy-dev:make Project --with-repository --with-service --interactive
php artisan easy-dev:make Task --with-service --interactive
php artisan easy-dev:make TimeLog --api-only --with-repository
php artisan easy-dev:make Comment --api-only

# Auto-detect relationships
php artisan easy-dev:sync-relations --all

# Add team memberships and project assignments
php artisan easy-dev:add-relation User belongsToMany Team --method=teams --pivot-table=team_members
php artisan easy-dev:add-relation User belongsToMany Project --method=projects --pivot-table=project_members
```

### Database Schema

```sql
-- Teams table
CREATE TABLE teams (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    owner_id BIGINT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

-- Team members pivot table
CREATE TABLE team_members (
    id BIGINT PRIMARY KEY,
    team_id BIGINT,
    user_id BIGINT,
    role ENUM('member', 'admin', 'owner') DEFAULT 'member',
    joined_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_member (team_id, user_id)
);

-- Projects table
CREATE TABLE projects (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    team_id BIGINT,
    manager_id BIGINT,
    status ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    budget DECIMAL(12,2) NULL,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (manager_id) REFERENCES users(id)
);

-- Project members pivot table
CREATE TABLE project_members (
    id BIGINT PRIMARY KEY,
    project_id BIGINT,
    user_id BIGINT,
    role ENUM('member', 'lead', 'manager') DEFAULT 'member',
    hourly_rate DECIMAL(8,2) NULL,
    joined_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_member (project_id, user_id)
);

-- Tasks table
CREATE TABLE tasks (
    id BIGINT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    project_id BIGINT,
    assignee_id BIGINT NULL,
    creator_id BIGINT,
    parent_task_id BIGINT NULL,
    status ENUM('todo', 'in_progress', 'review', 'done') DEFAULT 'todo',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    estimated_hours DECIMAL(8,2) NULL,
    actual_hours DECIMAL(8,2) DEFAULT 0,
    due_date DATETIME NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES users(id),
    FOREIGN KEY (creator_id) REFERENCES users(id),
    FOREIGN KEY (parent_task_id) REFERENCES tasks(id)
);

-- Time logs table
CREATE TABLE time_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    task_id BIGINT,
    project_id BIGINT,
    description TEXT,
    hours DECIMAL(8,2),
    billable BOOLEAN DEFAULT TRUE,
    logged_at DATETIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

-- Comments table (polymorphic for projects and tasks)
CREATE TABLE comments (
    id BIGINT PRIMARY KEY,
    content TEXT,
    commentable_type VARCHAR(255),
    commentable_id BIGINT,
    user_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Generated API Endpoints

```bash
# Team Management
GET    /api/teams                   # List teams
POST   /api/teams                   # Create team
GET    /api/teams/{id}              # Show team with members
PUT    /api/teams/{id}              # Update team
DELETE /api/teams/{id}              # Delete team
POST   /api/teams/{id}/members      # Add team member
DELETE /api/teams/{id}/members/{user} # Remove team member

# Project Management
GET    /api/projects                # List projects
POST   /api/projects                # Create project
GET    /api/projects/{id}           # Show project with tasks
PUT    /api/projects/{id}           # Update project
DELETE /api/projects/{id}           # Delete project
GET    /api/projects/{id}/tasks     # Get project tasks
GET    /api/projects/{id}/time-logs # Get project time logs

# Task Management
GET    /api/tasks                   # List tasks with filters
POST   /api/tasks                   # Create task
GET    /api/tasks/{id}              # Show task with subtasks
PUT    /api/tasks/{id}              # Update task
DELETE /api/tasks/{id}              # Delete task
POST   /api/tasks/{id}/time-logs    # Log time for task

# Time Tracking
GET    /api/time-logs               # List time logs
POST   /api/time-logs               # Create time log
PUT    /api/time-logs/{id}          # Update time log
DELETE /api/time-logs/{id}          # Delete time log
```

---

## 🏥 Healthcare Management System

A comprehensive healthcare management system with patients, doctors, appointments, and medical records.

### Quick Implementation

```bash
# Create all models
php artisan make:model Patient -m
php artisan make:model Doctor -m
php artisan make:model Appointment -m
php artisan make:model MedicalRecord -m
php artisan make:model Prescription -m
php artisan make:model Medication -m

# Generate complete CRUD with security considerations
php artisan easy-dev:make Patient --with-repository --with-service --interactive
php artisan easy-dev:make Doctor --with-repository --with-service --interactive
php artisan easy-dev:make Appointment --with-service --interactive
php artisan easy-dev:make MedicalRecord --with-repository --with-service --api-only
php artisan easy-dev:make Prescription --with-service --api-only
php artisan easy-dev:make Medication --with-repository

# Auto-detect all relationships
php artisan easy-dev:sync-relations --all
```

### Key Features Generated

- ✅ Patient management with HIPAA-compliant data handling
- ✅ Doctor profiles with specializations and schedules
- ✅ Appointment scheduling with conflict detection
- ✅ Medical records with file attachments
- ✅ Prescription management with medication database
- ✅ Audit trails for all medical data changes
- ✅ Role-based access control for different user types
- ✅ API endpoints for mobile applications

---

## 📚 Learning Management System (LMS)

Building an educational platform with courses, lessons, quizzes, and student progress tracking.

### Setup

```bash
# Educational models
php artisan make:model Course -m
php artisan make:model Lesson -m
php artisan make:model Quiz -m
php artisan make:model Question -m
php artisan make:model StudentCourse -m
php artisan make:model StudentProgress -m

# Generate with educational focus
php artisan easy-dev:make Course --with-repository --with-service --interactive
php artisan easy-dev:make Lesson --with-service --interactive
php artisan easy-dev:make Quiz --with-repository --with-service
php artisan easy-dev:make Question --with-repository
php artisan easy-dev:make StudentCourse --api-only --with-service
php artisan easy-dev:make StudentProgress --api-only --with-repository

# Auto-detect relationships
php artisan easy-dev:sync-relations --all

# Add enrollment relationships
php artisan easy-dev:add-relation User belongsToMany Course --method=enrolledCourses --pivot-table=student_courses
```

### Generated Features

- ✅ Course catalog with hierarchical lesson structure
- ✅ Video and document lesson content management
- ✅ Interactive quiz system with multiple question types
- ✅ Student enrollment and progress tracking
- ✅ Certificate generation upon course completion
- ✅ Discussion forums for each course
- ✅ Instructor dashboard with analytics
- ✅ Mobile API for learning apps

---

## 🏪 Multi-Vendor Marketplace

Creating a marketplace platform where multiple vendors can sell products.

### Implementation

```bash
# Marketplace models
php artisan make:model Vendor -m
php artisan make:model VendorProduct -m
php artisan make:model Commission -m
php artisan make:model Payout -m

# Generate marketplace-specific CRUD
php artisan easy-dev:make Vendor --with-repository --with-service --interactive
php artisan easy-dev:make VendorProduct --with-service --interactive
php artisan easy-dev:make Commission --api-only --with-repository
php artisan easy-dev:make Payout --api-only --with-service

# Auto-detect relationships
php artisan easy-dev:sync-relations --all

# Add vendor-product relationships
php artisan easy-dev:add-relation Vendor hasMany Product --method=products
php artisan easy-dev:add-relation Product belongsTo Vendor
```

### Key Features

- ✅ Multi-vendor product management
- ✅ Commission calculation and tracking
- ✅ Vendor payout management
- ✅ Vendor performance analytics
- ✅ Product approval workflow
- ✅ Vendor-specific branding
- ✅ Marketplace administration panel

---

## 💡 Best Practices from Examples

### 1. Start with Database Design

Always design your database schema first, following Laravel conventions:

```sql
-- Good: Following Laravel conventions
CREATE TABLE posts (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,              -- Foreign key naming
    category_id BIGINT,          -- Foreign key naming
    title VARCHAR(255),
    slug VARCHAR(255) UNIQUE,    -- SEO-friendly URLs
    content TEXT,
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

### 2. Use Interactive Mode for Complex Models

```bash
# For main entities, use interactive mode
php artisan easy-dev:make Product --interactive

# This helps you:
# - Choose the right patterns (Repository/Service)
# - Configure API endpoints
# - Set up proper validation
# - Select relationship detection options
```

### 3. Generate in Logical Order

```bash
# 1. Create parent/independent models first
php artisan easy-dev:make Category --with-repository
php artisan easy-dev:make User --with-service

# 2. Create dependent models
php artisan easy-dev:make Product --with-repository --with-service

# 3. Auto-detect all relationships at once
php artisan easy-dev:sync-relations --all

# 4. Add complex relationships manually
php artisan easy-dev:add-relation Product belongsToMany Tag
```

### 4. Customize Generated Code

After generation, customize the code for your specific needs:

```php
// Add custom methods to generated models
class Product extends Model
{
    // Generated fillable array
    protected $fillable = ['name', 'price', 'category_id'];

    // Add custom accessors
    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 2);
    }

    // Add custom scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Add custom methods
    public function getDiscountPercentage()
    {
        if ($this->sale_price && $this->price > $this->sale_price) {
            return round((($this->price - $this->sale_price) / $this->price) * 100);
        }
        return 0;
    }
}
```

### 5. Leverage Generated Tests

Use the generated tests as a foundation:

```php
// Generated test foundation
class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product()
    {
        // Basic generated test
        $response = $this->postJson('/api/products', $productData);
        $response->assertCreated();
    }

    // Add custom test cases
    public function test_cannot_create_product_with_invalid_price()
    {
        $productData = ['name' => 'Test', 'price' => -10];
        $response = $this->postJson('/api/products', $productData);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_can_filter_products_by_category()
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);
        
        $response = $this->getJson("/api/products?category_id={$category->id}");
        $response->assertOk()->assertJsonCount(3, 'data');
    }
}
```

These examples demonstrate how Laravel Easy Dev v2 can accelerate development for any type of application while maintaining code quality and best practices!
