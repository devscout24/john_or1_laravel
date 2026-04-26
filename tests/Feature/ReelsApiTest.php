<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Episode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReelsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_reels_do_not_include_coin_or_ad_locked_episodes(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $content = Content::create([
            'title' => 'Reel Series',
            'type' => 'series',
            'access_type' => 'free',
            'coins_required' => 0,
            'is_active' => true,
        ]);

        $freeEpisode = Episode::create([
            'content_id' => $content->id,
            'title' => 'Free Episode',
            'episode_number' => 1,
            'access_type' => 'free',
            'coins_required' => 0,
            'video_type' => 'external',
            'video_url' => 'https://example.com/free.m3u8',
            'is_active' => true,
        ]);

        Episode::create([
            'content_id' => $content->id,
            'title' => 'Coin Episode',
            'episode_number' => 2,
            'access_type' => 'coins',
            'coins_required' => 10,
            'video_type' => 'external',
            'video_url' => 'https://example.com/coin.m3u8',
            'is_active' => true,
        ]);

        Episode::create([
            'content_id' => $content->id,
            'title' => 'Ad Episode',
            'episode_number' => 3,
            'access_type' => 'ads',
            'coins_required' => 0,
            'video_type' => 'external',
            'video_url' => 'https://example.com/ad.m3u8',
            'is_active' => true,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/reels');

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.episode_id', $freeEpisode->id)
            ->assertJsonMissing(['episode_title' => 'Coin Episode'])
            ->assertJsonMissing(['episode_title' => 'Ad Episode']);
    }
}
