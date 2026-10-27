<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantInvitation extends Model
{
    protected $fillable = [
        'user_id',
        'restaurant_id',
        'email',
        'token_hash',
        'expires_at',
        'accepted_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Partners::class, 'restaurant_id');
    }

    public function isValid(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
