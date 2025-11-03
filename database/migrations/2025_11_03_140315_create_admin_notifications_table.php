<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'new_order', 'new_donation'
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Order details, customer info
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['created_at', 'read_at']);
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_notifications');
    }
};
