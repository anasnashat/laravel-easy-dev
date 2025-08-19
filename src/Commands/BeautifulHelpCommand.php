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
                'description' => 'Enhanced CRUD generator with interactive UI',
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
                'category' => 'Utilities'
            ],
            [
                'command' => 'easy-dev:sync-relations {model?}',
                'description' => 'Auto-detect and add relationships to models',
                'icon' => '🔄',
                'category' => 'Utilities'
            ],
            [
                'command' => 'easy-dev:help',
                'description' => 'Show this beautiful help guide',
                'icon' => '❓',
                'category' => 'Help'
            ]
        ];

        $currentCategory = '';
        foreach ($commands as $cmd) {
            if ($currentCategory !== $cmd['category']) {
                if ($currentCategory !== '') $this->newLine();
                $this->line("<fg=yellow;options=bold>{$cmd['category']} Commands:</>");
                $this->line(str_repeat('─', 20));
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
                'title' => 'Basic CRUD Generation',
                'command' => 'php artisan easy-dev:make Product',
                'description' => 'Creates basic CRUD with interactive wizard'
            ],
            [
                'title' => 'Full Architecture Pattern',
                'command' => 'php artisan easy-dev:crud Order --with-repository --with-service',
                'description' => 'Generates Repository + Service layers with interfaces'
            ],
            [
                'title' => 'API-Only Development',
                'command' => 'php artisan easy-dev:crud User --api-only --with-repository',
                'description' => 'Creates API-focused CRUD with repository pattern'
            ],
            [
                'title' => 'Quick Repository Addition',
                'command' => 'php artisan easy-dev:repository Customer',
                'description' => 'Adds repository pattern to existing model'
            ],
            [
                'title' => 'Relationship Discovery',
                'command' => 'php artisan easy-dev:sync-relations',
                'description' => 'Auto-detects and adds all model relationships'
            ],
            [
                'title' => 'Interactive Mode',
                'command' => 'php artisan easy-dev:make --interactive',
                'description' => 'Guided setup with beautiful UI'
            ]
        ];

        foreach ($examples as $i => $example) {
            $this->line("<fg=cyan;options=bold>" . ($i + 1) . ". {$example['title']}</>");
            $this->line("   <fg=green>{$example['command']}</>");
            $this->line("   <fg=gray>{$example['description']}</>");
            $this->newLine();
        }

        $this->info('🔥 Pro Tips:');
        $this->line('─────────────');
        $this->line('• Use <fg=yellow>--interactive</> for guided setup');
        $this->line('• Combine <fg=yellow>--with-repository --with-service</> for clean architecture');
        $this->line('• Run <fg=yellow>easy-dev:sync-relations</> after creating models');
        $this->line('• Use <fg=yellow>--api-only</> for headless applications');
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
    $this->line('│                 Built with ❤️  for the Laravel community                      │');
    $this->line('│                                                                              │');
    $this->line('╰──────────────────────────────────────────────────────────────────────────────╯');

    $this->newLine();
    $this->line('<fg=yellow>Thank you for using Laravel Easy Dev! 🚀</>');
    $this->newLine();
}



    }

