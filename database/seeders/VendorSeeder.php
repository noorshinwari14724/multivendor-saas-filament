<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;
use App\Models\User;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        // Get some users to be owners
        $users = User::role('user')->take(5)->get();

        foreach ($users as $user) {
            Vendor::factory()->create([
                'owner_id' => $user->id,
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => 1, // Super admin
            ]);
        }

        // Create some pending vendors
        Vendor::factory(3)->pending()->create();

        // Create some suspended vendors
        Vendor::factory(2)->suspended()->create();

        $this->command->info('Vendors created successfully!');
    }
}
