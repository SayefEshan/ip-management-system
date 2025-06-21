<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IPAddress extends Model
{
    use SoftDeletes;

    protected $table = 'ip_addresses';

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

    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function canBeModifiedBy($userId, $isSuperAdmin = false)
    {
        // Super admin can modify any IP
        if ($isSuperAdmin) {
            return true;
        }

        // Regular users can only modify their own IPs
        return $this->created_by == $userId;
    }
}
