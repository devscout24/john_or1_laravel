<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'title',
        'episode_number',
        'access_type',
        'coins_required',
        'video_type',
        'video_url',
        'storage_path',
        'duration',
        'is_active',
    ];

    protected $casts = [
        'episode_number' => 'integer',
        'coins_required' => 'integer',
        'duration' => 'integer',
        'is_active' => 'boolean',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function watchHistories()
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function likes()
    {
        return $this->hasMany(EpisodeLike::class);
    }

    public function gifts()
    {
        return $this->hasMany(EpisodeGift::class);
    }

    public function savedByUsers()
    {
        return $this->hasMany(SavedEpisode::class);
    }
}
