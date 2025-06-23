<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $auditLogs = [
            // Login logs
            [
                'user_id' => 1,
                'user_email' => 'admin@example.com',
                'session_id' => 'session-001',
                'action' => 'LOGIN',
                'ip_address' => '127.0.0.1',
                'created_at' => $now->copy()->subHours(24),
            ],
            [
                'user_id' => 2,
                'user_email' => 'john@example.com',
                'session_id' => 'session-002',
                'action' => 'LOGIN',
                'ip_address' => '127.0.0.2',
                'created_at' => $now->copy()->subHours(20),
            ],

            // IP Address creation logs
            [
                'user_id' => 1,
                'user_email' => 'admin@example.com',
                'session_id' => 'session-001',
                'action' => 'CREATE',
                'entity_type' => 'ip_address',
                'entity_id' => 1,
                'ip_address' => '127.0.0.1',
                'new_values' => json_encode([
                    'ip_address' => '192.168.1.1',
                    'label' => 'Main Router',
                    'comment' => 'Office main router',
                ]),
                'created_at' => $now->copy()->subHours(23),
            ],
            [
                'user_id' => 2,
                'user_email' => 'john@example.com',
                'session_id' => 'session-002',
                'action' => 'CREATE',
                'entity_type' => 'ip_address',
                'entity_id' => 3,
                'ip_address' => '127.0.0.2',
                'new_values' => json_encode([
                    'ip_address' => '192.168.1.20',
                    'label' => 'Database Server',
                    'comment' => 'MySQL database server',
                ]),
                'created_at' => $now->copy()->subHours(19),
            ],

            // Update logs
            [
                'user_id' => 1,
                'user_email' => 'admin@example.com',
                'session_id' => 'session-003',
                'action' => 'UPDATE',
                'entity_type' => 'ip_address',
                'entity_id' => 1,
                'ip_address' => '127.0.0.1',
                'old_values' => json_encode([
                    'label' => 'Router',
                    'comment' => 'Router',
                ]),
                'new_values' => json_encode([
                    'label' => 'Main Router',
                    'comment' => 'Office main router',
                ]),
                'metadata' => json_encode([
                    'changes' => [
                        'label' => ['Router', 'Main Router'],
                        'comment' => ['Router', 'Office main router'],
                    ]
                ]),
                'created_at' => $now->copy()->subHours(10),
            ],

            // Failed login
            [
                'user_id' => 0,
                'user_email' => 'anonymous',
                'action' => 'FAILED_LOGIN',
                'ip_address' => 'localhost',
                'metadata' => json_encode(['email' => 'hacker@test.com']),
                'created_at' => $now->copy()->subHours(5),
            ],

            // Recent activity
            [
                'user_id' => 2,
                'user_email' => 'john@example.com',
                'session_id' => 'session-current',
                'action' => 'LOGIN',
                'ip_address' => '127.0.1.111',
                'created_at' => $now->copy()->subMinutes(30),
            ],
            [
                'user_id' => 2,
                'user_email' => 'john@example.com',
                'session_id' => 'session-current',
                'action' => 'CREATE',
                'entity_type' => 'ip_address',
                'entity_id' => 4,
                'ip_address' => '127.0.1.111',
                'new_values' => json_encode([
                    'ip_address' => '10.0.0.1',
                    'label' => 'VPN Gateway',
                    'comment' => 'Company VPN gateway',
                ]),
                'created_at' => $now->copy()->subMinutes(25),
            ],

            // Logout logs
            [
                'user_id' => 1,
                'user_email' => 'admin@example.com',
                'session_id' => 'session-001',
                'action' => 'LOGOUT',
                'ip_address' => '127.0.0.1',
                'created_at' => $now->copy()->subHours(20),
            ],
        ];

        foreach ($auditLogs as $log) {
            AuditLog::create($log);
        }
    }
}
