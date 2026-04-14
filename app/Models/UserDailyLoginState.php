<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDailyLoginState extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'next_day',
        'last_claimed_day',
        'last_claimed_on',
    ];

    protected $casts = [
        'next_day' => 'integer',
        'last_claimed_day' => 'integer',
        'last_claimed_on' => 'date',
    ];
}
