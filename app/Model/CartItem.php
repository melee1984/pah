<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    public const UNAVAILABLE_ACTION_REMOVE_ITEM = 0;

    public const UNAVAILABLE_ACTION_CALL_ME = 1;

    public const UNAVAILABLE_ACTION_REPLACE_SIMILAR = 2;

    public const UNAVAILABLE_ITEM_ACTIONS = [
        self::UNAVAILABLE_ACTION_REMOVE_ITEM => 'remove_item',
        self::UNAVAILABLE_ACTION_CALL_ME => 'call_me',
        self::UNAVAILABLE_ACTION_REPLACE_SIMILAR => 'replace_similar',
    ];

    public static function resolveUnavailableItemAction(mixed $action): ?int
    {
        if (is_int($action) || (is_string($action) && ctype_digit($action))) {
            $action = (int) $action;

            return array_key_exists($action, self::UNAVAILABLE_ITEM_ACTIONS)
                ? $action
                : null;
        }

        $value = array_search($action, self::UNAVAILABLE_ITEM_ACTIONS, true);

        return $value === false ? null : $value;
    }

    protected $table = 'cart_details';

    protected $fillable = ['cart_id', 'item_id', 'qty', 'price', 'variance_total', 'variance_content', 'is_not_available', 'instruction', 'price_comm_total', 'variance_total_comm_total', 'discount_amount'];

    protected $casts = [
        'is_not_available' => 'integer',
    ];

    public $timestamps = true;

    public function item()
    {
        return $this->hasOne('App\Products', 'id', 'item_id');
    }
}
