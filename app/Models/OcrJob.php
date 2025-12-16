<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrJob extends Model
{
    protected $table = 'ocr_jobs';

    protected $fillable = [
        'document_version_id',
        'status',
        'queued_at',
        'processed_at',
        'completed_at',
        'error_message',
    ];


    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class,'document_version_id');
    }

    protected $casts = [
        'queued_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
}
