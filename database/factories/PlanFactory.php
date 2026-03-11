<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    protected $model = \App\Models\Plan::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Starter', 'Basic', 'Professional', 'Business', 'Enterprise']);
        $billingCycle = fake()->randomElement(['monthly', 'yearly']);
        
        $prices = [
            'Starter' => 9,
            'Basic' => 29,
            'Professional' => 79,
            'Business' => 149,
            'Enterprise' => 299,
        ];
        
        $price = $prices[$name] ?? 29;
        
        if ($billingCycle === 'yearly') {
            $price = $price * 10; // 2 months free
        }

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price' => $price,
            'currency' => 'USD',
            'billing_cycle' => $billingCycle,
            'trial_days' => fake()->randomElement([0, 7, 14, 30]),
            'is_active' => true,
            'is_featured' => fake()->boolean(20),
            'is_default' => false,
            'sort_order' => fake()->numberBetween(1, 10),
            'max_vendors' => 1,
            'max_users_per_vendor' => fake()->randomElement([1, 3, 5, 10, 25, -1]),
            'max_products' => fake()->randomElement([50, 100, 500, 1000, -1]),
            'max_storage_mb' => fake()->randomElement([512, 1024, 5120, 10240, -1]),
            'max_api_calls_per_day' => fake()->randomElement([100, 1000, 10000, 100000, -1]),
            'has_custom_domain' => fake()->boolean(60),
            'has_priority_support' => fake()->boolean(40),
            'has_advanced_analytics' => fake()->boolean(30),
            'has_white_label' => fake()->boolean(10),
            'features' => [
                ['icon' => 'heroicon-o-check', 'label' => 'Basic Features', 'value' => 'Included'],
                ['icon' => 'heroicon-o-check', 'label' => 'Email Support', 'value' => 'Included'],
            ],
            'metadata' => [],
            'stripe_price_id' => null,
            'stripe_product_id' => null,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Free',
            'slug' => 'free',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'trial_days' => 0,
            'max_users_per_vendor' => 2,
            'max_products' => 20,
            'max_storage_mb' => 100,
            'max_api_calls_per_day' => 100,
            'has_custom_domain' => false,
            'has_priority_support' => false,
            'has_advanced_analytics' => false,
            'has_white_label' => false,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
