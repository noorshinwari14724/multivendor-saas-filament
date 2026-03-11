<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('billing_cycle', ['monthly', 'yearly', 'lifetime'])->default('monthly');
            $table->integer('trial_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            
            // Plan Features & Limits
            $table->integer('max_vendors')->default(1);
            $table->integer('max_users_per_vendor')->default(5);
            $table->integer('max_products')->default(100);
            $table->integer('max_storage_mb')->default(1024);
            $table->integer('max_api_calls_per_day')->default(1000);
            $table->boolean('has_custom_domain')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->boolean('has_advanced_analytics')->default(false);
            $table->boolean('has_white_label')->default(false);
            $table->json('features')->nullable();
            $table->json('metadata')->nullable();
            
            // Stripe Integration
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_product_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('is_active');
            $table->index('is_default');
            $table->index('stripe_price_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
