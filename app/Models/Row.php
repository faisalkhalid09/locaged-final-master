<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Row extends Model
{
    protected $fillable = [
        'room_id',
        'name',
        'description',
    ];

    /**
     * Get the room that owns this row
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get all shelves in this row
     */
    public function shelves(): HasMany
    {
        return $this->hasMany(Shelf::class);
    }

    /**
     * Get full path representation
     */
    public function __toString(): string
    {
        return $this->room->name . ' → ' . $this->name;
    }

    /**
     * Get all boxes in this row (through shelves)
     */
    public function boxes()
    {
        return Box::whereHas('shelf', function ($query) {
            $query->where('row_id', $this->id);
        });
    }

    /**
     * Get all documents in this row
     */
    public function documents()
    {
        return \App\Models\Document::whereHas('box.shelf', function ($query) {
            $query->where('row_id', $this->id);
        });
    }
}
