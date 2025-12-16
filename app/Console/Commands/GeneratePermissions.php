<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class GeneratePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate permissions dynamically based on models in app/Models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $permissionsConfig = config('permissions');

        if (!$permissionsConfig) {
            $this->error('Config file "permissions.php" not found or empty.');
            return 1;
        }

        $count = 0;

        foreach ($permissionsConfig as $model => $actions) {
            foreach ($actions as $action) {
                // Format permission name as "<action> <model>"
                $permissionName = strtolower(trim($action)) . ' ' . strtolower(trim($model));

                // Check if permission exists
                if (Permission::where('name', $permissionName)->exists()) {
                    $this->info("Permission '{$permissionName}' already exists.");
                    continue;
                }

                // Create permission
                Permission::create(['name' => $permissionName]);
                $this->info("Created permission: {$permissionName}");
                $count++;
            }
        }

        $this->info("Total new permissions created: {$count}");
        return 0;
    }
}
