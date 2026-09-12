<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderApplicationDocument extends Model
{
    public const TYPES = [
        'profile_photo',
        'government_id',
        'drivers_license',
        'vehicle_registration',
    ];

    protected $fillable = [
        'reference',
        'type',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected $hidden = [
        'path',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RiderApplication::class, 'rider_application_id');
    }
}
