<?php
// database/seeders/ColorSeeder.php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'Red', 'hex_code' => '#FF0000', 'sort_order' => 1],
            ['name' => 'Blue', 'hex_code' => '#0000FF', 'sort_order' => 2],
            ['name' => 'Green', 'hex_code' => '#008000', 'sort_order' => 3],
            ['name' => 'Black', 'hex_code' => '#000000', 'sort_order' => 4],
            ['name' => 'White', 'hex_code' => '#FFFFFF', 'sort_order' => 5],
            ['name' => 'Yellow', 'hex_code' => '#FFFF00', 'sort_order' => 6],
            ['name' => 'Purple', 'hex_code' => '#800080', 'sort_order' => 7],
            ['name' => 'Orange', 'hex_code' => '#FFA500', 'sort_order' => 8],
            ['name' => 'Pink', 'hex_code' => '#FFC0CB', 'sort_order' => 9],
            ['name' => 'Gray', 'hex_code' => '#808080', 'sort_order' => 10],
        ];

        foreach ($colors as $color) {
            Color::create($color);
        }
    }
}
