<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sizes', function (Blueprint $table) {
            // Add translation columns
            $table->string('name_en')->nullable()->after('id');
            $table->string('name_ar')->nullable()->after('name_en');
        });

        // Migrate existing data: copy 'name' to 'name_en'
        DB::table('sizes')->get()->each(function ($size) {
            DB::table('sizes')
                ->where('id', $size->id)
                ->update(['name_en' => $size->name]);
        });

        // Drop the old 'name' column
        Schema::table('sizes', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sizes', function (Blueprint $table) {
            // Re-add the 'name' column
            $table->string('name')->after('id');
        });

        // Restore data from name_en to name
        DB::table('sizes')->get()->each(function ($size) {
            DB::table('sizes')
                ->where('id', $size->id)
                ->update(['name' => $size->name_en ?? $size->name_ar ?? '']);
        });

        // Drop translation columns
        Schema::table('sizes', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'name_ar']);
        });
    }
};
