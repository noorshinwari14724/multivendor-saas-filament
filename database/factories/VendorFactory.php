<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VendorFactory extends Factory
{
    protected $model = \App\Models\Vendor::class;

    public function definition(): array
    {
        $name = fake()->company();
        
        return [
            'owner_id' => \App\Models\User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->paragraph(),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'logo' => null,
            'favicon' => null,
            'address' => fake()->optional()->streetAddress(),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->state(),
            'country' => fake()->optional()->country(),
            'postal_code' => fake()->optional()->postcode(),
            'business_type' => fake()->optional()->randomElement(['LLC', 'Corporation', 'Sole Proprietorship', 'Partnership']),
            'tax_id' => fake()->optional()->regexify('[A-Z0-9]{10}'),
            'registration_number' => fake()->optional()->regexify('[A-Z0-9]{8}'),
            'status' => fake()->randomElement(['pending', 'approved', 'approved', 'approved', 'suspended']),
            'approved_at' => fn (array $attributes) => $attributes['status'] === 'approved' ? now() : null,
            'approved_by' => null,
            'rejection_reason' => null,
            'custom_domain' => null,
            'custom_domain_verified' => false,
            'primary_color' => fake()->optional()->hexColor(),
            'secondary_color' => fake()->optional()->hexColor(),
            'settings' => [],
            'total_users' => 0,
            'total_products' => fake()->numberBetween(0, 1000),
            'total_orders' => fake()->numberBetween(0, 5000),
            'total_revenue' => fake()->randomFloat(2, 0, 100000),
            'trial_ends_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'is_trial' => fake()->boolean(30),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function onTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trial' => true,
            'trial_ends_at' => now()->addDays(14),
        ]);
    }
}
