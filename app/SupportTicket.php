<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    public const CATEGORIES = ['Orders & Delivery', 'Billing & Payments', 'Refunds & Cancellations', 'Account Issues', 'General Inquiries'];

    public const STATUSES = ['Open', 'In Progress', 'Waiting for Customer', 'Resolved', 'Closed'];

    public const PRIORITIES = ['Low', 'Normal', 'High', 'Urgent'];

    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'integer',
        'assigned_to' => 'integer',
        'order_id' => 'integer',
        'unread' => 'integer',
    ];
}
