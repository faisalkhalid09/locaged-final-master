<?php

namespace App\Console\Commands;

use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class InitEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initial configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Running migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $this->info(Artisan::output());

            // --- Roles ---
            $this->info('Creating roles...');
            $masterRole = Role::firstOrCreate(['name' => 'master']);

            // Core functional roles (renamed as requested)
            $superAdminRole      = Role::firstOrCreate(['name' => 'Super Administrator']);
            $departmentAdminRole = Role::firstOrCreate(['name' => 'Admin de pole']);
            $divisionChiefRole   = Role::firstOrCreate(['name' => 'Admin de departments']);
            $serviceManagerRole  = Role::firstOrCreate(['name' => 'Admin de cellule']);
            // Service User is now the generic "user" role
            $serviceUserRole     = Role::firstOrCreate(['name' => 'user']);

            // --- Master/Admin user (existing behaviour) ---
            $email = 'admin@example.com';

            $this->info("Checking if admin user exists...");
            $admin = User::where('email', $email)->first();

            if (!$admin) {
                $this->info('Creating admin user (Master role)...');
                $admin = User::create([
                    'username' => $email, // keep DB non-null, align with policy
                    'full_name' => 'Admin',
                    'email' => $email,
                    'password' => bcrypt('password'), // use bcrypt!
                    'locale' => 'fr', // Default locale is French
                ]);
                $admin->assignRole($masterRole);
                $this->info('Admin user created and master role assigned.');
            } else {
                $this->info('Admin user already exists.');

                if (!$admin->hasRole('master')) {
                    $admin->assignRole($masterRole);
                    $this->info('Master role assigned to existing user.');
                } else {
                    $this->info('Admin already has master role.');
                }
            }

            // --- Additional default users for each new role ---
            $defaultPassword = bcrypt('password'); // shared default, easy to change later

            $usersToCreate = [
                [
                    'email' => 'superadmin@example.com',
                    'full_name' => 'Super Administrator',
                    'role' => $superAdminRole,
                ],
                [
                    'email' => 'deptadmin@example.com',
                    'full_name' => 'Admin de pole',
                    'role' => $departmentAdminRole,
                ],
                [
                    'email' => 'subdeptadmin@example.com',
                    'full_name' => 'Admin de departments',
                    'role' => $divisionChiefRole,
                ],
                [
                    'email' => 'servicemanager@example.com',
                    'full_name' => 'Admin de cellule',
                    'role' => $serviceManagerRole,
                ],
                [
                    'email' => 'serviceuser@example.com',
                    'full_name' => 'User',
                    'role' => $serviceUserRole,
                ],
            ];

            foreach ($usersToCreate as $config) {
                $this->info("Checking if {$config['email']} exists...");

                /** @var \App\Models\User|null $user */
                $user = User::where('email', $config['email'])->first();

                if (!$user) {
                    $this->info("Creating user {$config['email']}...");
                    $user = User::create([
                        'username' => $config['email'],
                        'full_name' => $config['full_name'],
                        'email' => $config['email'],
                        'password' => $defaultPassword,
                        'locale' => 'fr',
                    ]);
                } else {
                    $this->info("User {$config['email']} already exists.");
                }

                if (!$user->hasRole($config['role']->name)) {
                    $user->assignRole($config['role']);
                    $this->info("Role {$config['role']->name} assigned to {$config['email']}.");
                } else {
                    $this->info("User {$config['email']} already has role {$config['role']->name}.");
                }
            }

            // Seed roles and permissions mapping
            $this->info('Seeding roles and permissions...');
            Artisan::call('db:seed', [
                '--class' => \Database\Seeders\RolesAndPermissionsSeeder::class,
                '--force' => true,
            ]);
            $this->info(Artisan::output());

            $this->info('Setup complete.');
        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('InitEnvironment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
