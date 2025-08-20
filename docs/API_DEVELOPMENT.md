# API Development Guide

Complete guide to building APIs with Laravel Easy Dev v2, including resource generation, API controllers, and best practices.

## 🌐 Overview

Laravel Easy Dev v2 provides comprehensive API development features:

- 🚀 **Automatic API Controller Generation**
- 📄 **API Resource & Collection Classes**
- 🔧 **RESTful Route Registration**
- 🧪 **API Test Generation**
- 📊 **Standardized Response Formats**
- 🔐 **Authentication Integration**
- 📝 **API Documentation Support**

## ⚡ Quick Start

### Generate API-Only CRUD

```bash
# Generate complete API CRUD
php artisan easy-dev:make Product --api-only

# With Repository and Service patterns
php artisan easy-dev:make Product --api-only --with-repository --with-service

# Interactive mode for APIs
php artisan easy-dev:make Product --api-only --interactive
```

### Generate API Resources Only

```bash
# Generate API resource and collection
php artisan easy-dev:api-resource Product

# Resource only
php artisan easy-dev:api-resource Product --resource

# Collection only
php artisan easy-dev:api-resource Product --collection
```

## 📄 API Resources

### Generated Product Resource

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            
            // Conditional relationships
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            
            // Computed attributes
            'formatted_price' => $this->formatted_price,
            'in_stock' => $this->stock > 0,
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

### Generated Product Collection

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
                'has_more_pages' => $this->hasMorePages(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Products retrieved successfully',
        ];
    }
}
```

## 🎮 API Controllers

### Generated API Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Contracts\ProductServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductServiceInterface $productService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): ProductCollection
    {
        $products = $this->productService->getAllProducts(
            $request->get('per_page', 15),
            $request->only(['search', 'category_id', 'is_active'])
        );

        return new ProductCollection($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $product = $this->productService->createProduct($request->validated());

        return new ProductResource($product->load('category'));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'tags']));
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product = $this->productService->updateProduct($product->id, $request->validated());

        return new ProductResource($product->load('category'));
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->deleteProduct($product->id);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ], 204);
    }

    /**
     * Restore the specified product.
     */
    public function restore(int $id): ProductResource
    {
        $product = $this->productService->restoreProduct($id);

        return new ProductResource($product);
    }

    /**
     * Get products by category.
     */
    public function byCategory(int $categoryId, Request $request): ProductCollection
    {
        $products = $this->productService->getProductsByCategory(
            $categoryId,
            $request->get('per_page', 15)
        );

        return new ProductCollection($products);
    }
}
```

### API Controller with Repository Pattern

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): ProductCollection
    {
        $products = $this->productRepository->paginate(
            $request->get('per_page', 15),
            $request->only(['search', 'category_id'])
        );

        return new ProductCollection($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $product = $this->productRepository->create($request->validated());

        return new ProductResource($product);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'tags']));
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product = $this->productRepository->update($product->id, $request->validated());

        return new ProductResource($product);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productRepository->delete($product->id);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ], 204);
    }
}
```

## 🛣️ API Routes

### Generated Routes

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::apiResource('products', ProductController::class);
    
    // Additional custom routes
    Route::get('categories/{category}/products', [ProductController::class, 'byCategory']);
    Route::post('products/{id}/restore', [ProductController::class, 'restore']);
});
```

### Complete Route Structure

```php
// Generated API routes
GET    /api/v1/products              # index
POST   /api/v1/products              # store
GET    /api/v1/products/{product}    # show
PUT    /api/v1/products/{product}    # update
PATCH  /api/v1/products/{product}    # update
DELETE /api/v1/products/{product}    # destroy

// Custom routes
GET    /api/v1/categories/{category}/products  # byCategory
POST   /api/v1/products/{id}/restore           # restore
```

## 📊 Response Formats

### Successful Responses

#### Single Resource Response
```json
{
    "data": {
        "id": 1,
        "name": "Gaming Laptop",
        "description": "High-performance gaming laptop",
        "price": "1299.99",
        "stock": 15,
        "is_active": true,
        "category": {
            "id": 2,
            "name": "Electronics"
        },
        "formatted_price": "$1,299.99",
        "in_stock": true,
        "created_at": "2025-01-15T10:30:00.000000Z",
        "updated_at": "2025-01-15T10:30:00.000000Z"
    }
}
```

#### Collection Response
```json
{
    "data": [
        {
            "id": 1,
            "name": "Gaming Laptop",
            "price": "1299.99",
            "stock": 15,
            "is_active": true
        },
        {
            "id": 2,
            "name": "Wireless Mouse",
            "price": "29.99",
            "stock": 50,
            "is_active": true
        }
    ],
    "meta": {
        "total": 150,
        "count": 15,
        "per_page": 15,
        "current_page": 1,
        "total_pages": 10,
        "has_more_pages": true
    },
    "links": {
        "first": "http://localhost/api/v1/products?page=1",
        "last": "http://localhost/api/v1/products?page=10",
        "prev": null,
        "next": "http://localhost/api/v1/products?page=2"
    },
    "success": true,
    "message": "Products retrieved successfully"
}
```

### Error Responses

#### Validation Error (422)
```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "name": [
            "The name field is required."
        ],
        "price": [
            "The price must be a number.",
            "The price must be at least 0."
        ]
    }
}
```

#### Not Found Error (404)
```json
{
    "success": false,
    "message": "Product not found.",
    "error": "The requested product could not be found."
}
```

#### Server Error (500)
```json
{
    "success": false,
    "message": "Internal server error.",
    "error": "Something went wrong on our end."
}
```

## 🔐 Authentication Integration

### API Authentication Setup

```php
// routes/api.php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);
});

// Or with different auth guards
Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

### Generated Controller with Auth

```php
class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:products.create')->only(['store']);
        $this->middleware('permission:products.update')->only(['update']);
        $this->middleware('permission:products.delete')->only(['destroy']);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id(); // Add authenticated user

        $product = $this->productService->createProduct($data);

        return new ProductResource($product);
    }
}
```

## 📝 Form Requests for APIs

### Generated Store Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'is_active' => ['boolean'],
            'tags' => ['array'],
            'tags.*' => ['exists:tags,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'price.numeric' => 'Price must be a valid number.',
            'category_id.exists' => 'The selected category is invalid.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
```

## 🧪 API Testing

### Generated API Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_products_list(): void
    {
        Product::factory(3)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'stock',
                        'is_active',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages'
                ]
            ]);
    }

    public function test_can_create_product(): void
    {
        Sanctum::actingAs($this->user);
        
        $category = Category::factory()->create();
        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'stock' => 10,
            'category_id' => $category->id,
        ];

        $response = $this->postJson('/api/v1/products', $productData);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Product')
            ->assertJsonPath('data.price', '99.99');

        $this->assertDatabaseHas('products', $productData);
    }

    public function test_can_show_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_can_update_product(): void
    {
        Sanctum::actingAs($this->user);
        
        $product = Product::factory()->create();
        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 149.99,
        ];

        $response = $this->putJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Product Name')
            ->assertJsonPath('data.price', '149.99');

        $this->assertDatabaseHas('products', $updateData);
    }

    public function test_can_delete_product(): void
    {
        Sanctum::actingAs($this->user);
        
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_validation_errors_on_create(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/products', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price', 'category_id']);
    }

    public function test_unauthorized_access(): void
    {
        $response = $this->postJson('/api/v1/products', []);

        $response->assertUnauthorized();
    }

    public function test_can_filter_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id]);
        Product::factory()->count(3)->create(); // Different category

        $response = $this->getJson("/api/v1/products?category_id={$category->id}");

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_can_paginate_products(): void
    {
        Product::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/products?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }
}
```

## 🚀 Advanced API Features

### Custom API Endpoints

```php
// In ProductController
/**
 * Get featured products.
 */
public function featured(): ProductCollection
{
    $products = $this->productService->getFeaturedProducts();
    
    return new ProductCollection($products);
}

/**
 * Search products.
 */
public function search(Request $request): ProductCollection
{
    $request->validate([
        'query' => 'required|string|min:3',
        'category_id' => 'nullable|exists:categories,id',
    ]);

    $products = $this->productService->searchProducts(
        $request->query,
        $request->category_id
    );

    return new ProductCollection($products);
}

/**
 * Bulk operations.
 */
public function bulkUpdate(Request $request): JsonResponse
{
    $request->validate([
        'products' => 'required|array',
        'products.*.id' => 'required|exists:products,id',
        'products.*.is_active' => 'boolean',
    ]);

    $this->productService->bulkUpdate($request->products);

    return response()->json([
        'success' => true,
        'message' => 'Products updated successfully',
    ]);
}
```

### API Versioning

```php
// routes/api.php
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::apiResource('products', ProductV1Controller::class);
});

Route::prefix('v2')->name('api.v2.')->group(function () {
    Route::apiResource('products', ProductV2Controller::class);
});
```

### API Documentation Integration

```php
/**
 * @OA\Get(
 *     path="/api/v1/products",
 *     summary="Get products list",
 *     tags={"Products"},
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Number of items per page",
 *         @OA\Schema(type="integer", default=15)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Products list retrieved successfully",
 *         @OA\JsonContent(ref="#/components/schemas/ProductCollection")
 *     )
 * )
 */
public function index(Request $request): ProductCollection
{
    // Method implementation
}
```

## 🎯 Best Practices

### 1. Resource Relationships

```php
// Efficient relationship loading
public function show(Product $product): ProductResource
{
    return new ProductResource(
        $product->load(['category', 'tags', 'reviews.user'])
    );
}

// Conditional loading based on request
public function index(Request $request): ProductCollection
{
    $query = Product::query();
    
    if ($request->has('include')) {
        $includes = explode(',', $request->include);
        $query->with($includes);
    }
    
    return new ProductCollection($query->paginate());
}
```

### 2. Error Handling

```php
// Custom API exception handler
class ApiExceptionHandler
{
    public function render($request, Exception $exception)
    {
        if ($request->wantsJson()) {
            return match (true) {
                $exception instanceof ValidationException => response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $exception->errors(),
                ], 422),
                
                $exception instanceof ModelNotFoundException => response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                ], 404),
                
                $exception instanceof AuthenticationException => response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401),
                
                default => response()->json([
                    'success' => false,
                    'message' => 'Internal server error',
                ], 500),
            };
        }
        
        return parent::render($request, $exception);
    }
}
```

### 3. Rate Limiting

```php
// API rate limiting
Route::middleware(['throttle:api'])->group(function () {
    Route::apiResource('products', ProductController::class);
});

// Custom rate limiting
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('products/bulk', [ProductController::class, 'bulkUpdate']);
});
```

### 4. API Testing Best Practices

```php
// Use factories for consistent test data
public function test_api_endpoint()
{
    $products = Product::factory()->count(3)->create();
    
    $response = $this->getJson('/api/v1/products');
    
    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'price']
            ]
        ]);
}

// Test edge cases
public function test_empty_results()
{
    $response = $this->getJson('/api/v1/products');
    
    $response->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
}
```

Laravel Easy Dev v2 makes API development fast, consistent, and maintainable with its comprehensive generation capabilities and best practices!
