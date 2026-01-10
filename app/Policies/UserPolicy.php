<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Branding;
use App\Support\RoleHierarchy;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Primary check: explicit permissions
        if ($user->can('view any user') || $user->can('view department user') || $user->can('view service user')) {
            return true;
        }

        // Fallback: certain management roles should always be able to open
        // the Users page, even if permissions were not fully synced.
        if ($user->hasAnyRole([
            'master',
            'Super Administrator',
            'admin',
            'Admin de pole',
            'Admin de departments',
            'Admin de cellule',
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Enforce role hierarchy: cannot view higher-ranked users
        if (! RoleHierarchy::canViewUser($user, $model)) {
            return false;
        }

        if ($user->can('view any user')) {
            return true;
        }

        if ($user->can('view department user') && $user->departments->pluck('id')->intersect($model->departments->pluck('id'))->isNotEmpty()) {
            return true;
        }

        // Service-level visibility: users sharing at least one service
        if ($user->can('view service user') && $user->services->pluck('id')->intersect($model->services->pluck('id'))->isNotEmpty()) {
            return true;
        }

        if ($user->can('view own user') && $user->id === $model->id) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Primary check: explicit permission
        $hasPermission = $user->can('create user');

        // Fallback: certain management roles should always be able to create
        // users even if permissions were not fully synced.
        $hasFallbackRole = $user->hasAnyRole([
            'master',
            'Super Administrator',
            'admin',
            'Admin de pole',
            'Admin de departments',
            'Admin de cellule',
            'service manager',
        ]);

        if (! $hasPermission && ! $hasFallbackRole) {
            return false;
        }

        // Check if user limit is reached
        if (Branding::isUserLimitReached()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->cannot('update user')) {
            return false;
        }

        // Enforce role hierarchy
        if (! RoleHierarchy::canViewUser($user, $model)) {
            return false;
        }

        if ($user->can('view any user')) {
            return true;
        }

        if ($user->can('view department user') && $user->departments->pluck('id')->intersect($model->departments->pluck('id'))->isNotEmpty()) {
            return true;
        }

        // Service-level visibility: users sharing at least one service
        if ($user->can('view service user') && $user->services->pluck('id')->intersect($model->services->pluck('id'))->isNotEmpty()) {
            return true;
        }

        if ($user->can('view own user') && $user->id === $model->id) {
            return true;
        }
        return false;

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Only allow admin (master) and Super Administrator to delete users
        if (!$user->hasAnyRole(['master', 'Super Administrator'])) {
            return false;
        }

        if ($user->cannot('delete user')) {
            return false;
        }

        // Enforce role hierarchy
        if (! RoleHierarchy::canViewUser($user, $model)) {
            return false;
        }

        if ($user->can('view any user')) {
            return true;
        }

        if ($user->can('view department user') && $user->departments->pluck('id')->intersect($model->departments->pluck('id'))->isNotEmpty()) {
            return true;
        }

        // Service-level visibility: users sharing at least one service
        if ($user->can('view service user') && $user->services->pluck('id')->intersect($model->services->pluck('id'))->isNotEmpty()) {
            return true;
        }

        // dont allow to delete his account
        /*if ($user->can('view own user') && $user->id === $model->id) {
            return true;
        }*/
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        if ($user->cannot('restore user')) {
            return false;
        }

        // Enforce role hierarchy
        if (! RoleHierarchy::canViewUser($user, $model)) {
            return false;
        }

        if ($user->can('view any user')) {
            return true;
        }

        if ($user->can('view department user') && $user->departments->pluck('id')->intersect($model->departments->pluck('id'))->isNotEmpty()) {
            return true;
        }

        // Service-level visibility: users sharing at least one service
        if ($user->can('view service user') && $user->services->pluck('id')->intersect($model->services->pluck('id'))->isNotEmpty()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        if ($user->cannot('forceDelete user')) {
            return false;
        }

        // Enforce role hierarchy
        if (! RoleHierarchy::canViewUser($user, $model)) {
            return false;
        }

        if ($user->can('view any user')) {
            return true;
        }

        if ($user->can('view department user') && $user->departments->pluck('id')->intersect($model->departments->pluck('id'))->isNotEmpty()) {
            return true;
        }

        // Service-level visibility: users sharing at least one service
        if ($user->can('view service user') && $user->services->pluck('id')->intersect($model->services->pluck('id'))->isNotEmpty()) {
            return true;
        }

        return false;
    }

    /**
     * Allow a user to update only their own password, or admins to update any user's password.
     */
    public function updatePassword(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true; // self can change own password
        }
        return $user->can('update user'); // admins/managers
    }
}
