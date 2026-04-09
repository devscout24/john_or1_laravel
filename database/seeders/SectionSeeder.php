<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = [
            [
                'name' => 'Trending Now',
                'slug' => 'trending',
                'order' => 1,
            ],
            [
                'name' => 'New Releases',
                'slug' => 'new_releases',
                'order' => 2,
            ],
            [
                'name' => 'You Might Like',
                'slug' => 'recommended',
                'order' => 3,
            ],
        ];

        foreach ($sections as $section) {
            Section::create([
                'name' => $section['name'],
                'slug' => $section['slug'],
                'order' => $section['order'],
                'is_active' => true,
            ]);
        }
    }
}
