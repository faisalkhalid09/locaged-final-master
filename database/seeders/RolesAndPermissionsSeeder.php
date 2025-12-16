<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ---------------------------------------------------------------------
        // 1. Define all permission names used in the application (plus new ones)
        // ---------------------------------------------------------------------
        $permissionNames = [
            // Documents
            'view any document',
            'view department document',
            'view service document', // NEW: service-level scope
            'view own document',
            'create document',
            'update document',
            'delete document',
            'restore document',
            'forceDelete document',
            'approve document',
            'decline document',

            // Users
            'view any user',
            'view department user',
            'view service user', // NEW: service-level user visibility
            'view own user',
            'create user',
            'update user',
            'delete user',
            'restore user',
            'forceDelete user',

            // Departments
            'view any department',
            'create department',
            'update department',
            'delete department',
            'restore department',
            'forceDelete department',

            // Roles
            'view any role',
            'create role',
            'update role',
            'delete role',
            'restore role',
            'forceDelete role',

            // Categories
            'view any category',
            'create category',
            'update category',
            'delete category',
            'restore category',
            'forceDelete category',

            // Tags
            'view any tag',
            'create tag',
            'update tag',
            'delete tag',
            'restore tag',
            'forceDelete tag',

            // Physical locations
            'view any physical location',
            'create physical location',
            'update physical location',
            'delete physical location',
            'restore physical location',
            'forceDelete physical location',

            // Services (logical services under sub-departments)
            'view any service',
            'create service',
            'update service',
            'delete service',
            'restore service',
            'forceDelete service',

            // Workflow rules
            'view any workflow rule',
            'view department workflow rule',
            'create workflow rule',
            'update workflow rule',
            'delete workflow rule',
            'restore workflow rule',
            'forceDelete workflow rule',

            // Document destruction requests
            'view any document destruction request',
            'view department document destruction request',
            'view own document destruction request',
            'create document destruction request',
            'update document destruction request',
            'delete document destruction request',
            'restore document destruction request',
            'forceDelete document destruction request',
            'approve document destruction request',
            'decline document destruction request',

            // OCR jobs
            'view any ocr job',
            'view department ocr job',
            'view own ocr job',
            'create ocr job',
            'update ocr job',
            'delete ocr job',
            'restore ocr job',
            'forceDelete ocr job',

            // UI Translations / Localization
            'view any ui translation',
            'create ui translation',
            'update ui translation',
            'delete ui translation',
            'restore ui translation',
            'forceDelete ui translation',
        ];

        // Create (or find) all permissions
        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[$name] = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // Helper to fetch Permission models by name
        $pick = function (array $names) use ($permissions) {
            return collect($names)
                ->filter(fn ($name) => isset($permissions[$name]))
                ->map(fn ($name) => $permissions[$name])
                ->all();
        };

        // ------------------------------------------------------------------
        // 2. Define per-role permission sets based on required responsibilities
        // ------------------------------------------------------------------

        // Master: full access to everything
        $masterRole = Role::firstOrCreate(['name' => 'master']);
        $masterRole->syncPermissions(array_values($permissions));

        // Super Administrator (General Direction)
        $superAdminPermissions = $pick([
            // Documents
            'view any document',
            'create document',
            'update document',
            'delete document',
            'approve document',
            'decline document',

            // Users
            'view any user',
            'create user',
            'update user',
            'delete user',

            // Departments
            'view any department',
            'create department',
            'update department',
            'delete department',

            // Categories / Tags / Locations / Services
            'view any category', 'create category', 'update category', 'delete category',
            'view any tag', 'create tag', 'update tag', 'delete tag',
            'view any physical location', 'create physical location', 'update physical location', 'delete physical location',
            'view any service', 'create service', 'update service', 'delete service',

            // Workflow rules
            'view any workflow rule', 'create workflow rule', 'update workflow rule', 'delete workflow rule',

            // Destruction requests
            'view any document destruction request',
            'view department document destruction request',
            'approve document destruction request',
            'decline document destruction request',
            'delete document destruction request',

            // OCR jobs (operational access; Master controls configuration)
            'view any ocr job',
        ]);
        Role::firstOrCreate(['name' => 'Super Administrator'])->syncPermissions($superAdminPermissions);

        // Department admin ("Admin de pole")
        $departmentAdminPermissions = $pick([
            // Documents within own poles/structures
            'view department document',
            'view own document',
            'create document',
            'update document',
            'delete document',
            'approve document',
            'decline document',

            // Users within own departments
            'view department user',
            'view own user',
            'create user',
            'update user',
            'delete user',

            // Read departments to manage their own scope
            'view any department',

            // Tags: View, Create, Update (NO DELETE)
            'view any tag', 'create tag', 'update tag',

            // Categories: View, Create, Update (NO DELETE)
            'view any category', 'create category', 'update category',

            // Physical locations: View, Create, Update (NO DELETE)
            'view any physical location', 'create physical location', 'update physical location',

            // Services / workflow rules for their departments
            'view any service', 'create service', 'update service', 'delete service',
            'view any workflow rule', 'view department workflow rule', 'create workflow rule', 'update workflow rule', 'delete workflow rule',

            // Destruction requests in their departments
            'view department document destruction request',
            'approve document destruction request',
            'decline document destruction request',
        ]);
        $departmentAdminRole = Role::firstOrCreate(['name' => 'Admin de pole']);
        $departmentAdminRole->syncPermissions($departmentAdminPermissions);

        // Sub-department admin ("Admin de departments")
        $divisionChiefPermissions = $pick([
            // Documents within own departments (scoped via poles/sub-departments)
            'view department document',
            'view own document',
            'create document',
            'update document',
            'approve document',
            'decline document',

            // Users within own departments
            'view department user',
            'view own user',
            'create user',
            'update user',

            // Categories: View ONLY (no create/update/delete)
            'view any category',

            // Tags: View, Create, Update (NO DELETE)
            'view any tag', 'create tag', 'update tag',

            // Physical locations: View, Create, Update (NO DELETE)
            'view any physical location', 'create physical location', 'update physical location',
        ]);
        $divisionChiefRole = Role::firstOrCreate(['name' => 'Admin de departments']);
        $divisionChiefRole->syncPermissions($divisionChiefPermissions);

        // Service Manager ("Admin de cellule")
        $serviceManagerPermissions = $pick([
            // Documents in assigned service/cellule
            'view service document',
            'view own document',
            'create document',
            'update document',
            'approve document',
            'decline document',

            // Categories (service-level classification management - NO DELETE)
            'view any category',
            'create category',
            'update category',

            // Tags: View, Create, Update (NO DELETE)
            'view any tag', 'create tag', 'update tag',

            // Users: can manage (create/update) users in their services
            'view service user',
            'create user',
            'update user',

            // Physical locations: View, Create, Update (NO DELETE)
            'view any physical location', 'create physical location', 'update physical location',
        ]);
        $serviceManagerRole = Role::firstOrCreate(['name' => 'Admin de cellule']);
        $serviceManagerRole->syncPermissions($serviceManagerPermissions);

        // Service User → mapped to generic "user" role
        $serviceUserPermissions = $pick([
            // Documents in assigned cellule/service
            'view service document',
            'view own document',
            'create document',
            'update document',

            // Categories: READ-ONLY access (can see but not modify)
            'view any category',

            // Tags: can view + create personal/common tags (no update/delete)
            'view any tag',
            'create tag',
        ]);
        $serviceUserRole = Role::firstOrCreate(['name' => 'user']);
        $serviceUserRole->syncPermissions($serviceUserPermissions);

        // Legacy admin role kept for compatibility
        // Admin: close to Super Admin but without system-level pages
        $adminPermissions = $pick([
            'view any document', 'create document', 'update document', 'delete document', 'approve document', 'decline document',
            'view any user', 'create user', 'update user', 'delete user',
            'view any department', 'create department', 'update department', 'delete department',
            'view any category', 'create category', 'update category', 'delete category',
            'view any tag', 'create tag', 'update tag', 'delete tag',
            'view any physical location', 'create physical location', 'update physical location', 'delete physical location',
            'view any service', 'create service', 'update service', 'delete service',
        ]);
        Role::firstOrCreate(['name' => 'admin'])->syncPermissions($adminPermissions);

        // Note: no separate basic "user" role anymore; "user" is the service-level user.
    }
}
