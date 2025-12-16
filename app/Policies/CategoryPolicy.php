<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Primary check: explicit permission
        if ($user->can('view any category')) {
            return true;
        }

        // Fallback by role to avoid UI issues if permissions are not fully synced
        return $user->hasAnyRole([
            'Admin de pole',
            'Admin de departments',
            'Admin de cellule',
            'department administrator',
            'division chief',
            'service manager',
            'user', // service-level user can always browse categories
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Category $category): bool
    {
        // Same rules as viewAny() – global scope on Category already restricts
        // to categories within the user's own hierarchy.
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create category');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->can('update category');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->can('delete category');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Category $category): bool
    {
        return $user->can('restore category');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return $user->can('forceDelete category');
    }
}
