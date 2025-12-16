<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Service;
use App\Models\SubDepartment;

class Category extends Model
{
    // NOTE: This model relies on Service and SubDepartment for hierarchy-based
    // visibility. Make sure these imports stay in sync with the models.
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'department_id',
        'sub_department_id',
        'service_id',
        'sub_department_id',
        'service_id',
        'expiry_value',
        'expiry_unit',
    ];

    protected static function booted()
    {
        static::addGlobalScope('service_hierarchy', function ($query) {
            if (! auth()->check()) {
                // No categories for guests
                $query->whereRaw('1 = 0');
                return;
            }

            $user = auth()->user();

            // Global admins can see all categories without scoping
            if ($user->hasRole('master') ||
                $user->hasRole('Super Administrator') ||
                $user->hasRole('super administrator') ||
                $user->hasRole('super_admin') ||
                $user->hasRole('admin')) {
                return;
            }

            // Build the list of service IDs this user is allowed to see
            $serviceIds = collect();

            // 1) Direct service assignment (legacy single column)
            if ($user->service_id) {
                $serviceIds->push($user->service_id);
            }

            // 2) Services via many-to-many pivot
            if (method_exists($user, 'services')) {
                $serviceIds = $serviceIds->merge($user->services->pluck('id'));
            }

            // 3) Sub-department assignments (primary + pivot) → all services under those sub-departments
            $subDeptIds = collect();
            if ($user->sub_department_id) {
                $subDeptIds->push($user->sub_department_id);
            }
            if (method_exists($user, 'subDepartments')) {
                $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
            }
            $subDeptIds = $subDeptIds->unique()->filter();

            // Special case: Sub-department admins ("Admin de departments" / "Division Chief")
            $isDivisionChief = $user->hasAnyRole(['Admin de departments', 'Division Chief']);

            if ($isDivisionChief) {
                // For sub-department admins, only include services under the
                // sub-departments they are explicitly attached to.
                if ($subDeptIds->isNotEmpty()) {
                    $serviceIds = $serviceIds->merge(
                        Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }
            } else {
                // For all other scoped users, include services under any
                // sub-departments they belong to ...
                if ($subDeptIds->isNotEmpty()) {
                    $serviceIds = $serviceIds->merge(
                        Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }

                // ... plus services under any departments they belong to.
                $departmentIds = $user->departments->pluck('id');
                if ($departmentIds->isNotEmpty()) {
                    $deptSubDeptIds = SubDepartment::whereIn('department_id', $departmentIds)->pluck('id');
                    if ($deptSubDeptIds->isNotEmpty()) {
                        $serviceIds = $serviceIds->merge(
                            Service::whereIn('sub_department_id', $deptSubDeptIds)->pluck('id')
                        );
                    }
                }
            }

            $serviceIds = $serviceIds->unique()->filter();

            if ($serviceIds->isEmpty()) {
                // User is not attached to any service hierarchy they can see
                $query->whereRaw('1 = 0');
                return;
            }

            // Each service has its own categories: restrict to the services
            // the user is allowed to see. Category names may repeat across
            // different services because uniqueness is per service.
            $query->whereIn('service_id', $serviceIds);
        });
    }

    public function documents(): HasManyThrough
    {
        return $this->hasManyThrough(Document::class, Subcategory::class);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
