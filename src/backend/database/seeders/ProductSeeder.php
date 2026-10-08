<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Category::all() as $category) {
            for ($i = 1; $i <= 8; $i++) {
                $name = "{$category->name} Item {$i}";
                Product::updateOrCreate(
                    ['sku' => strtoupper(Str::slug($category->name)) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT)],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'slug' => Str::slug($name) . '-' . Str::random(4),
                        'description' => "Description for {$name}",
                        'price' => rand(1000, 50000) / 100,
                        'stock_quantity' => rand(0, 100),
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}