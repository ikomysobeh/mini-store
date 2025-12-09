<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * SAFE MIGRATION FOR PRODUCTION
     * Step 1: Migrate data from legacy fields to bilingual fields
     * Step 2: Remove redundant columns (slug_ar, slug_en, name, description)
     *
     * IMPORTANT: Backup your database before running this migration!
     */
    public function up(): void
    {
        // ============================================
        // STEP 1: SAFELY COPY DATA TO BILINGUAL FIELDS
        // ============================================

        // Products: Copy 'name' to 'name_en' if name_en is empty
        DB::statement('UPDATE products SET name_en = name WHERE (name_en IS NULL OR name_en = "") AND name IS NOT NULL AND name != ""');

        // Products: Copy 'description' to 'description_en' if description_en is empty
        DB::statement('UPDATE products SET description_en = description WHERE (description_en IS NULL OR description_en = "") AND description IS NOT NULL AND description != ""');

        // Products: Ensure slug has data from slug_en
        DB::statement('UPDATE products SET slug = slug_en WHERE (slug IS NULL OR slug = "") AND slug_en IS NOT NULL AND slug_en != ""');

        // Categories: Copy 'name' to 'name_en' if name_en is empty
        DB::statement('UPDATE categories SET name_en = name WHERE (name_en IS NULL OR name_en = "") AND name IS NOT NULL AND name != ""');

        // Categories: Copy 'description' to 'description_en' if description_en is empty
        DB::statement('UPDATE categories SET description_en = description WHERE (description_en IS NULL OR description_en = "") AND description IS NOT NULL AND description != ""');

        // Categories: Ensure slug has data from slug_en
        DB::statement('UPDATE categories SET slug = slug_en WHERE (slug IS NULL OR slug = "") AND slug_en IS NOT NULL AND slug_en != ""');

        // ============================================
        // STEP 2: REMOVE REDUNDANT COLUMNS
        // ============================================

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['slug_ar', 'slug_en', 'name', 'description']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['slug_ar', 'slug_en', 'name', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore columns for products
        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->text('description')->nullable()->after('slug');
            $table->string('slug_ar')->nullable()->after('slug');
            $table->string('slug_en')->nullable()->after('slug_ar');
        });

        // Restore columns for categories
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->text('description')->nullable()->after('slug');
            $table->string('slug_ar')->nullable()->after('slug');
            $table->string('slug_en')->nullable()->after('slug_ar');
        });

        // Restore data from bilingual fields
        DB::statement('UPDATE products SET name = name_en, description = description_en, slug_en = slug');
        DB::statement('UPDATE categories SET name = name_en, description = description_en, slug_en = slug');
    }
};
