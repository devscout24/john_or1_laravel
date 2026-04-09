<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Drama',
            'Action',
            'Romance',
            'Comedy',
            'Horror',
            'Thriller',
            'Sci-Fi',
            'Fantasy',
            'Anime',
            'Documentary',
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category,
                'slug' => Str::slug($category),
                'icon' => null, // you can add later
                'is_active' => true,
            ]);
        }
    }
}
