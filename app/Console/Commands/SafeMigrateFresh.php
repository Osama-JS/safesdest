<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SafeMigrateFresh extends Command
{
    /**
     * Overrides the built-in migrate:fresh command.
     * Permanently blocked in production environment.
     */
    protected $signature = 'migrate:fresh
                            {--database= : The database connection to use}
                            {--drop-views : Drop all tables and views}
                            {--drop-types : Drop all tables and types (Postgres only)}
                            {--force : Force the operation to run when in production}
                            {--path=* : The path(s) to the migrations files to be executed}
                            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                            {--schema-path= : The path to a prepared database schema}
                            {--seed : Indicates if the seed task should be re-run}
                            {--seeder= : The class name of the root seeder}
                            {--step : Force the migrations to be run so they can be rolled back individually}';

    protected $description = '[BLOCKED IN PRODUCTION] Drop all tables and re-run all migrations';

    public function handle(): int
    {
        // Permanently block in production - --force flag is intentionally ignored
        if (app()->environment('production')) {
            $this->newLine();
            $this->error('  ======================================================');
            $this->error('  COMMAND BLOCKED IN PRODUCTION');
            $this->error('  migrate:fresh DESTROYS ALL DATA.');
            $this->error('  This command is permanently disabled in production.');
            $this->error('  ======================================================');
            $this->newLine();
            $this->info('  Use: php artisan migrate   (to apply new migrations safely)');
            $this->newLine();

            Log::critical('SECURITY: migrate:fresh was attempted in production and was BLOCKED!', [
                'user' => get_current_user(),
                'host' => gethostname(),
                'time' => now()->toDateTimeString(),
            ]);

            return Command::FAILURE;
        }

        // On non-production, require explicit confirmation
        if (!$this->confirm(
            'This will DROP ALL TABLES and re-run all migrations. All data will be LOST. Continue?',
            false
        )) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->info('Running migrate:fresh on [' . app()->environment() . '] environment...');

        return $this->call('migrate', ['--fresh' => true, '--force' => true]);
    }
}

