<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_id',
        'ad_provider',
        'watched_at',
    ];

    protected $casts = [
        'watched_at' => 'datetime',
    ];
}
