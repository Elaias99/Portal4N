<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemEvents extends Model
{
    use HasFactory;

    protected $casts = [
        'payload' => 'array',
    ];

    protected $table = 'system_events';

    protected $fillable = ['event_type', 'source', 'reference_id', 'payload', 'ip_address', 'user_agent'];
}
