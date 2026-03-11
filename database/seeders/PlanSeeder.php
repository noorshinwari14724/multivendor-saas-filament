<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Create Free Plan
        Plan::firstOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'description' => 'Perfect for getting started with basic features.',
                'price' => 0,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 0,
                'is_active' => true,
                'is_featured' => false,
                'is_default' => true,
                'sort_order' => 1,
                'max_vendors' => 1,
                'max_users_per_vendor' => 2,
                'max_products' => 20,
                'max_storage_mb' => 100,
                'max_api_calls_per_day' => 100,
                'has_custom_domain' => false,
                'has_priority_support' => false,
                'has_advanced_analytics' => false,
                'has_white_label' => false,
                'features' => [
                    ['icon' => 'heroicon-o-check', 'label' => 'Up to 20 products', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => '2 team members', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => '100 MB storage', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => 'Email support', 'value' => ''],
                ],
            ]
        );

        // Create Starter Plan
        Plan::firstOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Great for small businesses and startups.',
                'price' => 9,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => false,
                'is_default' => false,
                'sort_order' => 2,
                'max_vendors' => 1,
                'max_users_per_vendor' => 5,
                'max_products' => 100,
                'max_storage_mb' => 1024,
                'max_api_calls_per_day' => 1000,
                'has_custom_domain' => false,
                'has_priority_support' => false,
                'has_advanced_analytics' => false,
                'has_white_label' => false,
                'features' => [
                    ['icon' => 'heroicon-o-check', 'label' => 'Up to 100 products', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => '5 team members', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => '1 GB storage', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => 'Priority email support', 'value' => ''],
                ],
            ]
        );

        // Create Professional Plan
        Plan::firstOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'description' => 'Best for growing businesses with advanced needs.',
                'price' => 29,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => true,
                'is_default' => false,
                'sort_order' => 3,
                'max_vendors' => 1,
                'max_users_per_vendor' => 15,
                'max_products' => 500,
                'max_storage_mb' => 5120,
                'max_api_calls_per_day' => 10000,
                'has_custom_domain' => true,
                'has_priority_support' => true,
                'has_advanced_analytics' => true,
                'has_white_label' => false,
                'features' => [
                    ['icon' => 'heroicon-o-check', 'label' => 'Up to 500 products', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => '15 team members', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => '5 GB storage', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => 'Custom domain', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => 'Priority support', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => 'Advanced analytics', 'value' => ''],
                ],
            ]
        );

        // Create Enterprise Plan
        Plan::firstOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'For large organizations with unlimited needs.',
                'price' => 99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'trial_days' => 30,
                'is_active' => true,
                'is_featured' => false,
                'is_default' => false,
                'sort_order' => 4,
                'max_vendors' => 1,
                'max_users_per_vendor' => -1,
                'max_products' => -1,
                'max_storage_mb' => -1,
                'max_api_calls_per_day' => -1,
                'has_custom_domain' => true,
                'has_priority_support' => true,
                'has_advanced_analytics' => true,
                'has_white_label' => true,
                'features' => [
                    ['icon' => 'heroicon-o-check', 'label' => 'Unlimited products', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => 'Unlimited team members', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => 'Unlimited storage', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => 'Custom domain', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => '24/7 Priority support', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => 'Advanced analytics', 'value' => ''],
                    ['icon' => 'heroicon-o-check', 'label' => 'White label option', 'value' => ''],
                ],
            ]
        );

        $this->command->info('Plans created successfully!');
    }
}
