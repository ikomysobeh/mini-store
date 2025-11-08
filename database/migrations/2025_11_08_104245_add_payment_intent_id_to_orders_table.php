<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_payment_intent_id_to_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_intent_id')->nullable()->after('payment_id');
            $table->index('payment_intent_id'); // For fast lookup
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_intent_id']);
            $table->dropColumn('payment_intent_id');
        });
    }
};