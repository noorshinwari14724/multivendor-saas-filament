<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Role within the vendor
            $table->enum('role', ['owner', 'admin', 'manager', 'staff', 'viewer'])->default('staff');
            $table->json('permissions')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'invited', 'suspended'])->default('invited');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            
            // Department
            $table->string('department')->nullable();
            $table->string('job_title')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['vendor_id', 'user_id']);
            $table->index('vendor_id');
            $table->index('user_id');
            $table->index('role');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_users');
    }
};
