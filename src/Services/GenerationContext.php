<?php

namespace AnasNashat\EasyDev\Services;

use Illuminate\Filesystem\Filesystem;

/**
 * Tracks all files created/modified during a generation run.
 * Supports dry-run mode and rollback on failure.
 */
class GenerationContext
{
    /** @var array<string, string> Files created during this run [path => type] */
    protected array $createdFiles = [];

    /** @var array<string, string> Files modified during this run [path => backup_path] */
    protected array $modifiedFiles = [];

    /** @var bool Whether this is a dry-run (no files written) */
    protected bool $dryRun = false;

    /** @var array<string> Log of actions taken (for dry-run output) */
    protected array $actionLog = [];

    public function __construct(protected Filesystem $files)
    {
    }

    /**
     * Enable or disable dry-run mode.
     */
    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;
        return $this;
    }

    /**
     * Check if this is a dry-run.
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Record that a file will be created.
     * In dry-run mode, only logs the action without creating anything.
     */
    public function recordCreatedFile(string $path, string $type = 'file'): void
    {
        $this->createdFiles[$path] = $type;
        $this->actionLog[] = "[CREATE] {$type}: {$path}";
    }

    /**
     * Record that a file will be modified (creates a .bak backup first).
     * In dry-run mode, only logs the action without modifying anything.
     */
    public function recordModifiedFile(string $path): void
    {
        if (!$this->dryRun && $this->files->exists($path)) {
            $backupPath = $path . '.bak';
            $this->files->copy($path, $backupPath);
            $this->modifiedFiles[$path] = $backupPath;
        }

        $this->actionLog[] = "[MODIFY] {$path}";
    }

    /**
     * Rollback all changes — delete created files, restore backups.
     */
    public function rollback(): void
    {
        // Delete all created files
        foreach ($this->createdFiles as $path => $type) {
            if ($this->files->exists($path)) {
                $this->files->delete($path);
            }
        }

        // Restore all modified files from backups
        foreach ($this->modifiedFiles as $originalPath => $backupPath) {
            if ($this->files->exists($backupPath)) {
                $this->files->move($backupPath, $originalPath);
            }
        }

        $this->createdFiles = [];
        $this->modifiedFiles = [];
    }

    /**
     * Clean up backup files after successful generation.
     */
    public function cleanupBackups(): void
    {
        foreach ($this->modifiedFiles as $originalPath => $backupPath) {
            if ($this->files->exists($backupPath)) {
                $this->files->delete($backupPath);
            }
        }
    }

    /**
     * Get a summary of all actions (for dry-run output).
     */
    public function getDryRunSummary(): array
    {
        return $this->actionLog;
    }

    /**
     * Get the list of created files.
     */
    public function getCreatedFiles(): array
    {
        return $this->createdFiles;
    }

    /**
     * Get the list of modified files.
     */
    public function getModifiedFiles(): array
    {
        return $this->modifiedFiles;
    }

    /**
     * Get a count summary.
     */
    public function getSummary(): array
    {
        return [
            'created' => count($this->createdFiles),
            'modified' => count($this->modifiedFiles),
            'total_actions' => count($this->actionLog),
        ];
    }

    /**
     * Reset the context for a new generation run.
     */
    public function reset(): void
    {
        $this->createdFiles = [];
        $this->modifiedFiles = [];
        $this->actionLog = [];
        $this->dryRun = false;
    }
}
