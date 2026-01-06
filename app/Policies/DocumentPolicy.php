<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Service;
use App\Models\SubDepartment;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if (
            $user->can('view any document') ||
            $user->can('view department document') ||
            $user->can('view service document') ||
            $user->can('view own document')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        if ($user->can('view any document')) {
            return true;
        }

        /**
         * Special rule for Division Chief:
         *
         * They may only see a document if BOTH:
         *  - the document's department is one of their departments, AND
         *  - the document's service belongs to a sub-department that is also
         *    one of their sub-departments for that same department.
         */
        if ($user->hasRole('Division Chief')) {
            // Departments assigned to the user
            $userDeptIds = ($user->relationLoaded('departments') || method_exists($user, 'departments'))
                ? $user->departments->pluck('id')->filter()
                : collect();

            // Sub-departments assigned to the user (pivot only)
            $userSubDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $userSubDeptIds = $userSubDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $userSubDeptIds = $userSubDeptIds->unique()->filter();

            if ($userDeptIds->isEmpty() || $userSubDeptIds->isEmpty()) {
                return false;
            }

            // Only keep sub-departments where BOTH the sub-department id and its department
            // belong to the user. This enforces the department+sub-department pair.
            $allowedSubDeptIds = SubDepartment::whereIn('id', $userSubDeptIds)
                ->whereIn('department_id', $userDeptIds)
                ->pluck('id');

            if ($allowedSubDeptIds->isEmpty()) {
                return false;
            }

            // Fetch services under those allowed sub-departments
            $serviceIds = Service::whereIn('sub_department_id', $allowedSubDeptIds)->pluck('id');
            if ($serviceIds->isEmpty()) {
                return false;
            }

            // Document is visible only if department & service match the allowed sets
            if (
                $userDeptIds->contains($document->department_id) &&
                $document->service_id &&
                $serviceIds->contains($document->service_id)
            ) {
                return true;
            }

            // For Division Chief, if the strict pair doesn't match, deny even if they
            // might have broader generic permissions.
            return false;
        }

        if ($user->can('view service document')) {
            // Build list of services the user is related to (pivot + via sub-departments)
            $visibleServiceIds = collect();

            // Services directly assigned (pivot)
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }

            // Sub-departments (pivot only) -> services under those sub-departments
            $subDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $subDeptIds = $subDeptIds->unique()->filter();

            if ($subDeptIds->isNotEmpty()) {
                $visibleServiceIds = $visibleServiceIds->merge(
                    Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                );
            }

            $visibleServiceIds = $visibleServiceIds->unique()->filter();

            if ($document->service_id && $visibleServiceIds->contains($document->service_id)) {
                return true;
            }
        }

        if ($user->can('view department document') && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create document');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        if ($user->cannot('update document')) {
            return false;
        }

        if ($user->can('view any document')) {
            return true;
        }

        // Service-level users (Admin de cellule, Service Manager, etc.)
        if ($user->can('view service document')) {
            // Build list of services the user is related to
            $visibleServiceIds = collect();

            // Services directly assigned (pivot)
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }

            // Sub-departments (pivot only) -> services under those sub-departments
            $subDeptIds = collect();
            if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $subDeptIds = $subDeptIds->unique()->filter();

            if ($subDeptIds->isNotEmpty()) {
                $visibleServiceIds = $visibleServiceIds->merge(
                    Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                );
            }

            $visibleServiceIds = $visibleServiceIds->unique()->filter();

            if ($document->service_id && $visibleServiceIds->contains($document->service_id)) {
                return true;
            }
        }

        if ($user->can('view department document') && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        // Master role can delete any document regardless of ownership or status
        if ($user->hasRole('master')) {
            return true;
        }

        if ($user->cannot('delete document')) {
            return false;
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view department document') && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        if ($user->cannot('restore document')) {
            return false;
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view department document') && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        if ($user->cannot('forceDelete document')) {
            return false;
        }

        if ($user->can('view any document')) {
            return true;
        }

        if ($user->can('view department document') && $user->departments->pluck('id')->contains($document->department_id)) {
            return true;
        }

        if ($user->can('view own document') && $user->id === $document->created_by) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can approve documents.
     */
    public function approve(User $user): bool
    {
        return $user->can('approve document');
    }

    /**
     * Determine whether the user can decline documents.
     */
    public function decline(User $user): bool
    {
        return $user->can('decline document');
    }

    /**
     * Determine whether the user can permanently delete a document.
     *
     * Only master, super admin, and admin de pôle roles are allowed.
     * Additionally, the document creator can delete their own declined documents.
     */
    public function permanentDelete(User $user, Document $document): bool
    {
        // Admin roles can always permanently delete
        if ($user->hasAnyRole([
            'master',
            'Super Administrator',
            'super administrator',
            'Admin de pole',
            'admin de pôle',
        ])) {
            return true;
        }

        // Allow document creator to permanently delete their own declined documents
        if ($document->status === 'declined' && $document->created_by === $user->id) {
            return true;
        }

        return false;
    }
}
