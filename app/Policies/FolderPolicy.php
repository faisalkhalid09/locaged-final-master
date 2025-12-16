<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    public function viewAny(User $user): bool
    {
        // Same as viewing any document
        return $user->can('view any document')
            || $user->can('view department document')
            || $user->can('view service document')
            || $user->can('view own document');
    }

    public function view(User $user, Folder $folder): bool
    {
        // Reuse document visibility logic based on department/service/owner
        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view service document') && $folder->service_id && $user->service_id == $folder->service_id) {
            return true;
        }

        if ($user->can('view department document') && $folder->department_id) {
            return $user->departments->pluck('id')->contains($folder->department_id);
        }

        if ($user->can('view own document') && $folder->created_by === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Creating folders is allowed whenever user can create documents
        return $user->can('create document');
    }

    public function update(User $user, Folder $folder): bool
    {
        return $this->view($user, $folder);
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->view($user, $folder);
    }

    public function approve(User $user, Folder $folder): bool
    {
        return $user->can('approve document');
    }

    public function decline(User $user, Folder $folder): bool
    {
        return $user->can('decline document');
    }
}
