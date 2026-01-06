<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'name',
        'description',
        'department_id',
    ];

    /**
     * Get the department that owns this room
     */
    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    /**
     * Get all rows in this room
     */
    public function rows(): HasMany
    {
        return $this->hasMany(Row::class);
    }

    /**
     * Get full path representation
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Get all boxes in this room (through rows and shelves)
     */
    public function boxes()
    {
        return Box::whereHas('shelf.row', function ($query) {
            $query->where('room_id', $this->id);
        });
    }

    /**
     * Get all documents in this room
     */
    public function documents()
    {
        return \App\Models\Document::whereHas('box.shelf.row', function ($query) {
            $query->where('room_id', $this->id);
        });
    }
}
