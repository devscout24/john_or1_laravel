<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [

        // Company Info
        'company_name',
        'website',
        'email',
        'hotline',
        'address',
        'description',
        'logo',

        // App Links
        'play_store_link',
        'apple_store_link',

        // Social
        'facebook',
        'linkedin',
        'youtube',
        'twitter',
        'tiktok',
        'threads',

        // Feature Toggles
        'daily_tasks_enabled',
        'referral_system_enabled',
    ];

    protected $casts = [
        'daily_tasks_enabled' => 'boolean',
        'referral_system_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'daily_tasks_enabled' => true,
                'referral_system_enabled' => true,
            ]
        );
    }

    public static function dailyTasksEnabled(): bool
    {
        return (bool) static::current()->daily_tasks_enabled;
    }

    public static function referralSystemEnabled(): bool
    {
        return (bool) static::current()->referral_system_enabled;
    }
}
