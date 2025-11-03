<?php
// database/migrations/2024_XX_XX_XXXXXX_add_variant_support_to_cart_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('selected_color_id')->nullable()->constrained('colors')->onDelete('set null');
            $table->foreignId('selected_size_id')->nullable()->constrained('sizes')->onDelete('set null');

            // Add indexes
            $table->index('variant_id');
            $table->index('selected_color_id');
            $table->index('selected_size_id');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropForeign(['selected_color_id']);
            $table->dropForeign(['selected_size_id']);
            $table->dropColumn(['variant_id', 'selected_color_id', 'selected_size_id']);
        });
    }
};
