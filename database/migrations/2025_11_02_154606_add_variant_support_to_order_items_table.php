<?php
// database/migrations/2024_XX_XX_XXXXXX_add_variant_support_to_order_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('set null');
            $table->string('selected_color')->nullable(); // Store color name for history
            $table->string('selected_size')->nullable(); // Store size name for history
            $table->string('selected_color_hex', 7)->nullable(); // Store hex for display

            // Add indexes
            $table->index('variant_id');
            $table->index('selected_color');
            $table->index('selected_size');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn(['variant_id', 'selected_color', 'selected_size', 'selected_color_hex']);
        });
    }
};
