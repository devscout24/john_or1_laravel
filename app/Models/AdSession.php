<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_id',
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

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
