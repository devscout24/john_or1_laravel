<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Monthly Plan',
                'price' => 4.99,
                'duration_days' => 30,
            ],
            [
                'name' => 'Quarterly Plan',
                'price' => 12.99,
                'duration_days' => 90,
            ],
            [
                'name' => 'Yearly Plan',
                'price' => 39.99,
                'duration_days' => 365,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create([
                'name' => $plan['name'],
                'price' => $plan['price'],
                'duration_days' => $plan['duration_days'],
                'is_active' => true,
            ]);
        }
    }
}
