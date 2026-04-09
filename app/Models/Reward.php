<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'coins',
        'action_type',
        'is_active',
    ];

    protected $casts = [
        'coins' => 'integer',
        'is_active' => 'boolean',
    ];
    public function userRewards()
    {
        return $this->hasMany(UserReward::class);
    }
}
