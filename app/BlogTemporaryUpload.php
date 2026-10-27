<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlogTemporaryUpload extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
