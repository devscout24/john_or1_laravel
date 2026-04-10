<?php

namespace Tests\Feature;

use App\Models\AdSession;
use App\Models\Content;
use App\Models\Episode;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoverActionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unlock_with_coins_deducts_balance_and_creates_transaction(): void
    {
        $user = User::factory()->create(['coins' => 100]);
        $token = auth('api')->login($user);

        $content = Content::create([
            'title' => 'Coins Locked Content',
            'type' => 'series',
            'access_type' => 'coins',
            'coins_required' => 60,
            'is_active' => true,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/discover/' . $content->id . '/unlock-with-coins');

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.access.can_watch', true)
            ->assertJsonPath('data.user_coins', 40);

        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $user->id,
            'type' => 'spend',
            'source' => 'unlock_content',
            'reference_id' => $content->id,
            'amount' => 60,
        ]);

        $this->assertSame(40, (int) $user->fresh()->coins);
    }

    public function test_unlock_with_ad_creates_or_updates_ad_session(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $content = Content::create([
            'title' => 'Ad Locked Content',
            'type' => 'movie',
            'access_type' => 'ads',
            'coins_required' => 0,
            'is_active' => true,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/discover/' . $content->id . '/unlock-with-ad');

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.access.can_watch', true)
            ->assertJsonPath('data.required_ads', 1);

        $session = AdSession::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->first();

        $this->assertNotNull($session);
        $this->assertSame(1, (int) $session->ads_watched);
        $this->assertNotNull($session->unlocked_until);
    }

    public function test_episode_watch_progress_creates_history_when_unlocked(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $content = Content::create([
            'title' => 'Free Content',
            'type' => 'series',
            'access_type' => 'free',
            'coins_required' => 0,
            'is_active' => true,
        ]);

        $episode = Episode::create([
            'content_id' => $content->id,
            'title' => 'Episode 1',
            'episode_number' => 1,
            'duration' => 1200,
            'video_type' => 'external',
            'video_url' => 'https://example.com/video.m3u8',
            'is_active' => true,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/episodes/' . $episode->id . '/watch-progress', [
                'progress' => 600,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.progress_percent', 50)
            ->assertJsonPath('data.is_completed', false);

        $history = WatchHistory::where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->where('episode_id', $episode->id)
            ->first();

        $this->assertNotNull($history);
        $this->assertSame(600, (int) $history->progress);
    }
}
