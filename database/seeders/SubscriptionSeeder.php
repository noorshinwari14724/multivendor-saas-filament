<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Models\Plan;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = Vendor::where('status', 'approved')->get();
        $plans = Plan::where('is_active', true)->get();

        foreach ($vendors as $vendor) {
            $plan = $plans->random();
            
            Subscription::create([
                'vendor_id' => $vendor->id,
                'plan_id' => $plan->id,
                'user_id' => $vendor->owner_id,
                'status' => fake()->randomElement(['active', 'active', 'active', 'trialing']),
                'starts_at' => now()->subDays(fake()->numberBetween(1, 60)),
                'ends_at' => null,
                'trial_ends_at' => fake()->optional(0.3)->dateTimeBetween('now', '+14 days'),
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'billing_cycle' => $plan->billing_cycle,
                'current_period_start' => now()->subDays(fake()->numberBetween(1, 30)),
                'current_period_end' => now()->addDays(fake()->numberBetween(1, 30)),
            ]);
        }

        $this->command->info('Subscriptions created successfully!');
    }
}
