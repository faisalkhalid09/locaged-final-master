<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Branding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username', // kept fillable to allow server-side assignment (email)
        'full_name',
        'phone',
        'image',
        'email',
        'password',
        // 'department_id', // Removed - now using multi-department system via pivot table
        'locale',
        // Keep legacy single sub_department_id / service_id columns for primary assignment,
        // but the authoritative associations are the pivot tables sub_department_user and service_user.
        'sub_department_id',
        'service_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $with = ['roles'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $maxUsers = Branding::getMaxUsers();
            if ($maxUsers > 0) {
                $currentUserCount = static::count();
                if ($currentUserCount >= $maxUsers) {
                    throw ValidationException::withMessages([
                        'email' => ["Cannot create user. Maximum number of users ({$maxUsers}) has been reached."],
                    ]);
                }
            }
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    // Removed: department() relationship - now using multi-department system via departments() relationship

    /**
     * The departments that the user belongs to (many-to-many).
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->withTimestamps();
    }

    public function documentsStatusHistory(): HasMany
    {
        return $this->hasMany(DocumentStatusHistory::class, 'changed_by');
    }

    /**
     * Sub-departments this user belongs to (many-to-many).
     */
    public function subDepartments(): BelongsToMany
    {
        return $this->belongsToMany(SubDepartment::class, 'sub_department_user')->withTimestamps();
    }

    /**
     * Services this user belongs to (many-to-many).
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_user')->withTimestamps();
    }

    public function documentsMovements(): HasMany
    {
        return $this->hasMany(DocumentMovement::class, 'moved_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function documentsVersionsUploaded(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'uploaded_by');
    }

    public function documentsVersionsLocked(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'locked_by');
    }

    public function documentsDestructionsRequests(): HasMany
    {
        return $this->hasMany(DocumentDestructionRequest::class, 'requested_by');
    }

    public function getRoleAttribute()
    {
        return ucfirst($this->roles()->first()?->name);
    }

    public function favoriteDocuments(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'favorites')->withTimestamps();
    }

    public function subDepartment(): BelongsTo
    {
        return $this->belongsTo(SubDepartment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Computed URL for the user's avatar image.
     */
    public function getAvatarUrlAttribute(): string
    {
        $image = $this->image;

        if (empty($image)) {
            return asset('assets/user.png');
        }

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        if (Str::startsWith($image, ['assets/', '/assets/'])) {
            return asset(ltrim($image, '/'));
        }

        if (Str::startsWith($image, ['storage/', '/storage/'])) {
            return asset(ltrim($image, '/'));
        }

        return asset('storage/' . ltrim($image, '/'));
    }

}
