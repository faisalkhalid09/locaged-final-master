<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Box extends Model
{
    protected $fillable = [
        'shelf_id',
        'name',
        'description',
    ];

    /**
     * Get the shelf that owns this box
     */
    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    /**
     * Get the row through shelf
     */
    public function row()
    {
        return $this->shelf()->with('row')->first()->row ?? null;
    }

    /**
     * Get the room through shelf and row
     */
    public function room()
    {
        return $this->shelf()->with('row.room')->first()->row->room ?? null;
    }

    /**
     * Get all documents in this box
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'box_id');
    }

    /**
     * Get full path representation (Room → Row → Shelf → Box)
     */
    public function __toString(): string
    {
        // Load relationships if not already loaded
        $this->loadMissing('shelf.row.room');
        
        return $this->shelf->row->room->name . ' → ' . 
               $this->shelf->row->name . ' → ' . 
               $this->shelf->name . ' → ' . 
               $this->name;
    }

    /**
     * Get the full path as an array
     */
    public function getFullPath(): array
    {
        // Load relationships if not already loaded
        $this->loadMissing('shelf.row.room');
        
        return [
            'room' => $this->shelf->row->room->name,
            'row' => $this->shelf->row->name,
            'shelf' => $this->shelf->name,
            'box' => $this->name,
        ];
    }
}
