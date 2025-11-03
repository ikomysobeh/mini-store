<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Books', 'slug' => 'books', 'description' => 'All kinds of books', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Gadgets and devices', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Apparel for all', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Donations', 'slug' => 'donations', 'description' => 'Support charitable causes', 'is_active' => true, 'sort_order' => 4],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}
