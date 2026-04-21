<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWatchDramaRewardState extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reward_date',
        'watched_seconds',
        'claimed_milestones',
    ];

    protected $casts = [
        'reward_date' => 'date',
        'watched_seconds' => 'integer',
        'claimed_milestones' => 'array',
    ];
}
