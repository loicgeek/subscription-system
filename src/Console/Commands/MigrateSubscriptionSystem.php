<?php

namespace NtechServices\SubscriptionSystem\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MigrateSubscriptionSystem extends Command
{
    protected $signature = 'migrate:subscription-system {--force : Force the migration}';
    protected $description = 'Migrate the subscription system tables';

    public function handle()
    {
        // Optionally show a message before running migrations
        $this->info('Migrating subscription system tables...');

        // Run the migration for the subscription system
        $migrationPath = base_path('vendor/ntech-services/subscription-system/src/Database/Migrations');

        // Get all migration files from the specified path
        $migrationFiles = File::files($migrationPath);

        foreach ($migrationFiles as $file) {
            // Get the file name
            $fileName = $file->getFilename();
            
            // Check if the file is a migration file
            if (str_ends_with($fileName, '.php')) {
                $this->warn("Migrating: $fileName");
                
                // Call the migration file using Artisan
                try {
                    $exitCode = Artisan::call('migrate', [
                    '--path' => "vendor/ntech-services/subscription-system/src/Database/Migrations/$fileName",
                    '--force' => $this->option('force'),
                ]);
                
                if ($exitCode !== 0) {
                    $this->error("Failed to migrate $fileName\n");
                } else {
                    $this->info("$fileName migrated successfully.\n");
                }
                } catch (\Throwable $th) {
                    $this->error("Failed to migrate $fileName\n");
                    $this->error($th->getMessage());
                    $this->error("\n");
                    //throw $th;
                }
            }
        }

        $this->info('All migrations completed.');
    }
}
