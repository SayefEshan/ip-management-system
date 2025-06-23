<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    public function logLogin(array $data): void
    {
        $this->createLog([
            'user_id' => $data['user_id'],
            'user_email' => $this->getUserEmail($data),
            'session_id' => $data['session_id'],
            'action' => 'LOGIN',
            'ip_address' => $data['ip_address'] ?? null,
        ]);
    }

    public function logLogout(array $data): void
    {
        $this->createLog([
            'user_id' => $data['user_id'],
            'user_email' => $this->getUserEmail($data),
            'session_id' => $data['session_id'],
            'action' => 'LOGOUT',
            'ip_address' => $data['ip_address'] ?? null,
        ]);
    }

    public function logIpCreated($ipAddress, array $userContext): void
    {
        $this->createLog([
            'user_id' => $userContext['id'],
            'user_email' => $userContext['email'],
            'session_id' => $userContext['session_id'],
            'action' => 'CREATE',
            'entity_type' => 'ip_address',
            'entity_id' => $ipAddress->id,
            'ip_address' => $userContext['ip_address'] ?? null,
            'new_values' => [
                'ip_address' => $ipAddress->ip_address,
                'label' => $ipAddress->label,
                'comment' => $ipAddress->comment,
            ],
        ]);
    }

    public function logIpUpdated($ipAddress, array $oldValues, array $userContext): void
    {
        $changes = [];
        $newValues = [];

        // Track what changed
        if ($oldValues['label'] !== $ipAddress->label) {
            $changes['label'] = [$oldValues['label'], $ipAddress->label];
            $newValues['label'] = $ipAddress->label;
        }

        if ($oldValues['comment'] !== $ipAddress->comment) {
            $changes['comment'] = [$oldValues['comment'], $ipAddress->comment];
            $newValues['comment'] = $ipAddress->comment;
        }

        if (empty($changes)) {
            return; // No changes to log
        }

        $this->createLog([
            'user_id' => $userContext['id'],
            'user_email' => $userContext['email'],
            'session_id' => $userContext['session_id'],
            'action' => 'UPDATE',
            'entity_type' => 'ip_address',
            'entity_id' => $ipAddress->id,
            'ip_address' => $userContext['ip_address'] ?? null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => ['changes' => $changes],
        ]);
    }

    public function logIpDeleted($ipAddress, array $userContext): void
    {
        $this->createLog([
            'user_id' => $userContext['id'],
            'user_email' => $userContext['email'],
            'session_id' => $userContext['session_id'],
            'action' => 'DELETE',
            'entity_type' => 'ip_address',
            'entity_id' => $ipAddress->id,
            'ip_address' => $userContext['ip_address'] ?? null,
            'old_values' => [
                'ip_address' => $ipAddress->ip_address,
                'label' => $ipAddress->label,
                'comment' => $ipAddress->comment,
            ],
        ]);
    }

    public function logFailedLogin(array $data): void
    {
        $this->createLog([
            'user_id' => 0,
            'user_email' => 'anonymous',
            'action' => 'FAILED_LOGIN',
            'ip_address' => $data['ip_address'] ?? null,
            'metadata' => ['email' => $data['metadata']['email'] ?? 'unknown'],
        ]);
    }

    private function createLog(array $data): void
    {
        try {
            AuditLog::create($data);
        } catch (\Exception $e) {
            Log::error('Failed to create audit log: ' . $e->getMessage(), $data);
        }
    }

    private function getUserEmail(array $data): string
    {
        return $data['user_email'] ?? 'unknown';
    }
}
