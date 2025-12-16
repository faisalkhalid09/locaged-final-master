<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentDestructionRequest extends Model
{
    protected $table = 'document_destruction_requests';

    protected $fillable = [
        'document_id',
        'requested_by',
        'requested_at',
        'status',
        'implementation_id',
        'implemented_at',
    ];

    public function document(): BelongsTo
    {
        // Bypass Document global scopes so records in the destruction queue remain
        // visible here even though they are hidden from normal document listings.
        return $this->belongsTo(Document::class)
            ->withoutGlobalScopes()
            ->whereNull('deleted_at');

    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'requested_by','id');
    }

    public function implementation(): BelongsTo
    {
        return $this->belongsTo(DocumentMovement::class,'implementation_id','id');
    }

    protected $casts = [
        'requested_at' => 'datetime',
        'implemented_at' => 'datetime'
    ];
}
