<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Content;
use App\Models\Favorite;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedSeriesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_series_api_returns_user_favorites(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $category = Category::create([
            'name' => 'Romance',
            'slug' => 'romance',
            'is_active' => true,
        ]);

        $series = Content::create([
            'title' => 'Love Against Time',
            'description' => 'Saved series test',
            'type' => 'series',
            'access_type' => 'free',
            'coins_required' => 0,
            'is_active' => true,
        ]);

        $series->categories()->attach($category->id);

        Favorite::create([
            'user_id' => $user->id,
            'content_id' => $series->id,
        ]);

        WatchHistory::create([
            'user_id' => $user->id,
            'content_id' => $series->id,
            'episode_id' => null,
            'progress' => 120,
            'last_watched' => now(),
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/saved-series');

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.title', 'Love Against Time')
            ->assertJsonPath('data.items.0.genre', 'Romance')
            ->assertJsonPath('data.items.0.is_saved', true);
    }

    public function test_saved_series_add_and_remove_apis_work(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $series = Content::create([
            'title' => 'Toggle Series',
            'description' => 'toggle flow',
            'type' => 'series',
            'access_type' => 'free',
            'coins_required' => 0,
            'is_active' => true,
        ]);

        $saveResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/saved-series/' . $series->id . '/add');

        $saveResponse
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.content_id', $series->id)
            ->assertJsonPath('data.is_saved', true);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'content_id' => $series->id,
        ]);

        $removeResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/saved-series/' . $series->id . '/remove');

        $removeResponse
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.content_id', $series->id)
            ->assertJsonPath('data.is_saved', false);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'content_id' => $series->id,
        ]);
    }

    public function test_saved_series_add_requires_authentication(): void
    {
        $series = Content::create([
            'title' => 'Toggle Auth',
            'description' => 'auth check',
            'type' => 'series',
            'access_type' => 'free',
            'coins_required' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/saved-series/' . $series->id . '/add');

        $response
            ->assertStatus(401)
            ->assertJsonPath('status', false);
    }

    public function test_saved_series_remove_requires_authentication(): void
    {
        $series = Content::create([
            'title' => 'Remove Auth',
            'description' => 'auth check remove',
            'type' => 'series',
            'access_type' => 'free',
            'coins_required' => 0,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/saved-series/' . $series->id . '/remove');

        $response
            ->assertStatus(401)
            ->assertJsonPath('status', false);
    }
}
