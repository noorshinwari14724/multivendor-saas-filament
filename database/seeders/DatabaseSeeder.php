<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingSeeder::class,
            UserSeeder::class,
            PlanSeeder::class,
            VendorSeeder::class,
            SubscriptionSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
