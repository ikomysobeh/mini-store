<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, let's fix existing duplicate SKUs
        $this->fixExistingDuplicateSkus();

        // Add indexes for better performance
        Schema::table('product_variants', function (Blueprint $table) {
            // Make sure the unique constraint exists
            if (!Schema::hasIndex('product_variants', 'product_variants_sku_unique')) {
                $table->unique('sku', 'product_variants_sku_unique');
            }

            // Add performance indexes
            $table->index(['product_id', 'is_active'], 'idx_product_variants_product_active');
            $table->index(['color_id', 'is_active'], 'idx_product_variants_color_active');
            $table->index(['size_id', 'is_active'], 'idx_product_variants_size_active');
            $table->index(['stock', 'is_active'], 'idx_product_variants_stock_active');
        });
    }

    public function down()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('idx_product_variants_product_active');
            $table->dropIndex('idx_product_variants_color_active');
            $table->dropIndex('idx_product_variants_size_active');
            $table->dropIndex('idx_product_variants_stock_active');
        });
    }

    private function fixExistingDuplicateSkus()
    {
        // Find duplicate SKUs
        $duplicates = DB::select("
            SELECT sku, COUNT(*) as count
            FROM product_variants
            WHERE sku IS NOT NULL
            GROUP BY sku
            HAVING count > 1
        ");

        foreach ($duplicates as $duplicate) {
            $variants = DB::select("
                SELECT id, product_id, color_id, size_id
                FROM product_variants
                WHERE sku = ?
                ORDER BY id
            ", [$duplicate->sku]);

            // Keep first one, update others
            for ($i = 1; $i < count($variants); $i++) {
                $variant = $variants[$i];
                $newSku = $this->generateNewSku($variant, $i);

                DB::update("
                    UPDATE product_variants
                    SET sku = ?
                    WHERE id = ?
                ", [$newSku, $variant->id]);
            }
        }

        // Fix any NULL SKUs
        $nullSkuVariants = DB::select("
            SELECT id, product_id, color_id, size_id
            FROM product_variants
            WHERE sku IS NULL OR sku = ''
        ");

        foreach ($nullSkuVariants as $variant) {
            $newSku = $this->generateNewSku($variant, 0);
            DB::update("
                UPDATE product_variants
                SET sku = ?
                WHERE id = ?
            ", [$newSku, $variant->id]);
        }
    }

    private function generateNewSku($variant, $suffix = 0)
    {
        // Get product, color, size info
        $product = DB::selectOne("SELECT name FROM products WHERE id = ?", [$variant->product_id]);
        $color = DB::selectOne("SELECT name FROM colors WHERE id = ?", [$variant->color_id]);
        $size = DB::selectOne("SELECT name FROM sizes WHERE id = ?", [$variant->size_id]);

        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->name ?? 'PRD'), 0, 3));
        $colorCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $color->name ?? 'COL'), 0, 3));
        $sizeCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $size->name ?? 'SZ'), 0, 2));

        if (empty($productCode)) $productCode = 'PRD';
        if (empty($colorCode)) $colorCode = 'COL';
        if (empty($sizeCode)) $sizeCode = 'SZ';

        $baseSku = "{$productCode}-{$colorCode}-{$sizeCode}-{$variant->product_id}";

        if ($suffix > 0) {
            $baseSku .= "-" . str_pad($suffix, 2, '0', STR_PAD_LEFT);
        }

        // Ensure uniqueness
        $counter = 0;
        $finalSku = $baseSku;
        while (DB::selectOne("SELECT id FROM product_variants WHERE sku = ? AND id != ?", [$finalSku, $variant->id])) {
            $counter++;
            $finalSku = $baseSku . "-" . str_pad($counter, 2, '0', STR_PAD_LEFT);
        }

        return $finalSku;
    }
};
