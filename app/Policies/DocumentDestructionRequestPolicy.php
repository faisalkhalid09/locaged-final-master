<?php

namespace App\Policies;

use App\Models\DocumentDestructionRequest;
use App\Models\User;

class DocumentDestructionRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->can('view any document destruction request') || 
            $user->can('view department document destruction request') ||
            $user->can('view service document') || 
            $user->hasRole('Admin de cellule') || 
            $user->hasRole('Service Manager')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DocumentDestructionRequest $destructionRequest): bool
    {
        if ($user->can('view any document destruction request')) {
            return true;
        }

        if ($user->can('view department document destruction request') && $user->departments->pluck('id')->contains($destructionRequest->document->department_id)) {
            return true;
        }

        if ($user->can('view own document destruction request') && $user->id === $destructionRequest->document->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create document destruction request');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DocumentDestructionRequest $destructionRequest): bool
    {
        if ($user->cannot('update document destruction request')) {
            return false;
        }

        if ($user->can('view any document destruction request')) {
            return true;
        }

        if ($user->can('view department document destruction request') && $user->departments->pluck('id')->contains($destructionRequest->document->department_id)) {
            return true;
        }

        if ($user->can('view own document destruction request') && $user->id === $destructionRequest->document->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DocumentDestructionRequest $destructionRequest): bool
    {
        if ($user->cannot('delete document destruction request')) {
            return false;
        }

        if ($user->can('view any document destruction request')) {
            return true;
        }

        if ($user->can('view department document destruction request') && $user->departments->pluck('id')->contains($destructionRequest->document->department_id)) {
            return true;
        }

        if ($user->can('view own document destruction request') && $user->id === $destructionRequest->document->created_by) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DocumentDestructionRequest $destructionRequest): bool
    {
        if ($user->cannot('restore document destruction request')) {
            return false;
        }

        if ($user->can('view any document destruction request')) {
            return true;
        }

        if ($user->can('view department document destruction request') && $user->departments->pluck('id')->contains($destructionRequest->document->department_id)) {
            return true;
        }

        if ($user->can('view own document destruction request') && $user->id === $destructionRequest->document->created_by) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DocumentDestructionRequest $destructionRequest): bool
    {
        if ($user->cannot('forceDelete document destruction request')) {
            return false;
        }

        if ($user->can('view any document destruction request')) {
            return true;
        }

        if ($user->can('view department document destruction request') && $user->departments->pluck('id')->contains($destructionRequest->document->department_id)) {
            return true;
        }

        if ($user->can('view own document destruction request') && $user->id === $destructionRequest->document->created_by) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can approve documents.
     */
    public function approve(User $user): bool
    {
        return $user->can('approve document destruction request');
    }

    /**
     * Determine whether the user can decline documents.
     */
    public function decline(User $user): bool
    {
        return $user->can('decline document destruction request');
    }

    /**
     * Determine whether the user can postpone document expiration.
     */
    public function postpone(User $user): bool
    {
        // Only master and Super Administrator can postpone (change expiration date)
        return $user->hasAnyRole(['master', 'Super Administrator', 'super administrator']);
    }
}
