<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Content;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoverApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_discover_api_returns_expected_sections_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $action = Category::create([
            'name' => 'Action',
            'slug' => 'action',
            'is_active' => true,
        ]);

        $drama = Category::create([
            'name' => 'Drama',
            'slug' => 'drama',
            'is_active' => true,
        ]);

        $trending = Section::create([
            'name' => 'Trending Now',
            'slug' => 'trending',
            'is_active' => true,
            'order' => 1,
        ]);

        $newReleases = Section::create([
            'name' => 'New Releases',
            'slug' => 'new_releases',
            'is_active' => true,
            'order' => 2,
        ]);

        $recommended = Section::create([
            'name' => 'You Might Like',
            'slug' => 'recommended',
            'is_active' => true,
            'order' => 3,
        ]);

        $actionMovie = Content::create([
            'title' => 'Action Blast',
            'description' => 'Action packed movie',
            'type' => 'movie',
            'access_type' => 'free',
            'coins_required' => 0,
            'is_active' => true,
        ]);
        $actionMovie->categories()->attach($action->id);
        $actionMovie->sections()->attach($trending->id, ['order' => 1]);

        $dramaSeries = Content::create([
            'title' => 'Drama Mood',
            'description' => 'Emotional drama series',
            'type' => 'series',
            'access_type' => 'coins',
            'coins_required' => 5,
            'is_active' => true,
        ]);
        $dramaSeries->categories()->attach($drama->id);
        $dramaSeries->sections()->attach($newReleases->id, ['order' => 1]);

        $recommendedItem = Content::create([
            'title' => 'Action Again',
            'description' => 'Another action title',
            'type' => 'movie',
            'access_type' => 'subscription',
            'coins_required' => 0,
            'is_active' => true,
        ]);
        $recommendedItem->categories()->attach($action->id);
        $recommendedItem->sections()->attach($recommended->id, ['order' => 1]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/discover?category=action&type=movie');

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.sections.trending_now.items.0.title', 'Action Blast')
            ->assertJsonPath('data.sections.new_releases.items', [])
            ->assertJsonPath('data.sections.you_might_also_like.items.0.title', 'Action Again');
    }

    public function test_discover_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/discover');

        $response
            ->assertStatus(401)
            ->assertJsonPath('status', false);
    }
}
