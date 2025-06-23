<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AuditService
{
    private string $appServiceUrl;

    public function __construct()
    {
        $this->appServiceUrl = env('APP_SERVICE_URL', 'http://app-service');
    }

    public function logLogin(int $userId, string $userEmail, string $sessionId, string $ipAddress)
    {
        $this->sendLog([
            'user_id' => $userId,
            'user_email' => $userEmail,
            'session_id' => $sessionId,
            'action' => 'LOGIN',
            'ip_address' => $ipAddress
        ]);
    }

    public function logLogout(int $userId, string $sessionId, string $ipAddress, string $userEmail)
    {
        $this->sendLog([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'action' => 'LOGOUT',
            'ip_address' => $ipAddress,
            'user_email' => $userEmail
        ]);
    }

    public function logFailedLogin(string $email, string $ipAddress)
    {
        $this->sendLog([
            'action' => 'FAILED_LOGIN',
            'metadata' => ['email' => $email],
            'ip_address' => $ipAddress
        ]);
    }

    private function sendLog(array $data)
    {
        try {
            Http::timeout(5)
                ->post($this->appServiceUrl . '/api/internal/audit-log', $data);
        } catch (Exception $e) {
            Log::error('Failed to send audit log: ' . $e->getMessage());
        }
    }
}
