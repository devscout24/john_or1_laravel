<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReferralUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'referrer_user_id',
        'referral_code',
        'used_on',
    ];

    protected $casts = [
        'used_on' => 'date',
    ];
}
