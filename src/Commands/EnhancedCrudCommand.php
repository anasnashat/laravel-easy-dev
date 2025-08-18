<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class EnhancedCrudCommand extends Command
{
    protected $name = 'easy-dev:make';
    protected $description = 'Enhanced CRUD generator with beautiful UI and interactive features';

    protected array $steps = [
        'validation' => '🔍 Validating Input',
        'migration' => '📋 Processing Migration',
        'model' => '🏗️  Building Model',
        'repository' => '🗄️  Creating Repository',
        'service' => '🔧 Generating Service',
        'controller' => '🎮 Building Controllers',
        'requests' => '📝 Creating Form Requests',
        'routes' => '🛣️  Setting up Routes',
        'bindings' => '🔗 Registering Bindings',
        'complete' => '✨ Finalizing'
    ];

    protected int $currentStep = 0;
    protected int $totalSteps = 10;

    public function handle(): int
    {
        $this->displayWelcomeBanner();
        
        // Interactive mode if no arguments provided
        if (!$this->argument('model')) {
            return $this->interactiveMode();
        }

        return $this->standardMode();
    }

    /**
     * Display a beautiful welcome banner
     */
    protected function displayWelcomeBanner(): void
    {
        $this->newLine();
        $this->line('╭─────────────────────────────────────────────────────────────╮');
        $this->line('│                                                             │');
        $this->line('│   🚀 <fg=cyan;options=bold>Laravel Easy Dev CRUD Generator</> 🚀                │');
        $this->line('│                                                             │');
        $this->line('│   <fg=green>Generate complete CRUD with Repository & Service patterns</>   │');
        $this->line('│                                                             │');
        $this->line('╰─────────────────────────────────────────────────────────────╯');
        $this->newLine();
    }

    /**
     * Interactive mode for better user experience
     */
    protected function interactiveMode(): int
    {
        $this->info('🎯 Welcome to Interactive CRUD Generation!');
        $this->newLine();

        // Get model name
        $modelName = $this->askForModelName();
        if (!$modelName) {
            $this->error('❌ Model name is required!');
            return self::FAILURE;
        }

        // Architecture choices
        $choices = $this->askArchitectureChoices();
        
        // Confirm generation
        if (!$this->confirmGeneration($modelName, $choices)) {
            $this->warn('⚠️  Generation cancelled by user.');
            return self::SUCCESS;
        }

        $this->displayProgressDemo($modelName, $choices);
        return self::SUCCESS;
    }

    /**
     * Standard command mode
     */
    protected function standardMode(): int
    {
        $modelName = $this->argument('model');
        $choices = $this->getOptionsFromArguments();
        
        $this->displayProgressDemo($modelName, $choices);
        return self::SUCCESS;
    }

    /**
     * Ask for model name with validation
     */
    protected function askForModelName(): ?string
    {
        $modelName = $this->ask('📝 What is the name of your model?', null);
        
        if (empty($modelName)) {
            return null;
        }

        $modelName = Str::studly($modelName);
        
        // Check if model already exists
        $modelPath = app_path("Models/{$modelName}.php");
        if (file_exists($modelPath)) {
            $overwrite = $this->confirm("⚠️  Model '{$modelName}' already exists. Do you want to enhance it?", true);
            if (!$overwrite) {
                return null;
            }
        }

        return $modelName;
    }

    /**
     * Ask architecture pattern choices
     */
    protected function askArchitectureChoices(): array
    {
        $this->info('🏗️  Let\'s configure your CRUD architecture:');
        $this->newLine();

        $choices = [];

        // Repository pattern
        $choices['repository'] = $this->choice(
            '🗄️  Repository Pattern',
            ['No', 'Yes, with interface', 'Yes, without interface'],
            1
        );

        // Service pattern
        $choices['service'] = $this->choice(
            '🔧 Service Layer',
            ['No', 'Yes, with interface', 'Yes, without interface'],
            1
        );

        // Controller type
        $choices['controller'] = $this->choice(
            '🎮 Controller Type',
            ['Both API & Web', 'API Only', 'Web Only'],
            0
        );

        return $choices;
    }

    /**
     * Get options from command arguments
     */
    protected function getOptionsFromArguments(): array
    {
        return [
            'repository' => $this->option('with-repository') ? 
                ($this->option('without-interface') ? 'Yes, without interface' : 'Yes, with interface') : 'No',
            'service' => $this->option('with-service') ? 
                ($this->option('without-interface') ? 'Yes, without interface' : 'Yes, with interface') : 'No',
            'controller' => $this->option('api-only') ? 'API Only' : 
                ($this->option('web-only') ? 'Web Only' : 'Both API & Web'),
        ];
    }

    /**
     * Confirm generation with summary
     */
    protected function confirmGeneration(string $modelName, array $choices): bool
    {
        $this->newLine();
        $this->info('📋 Generation Summary:');
        $this->line('─────────────────────');
        
        $this->line("📝 Model: <fg=yellow>{$modelName}</>");
        $this->line("🗄️  Repository: <fg=yellow>{$choices['repository']}</>");
        $this->line("🔧 Service: <fg=yellow>{$choices['service']}</>");
        $this->line("🎮 Controller: <fg=yellow>{$choices['controller']}</>");
        
        $this->newLine();
        
        return $this->confirm('🚀 Proceed with generation?', true);
    }

    /**
     * Display progress demo (for now, until we integrate with actual generation)
     */
    protected function displayProgressDemo(string $modelName, array $choices): void
    {
        $this->newLine();
        $this->info("🎬 Starting CRUD generation for <fg=cyan>{$modelName}</>");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($this->totalSteps);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();

        foreach ($this->steps as $step => $message) {
            $progressBar->setMessage($message);
            usleep(800000); // 0.8 seconds
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->displaySuccessMessage($modelName, $choices);
    }

    /**
     * Display beautiful success message with file summary
     */
    protected function displaySuccessMessage(string $modelName, array $choices): void
    {
        $this->newLine();
        $this->line('╭─────────────────────────────────────────────────────────────╮');
        $this->line('│                                                             │');
        $this->line('│   🎉 <fg=green;options=bold>CRUD Generation Completed Successfully!</> 🎉        │');
        $this->line('│                                                             │');
        $this->line('╰─────────────────────────────────────────────────────────────╯');
        $this->newLine();

        $this->info("📦 Generated files for <fg=cyan>{$modelName}</> (Demo)");
        $this->line('─────────────────────────────────');

        // List generated files
        $files = $this->getGeneratedFilesList($modelName, $choices);
        foreach ($files as $category => $fileList) {
            $this->line("<fg=yellow>{$category}:</>");
            foreach ($fileList as $file) {
                $this->line("  ✓ {$file}");
            }
            $this->newLine();
        }

        // Show next steps
        $this->displayNextSteps($modelName, $choices);
    }

    /**
     * Get list of generated files categorized
     */
    protected function getGeneratedFilesList(string $modelName, array $choices): array
    {
        $files = [
            'Core Files' => [
                "app/Models/{$modelName}.php",
                "app/Http/Requests/Store{$modelName}Request.php",
                "app/Http/Requests/Update{$modelName}Request.php",
            ]
        ];

        if ($choices['repository'] !== 'No') {
            $files['Repository Pattern'] = [
                "app/Repositories/{$modelName}Repository.php"
            ];
            if ($choices['repository'] === 'Yes, with interface') {
                $files['Repository Pattern'][] = "app/Repositories/Contracts/{$modelName}RepositoryInterface.php";
            }
        }

        if ($choices['service'] !== 'No') {
            $files['Service Layer'] = [
                "app/Services/{$modelName}Service.php"
            ];
            if ($choices['service'] === 'Yes, with interface') {
                $files['Service Layer'][] = "app/Services/Contracts/{$modelName}ServiceInterface.php";
            }
        }

        $controllers = [];
        if ($choices['controller'] !== 'Web Only') {
            $controllers[] = "app/Http/Controllers/Api/{$modelName}Controller.php";
        }
        if ($choices['controller'] !== 'API Only') {
            $controllers[] = "app/Http/Controllers/{$modelName}Controller.php";
        }
        if (!empty($controllers)) {
            $files['Controllers'] = $controllers;
        }

        return $files;
    }

    /**
     * Display helpful next steps
     */
    protected function displayNextSteps(string $modelName, array $choices): void
    {
        $this->info('🚀 Next Steps:');
        $this->line('─────────────');
        
        $steps = [
            "🔄 Run migrations: <fg=yellow>php artisan migrate</>",
            "🌱 Create factory & seeder: <fg=yellow>php artisan make:factory {$modelName}Factory</>",
            "🧪 Create tests: <fg=yellow>php artisan make:test {$modelName}Test</>",
        ];

        if ($choices['controller'] !== 'Web Only') {
            $steps[] = "📚 Check API routes: <fg=yellow>php artisan route:list --path=api</>"; 
        }

        if ($choices['controller'] !== 'API Only') {
            $steps[] = "🌐 Check web routes: <fg=yellow>php artisan route:list --path=web</>";
        }

        foreach ($steps as $step) {
            $this->line("  {$step}");
        }

        $this->newLine();
        $this->line("💡 <fg=cyan>Tip:</> Use <fg=yellow>php artisan easy-dev:help</> for more commands!");
        $this->line("💡 <fg=cyan>Note:</> This is a demo! Use <fg=yellow>php artisan easy-dev:crud</> for actual generation!");
    }

    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::OPTIONAL, 'The name of the model'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['with-repository', null, InputOption::VALUE_NONE, 'Generate repository pattern'],
            ['with-service', null, InputOption::VALUE_NONE, 'Generate service layer'],
            ['without-interface', null, InputOption::VALUE_NONE, 'Skip interface generation'],
            ['api-only', null, InputOption::VALUE_NONE, 'Generate API controller only'],
            ['web-only', null, InputOption::VALUE_NONE, 'Generate web controller only'],
            ['interactive', 'i', InputOption::VALUE_NONE, 'Run in interactive mode'],
        ];
    }
}
