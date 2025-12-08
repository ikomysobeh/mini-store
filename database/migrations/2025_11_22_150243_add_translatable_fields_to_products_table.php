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
        Schema::table('products', function (Blueprint $table) {
            // Add translatable fields for name, slug, and description
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('slug_ar')->nullable()->unique()->after('slug');
            $table->string('slug_en')->nullable()->unique()->after('slug_ar');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_ar');
        });

        // Migrate existing data: copy current name/slug/description to *_en fields
        DB::statement('UPDATE products SET name_en = name WHERE name_en IS NULL');
        DB::statement('UPDATE products SET slug_en = slug WHERE slug_en IS NULL');
        DB::statement('UPDATE products SET description_en = description WHERE description_en IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar',
                'name_en',
                'slug_ar',
                'slug_en',
                'description_ar',
                'description_en',
            ]);
        });
    }
};
