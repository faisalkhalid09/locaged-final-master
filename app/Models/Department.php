<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Department extends Model
{
    protected $fillable = [
        'name', 'description'
    ];

    protected static function booted()
    {
        static::addGlobalScope('department', function ($query) {
            if (! auth()->check()) {
                return;
            }

            $user = auth()->user();

            // Users with this permission can see all departments; no additional scoping.
            if ($user->can('view any department')) {
                return;
            }

            // Everyone else is restricted to departments explicitly assigned via the
            // department_user pivot table. This matches how other parts of the app
            // scope visibility to a user's own structures.
            $departmentIds = \DB::table('department_user')
                ->where('user_id', $user->id)
                ->pluck('department_id')
                ->toArray();

            if (! empty($departmentIds)) {
                $query->whereIn('departments.id', $departmentIds);
            } else {
                // If the user has no departments assigned, they see none.
                $query->whereRaw('1 = 0');
            }
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_user')
            ->withTimestamps();
    }

    public function rules(): HasMany
    {
        return $this->hasMany(WorkFlowRule::class,'department_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Direct children sub-departments.
     */
    public function subDepartments(): HasMany
    {
        return $this->hasMany(SubDepartment::class);
    }

    /**
     * Services belonging to this department via sub-departments.
     */
    public function services(): HasManyThrough
    {
        return $this->hasManyThrough(Service::class, SubDepartment::class);
    }
}
