<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'category_id',
    ];
}
