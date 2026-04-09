<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'thumbnail',
        'banner',
        'access_type',
        'coins_required',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'coins_required' => 'integer',
    ];

    // Episodes (for series)
    // public function episodes()
    // {
    //     return $this->hasMany(Episode::class);
    // }

    // // Categories (many to many)
    // public function categories()
    // {
    //     return $this->belongsToMany(Category::class, 'content_categories');
    // }

    // // Watch history
    // public function watchHistories()
    // {
    //     return $this->hasMany(WatchHistory::class);
    // }

    // // Favorites
    // public function favorites()
    // {
    //     return $this->hasMany(Favorite::class);
    // }

    // // Ad sessions
    // public function adSessions()
    // {
    //     return $this->hasMany(AdSession::class);
    // }
}
