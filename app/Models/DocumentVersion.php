<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;
use App\Models\Service;
use App\Models\SubDepartment;

class DocumentVersion extends Model
{
    use Searchable;
    use SoftDeletes;

    protected $table = 'document_versions';

    protected $fillable = [
        'document_id',
        'version_number',
        'file_path',
        'file_type',
        'uploaded_by',
        'uploaded_at',
        'ocr_text',
        'locked_by',
        'locked_at',
        'unlocked_at',
    ];
    protected $with = ['document'];
    protected $dates = ['deleted_at'];

    protected static function boot()
    {
        parent::boot();


        static::addGlobalScope('orderedByVersion', function (Builder $builder) {
            $builder->orderBy('version_number', 'desc'); // or 'asc' as needed
        });
        // Runs on creating event (before a new record is saved)
        static::creating(function ($documentVersion) {
            // Find the latest version number for the same document
            $latestVersion = self::where('document_id', $documentVersion->document_id)
                ->orderByDesc('version_number')
                ->first();

            if ($latestVersion) {
                // Increment by 0.1 and keep 1 decimal place
                $documentVersion->version_number = round($latestVersion->version_number + 0.1, 1);
            } else {
                // If no previous version exists, start at 1.0
                $documentVersion->version_number = 1.0;


            }
        });

        static::created(function ($documentVersion) {
            if ($documentVersion->version_number == 1.0) {
                // log new document created
                $documentVersion->document->logAction('created');
            }
        });

        // Remove from search index when document version is deleted (soft delete)
        static::deleted(function ($documentVersion) {
            try {
                $documentVersion->unsearchable();
            } catch (\Throwable $e) {
                Log::warning('Failed to unsearchable DocumentVersion on delete', [
                    'version_id' => $documentVersion->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('department', function ($query) {
            if (! auth()->check()) {
                return;
            }

            $user = auth()->user();

            // If user can view all documents, do not restrict versions
            if ($user->can('view any document')) {
                return;
            }

            // Service-level visibility: documents whose service is in user's services/sub-departments
            if ($user->can('view service document')) {
                $visibleServiceIds = collect();

                // Services directly assigned via pivot
                if ($user->relationLoaded('services') || method_exists($user, 'services')) {
                    $visibleServiceIds = $visibleServiceIds->merge($user->services->pluck('id'));
                }

                // Sub-departments via pivot -> their services
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

                if ($visibleServiceIds->isNotEmpty()) {
                    $query->whereHas('document', function ($q) use ($visibleServiceIds) {
                        $q->whereIn('service_id', $visibleServiceIds->all());
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }

                return;
            }

            // Department-level visibility
            if ($user->can('view department document')) {
                $departmentIds = $user->departments->pluck('id')->toArray();
                if (! empty($departmentIds)) {
                    $query->whereHas('document', function ($q) use ($departmentIds) {
                        $q->whereIn('department_id', $departmentIds);
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }

                return;
            }

            // Own documents only
            if ($user->can('view own document')) {
                $query->whereHas('document', function ($q) use ($user) {
                    $q->where('created_by', $user->id);
                });

                return;
            }

            // User has no document permissions at all - show nothing
            $query->whereRaw('1 = 0');
        });
    }


    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'uploaded_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'locked_by');
    }

    protected $casts = [
        'locked_at' => 'datetime',
        'unlocked_at' => 'datetime',
        'uploaded_at' => 'datetime'
    ];

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        // Only make the latest version searchable
        $latestVersion = $this->document->latestVersion;
        if (!$latestVersion || $latestVersion->id !== $this->id) {
            return false;
        }
        // if(!Storage::disk('local')->exists($this->file_path)) {
        //     Log::info('File Does not exist: ' . $this->file_path);
        //     return false;
        // }
        // File existence check removed - allow indexing even if file is missing
        return true;
    }



    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'name' => $this->document->title,
            'file' => $this->file_path,
            'ocr_text' => $this->ocr_text,
            'version_number' => $this->version_number,
            'uploaded_at' => $this->uploaded_at?->toDateTimeString(),
            'uploaded_by' => optional($this->uploadedBy)->full_name,
            'metadata_author' => $this->document->metadata['author'] ?? null,
            'tags' => $this->document->tags->pluck('name')->toArray(),
        ];
    }

    public function ocrJob(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OcrJob::class, 'document_version_id');
    }

}
