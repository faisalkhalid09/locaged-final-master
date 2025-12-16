<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkFlowRule extends Model
{
    protected $table = 'workflow_rules';

    protected $fillable = [
        'department_id',
        'from_status',
        'to_status',
        // 'role_id'
    ];

    protected static function booted()
    {
        static::addGlobalScope('department', function ($query) {
            if (auth()->check() && auth()->user()->cannot('view any workflow rule')) {
                if (auth()->user()->can('view department workflow rule')) {
                    $departmentIds = auth()->user()->departments->pluck('id')->toArray();
                    if (!empty($departmentIds)) {
                        $query->whereIn('department_id', $departmentIds);
                    } else {
                        $query->whereRaw('1 = 0'); // Show nothing if no departments assigned
                    }
                } else {
                    // No permission to view any workflow rules
                    $query->whereRaw('1 = 0');
                }
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }



}
