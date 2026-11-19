<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $fillable = [
        'equipment',
        'brand',
        'model',
        'module',
        'position',
    ];
}
