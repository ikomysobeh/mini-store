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
        Schema::table('cart_items', function (Blueprint $table) {
            // Add multilingual product name fields
            $table->string('product_name_en')->nullable()->after('product_name');
            $table->string('product_name_ar')->nullable()->after('product_name_en');
        });

        // Migrate existing data: copy current product_name to product_name_en
        DB::statement('UPDATE cart_items SET product_name_en = product_name WHERE product_name_en IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn([
                'product_name_en',
                'product_name_ar',
            ]);
        });
    }
};
