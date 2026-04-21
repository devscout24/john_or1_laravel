<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpisodeGift extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_user_id',
        'recipient_user_id',
        'episode_id',
        'gift_key',
        'gift_label',
        'coins',
    ];

    protected $casts = [
        'coins' => 'integer',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}
