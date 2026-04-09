<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Jetstream\HasProfilePhoto;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements JWTSubject
{
    use HasRoles;
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;

    protected $guarded = [
        'id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'coins' => 'integer',
        ];
    }


    // implement 2 methods for token get
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }

    // Watch history
    // public function watchHistories()
    // {
    //     return $this->hasMany(WatchHistory::class);
    // }

    // // Favorites
    // public function favorites()
    // {
    //     return $this->hasMany(Favorite::class);
    // }

    // // Subscriptions
    // public function subscriptions()
    // {
    //     return $this->hasMany(Subscription::class);
    // }

    // // Coin transactions
    // public function coinTransactions()
    // {
    //     return $this->hasMany(CoinTransaction::class);
    // }

    // // Rewards
    // public function rewards()
    // {
    //     return $this->hasMany(UserReward::class);
    // }

    // // Ad sessions
    // public function adSessions()
    // {
    //     return $this->hasMany(AdSession::class);
    // }
}
