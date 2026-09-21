<?php

namespace App\Services;

use App\Mail\RestaurantApplicationStatusMail;
use App\Partners;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RestaurantApplicationNotifier
{
    public function send(Partners $restaurant, string $message, ?string $remarks = null): void
    {
        $recipients = [
            [$restaurant->email, $restaurant->restaurant_name, route('merchant.application.show')],
        ];

        if ($restaurant->agent_id && $restaurant->agent) {
            $recipients[] = [$restaurant->agent->email, $restaurant->agent->name,
                route('agent.restaurants.show', $restaurant)];
        }

        foreach ($recipients as [$email, $name, $url]) {
            if (! $email) {
                continue;
            }

            try {
                Mail::to($email)->send(new RestaurantApplicationStatusMail(
                    $name,
                    $restaurant->restaurant_name,
                    $message,
                    $remarks,
                    $url,
                ));
            } catch (Throwable $exception) {
                Log::error('Restaurant application update email could not be delivered.', [
                    'restaurant_id' => $restaurant->id,
                    'email' => $email,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }
}
