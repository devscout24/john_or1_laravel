<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoinPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'coins',
        'price',
        'platform',
        'product_id',
        'is_active',
    ];

    protected $casts = [
        'coins' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
