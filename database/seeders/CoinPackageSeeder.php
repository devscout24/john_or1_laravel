<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CoinPackage;

class CoinPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'coins' => 100,
                'price' => 0.99,
                'platform' => 'ios',
                'product_id' => 'coins_100_ios',
            ],
            [
                'coins' => 500,
                'price' => 3.99,
                'platform' => 'ios',
                'product_id' => 'coins_500_ios',
            ],
            [
                'coins' => 1000,
                'price' => 6.99,
                'platform' => 'ios',
                'product_id' => 'coins_1000_ios',
            ],

            [
                'coins' => 100,
                'price' => 0.99,
                'platform' => 'android',
                'product_id' => 'coins_100_android',
            ],
            [
                'coins' => 500,
                'price' => 3.99,
                'platform' => 'android',
                'product_id' => 'coins_500_android',
            ],
            [
                'coins' => 1000,
                'price' => 6.99,
                'platform' => 'android',
                'product_id' => 'coins_1000_android',
            ],
        ];

        foreach ($packages as $package) {
            CoinPackage::create([
                'coins' => $package['coins'],
                'price' => $package['price'],
                'platform' => $package['platform'],
                'product_id' => $package['product_id'],
                'is_active' => true,
            ]);
        }
    }
}
