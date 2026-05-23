<?php

namespace AnasNashat\EasyDev\Tests\Feature\Commands;

use AnasNashat\EasyDev\Tests\TestCase;
use Illuminate\Support\Facades\File;

class V3CustomizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear any leftover directories and files
        File::deleteDirectory(base_path('app/Modules'));
        File::deleteDirectory(base_path('app/CustomDomain'));
        File::delete(base_path('easy-dev-ai.md'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('app/Modules'));
        File::deleteDirectory(base_path('app/CustomDomain'));
        File::delete(base_path('easy-dev-ai.md'));
        parent::tearDown();
    }

    public function test_can_generate_repository_to_custom_path(): void
    {
        $customPath = 'app/CustomDomain/Repositories';
        $fullPath = base_path($customPath . '/UserRepository.php');

        $this->assertFalse(file_exists($fullPath));

        $this->artisan('easy-dev:repository', [
            'model' => 'User',
            '--path' => $customPath,
            '--without-interface' => true,
        ])->assertExitCode(0);

        $this->assertTrue(file_exists($fullPath));
        $content = file_get_contents($fullPath);
        $this->assertStringContainsString('namespace App\CustomDomain\Repositories;', $content);
    }

    public function test_can_generate_crud_to_modular_architecture(): void
    {
        $module = 'Billing';
        $modelPath = base_path("app/Modules/{$module}/Models/Order.php");
        $repoPath = base_path("app/Modules/{$module}/Repositories/OrderRepository.php");
        $controllerPath = base_path("app/Modules/{$module}/Http/Controllers/OrderController.php");

        $this->assertFalse(file_exists($modelPath));
        $this->assertFalse(file_exists($repoPath));
        $this->assertFalse(file_exists($controllerPath));

        $this->artisan('easy-dev:crud', [
            'model' => 'Order',
            '--module' => $module,
            '--with-repository' => true,
            '--without-interface' => true,
            '--web-only' => true,
        ])->assertExitCode(0);

        $this->assertTrue(file_exists($modelPath));
        $this->assertTrue(file_exists($repoPath));
        $this->assertTrue(file_exists($controllerPath));

        // Check namespace translation
        $this->assertStringContainsString('namespace App\Modules\Billing\Models;', file_get_contents($modelPath));
        $this->assertStringContainsString('namespace App\Modules\Billing\Repositories;', file_get_contents($repoPath));
        $this->assertStringContainsString('namespace App\Modules\Billing\Http\Controllers;', file_get_contents($controllerPath));

        // Check import translation
        $this->assertStringContainsString('use App\Modules\Billing\Models\Order;', file_get_contents($repoPath));
    }

    public function test_can_run_generators_in_ai_silent_mode(): void
    {
        \Illuminate\Support\Facades\Artisan::call('easy-dev:enum', [
            'name' => 'OrderStatus',
            '--ai' => true,
        ]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertStringContainsString('"status": "success"', $output);
        $this->assertStringContainsString('"command": "easy-dev:enum"', $output);
    }

    public function test_can_run_ai_context_command(): void
    {
        \Illuminate\Support\Facades\Artisan::call('easy-dev:ai-context', [
            '--pretty' => true,
        ]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertStringContainsString('"status": "success"', $output);
        $this->assertStringContainsString('"paths"', $output);
    }

    public function test_can_run_publish_stubs_list(): void
    {
        \Illuminate\Support\Facades\Artisan::call('easy-dev:publish-stubs', [
            '--list' => true,
            '--ai' => true,
        ]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertStringContainsString('"status": "success"', $output);
        $this->assertStringContainsString('"stubs"', $output);
    }

    public function test_can_publish_stubs_and_ai_skill_file(): void
    {
        $this->assertFalse(file_exists(base_path('easy-dev-ai.md')));

        \Illuminate\Support\Facades\Artisan::call('easy-dev:publish-stubs', [
            '--only' => 'model',
        ]);

        $this->assertTrue(file_exists(base_path('easy-dev-ai.md')));
        $this->assertTrue(file_exists(resource_path('stubs/vendor/easy-dev/model.stub')));
        
        // Clean up stub
        @unlink(resource_path('stubs/vendor/easy-dev/model.stub'));
    }

    public function test_can_autodiscover_modular_ddd_model(): void
    {
        $modulePath = base_path('app/Modules/Patient/Domain/Models');
        if (!File::isDirectory($modulePath)) {
            File::makeDirectory($modulePath, 0755, true);
        }
        
        $modelFilePath = $modulePath . '/Patient.php';
        $modelContent = <<<PHP
<?php
namespace App\Modules\Patient\Domain\Models;
use Illuminate\Database\Eloquent\Model;
class Patient extends Model {}
PHP;
        File::put($modelFilePath, $modelContent);
        
        // Dynamically require the file so class_exists() resolves it in memory
        require_once $modelFilePath;

        // Resolve RelationDetector from container
        $relationDetector = app(\AnasNashat\EasyDev\Services\RelationDetector::class);
        
        $qualifyModel = $relationDetector->qualifyModel('Patient');
        $modelPath = $relationDetector->getModelPath('Patient');
        $allModels = $relationDetector->getAllModels();

        $this->assertEquals('App\Modules\Patient\Domain\Models\Patient', $qualifyModel);
        $this->assertEquals(str_replace('/', DIRECTORY_SEPARATOR, base_path('app/Modules/Patient/Domain/Models/Patient.php')), str_replace('/', DIRECTORY_SEPARATOR, $modelPath));
        $this->assertContains('Patient', $allModels);
    }

    public function test_can_generate_crud_with_clean_preset(): void
    {
        $this->artisan('easy-dev:crud', [
            'model' => 'Patient',
            '--module' => 'Patient',
            '--preset' => 'clean',
            '--with-repository' => true,
            '--with-service' => true,
            '--with-policy' => true,
            '--with-dto' => true,
            '--with-observer' => true,
            '--web-only' => true,
        ])->assertExitCode(0);

        // Verify DDD Clean paths exist
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Domain/Models/Patient.php')));
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Domain/Repositories/PatientRepositoryInterface.php')));
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Infrastructure/Repositories/PatientRepository.php')));
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Application/Services/PatientService.php')));
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Application/Services/PatientServiceInterface.php')));
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Presentation/Http/Controllers/PatientController.php')));
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Presentation/Http/Requests/StorePatientRequest.php')));
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Presentation/Http/Requests/UpdatePatientRequest.php')));
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Infrastructure/Policies/PatientPolicy.php')));
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Application/DTOs/PatientData.php')));
        $this->assertTrue(file_exists(base_path('app/Modules/Patient/Infrastructure/Observers/PatientObserver.php')));

        // Check namespaces and imports inside clean preset
        $this->assertStringContainsString('namespace App\Modules\Patient\Domain\Models;', file_get_contents(base_path('app/Modules/Patient/Domain/Models/Patient.php')));
        $this->assertStringContainsString('namespace App\Modules\Patient\Domain\Repositories;', file_get_contents(base_path('app/Modules/Patient/Domain/Repositories/PatientRepositoryInterface.php')));
        $this->assertStringContainsString('namespace App\Modules\Patient\Infrastructure\Repositories;', file_get_contents(base_path('app/Modules/Patient/Infrastructure/Repositories/PatientRepository.php')));
        $this->assertStringContainsString('namespace App\Modules\Patient\Application\Services;', file_get_contents(base_path('app/Modules/Patient/Application/Services/PatientService.php')));
        
        // Clean imports assertion: Repository should import custom interface and custom model locations
        $repoContent = file_get_contents(base_path('app/Modules/Patient/Infrastructure/Repositories/PatientRepository.php'));
        $this->assertStringContainsString('use App\Modules\Patient\Domain\Repositories\PatientRepositoryInterface;', $repoContent);
        $this->assertStringContainsString('use App\Modules\Patient\Domain\Models\Patient;', $repoContent);

        // Verify service interface namespace and imports
        $serviceInterfaceContent = file_get_contents(base_path('app/Modules/Patient/Application/Services/PatientServiceInterface.php'));
        $this->assertStringContainsString('namespace App\Modules\Patient\Application\Services;', $serviceInterfaceContent);
        $this->assertStringNotContainsString('{{ ModelNamespace }}', $serviceInterfaceContent);
        $this->assertStringNotContainsString('{{ModelNamespace}}', $serviceInterfaceContent);
        $this->assertStringContainsString('use App\Modules\Patient\Domain\Models\Patient;', $serviceInterfaceContent);

        // Verify service implementation namespace and imports (ensuring no leaked placeholders)
        $serviceContent = file_get_contents(base_path('app/Modules/Patient/Application/Services/PatientService.php'));
        $this->assertStringContainsString('namespace App\Modules\Patient\Application\Services;', $serviceContent);
        $this->assertStringNotContainsString('{{ ServiceContractNamespace }}', $serviceContent);
        $this->assertStringNotContainsString('{{ServiceContractNamespace}}', $serviceContent);
        $this->assertStringNotContainsString('{{ ModelNamespace }}', $serviceContent);
        $this->assertStringNotContainsString('{{ModelNamespace}}', $serviceContent);
        $this->assertStringContainsString('use App\Modules\Patient\Domain\Models\Patient;', $serviceContent);
        $this->assertStringContainsString('use App\Modules\Patient\Application\Services\PatientServiceInterface;', $serviceContent);
    }

    public function test_modular_provider_loading_and_reorganization(): void
    {
        $module = 'Catalog';
        $modulePath = base_path("app/Modules/{$module}");
        
        // 1. Setup traditional modular providers directory and file
        $oldProviderDir = "{$modulePath}/Providers";
        if (!File::isDirectory($oldProviderDir)) {
            File::makeDirectory($oldProviderDir, 0755, true);
        }
        
        $providerFile = "{$oldProviderDir}/CatalogServiceProvider.php";
        $providerContent = <<<PHP
<?php

namespace App\Modules\Catalog\Providers;

use Illuminate\Support\ServiceProvider;

class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void {}
}
PHP;
        File::put($providerFile, $providerContent);
        
        // 2. Setup module.json file
        $moduleJsonFile = "{$modulePath}/module.json";
        $moduleJsonContent = <<<JSON
{
    "name": "Catalog",
    "providers": [
        "App\\\\Modules\\\\Catalog\\\\Providers\\\\CatalogServiceProvider"
    ]
}
JSON;
        File::put($moduleJsonFile, $moduleJsonContent);

        $serviceProviderManager = app(\AnasNashat\EasyDev\Services\ServiceProviderManager::class);
        
        // 3. Reorganize modular providers
        $serviceProviderManager->reorganizeModuleProviders($module, 'clean');

        $newProviderFile = "{$modulePath}/Infrastructure/Providers/CatalogServiceProvider.php";
        
        // Assert old provider dir is deleted and file moved to Infrastructure
        $this->assertFalse(File::isDirectory($oldProviderDir));
        $this->assertTrue(File::exists($newProviderFile));
        
        // Assert namespace is updated inside the service provider
        $updatedProviderContent = File::get($newProviderFile);
        $this->assertStringContainsString('namespace App\Modules\Catalog\Infrastructure\Providers;', $updatedProviderContent);
        $this->assertStringNotContainsString('App\Modules\Catalog\Providers;', $updatedProviderContent);
        
        // Assert module.json is updated
        $updatedJsonContent = File::get($moduleJsonFile);
        $this->assertStringContainsString('Infrastructure\\\\Providers', $updatedJsonContent);
        $this->assertStringNotContainsString('Catalog\\\\Providers\\\\CatalogServiceProvider', $updatedJsonContent);

        // 4. Test binding auto-injection into dynamic module provider
        $serviceProviderManager->addRepositoryBinding('Product', $module, 'clean');
        $serviceProviderManager->addServiceBinding('Product', $module, 'clean');

        $updatedProviderContentWithBindings = File::get($newProviderFile);
        
        // Assert use statements have correct clean architecture paths
        $this->assertStringContainsString('use App\Modules\Catalog\Domain\Repositories\ProductRepositoryInterface;', $updatedProviderContentWithBindings);
        $this->assertStringContainsString('use App\Modules\Catalog\Infrastructure\Repositories\ProductRepository;', $updatedProviderContentWithBindings);
        $this->assertStringContainsString('use App\Modules\Catalog\Application\Services\ProductServiceInterface;', $updatedProviderContentWithBindings);
        $this->assertStringContainsString('use App\Modules\Catalog\Application\Services\ProductService;', $updatedProviderContentWithBindings);

        // Assert bind statements are successfully injected
        $this->assertStringContainsString('$this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);', $updatedProviderContentWithBindings);
        $this->assertStringContainsString('$this->app->bind(ProductServiceInterface::class, ProductService::class);', $updatedProviderContentWithBindings);
    }

    public function test_dream_command_ignores_leading_articles(): void
    {
        // Test article removal from the start of dream command prompts
        // Case 1: "A"
        \Illuminate\Support\Facades\Artisan::call('easy-dev:dream', [
            'prompt' => 'A Review model with rating:integer',
            '--ai' => true,
            '--dry-run' => true,
        ]);
        $output1 = \Illuminate\Support\Facades\Artisan::output();
        $plan1 = json_decode($output1, true);
        
        $this->assertEquals('success', $plan1['status']);
        $this->assertEquals('Review', $plan1['plans']['model']);

        // Case 2: "An"
        \Illuminate\Support\Facades\Artisan::call('easy-dev:dream', [
            'prompt' => 'an Order model with total:decimal',
            '--ai' => true,
            '--dry-run' => true,
        ]);
        $output2 = \Illuminate\Support\Facades\Artisan::output();
        $plan2 = json_decode($output2, true);
        
        $this->assertEquals('success', $plan2['status']);
        $this->assertEquals('Order', $plan2['plans']['model']);

        // Case 3: "The"
        \Illuminate\Support\Facades\Artisan::call('easy-dev:dream', [
            'prompt' => 'The Category model',
            '--ai' => true,
            '--dry-run' => true,
        ]);
        $output3 = \Illuminate\Support\Facades\Artisan::output();
        $plan3 = json_decode($output3, true);
        
        $this->assertEquals('success', $plan3['status']);
        $this->assertEquals('Category', $plan3['plans']['model']);
    }

    public function test_multiple_entities_provider_bindings_injection(): void
    {
        $module = 'Sales';
        $modulePath = base_path("app/Modules/{$module}");
        
        // 1. Setup providers directory and provider file WITHOUT : void return type hint on register()
        $providerDir = "{$modulePath}/Infrastructure/Providers";
        if (!File::isDirectory($providerDir)) {
            File::makeDirectory($providerDir, 0755, true);
        }
        
        $providerFile = "{$providerDir}/SalesServiceProvider.php";
        $providerContent = <<<PHP
<?php

namespace App\Modules\Sales\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class SalesServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Already empty register method without return type hint
    }
}
PHP;
        File::put($providerFile, $providerContent);
        
        $serviceProviderManager = app(\AnasNashat\EasyDev\Services\ServiceProviderManager::class);

        // 2. Inject bindings for first entity (Product)
        $serviceProviderManager->addRepositoryBinding('Product', $module, 'clean');
        $serviceProviderManager->addServiceBinding('Product', $module, 'clean');

        $providerContentAfterFirst = File::get($providerFile);
        $this->assertStringContainsString('use App\Modules\Sales\Domain\Repositories\ProductRepositoryInterface;', $providerContentAfterFirst);
        $this->assertStringContainsString('use App\Modules\Sales\Infrastructure\Repositories\ProductRepository;', $providerContentAfterFirst);
        $this->assertStringContainsString('$this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);', $providerContentAfterFirst);

        // 3. Inject bindings for second entity (Customer) in the SAME provider (tests the loop/subsequent appending capability!)
        $serviceProviderManager->addRepositoryBinding('Customer', $module, 'clean');
        $serviceProviderManager->addServiceBinding('Customer', $module, 'clean');

        $providerContentAfterSecond = File::get($providerFile);
        
        // Verify second entity use statements are added
        $this->assertStringContainsString('use App\Modules\Sales\Domain\Repositories\CustomerRepositoryInterface;', $providerContentAfterSecond);
        $this->assertStringContainsString('use App\Modules\Sales\Infrastructure\Repositories\CustomerRepository;', $providerContentAfterSecond);
        
        // Verify second entity bind statements are successfully appended!
        $this->assertStringContainsString('$this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);', $providerContentAfterSecond);
        $this->assertStringContainsString('$this->app->bind(CustomerServiceInterface::class, CustomerService::class);', $providerContentAfterSecond);

        // Verify first entity bind statements are STILL there!
        $this->assertStringContainsString('$this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);', $providerContentAfterSecond);
        $this->assertStringContainsString('$this->app->bind(ProductServiceInterface::class, ProductService::class);', $providerContentAfterSecond);
    }

    public function test_traditional_stubs_use_generic_naming(): void
    {
        // 1. Assert traditional Service Interface contains unified generic methods
        $serviceInterfaceContent = File::get(__DIR__ . '/../../../resources/stubs/service.interface.stub');
        $this->assertStringContainsString('public function getAll(array $filters = []): Collection;', $serviceInterfaceContent);
        $this->assertStringContainsString('public function findById(int $id): ?{{ ModelName }};', $serviceInterfaceContent);
        $this->assertStringContainsString('public function create(array $data): {{ ModelName }};', $serviceInterfaceContent);
        $this->assertStringContainsString('public function update({{ ModelName }} ${{ modelName }}, array $data): {{ ModelName }};', $serviceInterfaceContent);
        $this->assertStringContainsString('public function delete({{ ModelName }} ${{ modelName }}): bool;', $serviceInterfaceContent);

        // 2. Assert traditional Service Implementation contains unified generic methods and delegates to repository correctly
        $serviceContent = File::get(__DIR__ . '/../../../resources/stubs/service.stub');
        $this->assertStringContainsString('public function getAll(array $filters = []): Collection', $serviceContent);
        $this->assertStringContainsString('return $this->repository->getAll($filters);', $serviceContent);
        $this->assertStringContainsString('public function findById(int $id): ?{{ ModelName }}', $serviceContent);
        $this->assertStringContainsString('return $this->repository->findById($id);', $serviceContent);
        $this->assertStringContainsString('public function create(array $data): {{ ModelName }}', $serviceContent);
        $this->assertStringContainsString('return $this->repository->create($validatedData);', $serviceContent);
        $this->assertStringContainsString('public function update({{ ModelName }} ${{ modelName }}, array $data): {{ ModelName }}', $serviceContent);
        $this->assertStringContainsString('return $this->repository->update(${{ modelName }}, $validatedData);', $serviceContent);
        $this->assertStringContainsString('public function delete({{ ModelName }} ${{ modelName }}): bool', $serviceContent);
        $this->assertStringContainsString('return $this->repository->delete(${{ modelName }});', $serviceContent);

        // 3. Assert traditional Repository Interface contains matching signatures
        $repoInterfaceContent = File::get(__DIR__ . '/../../../resources/stubs/repository.interface.stub');
        $this->assertStringContainsString('public function getAll(array $filters = []): Collection;', $repoInterfaceContent);
        $this->assertStringContainsString('public function findById(int $id): ?{{ ModelName }};', $repoInterfaceContent);
        $this->assertStringContainsString('public function create(array $data): {{ ModelName }};', $repoInterfaceContent);
        $this->assertStringContainsString('public function update({{ ModelName }} ${{ modelName }}, array $data): {{ ModelName }};', $repoInterfaceContent);
        $this->assertStringContainsString('public function delete({{ ModelName }} ${{ modelName }}): bool;', $repoInterfaceContent);

        // 4. Assert traditional Repository Implementation matches interface signatures and Eloquent calls
        $repoContent = File::get(__DIR__ . '/../../../resources/stubs/repository.stub');
        $this->assertStringContainsString('public function getAll(array $filters = []): Collection', $repoContent);
        $this->assertStringContainsString('return $this->model->all();', $repoContent);
        $this->assertStringContainsString('public function findById(int $id): ?{{ ModelName }}', $repoContent);
        $this->assertStringContainsString('return $this->model->find($id);', $repoContent);
        $this->assertStringContainsString('public function create(array $data): {{ ModelName }}', $repoContent);
        $this->assertStringContainsString('return $this->model->create($data);', $repoContent);
        $this->assertStringContainsString('public function update({{ ModelName }} ${{ modelName }}, array $data): {{ ModelName }}', $repoContent);
        $this->assertStringContainsString('${{ modelName }}->update($data);', $repoContent);
        $this->assertStringContainsString('return ${{ modelName }}->fresh();', $repoContent);
        $this->assertStringContainsString('public function delete({{ ModelName }} ${{ modelName }}): bool', $repoContent);
        $this->assertStringContainsString('return ${{ modelName }}->delete();', $repoContent);

        // 5. Assert Controller stubs call the updated generic methods
        $apiServiceControllerContent = File::get(__DIR__ . '/../../../resources/stubs/controller.api.service.stub');
        $this->assertStringContainsString('$this->service->getAll($filters)', $apiServiceControllerContent);
        $this->assertStringContainsString('$this->service->create($request->validated())', $apiServiceControllerContent);
        $this->assertStringContainsString('$this->service->update(${{ modelVariable }}, $request->validated())', $apiServiceControllerContent);
        $this->assertStringContainsString('$this->service->delete(${{ modelVariable }})', $apiServiceControllerContent);

        $webServiceControllerContent = File::get(__DIR__ . '/../../../resources/stubs/controller.web.service.stub');
        $this->assertStringContainsString('$this->service->getAll($filters)', $webServiceControllerContent);
        $this->assertStringContainsString('$this->service->create($request->validated())', $webServiceControllerContent);
        $this->assertStringContainsString('$this->service->update(${{ modelVariable }}, $request->validated())', $webServiceControllerContent);
        $this->assertStringContainsString('$this->service->delete(${{ modelVariable }})', $webServiceControllerContent);
    }

    public function test_dream_command_supports_module_and_preset_options(): void
    {
        // 1. Scaffold using easy-dev:dream with module and preset=clean
        $this->artisan('easy-dev:dream', [
            'prompt' => 'Create a Warehouse with name:string and location:string connected to staff',
            '--module' => 'Inventory',
            '--preset' => 'clean',
            '--ai' => true,
            '--dry-run' => false,
        ])->assertExitCode(0);

        // 2. Assert clean architecture DDD paths and file generation under Inventory module
        $this->assertTrue(File::exists(base_path('app/Modules/Inventory/Domain/Models/Warehouse.php')));
        $this->assertTrue(File::exists(base_path('app/Modules/Inventory/Domain/Repositories/WarehouseRepositoryInterface.php')));
        $this->assertTrue(File::exists(base_path('app/Modules/Inventory/Infrastructure/Repositories/WarehouseRepository.php')));
        $this->assertTrue(File::exists(base_path('app/Modules/Inventory/Application/Services/WarehouseService.php')));
        $this->assertTrue(File::exists(base_path('app/Modules/Inventory/Application/Services/WarehouseServiceInterface.php')));

        // 3. Clean up the generated files and directories
        File::deleteDirectory(base_path('app/Modules/Inventory'));
    }

    public function test_clean_scrubber_removes_empty_directories(): void
    {
        $module = 'Inventory';
        $modulePath = base_path("app/Modules/{$module}");

        // Create modular directories (some empty, some with files)
        $emptyPath1 = "{$modulePath}/Http/Controllers";
        $emptyPath2 = "{$modulePath}/Providers";
        $nonEmptyPath = "{$modulePath}/Domain/Models";

        File::makeDirectory($emptyPath1, 0755, true);
        File::makeDirectory($emptyPath2, 0755, true);
        File::makeDirectory($nonEmptyPath, 0755, true);

        // Put a file in models
        File::put("{$nonEmptyPath}/Warehouse.php", '<?php // Warehouse model');

        $this->assertTrue(File::isDirectory($emptyPath1));
        $this->assertTrue(File::isDirectory($emptyPath2));
        $this->assertTrue(File::isDirectory($nonEmptyPath));

        // Let's run a MakeCrudCommand mock logic to scrub it, or invoke the command
        // easy-dev:crud will trigger clean scrubber bottom-up
        $this->artisan('easy-dev:crud', [
            'model' => 'Warehouse',
            '--module' => $module,
            '--preset' => 'clean',
            '--with-repository' => true,
        ])->assertExitCode(0);

        // Http/ and Providers/ directories must be fully scrubbed because they are empty
        $this->assertFalse(File::isDirectory("{$modulePath}/Http"));
        $this->assertFalse(File::isDirectory("{$modulePath}/Providers"));

        // Domain/Models must NOT be scrubbed because it contains the Warehouse.php model file!
        $this->assertTrue(File::isDirectory($nonEmptyPath));
        $this->assertTrue(File::exists("{$nonEmptyPath}/Warehouse.php"));

        // Clean up
        File::deleteDirectory($modulePath);
    }
}

