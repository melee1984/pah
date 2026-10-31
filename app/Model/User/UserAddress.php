<?php

namespace App\Model\User;

use App\City;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class UserAddress extends Model
{
    protected $table = 'user_address';

    protected $fillable = ['user_id', 'active', 'address_1', 'mobile', 'landmark', 'city_id', 'lat', 'long', 'default', 'title'];

    public $timestamps = true;

    protected $casts = [
        'active' => 'boolean',
        'default' => 'boolean',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function makeDefault(): self
    {
        return DB::transaction(function () {
            // Serialize default changes for this customer so two nearly
            // simultaneous address submissions cannot both remain default.
            static::query()
                ->where('user_id', $this->user_id)
                ->lockForUpdate()
                ->get(['id']);

            static::query()
                ->where('user_id', $this->user_id)
                ->whereKeyNot($this->getKey())
                ->update(['default' => false]);

            $this->forceFill(['default' => true])->save();

            return $this->refresh();
        });
    }
}
