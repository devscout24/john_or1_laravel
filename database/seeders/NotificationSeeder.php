<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        $customers = User::whereHas('roles', function ($q) {
            $q->where('name', 'customer');
        })->get();

        $providers = User::whereHas('roles', function ($q) {
            $q->where('name', 'provider');
        })->get();

        // Demo notifications for customers
        $customerNotifications = [
            [
                'title' => 'Welcome!',
                'message' => 'Thank you for joining us!',
                'type' => 'welcome'
            ],
            [
                'title' => 'Payment Successful',
                'message' => 'Your payment has been processed successfully.',
                'type' => 'payment_success'
            ]
        ];

        // Demo notifications for providers
        $providerNotifications = [
            [
                'title' => 'Welcome Provider!',
                'message' => 'Welcome to our provider platform!',
                'type' => 'welcome',
                'data' => []
            ]
        ];

        // Send notifications to customers
        foreach ($customers as $customer) {
            foreach ($customerNotifications as $notification) {
                $customer->notify(new GeneralNotification(
                    $notification['title'],
                    $notification['message'],
                    $notification['type'],
                    $notification['data'] ?? []
                ));
            }
        }

        // Send notifications to providers
        foreach ($providers as $provider) {
            foreach ($providerNotifications as $notification) {
                $provider->notify(new GeneralNotification(
                    $notification['title'],
                    $notification['message'],
                    $notification['type'],
                    $notification['data'] ?? []
                ));
            }
        }
    }
}
