<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDailyTaskState extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_task_id',
        'task_date',
        'progress',
        'is_claimed',
        'claimed_at',
    ];

    protected $casts = [
        'task_date' => 'date',
        'progress' => 'integer',
        'is_claimed' => 'boolean',
        'claimed_at' => 'datetime',
    ];
}
