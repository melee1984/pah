<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Agent extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'commission_percentage',
        'active',
        'last_login_at',
        'must_change_password',
        'temporary_password_created_at',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'must_change_password' => 'boolean',
            'commission_percentage' => 'decimal:2',
            'last_login_at' => 'datetime',
            'temporary_password_created_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Partners::class, 'agent_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AgentCommission::class);
    }
}
