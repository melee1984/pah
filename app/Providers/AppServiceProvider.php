<?php

namespace App\Providers;

use App\Events\SendPushNotificationEvent;
use App\Listeners\SendPushListerner;
use App\Model\Orders\Orders;
use App\Observers\OrderObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('qr-sessions', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('qr-checkout', fn (Request $request) => Limit::perMinute(10)->by($request->route('sessionId')));
        RateLimiter::for('qr-payment-status', fn (Request $request) => Limit::perMinute(20)->by($request->route('sessionId')));

        Vite::prefetch(concurrency: 3);
        Orders::observe(OrderObserver::class);
        Event::listen(SendPushNotificationEvent::class, SendPushListerner::class);
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $html = $event->message->getHtmlBody();

            if (is_string($html) && str_contains($html, 'cid:pahatud-logo@pahatud')) {
                $event->message->addPart(
                    (new DataPart(new File(public_path('images/logo.jpg')), 'pahatud-logo.jpg', 'image/jpeg'))
                        ->asInline()
                        ->setContentId('pahatud-logo@pahatud')
                );
            }
        });
    }
}
