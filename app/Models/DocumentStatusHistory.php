<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentStatusHistory extends Model
{
    protected $table = 'document_status_history';

    protected $fillable = [
        'document_id',
        'from_status',
        'to_status',
        'changed_by',
        'changed_at'
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'changed_by');
    }

    protected $casts = [
        'changed_at' => 'datetime'
    ];
}
