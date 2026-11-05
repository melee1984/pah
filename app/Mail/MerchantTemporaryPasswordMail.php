<?php

namespace App\Mail;

use App\Partners;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MerchantTemporaryPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $loginUrl;

    public string $merchantName;

    public string $restaurantName;

    public string $emailAddress;

    public function __construct(User $user, Partners $restaurant, public string $temporaryPassword)
    {
        $this->loginUrl = route('merchant.login');
        $this->merchantName = trim($user->firstname.' '.$user->lastname) ?: $restaurant->restaurant_name;
        $this->restaurantName = $restaurant->restaurant_name;
        $this->emailAddress = (string) $user->email;
    }

    public function build(): self
    {
        return $this->markdown('emails.merchantTemporaryPassword')
            ->subject('Your Pahatud Merchant Portal account');
    }
}
