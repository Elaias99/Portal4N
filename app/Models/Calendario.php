<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendario extends Model
{
    protected $fillable = [
        'date',
        'name',
        'type',
        'irrenunciable',
        'year',
        'country',
    ];
}
