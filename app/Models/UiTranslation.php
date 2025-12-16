<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UiTranslation extends Model
{
    protected $table = 'ui_translations';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'en_text',
        'fr_text',
        'ar_text',
    ];

}
