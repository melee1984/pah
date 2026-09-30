<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminPushNotificationDelivery extends Model
{
    protected $fillable = [
        'notification_id',
        'recipient_type',
        'recipient_id',
        'recipient_label',
        'token_hash',
        'status',
        'provider_message_id',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(AdminPushNotification::class, 'notification_id');
    }
}
