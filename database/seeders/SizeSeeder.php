<?php
// database/seeders/SizeSeeder.php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            // Clothing sizes
            ['name' => 'XS', 'category_type' => 'clothing', 'sort_order' => 1],
            ['name' => 'S', 'category_type' => 'clothing', 'sort_order' => 2],
            ['name' => 'M', 'category_type' => 'clothing', 'sort_order' => 3],
            ['name' => 'L', 'category_type' => 'clothing', 'sort_order' => 4],
            ['name' => 'XL', 'category_type' => 'clothing', 'sort_order' => 5],
            ['name' => 'XXL', 'category_type' => 'clothing', 'sort_order' => 6],

            // Shoe sizes
            ['name' => '36', 'category_type' => 'shoes', 'sort_order' => 1],
            ['name' => '37', 'category_type' => 'shoes', 'sort_order' => 2],
            ['name' => '38', 'category_type' => 'shoes', 'sort_order' => 3],
            ['name' => '39', 'category_type' => 'shoes', 'sort_order' => 4],
            ['name' => '40', 'category_type' => 'shoes', 'sort_order' => 5],
            ['name' => '41', 'category_type' => 'shoes', 'sort_order' => 6],
            ['name' => '42', 'category_type' => 'shoes', 'sort_order' => 7],
            ['name' => '43', 'category_type' => 'shoes', 'sort_order' => 8],
            ['name' => '44', 'category_type' => 'shoes', 'sort_order' => 9],
            ['name' => '45', 'category_type' => 'shoes', 'sort_order' => 10],

            // General sizes
            ['name' => 'Small', 'category_type' => 'general', 'sort_order' => 1],
            ['name' => 'Medium', 'category_type' => 'general', 'sort_order' => 2],
            ['name' => 'Large', 'category_type' => 'general', 'sort_order' => 3],
            ['name' => 'One Size', 'category_type' => 'general', 'sort_order' => 4],
        ];

        foreach ($sizes as $size) {
            Size::create($size);
        }
    }
}
