<?php

namespace App\Policies;

use App\Models\PhysicalLocation;
use App\Models\User;

class PhysicalLocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Primary: explicit permission
        if ($user->can('view any physical location')) {
            return true;
        }

        // Fallback: mid-level admins and service managers may always view locations
        return $user->hasAnyRole([
            'Admin de pole',
            'Admin de departments',
            'Admin de cellule',
            'department administrator',
            'division chief',
            'service manager',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PhysicalLocation $physicalLocation): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->can('create physical location')) {
            return true;
        }

        // Allow mid-level admins and service managers to create even if
        // permissions are slightly out of sync.
        return $user->hasAnyRole([
            'Admin de pole',
            'Admin de departments',
            'Admin de cellule',
            'department administrator',
            'division chief',
            'service manager',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PhysicalLocation $physicalLocation): bool
    {
        if ($user->can('update physical location')) {
            return true;
        }

        return $user->hasAnyRole([
            'Admin de pole',
            'Admin de departments',
            'Admin de cellule',
            'department administrator',
            'division chief',
            'service manager',
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PhysicalLocation $physicalLocation): bool
    {
        // Explicitly prevent mid-level admins and service managers from deleting
        // any physical locations (only global admins may delete).
        if ($user->hasAnyRole([
            'Admin de pole',
            'Admin de departments',
            'Admin de cellule',
            'department administrator',
            'division chief',
            'service manager',
        ])) {
            return false;
        }

        return $user->can('delete physical location');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PhysicalLocation $physicalLocation): bool
    {
        return $user->can('restore physical location');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PhysicalLocation $physicalLocation): bool
    {
        return $user->can('forceDelete physical location');
    }
}
