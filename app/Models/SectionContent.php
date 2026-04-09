<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SectionContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'content_id',
        'order',
    ];
}
