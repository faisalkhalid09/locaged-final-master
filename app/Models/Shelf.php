<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shelf extends Model
{
    protected $fillable = [
        'row_id',
        'name',
        'description',
    ];

    /**
     * Get the row that owns this shelf
     */
    public function row(): BelongsTo
    {
        return $this->belongsTo(Row::class);
    }

    /**
     * Get all boxes on this shelf
     */
    public function boxes(): HasMany
    {
        return $this->hasMany(Box::class);
    }

    /**
     * Get full path representation
     */
    public function __toString(): string
    {
        return $this->row->room->name . ' → ' . $this->row->name . ' → ' . $this->name;
    }

    /**
     * Get all documents on this shelf
     */
    public function documents()
    {
        return \App\Models\Document::whereHas('box', function ($query) {
            $query->where('shelf_id', $this->id);
        });
    }
}
