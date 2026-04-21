<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->select('id')->get();

        if ($users->isEmpty()) {
            return;
        }

        $templates = [
            [
                'title' => 'New Episode Available',
                'message' => 'Love Against Time - Episode 5 is now available!',
                'type' => 'new_episode',
                'icon_key' => 'play_icon',
                'reference_type' => 'episode',
            ],
            [
                'title' => 'Daily Reward Earned',
                'message' => "You've earned 50 free coins! Log in daily to earn more.",
                'type' => 'daily_reward',
                'icon_key' => 'coin_icon',
                'reference_type' => 'reward',
            ],
            [
                'title' => 'New Series Alert',
                'message' => "Check out 'Royal Hearts' - A historical romance drama just released!",
                'type' => 'series_alert',
                'icon_key' => 'star_icon',
                'reference_type' => 'content',
            ],
            [
                'title' => 'Continue Watching',
                'message' => 'Shadow Detective Episode 2 - Pick up where you left off at 65%',
                'type' => 'continue_watching',
                'icon_key' => 'play_icon',
                'reference_type' => 'episode',
            ],
        ];

        foreach ($users as $user) {
            foreach ($templates as $index => $template) {
                Notification::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => $template['type'],
                        'title' => $template['title'],
                    ],
                    [
                        'message' => $template['message'],
                        'icon_key' => $template['icon_key'],
                        'is_read' => $index >= 2,
                        'read_at' => $index >= 2 ? now()->subDay() : null,
                        'reference_id' => null,
                        'reference_type' => $template['reference_type'],
                    ]
                );
            }
        }
    }
}
