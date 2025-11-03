<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $categories = Category::all()->keyBy('slug');

        $products = [
            [
                'name' => 'Laravel Best Practices',
                'slug' => 'laravel-best-practices',
                'description' => 'A comprehensive guide to Laravel development.',
                'price' => 29.99,
                'stock' => 100,
                'category_id' => $categories['books']->id,
                'is_active' => true,
                'is_donatable' => false,
            ],
            [
                'name' => 'Wireless Headphones',
                'slug' => 'wireless-headphones',
                'description' => 'High quality wireless over-ear headphones.',
                'price' => 99.99,
                'stock' => 50,
                'category_id' => $categories['electronics']->id,
                'is_active' => true,
                'is_donatable' => false,
            ],
            [
                'name' => 'Organic Cotton T-Shirt',
                'slug' => 'organic-cotton-tshirt',
                'description' => 'Soft and sustainable cotton shirt.',
                'price' => 19.99,
                'stock' => 200,
                'category_id' => $categories['clothing']->id,
                'is_active' => true,
                'is_donatable' => false,
            ],
            [
                'name' => 'Support Education Fund',
                'slug' => 'support-education-fund',
                'description' => 'Make a donation to support education.',
                'price' => 5.00,
                'stock' => 0,
                'category_id' => $categories['donations']->id,
                'is_active' => true,
                'is_donatable' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
