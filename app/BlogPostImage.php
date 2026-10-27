<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlogPostImage extends Model
{
    protected $fillable = [
        'blog_post_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'alt_text',
        'sort_order',
    ];

    public function post()
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function getUrlAttribute(): string
    {
        return route('blog.images.show', $this);
    }
}
