<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CoinTransaction;
use App\Models\Content;
use App\Models\Episode;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoverDetailsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_discover_detail_returns_content_access_and_episodes(): void
    {
        $user = User::factory()->create(['coins' => 90]);
        $token = auth('api')->login($user);

        $category = Category::create([
            'name' => 'Drama',
            'slug' => 'drama',
            'is_active' => true,
        ]);

        $content = Content::create([
            'title' => 'Love Against Time',
            'description' => 'Drama reel details',
            'type' => 'series',
            'access_type' => 'coins',
            'coins_required' => 60,
            'is_active' => true,
        ]);

        $content->categories()->attach($category->id);

        $episodeOne = Episode::create([
            'content_id' => $content->id,
            'title' => 'Episode 1',
            'episode_number' => 1,
            'duration' => 1200,
            'video_type' => 'external',
            'video_url' => 'https://example.com/ep1.m3u8',
            'is_active' => true,
        ]);

        Episode::create([
            'content_id' => $content->id,
            'title' => 'Episode 2',
            'episode_number' => 2,
            'duration' => 900,
            'video_type' => 'external',
            'video_url' => 'https://example.com/ep2.m3u8',
            'is_active' => true,
        ]);

        WatchHistory::create([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'episode_id' => $episodeOne->id,
            'progress' => 600,
            'last_watched' => now(),
        ]);

        CoinTransaction::create([
            'user_id' => $user->id,
            'type' => 'spend',
            'amount' => 60,
            'source' => 'unlock_content',
            'reference_id' => $content->id,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/discover/' . $content->id);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.content.title', 'Love Against Time')
            ->assertJsonPath('data.access.access_type', 'coins')
            ->assertJsonPath('data.access.can_watch', true)
            ->assertJsonPath('data.episodes.0.is_locked', false)
            ->assertJsonPath('data.episodes.0.progress_percent', 50);
    }

    public function test_discover_detail_requires_authentication(): void
    {
        $content = Content::create([
            'title' => 'Auth Required Content',
            'type' => 'movie',
            'access_type' => 'free',
            'coins_required' => 0,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/discover/' . $content->id);

        $response->assertStatus(401)->assertJsonPath('status', false);
    }
}
