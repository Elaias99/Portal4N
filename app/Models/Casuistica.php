<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Casuistica extends Model
{
    use HasFactory;

        protected $table = 'casuisticas';

        protected $fillable = ['nombre', ];


        // En Casuistica.php
        public function reclamos()
        {
            return $this->hasMany(Reclamos::class);
        }
}
