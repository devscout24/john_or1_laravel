<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Episode;
use App\Models\Content;

class EpisodeSeeder extends Seeder
{
    public function run(): void
    {
        $contents = Content::all();

        foreach ($contents as $content) {

            // 🎬 MOVIE → only 1 episode
            if ($content->type === 'movie') {

                Episode::create([
                    'content_id' => $content->id,
                    'title' => $content->title,
                    'episode_number' => null,
                    'video_type' => 'external',
                    'video_url' => 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8',
                    'duration' => rand(3000, 7200),
                    'is_active' => true,
                ]);
            }

            // 📺 SERIES → multiple episodes
            if ($content->type === 'series') {

                $totalEpisodes = rand(3, 5);

                for ($i = 1; $i <= $totalEpisodes; $i++) {

                    Episode::create([
                        'content_id' => $content->id,
                        'title' => $content->title . ' Episode ' . $i,
                        'episode_number' => $i,
                        'video_type' => 'external',
                        'video_url' => 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8',
                        'duration' => rand(1200, 3600),
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
