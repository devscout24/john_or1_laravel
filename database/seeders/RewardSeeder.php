<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reward;

class RewardSeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            [
                'title' => 'Watch a Video',
                'description' => 'Watch any video and earn coins',
                'coins' => 10,
                'action_type' => 'watch',
            ],
            [
                'title' => 'Follow us on TikTok',
                'description' => 'Follow our TikTok page to earn rewards',
                'coins' => 50,
                'action_type' => 'follow',
            ],
            [
                'title' => 'Rate our App',
                'description' => 'Give us a rating on App Store or Play Store',
                'coins' => 100,
                'action_type' => 'rate',
            ],
            [
                'title' => 'Invite a Friend',
                'description' => 'Invite a friend and earn coins',
                'coins' => 200,
                'action_type' => 'invite',
            ],
        ];

        foreach ($rewards as $reward) {
            Reward::create([
                'title' => $reward['title'],
                'description' => $reward['description'],
                'coins' => $reward['coins'],
                'action_type' => $reward['action_type'],
                'is_active' => true,
            ]);
        }
    }
}
