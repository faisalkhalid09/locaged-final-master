<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Service;

class Box extends Model
{
    protected $fillable = [
        'shelf_id',
        'service_id',
        'name',
        'description',
    ];

    /**
     * Get the shelf that owns this box
     */
    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    /**
     * Get the service that owns this box
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the row through shelf
     */
    public function row()
    {
        return $this->shelf()->with('row')->first()->row ?? null;
    }

    /**
     * Get the room through shelf and row
     */
    public function room()
    {
        return $this->shelf()->with('row.room')->first()->row->room ?? null;
    }

    /**
     * Get all documents in this box
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'box_id');
    }

    /**
     * Get full path representation (Room → Row → Shelf → Box)
     */
    public function __toString(): string
    {
        // Load relationships if not already loaded
        $this->loadMissing('shelf.row.room');
        
        return $this->shelf->row->room->name . ' → ' . 
               $this->shelf->row->name . ' → ' . 
               $this->shelf->name . ' → ' . 
               $this->name;
    }

    /**
     * Get the full path as an array
     */
    public function getFullPath(): array
    {
        // Load relationships if not already loaded
        $this->loadMissing('shelf.row.room');
        
        return [
            'room' => $this->shelf->row->room->name,
            'row' => $this->shelf->row->name,
            'shelf' => $this->shelf->name,
            'box' => $this->name,
        ];
    }

    /**
     * Scope to filter boxes by user's accessible services
     */
    public function scopeForUser($query, $user)
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        $accessibleServiceIds = static::getAccessibleServiceIds($user);

        // If user can see all services or has no service restrictions, show all boxes
        if ($accessibleServiceIds === 'all') {
            return $query;
        }

        // If user has no accessible services, show no boxes
        if ($accessibleServiceIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        // Filter by accessible service IDs
        return $query->whereIn('service_id', $accessibleServiceIds);
    }

    /**
     * Get service IDs accessible to a user based on their role and assignments
     * 
     * @return \Illuminate\Support\Collection|string Returns 'all' for admins, or Collection of service IDs
     */
    public static function getAccessibleServiceIds($user)
    {
        if (!$user) {
            return collect();
        }

        // Master and Super Administrator can see all services
        if ($user->hasRole('master') || $user->hasRole('Super Administrator') || $user->hasRole('super_admin')) {
            return 'all';
        }

        $serviceIds = collect();

        // Admin de Pole (Pole Admin) - sees all services within their department(s)
        if ($user->hasRole('Admin de pole') || $user->hasRole('Pole Admin')) {
            $departmentIds = $user->departments->pluck('id');
            if ($departmentIds->isNotEmpty()) {
                $subDeptIds = \App\Models\SubDepartment::whereIn('department_id', $departmentIds)->pluck('id');
                if ($subDeptIds->isNotEmpty()) {
                    $serviceIds = $serviceIds->merge(
                        \App\Models\Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }
            }
        }

        // Service-level roles: Admin de cellule, Service Manager, Service User, user
        // Division Chief, Admin de departments
        // These users only see their directly assigned services
        $isServiceLevelUser = $user->hasAnyRole([
            'Admin de cellule',
            'Service Manager',
            'Service User',
            'user',
            'Division Chief',
            'Admin de departments',
        ]);

        if ($isServiceLevelUser) {
            // Direct service assignment via service_id column
            if ($user->service_id) {
                $serviceIds->push($user->service_id);
            }

            // Many-to-many service assignments via pivot table
            if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                $serviceIds = $serviceIds->merge($user->services->pluck('id'));
            }

            // For Division Chief: also include services from their assigned sub-departments
            if ($user->hasAnyRole(['Division Chief', 'Admin de departments'])) {
                $subDeptIds = collect();
                
                if ($user->sub_department_id) {
                    $subDeptIds->push($user->sub_department_id);
                }
                
                if ($user->relationLoaded('subDepartments') || method_exists($user, 'subDepartments')) {
                    $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
                }

                if ($subDeptIds->isNotEmpty()) {
                    $serviceIds = $serviceIds->merge(
                        \App\Models\Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }
            }
        }

        return $serviceIds->unique()->filter();
    }
}
