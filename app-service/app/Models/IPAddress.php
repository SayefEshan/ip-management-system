<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IPAddress extends Model
{
    protected $fillable = [
        'ip_address',
        'ip_version',
        'label',
        'comment',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    
}
