<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('donation_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('organization_name')->nullable();
            $table->enum('relationship_to_donor', ['self', 'family', 'friend', 'organization', 'other'])->nullable();
            $table->text('special_instructions')->nullable();
            $table->boolean('is_organization')->default(false);
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['first_name', 'last_name']);
            $table->index('organization_name');
            $table->index('is_organization');
        });
    }

    public function down()
    {
        Schema::dropIfExists('donation_beneficiaries');
    }
};
