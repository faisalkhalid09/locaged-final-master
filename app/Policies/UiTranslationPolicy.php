<?php

namespace App\Policies;

use App\Models\UiTranslation;
use App\Models\User;

class UiTranslationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view any ui translation');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UiTranslation $uiTranslation): bool
    {
        return $user->can('view any ui translation');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create ui translation');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UiTranslation $uiTranslation): bool
    {
        return $user->can('update ui translation');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UiTranslation $uiTranslation): bool
    {
        return $user->can('delete ui translation');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UiTranslation $uiTranslation): bool
    {
        return $user->can('restore ui translation');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UiTranslation $uiTranslation): bool
    {
        return $user->can('forceDelete ui translation');
    }
}
