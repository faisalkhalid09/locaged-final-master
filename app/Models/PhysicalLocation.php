<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhysicalLocation extends Model
{
    protected $table = 'physical_locations';

    protected $fillable = [
        'room',
        'row',
        'shelf',
        'box',
        'description',
    ];

    public function __toString()
    {
        return 'Room : ' . $this->room . ' Row : ' . $this->row . ' Shelf : ' . $this->shelf . ' Box : ' . $this->box;
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'physical_location_id');
    }
}
