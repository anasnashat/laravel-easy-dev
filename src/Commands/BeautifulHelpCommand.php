<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;

class BeautifulHelpCommand extends Command
{
    protected $signature = 'easy-dev:help {--examples : Show usage examples}';
    protected $description = 'Beautiful help guide for Laravel Easy Dev package';

    public function handle()
    {
        $this->displayWelcomeBanner();
        $this->displayCommands();
        $this->displayOptions();
        
        if ($this->option('examples')) {
            $this->displayExamples();
        } else {
            $this->info("💡 Use --examples flag to see usage examples!");
        }
        
        $this->displayFooter();
    }

    protected function displayWelcomeBanner(): void
    {
        $this->newLine();
        $this->line('╭─────────────────────────────────────────────────────────────╮');
        $this->line('│                                                             │');
        $this->line('│   📚 <fg=cyan;options=bold>Laravel Easy Dev - Help Guide</> 📚                │');
        $this->line('│                                                             │');
        $this->line('│   <fg=green>Supercharge your Laravel development workflow</>            │');
        $this->line('│                                                             │');
        $this->line('╰─────────────────────────────────────────────────────────────╯');
        $this->newLine();
    }

    protected function displayCommands(): void
    {
        $this->info('🚀 Available Commands:');
        $this->line('══════════════════════');
        $this->newLine();

        $commands = [
            [
                'command' => 'easy-dev:make {model}',
                'description' => 'Enhanced CRUD generator with interactive UI wizard',
                'icon' => '🎯',
                'category' => 'Primary'
            ],
            [
                'command' => 'easy-dev:crud {model}',
                'description' => 'Classic CRUD generator with Repository and Service patterns',
                'icon' => '🏗️',
                'category' => 'Primary'
            ],
            [
                'command' => 'easy-dev:repository {model}',
                'description' => 'Generate repository pattern for existing model',
                'icon' => '🗄️',
                'category' => 'Dynamic & Patterns'
            ],
            [
                'command' => 'easy-dev:api-resource {model}',
                'description' => 'Generate API resource and collection classes',
                'icon' => '🌐',
                'category' => 'Dynamic & Patterns'
            ],
            [
                'command' => 'easy-dev:policy {model}',
                'description' => 'Generate custom authorization policy',
                'icon' => '🛡️',
                'category' => 'Dynamic & Patterns'
            ],
            [
                'command' => 'easy-dev:dto {model}',
                'description' => 'Generate Data Transfer Object (DTO) data class',
                'icon' => '📦',
                'category' => 'Dynamic & Patterns'
            ],
            [
                'command' => 'easy-dev:observer {model}',
                'description' => 'Generate database lifecycle observer',
                'icon' => '👁️',
                'category' => 'Dynamic & Patterns'
            ],
            [
                'command' => 'easy-dev:filter {model}',
                'description' => 'Generate query filter class for advanced search filters',
                'icon' => '🔍',
                'category' => 'Dynamic & Patterns'
            ],
            [
                'command' => 'easy-dev:enum {name}',
                'description' => 'Generate schema/status PHP enums',
                'icon' => '🏷️',
                'category' => 'Dynamic & Patterns'
            ],
            [
                'command' => 'easy-dev:publish-stubs',
                'description' => 'Publish package stubs and easy-dev-ai.md skill to your application',
                'icon' => '📤',
                'category' => 'AI-Native Integration'
            ],
            [
                'command' => 'easy-dev:ai-context',
                'description' => 'Extract high-density, token-efficient JSON context map for AI',
                'icon' => '🤖',
                'category' => 'AI-Native Integration'
            ],
            [
                'command' => 'easy-dev:snapshot',
                'description' => 'Print high-density database and models schema snapshot',
                'icon' => '📸',
                'category' => 'AI-Native Integration'
            ],
            [
                'command' => 'easy-dev:info {model}',
                'description' => 'Get a comprehensive audit report of a specific model',
                'icon' => '📊',
                'category' => 'AI-Native Integration'
            ],
            [
                'command' => 'easy-dev:dream {prompt}',
                'description' => 'Scaffold a model, migration, and CRUD using natural language',
                'icon' => '✨',
                'category' => 'AI-Native Integration'
            ],
            [
                'command' => 'easy-dev:sync-relations {model?}',
                'description' => 'Auto-detect and add relationships to models',
                'icon' => '🔄',
                'category' => 'Utilities & Relations'
            ],
            [
                'command' => 'easy-dev:add-relation {model} {type} {related}',
                'description' => 'Manually add a relationship method to existing model',
                'icon' => '🔗',
                'category' => 'Utilities & Relations'
            ],
            [
                'command' => 'easy-dev:demo-ui',
                'description' => 'Demonstrate package\'s beautiful UI capabilities',
                'icon' => '🎨',
                'category' => 'Help & Utilities'
            ],
            [
                'command' => 'easy-dev:help',
                'description' => 'Show this beautiful help guide',
                'icon' => '❓',
                'category' => 'Help & Utilities'
            ]
        ];

        $currentCategory = '';
        foreach ($commands as $cmd) {
            if ($currentCategory !== $cmd['category']) {
                if ($currentCategory !== '') $this->newLine();
                $this->line("<fg=yellow;options=bold>{$cmd['category']} Commands:</>");
                $this->line(str_repeat('─', 25));
                $currentCategory = $cmd['category'];
            }
            
            $this->line("  {$cmd['icon']} <fg=green>{$cmd['command']}</>");
            $this->line("     <fg=white>{$cmd['description']}</>");
            $this->newLine();
        }
    }

    protected function displayOptions(): void
    {
        $this->info('⚙️  Command Options:');
        $this->line('═══════════════════');
        $this->newLine();

        $options = [
            [
                'option' => '--with-repository',
                'description' => 'Generate repository pattern with interface',
                'example' => 'Adds Repository and RepositoryInterface',
                'icon' => '🗄️'
            ],
            [
                'option' => '--with-service',
                'description' => 'Generate service layer with business logic',
                'example' => 'Adds Service and ServiceInterface',
                'icon' => '🔧'
            ],
            [
                'option' => '--without-interface',
                'description' => 'Skip interface generation (use with above options)',
                'example' => 'Only concrete classes, no interfaces',
                'icon' => '⚡'
            ],
            [
                'option' => '--api-only',
                'description' => 'Generate API controller only',
                'example' => 'Creates API routes and controller',
                'icon' => '🌐'
            ],
            [
                'option' => '--web-only',
                'description' => 'Generate web controller only',
                'example' => 'Creates web routes and controller',
                'icon' => '🖥️'
            ],
            [
                'option' => '--interactive',
                'description' => 'Run in interactive mode with guided setup',
                'example' => 'Step-by-step configuration wizard',
                'icon' => '🎮'
            ],
            [
                'option' => '--stub=NAME_OR_PATH',
                'description' => 'Override default stub name or pass an absolute path to a custom stub',
                'example' => 'Loads resources/stubs/vendor/easy-dev/NAME.stub or custom path',
                'icon' => '📝'
            ],
            [
                'option' => '--path=DIRECTORY',
                'description' => 'Override the target folder directory for the generated file',
                'example' => 'Outputs file under app/Custom/Path instead of default',
                'icon' => '📁'
            ],
            [
                'option' => '--module=MODULE',
                'description' => 'Place all generated files under a Domain Module structure',
                'example' => 'Structures files inside app/Modules/Billing/',
                'icon' => '🧱'
            ],
            [
                'option' => '--ai',
                'description' => 'Silent mode that returns structured JSON instead of interactive output',
                'example' => 'Suppress interactive text, perfect for AI parsing',
                'icon' => '🤖'
            ]
        ];

        foreach ($options as $opt) {
            $this->line("  {$opt['icon']} <fg=yellow>{$opt['option']}</>");
            $this->line("     <fg=white>{$opt['description']}</>");
            $this->line("     <fg=gray>→ {$opt['example']}</>");
            $this->newLine();
        }
    }

    protected function displayExamples(): void
    {
        $this->newLine();
        $this->info('💡 Usage Examples:');
        $this->line('══════════════════');
        $this->newLine();

        $examples = [
            [
                'title' => 'Basic CRUD Scaffolding',
                'command' => 'php artisan easy-dev:make Product',
                'description' => 'Creates basic CRUD with interactive wizard'
            ],
            [
                'title' => 'Modular Scaffolding with Repository',
                'command' => 'php artisan easy-dev:crud Order --module=Billing --with-repository',
                'description' => 'Generates Billing module domain files with repository layer'
            ],
            [
                'title' => 'Custom Path & Custom Stub',
                'command' => 'php artisan easy-dev:repository Product --path=app/Custom/Repos --stub=my-repo',
                'description' => 'Generates Product repository class under custom path using custom stub'
            ],
            [
                'title' => 'Extraction of Context Map for AI Assistant',
                'command' => 'php artisan easy-dev:ai-context --pretty',
                'description' => 'Extracts comprehensive schema context for feeding to an AI agent'
            ],
            [
                'title' => 'Natural Language Entity Creation',
                'command' => 'php artisan easy-dev:dream "Create a post with title:string, body:text connected to users" --dry-run',
                'description' => 'Dumps compilation blueprint representing natural language entity'
            ],
            [
                'title' => 'Silent Generator in AI Mode',
                'command' => 'php artisan easy-dev:enum OrderStatus --ai',
                'description' => 'Creates order status enum and outputs structured JSON'
            ],
            [
                'title' => 'Full Architecture Pattern (Classic)',
                'command' => 'php artisan easy-dev:crud Order --with-repository --with-service',
                'description' => 'Generates Repository + Service layers with interfaces'
            ],
            [
                'title' => 'Relationship Discovery & Syncing',
                'command' => 'php artisan easy-dev:sync-relations --all',
                'description' => 'Auto-detects and adds all model relationships based on database schema'
            ]
        ];

        foreach ($examples as $i => $example) {
            $this->line("<fg=cyan;options=bold>" . ($i + 1) . ". {$example['title']}</>");
            $this->line("   <fg=green>{$example['command']}</>");
            $this->line("   <fg=gray>{$example['description']}</>");
            $this->newLine();
        }

        $this->info('🔥 Pro Tips & AI-Native Integration:');
        $this->line('──────────────────────────────────────');
        $this->line('• Run <fg=yellow>easy-dev:publish-stubs</> to publish stubs and generate the <fg=cyan>easy-dev-ai.md</> AI agent skill.');
        $this->line('• Feed the JSON output of <fg=yellow>easy-dev:ai-context</> directly to your AI to make it project-aware.');
        $this->line('• Use the <fg=yellow>--ai</> flag to bypass interactive CLI dialogs and get structured JSON logs.');
        $this->line('• Keep your application clean and domain-driven by using the <fg=yellow>--module</> option.');
        $this->newLine();
    }
protected function displayFooter(): void
{
    $this->newLine();
    $this->line('╭──────────────────────────────────────────────────────────────────────────────╮');
    $this->line('│                                                                              │');
    $this->line('│                          <fg=cyan;options=bold>🌟 Get Involved 🌟</>                                  │');
    $this->line('│                                                                              │');
    $this->line('│  📖 <fg=green;options=bold>Documentation</>  │ <fg=blue>github.com/anasnashat/laravel-easy-dev/wiki</>             │');
    $this->line('│  🐛 <fg=red;options=bold>Report Issues</>    │ <fg=magenta>github.com/anasnashat/laravel-easy-dev/issues</>         │');
    $this->line('│  ⭐ <fg=yellow;options=bold>Give us a Star</> │ <fg=white>github.com/anasnashat/laravel-easy-dev</>                  │');
    $this->line('│  💬 <fg=cyan;options=bold>Discussions</>     │ <fg=cyan>Join our community discussions</>                         │');
    $this->line('│                                                                              │');
    $this->line('│                 Built with ❤️  for the Laravel community                     │');
    $this->line('│                                                                              │');
    $this->line('╰──────────────────────────────────────────────────────────────────────────────╯');

    $this->newLine();
    $this->line('<fg=yellow>Thank you for using Laravel Easy Dev! 🚀</>');
    $this->newLine();
}



    }

