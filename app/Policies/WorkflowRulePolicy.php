<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowRule;

class WorkflowRulePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->can('view any workflow rule') || $user->can('view department workflow rule')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkflowRule $workflowRule): bool
    {
        if ($user->can('view any workflow rule')) {
            return true;
        }

        if ($user->can('view department workflow rule') && $user->departments->pluck('id')->contains($workflowRule->department_id)) {
            return true;
        }


        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create workflow rule');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkflowRule $workflowRule): bool
    {
        if ($user->cannot('update workflow rule')) {
            return false;
        }

        if ($user->can('view any workflow rule')) {
            return true;
        }

        if ($user->can('view department workflow rule') && $user->departments->pluck('id')->contains($workflowRule->department_id)) {
            return true;
        }


        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkflowRule $workflowRule): bool
    {
        if ($user->cannot('delete workflow rule')) {
            return false;
        }

        if ($user->can('view any workflow rule')) {
            return true;
        }

        if ($user->can('view department workflow rule') && $user->departments->pluck('id')->contains($workflowRule->department_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkflowRule $workflowRule): bool
    {
        if ($user->cannot('restore workflow rule')) {
            return false;
        }

        if ($user->can('view any workflow rule')) {
            return true;
        }

        if ($user->can('view department workflow rule') && $user->departments->pluck('id')->contains($workflowRule->department_id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkflowRule $workflowRule): bool
    {
        if ($user->cannot('forceDelete workflow rule')) {
            return false;
        }

        if ($user->can('view any workflow rule')) {
            return true;
        }

        if ($user->can('view department workflow rule') && $user->departments->pluck('id')->contains($workflowRule->department_id)) {
            return true;
        }

        return false;
    }
}
