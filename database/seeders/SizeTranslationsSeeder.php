<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Size;

class SizeTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define translations for common sizes
        $translations = [
            // General sizes
            'Small' => 'صغير',
            'Medium' => 'متوسط',
            'Large' => 'كبير',
            'Extra Large' => 'كبير جداً',
            'One Size' => 'مقاس واحد',
            
            // Clothing sizes
            'XS' => 'صغير جداً',
            'S' => 'صغير',
            'M' => 'متوسط',
            'L' => 'كبير',
            'XL' => 'كبير جداً',
            'XXL' => 'كبير جداً جداً',
            
            // Age-based sizes
            'For children under 8 years old' => 'للأطفال أقل من 8 سنوات',
            'For adults' => 'للبالغين',
            'For kids' => 'للأطفال',
            'For teens' => 'للمراهقين',
            
            // Shoe sizes (Arabic numerals)
            '36' => '٣٦',
            '37' => '٣٧',
            '38' => '٣٨',
            '39' => '٣٩',
            '40' => '٤٠',
            '41' => '٤١',
            '42' => '٤٢',
            '43' => '٤٣',
            '44' => '٤٤',
            '45' => '٤٥',
            
            // Numeric sizes
            '12' => '١٢',
        ];

        $this->command->info('Starting size translations update...');
        
        $updated = 0;
        $skipped = 0;

        foreach (Size::all() as $size) {
            // Skip if already has Arabic translation
            if (!empty($size->name_ar)) {
                $this->command->warn("Skipped: {$size->name_en} (already has Arabic translation)");
                $skipped++;
                continue;
            }

            // Check if we have a translation for this size
            if (isset($translations[$size->name_en])) {
                $size->name_ar = $translations[$size->name_en];
                $size->save();
                
                $this->command->info("Updated: {$size->name_en} → {$size->name_ar}");
                $updated++;
            } else {
                $this->command->comment("No translation found for: {$size->name_en}");
            }
        }

        $this->command->newLine();
        $this->command->info("✓ Translation update complete!");
        $this->command->info("  - Updated: {$updated} sizes");
        $this->command->info("  - Skipped: {$skipped} sizes (already translated)");
        
        if ($updated === 0 && $skipped === 0) {
            $this->command->warn("  - No sizes found in database");
        }
    }
}
