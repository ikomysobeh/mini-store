<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Step 1: Map old statuses to new statuses
        DB::table('orders')->where('status', 'shipped')->update(['status' => 'processing']);
        DB::table('orders')->where('status', 'delivered')->update(['status' => 'done']);
        DB::table('orders')->where('status', 'cancelled')->update(['status' => 'failed']);
        
        // Step 2: Change the enum column to include new values
        DB::statement("ALTER TABLE `orders` CHANGE COLUMN `status` `status` ENUM('pending', 'processing', 'failed', 'success', 'done') NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        // Reverse mapping
        DB::table('orders')->where('status', 'done')->update(['status' => 'delivered']);
        DB::table('orders')->where('status', 'failed')->update(['status' => 'cancelled']);
        // processing stays as processing
        
        // Revert enum values
        DB::statement("ALTER TABLE `orders` CHANGE COLUMN `status` `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
