<?php
// database/migrations/2024_XX_XX_XXXXXX_create_product_variants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('color_id')->constrained()->onDelete('cascade');
            $table->foreignId('size_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->integer('stock')->default(0);
            $table->decimal('price_adjustment', 8, 2)->default(0.00); // +/- from base price
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique combination of product, color, and size
            $table->unique(['product_id', 'color_id', 'size_id'], 'unique_variant');

            $table->index('product_id');
            $table->index('color_id');
            $table->index('size_id');
            $table->index('sku');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
