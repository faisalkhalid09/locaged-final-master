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
                $query->whereRaw('1 = 0');
                return;
            }

            $user = auth()->user();

            // 1) Global Admins: see everything
            if ($user->hasAnyRole(['master', 'Super Administrator', 'super administrator', 'super_admin', 'admin'])) {
                return;
            }

            // 2) Department Administrator (Admin de pole): Filter by department_id
            if ($user->hasAnyRole(['Department Administrator', 'Admin de pole'])) {
                 $deptIds = $user->departments->pluck('id');
                 if ($deptIds->isNotEmpty()) {
                     $query->whereIn('department_id', $deptIds);
                 } else {
                     $query->whereRaw('1 = 0');
                 }
                 return;
            }

            // 3) Sub-Department Administrator (Admin de departments / Division Chief): Filter by sub_department_id
            if ($user->hasAnyRole(['Admin de departments', 'Division Chief'])) {
                $subDeptIds = collect();
                if ($user->sub_department_id) {
                    $subDeptIds->push($user->sub_department_id);
                }
                if (method_exists($user, 'subDepartments')) {
                    $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
                }
                $subDeptIds = $subDeptIds->unique()->filter();

                if ($subDeptIds->isNotEmpty()) {
                    $query->whereIn('sub_department_id', $subDeptIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
                return;
            }

            // 4) Service Manager (Admin de cellule): Filter by service_id
            // (Also catch 'service manager' just in case)
            if ($user->hasAnyRole(['Admin de cellule', 'Service Manager', 'service manager'])) {
                $serviceIds = collect();
                if ($user->service_id) {
                    $serviceIds->push($user->service_id);
                }
                if (method_exists($user, 'services')) {
                     $serviceIds = $serviceIds->merge($user->services->pluck('id'));
                }
                $serviceIds = $serviceIds->unique()->filter();

                if ($serviceIds->isNotEmpty()) {
                    $query->whereIn('service_id', $serviceIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
                return;
            }

            // 5) Regular User: usually filtered by service like Service Manager, or strictly own service
            // For now, let's treat them like Service Manager (filter by service_id)
            $serviceIds = collect();
            if ($user->service_id) {
                $serviceIds->push($user->service_id);
            }
            if (method_exists($user, 'services')) {
                 $serviceIds = $serviceIds->merge($user->services->pluck('id'));
            }
            $serviceIds = $serviceIds->unique()->filter();

            if ($serviceIds->isNotEmpty()) {
                $query->whereIn('service_id', $serviceIds);
            } else {
                $query->whereRaw('1 = 0');
            }
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
