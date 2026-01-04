<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking permissions...\n";

// 1. Check if permission exists
$perm = Permission::where('name', 'view service document')->first();
if ($perm) {
    echo "Permission 'view service document' EXISTS (id: {$perm->id})\n";
} else {
    echo "Permission 'view service document' DOES NOT EXIST in DB.\n";
}

// 2. Check role
$role = Role::where('name', 'Admin de cellule')->first();
if ($role) {
    echo "Role 'Admin de cellule' EXISTS (id: {$role->id})\n";
    
    // Check if role has permission
    if ($role->hasPermissionTo('view service document')) {
       echo "Role HAS 'view service document' permission.\n";
    } else {
       echo "Role DOES NOT HAVE 'view service document' permission.\n";
       
       // Try to fix it if permission exists
       if ($perm) {
           echo "Attempting to assign permission to role...\n";
           try {
               $role->givePermissionTo($perm);
               echo "Permission assigned successfully.\n";
           } catch (\Exception $e) {
               echo "Failed to assign: " . $e->getMessage() . "\n";
           }
       }
    }
} else {
    echo "Role 'Admin de cellule' DOES NOT EXIST.\n";
}

// 3. Check for specific user (if we knew one, but we'll list users with this role)
$users = User::role('Admin de cellule')->get();
echo "Found " . $users->count() . " users with 'Admin de cellule' role.\n";
foreach ($users as $user) {
    echo "- User: {$user->email} (ID: {$user->id})\n";
    if ($user->can('view service document')) {
        echo "  - Can 'view service document'\n";
    } else {
        echo "  - CANNOT 'view service document'\n";
    }
    
    // Check policy manually
    $policy = new \App\Policies\DocumentDestructionRequestPolicy();
    $canViewAny = $policy->viewAny($user);
    echo "  - Policy viewAny(): " . ($canViewAny ? 'TRUE' : 'FALSE') . "\n";
}
