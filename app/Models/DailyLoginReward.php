<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyLoginReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'day',
        'coins',
        'is_active',
    ];

    protected $casts = [
        'day' => 'integer',
        'coins' => 'integer',
        'is_active' => 'boolean',
    ];
}
