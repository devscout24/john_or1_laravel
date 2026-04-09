<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WatchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_id',
        'episode_id',
        'progress',
        'last_watched',
    ];

    protected $casts = [
        'progress' => 'integer',
        'last_watched' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}
