<?php

namespace Database\Seeders;

use App\Models\IPAddress;
use Illuminate\Database\Seeder;

class IPAddressSeeder extends Seeder
{
    public function run(): void
    {
        $ipAddresses = [
            [
                'ip_address' => '192.168.1.1',
                'ip_version' => 'IPv4',
                'label' => 'Main Router',
                'comment' => 'Office main router',
                'created_by' => 1, // Admin user
            ],
            [
                'ip_address' => '192.168.1.10',
                'ip_version' => 'IPv4',
                'label' => 'Web Server',
                'comment' => 'Production web server',
                'created_by' => 1,
            ],
            [
                'ip_address' => '192.168.1.20',
                'ip_version' => 'IPv4',
                'label' => 'Database Server',
                'comment' => 'MySQL database server',
                'created_by' => 2, // John user
            ],
            [
                'ip_address' => '10.0.0.1',
                'ip_version' => 'IPv4',
                'label' => 'VPN Gateway',
                'comment' => 'Company VPN gateway',
                'created_by' => 2,
            ],
            [
                'ip_address' => '2001:db8:85a3::8a2e:370:7334',
                'ip_version' => 'IPv6',
                'label' => 'IPv6 Test Server',
                'comment' => 'Testing IPv6 connectivity',
                'created_by' => 1,
            ],
            [
                'ip_address' => '172.16.0.5',
                'ip_version' => 'IPv4',
                'label' => 'Mail Server',
                'comment' => 'SMTP/IMAP server',
                'created_by' => 3, // Jane user
            ],
        ];

        foreach ($ipAddresses as $ip) {
            IPAddress::create($ip);
        }
    }
}
