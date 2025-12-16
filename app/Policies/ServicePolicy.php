<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view any service');
    }

    public function view(User $user, Service $service): bool
    {
        return $user->can('view any service');
    }

    public function create(User $user): bool
    {
        return $user->can('create service');
    }

    public function update(User $user, Service $service): bool
    {
        return $user->can('update service');
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->can('delete service');
    }

    public function restore(User $user, Service $service): bool
    {
        return $user->can('restore service');
    }

    public function forceDelete(User $user, Service $service): bool
    {
        return $user->can('forceDelete service');
    }
}
