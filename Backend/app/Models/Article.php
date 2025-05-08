<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'description',
        'publisher_name',
        'publisher_image',
        'article_image',
        'published_at',
    ];

}
