<?php

namespace App\Listeners;

use App\Events\SendPushNotificationEvent;
use App\Jobs\SendMerchantOrderPush;

class SendPushListerner
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(SendPushNotificationEvent $event): void
    {
        SendMerchantOrderPush::dispatch((int) $event->order->id)->afterCommit();
    }
}
