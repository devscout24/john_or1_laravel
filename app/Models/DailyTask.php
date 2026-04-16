<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'action_type',
        'target_count',
        'coins',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'target_count' => 'integer',
        'coins' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
