<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'document_id',
        'version_id',
        'action',
        'ip_address',
        'occurred_at',
    ];

    public $timestamps = false;

    protected static function booted()
    {
        static::addGlobalScope('org_visibility', function ($query) {
            if (! auth()->check()) {
                // No audits for guests
                $query->whereRaw('1 = 0');
                return;
            }

            $user = auth()->user();

            // Super admins see all audit logs
            if ($user->hasRole('master') || $user->hasRole('super administrator') || $user->hasRole('super_admin')) {
                return;
            }

            $departmentIds = $user->departments->pluck('id')->filter();

            // Always restrict audits to logs whose documents the user can see.
            // IMPORTANT: include soft-deleted documents so we can still see who deleted them.
            $query->whereHas('document', function ($docQuery) use ($departmentIds) {
                // Include soft-deleted documents in the relationship query
                $docQuery->withTrashed();

                if ($departmentIds->isNotEmpty()) {
                    $docQuery->whereIn('department_id', $departmentIds->all());
                }
            });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function document(): BelongsTo
    {
        // Include soft-deleted documents so audit logs remain visible even after permanent delete
        return $this->belongsTo(Document::class)->withTrashed();
    }

    public function documentVersion(): BelongsTo
    {
        // Include soft-deleted document versions for historical integrity
        return $this->belongsTo(DocumentVersion::class,'version_id','id')->withTrashed();
    }

    protected $casts = [
        'occurred_at' => 'datetime'
    ];
}
