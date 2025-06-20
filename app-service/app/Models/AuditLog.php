<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_email',
        'session_id',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'old_values',
        'new_values',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Override save to prevent updates
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new Exception('Audit logs cannot be updated');
        }

        return parent::save($options);
    }

    // Override delete to prevent deletion
    public function delete()
    {
        throw new Exception('Audit logs cannot be deleted');
    }
}
