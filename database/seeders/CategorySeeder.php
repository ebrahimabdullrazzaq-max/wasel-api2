<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Groceries',
            'Restaurants',
            'Pharmacy',
            'Electronics',
            'Flowers & Gifts',
            'Documents & Parcels',
            'Clothing',
            'Furniture',
            'Home & Kitchen',
            'Beauty & Cosmetics',
            'Pets & Animals',
            'Books & Stationery'
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['name' => $category],
                [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}