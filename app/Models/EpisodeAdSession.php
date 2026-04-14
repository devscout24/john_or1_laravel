<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpisodeAdSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'episode_id',
        'ads_watched',
        'unlocked_until',
    ];

    protected $casts = [
        'ads_watched' => 'integer',
        'unlocked_until' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}
