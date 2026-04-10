<?php

namespace Tests\Feature;

use App\Models\CoinPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinStoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_coin_store_api_returns_balance_and_platform_packages(): void
    {
        $user = User::factory()->create([
            'coins' => 250,
        ]);

        $token = auth('api')->login($user);

        CoinPackage::create([
            'coins' => 100,
            'price' => 0.99,
            'platform' => 'ios',
            'product_id' => 'coins_100_ios',
            'is_active' => true,
        ]);

        CoinPackage::create([
            'coins' => 500,
            'price' => 3.99,
            'platform' => 'ios',
            'product_id' => 'coins_500_ios',
            'is_active' => true,
        ]);

        CoinPackage::create([
            'coins' => 1000,
            'price' => 6.99,
            'platform' => 'android',
            'product_id' => 'coins_1000_android',
            'is_active' => true,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/coin-store?platform=ios');

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.balance.coins', 250)
            ->assertJsonPath('data.filters.platform', 'ios')
            ->assertJsonCount(2, 'data.packages')
            ->assertJsonPath('data.packages.0.platform', 'ios');
    }

    public function test_coin_store_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/coin-store');

        $response
            ->assertStatus(401)
            ->assertJsonPath('status', false);
    }
}
