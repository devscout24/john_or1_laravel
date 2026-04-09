<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Content;
use App\Models\Episode;
use App\Models\Favorite;
use App\Models\WatchHistory;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@user.com')->first();

        if (!$user) {
            return;
        }

        // ❤️ Favorites
        $contents = Content::take(3)->get();

        foreach ($contents as $content) {
            Favorite::firstOrCreate([
                'user_id' => $user->id,
                'content_id' => $content->id,
            ]);
        }

        // 🎬 Watch History
        $episodes = Episode::take(3)->get();

        foreach ($episodes as $episode) {
            WatchHistory::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'content_id' => $episode->content_id,
                    'episode_id' => $episode->id,
                ],
                [
                    'progress' => rand(100, 500),
                    'last_watched' => now(),
                ]
            );
        }
    }
}
