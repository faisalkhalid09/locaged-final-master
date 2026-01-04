<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Jobs\ProcessOcrJob;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use App\Models\Service;
use App\Models\SubDepartment;
use App\Models\Department;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Document extends Model
{

    use SoftDeletes;

    protected $fillable = [
        'uid',
        'category_id',
        'subcategory_id',
        'department_id',
        'service_id',
        'title',
        'metadata',
        'status',
        'physical_location_id',
        'box_id',
        'expire_at',
        'is_expired',
        'created_at',
        'created_by',
    ];
    protected $dates = ['deleted_at'];


    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'expire_at' => 'date',
        'is_expired' => 'boolean',
        'metadata' => 'array',
       // 'status' => DocumentStatus::class
    ];

    protected static function boot()
    {
        parent::boot();

        // Note: department_id is now set explicitly in controllers/forms
        // No automatic assignment to maintain multi-department compatibility


        // Runs on updating event (before an existing record is updated)
        static::updating(function ($document) {

            // Ensure category_id is synced if subcategory_id is present
            if ($document->isDirty('subcategory_id') && $document->subcategory_id) {
                $document->category_id = $document->subcategory->category_id;
            }

            // if status changed
            if ($document->isDirty('status')) {
                $statusFrom = $document->getOriginal('status');

                $currentStatus = $document->status;
                $statusTo = $currentStatus instanceof DocumentStatus
                    ? $currentStatus->value
                    : $currentStatus;

                // WorkflowRule enforcement disabled: relying solely on permissions
                // $ruleExists = WorkFlowRule::where('from_status',$statusFrom)
                //     ->where('to_status',$statusTo)
                //     ->where('department_id',auth()->user()->department_id)
                //     ->exists();
                // if (! $ruleExists) {
                //     throw ValidationException::withMessages([
                //         'status' => "Tried changing from \"$statusFrom\" to \"$statusTo\". No workflow rule found !"
                //     ]);
                // }


                DocumentStatusHistory::create([
                    'document_id' => $document->id,
                    'changed_by' => auth()->id(),
                    'from_status' => $statusFrom,
                    'to_status' => $statusTo,
                    'changed_at' => now()
                ]);
            }
        });

        // Remove all document versions from search index and delete OCR jobs when document is soft deleted
        static::deleted(function ($document) {
            foreach ($document->documentVersions as $version) {
                try {
                    $version->unsearchable();
                } catch (\Throwable $e) {
                    \Log::warning('Failed to unsearchable DocumentVersion on document soft delete', [
                        'version_id' => $version->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                // Delete associated OCR jobs
                try {
                    \App\Models\OcrJob::where('document_version_id', $version->id)->delete();
                } catch (\Throwable $e) {
                    \Log::warning('Failed to delete OCR job on document deletion', [
                        'version_id' => $version->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        // Re-index document versions when document title changes
        static::updated(function ($document) {
            if ($document->isDirty('title')) {
                foreach ($document->documentVersions as $version) {
                    if ($version->shouldBeSearchable()) {
                        $version->searchable();
                    }
                }
            }
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('department_service', function ($query) {
            
            if (!auth()->check()) {
                return;
            }
            $user = auth()->user();

            // Always hide documents that are already destroyed or currently
            // queued for destruction (pending / accepted) from the main document
            // and approvals views for ALL roles. Admins can still see them via
            // explicit queries using withoutGlobalScopes().
            $query
                ->whereNotIn('status', ['destroyed'])
                ->whereDoesntHave('destructionsRequests', function ($q) {
                    $q->whereIn('status', ['pending', 'accepted']);
                });

            // Bypass department/service scoping for roles that can view any document,
            // but still respect the destruction/expiry / destruction-queue filters.
            if ($user->can('view any document')) {
                return;
            }

            // =========================================================
            //  STRICT DIVISION CHIEF POLICY
            // =========================================================
            if ($user->hasRole('Division Chief')) {

                // 1. Get all Sub-Departments assigned to this user (pivot only)
                $userSubDeptIds = collect();
                // Pivot assignment (if exists)
                if (method_exists($user, 'subDepartments')) {
                    $userSubDeptIds = $userSubDeptIds->merge($user->subDepartments->pluck('id'));
                }
                
                $userSubDeptIds = $userSubDeptIds->unique()->filter();

                // If no sub-departments, show nothing
                if ($userSubDeptIds->isEmpty()) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                // 2. Fetch the SubDepartments with their Services AND their defined Department ID
                // We DO NOT rely on the User's department list. We rely on the SubDepartment's database record.
                $assignedSubDepts = SubDepartment::with('services:id,sub_department_id')
                    ->whereIn('id', $userSubDeptIds)
                    ->get(['id', 'department_id']); 

                // 3. Build the Strict Query
                $query->where(function ($mainQuery) use ($assignedSubDepts) {
                    
                    foreach ($assignedSubDepts as $subDept) {
                        
                        // Get services for this specific sub-department
                        $validServiceIds = $subDept->services->pluck('id');
                        
                        if ($validServiceIds->isEmpty()) {
                            continue;
                        }

                        // LOGIC FIX:
                        // We use '$subDept->department_id' (The real owner), NOT the user's department list.
                        // This ensures that even if User 2 is assigned 'Dept 2', 
                        // if they are looking at SubDept 1 (which belongs to Dept 1),
                        // the query forces 'department_id = 1'. 
                        // Since the document is Dept 1, User 1 sees it.
                        // User 2 (who expects Dept 2 documents) will NOT match this condition.
                        
                        $mainQuery->orWhere(function ($q) use ($subDept, $validServiceIds) {
                            $q->where('department_id', $subDept->department_id)
                              ->whereIn('service_id', $validServiceIds);
                        });
                    }
                });

                return;
            }
            // =========================================================
            //  END DIVISION CHIEF POLICY
            // =========================================================

            // -------------------------------
            // Other roles
            // -------------------------------

            // Resolve visible departments and services from pivots
            $visibleDepartmentIds = collect();
            $visibleServiceIds    = collect();

            // Departments directly assigned via pivot
            if (method_exists($user, 'departments')) {
                $visibleDepartmentIds = $visibleDepartmentIds->merge($user->departments->pluck('id'));
            }

            // Sub-departments via pivot -> their departments and services
            $subDeptIds = collect();
            if (method_exists($user, 'subDepartments')) {
                $subDeptIds = $subDeptIds->merge($user->subDepartments->pluck('id'));
            }
            
            // Also check for primary sub_department_id assignment
            if ($user->sub_department_id) {
                $subDeptIds->push($user->sub_department_id);
            }
            
            $subDeptIds = $subDeptIds->unique()->filter();

            if ($subDeptIds->isNotEmpty()) {
                // Departments from these sub-departments
                $visibleDepartmentIds = $visibleDepartmentIds->merge(
                    SubDepartment::whereIn('id', $subDeptIds)->pluck('department_id')
                );

                // IMPORTANT: Only include sub-department services for NON-service-level users
                // Service Managers should ONLY see their DIRECTLY assigned services
                if (! $user->can('view service document')) {
                    $visibleServiceIds = $visibleServiceIds->merge(
                        Service::whereIn('sub_department_id', $subDeptIds)->pluck('id')
                    );
                }
            }

            // Services DIRECTLY assigned to the user (THIS IS THE KEY FOR SERVICE MANAGERS)
            // 1. Check primary service_id column
            if ($user->service_id) {
                $visibleServiceIds->push($user->service_id);
            }
            
            // 2. Check many-to-many pivot table (service_user)
            if (method_exists($user, 'services')) {
                $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
            }

            $visibleDepartmentIds = $visibleDepartmentIds->unique()->filter();
            $visibleServiceIds    = $visibleServiceIds->unique()->filter();

            // Service-level visibility (Service Manager / Service User)
            if ($user->can('view service document')) {
                if ($visibleServiceIds->isNotEmpty()) {
                    // Show documents from assigned services
                    // If user can also view own documents, include those too
                    if ($user->can('view own document')) {
                        $query->where(function($q) use ($visibleServiceIds, $user) {
                            $q->whereIn('service_id', $visibleServiceIds->all())
                              ->orWhere('created_by', $user->id);
                        });
                    } else {
                        // Simple filter by service_id only
                        $query->whereIn('service_id', $visibleServiceIds->all());
                    }
                } else {
                    // No services assigned, but if they can view own documents, show those
                    if ($user->can('view own document')) {
                        $query->where('created_by', $user->id);
                    } else {
                        // No visible services assigned and can't view own => no documents
                        $query->whereRaw('1 = 0');
                    }
                }
                return;
            }

            // Department-level visibility (Department Admin etc.)
            if ($user->can('view department document')) {                    // Scope to visible departments (only show documents from assigned departments)
                if ($visibleDepartmentIds->isNotEmpty()) {
                    $query->whereIn('documents.department_id', $visibleDepartmentIds->all());
                } else {
                    $query->whereRaw('1 = 0');
                }
                return;
            }

            // Fallback: only own documents
            if ($user->can('view own document')) {
                $query->where('created_by', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        });
    }

    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class,'document_id');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class, 'document_id')->orderByDesc('version_number');
    }

    public function destructionsRequests(): HasMany
    {
        return $this->hasMany(DocumentDestructionRequest::class,'document_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by','id');
    }
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tags');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function physicalLocation()
    {
        return $this->belongsTo(PhysicalLocation::class,'physical_location_id','id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    /**
     * Get the box that contains this document (new hierarchical structure)
     */
    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'box_id');
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'document_id')
            ->orderBy('occurred_at', 'desc');
    }


    public function logAction(string $action, ?int $versionId = null)
    {
        $resolvedVersionId = $versionId ?? $this->latestVersion?->id;

        // If we somehow don't have a version, skip audit/notifications to avoid DB constraint errors.
        if (! $resolvedVersionId) {
            return;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'document_id' => $this->id,
            'version_id' => $resolvedVersionId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'occurred_at' => now(),
        ]);

        $notificationService = new NotificationService(
            $this->title,
            $this->createdBy,
            $this->id,
            $resolvedVersionId
        );
        $notificationService->notifyBasedOnAction($action);

        if (in_array($action, ['expired', 'moved'])) {
            $notificationService->notifyAdmins($action);
        }

    }

    /**
     * Queue an OCR job for the latest version of this document if and only if
     * the document is approved and no active/completed OCR job exists yet.
     */
    public function queueOcrIfNeeded(): void
    {
        // Support both enum-casted and plain string status values.
        $status = $this->status instanceof DocumentStatus
            ? $this->status->value
            : $this->status;

        if ($status !== DocumentStatus::Approved->value) {
            return;
        }

        $latestVersion = $this->latestVersion;
        if (! $latestVersion) {
            return;
        }

        // Avoid creating duplicate OCR jobs for the same version.
        $existingJob = OcrJob::where('document_version_id', $latestVersion->id)
            ->whereIn('status', ['queued', 'processing', 'completed'])
            ->first();

        if ($existingJob) {
            return;
        }

        $ocrJob = OcrJob::create([
            'document_version_id' => $latestVersion->id,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        ProcessOcrJob::dispatch($ocrJob);
    }

}
