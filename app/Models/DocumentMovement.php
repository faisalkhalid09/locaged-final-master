<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentMovement extends Model
{
    protected $table = 'document_movements';

    protected $fillable = [
        'document_id',
        'movement_type',
        'moved_from',
        'moved_to',
        'moved_from_box_id',
        'moved_to_box_id',
        'moved_by',
        'moved_at',
    ];

    protected $casts = [
        'moved_at' => 'datetime'
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function movedFrom(): BelongsTo
    {
        return $this->belongsTo(PhysicalLocation::class,'moved_from');
    }

    public function movedTo(): BelongsTo
    {
        return $this->belongsTo(PhysicalLocation::class,'moved_to');
    }

    /**
     * Get the box this document was moved from (new hierarchical structure)
     */
    public function movedFromBox(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'moved_from_box_id');
    }

    /**
     * Get the box this document was moved to (new hierarchical structure)
     */
    public function movedToBox(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'moved_to_box_id');
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'moved_by');
    }

    public function __toString()
    {
        return 'From : ' . $this->movedFrom .' To : ' .$this->movedTo .' By : '. $this->movedBy?->full_name;
    }




}
