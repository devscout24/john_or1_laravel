<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Content;
use App\Models\Category;
use App\Models\Section;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $contents = [
            [
                'title' => 'Naruto Shippuden',
                'type' => 'series',
                'access_type' => 'free',
                'coins_required' => 0,
                'categories' => ['Anime', 'Action'],
                'sections' => ['trending'],
            ],
            [
                'title' => 'Korean Drama Love',
                'type' => 'series',
                'access_type' => 'coins',
                'coins_required' => 50,
                'categories' => ['Drama', 'Romance'],
                'sections' => ['recommended'],
            ],
            [
                'title' => 'Avengers Endgame',
                'type' => 'movie',
                'access_type' => 'subscription',
                'coins_required' => 0,
                'categories' => ['Action', 'Sci-Fi'],
                'sections' => ['trending'],
            ],
            [
                'title' => 'Horror Night',
                'type' => 'movie',
                'access_type' => 'ads',
                'coins_required' => 0,
                'categories' => ['Horror', 'Thriller'],
                'sections' => ['new_releases'],
            ],
            [
                'title' => 'Comedy Show',
                'type' => 'series',
                'access_type' => 'free',
                'coins_required' => 0,
                'categories' => ['Comedy'],
                'sections' => ['recommended'],
            ],
        ];

        foreach ($contents as $item) {

            $content = Content::create([
                'title' => $item['title'],
                'description' => $item['title'] . ' description...',
                'type' => $item['type'],
                'thumbnail' => null,
                'banner' => null,
                'access_type' => $item['access_type'],
                'coins_required' => $item['coins_required'],
                'is_active' => true,
            ]);

            // Attach Categories
            $categoryIds = Category::whereIn('name', $item['categories'])->pluck('id');
            $content->categories()->sync($categoryIds);

            // Attach Sections
            $sectionIds = Section::whereIn('slug', $item['sections'])->pluck('id');

            foreach ($sectionIds as $sectionId) {
                $content->sections()->attach($sectionId, [
                    'order' => rand(1, 10),
                ]);
            }
        }
    }
}
