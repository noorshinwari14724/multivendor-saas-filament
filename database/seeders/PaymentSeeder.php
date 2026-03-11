<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Subscription;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $subscriptions = Subscription::all();

        foreach ($subscriptions as $subscription) {
            // Create 1-3 payments per subscription
            $paymentCount = fake()->numberBetween(1, 3);
            
            for ($i = 0; $i < $paymentCount; $i++) {
                $amount = $subscription->amount;
                $tax = $amount * 0.1; // 10% tax
                $total = $amount + $tax;

                Payment::create([
                    'vendor_id' => $subscription->vendor_id,
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'plan_id' => $subscription->plan_id,
                    'payment_number' => Payment::generatePaymentNumber(),
                    'description' => 'Subscription payment for ' . $subscription->plan->name,
                    'amount' => $amount,
                    'currency' => $subscription->currency,
                    'tax_amount' => $tax,
                    'discount_amount' => 0,
                    'total_amount' => $total,
                    'payment_method' => 'stripe',
                    'status' => fake()->randomElement(['completed', 'completed', 'completed', 'pending']),
                    'paid_at' => fake()->optional(0.8)->dateTimeBetween('-60 days', 'now'),
                    'billing_name' => $subscription->vendor->name,
                    'billing_email' => $subscription->vendor->email,
                ]);
            }
        }

        $this->command->info('Payments created successfully!');
    }
}
