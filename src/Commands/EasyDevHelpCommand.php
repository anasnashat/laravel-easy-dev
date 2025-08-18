<?php

namespace AnasNashat\EasyDev\Commands;

use Illuminate\Console\Command;

class EasyDevHelpCommand extends Command
{
    protected $name = 'easy-dev:help';
    protected $description = 'Show all available Easy Dev commands and usage examples.';

    public function handle(): int
    {
        $this->showBanner();
        $this->showCommands();
        $this->showExamples();
        $this->showTips();

        return self::SUCCESS;
    }

    /**
     * Show the package banner.
     */
    protected function showBanner(): void
    {
        $this->line('');
        $this->line('<fg=blue>╔══════════════════════════════════════════════════════════════╗</>');
        $this->line('<fg=blue>║</>                    <fg=yellow>Laravel Easy Dev Package</>                    <fg=blue>║</>');
        $this->line('<fg=blue>║</>              <fg=gray>Speed up your Laravel development!</fg=gray>              <fg=blue>║</>');
        $this->line('<fg=blue>╚══════════════════════════════════════════════════════════════╝</>');
        $this->line('');
    }

    /**
     * Show available commands.
     */
    protected function showCommands(): void
    {
        $this->line('<fg=green>Available Commands:</>');
        $this->line('');

        $commands = [
            'easy-dev:crud' => [
                'description' => 'Generate complete CRUD with optional Repository and Service patterns',
                'options' => '--with-repository, --with-service, --api-only, --web-only, --without-interface'
            ],
            'easy-dev:repository' => [
                'description' => 'Generate repository pattern (Interface + Implementation)',
                'options' => '--without-interface'
            ],
            'easy-dev:api-resource' => [
                'description' => 'Generate API resource and collection classes',
                'options' => '--without-collection'
            ],
            'easy-dev:add-relation' => [
                'description' => 'Add a relationship method to existing model',
                'options' => '--method'
            ],
            'easy-dev:sync-relations' => [
                'description' => 'Auto-detect and add relationships from database schema',
                'options' => '--all'
            ],
        ];

        foreach ($commands as $command => $details) {
            $this->line("  <fg=yellow>{$command}</>");
            $this->line("    {$details['description']}");
            if (!empty($details['options'])) {
                $this->line("    <fg=gray>Options: {$details['options']}</>");
            }
            $this->line('');
        }
    }

    /**
     * Show usage examples.
     */
    protected function showExamples(): void
    {
        $this->line('<fg=green>Usage Examples:</>');
        $this->line('');

        $examples = [
            'Generate complete CRUD with Repository and Service:' => 'php artisan easy-dev:crud Post --with-repository --with-service',
            'Generate API-only CRUD with Repository:' => 'php artisan easy-dev:crud Post --with-repository --api-only',
            'Generate web-only CRUD with Service:' => 'php artisan easy-dev:crud Post --with-service --web-only',
            'Generate basic CRUD:' => 'php artisan easy-dev:crud Post',
            'Generate repository pattern:' => 'php artisan easy-dev:repository Post',
            'Generate API resources:' => 'php artisan easy-dev:api-resource Post',
            'Add a belongsTo relation:' => 'php artisan easy-dev:add-relation Post belongsTo User',
            'Auto-sync all model relations:' => 'php artisan easy-dev:sync-relations --all',
            'Sync relations for specific model:' => 'php artisan easy-dev:sync-relations Post',
        ];

        foreach ($examples as $description => $command) {
            $this->line("  <fg=gray>{$description}</>");
            $this->line("  <fg=cyan>{$command}</>");
            $this->line('');
        }
    }

    /**
     * Show helpful tips.
     */
    protected function showTips(): void
    {
        $this->line('<fg=green>💡 Tips:</>');
        $this->line('');
        $this->line('  • Use <fg=yellow>easy-dev:sync-relations --all</> to automatically detect relationships');
        $this->line('  • Generate API resources after creating your models for clean API responses');
        $this->line('  • Use repository pattern for better testability and separation of concerns');
        $this->line('  • Publish stubs to customize code generation: <fg=cyan>php artisan vendor:publish --tag=easy-dev-stubs</>');
        $this->line('  • Publish config to customize paths: <fg=cyan>php artisan vendor:publish --tag=easy-dev-config</>');
        $this->line('');
        $this->line('<fg=gray>For more information, visit: https://github.com/anasnashat/laravel-easy-dev</>');
        $this->line('');
    }
}
