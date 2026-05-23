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
            'easy-dev:make' => [
                'description' => 'Enhanced CRUD generator with a beautiful interactive UI wizard',
                'options' => '--with-repository, --with-service, --api-only, --web-only, --without-interface, --interactive, --force, --tests'
            ],
            'easy-dev:crud' => [
                'description' => 'Classic CRUD generator supporting Repository and Service layers',
                'options' => '--with-repository, --with-service, --api-only, --web-only, --without-interface, --stub, --path, --module, --ai'
            ],
            'easy-dev:repository' => [
                'description' => 'Generate repository pattern (Interface + Implementation)',
                'options' => '--without-interface, --stub, --path, --module, --ai'
            ],
            'easy-dev:api-resource' => [
                'description' => 'Generate API resource and collection classes',
                'options' => '--collection, --resource, --stub, --path, --module, --ai'
            ],
            'easy-dev:policy' => [
                'description' => 'Generate custom authorization policy for a model',
                'options' => '--stub, --path, --module, --ai'
            ],
            'easy-dev:dto' => [
                'description' => 'Generate Data Transfer Object (DTO) data class',
                'options' => '--stub, --path, --module, --ai'
            ],
            'easy-dev:observer' => [
                'description' => 'Generate database lifecycle observer for a model',
                'options' => '--stub, --path, --module, --ai'
            ],
            'easy-dev:filter' => [
                'description' => 'Generate query filter class for advanced search filters',
                'options' => '--stub, --path, --module, --ai'
            ],
            'easy-dev:enum' => [
                'description' => 'Generate schema/status PHP enums',
                'options' => '--stub, --path, --module, --ai'
            ],
            'easy-dev:sync-relations' => [
                'description' => 'Auto-detect and add relationships from database schema to models',
                'options' => '--all, --morph-targets'
            ],
            'easy-dev:add-relation' => [
                'description' => 'Manually add a relationship method to an existing model',
                'options' => '--method, --foreign-key, --local-key, --pivot-table'
            ],
            'easy-dev:publish-stubs' => [
                'description' => 'Publish package stubs and easy-dev-ai.md skill to your application',
                'options' => '--only, --list, --ai'
            ],
            'easy-dev:ai-context' => [
                'description' => 'Extract high-density, token-efficient JSON context map for AI',
                'options' => '--pretty'
            ],
            'easy-dev:snapshot' => [
                'description' => 'Print high-density database and models schema snapshot',
                'options' => '--ai'
            ],
            'easy-dev:info' => [
                'description' => 'Get a comprehensive audit report of a specific model',
                'options' => '--ai'
            ],
            'easy-dev:dream' => [
                'description' => 'Scaffold a model, migration, and CRUD using natural language',
                'options' => '--dry-run, --ai'
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
            'Generate CRUD inside a Billing domain module:' => 'php artisan easy-dev:crud Order --module=Billing --with-repository',
            'Generate repository under custom path with custom stub:' => 'php artisan easy-dev:repository Product --path=app/Custom/Repos --stub=my-custom-repo',
            'Execute observer generator in silent AI mode:' => 'php artisan easy-dev:observer User --ai',
            'Publish only specific stubs:' => 'php artisan easy-dev:publish-stubs --only=model,controller',
            'Extract project schema and state for AI agent:' => 'php artisan easy-dev:ai-context --pretty',
            'Scaffold model using natural language:' => 'php artisan easy-dev:dream "Create post with title:string, body:text connected to users" --dry-run',
            'Add belongsTo relation:' => 'php artisan easy-dev:add-relation Post belongsTo User',
            'Auto-sync all model relations from database schema:' => 'php artisan easy-dev:sync-relations --all',
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
        $this->line('<fg=green>💡 Tips & AI-Native Features:</>');
        $this->line('');
        $this->line('  • Run <fg=yellow>php artisan easy-dev:publish-stubs</> to publish stubs and generate the <fg=cyan>easy-dev-ai.md</> AI agent skill.');
        $this->line('  • Feed <fg=yellow>easy-dev:ai-context</> output to your AI coding assistant so it instantly understands your project architecture.');
        $this->line('  • Use the <fg=yellow>--ai</> option on any generator to suppress interactive outputs and receive clean machine-friendly JSON.');
        $this->line('  • Organize your code using Domain Modules by adding <fg=cyan>--module=DomainName</> to commands.');
        $this->line('');
        $this->line('<fg=gray>For more information, visit: https://github.com/anasnashat/laravel-easy-dev</>');
        $this->line('');
    }
}
