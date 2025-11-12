<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            
            // Donor Information
            $table->string('name'); // Donor's full name
            $table->string('phone', 20); // Phone number with country code
            
            // Donation Details
            $table->decimal('value', 10, 2); // Donation amount
            $table->text('message')->nullable(); // Optional message from donor
            
            // Status Management
            $table->enum('status', ['pending', 'completed', 'failed'])
                  ->default('pending');
            
            // Payment Information
            $table->string('payment_method')->default('stripe');
            $table->string('payment_id')->nullable(); // Stripe session ID
            $table->string('payment_intent_id')->nullable(); // Stripe payment intent ID
            $table->timestamp('paid_at')->nullable(); // When payment was completed
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index('status');
            $table->index('created_at');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
