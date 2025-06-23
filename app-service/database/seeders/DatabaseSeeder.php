<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\AuditLogSeeder;
use Database\Seeders\IPAddressSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            IPAddressSeeder::class,
            AuditLogSeeder::class,
        ]);
    }
}
