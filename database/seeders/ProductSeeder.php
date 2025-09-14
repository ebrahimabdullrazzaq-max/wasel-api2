<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::first(); // Get existing category

        Product::create([
            'category_id' => $category->id,
            'name' => 'Tomato',
            'description' => 'Fresh red tomatoes',
            'price' => 1.50,
            'stock' => 100,
            'image' => 'tomato.jpg',
        ]);
    }
}
